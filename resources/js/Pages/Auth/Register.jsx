import { Head, Link, useForm } from '@inertiajs/react';
import AuthLayout from '../../Layouts/AuthLayout';
import TextField from '../../Components/TextField';

const RULES = [
    ['Minimal 8 karakter', (v) => v.length >= 8],
    ['Ada huruf besar (A-Z)', (v) => /[A-Z]/.test(v)],
    ['Ada angka (0-9)', (v) => /\d/.test(v)],
    ['Ada simbol (contoh: ! @ # $)', (v) => /[^A-Za-z0-9]/.test(v)],
];

function Mark({ ok }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" className="h-4 w-4 shrink-0">
            {ok ? <polyline points="20 6 9 17 4 12" /> : <><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></>}
        </svg>
    );
}

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        phone: '',
        username: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/register');
    };

    const mismatch = data.password_confirmation !== '' && data.password !== data.password_confirmation;
    const match = data.password_confirmation !== '' && !mismatch;

    return (
        <AuthLayout title="Buat akun Spotly" subtitle="Isi data diri sesuai dengan data diirmu dan mulai perjalananmu di Spotly!">
            <Head title="Daftar" />
            <form onSubmit={submit} className="space-y-4">
                <TextField label="Nama Lengkap" autoComplete="name" value={data.name}
                    onChange={(e) => setData('name', e.target.value)} error={errors.name} />

                <TextField label="E-Mail Aktif" type="email" autoComplete="email" value={data.email}
                    onChange={(e) => setData('email', e.target.value)} error={errors.email} />

                <TextField label="Nomor Telp. Aktif" type="tel" inputMode="numeric" autoComplete="tel"
                    placeholder="08xxxxxxxxxx" value={data.phone}
                    onChange={(e) => setData('phone', e.target.value.replace(/[^0-9+]/g, ''))}
                    error={errors.phone} />

                <TextField label="Username ID" autoComplete="username" placeholder="contoh: dimas_25"
                    hint="Buat Username ID milikmu. Gunakan huruf kecil, angka, underscore, dan titik."
                    value={data.username}
                    onChange={(e) => setData('username', e.target.value.toLowerCase().replace(/[^a-z0-9_.]/g, ''))}
                    error={errors.username} />

                <div>
                    <TextField label="Password Kuat" type="password" autoComplete="new-password" value={data.password}
                        onChange={(e) => setData('password', e.target.value)} error={errors.password} />
                    <ul className="mt-2 space-y-1">
                        {RULES.map(([label, test]) => {
                            const ok = test(data.password);
                            return (
                                <li key={label}
                                    className={`flex items-center gap-2 text-xs font-medium ${ok ? 'text-emerald-600' : 'text-ink/60'}`}>
                                    <Mark ok={ok} /> {label}
                                </li>
                            );
                        })}
                    </ul>
                </div>

                <div>
                    <TextField label="Konfirmasi Password" type="password" autoComplete="new-password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)} />
                    {mismatch && <p className="mt-1 text-sm font-medium text-red-600">Password belum sama.</p>}
                    {match && <p className="mt-1 text-sm font-medium text-emerald-600">Password sudah sama.</p>}
                </div>

                <button disabled={processing}
                    className="w-full rounded-xl bg-brand py-3 font-semibold text-white shadow-lg shadow-brand/30 transition hover:-translate-y-0.5 hover:shadow-xl disabled:opacity-50">
                    {processing ? 'Memproses...' : 'Daftar'}
                </button>
            </form>

            <p className="mt-5 text-center text-sm text-ink/80">
                Sudah punya akun?{' '}
                <Link href="/login" className="font-bold text-brand hover:underline">Masuk</Link>
            </p>
        </AuthLayout>
    );
}