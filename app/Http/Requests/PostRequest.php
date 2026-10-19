<?php

namespace App\Http\Requests;

use App\Support\PostHtml;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PostRequest extends FormRequest
{
    public const MAX_EXTRA_CATEGORIES = 2;

    public function authorize(): bool
    {
        return true; // otorisasi ditangani PostPolicy di controller
    }

    // Batas isi diukur dari TEKS polosnya (bukan HTML), supaya tag editor tidak ikut dihitung
    public const MAX_CONTENT_CHARS = 5000;

    // Isi dari editor dibersihkan dulu sebelum divalidasi dan disimpan
    protected function prepareForValidation(): void
    {
        if ($this->has('content')) {
            $this->merge(['content' => PostHtml::clean((string) $this->input('content'))]);
        }
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:150'],
            'content'     => ['required', 'string', 'max:60000'], // batas kasar HTML; batas sebenarnya di after()
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
            'title.required'   => 'Judul wajib diisi.',
            'title.string'     => 'Judul tidak valid.',
            'title.max'        => 'Judul maksimal 150 karakter.',

            'content.required' => 'Isi postingan wajib diisi.',
            'content.string'   => 'Isi postingan tidak valid.',
            'content.max'      => 'Isi postingan terlalu panjang.',

            'category_id.required' => 'Kategori utama wajib dipilih.',
            'category_id.exists'   => 'Kategori utama tidak ditemukan.',

            'extra_category_ids.array'      => 'Kategori tambahan tidak valid.',
            'extra_category_ids.max'        => 'Kategori tambahan maksimal ' . self::MAX_EXTRA_CATEGORIES . '.',
            'extra_category_ids.*.integer'  => 'Kategori tambahan tidak valid.',
            'extra_category_ids.*.distinct' => 'Kategori tambahan tidak boleh kembar.',
            'extra_category_ids.*.exists'   => 'Kategori tambahan tidak ditemukan.',

            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Gambar harus berformat JPG, PNG, atau WEBP.',
            'image.max'   => 'Ukuran gambar maksimal 2 MB.',
            'image.uploaded' => 'Gambar gagal diunggah. Coba file yang lebih kecil dari 2 MB.',

            'action.in' => 'Aksi tidak valid.',
        ];
    }

    // Kategori tambahan tidak boleh sama dengan kategori utama
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $validator->errors()->has('content')) {
                    $length = mb_strlen(PostHtml::plainText((string) $this->input('content')));

                    if ($length === 0) {
                        $validator->errors()->add('content', 'Isi postingan wajib diisi.');
                    } elseif ($length > self::MAX_CONTENT_CHARS) {
                        $validator->errors()->add('content', 'Isi postingan maksimal ' . self::MAX_CONTENT_CHARS . ' karakter.');
                    }
                }
            },
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