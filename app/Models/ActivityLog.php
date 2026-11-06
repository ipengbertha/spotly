<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

class ActivityLog extends Model
{
    // Kata kerja yang tampil di daftar: "[Admin] [label] "[judul]""
    public const LABELS = [
        'post.approved'    => 'menyetujui',
        'post.scheduled'   => 'menyetujui dan menjadwalkan',
        'post.rejected'    => 'menolak',
        'post.revision'    => 'meminta revisi',
        'post.unscheduled' => 'membatalkan jadwal',
        'post.archived'    => 'mengarsipkan',
        'post.deleted'     => 'menghapus',
        'post.pinned'      => 'menyematkan',
        'post.unpinned'    => 'melepas pin',
        'post.expiry'      => 'mengubah masa tayang',
        'post.republished' => 'menayangkan ulang',
        'report.upheld'    => 'menangguhkan postingan karena laporan',
        'report.dismissed' => 'menolak laporan pada',
        'comment.deleted'           => 'menghapus komentar dari',
        'comment.reports_dismissed' => 'menolak laporan pada komentar dari',
        'user.deactivated'          => 'menonaktifkan akun',
        'user.activated'            => 'mengaktifkan akun',
        'category.created'          => 'menambah kategori',
        'category.renamed'          => 'mengubah nama kategori',
        'category.deleted'          => 'menghapus kategori',
        'poll.created'              => 'membuat polling',
        'poll.extended'             => 'mengubah batas waktu polling',
        'poll.closed'               => 'menutup polling',
        'poll.deleted'              => 'menghapus polling',
    ];

    protected $fillable = ['admin_id', 'action', 'post_id', 'subject_title', 'detail'];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Catat aksi admin yang sedang login. Kegagalan mencatat tidak boleh
     * menggagalkan aksinya sendiri, jadi hanya dilaporkan ke log error.
     */
    public static function record(string $action, ?Post $post = null, ?string $detail = null): void
    {
        try {
            static::create([
                'admin_id'      => auth()->id(),
                'action'        => $action,
                'post_id'       => $post?->id,
                'subject_title' => $post?->title,
                'detail'        => $detail !== null ? Str::limit($detail, 500, '') : null,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

        /**
     * Seperti record(), tapi untuk aksi yang tidak terkait postingan
     * (akun, kategori, komentar). Subjeknya ditampilkan di kolom judul.
     */
    public static function recordSubject(string $action, string $subject, ?string $detail = null): void
    {
        try {
            static::create([
                'admin_id'      => auth()->id(),
                'action'        => $action,
                'post_id'       => null,
                'subject_title' => Str::limit($subject, 250, ''),
                'detail'        => $detail !== null ? Str::limit($detail, 500, '') : null,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function label(): string
    {
        return self::LABELS[$this->action] ?? $this->action;
    }

    // Bentuk baris untuk dikirim ke halaman (dashboard dan daftar aktivitas)
    public function toRow(): array
    {
        return [
            'id'         => $this->id,
            'admin'      => $this->admin?->name ?? 'Admin (akun dihapus)',
            'label'      => $this->label(),
            'title'      => $this->subject_title,
            'detail'     => $this->detail,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}