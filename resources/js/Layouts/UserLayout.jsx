import { Link, router, usePage } from '@inertiajs/react';
import Logo from '../Components/Logo';

export default function UserLayout({ children }) {
    const { auth } = usePage().props;
    const path = typeof window !== 'undefined' ? window.location.pathname : '';

    const navLink = (href, label) => (
        <Link href={href}
            className={`rounded-lg px-3 py-1.5 font-semibold transition ${
                path.startsWith(href) && href !== '/'
                    ? 'bg-peach/40 text-ink'
                    : 'text-ink/70 hover:bg-peach/30'
            }`}>
            {label}
        </Link>
    );

    return (
        <div className="min-h-screen bg-cream text-ink">
            <header className="border-b border-peach/40 bg-white shadow-sm">
                <div className="mx-auto flex max-w-5xl items-center justify-between px-6 py-3">
                    <div className="flex items-center gap-6">
                        <Link href="/"><Logo className="h-10" /></Link>
                        <nav className="hidden items-center gap-1 text-sm sm:flex">
                            {navLink('/', 'Beranda')}
                            {navLink('/user/dashboard', 'Postingan Saya')}
                        </nav>
                    </div>
                    <div className="flex items-center gap-4 text-sm">
                        <span className="hidden sm:inline">Halo, {auth.user.name}</span>
                        <button onClick={() => router.post('/logout')}
                            className="rounded-lg bg-brand px-3 py-1.5 font-semibold text-white hover:opacity-90">
                            Keluar
                        </button>
                    </div>
                </div>
            </header>
            <main className="mx-auto max-w-5xl p-6">{children}</main>
        </div>
    );
}