import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import UserLayout from '../../Layouts/UserLayout';
import StatusBadge from '../../Components/StatusBadge';
import ConfirmDialog, { SUBMIT_WARNING } from '../../Components/ConfirmDialog';

export default function Dashboard({ posts }) {
    const [toSubmit, setToSubmit] = useState(null); // postingan yang akan dikirim

    const remove = (p) => {
        if (confirm(`Hapus "${p.title}"? Like dan komentarnya juga akan terhapus.`)) {
            router.delete(`/user/posts/${p.id}`);
        }
    };

    return (
        <UserLayout>
            <Head title="Postingan Saya" />
            <div className="mb-4 flex items-center justify-between">
                <h1 className="text-2xl font-bold">Postingan Saya</h1>
                <Link href="/user/posts/create"
                    className="rounded-lg bg-slate-900 px-4 py-2 font-semibold text-white">+ Buat postingan</Link>
            </div>

            {posts.length === 0 && <p className="text-slate-600">Belum ada postingan.</p>}

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {posts.map((p) => (
                    <div key={p.id} className="overflow-hidden rounded-2xl bg-white shadow ring-1 ring-slate-200">
                        {p.image_url
                            ? <img src={p.image_url} alt="" className="h-40 w-full object-cover" />
                            : <div className="flex h-40 items-center justify-center bg-slate-100 text-slate-400">Tanpa gambar</div>}
                        <div className="space-y-2 p-4">
                            <div className="flex items-center justify-between">
                                <span className="text-xs text-slate-500">{p.category}</span>
                                <StatusBadge status={p.status} />
                            </div>
                            <h2 className="font-bold">{p.title}</h2>
                            {p.status === 'rejected' && p.rejection_reason && (
                                <p className="rounded bg-red-50 p-2 text-sm text-red-700">
                                    Ditolak: {p.rejection_reason}
                                </p>
                            )}
                            <div className="flex flex-wrap gap-2 pt-1 text-sm">
                                {p.can.edit && (
                                    <Link href={`/user/posts/${p.id}/edit`}
                                        className="rounded bg-slate-100 px-3 py-1 font-semibold">Edit</Link>
                                )}
                                {p.can.submit && (
                                    <button onClick={() => setToSubmit(p)}
                                        className="rounded bg-slate-900 px-3 py-1 font-semibold text-white">
                                        {p.status === 'rejected' ? 'Kirim ulang' : 'Kirim'}
                                    </button>
                                )}
                                {p.can.delete && (
                                    <button onClick={() => remove(p)}
                                        className="rounded bg-red-50 px-3 py-1 font-semibold text-red-600">Hapus</button>
                                )}
                            </div>
                        </div>
                    </div>
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
        </UserLayout>
    );
}