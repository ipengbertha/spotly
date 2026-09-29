import { Head, router, usePage } from '@inertiajs/react';

export default function Dashboard() {
    const { auth } = usePage().props;

    return (
        <main className="min-h-screen bg-amber-50 p-8">
            <Head title="Dashboard Admin" />
            <h1 className="text-2xl font-bold">Dashboard Admin</h1>
            <p className="mt-2">Halo, {auth.user.name} ({auth.user.role})</p>
            <button onClick={() => router.post('/logout')}
                className="mt-4 rounded-lg bg-slate-900 px-4 py-2 text-white">Keluar</button>
        </main>
    );
}