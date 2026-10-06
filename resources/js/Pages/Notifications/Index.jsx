import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import UserLayout from '../../Layouts/UserLayout';

// Teks tombol halaman bawaan Laravel masih berbahasa Inggris
const pageLabel = (label) =>
    label.replace('&laquo; Previous', '&laquo; Sebelumnya').replace('Next &raquo;', 'Berikutnya &raquo;');

const DOT = {
    approved:  'bg-grape',
    rejected:  'bg-brand',
    submitted: 'bg-peach',
};

export default function Index({ notifications, isAdmin }) {
    const Layout = isAdmin ? AdminLayout : UserLayout;
    const unread = notifications.data.filter((n) => !n.is_read).length;

    const open = (n) => router.post(`/notifications/${n.id}/read`);
    const readAll = () => router.post('/notifications/read-all', {}, { preserveScroll: true });

    return (
        <Layout title="Notifikasi">
            <Head title="Notifikasi" />

            <div className="mb-5 flex flex-wrap items-center justify-end gap-3">
                <button onClick={readAll} disabled={unread === 0}
                    className="rounded-full bg-grape/10 px-4 py-2 text-sm font-semibold text-grape hover:bg-grape/20 disabled:cursor-not-allowed disabled:opacity-40">
                    Tandai semua dibaca
                </button>
            </div>

            <div className="overflow-hidden rounded-3xl bg-white shadow-md ring-1 ring-ink/5">
                {notifications.data.length === 0 && (
                    <p className="p-8 text-center text-ink/60">Belum ada notifikasi.</p>
                )}
                <ul className="divide-y divide-ink/5">
                    {notifications.data.map((n) => (
                        <li key={n.id}>
                            <button onClick={() => open(n)}
                                className={`flex w-full items-start gap-3 px-5 py-4 text-left transition hover:bg-peach/20 ${
                                    n.is_read ? '' : 'bg-grape/5'}`}>
                                <span className={`mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full ${
                                    n.is_read ? 'bg-ink/15' : (DOT[n.type] ?? 'bg-grape')}`} />
                                <span className="min-w-0 flex-1">
                                    <span className={`block text-sm text-ink ${n.is_read ? 'font-medium' : 'font-bold'}`}>
                                        {n.title}
                                    </span>
                                    <span className="mt-0.5 block text-sm text-ink/70">{n.message}</span>
                                </span>
                                <span className="shrink-0 whitespace-nowrap text-xs text-ink/50">{n.time}</span>
                            </button>
                        </li>
                    ))}
                </ul>
            </div>

            <div className="mt-5 flex flex-wrap items-center justify-between gap-3">
                <p className="text-sm text-ink/60">
                    Menampilkan {notifications.from ?? 0}-{notifications.to ?? 0} dari {notifications.total} notifikasi
                </p>
                <div className="flex flex-wrap gap-1">
                    {notifications.links.map((l, i) =>
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
        </Layout>
    );
}