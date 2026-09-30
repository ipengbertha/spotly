import { Head, Link } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import StatusBadge from '../../../Components/StatusBadge';

export default function Index({ posts }) {
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
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {posts.data.length === 0 && (
                            <tr>
                                <td colSpan={5} className="p-6 text-center text-slate-500">
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
        </AdminLayout>
    );
}