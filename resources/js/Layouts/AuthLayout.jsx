import { Link } from '@inertiajs/react';
import Logo from '../Components/Logo';
import Icon from '../Components/Icon';

const POINTS = [
    ['pin', 'Bagikan informasi dan karyamu'],
    ['eye', 'Temukan kabar terbaru sekolah'],
    ['bulb', 'Tampilkan kreativitasmu'],
];

// Kiri: gelap dengan cahaya lembut di belakang logo, supaya logo menonjol
const DARK = {
    backgroundColor: '#241F52',
    backgroundImage: [
        'radial-gradient(at 50% 28%, rgba(255,255,255,0.16) 0px, transparent 55%)',
        'radial-gradient(at 50% 115%, rgba(240,36,90,0.30) 0px, transparent 55%)',
        'radial-gradient(at 0% 0%, rgba(124,58,237,0.25) 0px, transparent 50%)',
    ].join(','),
};

// Kanan: gradasi "mesh" yang halus (dulu dipakai di kiri)
const MESH = {
    backgroundColor: '#7C3AED',
    backgroundImage: [
        'radial-gradient(at 12% 8%, #A78BFA 0px, transparent 55%)',
        'radial-gradient(at 88% 12%, #F43F75 0px, transparent 55%)',
        'radial-gradient(at 80% 92%, #FF8A65 0px, transparent 55%)',
        'radial-gradient(at 8% 88%, #EC4899 0px, transparent 55%)',
    ].join(','),
};

export default function AuthLayout({ title, subtitle, children }) {
    return (
        <main className="min-h-screen lg:grid lg:grid-cols-2">
            {/* Panel kiri: gelap, menempel di layar. Bisa di-scroll tanpa garis scrollbar */}
            <aside style={DARK}
                className="relative hidden text-white lg:sticky lg:top-0 lg:block lg:h-screen lg:overflow-hidden">
                <div className="pointer-events-none absolute inset-0 overflow-hidden">
                    <div className="absolute -left-28 -top-28 h-80 w-80 rounded-full border-[3rem] border-white/5" />
                    <div className="absolute -bottom-24 -right-20 h-72 w-72 animate-pulse rounded-full bg-brand/25 blur-3xl" />
                </div>

                <div className="relative flex h-full flex-col px-12 py-8">
                    <Link href="/" className="w-fit rounded-xl bg-white px-4 py-2 shadow-lg">
                        <Logo className="h-9" />
                    </Link>

                    <div className="my-auto py-6 text-center">
                        <Logo variant="icon" className="animate-float mx-auto h-[min(17rem,32vh)] drop-shadow-2xl" />
                        <h2 className="mt-5 text-3xl font-bold leading-tight xl:text-4xl">
                            Satu Spot untuk Semua Kreativitas
                        </h2>
                        <p className="mx-auto mt-3 max-w-md text-white/85">
                            Mading digital sekolah tempat siswa berbagi informasi, karya, dan pencapaian.
                        </p>
                        <ul className="mx-auto mt-6 flex max-w-sm flex-col gap-3 text-left [@media(max-height:780px)]:hidden">
                            {POINTS.map(([icon, text]) => (
                                <li key={text} className="flex items-center gap-3 rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/20">
                                    <Icon name={icon} className="h-8 w-8" />
                                    <span className="font-semibold">{text}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </aside>

            {/* Panel kanan: gradasi berwarna + form. Tinggi mengikuti isi, halaman bisa di-scroll normal */}
            <section style={MESH} className="relative">
                <div className="pointer-events-none absolute inset-0 overflow-hidden">
                    <div className="absolute right-12 top-20 h-14 w-14 rotate-12 animate-float-slow rounded-2xl border-4 border-white/40" />
                    <div className="absolute left-10 top-1/2 h-8 w-8 animate-float-delay rounded-full bg-white/50" />
                    <div className="absolute bottom-24 right-16 h-4 w-4 animate-float-slow rounded-full bg-white/70" />
                    <div className="absolute bottom-16 left-16 h-16 w-16 -rotate-12 animate-float-delay rounded-full border-4 border-white/30" />
                </div>

                <div className="relative flex min-h-screen items-center justify-center px-6 py-10">
                    <div className="w-full max-w-md">
                        <Link href="/" className="mx-auto mb-6 block w-fit rounded-xl bg-white px-4 py-2 shadow-lg lg:hidden">
                            <Logo className="h-10" />
                        </Link>

                        <div className="overflow-hidden rounded-3xl bg-white shadow-2xl ring-4 ring-white/40">
                            <div className="h-2 bg-linear-to-r from-grape to-brand" />
                            <div className="p-7">
                                <h1 className="text-2xl font-bold text-ink">{title}</h1>
                                {subtitle && <p className="mt-1 text-sm text-ink/75">{subtitle}</p>}
                                <div className="mt-5">{children}</div>
                            </div>
                        </div>

                        <p className="mt-5 text-center text-sm">
                            <Link href="/" className="font-semibold text-white transition hover:underline">
                                ← Kembali ke beranda
                            </Link>
                        </p>
                    </div>
                </div>
            </section>
        </main>
    );
}