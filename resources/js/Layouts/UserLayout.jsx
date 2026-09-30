import { Link, router, usePage } from '@inertiajs/react';
import Logo from '../Components/Logo';

export default function PublicLayout({ children }) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const dashboardHref = user?.role === 'admin' ? '/admin/dashboard' : '/user/dashboard';

    return (
        <div className="min-h-screen bg-cream text-ink">
            <header className="border-b border-peach/40 bg-white shadow-sm">
                <div className="mx-auto flex max-w-5xl items-center justify-between px-6 py-3">
                    <Link href="/"><Logo className="h-10" /></Link>

                    <div className="flex items-center gap-3 text-sm">
                        {user ? (
                            <>
                                <Link href={dashboardHref}
                                    className="rounded-lg px-3 py-1.5 font-semibold text-ink/70 hover:bg-peach/30">
                                    Dashboard
                                </Link>
                                <button onClick={() => router.post('/logout')}
                                    className="rounded-lg bg-brand px-3 py-1.5 font-semibold text-white hover:opacity-90">
                                    Keluar
                                </button>
                            </>
                        ) : (
                            <>
                                <Link href="/login"
                                    className="rounded-lg px-3 py-1.5 font-semibold text-ink/70 hover:bg-peach/30">
                                    Masuk
                                </Link>
                                <Link href="/register"
                                    className="rounded-lg bg-brand px-3 py-1.5 font-semibold text-white hover:opacity-90">
                                    Daftar
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </header>
            <main>{children}</main>
        </div>
    );
}