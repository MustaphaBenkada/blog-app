<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        $this->call([
            BlogPostSeeder::class,
            CommentSeeder::class,
        ]);
        
        // Rebuild search index after seeding
        $this->command->info("Rebuilding search index...");
        Artisan::call('posts:rebuild-search-index');
        $this->command->info("Search index rebuilt successfully!");
    }
}
