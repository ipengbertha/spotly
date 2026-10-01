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

function Card({ className = '', children }) {
    return (
        <section className={`rounded-3xl bg-white/80 p-5 shadow-sm ring-1 ring-ink/5 ${className}`}>
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

    return (
        <UserLayout>
            <Head title="Dashboard" />

            {/* Sapaan + pill + angka besar */}
            <div className="mb-8 flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div className="min-w-0 flex-1">
                    <h1 className="mb-5 text-4xl font-light text-ink sm:text-5xl">Halo, {user?.name}</h1>
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

                <div className="flex gap-8">
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

            <div className="grid gap-5 lg:grid-cols-4">
                {/* Kartu profil */}
                <div className="relative flex min-h-64 flex-col justify-between overflow-hidden rounded-3xl bg-linear-to-br from-grape to-brand p-5 text-white shadow-sm">
                    <span className="flex h-16 w-16 items-center justify-center rounded-full bg-white/20 text-3xl font-bold">
                        {initial}
                    </span>
                    <div>
                        <p className="text-xl font-semibold">{user?.name}</p>
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
                            <div key={key} className="flex h-full flex-1 flex-col items-center justify-end gap-1">
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

                {/* Kartu gelap: perlu perhatian (menempati 2 baris) */}
                <div className="rounded-3xl bg-ink p-5 text-white shadow-sm lg:row-span-2">
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
                <Card className="lg:col-span-2">
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
            </div>
        </UserLayout>
    );
}