import { Head, Link, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';

const BARS = [
    ['submitted', 'Review'],
    ['active', 'Tayang'],
    ['archive', 'Arsip'],
    ['rejected', 'Ditolak'],
];

const PILLS = [
    ['submitted', 'Menunggu', 'bg-brand text-white'],
    ['active', 'Tayang', 'bg-grape text-white'],
    ['archive', 'Arsip', 'bg-ink text-white'],
];

function Card({ className = '', children }) {
    return (
        <section className={`rounded-3xl bg-white p-5 shadow-md ring-1 ring-ink/5 ${className}`}>
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
                <circle cx="60" cy="60" r={r} fill="none" strokeWidth="10" className="stroke-peach/40" />
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

export default function Dashboard({ stats, queue }) {
    const user = usePage().props.auth?.user;
    const initial = (user?.name ?? '?').trim().charAt(0).toUpperCase();

    const processed = stats.submitted + stats.active + stats.archive + stats.rejected;
    const percent = processed ? Math.round((stats.active / processed) * 100) : 0;
    const maxBar = Math.max(...BARS.map(([k]) => stats[k]), 1);
    const topKey = BARS.reduce((a, [k]) => (stats[k] > stats[a] ? k : a), 'submitted');

    return (
        <AdminLayout title="Dashboard">
            <Head title="Dashboard Admin" />

            {/* Sapaan + pill + angka besar */}
            <div className="mb-8 flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div className="min-w-0 flex-1">
                    <h1 className="mb-5 text-4xl font-light text-ink sm:text-5xl">Halo, {user?.name}</h1>
                    <div className="flex max-w-xl gap-2">
                        {PILLS.map(([key, label, cls]) => (
                            <div key={key} className="min-w-20"
                                style={{ flexGrow: Math.max(stats[key], 0.6), flexBasis: 0 }}>
                                <p className="mb-1 text-xs text-ink/70">{label}</p>
                                <div className={`rounded-full px-3 py-2 text-xs font-semibold ${cls}`}>
                                    {stats[key]}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="flex gap-8">
                    {[
                        ['submitted', 'Menunggu review', 'text-brand'],
                        ['active', 'Tayang', 'text-grape'],
                        ['users', 'Pengguna', 'text-ink'],
                    ].map(([key, label, color]) => (
                        <div key={key}>
                            <p className={`text-5xl font-light leading-none sm:text-6xl ${color}`}>{stats[key]}</p>
                            <p className="mt-1 text-xs font-medium text-ink/60">{label}</p>
                        </div>
                    ))}
                </div>
            </div>

            <div className="grid gap-5 lg:grid-cols-4">
                {/* Kartu admin */}
                <div className="flex min-h-64 flex-col justify-between overflow-hidden rounded-3xl bg-linear-to-br from-grape to-brand p-5 text-white shadow-md">
                    <span className="flex h-16 w-16 items-center justify-center rounded-full bg-white/20 text-3xl font-bold">
                        {initial}
                    </span>
                    <div>
                        <p className="text-xl font-semibold">{user?.name}</p>
                        <p className="mb-4 text-sm text-white/80">Administrator</p>
                        <Link href="/admin/reviews"
                            className="inline-block rounded-full border border-white/70 px-4 py-2 text-sm font-semibold hover:bg-white/15">
                            Buka Antrean Review
                        </Link>
                    </div>
                </div>

                {/* Grafik batang */}
                <Card>
                    <CardHead title="Status postingan" href="/admin/reviews" />
                    <div className="flex h-40 items-end justify-between gap-2">
                        {BARS.map(([key, label]) => (
                            <div key={key} className="flex h-full flex-1 flex-col items-center justify-end gap-1">
                                <span className="text-xs font-semibold text-ink">{stats[key]}</span>
                                <div
                                    className={`w-3 rounded-full ${key === topKey && stats[key] > 0 ? 'bg-brand' : 'bg-grape'}`}
                                    style={{ height: `${Math.max((stats[key] / maxBar) * 75, 4)}%` }}
                                />
                                <span className="text-[11px] text-ink/60">{label}</span>
                            </div>
                        ))}
                    </div>
                </Card>

                {/* Cincin */}
                <Card>
                    <CardHead title="Tingkat tayang" href="/" />
                    <Donut percent={percent} />
                </Card>

                {/* Kartu gelap: perlu ditinjau */}
                <div className="flex flex-col rounded-3xl bg-ink p-5 text-white shadow-md lg:row-span-2">
                    <div className="mb-4 flex items-baseline justify-between">
                        <h2 className="text-lg">Perlu ditinjau</h2>
                        <span className="text-3xl font-light">{stats.submitted}</span>
                    </div>
                    {stats.submitted === 0 ? (
                        <p className="text-sm text-white/70">Antrean kosong. Semua postingan sudah diproses.</p>
                    ) : (
                        <>
                            <p className="text-sm text-white/70">
                                {stats.submitted} postingan menunggu keputusanmu. Yang paling lama menunggu
                                ada di urutan teratas antrean.
                            </p>
                            <Link href="/admin/reviews"
                                className="mt-5 inline-block self-start rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white hover:opacity-90">
                                Tinjau sekarang
                            </Link>
                        </>
                    )}
                </div>

                {/* Pengguna */}
                <Card>
                    <CardHead title="Pengguna" />
                    <p className="text-5xl font-light text-ink">{stats.users}</p>
                    <p className="mt-1 text-sm text-ink/60">akun terdaftar dengan peran user</p>
                </Card>

                {/* Antrean terbaru */}
                <Card className="lg:col-span-2">
                    <CardHead title="Antrean terbaru" href="/admin/reviews" />
                    {queue.length === 0 ? (
                        <p className="text-sm text-ink/60">Tidak ada postingan yang menunggu review.</p>
                    ) : (
                        <ul className="space-y-2">
                            {queue.map((p) => (
                                <li key={p.id}
                                    className="flex items-center justify-between gap-3 rounded-2xl bg-cream/70 px-4 py-2.5 text-sm">
                                    <div className="min-w-0">
                                        <p className="truncate font-semibold text-ink">{p.title}</p>
                                        <p className="text-xs text-ink/60">
                                            {p.user?.name} &middot; {p.category?.name} &middot;{' '}
                                            {new Date(p.updated_at).toLocaleDateString('id-ID')}
                                        </p>
                                    </div>
                                    <Link href="/admin/reviews"
                                        className="shrink-0 rounded-full bg-grape/10 px-3 py-1 text-xs font-semibold text-grape hover:bg-grape/20">
                                        Tinjau
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>
            </div>
        </AdminLayout>
    );
}