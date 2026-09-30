import { Link, usePage } from '@inertiajs/react';
import Logo from '../Logo';

export default function Hero() {
    const user = usePage().props.auth?.user;
    return (
        <section className="mx-auto grid max-w-5xl items-center gap-8 px-6 py-14 md:grid-cols-2">
            <div>
                <span className="inline-block rounded-full bg-peach/40 px-3 py-1 text-xs font-semibold text-brand">
                    Mading digital sekolah
                </span>
                <h1 className="mt-4 text-4xl font-bold leading-tight text-ink sm:text-5xl">
                    Satu Spot untuk Semua <span className="text-brand">Kreativitas</span>
                </h1>
                <p className="mt-3 max-w-md text-ink/70">
                    Tempat berbagi informasi, karya, dan kreativitas siswa dalam satu mading digital sekolah.
                </p>
                <div className="mt-6 flex flex-wrap gap-3">
                    <a href="#mading" className="rounded-xl bg-brand px-5 py-2.5 font-semibold text-white hover:opacity-90">
                        Jelajahi Mading
                    </a>
                    <Link href={user ? '/user/dashboard' : '/register'}
                        className="rounded-xl border border-peach bg-white px-5 py-2.5 font-semibold text-ink hover:bg-peach/30">
                        Buat Postingan
                    </Link>
                </div>
            </div>
            <div className="flex justify-center">
                <Logo variant="icon" className="h-64" />
            </div>
        </section>
    );
}