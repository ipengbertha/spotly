import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import Avatar from './Avatar';
import CommentIcon from './CommentIcon';
import ConfirmDialog from './ConfirmDialog';

const MAX = 500;

const formatTime = (iso) =>
    new Date(iso).toLocaleString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });

// Kotak menulis komentar. Hanya dipakai siswa (canComment = true).
function CommentForm({ postId }) {
    const form = useForm({ content: '' });

    const submit = (e) => {
        e.preventDefault();
        form.post(`/posts/${postId}/comments`, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    const length = form.data.content.length;

    return (
        <form onSubmit={submit} className="space-y-2">
            <label htmlFor="comment-content" className="sr-only">Tulis komentar</label>
            <textarea id="comment-content" rows={3} maxLength={MAX} value={form.data.content}
                onChange={(e) => form.setData('content', e.target.value)}
                placeholder="Tulis komentar..."
                className="w-full resize-none rounded-2xl border border-ink/15 px-4 py-3 text-sm outline-none focus:border-grape focus:ring-2 focus:ring-grape/20" />
            {form.errors.content && <p className="text-sm text-brand">{form.errors.content}</p>}
            <div className="flex items-center justify-between gap-3">
                <p className="text-xs text-ink/50">{length}/{MAX}</p>
                <button type="submit" disabled={form.processing || length < 2}
                    className="rounded-full bg-brand px-5 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50">
                    Kirim
                </button>
            </div>
        </form>
    );
}

// Saklar untuk penulis postingan: mematikan / menyalakan komentar baru.
function CommentsSwitch({ post }) {
    const [busy, setBusy] = useState(false);
    const on = post.comments_enabled;

    const toggle = () => {
        if (busy) return;
        router.patch(`/posts/${post.id}/comments-setting`, { enabled: !on }, {
            preserveScroll: true,
            onStart: () => setBusy(true),
            onFinish: () => setBusy(false),
        });
    };

    return (
        <div className="mt-4 flex items-center justify-between gap-3 rounded-2xl bg-cream/60 px-4 py-3 ring-1 ring-ink/5">
            <div>
                <p className="text-sm font-semibold text-ink">Komentar di postingan ini</p>
                <p className="text-xs text-ink/60">
                    {on
                        ? 'Aktif. Siswa bisa menulis komentar.'
                        : 'Dimatikan. Komentar lama tetap tampil, tapi tidak ada yang bisa menambah komentar baru.'}
                </p>
            </div>
            <button type="button" role="switch" aria-checked={on} onClick={toggle} disabled={busy}
                aria-label={on ? 'Matikan komentar' : 'Nyalakan komentar'}
                className={`relative h-7 w-12 shrink-0 rounded-full transition disabled:opacity-60 ${on ? 'bg-brand' : 'bg-ink/25'}`}>
                <span className={`absolute top-0.5 h-6 w-6 rounded-full bg-white shadow transition-all ${on ? 'left-5.5' : 'left-0.5'}`} />
            </button>
        </div>
    );
}

// Bagian komentar di halaman detail postingan: form (siswa) + daftar komentar, terbaru di atas.
// viewer: 'guest' | 'user' | 'admin'
export default function CommentSection({ post, viewer }) {
    const [target, setTarget] = useState(null);

    const confirmDelete = () => {
        const url = target.delete_url;
        setTarget(null);
        router.delete(url, { preserveScroll: true });
    };

    return (
        <section id="komentar" className="mt-6 rounded-3xl border-2 border-peach bg-white p-6 shadow-md sm:p-8">
            <h2 className="flex items-center gap-2 text-xl font-bold text-ink">
                <CommentIcon className="h-6 w-6 text-grape" />
                Komentar ({post.comments_count})
            </h2>

            {post.can_toggle_comments && <CommentsSwitch post={post} />}

            <div className="mt-4">
                {post.can_comment && <CommentForm postId={post.id} />}

                {!post.comments_enabled && viewer !== 'admin' && (
                    <p className="rounded-2xl bg-grape/10 px-4 py-3 text-sm text-ink ring-1 ring-grape/20">
                        Penulis mematikan komentar di postingan ini.
                    </p>
                )}

                {viewer === 'guest' && post.comments_enabled && (
                    <p className="rounded-2xl bg-grape/10 px-4 py-3 text-sm text-ink ring-1 ring-grape/20">
                        <Link href="/login" className="font-semibold text-grape hover:underline">Masuk</Link>
                        {' '}untuk menulis komentar.
                    </p>
                )}

                {viewer === 'admin' && (
                    <p className="rounded-2xl bg-grape/10 px-4 py-3 text-sm text-ink ring-1 ring-grape/20">
                        Admin bisa membaca dan menghapus komentar, tapi tidak menulis komentar.
                        {!post.comments_enabled && ' Penulis sedang mematikan komentar di postingan ini.'}
                    </p>
                )}
            </div>

            {post.comments.length === 0 ? (
                <p className="mt-6 text-center text-sm text-ink/60">
                    {post.comments_enabled ? 'Belum ada komentar. Jadilah yang pertama!' : 'Belum ada komentar.'}
                </p>
            ) : (
                <ul className="mt-6 divide-y divide-ink/5">
                    {post.comments.map((c) => (
                        <li key={c.id} className="flex gap-3 py-4 first:pt-0 last:pb-0">
                            <Avatar src={c.author_avatar} name={c.author} className="h-9 w-9 text-sm" />
                            <div className="min-w-0 flex-1">
                                <div className="flex flex-wrap items-baseline gap-x-2">
                                    {c.author_id
                                        ? <Link href={`/profil/${c.author_id}`}
                                            className="text-sm font-semibold text-ink hover:text-brand hover:underline">
                                            {c.author ?? 'Siswa'}
                                        </Link>
                                        : <span className="text-sm font-semibold text-ink">{c.author ?? 'Siswa'}</span>}
                                    <span className="text-xs text-ink/50">{formatTime(c.created_at)}</span>
                                </div>
                                <p className="mt-1 whitespace-pre-line wrap-break-word text-sm leading-relaxed text-ink/90">
                                    {c.content}
                                </p>
                            </div>
                            {c.delete_url && (
                                <button type="button" onClick={() => setTarget(c)}
                                    className="h-fit shrink-0 rounded-full border border-brand/40 px-3 py-1 text-xs font-semibold text-brand hover:bg-brand/10">
                                    Hapus
                                </button>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            <ConfirmDialog open={!!target} title="Hapus komentar?"
                message={viewer === 'admin'
                    ? 'Komentar ini akan dihapus permanen dan tidak bisa dikembalikan.'
                    : 'Komentarmu akan dihapus permanen dan tidak bisa dikembalikan.'}
                confirmLabel="Ya, hapus"
                onConfirm={confirmDelete} onCancel={() => setTarget(null)} />
        </section>
    );
}