import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import UserLayout from '../../Layouts/UserLayout';
import PublicLayout from '../../Layouts/PublicLayout';
import Footer from '../../Components/Landing/Footer';
import StatusBadge from '../../Components/StatusBadge';
import LikeButton from '../../Components/LikeButton';

const formatDate = (iso) =>
    iso
        ? new Date(iso).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
        : null;

// Layout dipilih sesuai peran pembaca: admin, siswa, atau tamu
function Shell({ viewer, children }) {
    if (viewer === 'admin') return <AdminLayout title="Detail Postingan">{children}</AdminLayout>;
    if (viewer === 'user') return <UserLayout>{children}</UserLayout>;
    return (
        <PublicLayout>
            <div className="bg-linear-to-b from-peach/60 to-rose-200 px-6 py-10">{children}</div>
            <Footer />
        </PublicLayout>
    );
}

export default function Show({ post, viewer }) {
    const fallback = viewer === 'guest' ? '/' : '/user/beranda';
    const back = () => (window.history.length > 1 ? window.history.back() : router.visit(fallback));
    const date = formatDate(post.published_at);

    return (
        <Shell viewer={viewer}>
            <Head title={post.title} />

            <div className="mx-auto w-full max-w-3xl">
                <button type="button" onClick={back}
                    className="mb-4 rounded-full bg-white px-4 py-1.5 text-sm font-semibold text-ink ring-2 ring-peach transition hover:bg-peach/40">
                    ← Kembali
                </button>

                {!post.is_active && (
                    <div className="mb-4 rounded-2xl bg-grape/10 p-4 text-sm text-ink ring-1 ring-grape/20">
                        <div className="flex flex-wrap items-center gap-2">
                            <StatusBadge status={post.status} />
                            <p>
                                {viewer === 'admin'
                                    ? 'Postingan ini tidak sedang tayang. Hanya penulis dan admin yang bisa melihat halaman ini.'
                                    : 'Postingan ini belum tayang. Hanya kamu dan admin yang bisa melihat halaman ini.'}
                            </p>
                        </div>
                        {post.rejection_reason && (
                            <div className="mt-3 rounded-xl bg-brand/10 p-3 text-brand">
                                <p className="font-semibold">Alasan penolakan</p>
                                <p className="mt-0.5 text-ink/80">{post.rejection_reason}</p>
                            </div>
                        )}
                    </div>
                )}

                <article className="overflow-hidden rounded-3xl border-2 border-peach bg-white shadow-md">
                    {post.image_url && (
                        <img src={post.image_url} alt="" className="max-h-96 w-full object-cover" />
                    )}

                    <div className="p-6 sm:p-8">
                        <div className="flex flex-wrap gap-1.5">
                            {post.categories.map((name) => (
                                <span key={name}
                                    className="inline-block rounded-full bg-grape px-3 py-0.5 text-xs font-bold text-white">
                                    {name}
                                </span>
                            ))}
                        </div>

                        <h1 className="mt-3 text-3xl font-bold leading-tight text-ink sm:text-4xl">{post.title}</h1>

                        <p className="mt-2 text-sm font-medium text-ink/70">
                            Oleh {post.author ?? 'Penulis'}
                            {date && <> &middot; Tayang {date}</>}
                        </p>

                        <div className="mt-6 max-w-prose whitespace-pre-line wrap-break-word leading-relaxed text-ink/90">
                            {post.content}
                        </div>

                        {post.is_active && (
                            <div className="mt-8 flex items-center gap-3 border-t border-ink/10 pt-4">
                                <LikeButton big postId={post.id} count={post.likes_count} liked={post.liked} />
                                {viewer === 'guest' && (
                                    <span className="text-sm text-ink/60">Masuk untuk menyukai postingan ini.</span>
                                )}
                            </div>
                        )}

                        {post.edit_url && (
                            <div className="mt-8 border-t border-ink/10 pt-4">
                                <Link href={post.edit_url}
                                    className="inline-block rounded-xl border border-ink/20 px-4 py-2 text-sm font-semibold text-ink hover:bg-ink/5">
                                    Edit postingan
                                </Link>
                            </div>
                        )}
                    </div>
                </article>
            </div>
        </Shell>
    );
}