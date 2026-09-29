import { Head, Link, usePage } from '@inertiajs/react';

export default function Home() {
    const { auth } = usePage().props;
    const dashboard = auth.user?.role === 'admin' ? '/admin/dashboard' : '/user/dashboard';

    return (
        <>
            <Head title="Beranda" />
            <main className="flex min-h-screen flex-col items-center justify-center bg-amber-50 p-6 text-center">
                <h1 className="text-5xl font-black tracking-tight text-slate-900">SPOTLY</h1>
                <p className="mt-2 text-lg text-slate-600">A Spot for Innovation</p>
                <div className="mt-8 flex gap-3">
                    {auth.user ? (
                        <Link href={dashboard} className="rounded-lg bg-slate-900 px-5 py-2 font-semibold text-white">
                            Ke Dashboard
                        </Link>
                    ) : (
                        <>
                            <Link href="/login" className="rounded-lg bg-slate-900 px-5 py-2 font-semibold text-white">Masuk</Link>
                            <Link href="/register" className="rounded-lg bg-white px-5 py-2 font-semibold ring-1 ring-slate-300">Daftar</Link>
                        </>
                    )}
                </div>
            </main>
        </>
    );
}