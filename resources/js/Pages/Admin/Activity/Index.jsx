import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

// Teks tombol halaman bawaan Laravel masih berbahasa Inggris
const pageLabel = (label) =>
    label.replace('&laquo; Previous', '&laquo; Sebelumnya').replace('Next &raquo;', 'Berikutnya &raquo;');

const fmtTime = (iso) =>
    new Date(iso).toLocaleString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });

export default function Index({ logs, filters }) {
    const [q, setQ] = useState(filters?.q ?? '');

    const search = (e) => {
        e.preventDefault();
        router.get('/admin/activity', q ? { q } : {}, { preserveState: true, replace: true });
    };

    return (
        <AdminLayout title="Aktivitas">
            <Head title="Aktivitas Admin" />

            <div className="mb-5 flex flex-wrap items-stretch gap-3">
                <div className="flex min-w-0 flex-1 items-start gap-3 rounded-2xl bg-grape/10 px-4 py-3 text-sm text-ink ring-1 ring-grape/20">
                    <span className="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-grape text-xs font-bold text-white">
                        i
                    </span>
                    <p>
                        Riwayat tindakan admin: siapa melakukan apa dan kapan.
                        Catatan tetap tersimpan walau postingan, akun, atau kategorinya sudah dihapus.
                    </p>
                </div>

                <form onSubmit={search} className="flex w-full items-center gap-2 sm:w-auto">
                    <input value={q} onChange={(e) => setQ(e.target.value)}
                        placeholder="Cari nama, admin, atau catatan..."
                        aria-label="Cari aktivitas"
                        className="w-full rounded-full border border-ink/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-grape focus:ring-2 focus:ring-grape/20 sm:w-72" />
                    <button type="submit"
                        className="shrink-0 rounded-full bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">
                        Cari
                    </button>
                </form>
            </div>

            <div className="overflow-x-auto rounded-3xl bg-white shadow-md ring-1 ring-ink/5">
                <table className="w-full text-left text-sm">
                    <thead className="text-ink/50">
                        <tr>
                            <th className="whitespace-nowrap px-5 py-4 font-medium">Waktu</th>
                            <th className="px-5 py-4 font-medium">Admin</th>
                            <th className="px-5 py-4 font-medium">Aktivitas</th>
                            <th className="px-5 py-4 font-medium">Catatan</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-ink/5">
                        {logs.data.length === 0 && (
                            <tr>
                                <td colSpan={4} className="p-8 text-center text-ink/60">
                                    {filters?.q ? 'Tidak ada aktivitas yang cocok.' : 'Belum ada aktivitas yang tercatat.'}
                                </td>
                            </tr>
                        )}
                        {logs.data.map((l) => (
                            <tr key={l.id}>
                                <td className="whitespace-nowrap px-5 py-3 text-ink/70">{fmtTime(l.created_at)}</td>
                                <td className="whitespace-nowrap px-5 py-3 font-semibold text-ink">{l.admin}</td>
                                <td className="px-5 py-3 text-ink">
                                    {l.label}
                                    {l.title && <> <span className="font-semibold">&quot;{l.title}&quot;</span></>}
                                </td>
                                <td className="px-5 py-3 text-ink/70">{l.detail ?? '-'}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="mt-5 flex flex-wrap items-center justify-between gap-3">
                <p className="text-sm text-ink/60">
                    Menampilkan {logs.from ?? 0}-{logs.to ?? 0} dari {logs.total} aktivitas
                </p>
                <div className="flex flex-wrap gap-1">
                    {logs.links.map((l, i) =>
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
        </AdminLayout>
    );
}