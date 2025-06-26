<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    protected string $blogListKey = 'blog:list:';
    protected string $blogKey = 'blog:';
    protected int $defaultTtl = 600; // 10 minutes

    public function getBlogList($cacheKey)
    {
        return Cache::get($this->blogListKey . $cacheKey);
    }

    public function setBlogList($cacheKey, $data, $ttl = null)
    {
        Cache::put($this->blogListKey . $cacheKey, $data, $ttl ?? $this->defaultTtl);
    }

    public function getBlog($id)
    {
        return Cache::get($this->blogKey . $id);
    }

    public function setBlog($id, $data, $ttl = null)
    {
        Cache::put($this->blogKey . $id, $data, $ttl ?? $this->defaultTtl);
    }

    public function invalidateBlogList($cacheKey = null)
    {
        if ($cacheKey) {
            Cache::forget($this->blogListKey . $cacheKey);
        } else {
            $masterKey = $this->blogListKey . 'keys';
            $keys = Cache::get($masterKey, []);
            foreach ($keys as $key) {
                Cache::forget($this->blogListKey . $key);
            }
            Cache::forget($masterKey);
        }
    }

    public function invalidateBlog($id)
    {
        Cache::forget($this->blogKey . $id);
    }

    public function getPaginatedBlogList($page, $perPage)
    {
        $cacheKey = "page_{$page}_per_{$perPage}";
        return $this->getBlogList($cacheKey);
    }

    public function setPaginatedBlogList($page, $perPage, $posts)
    {
        $cacheKey = "page_{$page}_per_{$perPage}";
        $postData = $posts->map(function($post) {
            return (new \App\Http\Resources\BlogPostResource($post))->toArray(request());
        })->all();
        $meta = [
            'current_page' => $posts->currentPage(),
            'last_page' => $posts->lastPage(),
            'per_page' => $posts->perPage(),
            'total' => $posts->total(),
        ];
        $this->setBlogList($cacheKey, [
            'data' => $postData,
            'meta' => $meta
        ]);
        // Track the key in a master list
        $masterKey = $this->blogListKey . 'keys';
        $keys = Cache::get($masterKey, []);
        if (!in_array($cacheKey, $keys)) {
            $keys[] = $cacheKey;
            Cache::forever($masterKey, $keys);
        }
        return [
            'data' => $postData,
            'meta' => $meta
        ];
    }
} 