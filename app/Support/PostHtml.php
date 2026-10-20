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
        . 'a[href],table,thead,tbody,tr,th[colspan|rowspan],td[colspan|rowspan],'
        . 'img[src|alt],iframe[src|width|height|frameborder]';

    // Kalau teks mengandung salah satu tag ini, dianggap HTML dari editor
    private const HTML_HINT = '/<\/?(p|br|h2|h3|ul|ol|li|strong|em|u|s|a|table|thead|tbody|tr|th|td|blockquote|hr|img|iframe)[\s>\/]/i';

    // Gambar hanya boleh dari penyimpanan kita: /storage/post-images/<nama-file>
    private const IMG_SRC = '#^/storage/post-images/[A-Za-z0-9._-]+$#';

    // Video hanya YouTube lewat youtube-nocookie.com, dengan ID 11 karakter
    private const YT_SRC = '#^https://www\.youtube-nocookie\.com/embed/([A-Za-z0-9_-]{11})(?:\?.*)?$#';

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

        $html = trim(self::purifier()->purify($content));

        return trim(self::restrictMedia($html));
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

    /**
     * Lapis kedua setelah pembersih: gambar dan iframe dibangun ulang dari nol.
     * Yang alamatnya tidak sesuai aturan dibuang, sisanya ditulis ulang dengan atribut tetap.
     */
    private static function restrictMedia(string $html): string
    {
        $html = preg_replace_callback('/<img\b[^>]*>/i', function (array $m) {
            if (preg_match('/\ssrc="([^"]*)"/i', $m[0], $s) && preg_match(self::IMG_SRC, $s[1])) {
                return '<img src="' . $s[1] . '" alt="" loading="lazy">';
            }

            return '';
        }, $html);

        return preg_replace_callback('/<iframe\b[^>]*>\s*<\/iframe>|<iframe\b[^>]*\/>/i', function (array $m) {
            if (
                preg_match('/\ssrc="([^"]*)"/i', $m[0], $s)
                && preg_match(self::YT_SRC, html_entity_decode($s[1]), $id)
            ) {
                return '<div class="post-video"><iframe src="https://www.youtube-nocookie.com/embed/' . $id[1]
                    . '" title="Video YouTube" allowfullscreen loading="lazy"'
                    . ' referrerpolicy="strict-origin-when-cross-origin"></iframe></div>';
            }

            return '';
        }, $html);
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

        // iframe hanya lolos kalau alamatnya YouTube (youtube-nocookie.com)
        $config->set('HTML.SafeIframe', true);
        $config->set('URI.SafeIframeRegexp', '#^https://www\.youtube-nocookie\.com/embed/[A-Za-z0-9_-]{11}(\?[A-Za-z0-9_=&.;%-]*)?$#');

        return self::$purifier = new HTMLPurifier($config);
    }
}