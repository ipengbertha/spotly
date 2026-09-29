<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // otorisasi ditangani PostPolicy di controller
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:150'],
            'content'     => ['required', 'string', 'max:5000'],
            'category_id' => ['required', 'exists:categories,id'],
            // Batasan upload: tipe dan ukuran (2048 KB = 2 MB)
            'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'action'      => ['nullable', 'in:draft,submit'],
        ];
    }
}