import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';

// Baris reaksi emoji (ringkas, ditaruh di samping tombol komentar).
// - Siswa: bisa menekan (beri / ganti / cabut)
// - Tamu: menekan -> diarahkan ke login
// - Admin: hanya melihat jumlah
export default function ReactionBar({ postId, reactions = [], mine = null, onDark = false, className = '' }) {
    const { auth } = usePage().props;
    const role = auth?.user?.role;
    const [busy, setBusy] = useState(false);

    const press = (type) => {
        if (busy) return;
        if (!role) {
            router.visit('/login');
            return;
        }
        router.post(`/posts/${postId}/reaction`, { type }, {
            preserveScroll: true,
            preserveState: true,
            onStart: () => setBusy(true),
            onFinish: () => setBusy(false),
        });
    };

    const idle = onDark ? 'text-white/80 hover:bg-white/10' : 'text-ink/70 hover:bg-ink/5';

    return (
        <div role="group" aria-label="Reaksi" className={`flex flex-wrap items-center gap-0.5 ${className}`}>
            {reactions.map((r) => {
                const active = mine === r.type;

                if (role === 'admin') {
                    return (
                        <span key={r.type} title="Jumlah reaksi"
                            className={`inline-flex items-center gap-1 rounded-full px-2 py-1 text-sm font-semibold ${onDark ? 'text-white/70' : 'text-ink/60'}`}>
                            <span className="text-lg leading-none">{r.emoji}</span>{r.count}
                        </span>
                    );
                }

                return (
                    <button key={r.type} type="button" onClick={() => press(r.type)} disabled={busy}
                        aria-pressed={active}
                        aria-label={`${r.emoji} (${r.count})`}
                        className={`inline-flex items-center gap-1 rounded-full px-2 py-1 text-sm font-semibold transition disabled:opacity-60 ${
                            active ? 'bg-brand/15 text-brand ring-1 ring-brand' : idle}`}>
                        <span className="text-lg leading-none">{r.emoji}</span>{r.count}
                    </button>
                );
            })}
        </div>
    );
}