import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';

function BookmarkIcon({ filled, className }) {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true" className={className}
            fill={filled ? 'currentColor' : 'none'} stroke="currentColor"
            strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" />
        </svg>
    );
}

// Tombol simpan artikel ("baca nanti").
// - Siswa: bisa menekan (simpan / batal simpan)
// - Tamu dan admin: tombol tidak ditampilkan
export default function BookmarkButton({ postId, saved = false, big = false, onDark = false }) {
    const { auth } = usePage().props;
    const [busy, setBusy] = useState(false);

    if (auth?.user?.role !== 'user') return null;

    const size = big ? 'h-6 w-6' : 'h-5 w-5';
    const idle = onDark ? 'text-white/70 hover:text-grape' : 'text-ink/60 hover:text-grape';

    const toggle = (e) => {
        // Tombol ini bisa berada di dalam kartu yang berupa tautan: jangan ikut membuka halaman detail
        e.preventDefault();
        e.stopPropagation();
        if (busy) return;

        router.post(`/posts/${postId}/bookmark`, {}, {
            preserveScroll: true,
            preserveState: true,
            onStart: () => setBusy(true),
            onFinish: () => setBusy(false),
        });
    };

    return (
        <button type="button" onClick={toggle} disabled={busy}
            aria-pressed={saved}
            aria-label={saved ? 'Batal simpan artikel' : 'Simpan artikel'}
            title={saved ? 'Batal simpan' : 'Simpan untuk dibaca nanti'}
            className={`inline-flex items-center rounded-full px-2 py-1 transition hover:bg-grape/10 disabled:opacity-60 ${
                saved ? (onDark ? 'text-peach' : 'text-grape') : idle}`}>
            <BookmarkIcon filled={saved} className={size} />
        </button>
    );
}
