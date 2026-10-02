import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

export default function Index({ users, filters }) {
    const { errors } = usePage().props;

    const toggle = (u) => {
        if (u.is_active && !confirm(
            `Nonaktifkan akun ${u.name}? Dia tidak akan bisa login, tapi postingannya tetap ada.`
        )) return;

        router.patch(`/admin/users/${u.id}/${u.is_active ? 'deactivate' : 'activate'}`, {}, {
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout title="Pengguna">
            <Head title="Pengguna" />

            <p className="mb-5 text-sm text-ink/60">
                Akun siswa yang terdaftar. Akun yang dinonaktifkan tidak bisa login, dan postingannya tetap aman.
            </p>

            {errors.user && (
                <p className="mb-4 rounded-2xl bg-brand/10 px-4 py-3 text-sm font-medium text-brand">
                    {errors.user}
                </p>
            )}

            <div className="overflow-x-auto rounded-3xl bg-white shadow-md ring-1 ring-ink/5">
                <table className="w-full text-left text-sm">
                    <thead className="text-ink/50">
                        <tr>
                            <th className="px-5 py-4 font-medium">Nama</th>
                            <th className="px-5 py-4 font-medium">Postingan</th>
                            <th className="px-5 py-4 font-medium">Bergabung</th>
                            <th className="px-5 py-4 font-medium">Status</th>
                            <th className="px-5 py-4 font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-ink/5">
                        {users.data.length === 0 && (
                            <tr>
                                <td colSpan={5} className="p-8 text-center text-ink/60">
                                    {filters.q ? 'Tidak ada pengguna yang cocok.' : 'Belum ada pengguna.'}
                                </td>
                            </tr>
                        )}
                        {users.data.map((u) => (
                            <tr key={u.id}>
                                <td className="px-5 py-3">
                                    <p className="font-semibold text-ink">{u.name}</p>
                                    <p className="text-xs text-ink/60">{u.email}</p>
                                </td>
                                <td className="px-5 py-3 text-ink/70">{u.posts_count}</td>
                                <td className="px-5 py-3 text-ink/70">{u.joined}</td>
                                <td className="px-5 py-3">
                                    <span className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                                        u.is_active ? 'bg-grape/10 text-grape' : 'bg-brand/10 text-brand'}`}>
                                        {u.is_active ? 'Aktif' : 'Nonaktif'}
                                    </span>
                                </td>
                                <td className="px-5 py-3">
                                    <button onClick={() => toggle(u)}
                                        className={`rounded-full px-3 py-1 text-xs font-semibold ${
                                            u.is_active
                                                ? 'border border-brand/40 text-brand hover:bg-brand/10'
                                                : 'bg-grape/10 text-grape hover:bg-grape/20'}`}>
                                        {u.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {users.last_page > 1 && (
                <div className="mt-5 flex flex-wrap gap-1">
                    {users.links.map((l, i) =>
                        l.url ? (
                            <Link key={i} href={l.url}
                                className={`rounded-full px-3.5 py-1.5 text-sm font-semibold ${
                                    l.active ? 'bg-ink text-white' : 'bg-white text-ink/70 ring-1 ring-ink/10 hover:bg-peach/40'}`}
                                dangerouslySetInnerHTML={{ __html: l.label }} />
                        ) : (
                            <span key={i} className="px-3.5 py-1.5 text-sm text-ink/30"
                                dangerouslySetInnerHTML={{ __html: l.label }} />
                        )
                    )}
                </div>
            )}
        </AdminLayout>
    );
}