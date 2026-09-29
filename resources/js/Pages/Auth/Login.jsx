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
                <TextField label="Email" type="email" value={data.email}
                    onChange={(e) => setData('email', e.target.value)} error={errors.email} />
                <TextField label="Password" type="password" value={data.password}
                    onChange={(e) => setData('password', e.target.value)} error={errors.password} />
                <label className="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" checked={data.remember}
                        onChange={(e) => setData('remember', e.target.checked)} />
                    Ingat saya
                </label>
                <button disabled={processing}
                    className="w-full rounded-lg bg-slate-900 py-2 font-semibold text-white hover:bg-slate-700 disabled:opacity-50">
                    Masuk
                </button>
            </form>
            <p className="mt-4 text-center text-sm text-slate-600">
                Belum punya akun?{' '}
                <Link href="/register" className="font-semibold text-amber-600">Daftar</Link>
            </p>
        </AuthLayout>
    );
}