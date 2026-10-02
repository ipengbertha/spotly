<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PostRequest extends FormRequest
{
    public const MAX_EXTRA_CATEGORIES = 2;

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
            // Kategori tambahan: opsional, maksimal 2.
            // Kalau ingin WAJIB minimal 1, ganti 'nullable' menjadi 'required' dan tambah 'min:1'.
            'extra_category_ids'   => ['nullable', 'array', 'max:' . self::MAX_EXTRA_CATEGORIES],
            'extra_category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
            // Batasan upload: tipe dan ukuran (2048 KB = 2 MB)
            'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'action'      => ['nullable', 'in:draft,submit'],
        ];
    }

    public function messages(): array
    {
        return [
            'extra_category_ids.max'        => 'Kategori tambahan maksimal ' . self::MAX_EXTRA_CATEGORIES . '.',
            'extra_category_ids.*.integer'  => 'Kategori tambahan tidak valid.',
            'extra_category_ids.*.distinct' => 'Kategori tambahan tidak boleh kembar.',
            'extra_category_ids.*.exists'   => 'Kategori tambahan tidak ditemukan.',
        ];
    }

    // Kategori tambahan tidak boleh sama dengan kategori utama
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $primary = (string) $this->input('category_id');

                foreach ((array) $this->input('extra_category_ids', []) as $id) {
                    if ((string) $id === $primary) {
                        $validator->errors()->add(
                            'extra_category_ids',
                            'Kategori tambahan tidak boleh sama dengan kategori utama.'
                        );
                        break;
                    }
                }
            },
        ];
    }
}