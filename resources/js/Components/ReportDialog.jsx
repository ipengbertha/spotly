import { useForm } from '@inertiajs/react';

export function FlagIcon({ filled = false, className = 'h-5 w-5' }) {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true" className={className}
            fill={filled ? 'currentColor' : 'none'} stroke="currentColor"
            strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1zM4 22v-7" />
        </svg>
    );
}

// Dialog pilihan alasan laporan (hanya untuk siswa, pada postingan orang lain).
// Dibuka dari menu titik tiga (PostMenu), jadi buka/tutupnya dikendalikan dari luar.
export default function ReportDialog({ postId, reasons = [], open, onClose }) {
    const form = useForm({ reason: '', note: '' });

    if (!open) return null;

    const close = () => {
        onClose();
        form.reset();
        form.clearErrors();
    };

    const submit = (e) => {
        e.preventDefault();
        form.post(`/posts/${postId}/report`, {
            preserveScroll: true,
            onSuccess: close,
        });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 p-4" onClick={close}>
            <form onSubmit={submit} onClick={(e) => e.stopPropagation()}
                className="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-3xl bg-white p-6 shadow-xl">
                <h2 className="text-lg text-ink">Laporkan postingan</h2>
                <p className="mt-1 text-sm text-ink/60">
                    Admin akan meninjau laporanmu. Identitasmu tidak ditampilkan kepada penulis.
                </p>

                <fieldset className="mt-4 space-y-2">
                    <legend className="mb-1 text-sm font-semibold text-ink">Alasan</legend>
                    {reasons.map((r) => (
                        <label key={r.key}
                            className={`flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 text-sm ${
                                form.data.reason === r.key ? 'border-brand bg-brand/5' : 'border-ink/15'}`}>
                            <input type="radio" name="reason" value={r.key}
                                checked={form.data.reason === r.key}
                                onChange={() => form.setData('reason', r.key)} />
                            {r.label}
                        </label>
                    ))}
                </fieldset>
                {form.errors.reason && <p className="mt-1 text-sm text-brand">{form.errors.reason}</p>}

                <label htmlFor="report-note" className="mb-1 mt-4 block text-sm font-semibold text-ink">
                    Catatan <span className="font-normal text-ink/50">(opsional)</span>
                </label>
                <textarea id="report-note" rows={3} maxLength={300}
                    value={form.data.note}
                    onChange={(e) => form.setData('note', e.target.value)}
                    className="w-full rounded-xl border border-ink/15 px-3 py-2 text-sm outline-none focus:border-grape focus:ring-2 focus:ring-grape/20" />
                {form.errors.note && <p className="mt-1 text-sm text-brand">{form.errors.note}</p>}

                <div className="mt-6 flex justify-end gap-2">
                    <button type="button" onClick={close}
                        className="rounded-full border border-ink/20 px-4 py-2 text-sm font-semibold text-ink hover:bg-ink/5">
                        Batal
                    </button>
                    <button type="submit" disabled={form.processing}
                        className="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-60">
                        Kirim laporan
                    </button>
                </div>
            </form>
        </div>
    );
}