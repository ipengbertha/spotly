import { Head, Link, usePage } from '@inertiajs/react';
import UserLayout from '../../Layouts/UserLayout';
import AdminLayout from '../../Layouts/AdminLayout';
import PostCard from '../../Components/PostCard';
import PollCard from '../../Components/PollCard';
import ContributorCard from '../../Components/ContributorCard';
import CategoryBar from '../../Components/CategoryBar';

// Pilihan filter tanggal (nilai harus sama dengan FeedController::PERIOD_OPTIONS)
const PERIODS = [
    { value: '7',           label: '7 hari terakhir',  phrase: 'dalam 7 hari terakhir' },
    { value: '30',          label: '30 hari terakhir', phrase: 'dalam 30 hari terakhir' },
    { value: '90', label: '3 bulan terakhir', phrase: 'dalam 3 bulan terakhir' },
    { value: 'month',       label: 'Bulan ini',        phrase: 'bulan ini' },
    { value: 'school_year', label: 'Tahun ajaran ini', phrase: 'pada tahun ajaran ini' },
];

export default function Beranda({ posts, filters, isAdmin, categories = [], poll = null, contributors = null }) {
    const { auth } = usePage().props;
    const Layout = isAdmin ? AdminLayout : UserLayout;
    const catName = categories.find((c) => String(c.id) === String(filters.category))?.name;

    // Alamat Mading dengan filter yang sedang aktif; `overrides` mengubah sebagian saja (null = dihapus)
    const buildHref = (overrides = {}) => {
        const params = {
            q: filters.q,
            category: filters.category,
            period: filters.period,
            spotlight: filters.spotlight ? 1 : null,
            ...overrides,
        };
        const qs = new URLSearchParams(
            Object.entries(params).filter(([, v]) => v !== null && v !== undefined && v !== '' && v !== false)
        ).toString();
        return qs ? `/user/beranda?${qs}` : '/user/beranda';
    };

    const activePeriod = PERIODS.find((x) => x.value === String(filters.period));

    let title = 'Informasi Terbaru';
    let subtitle = 'Temukan informasi terbaru dari sekolah.';
    if (filters.spotlight) {
        title = 'Spotlight';
        subtitle = 'Informasi pilihan yang sedang menjadi sorotan.';
    } else if (filters.q) {
        title = `Hasil untuk "${filters.q}"`;
        subtitle = `${posts.total} postingan ditemukan${catName ? ` dalam kategori ${catName}` : ''}.`;
    } else if (catName) {
        title = catName;
        subtitle = `Semua postingan dalam kategori ${catName}.`;
    }
    if (activePeriod) subtitle += ` Hanya yang tayang ${activePeriod.phrase}.`;

    const isFiltering = Boolean(filters.q || filters.category || filters.spotlight || filters.period);

    const emptyMessage = activePeriod
        ? `Belum ada postingan yang tayang ${activePeriod.phrase}.`
        : isFiltering
            ? 'Tidak ada postingan yang cocok dengan pilihanmu.'
            : 'Belum ada postingan yang tayang. Jadilah yang pertama membagikan sesuatu.';

    return (
        <Layout title="Mading">
            <Head title="Beranda" />

            {!isFiltering && (
                <p className="mb-1 text-sm font-semibold text-brand">Halo, {auth?.user?.name}</p>
            )}

            <div className="mb-5 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 className="text-3xl text-ink">{title}</h1>
                    <p className="mt-1 text-sm text-ink/60">{subtitle}</p>
                </div>
                <div className="flex items-center gap-2">
                    {isFiltering && (
                        <Link href="/user/beranda"
                            className="rounded-lg border border-ink/20 px-3 py-1.5 text-sm font-semibold text-ink hover:bg-ink/5">
                            Hapus filter
                        </Link>
                    )}
                </div>
            </div>

            {!isFiltering && poll && <PollCard poll={poll} />}
            {!isFiltering && contributors && <ContributorCard data={contributors} />}

                        {/* Filter kategori */}
                <CategoryBar categories={categories} filters={filters} buildHref={buildHref} />

            {posts.data.length === 0 ? (
                <div className="rounded-2xl border border-dashed border-peach bg-white/60 px-6 py-16 text-center">
                    <h2 className="text-xl text-ink">Belum ada postingan</h2>
                    <p className="mx-auto mt-2 max-w-sm text-sm text-ink/60">{emptyMessage}</p>
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