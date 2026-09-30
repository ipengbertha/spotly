import { Head, Link } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import StatusBadge from '../../Components/StatusBadge';

const CARDS = [
    { key: 'submitted', label: 'Menunggu review' },
    { key: 'active', label: 'Tayang sekarang' },
    { key: 'archive', label: 'Arsip' },
    { key: 'rejected', label: 'Ditolak' },
    { key: 'users', label: 'Pengguna' },
];

export default function Dashboard({ stats, queue }) {
    return (
        <AdminLayout title="Dashboard">
            <Head title="Dashboard Admin" />

            <div className="grid grid-cols-2 gap-4 lg:grid-cols-5">
                {CARDS.map((c) => (
                    <div key={c.key} className="rounded-xl border border-slate-200 bg-white p-4">
                        <p className="text-sm text-slate-500">{c.label}</p>
                        <p className="mt-1 text-3xl font-bold text-slate-900">{stats[c.key]}</p>
                    </div>
                ))}
            </div>

            <section className="mt-8 rounded-xl border border-slate-200 bg-white">
                <div className="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <h2 className="font-semibold text-slate-900">Antrean terbaru</h2>
                    <Link href="/admin/reviews" className="text-sm text-slate-600 hover:underline">
                        Lihat semua
                    </Link>
                </div>

                {queue.length === 0 ? (
                    <p className="p-6 text-center text-sm text-slate-500">Tidak ada postingan yang menunggu review.</p>
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {queue.map((p) => (
                            <li key={p.id} className="flex items-center justify-between gap-4 px-4 py-3">
                                <div className="min-w-0">
                                    <p className="truncate font-medium text-slate-800">{p.title}</p>
                                    <p className="text-xs text-slate-500">
                                        {p.user?.name} · {p.category?.name}
                                    </p>
                                </div>
                                <StatusBadge status={p.status} />
                            </li>
                        ))}
                    </ul>
                )}
            </section>
        </AdminLayout>
    );
}