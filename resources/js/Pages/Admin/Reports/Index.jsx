import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

// Teks tombol halaman bawaan Laravel masih berbahasa Inggris
const pageLabel = (label) =>
    label.replace('&laquo; Previous', '&laquo; Sebelumnya').replace('Next &raquo;', 'Berikutnya &raquo;');

const INPUT = 'w-full rounded-xl border border-ink/15 px-3 py-2 text-sm outline-none focus:border-grape focus:ring-2 focus:ring-grape/20';

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

function Pagination({ data, noun }) {
    return (
        <div className="mt-5 flex flex-wrap items-center justify-between gap-3">
            <p className="text-sm text-ink/60">
                Menampilkan {data.from ?? 0}-{data.to ?? 0} dari {data.total} {noun}
            </p>
            <div className="flex flex-wrap gap-1">
                {data.links.map((l, i) =>
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
    );
}

export default function Index({ tab, posts, history, counts, reasons, filters }) {
    const [dialog, setDialog] = useState(null); // { type: 'suspend' | 'dismiss', post }
    const suspend = useForm({ reason: '', admin_note: '' });
    const dismiss = useForm({ admin_note: '' });

    const open = (type, post) => {
        suspend.clearErrors();
        dismiss.clearErrors();
        suspend.setData({ reason: post.top_reason ?? reasons[0]?.key ?? '', admin_note: '' });
        dismiss.setData({ admin_note: '' });
        setDialog({ type, post });
    };
    const close = () => setDialog(null);

    const submitSuspend = (e) => {
        e.preventDefault();
        suspend.post(`/admin/reports/posts/${dialog.post.id}/suspend`, { preserveScroll: true, onSuccess: close });
    };
    const submitDismiss = (e) => {
        e.preventDefault();
        dismiss.post(`/admin/reports/posts/${dialog.post.id}/dismiss`, { preserveScroll: true, onSuccess: close });
    };

    const tabHref = (t) => `/admin/reports?tab=${t}${filters.q ? `&q=${encodeURIComponent(filters.q)}` : ''}`;
    const reasonLabel = (key) => reasons.find((r) => r.key === key)?.label ?? key;

    return (
        <AdminLayout title="Laporan">
            <Head title="Laporan" />

            <div className="mb-5 flex items-start gap-3 rounded-2xl bg-grape/10 px-4 py-3 text-sm text-ink ring-1 ring-grape/20">
                <span className="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-grape text-xs font-bold text-white">i</span>
                <p>
                    Periksa dulu postingan yang dilaporkan. Pilih <b className="text-grape">Tangguhkan</b> kalau memang
                    melanggar (penulis melihat alasannya dan tidak bisa mengirim ulang), atau{' '}
                    <b className="text-grape">Tidak ada pelanggaran</b> supaya postingan tetap tayang dan pelapor diberi tahu.
                </p>
            </div>

            <div className="mb-5 flex gap-2">
                {[['pending', 'Menunggu', counts.pending], ['history', 'Riwayat', counts.history]].map(([key, label, n]) => (
                    <Link key={key} href={tabHref(key)}
                        className={`rounded-full px-4 py-1.5 text-sm font-semibold transition ${
                            tab === key ? 'bg-grape text-white shadow-sm' : 'bg-white text-ink/70 ring-1 ring-ink/15 hover:bg-peach/30'}`}>
                        {label} <span className="opacity-70">({n})</span>
                    </Link>
                ))}
            </div>

            {tab === 'pending' && (
                <>
                    {posts.data.length === 0 && (
                        <div className="rounded-3xl bg-white p-10 text-center text-ink/60 shadow-md ring-1 ring-ink/5">
                            {filters.q ? 'Tidak ada laporan yang cocok.' : 'Tidak ada laporan yang menunggu.'}
                        </div>
                    )}

                    <div className="space-y-4">
                        {posts.data.map((p) => (
                            <article key={p.id} className="rounded-3xl bg-white p-5 shadow-md ring-1 ring-ink/5">
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <Link href={`/posts/${p.slug}`} className="text-lg font-semibold text-ink hover:text-brand hover:underline">
                                            {p.title}
                                        </Link>
                                        <p className="mt-0.5 text-sm text-ink/60">
                                            {p.author} &middot; {p.category}
                                            {!p.can_suspend && <> &middot; <span className="font-semibold">tidak sedang tayang</span></>}
                                        </p>
                                    </div>
                                    <span className="whitespace-nowrap rounded-full bg-brand/10 px-3 py-1 text-xs font-bold text-brand">
                                        {p.pending_count} laporan
                                    </span>
                                </div>

                                <ul className="mt-4 space-y-2 border-t border-ink/10 pt-3 text-sm">
                                    {p.reports.map((r) => (
                                        <li key={r.id} className="rounded-xl bg-peach/20 px-3 py-2">
                                            <p className="font-semibold text-ink">{r.reason_label}</p>
                                            {r.note && <p className="mt-0.5 whitespace-pre-line text-ink/80">{r.note}</p>}
                                            <p className="mt-0.5 text-xs text-ink/50">{r.reporter} &middot; {r.time}</p>
                                        </li>
                                    ))}
                                </ul>

                                <div className="mt-4 flex flex-wrap gap-2">
                                    {p.can_suspend && (
                                        <button onClick={() => open('suspend', p)}
                                            className="rounded-full bg-brand px-4 py-1.5 text-sm font-semibold text-white hover:opacity-90">
                                            Tangguhkan
                                        </button>
                                    )}
                                    <button onClick={() => open('dismiss', p)}
                                        className="rounded-full border border-ink/20 px-4 py-1.5 text-sm font-semibold text-ink hover:bg-ink/5">
                                        Tidak ada pelanggaran
                                    </button>
                                </div>
                            </article>
                        ))}
                    </div>

                    <Pagination data={posts} noun="postingan" />
                </>
            )}

            {tab === 'history' && (
                <>
                    <div className="overflow-x-auto rounded-3xl bg-white shadow-md ring-1 ring-ink/5">
                        <table className="w-full text-left text-sm">
                            <thead className="text-ink/50">
                                <tr>
                                    <th className="px-5 py-4 font-medium">Ditinjau</th>
                                    <th className="px-5 py-4 font-medium">Postingan</th>
                                    <th className="px-5 py-4 font-medium">Pelapor</th>
                                    <th className="px-5 py-4 font-medium">Alasan</th>
                                    <th className="px-5 py-4 font-medium">Keputusan</th>
                                    <th className="px-5 py-4 font-medium">Catatan admin</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-ink/5">
                                {history.data.length === 0 && (
                                    <tr><td colSpan={6} className="p-8 text-center text-ink/60">Belum ada riwayat.</td></tr>
                                )}
                                {history.data.map((r) => (
                                    <tr key={r.id}>
                                        <td className="whitespace-nowrap px-5 py-3 text-ink/70">{r.reviewed_at}</td>
                                        <td className="px-5 py-3 font-semibold text-ink">
                                            {r.post_slug
                                                ? <Link href={`/posts/${r.post_slug}`} className="hover:text-brand hover:underline">{r.post_title}</Link>
                                                : r.post_title}
                                        </td>
                                        <td className="px-5 py-3 text-ink/70">{r.reporter}</td>
                                        <td className="px-5 py-3 text-ink/70">{r.reason_label}</td>
                                        <td className="px-5 py-3">
                                            <span className={`whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                                                r.status === 'upheld' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'}`}>
                                                {r.status === 'upheld' ? 'Ditangguhkan' : 'Tidak ada pelanggaran'}
                                            </span>
                                        </td>
                                        <td className="px-5 py-3 text-ink/70">{r.admin_note ?? '-'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <Pagination data={history} noun="laporan" />
                </>
            )}

            {dialog?.type === 'suspend' && (
                <Modal title="Tangguhkan postingan" onClose={close}>
                    <form onSubmit={submitSuspend}>
                        <p className="mb-4 text-sm text-ink/60">
                            &quot;{dialog.post.title}&quot; akan dicabut dari mading. Penulis melihat alasan di bawah
                            dan tidak bisa mengedit atau mengirimnya ulang.
                        </p>
                        {suspend.errors.post && (
                            <p className="mb-3 rounded-2xl bg-brand/10 px-3 py-2 text-sm font-medium text-brand">{suspend.errors.post}</p>
                        )}
                        <label htmlFor="s-reason" className="mb-1 block text-sm font-semibold text-ink">Alasan penangguhan</label>
                        <select id="s-reason" value={suspend.data.reason}
                            onChange={(e) => suspend.setData('reason', e.target.value)} className={INPUT}>
                            {reasons.map((r) => <option key={r.key} value={r.key}>{r.label}</option>)}
                        </select>
                        {suspend.errors.reason && <p className="mt-1 text-sm text-brand">{suspend.errors.reason}</p>}

                        <label htmlFor="s-note" className="mb-1 mt-4 block text-sm font-semibold text-ink">
                            Catatan untuk penulis <span className="font-normal text-ink/50">(opsional)</span>
                        </label>
                        <textarea id="s-note" rows={3} maxLength={300} value={suspend.data.admin_note}
                            onChange={(e) => suspend.setData('admin_note', e.target.value)} className={INPUT} />
                        {suspend.errors.admin_note && <p className="mt-1 text-sm text-brand">{suspend.errors.admin_note}</p>}

                        <p className="mt-4 rounded-xl bg-red-50 px-3 py-2 text-sm text-ink/80">
                            <b className="text-red-700">Yang dilihat penulis:</b>{' '}
                            {reasonLabel(suspend.data.reason)}
                            {suspend.data.admin_note.trim() && `. ${suspend.data.admin_note.trim()}`}
                        </p>

                        <div className="mt-6 flex justify-end gap-2">
                            <button type="button" onClick={close}
                                className="rounded-full border border-ink/20 px-4 py-2 text-sm font-semibold text-ink hover:bg-ink/5">Batal</button>
                            <button type="submit" disabled={suspend.processing}
                                className="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-60">
                                Tangguhkan
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {dialog?.type === 'dismiss' && (
                <Modal title="Tidak ada pelanggaran" onClose={close}>
                    <form onSubmit={submitDismiss}>
                        <p className="mb-4 text-sm text-ink/60">
                            &quot;{dialog.post.title}&quot; tetap tayang. Semua pelapor ({dialog.post.pending_count}) menerima
                            notifikasi bahwa tidak ditemukan pelanggaran.
                        </p>
                        {dismiss.errors.post && (
                            <p className="mb-3 rounded-2xl bg-brand/10 px-3 py-2 text-sm font-medium text-brand">{dismiss.errors.post}</p>
                        )}
                        <label htmlFor="d-note" className="mb-1 block text-sm font-semibold text-ink">
                            Catatan untuk pelapor <span className="font-normal text-ink/50">(opsional)</span>
                        </label>
                        <textarea id="d-note" rows={3} maxLength={300} value={dismiss.data.admin_note}
                            onChange={(e) => dismiss.setData('admin_note', e.target.value)} className={INPUT} />
                        {dismiss.errors.admin_note && <p className="mt-1 text-sm text-brand">{dismiss.errors.admin_note}</p>}

                        <div className="mt-6 flex justify-end gap-2">
                            <button type="button" onClick={close}
                                className="rounded-full border border-ink/20 px-4 py-2 text-sm font-semibold text-ink hover:bg-ink/5">Batal</button>
                            <button type="submit" disabled={dismiss.processing}
                                className="rounded-full bg-grape px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-60">
                                Simpan keputusan
                            </button>
                        </div>
                    </form>
                </Modal>
            )}
        </AdminLayout>
    );
}
