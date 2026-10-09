<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public const PER_PAGE_OPTIONS = [6, 12, 18, 24];

    private const DEFAULTS = [
        'mading_name'        => 'Spotly',
        'mading_description' => 'Mading Digital Sekolah',
        'posts_per_page'     => '12',
    ];

    public static function get(string $key): string
    {
        return static::find($key)?->value ?? self::DEFAULTS[$key];
    }

    public static function put(string $key, string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function perPage(): int
    {
        $n = (int) static::get('posts_per_page');

        return in_array($n, self::PER_PAGE_OPTIONS, true) ? $n : 12;
    }

    public static function mading(): array
    {
        return [
            'name'           => static::get('mading_name'),
            'description'    => static::get('mading_description'),
            'posts_per_page' => static::perPage(),
        ];
    }
}