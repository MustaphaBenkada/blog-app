<?php

namespace App\Console\Commands;

use App\Jobs\CheckScheduledPosts;
use Illuminate\Console\Command;

class StartScheduledPostsChecker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:start-checker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the scheduled posts checker job';

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
        $this->info('Starting scheduled posts checker...');
        
        // Dispatch the first check job
        CheckScheduledPosts::dispatch();
        
        $this->info('Scheduled posts checker started. The job will run every minute.');
        
        return 0;
    }
} 