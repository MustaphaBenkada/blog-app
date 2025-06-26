<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BlogPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // For performance, disable query logging and events.
        DB::connection()->disableQueryLog();
        DB::connection()->unsetEventDispatcher();

        $faker = \Faker\Factory::create();

        // Ensure we have users and tags to associate.
        $users = User::factory(10)->create();
        if ($users->isEmpty()) {
            $this->command->error('No users found. Please seed users first.');
            return;
        }

        $tags = Tag::factory(20)->create();
        if ($tags->isEmpty()) {
            $this->command->error('No tags found. Please seed tags first.');
            return;
        }
        $tagIds = $tags->pluck('id');

        $totalPosts = 20000;
        $chunkSize = 500;

        $this->command->info("Creating {$totalPosts} blog posts...");
        Log::info("Blog post seeding started for {$totalPosts} posts.");
        $this->command->getOutput()->progressStart($totalPosts);

        for ($i = 0; $i < $totalPosts; $i += $chunkSize) {
            $postsToCreate = [];
            $currentChunkSize = min($chunkSize, $totalPosts - $i);

            for ($j = 0; $j < $currentChunkSize; $j++) {
                $postsToCreate[] = [
                    'user_id' => $users->random()->id,
                    'title' => $faker->sentence,
                    'excerpt' => $faker->paragraph,
                    'description' => $faker->text(800),
                    'status' => 'published',
                    'published_at' => $faker->dateTimeBetween('-1 year', 'now'),
                    'meta_title' => $faker->sentence,
                    'meta_description' => $faker->sentence,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            
            BlogPost::insert($postsToCreate);
            
            $lastInsertedId = DB::getPdo()->lastInsertId();
            $postsJustCreated = BlogPost::where('id', '>=', $lastInsertedId)
                                         ->limit($currentChunkSize)
                                         ->get();

            $postTags = [];
            foreach ($postsJustCreated as $post) {
                $tagsToAttach = $tagIds->random(rand(1, 3));
                foreach ($tagsToAttach as $tagId) {
                    $postTags[] = ['blog_post_id' => $post->id, 'tag_id' => $tagId];
                }
            }

            if (!empty($postTags)) {
                DB::table('blog_post_tag')->insert($postTags);
            }

            $this->command->getOutput()->progressAdvance($currentChunkSize);
            Log::info("Seeded chunk " . ($i / $chunkSize + 1) . ". Total posts so far: " . ($i + $currentChunkSize));
        }

        $this->command->getOutput()->progressFinish();
        Log::info("Successfully finished seeding {$totalPosts} blog posts with tags!");
        $this->command->info("\nSuccessfully created {$totalPosts} blog posts with tags!");
    }
}
