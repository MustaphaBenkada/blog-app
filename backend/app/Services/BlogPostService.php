<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\Tag;
use App\Jobs\SendPublishedPostEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class BlogPostService
{

    protected ImageService $imageService;
    protected CacheService $cacheService;
    protected SearchService $searchService;

    public function __construct(ImageService $imageService, CacheService $cacheService, SearchService $searchService)
    {
        $this->imageService = $imageService;
        $this->cacheService = $cacheService;
        $this->searchService = $searchService;
    }

    public function getPaginatedPosts(int $perPage = 10): LengthAwarePaginator
    {
        return Cache::tags('blog_posts')->remember('blog_posts.' . request('page', 1), 60, function () use ($perPage) {
            return BlogPost::published()->with('tags')->withCount('comments')->latest('published_at')->paginate($perPage);
        });
    }

    public function getAuthorPosts(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return BlogPost::where('user_id', $userId)
                        ->with('tags')
                        ->withCount('comments')
                        ->latest('created_at')
                        ->paginate($perPage);
    }

    public function createPost(array $data, $image = null): BlogPost
    {
        $imagePath = $data['image'] ?? null;
        
        // Determine status based on published_at
        $status = 'draft';
        $publishedAt = null;
        $shouldSendEmail = false;
        
        // Check if published_at is provided and not empty
        if (isset($data['published_at']) && !empty($data['published_at']) && $data['published_at'] !== null) {
            $publishedAt = Carbon::parse($data['published_at'])->setTimezone(config('app.timezone'));
            $status = $publishedAt->isPast() ? 'published' : 'scheduled';
            // Only send email if explicitly published (past date)
            $shouldSendEmail = $publishedAt->isPast();
        } else {
            // If no published_at is provided, publish immediately but don't send email
            $publishedAt = Carbon::now()->setTimezone(config('app.timezone'));
            $status = 'published';
            $shouldSendEmail = false;
        }

        $post = BlogPost::create([
            'user_id' => Auth::id(),
            'title' => $data['title'],
            'excerpt' => $data['excerpt'],
            'description' => $data['description'],
            'image' => $imagePath,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'published_at' => $publishedAt,
            'status' => $status,
        ]);

        if (!empty($data['tags'])) {
            $tagIds = collect($data['tags'])->map(function ($tagName) {
                return Tag::firstOrCreate(['name' => strtolower(trim($tagName))])->id;
            });
            $post->tags()->sync($tagIds);
        }

        // Add post to search index if published
        if ($status === 'published') {
            try {
                $this->searchService->indexPost($post);
            } catch (\Exception $e) {
                // Handle exception
            }
        }

        // Send email notification only when explicitly published (past date)
        if ($shouldSendEmail) {
            $user = Auth::user();
            SendPublishedPostEmail::dispatch($post, $user->email, $user->name);
        }

        return $post->load('tags');
    }

    public function loadPostWithTags(BlogPost $post): BlogPost
    {
        return Cache::remember('blog_post.' . $post->id, 60, function () use ($post) {
            return $post->load(['tags', 'comments.user'])->loadCount('comments');
        });
    }

    public function updatePost(BlogPost $post, array $data, $image = null): BlogPost
    {
         if (isset($data['image']) && $data['image'] !== $post->image) {
            $this->imageService->delete($post->image);

            $post->image = $data['image'] ?? null;
        }

        if (isset($data['image']) && $data['image'] !== $post->image) {
            $this->imageService->delete($post->image);
            $post->image = $data['image'];
        }

        // Store the old status to check if it changed to published
        $oldStatus = $post->status;

        // Determine status based on published_at
        $status = $post->status;
        $publishedAt = $post->published_at;
        $shouldSendEmail = false;
        
        if (isset($data['published_at']) && !empty($data['published_at']) && $data['published_at'] !== null) {
            $publishedAt = Carbon::parse($data['published_at'])->setTimezone(config('app.timezone'));
            $status = $publishedAt->isPast() ? 'published' : 'scheduled';
            // Only send email if explicitly published (past date) and status changed to published
            $shouldSendEmail = $publishedAt->isPast() && $oldStatus !== 'published';
        } else if (isset($data['published_at'])) {
            // If published_at is explicitly set to null/empty, publish immediately but don't send email
            $publishedAt = Carbon::now()->setTimezone(config('app.timezone'));
            $status = 'published';
            $shouldSendEmail = false;
        }

        $post->fill([
            'title' => $data['title'] ?? $post->title,
            'excerpt' => $data['excerpt'] ?? $post->excerpt,
            'description' => $data['description'] ?? $post->description,
            'meta_title' => $data['meta_title'] ?? $post->meta_title,
            'meta_description' => $data['meta_description'] ?? $post->meta_description,
            'published_at' => $publishedAt,
            'status' => $status,
        ]);

        $post->save();

        if (!empty($data['tags'])) {
            $tagIds = collect($data['tags'])->map(function ($tagName) {
                return Tag::firstOrCreate(['name' => strtolower(trim($tagName))])->id;
            });
            $post->tags()->sync($tagIds);
        }

        Cache::forget('blog_post.' . $post->id);

        // Handle search indexing based on status changes
        if ($status === 'published' && $oldStatus !== 'published') {
            // Post was just published - add to search index
            try {
                $this->searchService->indexPost($post);
            } catch (\Exception $e) {
                // Handle exception
            }
        } else if ($oldStatus === 'published' && $status !== 'published') {
            // Post was unpublished - remove from search index
            try {
                $this->searchService->removePost($post->id);
            } catch (\Exception $e) {
                // Handle exception
            }
        } else if ($status === 'published' && $oldStatus === 'published') {
            // Post was already published and still is - update search index
            try {
                $this->searchService->indexPost($post);
            } catch (\Exception $e) {
                // Handle exception
            }
        }

        // Send email notification only when explicitly published (past date)
        if ($shouldSendEmail) {
            $user = Auth::user();
            SendPublishedPostEmail::dispatch($post, $user->email, $user->name);
        }

        return $post->load('tags');
    }

    public function deletePost(BlogPost $post): void
    {
        $this->imageService->delete($post->image);
        $post->tags()->detach();
        
        // Remove post from search index before deleting
        try {
            $this->searchService->removePost($post->id);
        } catch (\Exception $e) {
            // Handle exception
        }
        
        $post->delete();
        
        Cache::forget('blog_post.' . $post->id);
    }
}
