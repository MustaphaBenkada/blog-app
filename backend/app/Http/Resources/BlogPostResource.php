<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Comment;

class BlogPostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'tags' => $this->tags->pluck('name'),
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'published_at' => $this->published_at,
            'status' => $this->status,
            'comments_count' => $this->comments_count,
            'comments' => $this->comments()->with('user')->get()->map(function($comment) {
                return [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'created_at' => $comment->created_at,
                    'user' => [
                        'id' => $comment->user->id ?? null,
                        'name' => $comment->user->name ?? 'Unknown',
                    ],
                ];
            }),
            'created_at' => $this->created_at,
        ];
    }
}
