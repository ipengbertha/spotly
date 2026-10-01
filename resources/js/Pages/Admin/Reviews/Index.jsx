import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import StatusBadge from '../../../Components/StatusBadge';

const pad = (n) => String(n).padStart(2, '0');
const toInputDate = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const defaultExpiry = () => {
    const d = new Date();
    d.setDate(d.getDate() + 7);
    return toInputDate(d);
};

function Modal({ title, onClose, children }) {
    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            onClick={onClose}
        >
            <div
                className="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white p-6 shadow-xl"
                onClick={(e) => e.stopPropagation()}
            >
                <h2 className="mb-4 text-lg font-semibold text-slate-900">{title}</h2>
                {children}
            </div>
        </div>
    );
}

export default function Index({ posts }) {
    const [dialog, setDialog] = useState(null); // { type: 'view' | 'approve' | 'reject', post }
    const approve = useForm({ expired_at: defaultExpiry() });
    const reject = useForm({ rejection_reason: '' });

    const open = (type, post) => {
        approve.reset();
        approve.clearErrors();
        reject.reset();
        reject.clearErrors();
        setDialog({ type, post });
    };
    const close = () => setDialog(null);

    const submitApprove = (e) => {
        e.preventDefault();
        approve.post(`/admin/reviews/${dialog.post.id}/approve`, {
            preserveScroll: true,
            onSuccess: close,
        });
    };

    const submitReject = (e) => {
        e.preventDefault();
        reject.post(`/admin/reviews/${dialog.post.id}/reject`, {
            preserveScroll: true,
            onSuccess: close,
        });
    };

    return (
        <AdminLayout title="Antrean Review">
            <Head title="Antrean Review" />

            <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                <table className="w-full text-left text-sm">
                    <thead className="bg-slate-50 text-slate-500">
                        <tr>
                            <th className="px-4 py-3 font-medium">Judul</th>
                            <th className="px-4 py-3 font-medium">Penulis</th>
                            <th className="px-4 py-3 font-medium">Kategori</th>
                            <th className="px-4 py-3 font-medium">Dikirim</th>
                            <th className="px-4 py-3 font-medium">Status</th>
                            <th className="px-4 py-3 font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {posts.data.length === 0 && (
                            <tr>
                                <td colSpan={6} className="p-6 text-center text-slate-500">
                                    Antrean kosong.
                                </td>
                            </tr>
                        )}
                        {posts.data.map((p) => (
                            <tr key={p.id}>
                                <td className="px-4 py-3 font-medium text-slate-800">{p.title}</td>
                                <td className="px-4 py-3">{p.user?.name}</td>
                                <td className="px-4 py-3">{p.category?.name}</td>
                                <td className="px-4 py-3">
                                    {new Date(p.updated_at).toLocaleDateString('id-ID')}
                                </td>
                                <td className="px-4 py-3"><StatusBadge status={p.status} /></td>
                                <td className="px-4 py-3">
                                    <div className="flex flex-wrap gap-2">
                                        <button
                                            onClick={() => open('view', p)}
                                            className="rounded border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100"
                                        >
                                            Lihat
                                        </button>
                                        <button
                                            onClick={() => open('approve', p)}
                                            className="rounded bg-green-600 px-3 py-1 text-xs font-medium text-white hover:bg-green-700"
                                        >
                                            Setujui
                                        </button>
                                        <button
                                            onClick={() => open('reject', p)}
                                            className="rounded bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-700"
                                        >
                                            Tolak
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="mt-4 flex flex-wrap gap-1">
                {posts.links.map((l, i) =>
                    l.url ? (
                        <Link
                            key={i}
                            href={l.url}
                            className={`rounded px-3 py-1 text-sm ${
                                l.active ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 hover:bg-slate-100'
                            }`}
                            dangerouslySetInnerHTML={{ __html: l.label }}
                        />
                    ) : (
                        <span
                            key={i}
                            className="px-3 py-1 text-sm text-slate-300"
                            dangerouslySetInnerHTML={{ __html: l.label }}
                        />
                    )
                )}
            </div>

            {dialog?.type === 'view' && (
                <Modal title={dialog.post.title} onClose={close}>
                    <p className="mb-3 text-xs text-slate-500">
                        {dialog.post.user?.name} &middot; {dialog.post.category?.name}
                    </p>
                    {dialog.post.image && (
                        <img
                            src={`/storage/${dialog.post.image}`}
                            alt=""
                            className="mb-3 max-h-64 w-full rounded-lg object-cover"
                        />
                    )}
                    <p className="whitespace-pre-line text-sm text-slate-700">{dialog.post.content}</p>
                    <div className="mt-6 flex justify-end">
                        <button
                            onClick={close}
                            className="rounded border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-100"
                        >
                            Tutup
                        </button>
                    </div>
                </Modal>
            )}

            {dialog?.type === 'approve' && (
                <Modal title="Setujui postingan" onClose={close}>
                    <form onSubmit={submitApprove}>
                        <p className="mb-4 text-sm text-slate-600">
                            &quot;{dialog.post.title}&quot; akan langsung tayang. Tentukan sampai kapan
                            postingan ini tampil di mading.
                        </p>
                        {approve.errors.post && (
                            <p className="mb-3 rounded bg-red-50 p-2 text-sm text-red-700">
                                {approve.errors.post}
                            </p>
                        )}
                        <label className="mb-1 block text-sm font-medium text-slate-700" htmlFor="expired_at">
                            Tayang sampai
                        </label>
                        <input
                            id="expired_at"
                            type="date"
                            min={toInputDate(new Date())}
                            value={approve.data.expired_at}
                            onChange={(e) => approve.setData('expired_at', e.target.value)}
                            className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                        />
                        {approve.errors.expired_at && (
                            <p className="mt-1 text-sm text-red-600">{approve.errors.expired_at}</p>
                        )}
                        <div className="mt-6 flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={close}
                                className="rounded border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-100"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                disabled={approve.processing}
                                className="rounded bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-60"
                            >
                                Setujui dan tayangkan
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {dialog?.type === 'reject' && (
                <Modal title="Tolak postingan" onClose={close}>
                    <form onSubmit={submitReject}>
                        <p className="mb-4 text-sm text-slate-600">
                            Penulis akan melihat alasan ini dan bisa memperbaiki lalu mengirim ulang
                            &quot;{dialog.post.title}&quot;.
                        </p>
                        {reject.errors.post && (
                            <p className="mb-3 rounded bg-red-50 p-2 text-sm text-red-700">
                                {reject.errors.post}
                            </p>
                        )}
                        <label className="mb-1 block text-sm font-medium text-slate-700" htmlFor="reason">
                            Alasan penolakan
                        </label>
                        <textarea
                            id="reason"
                            rows={4}
                            maxLength={500}
                            value={reject.data.rejection_reason}
                            onChange={(e) => reject.setData('rejection_reason', e.target.value)}
                            className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                        />
                        {reject.errors.rejection_reason && (
                            <p className="mt-1 text-sm text-red-600">{reject.errors.rejection_reason}</p>
                        )}
                        <div className="mt-6 flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={close}
                                className="rounded border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-100"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                disabled={reject.processing}
                                className="rounded bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-60"
                            >
                                Tolak postingan
                            </button>
                        </div>
                    </form>
                </Modal>
            )}
        </AdminLayout>
    );
}