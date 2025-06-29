<?php

namespace App\Services;

use App\Models\BlogPost;
use Illuminate\Support\Facades\Redis;

class SearchService
{
    protected string $keyPrefix = 'post:';

    public function indexPost(BlogPost $post): void
    {
        if (!$post->isPublished()) {
            $this->removePost($post->id);
            return;
        }
        $key = $this->keyPrefix . $post->id;
        Redis::del($key);
        
        // Load tags if not already loaded
        if (!$post->relationLoaded('tags')) {
            $post->load('tags');
        }
        
        $data = [
            'title' => $post->title ?? '',
            'description' => $post->description ?? '',
            'excerpt' => $post->excerpt ?? '',
            'tags' => $post->tags->pluck('name')->implode(' '),
        ];
        foreach ($data as $field => $value) {
            Redis::hset($key, $field, $value);
        }
    }

    public function removePost(int $postId): void
    {
        $key = $this->keyPrefix . $postId;
        Redis::del($key);
    }

    public function rebuildIndex(): int
    {
        $keys = Redis::keys($this->keyPrefix . '*');
        if (!empty($keys)) {
            Redis::del(...$keys);
        }
        $count = 0;
        try {
            BlogPost::published()->with('tags')->chunk(500, function ($posts) use (&$count) {
                foreach ($posts as $post) {
                    $this->indexPost($post);
                    $count++;
                }
            });
        } catch (\Throwable $e) {
            // Exception handled silently
        }
        return $count;
    }

   public function search(string $query = null, int $perPage = 10)
    {
        if (empty($query)) {
            $page = request()->get('page', 1);
            return BlogPost::published()
                ->withCount('comments')
                ->orderByDesc('id')
                ->paginate($perPage);
        }

        $query = strtolower(trim($query));
        $page = request()->get('page', 1);

        // Step 1: Scan keys
        $startScan = microtime(true);
        $keys = $this->scanKeys($this->keyPrefix . '*');
        $endScan = microtime(true);
        \Log::info('Redis scanKeys duration: ' . round($endScan - $startScan, 4) . ' seconds');

        // Step 2: Pipelined fetch + filter
        $startFetch = microtime(true);
        $postIds = [];

        // Pipelined hgetall calls
        $allData = Redis::pipeline(function ($pipe) use ($keys) {
            foreach ($keys as $key) {
                $pipe->hgetall($key);
            }
        });

        foreach ($allData as $index => $postData) {
            if (empty($postData)) continue;

            $blob = strtolower(
                ($postData['title'] ?? '') . ' ' .
                ($postData['description'] ?? '') . ' ' .
                ($postData['excerpt'] ?? '') . ' ' .
                ($postData['tags'] ?? '')
            );

            if (strpos($blob, $query) !== false) {
                $postId = (int) str_replace($this->keyPrefix, '', $keys[$index]);
                $postIds[] = $postId;
            }
        }

        $endFetch = microtime(true);
        \Log::info('Redis fetch & filter duration (pipeline): ' . round($endFetch - $startFetch, 4) . ' seconds');

        // Step 3: Paginate filtered results
        $total = count($postIds);
        $slicedIds = array_slice($postIds, ($page - 1) * $perPage, $perPage);
        $posts = BlogPost::published()
            ->whereIn('id', $slicedIds)
            ->with('tags')
            ->withCount('comments')
            ->orderByDesc('id')
            ->get();

        return new \Illuminate\Pagination\LengthAwarePaginator($posts, $total, $perPage, $page);
    }

    protected function scanKeys($pattern)
    {
        $redis = \Illuminate\Support\Facades\Redis::connection()->client();
        $iterator = null;
        $keys = [];
        do {
            $batch = $redis->scan($iterator, $pattern, 1000);
            if ($batch === false) break;
            $keys = array_merge($keys, $batch);
        } while ($iterator > 0);
        return $keys;
    }
} 