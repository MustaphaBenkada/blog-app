<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class BlogPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'excerpt',
        'description',
        'image',
        'meta_title',
        'meta_description',
        'published_at',
        'status',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    // Scope to get only published posts
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->where('published_at', '<=', Carbon::now());
    }

    // Scope to get scheduled posts
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
                    ->where('published_at', '>', Carbon::now());
    }

    // Scope to get draft posts
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    // Check if post is published
    public function isPublished()
    {
        return $this->status === 'published' && $this->published_at && $this->published_at <= Carbon::now();
    }

    // Check if post is scheduled
    public function isScheduled()
    {
        return $this->status === 'scheduled' && $this->published_at && $this->published_at > Carbon::now();
    }

    // Check if post is draft
    public function isDraft()
    {
        return $this->status === 'draft';
    }
}
