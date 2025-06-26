<?php

namespace App\Console\Commands;

use App\Jobs\SendPublishedPostEmail;
use App\Models\BlogPost;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Services\SearchService;
use App\Services\CacheService;

class PublishScheduledPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish scheduled blog posts';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(SearchService $searchService, CacheService $cacheService)
    {
        $this->info('Checking for scheduled posts...');
        $this->info('Current time: ' . Carbon::now());
        
        $posts = BlogPost::where('status', 'scheduled')
            ->where('published_at', '<=', Carbon::now())
            ->get();

        $this->info('Found ' . $posts->count() . ' posts to publish');

        foreach ($posts as $post) {
            $this->info("Processing post: {$post->title} (ID: {$post->id})");
            $this->info("Post status before: {$post->status}");
            $this->info("Post published_at: {$post->published_at}");
            
            $post->update(['status' => 'published']);
            
            // Reload the post to get updated status
            $post->refresh();
            $this->info("Post status after: {$post->status}");
            
            // Invalidate individual post cache
            Cache::forget('blog_post.' . $post->id);
            
            // Add post to search index
            try {
                $searchService->indexPost($post);
                $this->info("Post added to search index: {$post->title}");
            } catch (\Exception $e) {
                $this->error("Failed to add post to search index: " . $e->getMessage());
            }
            
            // Send email notification to the author
            try {
                // Load the user relationship to ensure it's available
                $post->load('user');
                SendPublishedPostEmail::dispatch($post, $post->user->email, $post->user->name);
                $this->info("Email job dispatched for post: {$post->title}");
            } catch (\Exception $e) {
                $this->error("Failed to dispatch email job: " . $e->getMessage());
            }
            
            $this->info("Published post: {$post->title}");
        }

        // Invalidate cache after publishing posts
        if ($posts->count() > 0) {
            $this->info('Invalidating cache...');
            Cache::tags('blog_posts')->flush();
            $cacheService->invalidateBlogList();
            $this->info('Cache invalidated successfully.');
        }

        if ($posts->count() > 0) {
        $this->info('Published ' . $posts->count() . ' posts.');
        } else {
            $this->info('No posts to publish.');
        }

        return 0;
    }
}
