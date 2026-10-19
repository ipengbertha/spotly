<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Semua urusan isi artikel (HTML dari editor) ada di sini:
 * membersihkan sebelum disimpan, menampilkan, dan mengambil teks polosnya.
 *
 * Artikel lama berupa teks biasa (paragraf dipisah baris kosong). Artikel baru berupa HTML.
 * Keduanya dikenali lewat isLegacy(), jadi data lama tidak perlu dimigrasi.
 */
class PostHtml
{
    // Tag yang boleh ada di artikel. Selain ini dibuang oleh pembersih.
    private const ALLOWED = 'p,br,strong,em,u,s,h2,h3,ul,ol,li,blockquote,hr,'
        . 'a[href],table,thead,tbody,tr,th[colspan|rowspan],td[colspan|rowspan]';

    // Kalau teks mengandung salah satu tag ini, dianggap HTML dari editor
    private const HTML_HINT = '/<\/?(p|br|h2|h3|ul|ol|li|strong|em|u|s|a|table|thead|tbody|tr|th|td|blockquote|hr)[\s>\/]/i';

    private static ?HTMLPurifier $purifier = null;

    public static function isLegacy(string $content): bool
    {
        return ! preg_match(self::HTML_HINT, $content);
    }

    /**
     * Bersihkan isi dari editor sebelum disimpan. Teks biasa (bukan HTML) dikembalikan apa adanya
     * dan tetap aman karena saat ditampilkan selalu di-escape.
     */
    public static function clean(?string $content): string
    {
        $content = trim((string) $content);

        if ($content === '' || self::isLegacy($content)) {
            return $content;
        }

        return trim(self::purifier()->purify($content));
    }

    /** HTML siap tampil. Hasilnya aman untuk dipasang dengan dangerouslySetInnerHTML. */
    public static function render(?string $content): string
    {
        $content = trim((string) $content);

        if ($content === '') {
            return '';
        }
        if (! self::isLegacy($content)) {
            return $content; // sudah dibersihkan saat disimpan
        }

        $paragraphs = preg_split('/\R{2,}/u', $content, -1, PREG_SPLIT_NO_EMPTY);

        return collect($paragraphs)
            ->map(fn (string $p) => '<p>' . nl2br(e(trim($p)), false) . '</p>')
            ->implode('');
    }

    /** Teks polos (tanpa tag) untuk kutipan kartu, estimasi waktu baca, dan batas panjang. */
    public static function plainText(?string $content): string
    {
        $content = (string) $content;

        if (! self::isLegacy($content)) {
            // Beri spasi di akhir blok supaya kata antar paragraf tidak menempel
            $content = preg_replace('/<\/(p|h2|h3|li|tr|td|th|blockquote)>|<br\s*\/?>/i', ' ', $content);
            $content = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return trim(preg_replace('/\s+/u', ' ', $content));
    }

    private static function purifier(): HTMLPurifier
    {
        if (self::$purifier) {
            return self::$purifier;
        }

        $cache = storage_path('framework/cache/purifier');
        if (! is_dir($cache)) {
            @mkdir($cache, 0775, true);
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('Cache.SerializerPath', $cache);
        $config->set('HTML.Allowed', self::ALLOWED);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('HTML.TargetBlank', true);   // tautan dibuka di tab baru (+ noopener otomatis)
        $config->set('HTML.Nofollow', true);
        $config->set('AutoFormat.RemoveEmpty', true); // buang <p></p> kosong dari editor

        return self::$purifier = new HTMLPurifier($config);
    }
}
