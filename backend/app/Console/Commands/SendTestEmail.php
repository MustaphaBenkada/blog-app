<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\PostPublishedMail;
use App\Models\BlogPost;

class SendTestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email to verify email configuration';

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
    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info("Sending test email to: {$email}");
        
        // Create a dummy post for testing
        $post = new BlogPost([
            'id' => 999,
            'title' => 'Test Post',
            'description' => 'This is a test post for email verification.',
            'published_at' => now(),
        ]);
        
        // Create a dummy user
        $user = new \App\Models\User([
            'name' => 'Test User',
            'email' => $email,
        ]);
        
        $post->setRelation('user', $user);
        
        try {
            Mail::to($email)->send(new PostPublishedMail($post));
            $this->info('Test email sent successfully!');
            $this->info('Check your email inbox or Laravel logs for the email content.');
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to send test email: ' . $e->getMessage());
            return 1;
        }
    }
} 