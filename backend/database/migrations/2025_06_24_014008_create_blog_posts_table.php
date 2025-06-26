<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBlogPostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('blog_posts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade'); // add this
                $table->string('title');
                $table->text('excerpt')->nullable();
                $table->longText('description')->nullable();
                $table->string('image')->nullable();
                $table->string('meta_title')->nullable();
                $table->string('meta_description')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->string('status')->default('draft');
                $table->timestamps();

                // Composite index for optimized pagination
                $table->index(['status', 'published_at', 'created_at', 'id'], 'idx_status_published_at_created_at_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('blog_posts');
    }
}
