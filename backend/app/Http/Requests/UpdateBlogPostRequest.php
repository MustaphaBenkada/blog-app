<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string',
            'excerpt' => 'sometimes|required|string',
            'description' => 'sometimes|required|string',
            'image' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'meta_title' => 'nullable|string',
            'meta_description' => 'nullable|string',
            'published_at' => 'nullable|date',
        ];
    }
}
