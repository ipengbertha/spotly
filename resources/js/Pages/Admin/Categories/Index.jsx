import { useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

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

export default function Index({ categories, allCategories, filters }) {
    const { errors } = usePage().props;
    const [dialog, setDialog] = useState(null); // { type: 'create' | 'edit' | 'delete', category? }
    const form = useForm({ name: '' });
    const deleteForm = useForm({ move_to: '' });

    const openCreate = () => {
        form.reset();
        form.clearErrors();
        setDialog({ type: 'create' });
    };
    const openEdit = (c) => {
        form.clearErrors();
        form.setData('name', c.name);
        setDialog({ type: 'edit', category: c });
    };
    const close = () => setDialog(null);

    const submit = (e) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: close };
        if (dialog.type === 'create') {
            form.post('/admin/categories', opts);
        } else {
            form.put(`/admin/categories/${dialog.category.id}`, opts);
        }
    };

    // Kategori kosong: hapus langsung. Kategori berisi postingan: tanya tujuan pemindahan.
        const openDelete = (c) => {
        if (c.primary_count === 0) {
            const note = c.posts_count > 0
                ? ` Kategori ini juga dipakai sebagai kategori tambahan di ${c.posts_count} postingan, dan akan dilepas dari postingan itu.`
                : '';
            if (!confirm(`Hapus kategori "${c.name}"?${note}`)) return;
            router.delete(`/admin/categories/${c.id}`, { preserveScroll: true });
            return;
        }
        deleteForm.reset();
        deleteForm.clearErrors();
        setDialog({ type: 'delete', category: c });
    };

    const submitDelete = (e) => {
        e.preventDefault();
        deleteForm.delete(`/admin/categories/${dialog.category.id}`, {
            preserveScroll: true,
            onSuccess: close,
        });
    };

    // Pilihan tujuan: semua kategori lain, kecuali "Lainnya" (sudah jadi pilihan pertama)
    const targets = dialog?.type === 'delete'
        ? allCategories.filter((x) => x.id !== dialog.category.id && !x.is_fallback)
        : [];

    return (
        <AdminLayout title="Kategori">
            <Head title="Kategori" />

            {/* Keterangan berbentuk highlight + tombol tambah */}
            <div className="mb-5 flex flex-wrap items-stretch gap-3">
                <div className="flex min-w-0 flex-1 items-start gap-3 rounded-2xl bg-grape/10 px-4 py-3 text-sm text-ink ring-1 ring-grape/20">
                    <span className="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-grape text-xs font-bold text-white">
                        i
                    </span>
                    <p>
                        Kategori yang tampil di <b className="text-grape">form postingan</b> dan{' '}
                        <b className="text-grape">filter Mading</b>. Kalau kategori dihapus, postingannya{' '}
                        <b className="text-grape">dipindahkan</b> ke kategori pilihanmu, atau otomatis ke{' '}
                        <b className="text-grape">Lainnya</b>.
                    </p>
                </div>
                <button onClick={openCreate}
                    className="rounded-2xl bg-brand px-5 py-3 text-sm font-semibold text-white shadow-sm hover:opacity-90">
                    + Tambah kategori
                </button>
            </div>

            {errors.category && !dialog && (
                <p className="mb-4 rounded-2xl bg-brand/10 px-4 py-3 text-sm font-medium text-brand">
                    {errors.category}
                </p>
            )}

            <div className="overflow-x-auto rounded-3xl bg-white shadow-md ring-1 ring-ink/5">
                <table className="w-full text-left text-sm">
                    <thead className="text-ink/50">
                        <tr>
                            <th className="px-5 py-4 font-medium">Nama</th>
                            <th className="px-5 py-4 font-medium">Slug</th>
                            <th className="px-5 py-4 font-medium">Postingan</th>
                            <th className="px-5 py-4 font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-ink/5">
                        {categories.data.length === 0 && (
                            <tr>
                                <td colSpan={4} className="p-8 text-center text-ink/60">
                                    {filters?.q ? 'Tidak ada kategori yang cocok.' : 'Belum ada kategori.'}
                                </td>
                            </tr>
                        )}
                        {categories.data.map((c) => (
                            <tr key={c.id}>
                                <td className="px-5 py-3 font-semibold text-ink">
                                    {c.name}
                                    {c.is_fallback && (
                                        <span className="ml-2 rounded-full bg-ink/10 px-2 py-0.5 text-[10px] font-bold text-ink/70">
                                            BAWAAN
                                        </span>
                                    )}
                                </td>
                                <td className="px-5 py-3 text-ink/60">{c.slug}</td>
                                <td className="px-5 py-3 text-ink/70">{c.posts_count}</td>
                                <td className="whitespace-nowrap px-5 py-3">
                                    <div className="flex flex-nowrap gap-2">
                                        <button onClick={() => openEdit(c)} disabled={c.is_fallback}
                                            title={c.is_fallback ? 'Kategori bawaan tidak bisa diubah' : ''}
                                            className="rounded-full bg-grape/10 px-3 py-1 text-xs font-semibold text-grape hover:bg-grape/20 disabled:cursor-not-allowed disabled:opacity-40">
                                            Ubah
                                        </button>
                                        <button onClick={() => openDelete(c)} disabled={c.is_fallback}
                                            title={c.is_fallback ? 'Kategori bawaan tidak bisa dihapus' : ''}
                                            className="rounded-full border border-brand/40 px-3 py-1 text-xs font-semibold text-brand hover:bg-brand/10 disabled:cursor-not-allowed disabled:opacity-40">
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
                    Menampilkan {categories.from ?? 0}-{categories.to ?? 0} dari {categories.total} kategori
                </p>
                <div className="flex flex-wrap gap-1">
                    {categories.links.map((l, i) =>
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

            {(dialog?.type === 'create' || dialog?.type === 'edit') && (
                <Modal title={dialog.type === 'create' ? 'Tambah kategori' : 'Ubah kategori'} onClose={close}>
                    <form onSubmit={submit}>
                        <label className="mb-1 block text-sm font-semibold text-ink" htmlFor="cat-name">
                            Nama kategori
                        </label>
                        <input id="cat-name" type="text" maxLength={50} autoFocus
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            className="w-full rounded-xl border border-ink/15 px-3 py-2 text-sm outline-none focus:border-grape focus:ring-2 focus:ring-grape/20" />
                        {form.errors.name && <p className="mt-1 text-sm text-brand">{form.errors.name}</p>}
                        <div className="mt-6 flex justify-end gap-2">
                            <button type="button" onClick={close}
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

            {dialog?.type === 'delete' && (
                <Modal title={`Hapus kategori "${dialog.category.name}"`} onClose={close}>
                    <form onSubmit={submitDelete}>
                        <p className="mb-4 text-sm text-ink/70">
                            Ada <b className="text-ink">{dialog.category.primary_count} postingan</b> yang memakai
                            kategori ini sebagai kategori utama. Yang punya kategori tambahan otomatis memakai
                            kategori tambahannya. Sisanya dipindahkan ke kategori mana?
                        </p>

                        {deleteForm.errors.category && (
                            <p className="mb-3 rounded-2xl bg-brand/10 px-3 py-2 text-sm font-medium text-brand">
                                {deleteForm.errors.category}
                            </p>
                        )}

                        <label className="mb-1 block text-sm font-semibold text-ink" htmlFor="move-to">
                            Pindahkan postingan ke
                        </label>
                        <select id="move-to" value={deleteForm.data.move_to}
                            onChange={(e) => deleteForm.setData('move_to', e.target.value)}
                            className="w-full rounded-xl border border-ink/15 bg-white px-3 py-2 text-sm outline-none focus:border-grape focus:ring-2 focus:ring-grape/20">
                            <option value="">Lainnya (otomatis)</option>
                            {targets.map((t) => (
                                <option key={t.id} value={t.id}>{t.name}</option>
                            ))}
                        </select>
                        {deleteForm.errors.move_to && (
                            <p className="mt-1 text-sm text-brand">{deleteForm.errors.move_to}</p>
                        )}

                        <div className="mt-6 flex justify-end gap-2">
                            <button type="button" onClick={close}
                                className="rounded-full border border-ink/20 px-4 py-2 text-sm font-semibold text-ink hover:bg-ink/5">
                                Batal
                            </button>
                            <button type="submit" disabled={deleteForm.processing}
                                className="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-60">
                                Pindahkan dan hapus
                            </button>
                        </div>
                    </form>
                </Modal>
            )}
        </AdminLayout>
    );
}