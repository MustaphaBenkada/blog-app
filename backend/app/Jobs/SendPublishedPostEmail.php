<?php

namespace App\Jobs;

use App\Models\BlogPost;
use App\Mail\PostPublishedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPublishedPostEmail
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $post;
    protected $userEmail;
    protected $userName;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(BlogPost $post, $userEmail, $userName)
    {
        $this->post = $post;
        $this->userEmail = $userEmail;
        $this->userName = $userName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            if (!$this->userEmail) {
                return;
            }
            
            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));
            $postUrl = $frontendUrl . '/posts/' . $this->post->id;
            
            // Prepare post data as array to avoid serialization issues
            $postData = [
                'id' => $this->post->id,
                'title' => $this->post->title,
                'description' => $this->post->description,
                'excerpt' => $this->post->excerpt,
                'published_at' => $this->post->published_at,
            ];
            
            Mail::to($this->userEmail)->queue(new PostPublishedMail($postData, $this->userName, $postUrl));
        } catch (\Exception $e) {
            // Log::error('Failed to send published post email', ...);
        }
    }
}
