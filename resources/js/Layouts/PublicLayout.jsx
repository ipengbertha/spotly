import { Link, router, usePage } from '@inertiajs/react';

export default function PublicLayout({ children }) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const dashboard = user?.role === 'admin' ? '/admin/dashboard' : '/user/dashboard';

    return (
        <div className="flex min-h-screen flex-col bg-amber-50 text-slate-900">
            <header className="sticky top-0 z-10 border-b border-slate-200 bg-white/90 backdrop-blur">
                <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
                    <Link href="/"><Logo className="h-11" /></Link>
                    <nav className="flex items-center gap-2 text-sm font-semibold">
                        {user ? (
                            <>
                                <Link href={dashboard} className="rounded-lg px-3 py-2 hover:bg-slate-100">
                                    Halo, {user.name}
                                </Link>
                                <button onClick={() => router.post('/logout')}
                                    className="rounded-lg bg-slate-900 px-4 py-2 text-white">Keluar</button>
                            </>
                        ) : (
                            <>
                                <Link href="/login" className="rounded-lg px-3 py-2 hover:bg-slate-100">Masuk</Link>
                                <Link href="/register" className="rounded-lg bg-slate-900 px-4 py-2 text-white">Daftar</Link>
                            </>
                        )}
                    </nav>
                </div>
            </header>

            <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-8">{children}</main>

            <div className="mx-auto flex max-w-6xl flex-col items-center gap-3 px-4 text-center text-sm text-ink/60">
                <Logo variant="full" className="h-16" />
                <p>© {new Date().getFullYear()} Spotly</p>
            </div>
        </div>
    );
}