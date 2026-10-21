import { Head, Link, usePage } from '@inertiajs/react';
import UserLayout from '../../Layouts/UserLayout';
import StatusBadge from '../../Components/StatusBadge';

const BARS = [
    ['draft', 'Draft'],
    ['submitted', 'Review'],
    ['published', 'Tayang'],
    ['rejected', 'Ditolak'],
    ['archived', 'Arsip'],
];

const PILLS = [
    ['draft', 'Draft', 'bg-ink text-white'],
    ['submitted', 'Menunggu', 'bg-brand text-white'],
    ['published', 'Tayang', 'bg-grape text-white'],
];

// Komposisi status: [kunci, label, warna]
const SEGMENTS = [
    ['draft', 'Draft', 'bg-ink'],
    ['submitted', 'Review', 'bg-brand'],
    ['published', 'Tayang', 'bg-grape'],
    ['rejected', 'Ditolak', 'bg-red-400'],
    ['archived', 'Arsip', 'bg-peach'],
];

const SHORTCUTS = [
    ['Postingan Saya', 'Kelola semua kirimanmu', '/user/posts'],
    ['Disukai', 'Postingan yang kamu sukai', '/user/disukai'],
    ['Tersimpan', 'Bacaan untuk nanti', '/user/tersimpan'],
    ['Riwayat', 'Yang pernah kamu baca', '/user/riwayat'],
];

const TIPS = [
    'Judul yang singkat dan jelas lebih mudah ditemukan lewat pencarian.',
    'Tambahkan foto sampul. Postingan bergambar lebih menarik di mading.',
    'Pilih kategori yang paling pas supaya pembaca yang tepat menemukannya.',
    'Cek tombol Pratinjau, lalu pilih Mobile untuk melihat tampilannya di HP.',
    'Belum selesai? Simpan sebagai draf. Tulisanmu tersimpan otomatis.',
    'Pakai subjudul di tulisan panjang supaya mudah dipindai pembaca.',
    'Untuk pengumuman, isi tanggal berakhir supaya mading tetap rapi.',
];

function Card({ className = '', children }) {
    return (
        <section className={`min-w-0 rounded-3xl bg-white/80 p-5 shadow-sm ring-1 ring-ink/5 ${className}`}>
            {children}
        </section>
    );
}

function CardHead({ title, href }) {
    return (
        <div className="mb-4 flex items-center justify-between">
            <h2 className="text-lg text-ink">{title}</h2>
            {href && (
                <Link href={href} aria-label={`Buka ${title}`}
                    className="flex h-9 w-9 items-center justify-center rounded-full bg-white text-ink shadow-sm ring-1 ring-ink/10 hover:bg-peach/40">
                    &#8599;
                </Link>
            )}
        </div>
    );
}

function Donut({ percent }) {
    const r = 52;
    const c = 2 * Math.PI * r;
    return (
        <div className="relative mx-auto h-40 w-40">
            <svg viewBox="0 0 120 120" className="h-full w-full -rotate-90">
                <circle cx="60" cy="60" r={r} fill="none" strokeWidth="10" className="stroke-peach/40"/>
                {percent > 0 && (
                    <circle cx="60" cy="60" r={r} fill="none" strokeWidth="10" strokeLinecap="round"
                        className="stroke-brand"
                        strokeDasharray={`${(percent / 100) * c} ${c}`} />
                )}
            </svg>
            <div className="absolute inset-0 flex flex-col items-center justify-center">
                <span className="text-4xl font-light text-ink">{percent}%</span>
                <span className="text-xs text-ink/60">sedang tayang</span>
            </div>
        </div>
    );
}

export default function Dashboard({ counts, attention, expiring, recent }) {
    const user = usePage().props.auth?.user;
    const initial = (user?.name ?? '?').trim().charAt(0).toUpperCase();
    const maxBar = Math.max(...BARS.map(([k]) => counts[k]), 1);
    const topKey = BARS.reduce((a, [k]) => (counts[k] > counts[a] ? k : a), 'draft');
    const percent = counts.total ? Math.round((counts.published / counts.total) * 100) : 0;

    // Komposisi status (lebar tiap segmen dihitung dari jumlah kelima status)
    const segTotal = SEGMENTS.reduce((sum, [k]) => sum + counts[k], 0);

    // Langkah berikutnya: disusun dari kondisi kirimanmu saat ini
    const steps = [];
    if (counts.total === 0) {
        steps.push(['Tulis postingan pertamamu', '/user/posts/create']);
    }
    if (attention.length > 0) {
        steps.push([`Periksa ${attention.length} postingan yang perlu perhatian`, '/user/posts']);
    }
    if (counts.draft > 0) {
        steps.push([`Selesaikan ${counts.draft} draf lalu kirim ke review`, '/user/posts']);
    }
    if (counts.submitted > 0) {
        steps.push([`${counts.submitted} postingan sedang menunggu review admin`, '/user/posts']);
    }
    if (steps.length === 0) {
        steps.push(['Semua beres. Tulis sesuatu yang baru?', '/user/posts/create']);
    }

    // Tips berganti setiap hari
    const tip = TIPS[Math.floor(Date.now() / 86400000) % TIPS.length];

    return (
        <UserLayout>
            <Head title="Dashboard" />

            {/* Sapaan + pill + angka besar */}
            <div className="mb-8 flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div className="min-w-0 flex-1">
                    <h1 className="mb-5 wrap-break-word text-4xl font-light text-ink sm:text-5xl">Halo, {user?.name}</h1>
                    <div className="flex max-w-xl gap-2">
                        {PILLS.map(([key, label, cls]) => (
                            <div key={key} className="min-w-20"
                                style={{ flexGrow: Math.max(counts[key], 0.6), flexBasis: 0 }}>
                                <p className="mb-1 text-xs text-ink/70">{label}</p>
                                <div className={`rounded-full px-3 py-2 text-xs font-semibold ${cls}`}>
                                    {counts[key]}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="flex flex-wrap gap-x-8 gap-y-4">
                    {[
                        ['total', 'Total kiriman', 'text-ink'],
                        ['published', 'Tayang', 'text-grape'],
                        ['submitted', 'Menunggu review', 'text-brand'],
                    ].map(([key, label, color]) => (
                        <div key={key}>
                            <p className={`text-5xl font-light leading-none sm:text-6xl ${color}`}>{counts[key]}</p>
                            <p className="mt-1 text-xs font-medium text-ink/60">{label}</p>
                        </div>
                    ))}
                </div>
            </div>

            {/* Pintasan cepat */}
            <div className="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
                {SHORTCUTS.map(([label, desc, href]) => (
                    <Link key={href} href={href}
                        className="min-w-0 rounded-2xl bg-white/80 p-4 shadow-sm ring-1 ring-ink/5 transition hover:bg-peach/30">
                        <p className="truncate text-sm font-bold text-ink">{label}</p>
                        <p className="mt-0.5 line-clamp-2 text-xs text-ink/60">{desc}</p>
                    </Link>
                ))}
            </div>

            {/* grid-cols-1 wajib: tanpa kolom dasar, kolom melebar mengikuti isi dan halaman bisa digeser ke samping di HP */}
            <div className="grid grid-flow-dense grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                {/* Kartu profil */}
                <div className="relative flex min-h-64 min-w-0 flex-col justify-between overflow-hidden rounded-3xl bg-linear-to-br from-grape to-brand p-5 text-white shadow-sm">
                    <span className="flex h-16 w-16 items-center justify-center rounded-full bg-white/20 text-3xl font-bold">
                        {initial}
                    </span>
                    <div>
                        <p className="wrap-break-word text-xl font-semibold">{user?.name}</p>
                        <p className="mb-4 text-sm text-white/80">{counts.total} kiriman</p>
                        <Link href="/user/posts/create"
                            className="inline-block rounded-full border border-white/70 px-4 py-2 text-sm font-semibold hover:bg-white/15">
                            + Buat Postingan
                        </Link>
                    </div>
                </div>

                {/* Grafik batang status */}
                <Card>
                    <CardHead title="Status kiriman" href="/user/posts" />
                    <div className="flex h-40 items-end justify-between gap-2">
                        {BARS.map(([key, label]) => (
                            <div key={key} className="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-1">
                                <span className="text-xs font-semibold text-ink">{counts[key]}</span>
                                <div
                                    className={`w-3 rounded-full ${key === topKey && counts[key] > 0 ? 'bg-brand' : 'bg-grape'}`}
                                    style={{ height: `${Math.max((counts[key] / maxBar) * 75, 4)}%` }}
                                />
                                <span className="text-[11px] text-ink/60">{label}</span>
                            </div>
                        ))}
                    </div>
                </Card>

                {/* Cincin persentase */}
                <Card>
                    <CardHead title="Tingkat tayang" href="/user/beranda" />
                    <Donut percent={percent} />
                </Card>

                {/* Kartu gelap: perlu perhatian (menempati 2 baris di layar lebar) */}
                <div className="min-w-0 rounded-3xl bg-ink p-5 text-white shadow-sm lg:row-span-2">
                    <div className="mb-4 flex items-baseline justify-between">
                        <h2 className="text-lg">Perlu perhatian</h2>
                        <span className="text-3xl font-light">{attention.length}</span>
                    </div>
                    {attention.length === 0 ? (
                        <p className="text-sm text-white/70">Tidak ada postingan ditolak. Semua aman.</p>
                    ) : (
                        <ul className="space-y-4">
                            {attention.map((p) => (
                                <li key={p.id} className="flex items-start gap-3">
                                    <span className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/10 text-sm">
                                        !
                                    </span>
                                    <div className="min-w-0 text-sm">
                                        <p className="truncate font-semibold">{p.title}</p>
                                        <p className="line-clamp-2 text-xs text-white/70">{p.rejection_reason}</p>
                                        <Link href={`/user/posts/${p.id}/edit`}
                                            className="mt-1 inline-block text-xs font-semibold text-peach hover:underline">
                                            Perbaiki dan kirim ulang
                                        </Link>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                {/* Segera berakhir */}
                <Card>
                    <CardHead title="Segera berakhir" />
                    {expiring.length === 0 ? (
                        <p className="text-sm text-ink/60">Tidak ada yang berakhir dalam 3 hari ke depan.</p>
                    ) : (
                        <ul className="divide-y divide-dashed divide-ink/15 text-sm">
                            {expiring.map((p) => (
                                <li key={p.id} className="py-2.5">
                                    <p className="truncate font-medium text-ink">{p.title}</p>
                                    <p className="text-xs text-ink/60">sampai {p.expired_at}</p>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>

                {/* Kiriman terbaru */}
                <Card className="sm:col-span-2">
                    <CardHead title="Kiriman terbaru" href="/user/posts" />
                    {recent.length === 0 ? (
                        <p className="text-sm text-ink/60">
                            Belum ada postingan.{' '}
                            <Link href="/user/posts/create" className="font-semibold text-brand hover:underline">
                                Buat yang pertama
                            </Link>
                        </p>
                    ) : (
                        <ul className="space-y-2">
                            {recent.map((p) => (
                                <li key={p.id}
                                    className="flex items-center justify-between gap-3 rounded-2xl bg-cream/70 px-4 py-2.5 text-sm">
                                    <div className="min-w-0">
                                        <p className="truncate font-semibold text-ink">{p.title}</p>
                                        <p className="text-xs text-ink/60">{p.category}</p>
                                    </div>
                                    <StatusBadge status={p.display_status} />
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>

                {/* Komposisi status */}
                <Card className="sm:col-span-2">
                    <CardHead title="Komposisi kiriman" />
                    {segTotal === 0 ? (
                        <p className="text-sm text-ink/60">Belum ada kiriman untuk dihitung.</p>
                    ) : (
                        <>
                            <div className="flex h-4 overflow-hidden rounded-full bg-ink/5"
                                role="img"
                                aria-label={SEGMENTS.filter(([k]) => counts[k] > 0)
                                    .map(([k, label]) => `${label} ${counts[k]}`).join(', ')}>
                                {SEGMENTS.map(([key, , color]) => counts[key] > 0 && (
                                    <div key={key} className={color}
                                        style={{ width: `${(counts[key] / segTotal) * 100}%` }} />
                                ))}
                            </div>
                            <ul className="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 text-sm sm:grid-cols-3">
                                {SEGMENTS.map(([key, label, color]) => (
                                    <li key={key} className="flex items-center gap-2">
                                        <span className={`h-3 w-3 shrink-0 rounded-full ${color}`} />
                                        <span className="truncate text-ink/70">{label}</span>
                                        <span className="ml-auto font-semibold text-ink">{counts[key]}</span>
                                    </li>
                                ))}
                            </ul>
                        </>
                    )}
                </Card>

                {/* Langkah berikutnya */}
                <Card className="sm:col-span-2 lg:col-span-1">
                    <CardHead title="Langkah berikutnya" />
                    <ul className="space-y-2 text-sm">
                        {steps.map(([text, href]) => (
                            <li key={text}>
                                <Link href={href}
                                    className="block rounded-2xl bg-cream/70 px-4 py-2.5 font-medium text-ink transition hover:bg-peach/40">
                                    {text}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </Card>

                {/* Tips menulis */}
                <Card>
                    <CardHead title="Tips hari ini" />
                    <p className="text-sm leading-relaxed text-ink/80">{tip}</p>
                </Card>
            </div>
        </UserLayout>
    );
}