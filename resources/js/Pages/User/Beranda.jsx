import { Head, Link, usePage } from '@inertiajs/react';
import UserLayout from '../../Layouts/UserLayout';
import PostCard from '../../Components/PostCard';
import AdminLayout from '../../Layouts/AdminLayout';

function Chip({ href, active, children }) {
    return (
        <Link href={href}
            className={`shrink-0 rounded-full px-4 py-1.5 text-sm font-semibold transition ${
                active
                    ? 'bg-ink text-white shadow-sm'
                    : 'bg-white text-ink/70 ring-1 ring-ink/10 hover:bg-peach/40'}`}>
            {children}
        </Link>
    );
}

export default function Beranda({ posts, filters, isAdmin }) {
    const Layout = isAdmin ? AdminLayout : UserLayout;
    const { auth, categories = [] } = usePage().props;
    const catName = categories.find((c) => String(c.id) === String(filters.category))?.name;

    let title = 'Informasi Terbaru';
    let subtitle = 'Temukan informasi terbaru dari sekolah.';
    if (filters.spotlight) {
        title = 'Spotlight';
        subtitle = 'Informasi pilihan yang sedang menjadi sorotan.';
    } else if (catName) {
        title = catName;
        subtitle = `Semua postingan dalam kategori ${catName}.`;
    } else if (filters.q) {
        title = `Hasil untuk "${filters.q}"`;
        subtitle = `${posts.total} postingan ditemukan.`;
    }

    const isFiltering = Boolean(filters.q || filters.category || filters.spotlight);
    const noCategory = !filters.category && !filters.spotlight;

    return (
        <Layout tittle="Mading">
            <Head title="Beranda" />

            {!isFiltering && (
                <p className="mb-1 text-sm font-semibold text-brand">Halo, {auth?.user?.name}</p>
            )}

            <div className="mb-5 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 className="text-3xl text-ink">{title}</h1>
                    <p className="mt-1 text-sm text-ink/60">{subtitle}</p>
                </div>
                {isFiltering && (
                    <Link href="/user/beranda"
                        className="rounded-lg border border-ink/20 px-3 py-1.5 text-sm font-semibold text-ink hover:bg-ink/5">
                        Hapus filter
                    </Link>
                )}
            </div>

            {/* Filter kategori */}
            <div className="mb-8 flex gap-2 overflow-x-auto pb-1 scrollbar-thin">
                <Chip href="/user/beranda" active={noCategory}>Semua</Chip>
                {categories.map((c) => (
                    <Chip key={c.id} href={`/user/beranda?category=${c.id}`}
                        active={String(c.id) === String(filters.category)}>
                        {c.name}
                    </Chip>
                ))}
            </div>

            {posts.data.length === 0 ? (
                <div className="rounded-2xl border border-dashed border-peach bg-white/60 px-6 py-16 text-center">
                    <h2 className="text-xl text-ink">Belum ada postingan</h2>
                    <p className="mx-auto mt-2 max-w-sm text-sm text-ink/60">
                        {isFiltering
                            ? 'Tidak ada postingan yang cocok dengan pilihanmu.'
                            : 'Belum ada postingan yang tayang. Jadilah yang pertama membagikan sesuatu.'}
                    </p>
                </div>
            ) : (
                <div className="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    {posts.data.map((p) => <PostCard key={p.id} p={p} />)}
                </div>
            )}

            {/* Halaman */}
            {posts.last_page > 1 && (
                <div className="mt-8 flex flex-wrap justify-center gap-1">
                    {posts.links.map((l, i) =>
                        l.url ? (
                            <Link key={i} href={l.url}
                                className={`rounded-full px-3.5 py-1.5 text-sm font-semibold ${
                                    l.active ? 'bg-ink text-white' : 'bg-white text-ink/70 ring-1 ring-ink/10 hover:bg-peach/40'}`}
                                dangerouslySetInnerHTML={{ __html: l.label }} />
                        ) : (
                            <span key={i} className="px-3.5 py-1.5 text-sm text-ink/30"
                                dangerouslySetInnerHTML={{ __html: l.label }} />
                        )
                    )}
                </div>
            )}
        </Layout>
    );
}