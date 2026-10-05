import { useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

const pad = (n) => String(n).padStart(2, '0');
const toInputDate = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

// Teks tombol halaman bawaan Laravel masih berbahasa Inggris
const pageLabel = (label) =>
    label.replace('&laquo; Previous', '&laquo; Sebelumnya').replace('Next &raquo;', 'Berikutnya &raquo;');

function Modal({ title, onClose, children }) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 p-4" onClick={onClose}>
            <div className="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-3xl bg-white p-6 shadow-xl"
                onClick={(e) => e.stopPropagation()}>
                <h2 className="mb-4 text-lg text-ink">{title}</h2>
                {children}
            </div>
        </div>
    );
}

export default function Index({ posts, pinnedCount, maxPinned, filters }) {
    const { errors } = usePage().props;
    const [editing, setEditing] = useState(null);
    const form = useForm({ expired_at: '' });

    const openEdit = (p) => {
        form.clearErrors();
        form.setData('expired_at', p.expired_input);
        setEditing(p);
    };
    const closeEdit = () => setEditing(null);

    const submitExpiry = (e) => {
        e.preventDefault();
        form.patch(`/admin/posts/${editing.id}/expiry`, {
            preserveScroll: true,
            onSuccess: closeEdit,
        });
    };

    const pin = (p) => router.post(`/admin/posts/${p.id}/pin`, {}, { preserveScroll: true });
    const unpin = (p) => router.delete(`/admin/posts/${p.id}/pin`, { preserveScroll: true });

    const archive = (p) => {
        if (!confirm(`Arsipkan "${p.title}"? Postingan akan hilang dari mading dan pin-nya dilepas.`)) return;
        router.post(`/admin/posts/${p.id}/archive`, {}, { preserveScroll: true });
    };

    const remove = (p) => {
        if (!confirm(`Hapus permanen "${p.title}"? Postingan dan gambarnya akan hilang dan tidak bisa dikembalikan.`)) return;
        router.delete(`/admin/posts/${p.id}`, { preserveScroll: true });
    };

    const full = pinnedCount >= maxPinned;

    return (
        <AdminLayout title="Postingan">
            <Head title="Postingan Tayang" />

            {/* Keterangan berbentuk highlight + penghitung pin */}
            <div className="mb-5 flex flex-wrap items-stretch gap-3">
                <div className="flex min-w-0 flex-1 items-start gap-3 rounded-2xl bg-grape/10 px-4 py-3 text-sm text-ink ring-1 ring-grape/20">
                    <span className="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-grape text-xs font-bold text-white">
                        i
                    </span>
                    <p>
                        Postingan yang <b className="text-grape">sedang tayang</b> di mading. Atur{' '}
                        <b className="text-grape">masa tayang</b>, <b className="text-grape">pin</b> untuk
                        Spotlight, atau <b className="text-grape">arsipkan</b>.
                    </p>
                </div>
                <span className="flex items-center rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-grape shadow-sm ring-1 ring-ink/5">
                    Pin terpakai: {pinnedCount} / {maxPinned}
                </span>
            </div>

            {errors.post && (
                <p className="mb-4 rounded-2xl bg-brand/10 px-4 py-3 text-sm font-medium text-brand">
                    {errors.post}
                </p>
            )}

            <div className="overflow-x-auto rounded-3xl bg-white shadow-md ring-1 ring-ink/5">
                <table className="w-full text-left text-sm">
                    <thead className="text-ink/50">
                        <tr>
                            <th className="px-5 py-4 font-medium">Judul</th>
                            <th className="px-5 py-4 font-medium">Penulis</th>
                            <th className="px-5 py-4 font-medium">Kategori</th>
                            <th className="whitespace-nowrap px-5 py-4 font-medium">Tayang sejak</th>
                            <th className="px-5 py-4 font-medium">Berakhir</th>
                            <th className="px-5 py-4 font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-ink/5">
                        {posts.data.length === 0 && (
                            <tr>
                                <td colSpan={6} className="p-8 text-center text-ink/60">
                                    {filters?.q
                                        ? 'Tidak ada postingan yang cocok.'
                                        : 'Belum ada postingan yang sedang tayang.'}
                                </td>
                            </tr>
                        )}
                        {posts.data.map((p) => (
                            <tr key={p.id}>
                                <td className="px-5 py-3 font-semibold text-ink">
                                    {p.title}
                                    {p.is_pinned && (
                                        <span className="ml-2 rounded-full bg-brand px-2 py-0.5 text-[10px] font-bold text-white">
                                            PIN
                                        </span>
                                    )}
                                </td>
                                <td className="px-5 py-3 text-ink/70">{p.author}</td>
                                <td className="px-5 py-3 text-ink/70">{p.category}</td>
                                <td className="whitespace-nowrap px-5 py-3 text-ink/70">{p.published_label}</td>
                                <td className="whitespace-nowrap px-5 py-3 text-ink/70">{p.expired_label}</td>
                                <td className="whitespace-nowrap px-5 py-3">
                                    <div className="flex flex-nowrap gap-2">
                                        <button onClick={() => openEdit(p)}
                                            className="rounded-full bg-grape/10 px-3 py-1 text-xs font-semibold text-grape hover:bg-grape/20">
                                            Masa tayang
                                        </button>
                                        {p.is_pinned ? (
                                            <button onClick={() => unpin(p)}
                                                className="rounded-full border border-ink/20 px-3 py-1 text-xs font-semibold text-ink hover:bg-ink/5">
                                                Lepas pin
                                            </button>
                                        ) : (
                                            <button onClick={() => pin(p)} disabled={full}
                                                title={full ? 'Slot pin penuh' : ''}
                                                className="rounded-full bg-brand px-3 py-1 text-xs font-semibold text-white hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40">
                                                Pin
                                            </button>
                                        )}
                                        <button onClick={() => archive(p)}
                                            className="rounded-full border border-ink/20 px-3 py-1 text-xs font-semibold text-ink hover:bg-ink/5">
                                            Arsipkan
                                        </button>
                                        <button onClick={() => remove(p)}
                                            className="rounded-full border border-brand/40 px-3 py-1 text-xs font-semibold text-brand hover:bg-brand/10">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Pagination: selalu tampil */}
            <div className="mt-5 flex flex-wrap items-center justify-between gap-3">
                <p className="text-sm text-ink/60">
                    Menampilkan {posts.from ?? 0}-{posts.to ?? 0} dari {posts.total} postingan
                </p>
                <div className="flex flex-wrap gap-1">
                    {posts.links.map((l, i) =>
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

            {editing && (
                <Modal title="Ubah masa tayang" onClose={closeEdit}>
                    <form onSubmit={submitExpiry}>
                        <p className="mb-4 text-sm text-ink/60">
                            Atur sampai kapan &quot;{editing.title}&quot; tampil di mading.
                        </p>
                        <label className="mb-1 block text-sm font-semibold text-ink" htmlFor="expired_at">
                            Tayang sampai
                        </label>
                        <input id="expired_at" type="date" min={toInputDate(new Date())}
                            value={form.data.expired_at}
                            onChange={(e) => form.setData('expired_at', e.target.value)}
                            className="w-full rounded-xl border border-ink/15 px-3 py-2 text-sm outline-none focus:border-grape focus:ring-2 focus:ring-grape/20" />
                        {form.errors.expired_at && (
                            <p className="mt-1 text-sm text-brand">{form.errors.expired_at}</p>
                        )}
                        <div className="mt-6 flex justify-end gap-2">
                            <button type="button" onClick={closeEdit}
                                className="rounded-full border border-ink/20 px-4 py-2 text-sm font-semibold text-ink hover:bg-ink/5">
                                Batal
                            </button>
                            <button type="submit" disabled={form.processing}
                                className="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-60">
                                Simpan
                            </button>
                        </div>
                    </form>
                </Modal>
            )}
        </AdminLayout>
    );
}