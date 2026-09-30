import { Link, router, usePage } from '@inertiajs/react';
import Logo from '../Components/Logo';

const MENU = [
    { label: 'Dashboard', href: '/admin/dashboard', match: '/admin/dashboard' },
    { label: 'Antrean Review', href: '/admin/reviews', match: '/admin/reviews' },
];

export default function AdminLayout({ title, children }) {
    const { url, props } = usePage();
    const user = props.auth?.user;

    return (
        <div className="flex min-h-screen bg-slate-50">
            <aside className="hidden w-60 shrink-0 flex-col border-r border-slate-200 bg-white p-4 md:flex">
                <Link href="/"><Logo variant="wordmark" className="h-9" /></Link>
                <p className="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Admin</p>

                <nav className="mt-6 flex flex-col gap-1">
                    {MENU.map((m) => {
                        const active = url.startsWith(m.match);
                        return (
                            <Link
                                key={m.href}
                                href={m.href}
                                className={`rounded-lg px-3 py-2 text-sm font-medium ${
                                    active ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'
                                }`}
                            >
                                {m.label}
                            </Link>
                        );
                    })}
                </nav>

                <div className="mt-auto border-t border-slate-200 pt-4 text-sm">
                    <p className="truncate font-medium text-slate-700">{user?.name}</p>
                    <button
                        onClick={() => router.post('/logout')}
                        className="mt-1 text-slate-500 hover:text-red-600"
                    >
                        Keluar
                    </button>
                </div>
            </aside>

            <main className="flex-1 p-6">
                <h1 className="text-2xl font-bold text-slate-900">{title}</h1>
                <div className="mt-6">{children}</div>
            </main>
        </div>
    );
}