import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import PublicLayout from '../Layouts/PublicLayout';
import Logo from '../Components/Logo';
import Reveal from '../Components/Reveal';
import Icon from '../Components/Icon';
import Footer from '../Components/Landing/Footer';

const W = 'mx-auto w-full max-w-7xl px-6 lg:px-10';
const clean = (o) => Object.fromEntries(Object.entries(o).filter(([, v]) => v));

// [nama file ikon, deskripsi, warna border]
const CAT = {
    'Pengumuman':       ['pengumuman', 'Informasi penting dan pemberitahuan sekolah.', 'border-rose-400'],
    'Prestasi':         ['prestasi', 'Pencapaian dan prestasi siswa.', 'border-amber-400'],
    'Info Lomba':       ['info-lomba', 'Informasi kompetisi yang bisa kamu ikuti.', 'border-orange-400'],
    'Ekstrakurikuler':  ['ekstrakurikuler', 'Kegiatan dan kabar ekstrakurikuler.', 'border-sky-400'],
    'Karya Siswa':      ['karya-siswa', 'Tempat menampilkan karya dan kreativitas.', 'border-violet-400'],
    'Kegiatan Sekolah': ['kegiatan-sekolah', 'Acara dan agenda di lingkungan sekolah.', 'border-emerald-400'],
    'Opini':            ['opini', 'Bagikan pendapat dan gagasanmu.', 'border-teal-400'],
};

// warna kartu bergantian (latar + border)
const TONE = [
    'bg-rose-100 border-rose-400',
    'bg-amber-100 border-amber-400',
    'bg-sky-100 border-sky-400',
    'bg-emerald-100 border-emerald-400',
];

const STEPS = [
    ['01', 'Buat Postingan', 'Tulis postingan, pilih kategori, tambahkan gambar.'],
    ['02', 'Kirim untuk Ditinjau', 'Postingan dikirim untuk diperiksa sebelum tayang.'],
    ['03', 'Dipublikasikan', 'Jika disetujui, postingan tampil di mading.'],
    ['04', 'Jadi Arsip', 'Setelah masa tayang berakhir, masuk ke arsip.'],
];

const ABOUT = [
    ['pin', 'Bagikan', 'Buat dan kirim postinganmu.'],
    ['eye', 'Temukan', 'Jelajahi informasi dari warga sekolah.'],
    ['bulb', 'Tampilkan', 'Tunjukkan karya dan prestasimu.'],
];

const ROLES = [
    ['student', 'Siswa', 'Membuat dan membagikan postingan, lalu memantau statusnya.'],
    ['eye', 'Pengunjung', 'Menemukan informasi, kegiatan, karya, dan prestasi sekolah.'],
];

const WHY = [
    ['pin', 'Mudah Berbagi', 'Bagikan informasi dan karya dengan mudah.'],
    ['folder', 'Terorganisir', 'Konten tersusun berdasarkan kategori.'],
    ['search', 'Mudah Ditemukan', 'Cari postingan lewat kata kunci dan kategori.'],
    ['check', 'Terkelola', 'Setiap postingan ditinjau sebelum tayang.'],
];

function PostCard({ p, big = false }) {
    const h = big ? 'h-72' : 'h-52';
    return (
        <article className="group h-full overflow-hidden rounded-2xl border-2 border-peach bg-white shadow-md transition duration-300 hover:-translate-y-2 hover:border-brand hover:shadow-2xl">
            <div className="relative overflow-hidden">
                {p.image_url
                    ? <img src={p.image_url} alt="" className={`w-full object-cover transition duration-500 group-hover:scale-110 ${h}`} />
                    : <div className={`flex items-center justify-center bg-linear-to-br from-peach to-grape/50 font-medium text-white ${h}`}>Tanpa gambar</div>}
                {p.is_pinned && (
                    <span className="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-brand px-3 py-1 text-xs font-bold text-white shadow">
                        <Icon name="pin" className="h-4 w-4" /> Disematkan
                    </span>
                )}
            </div>
            <div className="space-y-2 p-5">
                <span className="inline-block rounded-full bg-grape px-3 py-0.5 text-xs font-bold text-white">{p.category}</span>
                <h3 className={`font-bold leading-snug text-ink ${big ? 'text-xl' : 'text-lg'}`}>{p.title}</h3>
                <p className="text-sm text-ink/85">{p.excerpt}</p>
                <p className="pt-1 text-xs font-medium text-ink/70">{p.author} · {p.published_at}</p>
            </div>
        </article>
    );
}

export default function Home({ posts, categories, filters }) {
    const user = usePage().props.auth?.user;
    const [q, setQ] = useState(filters.q ?? '');
    const [showAll, setShowAll] = useState(false);
    const activeCat = filters.category ? String(filters.category) : '';
    const isFiltering = Boolean(filters.q || filters.category);
    const start = user ? '/user/dashboard' : '/register';

    const go = (params, scroll = false) =>
        router.get('/', clean(params), {
            preserveState: true, replace: true, preserveScroll: true,
            onSuccess: () => scroll && document.getElementById('mading')?.scrollIntoView({ behavior: 'smooth' }),
        });

    const search = (e) => { e.preventDefault(); go({ q, category: activeCat }); };

    const chip = (active) =>
        `rounded-full px-4 py-1.5 text-sm font-semibold transition hover:-translate-y-0.5 ${
            active ? 'bg-brand text-white shadow-md' : 'bg-white text-ink ring-2 ring-peach hover:bg-peach/50'}`;

    const pinned = isFiltering ? [] : posts.filter((p) => p.is_pinned);
    const latest = isFiltering ? posts : posts.filter((p) => !p.is_pinned);

    // Landing menampilkan 5 (atau 3 kalau kurang dari 5). Saat mencari/filter, semua hasil ditampilkan.
    const limit = latest.length >= 5 ? 5 : latest.length >= 3 ? 3 : latest.length;
    const shown = isFiltering || showAll ? latest : latest.slice(0, limit);

    return (
        <PublicLayout>
            <Head title="Beranda" />

            {/* HERO */}
            <section className="relative overflow-hidden bg-linear-to-br from-peach via-rose-200 to-grape/60">
                <div className="absolute -left-20 top-0 h-72 w-72 animate-pulse rounded-full bg-brand/30 blur-3xl" />
                <div className="absolute -right-20 bottom-0 h-80 w-80 animate-pulse rounded-full bg-grape/40 blur-3xl" />
                <div className="relative mx-auto grid w-full max-w-7xl items-center gap-4 px-6 pb-10 pt-4 lg:grid-cols-2 lg:gap-2 lg:px-10 lg:pb-12 lg:pt-2">
                    <div>
                        <span className="inline-flex items-center gap-2 rounded-full bg-white px-4 py-1.5 text-xs font-bold text-brand shadow-md">
                            <Icon name="pin" className="h-4 w-4" /> Mading digital sekolah
                        </span>
                        <h1 className="mt-4 text-4xl font-bold leading-tight text-ink sm:text-5xl lg:text-6xl">
                            Satu Spot untuk Semua{' '}
                            <span className="text-brand">Kreativitas</span>
                        </h1>
                        <p className="mt-4 max-w-xl text-lg text-ink/85">
                            Spotly adalah mading digital sekolah tempat siswa membagikan informasi, karya, kegiatan, dan pencapaian secara terorganisir.
                        </p>
                        <div className="mt-6 flex flex-wrap gap-3">
                            <a href="#mading" className="rounded-xl bg-brand px-6 py-3 font-semibold text-white shadow-lg shadow-brand/40 transition hover:-translate-y-1 hover:shadow-xl">Jelajahi Mading</a>
                            <Link href={start} className="rounded-xl bg-ink px-6 py-3 font-semibold text-white transition hover:-translate-y-1 hover:bg-grape">Buat Postingan</Link>
                        </div>
                        <div className="mt-6 flex gap-8">
                            {[[posts.length, 'Postingan tayang'], [categories.length, 'Kategori'], ['2', 'Peran pengguna']].map(([n, l]) => (
                                <div key={l}>
                                    <p className="text-3xl font-bold text-ink">{n}</p>
                                    <p className="text-sm font-medium text-ink/80">{l}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                    <div className="flex justify-center lg:-mt-8 lg:justify-start lg:pl-6">
                        <div className="relative">
                            <div className="absolute inset-0 scale-90 rounded-full bg-white/40 blur-2xl" />
                            <Logo variant="icon" className="animate-float relative h-64 drop-shadow-2xl md:h-100" />
                        </div>
                    </div>
                </div>
            </section>

            {/* TENTANG */}
            <section id="tentang" className="scroll-mt-20 bg-cream py-14">
                <div className={`${W} grid items-center gap-10 lg:grid-cols-2`}>
                    <Reveal>
                        <p className="text-sm font-bold uppercase tracking-widest text-brand">Apa itu Spotly?</p>
                        <h2 className="mt-2 text-3xl font-bold text-ink sm:text-4xl">Papan mading, versi digital.</h2>
                        <p className="mt-4 text-ink/85">
                            Spotly menjadi wadah bagi siswa untuk berbagi informasi, karya, kegiatan, dan pencapaian.
                            Semua postingan ditinjau sebelum tayang, jadi isi mading tetap rapi dan terpercaya.
                        </p>
                    </Reveal>
                    <div className="grid gap-4 sm:grid-cols-3">
                        {ABOUT.map(([i, t, d], n) => (
                            <Reveal key={t} delay={n * 120}>
                                <div className={`h-full rounded-2xl border-2 p-5 shadow-md transition hover:-translate-y-2 hover:shadow-xl ${TONE[n]}`}>
                                    <Icon name={i} className="h-12 w-12" />
                                    <h3 className="mt-3 font-bold text-ink">{t}</h3>
                                    <p className="mt-1 text-sm text-ink/85">{d}</p>
                                </div>
                            </Reveal>
                        ))}
                    </div>
                </div>
            </section>

            {/* KATEGORI */}
            <section id="kategori" className="scroll-mt-20 bg-linear-to-br from-grape/25 via-rose-100 to-peach/60 py-14">
                <div className={W}>
                    <Reveal>
                        <h2 className="text-center text-3xl font-bold text-ink">Apa yang Bisa Kamu Temukan?</h2>
                        <p className="mt-2 text-center text-ink/80">Klik kategori untuk langsung melihat postingannya.</p>
                    </Reveal>
                    <div className="mt-8 flex flex-wrap justify-center gap-4">
                        {categories.map((c, n) => {
                            const [icon, desc, border] = CAT[c.name] ?? ['folder', 'Postingan dalam kategori ini.', 'border-peach'];
                            return (
                                <Reveal key={c.id} delay={(n % 4) * 100}
                                    className="w-full sm:w-[calc(50%-0.5rem)] lg:w-[calc(25%-0.75rem)]">
                                    <button onClick={() => go({ category: c.id }, true)}
                                        className={`group h-full w-full rounded-2xl border-2 bg-white p-5 text-left shadow-md transition hover:-translate-y-2 hover:border-brand hover:bg-brand hover:shadow-xl ${border}`}>
                                        <Icon name={icon} className="h-12 w-12 transition group-hover:scale-125" />
                                        <p className="mt-3 font-bold text-ink group-hover:text-white">{c.name}</p>
                                        <p className="mt-1 text-sm text-ink/85 group-hover:text-white/90">{desc}</p>
                                    </button>
                                </Reveal>
                            );
                        })}
                    </div>
                </div>
            </section>

            {/* CARA KERJA */}
            <section id="cara-kerja" className="scroll-mt-20 bg-ink py-16 text-white">
                <div className={W}>
                    <Reveal>
                        <h2 className="text-center text-3xl font-bold">Dari Ide hingga Tampil di Spotly</h2>
                    </Reveal>
                    <div className="mt-10 grid gap-6 md:grid-cols-4">
                        {STEPS.map(([n, t, d], i) => (
                            <Reveal key={n} delay={i * 150}>
                                <div className="relative h-full rounded-2xl border-2 border-white/20 bg-white/10 p-6 transition hover:-translate-y-2 hover:border-brand hover:bg-white/15">
                                    <span className="text-5xl font-bold text-peach">{n}</span>
                                    <h3 className="mt-3 font-bold">{t}</h3>
                                    <p className="mt-1 text-sm text-white/85">{d}</p>
                                    {i < 3 && <span className="absolute -right-5 top-1/2 hidden -translate-y-1/2 text-2xl text-peach md:block">→</span>}
                                </div>
                            </Reveal>
                        ))}
                    </div>
                </div>
            </section>

            {/* SPOTLIGHT */}
            {pinned.length > 0 && (
                <section className="bg-cream py-14">
                    <div className={W}>
                        <Reveal>
                            <h2 className="flex items-center gap-2 text-3xl font-bold text-ink">
                                <Icon name="pin" className="h-8 w-8" /> Spotlight
                            </h2>
                            <p className="text-ink/80">Informasi pilihan yang sedang menjadi sorotan di sekolah.</p>
                        </Reveal>
                        <div className="mt-6 flex flex-wrap justify-center gap-6">
                            {pinned.map((p, n) => (
                                <Reveal key={p.id} delay={n * 120} className="w-full md:w-[calc(50%-0.75rem)]">
                                    <PostCard p={p} big />
                                </Reveal>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {/* POSTINGAN TERBARU */}
            <section id="mading" className="scroll-mt-20 bg-linear-to-b from-peach/60 to-rose-200 py-14">
                <div className={W}>
                    <Reveal>
                        <h2 className="text-center text-3xl font-bold text-ink">Postingan Terbaru</h2>
                        <p className="mt-2 text-center text-ink/80">Jelajahi informasi, karya, dan kegiatan dari warga sekolah.</p>
                    </Reveal>

                    <form onSubmit={search} className="mx-auto mt-6 flex max-w-xl gap-2">
                        <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Cari postingan..."
                            className="w-full rounded-xl border-2 border-peach bg-white px-4 py-3 shadow-sm outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40" />
                        <button className="rounded-xl bg-brand px-6 font-semibold text-white shadow-md transition hover:-translate-y-0.5 hover:shadow-lg">Cari</button>
                    </form>

                    <div className="my-6 flex flex-wrap justify-center gap-2">
                        <button onClick={() => go({ q })} className={chip(activeCat === '')}>Semua</button>
                        {categories.map((c) => (
                            <button key={c.id} onClick={() => go({ q, category: c.id })} className={chip(activeCat === String(c.id))}>{c.name}</button>
                        ))}
                    </div>

                    {latest.length === 0 ? (
                        <p className="py-16 text-center font-medium text-ink/80">Tidak ada postingan yang cocok.</p>
                    ) : (
                        <>
                            <div className="flex flex-wrap justify-center gap-6">
                                {shown.map((p, n) => (
                                    <Reveal key={p.id} delay={(n % 3) * 80}
                                        className="w-full sm:w-[calc(50%-0.75rem)] lg:w-[calc(33.333%-1rem)]">
                                        <PostCard p={p} />
                                    </Reveal>
                                ))}
                            </div>

                            {!isFiltering && latest.length > limit && (
                                <div className="mt-8 text-center">
                                    <button onClick={() => setShowAll(!showAll)}
                                        className="rounded-xl bg-ink px-6 py-3 font-semibold text-white transition hover:-translate-y-1 hover:bg-brand">
                                        {showAll ? 'Tampilkan lebih sedikit' : `Lihat semua (${latest.length})`}
                                    </button>
                                </div>
                            )}
                        </>
                    )}
                </div>
            </section>

            {/* SIAPA YANG MENGGUNAKAN */}
            <section className="bg-linear-to-b from-cream to-peach/60 py-14">
                <div className={W}>
                    <Reveal>
                        <div className="relative grid items-center gap-4 overflow-hidden rounded-3xl bg-linear-to-br from-grape via-brand to-peach shadow-xl lg:grid-cols-[2fr_3fr]">
                            {/* Gambar: latar tipis di HP */}
                            <img src="/images/siswa.png" alt=""
                                className="pointer-events-none absolute inset-0 h-full w-full object-contain opacity-25 lg:hidden" />

                            {/* Gambar: kolom kiri di layar besar, ada jarak dari tepi */}
                            <div className="hidden p-8 lg:block lg:p-10">
                                <img src="/images/siswa.png" alt=""
                                    className="mx-auto max-h-96 w-full object-contain"
                                    style={{
                                        WebkitMaskImage: 'linear-gradient(to right, black 85%, transparent 100%)',
                                        maskImage: 'linear-gradient(to right, black 85%, transparent 100%)',
                                    }} />
                            </div>

                            {/* Teks dan kartu */}
                            <div className="relative px-6 py-10 lg:py-14 lg:pl-0 lg:pr-12">
                                <h2 className="text-3xl font-bold text-white">Siapa yang Menggunakan Spotly?</h2>
                                <p className="mt-2 text-white/90">Semua warga sekolah punya tempat di mading ini.</p>

                                <div className="mt-6 space-y-4">
                                    {ROLES.map(([i, t, d]) => (
                                        <div key={t}
                                            className="flex items-start gap-4 rounded-2xl border-2 border-white/40 bg-ink/70 p-5 text-white shadow-lg backdrop-blur transition hover:-translate-y-1 hover:bg-ink/80">
                                            <Icon name={i} className="h-14 w-14 shrink-0" />
                                            <div>
                                                <h3 className="text-lg font-bold">{t}</h3>
                                                <p className="mt-1 text-sm text-white/90">{d}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </Reveal>
                </div>
            </section>

            {/* KENAPA SPOTLY */}
            <section id="kenapa-spotly" className="scroll-mt-20 bg-linear-to-br from-grape/20 via-cream to-peach/50 py-14">
                <div className={W}>
                    <Reveal><h2 className="text-center text-3xl font-bold text-ink">Kenapa Spotly?</h2></Reveal>
                    <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {WHY.map(([i, t, d], n) => (
                            <Reveal key={t} delay={n * 100}>
                                <div className={`h-full rounded-2xl border-2 p-5 shadow-md transition hover:-translate-y-2 hover:shadow-xl ${TONE[n]}`}>
                                    <Icon name={i} className="h-12 w-12" />
                                    <h3 className="mt-3 font-bold text-ink">{t}</h3>
                                    <p className="mt-1 text-sm text-ink/85">{d}</p>
                                </div>
                            </Reveal>
                        ))}
                    </div>
                </div>
            </section>

            {/* CTA */}
            <section className={`${W} py-14`}>
                <Reveal>
                    <div className="rounded-3xl bg-linear-to-r from-ink via-grape to-brand px-6 py-14 text-center text-white shadow-xl">
                        <h2 className="text-3xl font-bold sm:text-4xl">Punya sesuatu untuk dibagikan?</h2>
                        <p className="mx-auto mt-3 max-w-lg text-white/90">Jadikan Spotly tempat untuk berbagi informasi, karya, dan kreativitasmu.</p>
                        <Link href={start} className="mt-7 inline-block rounded-xl bg-white px-7 py-3 font-bold text-ink transition hover:-translate-y-1 hover:shadow-2xl">
                            Buat Postingan →
                        </Link>
                    </div>
                </Reveal>
            </section>

            <Footer />
        </PublicLayout>
    );
}