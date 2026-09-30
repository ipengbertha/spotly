import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import PublicLayout from '../Layouts/PublicLayout';
import Logo from '../Components/Logo';

const TILT = ['rotate-1', '-rotate-1', 'rotate-0'];
const clean = (o) => Object.fromEntries(Object.entries(o).filter(([, v]) => v));

export default function Home({ posts, categories, filters }) {
    const [q, setQ] = useState(filters.q ?? '');
    const activeCat = filters.category ? String(filters.category) : '';

    const go = (params) =>
        router.get('/', clean(params), { preserveState: true, replace: true });

    const search = (e) => {
        e.preventDefault();
        go({ q, category: activeCat });
    };

    const chip = (active) =>
        `rounded-full px-4 py-1.5 text-sm font-semibold transition ${
            active
                ? 'bg-brand text-white shadow'
                : 'bg-white text-ink ring-1 ring-peach/60 hover:bg-peach/30'
        }`;

    return (
        <PublicLayout>
            <Head title="Beranda" />

            {/* Hero */}
            <section className="relative overflow-hidden py-8">
                <div className="pointer-events-none absolute -left-16 -top-10 h-56 w-56 rounded-full bg-peach/50 blur-3xl" />
                <div className="pointer-events-none absolute -right-16 top-10 h-56 w-56 rounded-full bg-grape/20 blur-3xl" />

                <div className="relative grid items-center gap-8 md:grid-cols-2">
                    <div className="text-center md:text-left">
                        <h1 className="sr-only">SPOTLY – A Spot for Innovation</h1>
                        <Logo className="mx-auto h-24 mix-blend-multiply md:mx-0 md:h-32" />
                        <p className="mx-auto mt-4 max-w-md text-ink/70 md:mx-0">
                            Mading digital sekolah: info lomba, pengumuman, dan karya terbaru dalam satu tempat.
                        </p>

                        <form onSubmit={search} className="mx-auto mt-6 flex max-w-md gap-2 md:mx-0">
                            <input value={q} onChange={(e) => setQ(e.target.value)}
                                placeholder="Cari postingan..."
                                className="w-full rounded-xl border border-peach/60 bg-white px-4 py-2.5 shadow-sm outline-none focus:ring-2 focus:ring-brand" />
                            <button className="rounded-xl bg-brand px-5 font-semibold text-white hover:opacity-90">Cari</button>
                        </form>
                    </div>

                    <div className="flex justify-center">
                        <Logo variant="icon"
                            className="h-40 rotate-6 mix-blend-multiply drop-shadow-xl md:h-64" />
                    </div>
                </div>
            </section>

            {/* Filter kategori */}
            <div className="mb-8 flex flex-wrap justify-center gap-2">
                <button onClick={() => go({ q })} className={chip(activeCat === '')}>Semua</button>
                {categories.map((c) => (
                    <button key={c.id} onClick={() => go({ q, category: c.id })}
                        className={chip(activeCat === String(c.id))}>
                        {c.name}
                    </button>
                ))}
            </div>

            {/* Grid poster */}
            {posts.length === 0 ? (
                <p className="py-16 text-center text-ink/60">Tidak ada postingan yang cocok.</p>
            ) : (
                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {posts.map((p, i) => (
                        <article key={p.id}
                            className={`overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-peach/40 transition duration-200 hover:-translate-y-1 hover:rotate-0 hover:shadow-xl ${TILT[i % 3]}`}>
                            <div className="relative">
                                {p.image_url
                                    ? <img src={p.image_url} alt="" className="h-48 w-full object-cover" />
                                    : <div className="flex h-48 items-center justify-center bg-peach/20 text-ink/40">Tanpa gambar</div>}
                                {p.is_pinned && (
                                    <span className="absolute left-3 top-3 rounded-full bg-peach px-3 py-1 text-xs font-bold text-ink shadow">
                                        📌 Disematkan
                                    </span>
                                )}
                            </div>
                            <div className="space-y-2 p-4">
                                <span className="text-xs font-semibold uppercase tracking-wide text-grape">{p.category}</span>
                                <h2 className="text-lg font-bold leading-snug">{p.title}</h2>
                                <p className="text-sm text-ink/70">{p.excerpt}</p>
                                <p className="pt-1 text-xs text-ink/50">{p.author} · {p.published_at}</p>
                            </div>
                        </article>
                    ))}
                </div>
            )}
        </PublicLayout>
    );
}