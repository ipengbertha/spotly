import { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import Logo from '../../Components/Logo';

export default function ForgotPassword() {
    const user = usePage().props.auth?.user;
    const [email, setEmail] = useState('');
    const [sent, setSent] = useState(false);

    const back = user ? '/settings' : '/login';
    const backLabel = user ? 'Kembali ke Pengaturan' : 'Kembali ke halaman masuk';

    const submit = (e) => {
        e.preventDefault();
        setSent(true);
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-linear-to-br from-peach/40 via-cream to-grape/15 px-4 py-10 text-ink">
            <Head title="Lupa Password" />

            <div className="w-full max-w-md rounded-3xl bg-white p-8 shadow-md ring-1 ring-ink/5">
                <Link href="/" className="mb-6 flex justify-center">
                    <Logo variant="wordmark" className="h-10" />
                </Link>

                <h1 className="text-center text-2xl text-ink">Lupa Password?</h1>
                <p className="mt-2 text-center text-sm text-ink/60">
                    Masukkan e-mail akunmu. Kami akan mengirim tautan untuk membuat password baru.
                </p>

                <form onSubmit={submit} className="mt-6 space-y-4">
                    <div>
                        <label htmlFor="email" className="mb-1 block text-sm font-semibold text-ink">E-mail</label>
                        <input id="email" type="email" required value={email}
                            onChange={(e) => { setEmail(e.target.value); setSent(false); }}
                            placeholder="nama@email.com"
                            className="w-full rounded-xl border border-ink/15 px-3 py-2 text-sm outline-none focus:border-grape focus:ring-2 focus:ring-grape/20" />
                    </div>

                    {sent && (
                        <p className="rounded-2xl bg-grape/10 px-4 py-3 text-sm text-ink ring-1 ring-grape/20">
                            Pengiriman e-mail reset password belum diaktifkan. Untuk sementara,
                            hubungi admin sekolah untuk mengatur ulang passwordmu.
                        </p>
                    )}

                    <button type="submit"
                        className="w-full rounded-full bg-brand px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90">
                        Kirim Tautan Reset
                    </button>
                </form>

                <Link href={back} className="mt-6 block text-center text-sm font-semibold text-grape hover:underline">
                    {backLabel}
                </Link>
            </div>
        </div>
    );
}