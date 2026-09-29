import { Link } from '@inertiajs/react';

export default function AuthLayout({ title, children }) {
    return (
        <main className="flex min-h-screen flex-col items-center justify-center bg-amber-50 p-6">
            <Link href="/" className="text-center">
                <h1 className="text-4xl font-black tracking-tight text-slate-900">SPOTLY</h1>
                <p className="text-sm text-slate-600">A Spot for Innovation</p>
            </Link>
            <div className="mt-6 w-full max-w-md rounded-2xl bg-white p-6 shadow-lg ring-1 ring-slate-200">
                <h2 className="mb-4 text-xl font-bold text-slate-900">{title}</h2>
                {children}
            </div>
        </main>
    );
}