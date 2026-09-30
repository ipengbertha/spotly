import { Link, usePage } from '@inertiajs/react';

export default function CtaSection() {
    const user = usePage().props.auth?.user;
    return (
        <section className="mx-auto max-w-5xl px-6 py-12">
            <div className="rounded-3xl bg-ink px-6 py-12 text-center text-white">
                <h2 className="text-2xl font-bold sm:text-3xl">Punya sesuatu untuk dibagikan?</h2>
                <p className="mx-auto mt-2 max-w-md text-white/70">
                    Jadikan Spotly tempat untuk menunjukkan informasi, karya, dan kreativitasmu.
                </p>
                <Link href={user ? '/user/dashboard' : '/register'}
                    className="mt-6 inline-block rounded-xl bg-brand px-6 py-2.5 font-semibold text-white hover:opacity-90">
                    Mulai Posting →
                </Link>
            </div>
        </section>
    );
}