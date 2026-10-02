import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import UserLayout from '../../../Layouts/UserLayout';
import StatusBadge from '../../../Components/StatusBadge';
import ConfirmDialog, { SUBMIT_WARNING } from '../../../Components/ConfirmDialog';

const TABS = [
    ['all', 'Semua'],
    ['submitted', 'Menunggu review'],
    ['published', 'Tayang'],
    ['rejected', 'Ditolak'],
    ['draft', 'Draft'],
    ['archived', 'Arsip'],
];

function ImagePlaceholder() {
    return (
        <div className="flex h-40 w-full items-center justify-center bg-linear-to-br from-peach/50 via-cream to-grape/20">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5"
                className="h-10 w-10 text-ink/30" aria-hidden="true">
                <rect x="3" y="4" width="18" height="16" rx="2" />
                <circle cx="9" cy="10" r="1.5" />
                <path d="M21 16l-5-5-8 8" />
            </svg>
        </div>
    );
}

export default function Index({ posts }) {
    const [tab, setTab] = useState('all');
    const [toSubmit, setToSubmit] = useState(null);
    const [toDelete, setToDelete] = useState(null);

    const count = (key) => (key === 'all' ? posts.length : posts.filter((p) => p.display_status === key).length);
    const visible = tab === 'all' ? posts : posts.filter((p) => p.display_status === tab);

    return (
        <UserLayout>
            <Head title="Kiriman Saya" />

            <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 className="text-3xl text-ink">Kiriman Saya</h1>
                    <p className="mt-1 text-sm text-ink/60">Pantau status semua postingan yang pernah kamu buat.</p>
                </div>
            </div>

            <div className="mb-6 flex flex-wrap gap-2">
                {TABS.map(([key, label]) => (
                    <button key={key} onClick={() => setTab(key)}
                        className={`rounded-full px-4 py-1.5 text-sm font-semibold transition ${
                            tab === key
                                ? 'bg-grape text-white shadow-sm'
                                : 'bg-white text-ink/70 ring-1 ring-ink/15 hover:bg-peach/30'}`}>
                        {label} <span className="opacity-70">({count(key)})</span>
                    </button>
                ))}
            </div>

            {posts.length === 0 && (
                <div className="rounded-2xl border border-dashed border-peach bg-white/60 px-6 py-16 text-center">
                    <h2 className="text-xl text-ink">Belum ada postingan</h2>
                    <p className="mx-auto mt-2 max-w-sm text-sm text-ink/60">
                        Tulis pengumuman, karya, atau kabar kegiatanmu. Setelah dikirim, admin akan meninjaunya.
                    </p>
                    <Link href="/user/posts/create"
                        className="mt-5 inline-block rounded-xl bg-brand px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90">
                        Buat postingan pertama
                    </Link>
                </div>
            )}

            {posts.length > 0 && visible.length === 0 && (
                <p className="py-12 text-center text-sm text-ink/60">Tidak ada postingan dengan status ini.</p>
            )}

            <div className="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                {visible.map((p) => (
                    <article key={p.id}
                        className="flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-ink/10 transition hover:shadow-md">
                        {p.image_url
                            ? <img src={p.image_url} alt="" className="h-40 w-full object-cover" />
                            : <ImagePlaceholder />}

                        <div className="flex flex-1 flex-col gap-3 p-5">
                            <div className="flex items-center justify-between gap-2">
                                <span className="text-xs font-medium text-ink/60">{(p.categories ?? [p.category]).join(' · ')}</span>
                                <StatusBadge status={p.display_status} />
                            </div>

                            <h2 className="line-clamp-2 text-lg leading-snug text-ink">{p.title}</h2>

                            {p.status === 'rejected' && p.rejection_reason && (
                                <div className="rounded-lg bg-brand/10 p-3 text-sm text-brand">
                                    <p className="font-semibold">Alasan penolakan</p>
                                    <p className="mt-0.5 text-ink/80">{p.rejection_reason}</p>
                                </div>
                            )}

                            <div className="mt-auto flex flex-wrap gap-2 pt-2 text-sm">
                                {p.can.edit && (
                                    <Link href={`/user/posts/${p.id}/edit`}
                                        className="rounded-lg border border-ink/20 px-3 py-1.5 font-semibold text-ink hover:bg-ink/5">
                                        Edit
                                    </Link>
                                )}
                                {p.can.submit && (
                                    <button onClick={() => setToSubmit(p)}
                                        className="rounded-lg bg-ink px-3 py-1.5 font-semibold text-white hover:opacity-90">
                                        {p.status === 'rejected' ? 'Kirim ulang' : 'Kirim'}
                                    </button>
                                )}
                                {p.can.delete && (
                                    <button onClick={() => setToDelete(p)}
                                        className="ml-auto rounded-lg px-3 py-1.5 font-semibold text-brand hover:bg-brand/10">
                                        Hapus
                                    </button>
                                )}
                            </div>
                        </div>
                    </article>
                ))}
            </div>

            {/* Dialog harus berada DI DALAM UserLayout */}
            <ConfirmDialog
                open={toSubmit !== null}
                title={toSubmit?.status === 'rejected' ? 'Kirim ulang?' : 'Kirim untuk review?'}
                message={SUBMIT_WARNING}
                confirmLabel="Ya, kirim"
                onCancel={() => setToSubmit(null)}
                onConfirm={() => {
                    router.post(`/user/posts/${toSubmit.id}/submit`);
                    setToSubmit(null);
                }}
            />

            <ConfirmDialog
                open={toDelete !== null}
                title="Hapus postingan?"
                message={`"${toDelete?.title ?? ''}" akan dihapus permanen, termasuk like dan komentarnya.`}
                confirmLabel="Ya, hapus"
                onCancel={() => setToDelete(null)}
                onConfirm={() => {
                    router.delete(`/user/posts/${toDelete.id}`);
                    setToDelete(null);
                }}
            />
        </UserLayout>
    );
}