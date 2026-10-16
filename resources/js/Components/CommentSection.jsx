import { useId, useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import Avatar from './Avatar';
import CommentIcon from './CommentIcon';
import ConfirmDialog from './ConfirmDialog';

const MAX = 500;
// Jorokan balasan dibatasi 3 tingkat. Balasan yang lebih dalam tetap sejajar dengan tingkat 3
// dan diberi penanda "membalas Nama" supaya jelas dia menjawab siapa.
const MAX_INDENT = 1;

const formatTime = (iso) =>
    new Date(iso).toLocaleString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });

// Kotak menulis komentar atau balasan. Hanya dipakai siswa (canComment = true).
// parentId + replyTo diisi kalau ini balasan; onDone dan onCancel dipakai kotak balasan.
function CommentForm({ postId, parentId = null, replyTo = null, onDone, onCancel }) {
    const form = useForm({ content: '', parent_id: parentId });
    const fieldId = useId();
    const isReply = parentId !== null;

    const submit = (e) => {
        e.preventDefault();
        form.post(`/posts/${postId}/comments`, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('content');
                onDone?.();
            },
        });
    };

    const length = form.data.content.length;
    const error = form.errors.content || form.errors.parent_id;

    return (
        <form onSubmit={submit} className="space-y-2">
            <label htmlFor={fieldId} className="sr-only">
                {isReply ? `Balas ${replyTo ?? 'komentar'}` : 'Tulis komentar'}
            </label>
            <textarea id={fieldId} rows={isReply ? 2 : 3} maxLength={MAX} value={form.data.content}
                autoFocus={isReply}
                onChange={(e) => form.setData('content', e.target.value)}
                placeholder={isReply ? `Balas ${replyTo ?? 'komentar'}...` : 'Tulis komentar...'}
                className="w-full resize-none rounded-2xl border border-ink/15 px-4 py-3 text-sm outline-none focus:border-grape focus:ring-2 focus:ring-grape/20" />
            {error && <p className="text-sm text-brand">{error}</p>}
            <div className="flex items-center justify-between gap-3">
                <p className="text-xs text-ink/50">{length}/{MAX}</p>
                <div className="flex items-center gap-2">
                    {isReply && (
                        <button type="button" onClick={onCancel}
                            className="rounded-full border border-ink/20 px-4 py-2 text-sm font-semibold text-ink hover:bg-ink/5">
                            Batal
                        </button>
                    )}
                    <button type="submit" disabled={form.processing || length < 2}
                        className="rounded-full bg-brand px-5 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50">
                        {isReply ? 'Balas' : 'Kirim'}
                    </button>
                </div>
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

// Satu komentar beserta balasannya (rekursif). depth 0 = komentar utama.
function CommentNode({ c, depth, post, onDelete }) {
    const [replying, setReplying] = useState(false);
    const [open, setOpen] = useState(true);
    const hasReplies = c.replies.length > 0;
    const small = depth > 0;

    return (
        <li className={depth === 0 ? 'py-4 first:pt-0 last:pb-0' : ''}>
            <div className="flex gap-3">
                <Avatar src={c.author_avatar} name={c.author} className={small ? 'h-7 w-7 text-xs' : 'h-9 w-9 text-sm'} />
                <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-baseline gap-x-2">
                        {c.author_id
                            ? <Link href={`/profil/${c.author_id}`}
                                className="text-sm font-semibold text-ink hover:text-brand hover:underline">
                                {c.author ?? 'Siswa'}
                            </Link>
                            : <span className="text-sm font-semibold text-ink">{c.author ?? 'Siswa'}</span>}
                        {c.author_username && (
                            <span className="text-xs text-ink/45">@{c.author_username}</span>
                        )}
                        <span className="text-xs text-ink/50">{formatTime(c.created_at)}</span>
                    </div>
                    {depth >= 2 && (c.reply_to_username || c.reply_to) && (
                        <p className="mt-0.5 text-xs font-semibold text-grape">
                            &#8618; Membalas {c.reply_to_username ? `@${c.reply_to_username}` : c.reply_to}
                        </p>
                    )}
                    <p className="mt-1 whitespace-pre-line wrap-break-word text-sm leading-relaxed text-ink/90">
                        {c.content}
                    </p>

                    <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs font-semibold text-ink/60">
                        {post.can_comment && (
                            <button type="button" onClick={() => setReplying((v) => !v)}
                                aria-expanded={replying}
                                className="hover:text-grape hover:underline">
                                Balas
                            </button>
                        )}
                        {hasReplies && (
                            <button type="button" onClick={() => setOpen((v) => !v)}
                                aria-expanded={open}
                                className="hover:text-grape hover:underline">
                                {open ? 'Sembunyikan balasan' : `Lihat ${c.replies_total} balasan`}
                            </button>
                        )}
                    </div>

                    {replying && (
                        <div className="mt-3">
                            <CommentForm postId={post.id} parentId={c.id} replyTo={c.author}
                                onDone={() => { setReplying(false); setOpen(true); }}
                                onCancel={() => setReplying(false)} />
                        </div>
                    )}
                </div>
                {c.delete_url && (
                    <button type="button" onClick={() => onDelete(c)}
                        className="h-fit shrink-0 rounded-full border border-brand/40 px-3 py-1 text-xs font-semibold text-brand hover:bg-brand/10">
                        Hapus
                    </button>
                )}
            </div>

            {hasReplies && open && (
                <ul className={`mt-4 space-y-4 ${depth < MAX_INDENT ? 'ml-4 border-l-2 border-ink/10 pl-4 sm:ml-12' : ''}`}>
                    {c.replies.map((r) => (
                        <CommentNode key={r.id} c={r} depth={depth + 1} post={post} onDelete={onDelete} />
                    ))}
                </ul>
            )}
        </li>
    );
}

// Bagian komentar di halaman detail postingan: form (siswa) + daftar komentar bertingkat.
// Komentar utama terbaru di atas; balasan terlama di atas.
// viewer: 'guest' | 'user' | 'admin'
function deleteMessage(viewer, target) {
    const base = viewer === 'admin' ? 'Komentar ini' : 'Komentarmu';
    const extra = target?.replies_total > 0
        ? ` beserta ${target.replies_total} balasan di bawahnya`
        : '';
    return `${base}${extra} akan dihapus permanen dan tidak bisa dikembalikan.`;
}

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
                        <CommentNode key={c.id} c={c} depth={0} post={post} onDelete={setTarget} />
                    ))}
                </ul>
            )}

            <ConfirmDialog open={!!target} title="Hapus komentar?"
                message={deleteMessage(viewer, target)}
                confirmLabel="Ya, hapus"
                onConfirm={confirmDelete} onCancel={() => setTarget(null)} />
        </section>
    );
}