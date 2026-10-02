import { useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import StatusBadge from '../../../Components/StatusBadge';

const pad = (n) => String(n).padStart(2, '0');
const toInputDate = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const defaultExpiry = () => {
    const d = new Date();
    d.setDate(d.getDate() + 7);
    return toInputDate(d);
};

// Teks tombol halaman bawaan Laravel masih berbahasa Inggris
const pageLabel = (label) =>
    label.replace('&laquo; Previous', '&laquo; Sebelumnya').replace('Next &raquo;', 'Berikutnya &raquo;');

function Modal({ title, onClose, children }) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 p-4" onClick={onClose}>
            <div className="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-3xl bg-white p-6 shadow-xl"
                onClick={(e) => e.stopPropagation()}>
                <h2 className="mb-4 text-lg text-ink">{title}</h2>
                {children}
            </div>
        </div>
    );
}

export default function Index({ posts }) {
    const { url } = usePage();
    const keyword = new URL(url, 'http://localhost').searchParams.get('q');

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

            {/* Keterangan berbentuk highlight */}
            <div className="mb-5 flex items-start gap-3 rounded-2xl bg-grape/10 px-4 py-3 text-sm text-ink ring-1 ring-grape/20">
                <span className="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-grape text-xs font-bold text-white">
                    i
                </span>
                <p>
                    Postingan siswa yang menunggu keputusanmu. <b className="text-grape">Setujui</b> untuk
                    menayangkannya di mading, atau <b className="text-grape">tolak</b> dengan alasan agar
                    penulis bisa memperbaikinya.
                </p>
            </div>

            <div className="overflow-x-auto rounded-3xl bg-white shadow-md ring-1 ring-ink/5">
                <table className="w-full text-left text-sm">
                    <thead className="text-ink/50">
                        <tr>
                            <th className="px-5 py-4 font-medium">Judul</th>
                            <th className="px-5 py-4 font-medium">Penulis</th>
                            <th className="px-5 py-4 font-medium">Kategori</th>
                            <th className="px-5 py-4 font-medium">Dikirim</th>
                            <th className="px-5 py-4 font-medium">Status</th>
                            <th className="px-5 py-4 font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-ink/5">
                        {posts.data.length === 0 && (
                            <tr>
                                <td colSpan={6} className="p-8 text-center text-ink/60">
                                    {keyword ? 'Tidak ada postingan yang cocok.' : 'Antrean kosong.'}
                                </td>
                            </tr>
                        )}
                        {posts.data.map((p) => (
                            <tr key={p.id}>
                                <td className="px-5 py-3 font-semibold text-ink">{p.title}</td>
                                <td className="px-5 py-3 text-ink/70">{p.user?.name}</td>
                                <td className="px-5 py-3 text-ink/70">{p.category?.name}</td>
                                <td className="px-5 py-3 text-ink/70">
                                    {new Date(p.updated_at).toLocaleDateString('id-ID')}
                                </td>
                                <td className="px-5 py-3"><StatusBadge status={p.status} /></td>
                                <td className="whitespace-nowrap px-5 py-3">
                                    <div className="flex flex-nowrap gap-2">
                                        <button onClick={() => open('view', p)}
                                            className="rounded-full border border-ink/20 px-3 py-1 text-xs font-semibold text-ink hover:bg-ink/5">
                                            Lihat
                                        </button>
                                        <button onClick={() => open('approve', p)}
                                            className="rounded-full bg-grape px-3 py-1 text-xs font-semibold text-white hover:opacity-90">
                                            Setujui
                                        </button>
                                        <button onClick={() => open('reject', p)}
                                            className="rounded-full border border-brand/40 px-3 py-1 text-xs font-semibold text-brand hover:bg-brand/10">
                                            Tolak
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

            {dialog?.type === 'view' && (
                <Modal title={dialog.post.title} onClose={close}>
                    <p className="mb-3 text-xs text-ink/60">
                        {dialog.post.user?.name} &middot; {dialog.post.category?.name}
                    </p>
                    {dialog.post.image && (
                        <img src={`/storage/${dialog.post.image}`} alt=""
                            className="mb-3 max-h-64 w-full rounded-2xl object-cover" />
                    )}
                    <p className="whitespace-pre-line text-sm text-ink/80">{dialog.post.content}</p>
                    <div className="mt-6 flex justify-end">
                        <button onClick={close}
                            className="rounded-full border border-ink/20 px-4 py-2 text-sm font-semibold text-ink hover:bg-ink/5">
                            Tutup
                        </button>
                    </div>
                </Modal>
            )}

            {dialog?.type === 'approve' && (
                <Modal title="Setujui postingan" onClose={close}>
                    <form onSubmit={submitApprove}>
                        <p className="mb-4 text-sm text-ink/60">
                            &quot;{dialog.post.title}&quot; akan langsung tayang. Tentukan sampai kapan
                            postingan ini tampil di mading.
                        </p>
                        {approve.errors.post && (
                            <p className="mb-3 rounded-2xl bg-brand/10 px-3 py-2 text-sm font-medium text-brand">
                                {approve.errors.post}
                            </p>
                        )}
                        <label className="mb-1 block text-sm font-semibold text-ink" htmlFor="expired_at">
                            Tayang sampai
                        </label>
                        <input id="expired_at" type="date" min={toInputDate(new Date())}
                            value={approve.data.expired_at}
                            onChange={(e) => approve.setData('expired_at', e.target.value)}
                            className="w-full rounded-xl border border-ink/15 px-3 py-2 text-sm outline-none focus:border-grape focus:ring-2 focus:ring-grape/20" />
                        {approve.errors.expired_at && (
                            <p className="mt-1 text-sm text-brand">{approve.errors.expired_at}</p>
                        )}
                        <div className="mt-6 flex justify-end gap-2">
                            <button type="button" onClick={close}
                                className="rounded-full border border-ink/20 px-4 py-2 text-sm font-semibold text-ink hover:bg-ink/5">
                                Batal
                            </button>
                            <button type="submit" disabled={approve.processing}
                                className="rounded-full bg-grape px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-60">
                                Setujui dan tayangkan
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {dialog?.type === 'reject' && (
                <Modal title="Tolak postingan" onClose={close}>
                    <form onSubmit={submitReject}>
                        <p className="mb-4 text-sm text-ink/60">
                            Penulis akan melihat alasan ini dan bisa memperbaiki lalu mengirim ulang
                            &quot;{dialog.post.title}&quot;.
                        </p>
                        {reject.errors.post && (
                            <p className="mb-3 rounded-2xl bg-brand/10 px-3 py-2 text-sm font-medium text-brand">
                                {reject.errors.post}
                            </p>
                        )}
                        <label className="mb-1 block text-sm font-semibold text-ink" htmlFor="reason">
                            Alasan penolakan
                        </label>
                        <textarea id="reason" rows={4} maxLength={500}
                            value={reject.data.rejection_reason}
                            onChange={(e) => reject.setData('rejection_reason', e.target.value)}
                            className="w-full rounded-xl border border-ink/15 px-3 py-2 text-sm outline-none focus:border-grape focus:ring-2 focus:ring-grape/20" />
                        {reject.errors.rejection_reason && (
                            <p className="mt-1 text-sm text-brand">{reject.errors.rejection_reason}</p>
                        )}
                        <div className="mt-6 flex justify-end gap-2">
                            <button type="button" onClick={close}
                                className="rounded-full border border-ink/20 px-4 py-2 text-sm font-semibold text-ink hover:bg-ink/5">
                                Batal
                            </button>
                            <button type="submit" disabled={reject.processing}
                                className="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-60">
                                Tolak postingan
                            </button>
                        </div>
                    </form>
                </Modal>
            )}
        </AdminLayout>
    );
}