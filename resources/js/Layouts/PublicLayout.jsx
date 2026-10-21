import { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import Logo from '../Components/Logo';
import AppDrawer from '../Components/AppDrawer';

const MENU = [
    ['Tentang', '/#tentang'],
    ['Kategori', '/#kategori'],
    ['Cara Kerja', '/#cara-kerja'],
    ['Postingan', '/#mading'],
];

// showDate : tampilkan tanggal hari ini
// appMenu  : untuk siswa/admin yang sudah masuk, tautan landing page dihapus dan
//            hamburger membuka menu aplikasi (sama seperti di halaman lain).
//            Tamu tetap memakai tautan landing page.
export default function PublicLayout({ children, showDate = false, appMenu = false }) {
    const user = usePage().props.auth?.user;
    const [open, setOpen] = useState(false);
    const appNav = appMenu && !!user;
    const today = new Date().toLocaleDateString('id-ID', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    });
    const dash = user?.role === 'admin' ? '/admin/dashboard' : '/user/dashboard';
    const ghost = 'rounded-lg px-3 py-1.5 font-semibold text-white/80 transition hover:bg-white/10 hover:text-white';
    const solid = 'rounded-lg bg-brand px-4 py-1.5 font-semibold text-white transition hover:bg-peach hover:text-ink';
    // Di panel HP: baris penuh dengan area sentuh yang lebih tinggi
    const row = `block ${ghost} py-2.5`;

    // Tombol "ghost" (Masuk / Dashboard)
    const ghostLink = user
        ? { href: dash, label: 'Dashboard' }
        : { href: '/login', label: 'Masuk' };

    return (
        <div className="min-h-dvh bg-cream text-ink">
            <header className="sticky top-0 z-40 border-b border-white/10 bg-ink/95 shadow-lg backdrop-blur">
                <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-2.5 sm:px-6 lg:px-10">
                    <Link href="/" className="rounded-xl bg-white px-3 py-1">
                        <Logo className="h-8" />
                    </Link>

                    {!appNav && (
                        <nav className="hidden items-center gap-1 text-sm md:flex">
                            {MENU.map(([label, href]) => (
                                <a key={href} href={href} className={ghost}>{label}</a>
                            ))}
                        </nav>
                    )}

                    <div className="flex items-center gap-2 text-sm">
                        {showDate && (
                            <span className="mr-2 hidden rounded-full bg-white/10 px-4 py-2 text-xs font-semibold text-white/80 xl:block">
                                {today}
                            </span>
                        )}
                        <Link href={ghostLink.href}
                            className={`${ghost} hidden ${appNav ? 'lg:block' : 'md:block'}`}>
                            {ghostLink.label}
                        </Link>
                        {user ? (
                            <button onClick={() => router.post('/logout')} className={solid}>Keluar</button>
                        ) : (
                            <Link href="/register" className={solid}>Daftar</Link>
                        )}
                        <button type="button" onClick={() => setOpen(!open)}
                            aria-expanded={open} aria-label={open ? 'Tutup menu' : 'Buka menu'}
                            className={`rounded-lg p-2 text-white/80 transition hover:bg-white/10 hover:text-white ${
                                appNav ? 'lg:hidden' : 'md:hidden'}`}>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8"
                                strokeLinecap="round" strokeLinejoin="round" className="h-6 w-6" aria-hidden="true">
                                <path d={open && !appNav ? 'M6 6l12 12M18 6L6 18' : 'M4 6h16M4 12h16M4 18h16'} />
                            </svg>
                        </button>
                    </div>
                </div>

                {/* Panel menu untuk tamu di HP (tautan landing page) */}
                {open && !appNav && (
                    <nav className="border-t border-white/10 px-4 py-2 text-sm sm:px-6 md:hidden"
                        onClick={() => setOpen(false)}>
                        {MENU.map(([label, href]) => (
                            <a key={href} href={href} className={row}>{label}</a>
                        ))}
                        <Link href={ghostLink.href} className={row}>{ghostLink.label}</Link>
                        {showDate && (
                            <p className="px-3 pb-1 pt-2 text-xs font-semibold text-white/50">{today}</p>
                        )}
                    </nav>
                )}
            </header>

            {/* Drawer menu aplikasi (siswa/admin). Sengaja di luar header: efek blur header
                akan mengunci posisi elemen fixed di dalamnya. */}
            {appNav && (
                <AppDrawer role={user.role} open={open} setOpen={setOpen} date={showDate ? today : null} />
            )}

            <main>{children}</main>
        </div>
    );
}