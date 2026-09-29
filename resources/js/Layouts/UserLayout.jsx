import { Link, router, usePage } from '@inertiajs/react';

export default function UserLayout({ children }) {
    const { auth } = usePage().props;

    return (
        <div className="min-h-screen bg-amber-50">
            <header className="flex items-center justify-between bg-white px-6 py-3 shadow-sm">
                <Link href="/" className="text-xl font-black tracking-tight">SPOTLY</Link>
                <div className="flex items-center gap-4 text-sm">
                    <span>Halo, {auth.user.name}</span>
                    <button onClick={() => router.post('/logout')}
                        className="rounded-lg bg-slate-900 px-3 py-1.5 font-semibold text-white">Keluar</button>
                </div>
            </header>
            <main className="mx-auto max-w-5xl p-6">{children}</main>
        </div>
    );
}