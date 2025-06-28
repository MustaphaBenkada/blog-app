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
            $result = BlogPost::published()->withCount('comments')->orderByDesc('created_at')->paginate($perPage);
            return $result;
        }
        $query = strtolower(trim($query));
        $page = request()->get('page', 1);
        $keys = $this->scanKeys($this->keyPrefix . '*');
        $postIds = [];
        foreach ($keys as $key) {
            $postData = Redis::hgetall($key);
            if (empty($postData)) continue;
            $blob = strtolower(($postData['title'] ?? '') . ' ' . ($postData['description'] ?? '') . ' ' . ($postData['excerpt'] ?? '') . ' ' . ($postData['tags'] ?? ''));
            if (strpos($blob, $query) !== false) {
                $postId = (int) str_replace($this->keyPrefix, '', $key);
                $postIds[] = $postId;
            }
        }
        $total = count($postIds);
        $slicedIds = array_slice($postIds, ($page - 1) * $perPage, $perPage);
        $posts = BlogPost::published()
            ->whereIn('id', $slicedIds)
            ->with('tags')
            ->withCount('comments')
            ->orderByDesc('created_at')
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