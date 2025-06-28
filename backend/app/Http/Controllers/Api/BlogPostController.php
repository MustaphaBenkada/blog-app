<?php

namespace App\Http\Controllers\Api;

use App\Models\BlogPost;
use Illuminate\Http\Request;
use App\Services\ImageService;
use App\Services\BlogPostService;
use App\Http\Controllers\Controller;
use App\Http\Requests\BlogPostRequest;
use App\Http\Resources\BlogPostResource;
use App\Http\Requests\UpdateBlogPostRequest;
use App\Services\SearchService;
use App\Services\CacheService;

class BlogPostController extends Controller
{
    protected BlogPostService $blogPostService;
    protected ImageService $imageService;
    protected SearchService $searchService;
    protected CacheService $cacheService;

    public function __construct(BlogPostService $blogPostService, ImageService $imageService, SearchService $searchService, CacheService $cacheService)
    {
        $this->blogPostService = $blogPostService;
        $this->imageService = $imageService;
        $this->searchService = $searchService;
        $this->cacheService = $cacheService;
    }


     public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120',
        ]);

        $path = $this->imageService->upload($request->file('image'));

        return response()->json([
            'message' => 'Image uploaded successfully',
            'url' => asset('storage/' . $path),
            'path' => $path,
        ], 201);
    }

    public function index()
    {
        $posts = $this->blogPostService->getPaginatedPosts();
        return BlogPostResource::collection($posts);
    }

    public function myPosts()
    {
        $posts = $this->blogPostService->getAuthorPosts(auth()->id());
        return BlogPostResource::collection($posts);
    }

    public function search(Request $request)
    {
        $searchTerm = $request->query('query', $request->query);
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 10);

        if (empty($searchTerm)) {
            $cached = $this->cacheService->getPaginatedBlogList($page, $perPage);
            if ($cached) {
                return response()->json($cached + ['from_cache' => true]);
            }
            $posts = $this->searchService->search('', $perPage);
            $result = $this->cacheService->setPaginatedBlogList($page, $perPage, $posts);
            return response()->json($result + ['from_cache' => false]);
        }

        $posts = $this->searchService->search($searchTerm, $perPage);
        return BlogPostResource::collection($posts);
    }

    public function rebuildSearchIndex()
    {
        $count = $this->searchService->rebuildIndex();
        return response()->json([
            'message' => 'Search index rebuilt successfully',
            'indexed_posts' => $count
        ]);
    }

    public function testPublishedEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        // Create a dummy post for testing
        $post = new \App\Models\BlogPost([
            'id' => 999,
            'title' => 'Test Post for Email',
            'description' => 'This is a test post to verify email functionality.',
            'published_at' => now(),
        ]);
        
        // Create a dummy user
        $user = new \App\Models\User([
            'name' => 'Test User',
            'email' => $request->email,
        ]);
        
        $post->setRelation('user', $user);
        
        try {
            // Dispatch the email job
            \App\Jobs\SendPublishedPostEmail::dispatch($post, $request->email, 'Test User');
            
            return response()->json([
                'message' => 'Test email job dispatched successfully',
                'email' => $request->email,
                'note' => 'Check the queue worker logs and Laravel logs for email processing'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to dispatch email job: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(BlogPostRequest $request)
    {
        $post = $this->blogPostService->createPost($request->validated(), $request->file('image'));
        $this->searchService->indexPost($post);
        $this->cacheService->invalidateBlogList();
        return response()->json([
            'message' => 'Blog post created',
            'data' => new BlogPostResource($post)
        ], 201);
    }

    public function show(BlogPost $post)
    {
        // Check if user is authenticated and is the author
        $isAuthor = auth()->id() === $post->user_id;
        // If not the author, only show published posts
        if (!$isAuthor && !$post->isPublished()) {
             return response()->json([
                'message' => 'Post not found or not accessible'
            ], 404);
        }
        
        return new BlogPostResource($this->blogPostService->loadPostWithTags($post));
    }

    public function update(UpdateBlogPostRequest $request, BlogPost $post)
    {
        $updatedPost = $this->blogPostService->updatePost($post, $request->validated(), $request->file('image'));
        $this->searchService->indexPost($updatedPost);
        $this->cacheService->invalidateBlogList();
        return response()->json([
            'message' => 'Blog post updated',
            'data' => new BlogPostResource($updatedPost)
        ]);
    }

    public function destroy(BlogPost $post)
    {
        $this->blogPostService->deletePost($post);
        $this->searchService->removePost($post->id);
        $this->cacheService->invalidateBlogList();
        return response()->json(['message' => 'The post has been deleted successfully'], 200);
    }

    public function publishScheduled()
    {
        // Only allow this in development/testing
        if (!app()->environment(['local', 'testing'])) {
            return response()->json(['message' => 'Not allowed in production'], 403);
        }

        \Artisan::call('posts:publish');
        $output = \Artisan::output();

        return response()->json([
            'message' => 'Scheduled posts published',
            'output' => $output
        ]);
    }

    public function debugPostStatus()
    {
        // Only allow this in development/testing
        if (!app()->environment(['local', 'testing'])) {
            return response()->json(['message' => 'Not allowed in production'], 403);
        }

        $posts = BlogPost::with('user')->get()->map(function ($post) {
            return [
                'id' => $post->id,
                'title' => $post->title,
                'status' => $post->status,
                'published_at' => $post->published_at,
                'is_published' => $post->isPublished(),
                'is_scheduled' => $post->isScheduled(),
                'is_draft' => $post->isDraft(),
                'author' => $post->user->name,
                'created_at' => $post->created_at,
            ];
        });

        return response()->json([
            'current_time' => now(),
            'timezone' => config('app.timezone'),
            'posts' => $posts
        ]);
    }
}
