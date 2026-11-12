import { useCallback, useEffect, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';

// Ganti judul kartu di sini kalau mau nama lain.
const TITLE = 'Bintang Mading';

// Warna per peringkat asli (bukan urutan tampil), jadi juara 1 tetap emas walau admin tidak memilih semua.
const ACCENT = {
    1: '#FFC83D', // emas
    2: '#5EE0C8', // tosca
    3: '#FF8A6B', // koral
};
const EXTRA = ['#B79CFF', '#FF7EB6']; // lavender, pink untuk peringkat 4 dst.
const accentOf = (rank, i) => ACCENT[rank] ?? EXTRA[i % EXTRA.length];

// Tinggi podium menurut urutan tampil: 0 = paling tinggi
const HEIGHTS = [52, 42, 34, 28, 24];

const CONFETTI_COLORS = ['#FFC83D', '#5EE0C8', '#FF8A6B', '#ffffff', '#B79CFF', '#FF7EB6'];
const CONFETTI = Array.from({ length: 18 }, (_, i) => ({
    left: (i * 37 + 8) % 96,
    color: CONFETTI_COLORS[i % CONFETTI_COLORS.length],
    delay: 0.9 + (i % 7) * 0.17,
    duration: 2.2 + (i % 4) * 0.4,
    size: 6 + (i % 3) * 2,
    round: i % 2 === 0,
    spin: 360 + (i % 5) * 120,
}));

const CSS = `
.kc-root{background:linear-gradient(135deg,#FF5A7A 0%,#8B5CF6 55%,#38BDF8 100%);background-size:200% 200%;animation:kc-drift 14s ease-in-out infinite alternate}
@keyframes kc-drift{from{background-position:0% 50%}to{background-position:100% 50%}}

.kc-bar{height:0;animation:kc-rise .7s cubic-bezier(.2,.8,.2,1) both}
@keyframes kc-rise{from{height:0}to{height:var(--h)}}

.kc-pop{animation:kc-pop .6s cubic-bezier(.34,1.56,.64,1) both}
@keyframes kc-pop{from{opacity:0;transform:translateY(14px) scale(.5)}to{opacity:1;transform:none}}

.kc-bob{animation:kc-bob 2.4s ease-in-out infinite}
@keyframes kc-bob{0%,100%{transform:translateY(0) rotate(-4deg)}50%{transform:translateY(-3px) rotate(4deg)}}

.kc-wobble{animation:kc-wobble 1s ease-in-out .2s both}
@keyframes kc-wobble{0%{transform:scale(.4) rotate(-25deg);opacity:0}50%{transform:scale(1.15) rotate(12deg);opacity:1}75%{transform:rotate(-6deg)}100%{transform:none;opacity:1}}

.kc-conf{position:absolute;top:-12px;opacity:0;pointer-events:none;animation:kc-fall var(--d) ease-in both}
@keyframes kc-fall{0%{opacity:1;transform:translateY(0) rotate(0)}85%{opacity:1}100%{opacity:0;transform:translateY(420px) rotate(var(--spin))}}

@media (prefers-reduced-motion:reduce){
  .kc-root,.kc-pop,.kc-bob,.kc-wobble,.kc-bar{animation:none!important}
  .kc-bar{height:var(--h)}
  .kc-conf{display:none}
}
`;

const firstNameOf = (name) => (name ?? '').trim().split(/\s+/)[0] || 'kamu';

// Cincin avatar; milik sendiri diberi lingkaran putih tambahan supaya mudah ditemukan.
const ringShadow = (color, isMe) =>
    `0 0 0 3px ${color}, ${isMe ? '0 0 0 8px rgba(255,255,255,.4), ' : ''}0 6px 14px rgba(0,0,0,.25)`;

function Confetti() {
    return CONFETTI.map((p, i) => (
        <span key={i} className="kc-conf"
            style={{
                left: `${p.left}%`,
                width: p.size,
                height: p.round ? p.size : p.size * 1.6,
                borderRadius: p.round ? '9999px' : '2px',
                background: p.color,
                animationDelay: `${p.delay}s`,
                '--d': `${p.duration}s`,
                '--spin': `${p.spin}deg`,
            }} />
    ));
}

function Crown({ color }) {
    return (
        <span className="absolute -top-5 left-1/2 -translate-x-1/2">
            <svg viewBox="0 0 24 24" className="kc-bob h-5 w-5 drop-shadow" fill={color} aria-hidden="true">
                <path d="M3 8l4.5 4L12 5l4.5 7L21 8l-2 11H5z" />
            </svg>
        </span>
    );
}

/** Ucapan selamat yang muncul sekali untuk siswa yang terpilih. */
function CelebrationDialog({ me, rank, label, onClose }) {
    const buttonRef = useRef(null);
    const color = accentOf(rank, rank - 1);
    const shadow = ringShadow(color, true);

    useEffect(() => {
        buttonRef.current?.focus();
        const onKey = (e) => { if (e.key === 'Escape') onClose(); };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [onClose]);

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-ink/60 p-4" onClick={onClose}>
            <div role="dialog" aria-modal="true" aria-labelledby="kc-dialog-title"
                onClick={(e) => e.stopPropagation()}
                className="kc-root kc-pop relative w-full max-w-sm overflow-hidden rounded-3xl p-8 text-center text-white shadow-2xl">
                <Confetti />

                <div className="relative">
                    <div className="relative mx-auto mt-4 w-fit">
                        {rank === 1 && <Crown color={color} />}
                        {me.avatar_url ? (
                            <img src={me.avatar_url} alt=""
                                className="h-20 w-20 rounded-full object-cover" style={{ boxShadow: shadow }} />
                        ) : (
                            <span className="flex h-20 w-20 items-center justify-center rounded-full bg-white text-3xl font-bold text-ink"
                                style={{ boxShadow: shadow }}>
                                {(me.name ?? '?').trim().charAt(0).toUpperCase()}
                            </span>
                        )}
                    </div>

                    <h2 id="kc-dialog-title" className="mt-6 text-3xl leading-tight text-white">
                        Selamat, {firstNameOf(me.name)}!
                    </h2>
                    <p className="mt-2 text-base text-white/95">
                        {rank === 1
                            ? `Kamu juara 1 ${TITLE} ${label}.`
                            : `Kamu peringkat ${rank} ${TITLE} ${label}.`}
                    </p>
                    <p className="mt-1 text-sm text-white/85">
                        {me.posts_count} karya dan {me.likes_count} suka bulan ini.
                    </p>

                    <button ref={buttonRef} onClick={onClose}
                        className="mt-7 rounded-full bg-white px-6 py-2.5 text-sm font-semibold text-ink shadow-md transition hover:bg-white/90 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/60">
                        Lihat podium
                    </button>
                </div>
            </div>
        </div>
    );
}

function Column({ c, i, total, isMe }) {
    const rank = c.rank ?? i + 1;
    const color = accentOf(rank, i);
    const first = i === 0;
    const delay = (total - 1 - i) * 0.14 + 0.15; // juara teratas muncul paling akhir
    const size = first ? 'h-14 w-14 text-xl' : 'h-11 w-11 text-base';
    const shadow = ringShadow(color, isMe);

    return (
        <li className="flex min-w-0 flex-col items-center">
            <div className="kc-pop relative" style={{ animationDelay: `${delay + 0.35}s` }}>
                {first && <Crown color={color} />}
                {c.avatar_url ? (
                    <img src={c.avatar_url} alt=""
                        className={`${size} rounded-full object-cover`}
                        style={{ boxShadow: shadow }} />
                ) : (
                    <span className={`${size} flex items-center justify-center rounded-full bg-white font-bold text-ink`}
                        style={{ boxShadow: shadow }}>
                        {(c.name ?? '?').trim().charAt(0).toUpperCase()}
                    </span>
                )}
            </div>

            <div className="kc-pop mt-2 w-full px-1 text-center" style={{ animationDelay: `${delay + 0.45}s` }}>
                <p className="truncate text-sm font-semibold text-white">{c.name}</p>
                <p className="text-xs leading-tight text-white/85">{c.posts_count} karya, {c.likes_count} suka</p>
                {isMe && (
                    <span className="mt-1 inline-block rounded-full bg-white px-2 py-0.5 text-[10px] font-bold text-ink">
                        Kamu!
                    </span>
                )}
            </div>

            <div className="kc-bar mt-2 flex w-full items-start justify-center rounded-t-xl pt-1"
                style={{
                    '--h': `${HEIGHTS[i] ?? 24}px`,
                    animationDelay: `${delay}s`,
                    background: `linear-gradient(180deg, ${color} 0%, ${color}B3 100%)`,
                }}>
                <span className="text-lg font-bold leading-none text-ink/80">{rank}</span>
            </div>
        </li>
    );
}

export default function ContributorCard({ data }) {
    // Id user yang sedang login (dari shared props Inertia)
    const meId = usePage().props.auth?.user?.id;

    const items = data?.items?.slice(0, 5) ?? [];
    const meIndex = meId != null ? items.findIndex((c) => c.id === meId) : -1;
    const me = meIndex >= 0 ? items[meIndex] : null;
    const meRank = me ? (me.rank ?? meIndex + 1) : null;

    // Ucapan selamat tampil sekali per siswa per bulan (diingat di browser).
    const storageKey = me ? `bintang-mading:${data.period}:${meId}` : null;
    const [celebrate, setCelebrate] = useState(false);

    useEffect(() => {
        if (!storageKey) return;
        try {
            if (!localStorage.getItem(storageKey)) setCelebrate(true);
        } catch {
            setCelebrate(true);
        }
    }, [storageKey]);

    const closeCelebration = useCallback(() => {
        setCelebrate(false);
        try {
            if (storageKey) localStorage.setItem(storageKey, '1');
        } catch {
            // penyimpanan browser tidak tersedia; abaikan
        }
    }, [storageKey]);

    if (!items.length) return null;

    // Susun ala podium: yang pertama di tengah, lalu kiri-kanan bergantian
    const left = [];
    const right = [];
    let center = null;
    items.forEach((c, i) => {
        if (i === 0) center = { c, i };
        else if (i % 2 === 1) left.unshift({ c, i });
        else right.push({ c, i });
    });
    const arranged = [...left, center, ...right];

    return (
        <>
            <style>{CSS}</style>

            {celebrate && me && (
                <CelebrationDialog me={me} rank={meRank} label={data.label} onClose={closeCelebration} />
            )}

            <section className="kc-root relative mb-6 overflow-hidden rounded-3xl p-5 text-white shadow-lg">
                <Confetti />

                {/* Layar lebar: judul di kiri, podium di kanan. Layar kecil: ditumpuk. */}
                <div className="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:gap-10">
                    <div className="flex items-center gap-4 lg:w-72 lg:shrink-0">
                        <span className="kc-wobble flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white shadow-md">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#F59E0B" strokeWidth="2"
                                strokeLinecap="round" strokeLinejoin="round" className="h-5 w-5" aria-hidden="true">
                                <path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 01-10 0zM17 5h3v2a3 3 0 01-3 3M7 5H4v2a3 3 0 003 3" />
                            </svg>
                        </span>
                        <div>
                            <h2 className="text-xl leading-tight text-white">
                                {me ? `Selamat, ${firstNameOf(me.name)}!` : `${TITLE} ${data.label}`}
                            </h2>
                            <p className="text-sm text-white/85">
                                {me
                                    ? `Kamu peringkat ${meRank} ${TITLE} ${data.label}.`
                                    : 'Paling aktif membagikan karya.'}
                            </p>
                        </div>
                    </div>

                    <ul className="mx-auto mt-3 grid w-full max-w-xl auto-cols-fr grid-flow-col items-end gap-2 sm:gap-3 lg:mt-4 lg:flex-1">
                        {arranged.map(({ c, i }) => (
                            <Column key={c.id} c={c} i={i} total={items.length} isMe={meId != null && c.id === meId} />
                        ))}
                    </ul>
                </div>
            </section>
        </>
    );
}