import { Link, router, usePage } from '@inertiajs/react';
import Logo from '../Components/Logo';

const MENU = [
    ['Tentang', '/#tentang'],
    ['Kategori', '/#kategori'],
    ['Cara Kerja', '/#cara-kerja'],
    ['Postingan', '/#mading'],
];

export default function PublicLayout({ children }) {
    const user = usePage().props.auth?.user;
    const dash = user?.role === 'admin' ? '/admin/dashboard' : '/user/dashboard';
    const ghost = 'rounded-lg px-3 py-1.5 font-semibold text-white/80 transition hover:bg-white/10 hover:text-white';
    const solid = 'rounded-lg bg-brand px-4 py-1.5 font-semibold text-white transition hover:bg-peach hover:text-ink';

    return (
        <div className="min-h-screen bg-cream text-ink">
            <header className="sticky top-0 z-40 border-b border-white/10 bg-ink/95 shadow-lg backdrop-blur">
                <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-2.5 lg:px-10">
                    <Link href="/" className="rounded-xl bg-white px-3 py-1">
                        <Logo className="h-8" />
                    </Link>

                    <nav className="hidden items-center gap-1 text-sm md:flex">
                        {MENU.map(([label, href]) => (
                            <a key={href} href={href} className={ghost}>{label}</a>
                        ))}
                    </nav>

                    <div className="flex items-center gap-2 text-sm">
                        {user ? (
                            <>
                                <Link href={dash} className={ghost}>Dashboard</Link>
                                <button onClick={() => router.post('/logout')} className={solid}>Keluar</button>
                            </>
                        ) : (
                            <>
                                <Link href="/login" className={ghost}>Masuk</Link>
                                <Link href="/register" className={solid}>Daftar</Link>
                            </>
                        )}
                    </div>
                </div>
            </header>
            <main>{children}</main>
        </div>
    );
}