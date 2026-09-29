import { Head, Link, useForm } from '@inertiajs/react';
import AuthLayout from '../../Layouts/AuthLayout';
import TextField from '../../Components/TextField';

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/register');
    };

    return (
        <AuthLayout title="Daftar">
            <Head title="Daftar" />
            <form onSubmit={submit} className="space-y-4">
                <TextField label="Nama" value={data.name}
                    onChange={(e) => setData('name', e.target.value)} error={errors.name} />
                <TextField label="Email" type="email" value={data.email}
                    onChange={(e) => setData('email', e.target.value)} error={errors.email} />
                <TextField label="Password (minimal 8 karakter)" type="password" value={data.password}
                    onChange={(e) => setData('password', e.target.value)} error={errors.password} />
                <TextField label="Ulangi Password" type="password" value={data.password_confirmation}
                    onChange={(e) => setData('password_confirmation', e.target.value)} />
                <button disabled={processing}
                    className="w-full rounded-lg bg-slate-900 py-2 font-semibold text-white hover:bg-slate-700 disabled:opacity-50">
                    Daftar
                </button>
            </form>
            <p className="mt-4 text-center text-sm text-slate-600">
                Sudah punya akun?{' '}
                <Link href="/login" className="font-semibold text-amber-600">Masuk</Link>
            </p>
        </AuthLayout>
    );
}