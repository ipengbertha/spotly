import { Head, Link, router } from '@inertiajs/react';
import ViewerLayout from '../../Layouts/ViewerLayout';
import Avatar from '../../Components/Avatar';
import StatusBadge from '../../Components/StatusBadge';
import LikeButton from '../../Components/LikeButton';
import BookmarkButton from '../../Components/BookmarkButton';
import CommentIcon from '../../Components/CommentIcon';
import CommentSection from '../../Components/CommentSection';
import PostMenu from '../../Components/PostMenu';
import ReactionBar from '../../Components/ReactionBar';
import ReaderToolbar, { SIZE_CLASSES, useReaderPrefs } from '../../Components/ReaderToolbar';

const formatDate = (iso) =>
    iso
        ? new Date(iso).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
        : null;

export default function Show({ post, viewer }) {
    const fallback = viewer === 'guest' ? '/' : '/user/beranda';
    const back = () => (window.history.length > 1 ? window.history.back() : router.visit(fallback));
    const date = formatDate(post.published_at);
    // Ukuran teks dan mode gelap: hanya untuk area artikel, diingat di browser
    const [prefs, setPrefs] = useReaderPrefs();
    const dark = prefs.dark;

    return (
        <ViewerLayout viewer={viewer} title="Detail Postingan">
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
                                {post.status === 'suspended'
                                    ? (viewer === 'admin'
                                        ? 'Postingan ini ditangguhkan dan tidak tampil di mading. Hanya penulis dan admin yang bisa melihat halaman ini.'
                                        : 'Postingan ini ditangguhkan dan tidak tampil di mading. Kamu tidak bisa mengedit atau mengirimnya ulang.')
                                    : viewer === 'admin'
                                        ? 'Postingan ini tidak sedang tayang. Hanya penulis dan admin yang bisa melihat halaman ini.'
                                        : 'Postingan ini belum tayang. Hanya kamu dan admin yang bisa melihat halaman ini.'}
                            </p>
                        </div>
                        {post.suspension_reason && (
                            <div className="mt-3 rounded-xl bg-red-100 p-3 text-red-700">
                                <p className="font-semibold">Alasan penangguhan</p>
                                <p className="mt-0.5 text-ink/80">{post.suspension_reason}</p>
                            </div>
                        )}
                        {post.rejection_reason && (
                            <div className="mt-3 rounded-xl bg-brand/10 p-3 text-brand">
                                <p className="font-semibold">Alasan penolakan</p>
                                <p className="mt-0.5 text-ink/80">{post.rejection_reason}</p>
                            </div>
                        )}
                    </div>
                )}

                <article className={`relative overflow-hidden rounded-3xl border-2 shadow-md transition-colors ${
                    dark ? 'border-grape/50 bg-ink' : 'border-peach bg-white'}`}>
                    {/* Menu titik tiga di pojok kanan atas (tanpa z-index supaya dialognya tetap di atas header) */}
                    {post.is_active && (
                        <div className="absolute right-4 top-4">
                            <PostMenu post={post} />
                        </div>
                    )}

                    {post.image_url && (
                        <img src={post.image_url} alt="" className="max-h-96 w-full object-cover" />
                    )}

                    <div className="p-6 sm:p-8">
                        <div className="flex flex-wrap gap-1.5 pr-12">
                            {post.categories.map((name) => (
                                <span key={name}
                                    className="inline-block rounded-full bg-grape px-3 py-0.5 text-xs font-bold text-white">
                                    {name}
                                </span>
                            ))}
                        </div>

                        <h1 className={`mt-3 text-3xl font-bold leading-tight sm:text-4xl ${dark ? 'text-white' : 'text-ink'}`}>{post.title}</h1>

                        <div className={`mt-3 flex items-center gap-2 text-sm font-medium ${dark ? 'text-white/70' : 'text-ink/70'}`}>
                            <Avatar src={post.author_avatar} name={post.author} className="h-7 w-7 text-xs" />
                            <p>
                                Oleh{' '}
                                {post.author_id
                                    ? <Link href={`/profil/${post.author_id}`} className={`font-semibold hover:text-brand hover:underline ${dark ? 'text-white' : 'text-ink'}`}>{post.author ?? 'Penulis'}</Link>
                                    : (post.author ?? 'Penulis')}
                                {date && <> &middot; Tayang {date}</>}
                                {post.reading_minutes && <> &middot; {post.reading_minutes} menit baca</>}
                            </p>
                        </div>

                        <ReaderToolbar prefs={prefs} onChange={setPrefs} />

                        <div className={`mt-6 max-w-prose whitespace-pre-line wrap-break-word leading-relaxed ${
                            SIZE_CLASSES[prefs.size]} ${dark ? 'text-white/90' : 'text-ink/90'}`}>
                            {post.content}
                        </div>

                        {post.is_active && (
                            <div className={`mt-8 flex flex-wrap items-center gap-3 border-t pt-4 ${dark ? 'border-white/15' : 'border-ink/10'}`}>
                                <LikeButton big onDark={dark} postId={post.id} count={post.likes_count} liked={post.liked} />
                                <a href="#komentar" aria-label={`${post.comments_count} komentar`}
                                    className={`inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-base font-semibold transition hover:bg-grape/10 hover:text-grape ${
                                        dark ? 'text-white/70' : 'text-ink/60'}`}>
                                    <CommentIcon className="h-6 w-6" />
                                    {post.comments_count}
                                </a>
                                <span aria-hidden="true" className={`h-5 w-px ${dark ? 'bg-white/20' : 'bg-ink/15'}`} />
                                <ReactionBar onDark={dark} postId={post.id}
                                    reactions={post.reactions} mine={post.my_reaction} />
                                {post.can_bookmark && (
                                    <div className="ml-auto">
                                        <BookmarkButton big onDark={dark} postId={post.id} saved={post.bookmarked} />
                                    </div>
                                )}
                                {viewer === 'guest' && (
                                    <span className={`text-sm ${dark ? 'text-white/70' : 'text-ink/60'}`}>Masuk untuk menyukai postingan ini.</span>
                                )}
                            </div>
                        )}

                        {post.edit_url && (
                            <div className={`mt-8 border-t pt-4 ${dark ? 'border-white/15' : 'border-ink/10'}`}>
                                <Link href={post.edit_url}
                                    className={`inline-block rounded-xl border px-4 py-2 text-sm font-semibold ${
                                        dark ? 'border-white/30 text-white hover:bg-white/10' : 'border-ink/20 text-ink hover:bg-ink/5'}`}>
                                    Edit postingan
                                </Link>
                            </div>
                        )}
                    </div>
                </article>

                {post.is_active && <CommentSection post={post} viewer={viewer} />}
            </div>
        </ViewerLayout>
    );
}