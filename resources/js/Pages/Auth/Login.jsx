import { Head, Link, useForm } from '@inertiajs/react';
import AuthLayout from '../../Layouts/AuthLayout';
import TextField from '../../Components/TextField';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <AuthLayout title="Masuk">
            <Head title="Masuk" />
            <form onSubmit={submit} className="space-y-4">
                <TextField label="Email" type="email" autoComplete="email" value={data.email}
                    onChange={(e) => setData('email', e.target.value)} error={errors.email} />
                <TextField label="Password" type="password" autoComplete="current-password" value={data.password}
                    onChange={(e) => setData('password', e.target.value)} error={errors.password} />

                <div className="flex items-center justify-between">
                    <label className="flex items-center gap-2 text-sm text-ink/80">
                        <input type="checkbox" checked={data.remember} className="accent-brand"
                            onChange={(e) => setData('remember', e.target.checked)} />
                        Ingat saya
                    </label>
                    <Link href="/forgot-password" className="text-sm font-bold text-brand hover:underline">
                        Lupa password?
                    </Link>
                </div>

                <button disabled={processing}
                    className="w-full rounded-xl bg-brand py-3 font-semibold text-white shadow-lg shadow-brand/30 transition hover:-translate-y-0.5 hover:shadow-xl disabled:opacity-50">
                    {processing ? 'Memproses...' : 'Masuk'}
                </button>
            </form>

            <p className="mt-5 text-center text-sm text-ink/80">
                Belum punya akun?{' '}
                <Link href="/register" className="font-bold text-brand hover:underline">Daftar</Link>
            </p>
        </AuthLayout>
    );
}