import { useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

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

export default function Index({ categories }) {
    const { errors } = usePage().props;
    const [dialog, setDialog] = useState(null); // { type: 'create' | 'edit', category? }
    const form = useForm({ name: '' });

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

    const remove = (c) => {
        if (!confirm(`Hapus kategori "${c.name}"?`)) return;
        router.delete(`/admin/categories/${c.id}`, { preserveScroll: true });
    };

    return (
        <AdminLayout title="Kategori">
            <Head title="Kategori" />

            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <p className="text-sm text-ink/60">
                    Kategori yang tampil di form postingan dan filter Mading.
                </p>
                <button onClick={openCreate}
                    className="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90">
                    + Tambah kategori
                </button>
            </div>

            {errors.category && (
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
                        {categories.length === 0 && (
                            <tr>
                                <td colSpan={4} className="p-8 text-center text-ink/60">Belum ada kategori.</td>
                            </tr>
                        )}
                        {categories.map((c) => (
                            <tr key={c.id}>
                                <td className="px-5 py-3 font-semibold text-ink">{c.name}</td>
                                <td className="px-5 py-3 text-ink/60">{c.slug}</td>
                                <td className="px-5 py-3 text-ink/70">{c.posts_count}</td>
                                <td className="px-5 py-3">
                                    <div className="flex flex-wrap gap-2">
                                        <button onClick={() => openEdit(c)}
                                            className="rounded-full bg-grape/10 px-3 py-1 text-xs font-semibold text-grape hover:bg-grape/20">
                                            Ubah
                                        </button>
                                        <button onClick={() => remove(c)} disabled={c.posts_count > 0}
                                            title={c.posts_count > 0 ? 'Masih dipakai postingan' : ''}
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

            {dialog && (
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
        </AdminLayout>
    );
}