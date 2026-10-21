import { Link, router, usePage } from '@inertiajs/react';
import Logo from './Logo';
import useDrawer from '../hooks/useDrawer';

const ICONS = {
    dash:    'M4 4h7v7H4zM13 4h7v4h-7zM13 11h7v9h-7zM4 14h7v6H4z',
    home:    'M3 11l9-8 9 8M5 10v10h5v-6h4v6h5V10',
    spark:   'M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z',
    plus:    'M12 5v14M5 12h14',
    doc:     'M7 3h7l5 5v13H7zM14 3v5h5M10 13h6M10 17h6',
    heart:   'M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 00-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 000-7.8z',
    mark:    'M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z',
    clock:   'M12 3a9 9 0 100 18 9 9 0 000-18zM12 7v5l3 2',
    bell:    'M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 01-3.4 0',
    user:    'M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z',
    gear:    'M12 15a3 3 0 100-6 3 3 0 000 6zM19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z',
    logout:  'M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9',
    close:   'M6 6l12 12M18 6L6 18',
    flag:    'M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1zM4 22v-7',
    chat:    'M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z',
    archive: 'M3 4h18v4H3zM5 8v12h14V8M10 12h4',
    tag:     'M20.6 13.4l-7.2 7.2a2 2 0 01-2.8 0L3 13V3h10l7.6 7.6a2 2 0 010 2.8zM7.5 7.5h.01',
    users:   'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.9M16 3.1a4 4 0 010 7.8',
};

function Ico({ name, className = 'h-5 w-5' }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8"
            strokeLinecap="round" strokeLinejoin="round" className={className} aria-hidden="true">
            <path d={ICONS[name]} />
        </svg>
    );
}

// Isi menu sama dengan drawer di UserLayout dan AdminLayout
function groupsFor(role, unread, pendingReports) {
    if (role === 'admin') {
        return [
            ['Utama', [
                { icon: 'dash',    label: 'Dashboard', href: '/admin/dashboard' },
                { icon: 'home',    label: 'Mading',    href: '/user/beranda' },
            ]],
            ['Konten', [
                { icon: 'doc',     label: 'Postingan', href: '/admin/posts' },
                { icon: 'clock',   label: 'Review',    href: '/admin/reviews' },
                { icon: 'flag',    label: 'Laporan',   href: '/admin/reports', badge: pendingReports },
                { icon: 'chat',    label: 'Komentar',  href: '/admin/comments' },
                { icon: 'archive', label: 'Arsip',     href: '/admin/archive' },
            ]],
            ['Manajemen', [
                { icon: 'tag',     label: 'Kategori',  href: '/admin/categories' },
                { icon: 'users',   label: 'Pengguna',  href: '/admin/users' },
            ]],
            ['Akun', [
                { icon: 'bell',    label: 'Notifikasi', href: '/notifications', badge: unread },
                { icon: 'gear',    label: 'Pengaturan', href: '/settings' },
            ]],
        ];
    }

    return [
        ['Utama', [
            { icon: 'dash',  label: 'Dashboard',      href: '/user/dashboard' },
            { icon: 'home',  label: 'Mading',         href: '/user/beranda' },
            { icon: 'spark', label: 'Spotlight',      href: '/user/beranda?spotlight=1' },
        ]],
        ['Konten', [
            { icon: 'plus',  label: 'Buat Postingan', href: '/user/posts/create' },
            { icon: 'doc',   label: 'Postingan Saya', href: '/user/posts' },
            { icon: 'heart', label: 'Disukai',        href: '/user/disukai' },
            { icon: 'mark',  label: 'Tersimpan',      href: '/user/tersimpan' },
            { icon: 'clock', label: 'Riwayat',        href: '/user/riwayat' },
        ]],
        ['Akun', [
            { icon: 'bell',  label: 'Notifikasi',     href: '/notifications', badge: unread },
            { icon: 'user',  label: 'Profil',         soon: true },
            { icon: 'gear',  label: 'Pengaturan',     href: '/settings' },
        ]],
    ];
}

const ITEM = 'relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition';

// Drawer menu untuk layar kecil. Ditaruh di luar <header> supaya posisinya tidak
// terkunci oleh efek blur header.
export default function AppDrawer({ role, open, setOpen, date = null }) {
    const props = usePage().props;
    useDrawer(open, setOpen);

    const groups = groupsFor(role, props.unreadNotifications ?? 0, props.pendingReports ?? 0);

    return (
        <>
            {open && (
                <div className="fixed inset-0 z-50 bg-ink/40 lg:hidden" onClick={() => setOpen(false)} />
            )}

            <aside
                className={`fixed inset-y-0 right-0 z-60 flex w-64 flex-col gap-3 overflow-y-auto border-l border-peach/40 bg-white p-4 transition-[transform,visibility] lg:hidden ${
                    open ? 'translate-x-0' : 'translate-x-full invisible'}`}>
                <div className="flex items-center justify-between">
                    <div>
                        <Link href="/">
                            {role === 'admin'
                                ? <Logo variant="wordmark" className="h-9" />
                                : <Logo className="h-9" />}
                        </Link>
                        {role === 'admin' && (
                            <p className="mt-1 text-xs font-semibold uppercase tracking-wide text-ink/40">Admin</p>
                        )}
                    </div>
                    <button onClick={() => setOpen(false)}
                        className="rounded-lg p-1 text-ink/60 hover:bg-ink/5" aria-label="Tutup menu">
                        <Ico name="close" />
                    </button>
                </div>

                <nav onClick={() => setOpen(false)} className="flex min-h-0 flex-1 flex-col overflow-y-auto">
                    {groups.map(([label, items]) => (
                        <div key={label} className="flex flex-col gap-0.5">
                            <p className="mt-4 px-3 text-xs font-bold uppercase tracking-wider text-ink/50">{label}</p>
                            {items.map((it) => {
                                const inner = (
                                    <>
                                        <Ico name={it.icon} className="h-5 w-5 shrink-0" />
                                        <span>
                                            {it.label}
                                            {it.soon && <span className="ml-2 text-[10px] font-medium text-ink/40">segera</span>}
                                        </span>
                                        {it.badge > 0 && (
                                            <span className="absolute right-2 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-brand px-1 text-[10px] font-bold text-white ring-2 ring-white">
                                                {it.badge > 9 ? '9+' : it.badge}
                                            </span>
                                        )}
                                    </>
                                );
                                return it.soon ? (
                                    <span key={it.label} aria-disabled="true"
                                        className={`${ITEM} cursor-not-allowed text-ink/30`}>{inner}</span>
                                ) : (
                                    <Link key={it.label} href={it.href}
                                        className={`${ITEM} text-ink/70 hover:bg-peach/30`}>{inner}</Link>
                                );
                            })}
                        </div>
                    ))}
                </nav>

                <div className="shrink-0 border-t border-ink/10 pt-3">
                    {date && <p className="px-3 pb-2 text-xs font-medium text-ink/40">{date}</p>}
                    <button onClick={() => router.post('/logout')}
                        className={`${ITEM} w-full text-brand hover:bg-brand/10`}>
                        <Ico name="logout" className="h-5 w-5 shrink-0" />
                        <span>Keluar</span>
                    </button>
                </div>
            </aside>
        </>
    );
}