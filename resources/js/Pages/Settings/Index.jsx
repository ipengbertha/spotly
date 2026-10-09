import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import UserLayout from '../../Layouts/UserLayout';

const INPUT =
    'w-full rounded-xl border border-ink/15 px-3 py-2 text-sm outline-none focus:border-grape focus:ring-2 focus:ring-grape/20';
const LABEL = 'mb-1 block text-sm font-semibold text-ink';

function Field({ label, error, children }) {
    return (
        <div>
            <label className={LABEL}>{label}</label>
            {children}
            {error && <p className="mt-1 text-sm text-brand">{error}</p>}
        </div>
    );
}

function Saved({ show }) {
    return show ? <span className="text-sm font-semibold text-grape">Tersimpan.</span> : null;
}

function ProfileTab({ profile }) {
    const form = useForm({
        name: profile.name,
        email: profile.email,
        bio: profile.bio ?? '',
        avatar: null,
        remove_avatar: false,
    });
    const [preview, setPreview] = useState(null);
    const [saved, setSaved] = useState(false);

    const shownAvatar = form.data.remove_avatar ? null : (preview ?? profile.avatar_url);
    const initial = (form.data.name || '?').trim().charAt(0).toUpperCase();

    const pick = (e) => {
        const file = e.target.files?.[0];
        if (!file) return;
        form.setData((d) => ({ ...d, avatar: file, remove_avatar: false }));
        setPreview(URL.createObjectURL(file));
    };

    const removePhoto = () => {
        form.setData((d) => ({ ...d, avatar: null, remove_avatar: true }));
        setPreview(null);
    };

    const submit = (e) => {
        e.preventDefault();
        setSaved(false);
        form.post('/settings/profile', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.setData((d) => ({ ...d, avatar: null, remove_avatar: false }));
                setPreview(null);
                setSaved(true);
            },
        });
    };

    return (
        <form onSubmit={submit} className="space-y-5">
            <div className="flex items-center gap-4">
                {shownAvatar ? (
                    <img src={shownAvatar} alt="" className="h-20 w-20 rounded-full object-cover ring-2 ring-grape/20" />
                ) : (
                    <span className="flex h-20 w-20 items-center justify-center rounded-full bg-grape text-3xl font-bold text-white">
                        {initial}
                    </span>
                )}
                <div>
                    <div className="flex flex-wrap gap-2">
                        <label className="cursor-pointer rounded-full bg-grape/10 px-4 py-2 text-sm font-semibold text-grape hover:bg-grape/20">
                            Ganti foto
                            <input type="file" accept="image/png,image/jpeg,image/webp" onChange={pick} className="hidden" />
                        </label>
                        {shownAvatar && (
                            <button type="button" onClick={removePhoto}
                                className="rounded-full border border-ink/20 px-4 py-2 text-sm font-semibold text-ink hover:bg-ink/5">
                                Hapus foto
                            </button>
                        )}
                    </div>
                    <p className="mt-1.5 text-xs text-ink/50">JPG, PNG, atau WEBP. Maksimal 2 MB.</p>
                    {form.errors.avatar && <p className="mt-1 text-sm text-brand">{form.errors.avatar}</p>}
                </div>
            </div>

            <Field label="Nama" error={form.errors.name}>
                <input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} className={INPUT} />
            </Field>

            <Field label="E-mail" error={form.errors.email}>
                <input type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} className={INPUT} />
            </Field>

            <div className="grid gap-5 sm:grid-cols-2">
                <Field label="Username">
                    <input value={profile.username ?? ''} disabled className={`${INPUT} bg-ink/5 text-ink/60`} />
                </Field>
                <Field label="Peran">
                    <input value={profile.role_label} disabled className={`${INPUT} bg-ink/5 text-ink/60`} />
                </Field>
            </div>

            <Field label="Bio singkat (opsional)" error={form.errors.bio}>
                <textarea rows={3} maxLength={200} value={form.data.bio}
                    onChange={(e) => form.setData('bio', e.target.value)} className={INPUT} />
                <p className="mt-1 text-right text-xs text-ink/40">{form.data.bio.length}/200</p>
            </Field>

            <div className="flex items-center gap-3">
                <button type="submit" disabled={form.processing}
                    className="rounded-full bg-brand px-5 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-60">
                    Simpan Perubahan
                </button>
                <Saved show={saved} />
            </div>
        </form>
    );
}

function PasswordInput({ value, onChange, placeholder, autoComplete }) {
    const [show, setShow] = useState(false);

    return (
        <div className="relative">
            <input type={show ? 'text' : 'password'} value={value} onChange={onChange}
                placeholder={placeholder} autoComplete={autoComplete}
                className={`${INPUT} pr-11`} />
            <button type="button" onClick={() => setShow(!show)}
                aria-label={show ? 'Sembunyikan password' : 'Tampilkan password'}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-ink/50 hover:text-ink">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8"
                    strokeLinecap="round" strokeLinejoin="round" className="h-5 w-5" aria-hidden="true">
                    {show ? (
                        <>
                            <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19M14.12 14.12a3 3 0 11-4.24-4.24" />
                            <path d="M1 1l22 22" />
                        </>
                    ) : (
                        <>
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                            <circle cx="12" cy="12" r="3" />
                        </>
                    )}
                </svg>
            </button>
        </div>
    );
}

function SecurityTab() {
    const form = useForm({ current_password: '', password: '', password_confirmation: '' });
    const [saved, setSaved] = useState(false);
    const [localError, setLocalError] = useState('');

    const submit = (e) => {
        e.preventDefault();
        setSaved(false);
        setLocalError('');

        // Cek langsung di browser supaya pesannya pasti muncul
        if (form.data.password && form.data.password === form.data.current_password) {
            setLocalError('Password baru tidak boleh sama dengan password saat ini.');
            return;
        }

        form.put('/settings/password', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setSaved(true);
            },
        });
    };

    return (
        <form onSubmit={submit} className="max-w-md space-y-5">
            <Field label="Password saat ini" error={form.errors.current_password}>
                <PasswordInput autoComplete="current-password" placeholder="Masukkan password lama.."
                    value={form.data.current_password}
                    onChange={(e) => form.setData('current_password', e.target.value)} />
            </Field>
            <div className="-mt-3 text-right">
                <Link href="/forgot-password" className="text-sm font-semibold text-grape hover:underline">
                    Lupa password?
                </Link>
            </div>
            <Field label="Password baru" error={localError || form.errors.password}>
                <PasswordInput autoComplete="new-password" placeholder="Masukkan password baru.."
                    value={form.data.password}
                    onChange={(e) => { setLocalError(''); form.setData('password', e.target.value); }} />
            </Field>
            <Field label="Konfirmasi password baru">
                <PasswordInput autoComplete="new-password" placeholder="Ulangi password baru.."
                    value={form.data.password_confirmation}
                    onChange={(e) => form.setData('password_confirmation', e.target.value)} />
            </Field>

            <p className="rounded-2xl bg-grape/10 px-4 py-3 text-sm text-ink ring-1 ring-grape/20">
                Gunakan password yang kuat (minimal 8 karakter, ada huruf besar, angka, dan simbol)
                dan jangan membagikannya kepada orang lain.
            </p>

            <div className="flex items-center gap-3">
                <button type="submit" disabled={form.processing}
                    className="rounded-full bg-brand px-5 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-60">
                    Ubah Password
                </button>
                <Saved show={saved} />
            </div>
        </form>
    );
}

function NotificationsTab({ isAdmin, preferences }) {
    const form = useForm({ ...preferences });
    const [saved, setSaved] = useState(false);

    const groups = isAdmin
        ? [['Moderasi', [['post_submitted', 'Ada postingan baru yang perlu ditinjau']]]]
        : [['Postingan', [
            ['post_approved', 'Postingan disetujui'],
            ['post_rejected', 'Postingan ditolak'],
        ]]];

    const submit = (e) => {
        e.preventDefault();
        setSaved(false);
        form.put('/settings/notifications', {
            preserveScroll: true,
            onSuccess: () => setSaved(true),
        });
    };

    return (
        <form onSubmit={submit} className="max-w-md space-y-6">
            {groups.map(([title, items]) => (
                <div key={title}>
                    <p className="mb-2 text-sm font-bold text-ink">{title}</p>
                    <div className="space-y-2">
                        {items.map(([key, label]) => (
                            <label key={key}
                                className="flex cursor-pointer items-center gap-3 rounded-2xl bg-cream/60 px-4 py-3 text-sm text-ink ring-1 ring-ink/5">
                                <input type="checkbox" checked={!!form.data[key]}
                                    onChange={(e) => form.setData(key, e.target.checked)}
                                    className="h-4 w-4 accent-brand" />
                                {label}
                            </label>
                        ))}
                    </div>
                </div>
            ))}

            <p className="text-xs text-ink/50">
                Pilihan lain (komentar, interaksi, masa tayang berakhir) akan muncul bersama fiturnya.
            </p>

            <div className="flex items-center gap-3">
                <button type="submit" disabled={form.processing}
                    className="rounded-full bg-brand px-5 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-60">
                    Simpan Perubahan
                </button>
                <Saved show={saved} />
            </div>
        </form>
    );
}

function MadingTab({ mading }) {
    const form = useForm({
        mading_name: mading.name,
        mading_description: mading.description,
        posts_per_page: mading.posts_per_page,
    });
    const [saved, setSaved] = useState(false);

    const submit = (e) => {
        e.preventDefault();
        setSaved(false);
        form.put('/settings/mading', {
            preserveScroll: true,
            onSuccess: () => setSaved(true),
        });
    };

    return (
        <form onSubmit={submit} className="max-w-md space-y-5">
            <Field label="Nama mading" error={form.errors.mading_name}>
                <input value={form.data.mading_name} maxLength={30}
                    onChange={(e) => form.setData('mading_name', e.target.value)} className={INPUT} />
            </Field>
            <Field label="Deskripsi" error={form.errors.mading_description}>
                <input value={form.data.mading_description} maxLength={80}
                    onChange={(e) => form.setData('mading_description', e.target.value)} className={INPUT} />
            </Field>
            <Field label="Postingan per halaman (Mading)" error={form.errors.posts_per_page}>
                <select value={form.data.posts_per_page}
                    onChange={(e) => form.setData('posts_per_page', Number(e.target.value))} className={INPUT}>
                    {[6, 12, 18, 24].map((n) => <option key={n} value={n}>{n}</option>)}
                </select>
            </Field>
            <p className="text-xs text-ink/50">
                Nama dan deskripsi tampil di halaman depan (landing page).
            </p>
            <div className="flex items-center gap-3">
                <button type="submit" disabled={form.processing}
                    className="rounded-full bg-brand px-5 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-60">
                    Simpan Perubahan
                </button>
                <Saved show={saved} />
            </div>
        </form>
    );
}

export default function Index({ isAdmin, profile, preferences, mading }) {
    const Layout = isAdmin ? AdminLayout : UserLayout;
    const [tab, setTab] = useState('profil');

    const tabs = [
        ['profil', 'Profil & Akun'],
        ['keamanan', 'Keamanan'],
        ['notifikasi', 'Notifikasi'],
        ...(isAdmin ? [['mading', 'Mading']] : []),
    ];

    return (
        <Layout title="Pengaturan">
            <Head title="Pengaturan" />

            <div className="mb-5 flex flex-wrap gap-2">
                {tabs.map(([key, label]) => (
                    <button key={key} onClick={() => setTab(key)}
                        className={`rounded-full px-4 py-2 text-sm font-semibold transition ${
                            tab === key ? 'bg-ink text-white' : 'bg-white text-ink/70 ring-1 ring-ink/10 hover:bg-peach/40'}`}>
                        {label}
                    </button>
                ))}
            </div>

            <div className="rounded-3xl bg-white p-6 shadow-md ring-1 ring-ink/5">
                {tab === 'profil' && <ProfileTab profile={profile} />}
                {tab === 'keamanan' && <SecurityTab />}
                {tab === 'notifikasi' && <NotificationsTab isAdmin={isAdmin} preferences={preferences} />}
                {tab === 'mading' && isAdmin && <MadingTab mading={mading} />}
            </div>
        </Layout>
    );
}