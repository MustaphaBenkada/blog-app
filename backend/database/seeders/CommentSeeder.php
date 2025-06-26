<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BlogPost;
use App\Models\User;
use App\Models\Comment;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class CommentSeeder extends Seeder
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

        $faker = Faker::create();
        
        $posts = BlogPost::inRandomOrder()->limit(10)->get(); // Limit to 10 posts for testing
        $users = User::all();

        if ($posts->isEmpty() || $users->isEmpty()) {
            $this->command->info('No posts or users found, skipping comment seeding.');
            return;
        }

        $this->command->info('Seeding comments for ' . $posts->count() . ' posts...');
        $this->command->getOutput()->progressStart($posts->count());

        foreach ($posts as $post) {
            $numberOfComments = rand(5, 15);
            $comments = [];
            for ($i = 0; $i < $numberOfComments; $i++) {
                $comments[] = [
                    'blog_post_id' => $post->id,
                    'user_id' => $users->random()->id,
                    'content' => $faker->paragraph,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            try {
                Comment::insert($comments);
            } catch (\Throwable $e) {
                $this->command->error('Failed to insert comments for post ' . $post->id . ': ' . $e->getMessage());
            }
            $this->command->getOutput()->progressAdvance();
        }

        $this->command->getOutput()->progressFinish();
        $this->command->info("\nSuccessfully seeded comments.");
    }
}
