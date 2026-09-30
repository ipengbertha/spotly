import { Link, usePage } from '@inertiajs/react';
import Logo from '../Logo';

export default function Footer() {
    const user = usePage().props.auth?.user;
    const link = 'text-ink/70 hover:text-brand';
    return (
        <footer className="mt-12 border-t border-peach/40 bg-white">
            <div className="mx-auto grid max-w-5xl gap-8 px-6 py-10 sm:grid-cols-3">
                <div>
                    <Logo variant="full" className="h-12" />
                    <p className="mt-2 text-sm text-ink/60">A Spot for Innovation</p>
                </div>
                <div>
                    <p className="text-sm font-semibold text-ink">Navigasi</p>
                    <ul className="mt-2 space-y-1 text-sm">
                        <li><Link href="/" className={link}>Beranda</Link></li>
                        <li><a href="#kategori" className={link}>Kategori</a></li>
                        <li><a href="#kenapa-spotly" className={link}>Kenapa Spotly</a></li>
                    </ul>
                </div>
                <div>
                    <p className="text-sm font-semibold text-ink">Akun</p>
                    <ul className="mt-2 space-y-1 text-sm">
                        {user ? (
                            <li><Link href={user.role === 'admin' ? '/admin/dashboard' : '/user/dashboard'} className={link}>Dashboard</Link></li>
                        ) : (
                            <>
                                <li><Link href="/login" className={link}>Masuk</Link></li>
                                <li><Link href="/register" className={link}>Daftar</Link></li>
                            </>
                        )}
                    </ul>
                </div>
            </div>
            <p className="border-t border-peach/30 py-4 text-center text-xs text-ink/50">
                © 2026 Spotly. All rights reserved.
            </p>
        </footer>
    );
}