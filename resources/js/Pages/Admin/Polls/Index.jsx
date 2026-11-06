import { useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

function Modal({ title, onClose, children }) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 p-4" onClick={onClose}>
            <div className="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-3xl bg-white p-6 shadow-xl"
                onClick={(e) => e.stopPropagation()}>
                <h2 className="mb-4 text-lg text-ink">{title}</h2>
                {children}
            </div>
        </div>
    );
}

const STATUS = {
    open:   { label: 'Berjalan', cls: 'bg-green-100 text-green-700' },
    ended:  { label: 'Berakhir', cls: 'bg-ink/10 text-ink/70' },
    closed: { label: 'Ditutup',  cls: 'bg-brand/10 text-brand' },
};

const MODES = [
    { value: true,  title: 'Suara bisa diganti', desc: 'Siswa boleh memilih ulang selama polling terbuka.' },
    { value: false, title: 'Suara final',        desc: 'Setelah memilih, siswa tidak bisa mengubah suaranya.' },
];

const inputCls =
    'w-full rounded-xl border border-ink/15 px-3 py-2 text-sm outline-none focus:border-grape focus:ring-2 focus:ring-grape/20';

export default function Index({ polls, hasOpen }) {
    const { errors } = usePage().props;
    const [dialog, setDialog] = useState(null); // { type: 'create' | 'extend', poll? }
    const form = useForm({ question: '', allow_change: false, ends_at: '', options: ['', ''] });
    const extendForm = useForm({ ends_at: '' });
    const today = new Date().toLocaleDateString('en-CA'); // YYYY-MM-DD, jam lokal

    const close = () => setDialog(null);

    const openCreate = () => {
        form.reset();
        form.clearErrors();
        setDialog({ type: 'create' });
    };

    const openExtend = (poll) => {
        extendForm.clearErrors();
        extendForm.setData('ends_at', poll.ends_input);
        setDialog({ type: 'extend', poll });
    };

    const setOption = (i, value) =>
        form.setData('options', form.data.options.map((o, idx) => (idx === i ? value : o)));
    const addOption = () => form.setData('options', [...form.data.options, '']);
    const removeOption = (i) => form.setData('options', form.data.options.filter((_, idx) => idx !== i));

    const submitCreate = (e) => {
        e.preventDefault();
        form.post('/admin/polls', { preserveScroll: true, onSuccess: close });
    };

    const submitExtend = (e) => {
        e.preventDefault();
        extendForm.patch(`/admin/polls/${dialog.poll.id}/end`, { preserveScroll: true, onSuccess: close });
    };

    const closePoll = (p) => {
        if (!confirm(`Tutup polling "${p.question}"? Siswa tidak bisa memilih lagi.`)) return;
        router.post(`/admin/polls/${p.id}/close`, {}, { preserveScroll: true });
    };

    const removePoll = (p) => {
        if (!confirm(`Hapus polling "${p.question}" beserta semua suaranya? Ini tidak bisa dibatalkan.`)) return;
        router.delete(`/admin/polls/${p.id}`, { preserveScroll: true });
    };

    // Satu pesan untuk semua galat pilihan (options, options.0, ...)
    const optionError = Object.entries(form.errors)
        .find(([k]) => k === 'options' || k.startsWith('options.'))?.[1];

    return (
        <AdminLayout title="Polling">
            <Head title="Polling" />

            <div className="mb-5 flex flex-wrap items-stretch gap-3">
                <div className="flex min-w-0 flex-1 items-start gap-3 rounded-2xl bg-grape/10 px-4 py-3 text-sm text-ink ring-1 ring-grape/20">
                    <span className="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-grape text-xs font-bold text-white">
                        i
                    </span>
                    <p>
                        Polling tampil di <b className="text-grape">Beranda siswa</b>. Hanya{' '}
                        <b className="text-grape">satu polling</b> yang bisa berjalan dalam satu waktu. Setelah dibuat,
                        hanya <b className="text-grape">tanggal berakhir</b> yang bisa diubah. Kalau ada salah ketik,
                        hapus lalu buat ulang.
                    </p>
                </div>
                <button onClick={openCreate} disabled={hasOpen}
                    title={hasOpen ? 'Tutup polling yang berjalan dulu' : ''}
                    className="rounded-2xl bg-brand px-5 py-3 text-sm font-semibold text-white shadow-sm hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40">
                    + Buat polling
                </button>
            </div>

            {errors.poll && !dialog && (
                <p className="mb-4 rounded-2xl bg-brand/10 px-4 py-3 text-sm font-medium text-brand">{errors.poll}</p>
            )}

            {polls.length === 0 ? (
                <div className="rounded-2xl border border-dashed border-peach bg-white/60 px-6 py-16 text-center">
                    <h2 className="text-xl text-ink">Belum ada polling</h2>
                    <p className="mx-auto mt-2 max-w-sm text-sm text-ink/60">
                        Buat polling pertama untuk menanyakan pendapat siswa.
                    </p>
                </div>
            ) : (
                <div className="flex flex-col gap-4">
                    {polls.map((p) => {
                        const st = STATUS[p.status];
                        return (
                            <div key={p.id} className="rounded-3xl bg-white p-5 shadow-md ring-1 ring-ink/5">
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className={`rounded-full px-3 py-0.5 text-xs font-bold ${st.cls}`}>{st.label}</span>
                                    <span className="rounded-full bg-ink/10 px-3 py-0.5 text-xs font-semibold text-ink/70">
                                        {p.allow_change ? 'Suara bisa diganti' : 'Suara final'}
                                    </span>
                                    <span className="text-xs text-ink/50">Berakhir {p.ends_label}</span>
                                </div>

                                <h2 className="mt-3 text-lg text-ink">{p.question}</h2>

                                <div className="mt-3 flex flex-col gap-2">
                                    {p.options.map((o) => {
                                        const percent = p.total_votes > 0 ? Math.round((o.votes / p.total_votes) * 100) : 0;
                                        return (
                                            <div key={o.id} className="relative overflow-hidden rounded-xl border border-ink/10 px-4 py-2 text-sm">
                                                <span aria-hidden="true" className="absolute inset-y-0 left-0 bg-peach/50"
                                                    style={{ width: `${percent}%` }} />
                                                <span className="relative flex justify-between gap-3">
                                                    <span className="font-semibold text-ink">{o.label}</span>
                                                    <span className="shrink-0 text-ink/70">{percent}% ({o.votes})</span>
                                                </span>
                                            </div>
                                        );
                                    })}
                                </div>

                                <div className="mt-4 flex flex-wrap items-center justify-between gap-2">
                                    <p className="text-xs text-ink/50">{p.total_votes} suara</p>
                                    <div className="flex flex-wrap gap-2">
                                        {p.status === 'open' && (
                                            <>
                                                <button onClick={() => openExtend(p)}
                                                    className="rounded-full bg-grape/10 px-3 py-1 text-xs font-semibold text-grape hover:bg-grape/20">
                                                    Ubah tanggal
                                                </button>
                                                <button onClick={() => closePoll(p)}
                                                    className="rounded-full border border-ink/20 px-3 py-1 text-xs font-semibold text-ink hover:bg-ink/5">
                                                    Tutup
                                                </button>
                                            </>
                                        )}
                                        <button onClick={() => removePoll(p)}
                                            className="rounded-full border border-brand/40 px-3 py-1 text-xs font-semibold text-brand hover:bg-brand/10">
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}

            {dialog?.type === 'create' && (
                <Modal title="Buat polling" onClose={close}>
                    <form onSubmit={submitCreate}>
                        {form.errors.poll && (
                            <p className="mb-3 rounded-2xl bg-brand/10 px-3 py-2 text-sm font-medium text-brand">
                                {form.errors.poll}
                            </p>
                        )}

                        <label className="mb-1 block text-sm font-semibold text-ink" htmlFor="poll-q">Pertanyaan</label>
                        <input id="poll-q" type="text" maxLength={200} autoFocus value={form.data.question}
                            onChange={(e) => form.setData('question', e.target.value)} className={inputCls} />
                        {form.errors.question && <p className="mt-1 text-sm text-brand">{form.errors.question}</p>}

                        <p className="mb-1 mt-4 text-sm font-semibold text-ink">Pilihan jawaban (2 sampai 6)</p>
                        <div className="flex flex-col gap-2">
                            {form.data.options.map((o, i) => (
                                <div key={i} className="flex gap-2">
                                    <input type="text" maxLength={100} value={o} placeholder={`Pilihan ${i + 1}`}
                                        onChange={(e) => setOption(i, e.target.value)} className={inputCls} />
                                    {form.data.options.length > 2 && (
                                        <button type="button" onClick={() => removeOption(i)} aria-label="Hapus pilihan"
                                            className="shrink-0 rounded-xl border border-ink/15 px-3 text-ink/60 hover:bg-ink/5">
                                            ×
                                        </button>
                                    )}
                                </div>
                            ))}
                        </div>
                        {form.data.options.length < 6 && (
                            <button type="button" onClick={addOption}
                                className="mt-2 text-sm font-semibold text-grape hover:underline">
                                + Tambah pilihan
                            </button>
                        )}
                        {optionError && <p className="mt-1 text-sm text-brand">{optionError}</p>}

                        <p className="mb-1 mt-4 text-sm font-semibold text-ink">Aturan suara</p>
                        <div className="flex flex-col gap-2">
                            {MODES.map((m) => (
                                <label key={String(m.value)}
                                    className={`flex cursor-pointer items-start gap-3 rounded-xl border-2 px-3 py-2 ${
                                        form.data.allow_change === m.value ? 'border-grape bg-grape/5' : 'border-ink/10'}`}>
                                    <input type="radio" name="allow_change" className="mt-1"
                                        checked={form.data.allow_change === m.value}
                                        onChange={() => form.setData('allow_change', m.value)} />
                                    <span>
                                        <span className="block text-sm font-semibold text-ink">{m.title}</span>
                                        <span className="block text-xs text-ink/60">{m.desc}</span>
                                    </span>
                                </label>
                            ))}
                        </div>
                        {form.errors.allow_change && <p className="mt-1 text-sm text-brand">{form.errors.allow_change}</p>}

                        <label className="mb-1 mt-4 block text-sm font-semibold text-ink" htmlFor="poll-end">
                            Berakhir pada
                        </label>
                        <input id="poll-end" type="date" min={today} value={form.data.ends_at}
                            onChange={(e) => form.setData('ends_at', e.target.value)} className={inputCls} />
                        {form.errors.ends_at && <p className="mt-1 text-sm text-brand">{form.errors.ends_at}</p>}

                        <div className="mt-6 flex justify-end gap-2">
                            <button type="button" onClick={close}
                                className="rounded-full border border-ink/20 px-4 py-2 text-sm font-semibold text-ink hover:bg-ink/5">
                                Batal
                            </button>
                            <button type="submit" disabled={form.processing}
                                className="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-60">
                                Buat polling
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {dialog?.type === 'extend' && (
                <Modal title="Ubah tanggal berakhir" onClose={close}>
                    <form onSubmit={submitExtend}>
                        {extendForm.errors.poll && (
                            <p className="mb-3 rounded-2xl bg-brand/10 px-3 py-2 text-sm font-medium text-brand">
                                {extendForm.errors.poll}
                            </p>
                        )}
                        <label className="mb-1 block text-sm font-semibold text-ink" htmlFor="poll-ext">
                            Berakhir pada
                        </label>
                        <input id="poll-ext" type="date" min={today} value={extendForm.data.ends_at}
                            onChange={(e) => extendForm.setData('ends_at', e.target.value)} className={inputCls} />
                        {extendForm.errors.ends_at && <p className="mt-1 text-sm text-brand">{extendForm.errors.ends_at}</p>}
                        <div className="mt-6 flex justify-end gap-2">
                            <button type="button" onClick={close}
                                className="rounded-full border border-ink/20 px-4 py-2 text-sm font-semibold text-ink hover:bg-ink/5">
                                Batal
                            </button>
                            <button type="submit" disabled={extendForm.processing}
                                className="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-60">
                                Simpan
                            </button>
                        </div>
                    </form>
                </Modal>
            )}
        </AdminLayout>
    );
}