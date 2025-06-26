<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add auth logic if needed
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string',
            'excerpt' => 'required|string',
            'description' => 'required|string',
            'image' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'meta_title' => 'nullable|string',
            'meta_description' => 'nullable|string',
            'published_at' => 'nullable|date',
        ];
    }
}

