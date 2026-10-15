import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';

function Heart({ filled, className }) {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true" className={className}
            fill={filled ? 'currentColor' : 'none'} stroke="currentColor"
            strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z" />
        </svg>
    );
}

// Tombol suka.
// - Siswa: bisa menekan (suka / batal suka)
// - Tamu: menekan -> diarahkan ke halaman login
// - Admin: hanya melihat jumlah (bukan tombol)
export default function LikeButton({ postId, count = 0, liked = false, big = false, onDark = false }) {
    const { auth } = usePage().props;
    const role = auth?.user?.role;
    const [busy, setBusy] = useState(false);

    const size = big ? 'h-6 w-6' : 'h-5 w-5';
    const text = big ? 'text-base' : 'text-sm';
    // Di latar gelap (mode gelap halaman baca) warna teks netral dibuat terang
    const idle = onDark ? 'text-white/70 hover:text-brand' : 'text-ink/60 hover:text-brand';

    if (role === 'admin') {
        return (
            <span title="Jumlah suka" className={`inline-flex items-center gap-1.5 font-semibold ${onDark ? 'text-white/70' : 'text-ink/60'} ${text}`}>
                <Heart filled={false} className={size} />
                {count}
            </span>
        );
    }

    const toggle = (e) => {
        // Tombol ini bisa berada di dalam kartu yang berupa tautan: jangan ikut membuka halaman detail
        e.preventDefault();
        e.stopPropagation();
        if (busy) return;

        if (!role) {
            router.visit('/login');
            return;
        }

        router.post(`/posts/${postId}/like`, {}, {
            preserveScroll: true,
            preserveState: true,
            onStart: () => setBusy(true),
            onFinish: () => setBusy(false),
        });
    };

    return (
        <button type="button" onClick={toggle} disabled={busy}
            aria-pressed={liked}
            aria-label={liked ? `Batal suka (${count} suka)` : `Suka (${count} suka)`}
            className={`inline-flex items-center gap-1.5 rounded-full px-2 py-1 font-semibold transition hover:bg-brand/10 disabled:opacity-60 ${text} ${
                liked ? 'text-brand' : idle}`}>
            <Heart filled={liked} className={size} />
            {count}
        </button>
    );
}
