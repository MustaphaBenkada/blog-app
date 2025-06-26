<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Services\CacheService;

class CommentController extends Controller
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function store(Request $request, BlogPost $post)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $comment = $post->comments()->create([
            'user_id' => Auth::id(),
            'content' => $request->content,
        ]);

        // Clear cache for this specific post to update comments_count
        Cache::forget('blog_post.' . $post->id);
        
        // Invalidate blog list cache to update comments_count in post lists
        $this->cacheService->invalidateBlogList();

        return response()->json($comment->load('user'), 201);
    }

    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);
        $postId = $comment->blog_post_id;
        $comment->delete();

        // Clear cache for the post to update comments_count
        Cache::forget('blog_post.' . $postId);
        
        // Invalidate blog list cache to update comments_count in post lists
        $this->cacheService->invalidateBlogList();

        return response()->json(null, 204);
    }
} 