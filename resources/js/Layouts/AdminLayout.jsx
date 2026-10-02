import { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import Logo from '../Components/Logo';

const ICONS = {
    dash:    'M4 4h7v7H4zM13 4h7v4h-7zM13 11h7v9h-7zM4 14h7v6H4z',
    home:    'M3 11l9-8 9 8M5 10v10h5v-6h4v6h5V10',
    doc:     'M7 3h7l5 5v13H7zM14 3v5h5M10 13h6M10 17h6',
    clock:   'M12 21a9 9 0 100-18 9 9 0 000 18zM12 7v5l3 2',
    archive: 'M3 4h18v4H3zM5 8v12h14V8M10 12h4',
    tag:     'M20.6 13.4l-7.2 7.2a2 2 0 01-2.8 0L3 13V3h10l7.6 7.6a2 2 0 010 2.8zM7.5 7.5h.01',
    users:   'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.9M16 3.1a4 4 0 010 7.8',
    bell:    'M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 01-3.4 0',
    gear:    'M12 15a3 3 0 100-6 3 3 0 000 6zM19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z',
    logout:  'M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9',
    menu:    'M4 6h16M4 12h16M4 18h16',
    close:   'M6 6l12 12M18 6L6 18',
    search:  'M11 4a7 7 0 100 14 7 7 0 000-14zM21 21l-4.3-4.3',
};

function Ico({ name, className = 'h-5 w-5' }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8"
            strokeLinecap="round" strokeLinejoin="round" className={className} aria-hidden="true">
            <path d={ICONS[name]} />
        </svg>
    );
}

// Tooltip: hanya muncul di mode rail (layar lebar)
function Tip({ children }) {
    return (
        <span className="pointer-events-none absolute left-full z-50 ml-3 hidden whitespace-nowrap rounded-lg bg-ink px-2.5 py-1 text-xs font-semibold text-white opacity-0 shadow-lg ring-1 ring-white/10 transition group-hover:opacity-100 lg:block">
            {children}
        </span>
    );
}

const BASE =
    'group relative flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition ' +
    'lg:h-9 lg:w-9 lg:justify-center lg:rounded-full lg:p-0';

function NavItem({ icon, label, href, active, soon }) {
    const content = (
        <>
            <Ico name={icon} className="h-5 w-5 shrink-0" />
            <span className="lg:hidden">
                {label}
                {soon && <span className="ml-2 text-[10px] font-medium text-ink/40">segera</span>}
            </span>
            <Tip>{soon ? `${label} (segera)` : label}</Tip>
        </>
    );

    if (soon) {
        return (
            <span aria-disabled="true" className={`${BASE} cursor-not-allowed text-ink/30 lg:text-white/25`}>
                {content}
            </span>
        );
    }

    return (
        <Link href={href}
            className={`${BASE} ${
                active
                    ? 'bg-grape/10 text-grape lg:bg-brand lg:text-white lg:shadow-md'
                    : 'text-ink/70 hover:bg-peach/30 lg:text-white/70 lg:hover:bg-white/15 lg:hover:text-white'}`}>
            {content}
        </Link>
    );
}

export default function AdminLayout({ title, children }) {
    const { url, props } = usePage();
    const user = props.auth?.user;
    const [open, setOpen] = useState(false);

    const u = new URL(url, 'http://localhost');
    const onMading = u.pathname === '/user/beranda';
    const isMading = onMading;
    const onUsers = u.pathname.startsWith('/admin/users');
    const onReviews = u.pathname.startsWith('/admin/reviews');
    const onArchive = u.pathname.startsWith('/admin/archive');
    const onCategories = u.pathname.startsWith('/admin/categories');
    const searchPath = onMading ? '/user/beranda'
        : onUsers ? '/admin/users'
        : onArchive ? '/admin/archive'
        : onCategories ? '/admin/categories'
        : '/admin/reviews';
    const [q, setQ] = useState(u.pathname === searchPath ? (u.searchParams.get('q') ?? '') : '');

    const search = (e) => {
        e.preventDefault();
        router.get(searchPath, q ? { q } : {});
    };

    const isDash = url.startsWith('/admin/dashboard');
    const isReview = url.startsWith('/admin/reviews');
    const isPosts = url.startsWith('/admin/posts');
    const isCategories = url.startsWith('/admin/categories');
    const isUsers = url.startsWith('/admin/users');
    const isArchive = url.startsWith('/admin/archive');
    const initial = (user?.name ?? '?').trim().charAt(0).toUpperCase();
    const today = new Date().toLocaleDateString('id-ID', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    });

    // Menu dengan soon:true = halaman belum dibuat (tampil redup, tidak bisa diklik)
    const groups = [
        ['Utama', [
            { icon: 'dash',    label: 'Dashboard', href: '/admin/dashboard', active: isDash },
            { icon: 'home',    label: 'Mading',    href: '/user/beranda', active: isMading },
        ]],
        ['Konten', [
            { icon: 'doc',     label: 'Postingan', href: '/admin/posts', active: isPosts },
            { icon: 'clock',   label: 'Review',    href: '/admin/reviews', active: isReview },
            { icon: 'archive', label: 'Arsip',     href: '/admin/archive', active: isArchive },
        ]],
        ['Manajemen', [
            { icon: 'tag',     label: 'Kategori',  href: '/admin/categories', active: isCategories },
            { icon: 'users',   label: 'Pengguna',  href: '/admin/users', active: isUsers },
        ]],
        ['Akun', [
            { icon: 'bell',    label: 'Notifikasi', soon: true },
            { icon: 'gear',    label: 'Pengaturan', soon: true },
        ]],
    ];

    return (
        <div className="min-h-screen bg-linear-to-br from-peach/40 via-cream to-grape/15 bg-fixed text-ink">
            {open && (
                <div className="fixed inset-0 z-30 bg-ink/40 lg:hidden" onClick={() => setOpen(false)} />
            )}

            {/* SIDEBAR: drawer di layar kecil, rail ikon melayang di layar lebar */}
            <aside
                className={`fixed inset-y-0 left-0 z-40 flex w-64 flex-col gap-3 overflow-y-auto border-r border-peach/40 bg-white p-4 transition-transform lg:inset-y-3 lg:left-4 lg:w-16 lg:translate-x-0 lg:overflow-visible lg:border-0 lg:bg-transparent lg:p-0 ${
                    open ? 'translate-x-0' : '-translate-x-full'}`}
            >
                {/* Drawer (mobile): logo + label Admin + tombol tutup */}
                <div className="flex items-center justify-between lg:hidden">
                    <div>
                        <Link href="/"><Logo variant="wordmark" className="h-9" /></Link>
                        <p className="mt-1 text-xs font-semibold uppercase tracking-wide text-ink/40">Admin</p>
                    </div>
                    <button onClick={() => setOpen(false)}
                        className="rounded-lg p-1 text-ink/60 hover:bg-ink/5" aria-label="Tutup menu">
                        <Ico name="close" />
                    </button>
                </div>

                {/* Rail (desktop): pill logo */}
                <Link href="/" aria-label="Spotly"
                    className="hidden h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white shadow-md ring-1 ring-ink/5 lg:flex">
                    <Logo variant="icon" className="h-10" />
                </Link>

                {/* Menu utama */}
                <nav onClick={() => setOpen(false)}
                    className="flex min-h-0 flex-1 flex-col lg:items-center lg:justify-center lg:rounded-full lg:bg-ink lg:py-3 lg:shadow-md">
                    {groups.map(([label, items], i) => (
                        <div key={label} className="flex flex-col gap-0.5 lg:items-center">
                            {i > 0 && <div className="hidden h-px w-6 bg-white/20 lg:my-1.5 lg:block" />}
                            <p className="mt-4 px-3 text-xs font-bold uppercase tracking-wider text-ink/50 lg:hidden">
                                {label}
                            </p>
                            {items.map((it) => <NavItem key={it.label} {...it} />)}
                        </div>
                    ))}
                </nav>

                {/* Bawah: keluar */}
                <div className="flex shrink-0 flex-col border-t border-ink/10 pt-3 lg:items-center lg:rounded-full lg:border-t-0 lg:bg-brand lg:py-3 lg:shadow-md">
                    <button onClick={() => router.post('/logout')}
                        className={`${BASE} text-brand hover:bg-brand/10 lg:text-white lg:hover:bg-white/20`}>
                        <Ico name="logout" className="h-5 w-5 shrink-0" />
                        <span className="lg:hidden">Keluar</span>
                        <Tip>Keluar</Tip>
                    </button>
                </div>
            </aside>

            {/* AREA UTAMA */}
            <div className="lg:pl-24">
                {/* Header: pill putih melayang */}
                <header className="sticky top-0 z-20 px-4 pt-3 sm:px-6">
                    <div className="flex items-center gap-3 rounded-full bg-white px-3 py-2 shadow-md ring-1 ring-ink/5">
                        <button onClick={() => setOpen(true)}
                            className="rounded-full p-2 text-ink hover:bg-ink/5 lg:hidden" aria-label="Buka menu">
                            <Ico name="menu" />
                        </button>

                        <h1 className="truncate pl-2 text-xl text-ink">{title}</h1>

                        {(onMading || onUsers || onReviews || onArchive || onCategories) && (
                            <form onSubmit={search} className="relative ml-2 hidden w-full max-w-sm sm:block">
                                <Ico name="search" className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-ink/40" />
                                <input value={q} onChange={(e) => setQ(e.target.value)}
                                    placeholder={onMading ? 'Cari informasi...'
                                        : onUsers ? 'Cari pengguna...'
                                        : onArchive ? 'Cari di arsip...'
                                        : onCategories ? 'Cari kategori...'
                                        : 'Cari di antrean review...'}
                                    className="w-full rounded-full border border-ink/10 bg-cream/70 py-2 pl-10 pr-4 text-sm outline-none focus:border-grape focus:ring-2 focus:ring-grape/20" />
                            </form>
                        )}

                        <div className="ml-auto flex items-center gap-3">
                            <span className="hidden rounded-full bg-grape/10 px-4 py-2 text-xs font-semibold text-grape xl:block">
                                {today}
                            </span>
                            {!onReviews && (
                                <Link href="/admin/reviews"
                                    className="hidden items-center gap-2 rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90 sm:flex">
                                    <Ico name="clock" className="h-4 w-4" /> Antrean Review
                                </Link>
                            )}
                            <div className="flex items-center gap-2 rounded-full py-1 pl-1 pr-2">
                                <span className="flex h-8 w-8 items-center justify-center rounded-full bg-grape text-sm font-bold text-white">
                                    {initial}
                                </span>
                                <span className="hidden max-w-32 truncate text-sm font-semibold md:block">{user?.name}</span>
                            </div>
                        </div>
                    </div>
                </header>

                <main className="mx-auto max-w-6xl px-4 py-8 sm:px-6">{children}</main>
            </div>
        </div>
    );
}