<?php

namespace App\Console\Commands;

use App\Services\SearchService;
use Illuminate\Console\Command;

class RebuildSearchIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:rebuild-search-index';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild the search index for all published posts';

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
    public function handle(SearchService $searchService)
    {
        $this->info('Starting search index rebuild...');
        
        try {
            $count = $searchService->rebuildIndex();
            
            $this->info("Search index rebuilt successfully!");
            $this->info("Indexed {$count} posts.");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to rebuild search index: " . $e->getMessage());
            return 1;
        }
    }
} 