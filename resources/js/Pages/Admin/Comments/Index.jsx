import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import StatusBadge from '../../../Components/StatusBadge';
import ConfirmDialog from '../../../Components/ConfirmDialog';

const pageLabel = (label) =>
    label.replace('&laquo; Previous', '&laquo; Sebelumnya').replace('Next &raquo;', 'Berikutnya &raquo;');

export default function Index({ comments, filters }) {
    const [target, setTarget] = useState(null);

    const confirmDelete = () => {
        const id = target.id;
        setTarget(null);
        router.delete(`/admin/comments/${id}`, { preserveScroll: true });
    };

    return (
        <AdminLayout title="Komentar">
            <Head title="Komentar" />

            {/* Keterangan berbentuk highlight */}
            <div className="mb-5 flex items-start gap-3 rounded-2xl bg-grape/10 px-4 py-3 text-sm text-ink ring-1 ring-grape/20">
                <span className="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-grape text-xs font-bold text-white">
                    i
                </span>
                <p>
                    Komentar siswa tampil <b className="text-grape">langsung tanpa persetujuan</b>, jadi di sinilah
                    tempat memeriksanya. Kamu bisa <b className="text-grape">menghapus komentar apa pun</b>, termasuk
                    di postingan yang sudah tidak tayang.
                </p>
            </div>

            <div className="overflow-x-auto rounded-3xl bg-white shadow-md ring-1 ring-ink/5">
                <table className="w-full text-left text-sm">
                    <thead className="text-ink/50">
                        <tr>
                            <th className="px-5 py-4 font-medium">Komentar</th>
                            <th className="px-5 py-4 font-medium">Penulis</th>
                            <th className="px-5 py-4 font-medium">Postingan</th>
                            <th className="px-5 py-4 font-medium">Waktu</th>
                            <th className="px-5 py-4 font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-ink/5">
                        {comments.data.length === 0 && (
                            <tr>
                                <td colSpan={5} className="p-8 text-center text-ink/60">
                                    {filters.q ? 'Tidak ada komentar yang cocok.' : 'Belum ada komentar.'}
                                </td>
                            </tr>
                        )}
                        {comments.data.map((c) => (
                            <tr key={c.id}>
                                <td className="max-w-xs px-5 py-3 text-ink">
                                    {c.reply_to && (
                                        <p className="mb-0.5 text-xs font-semibold text-grape">&#8618; membalas {c.reply_to}</p>
                                    )}
                                    <p className="line-clamp-3 whitespace-pre-line wrap-break-word">{c.content}</p>
                                </td>
                                <td className="whitespace-nowrap px-5 py-3 text-ink/70">
                                    {c.author_id
                                        ? <Link href={`/profil/${c.author_id}`} className="font-semibold text-ink hover:text-brand hover:underline">{c.author}</Link>
                                        : (c.author ?? '-')}
                                </td>
                                <td className="max-w-56 px-5 py-3">
                                    {c.post_slug
                                        ? <Link href={`/posts/${c.post_slug}`} className="font-semibold text-ink hover:text-brand hover:underline">{c.post_title}</Link>
                                        : <span className="text-ink/60">{c.post_title ?? '-'}</span>}
                                    {c.post_status && c.post_status !== 'published' && (
                                        <span className="ml-2 align-middle"><StatusBadge status={c.post_status} /></span>
                                    )}
                                </td>
                                <td className="whitespace-nowrap px-5 py-3 text-ink/70" title={c.date}>{c.time}</td>
                                <td className="whitespace-nowrap px-5 py-3">
                                    <button onClick={() => setTarget(c)}
                                        className="rounded-full border border-brand/40 px-3 py-1 text-xs font-semibold text-brand hover:bg-brand/10">
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Pagination: selalu tampil */}
            <div className="mt-5 flex flex-wrap items-center justify-between gap-3">
                <p className="text-sm text-ink/60">
                    Menampilkan {comments.from ?? 0}-{comments.to ?? 0} dari {comments.total} komentar
                </p>
                <div className="flex flex-wrap gap-1">
                    {comments.links.map((l, i) =>
                        l.url ? (
                            <Link key={i} href={l.url}
                                className={`rounded-full px-3.5 py-1.5 text-sm font-semibold ${
                                    l.active ? 'bg-ink text-white' : 'bg-white text-ink/70 ring-1 ring-ink/10 hover:bg-peach/40'}`}
                                dangerouslySetInnerHTML={{ __html: pageLabel(l.label) }} />
                        ) : (
                            <span key={i}
                                className="rounded-full bg-white/60 px-3.5 py-1.5 text-sm font-semibold text-ink/30 ring-1 ring-ink/5"
                                dangerouslySetInnerHTML={{ __html: pageLabel(l.label) }} />
                        )
                    )}
                </div>
            </div>

            <ConfirmDialog open={!!target} title="Hapus komentar?"
                message={target
                    ? `Komentar dari ${target.author ?? 'siswa'}${target.replies_count > 0 ? ` beserta semua balasan di bawahnya (${target.replies_count} balasan langsung)` : ''} akan dihapus permanen dan tidak bisa dikembalikan.`
                    : ''}
                confirmLabel="Ya, hapus"
                onConfirm={confirmDelete} onCancel={() => setTarget(null)} />
        </AdminLayout>
    );
}