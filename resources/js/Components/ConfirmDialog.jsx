// Teks peringatan disimpan di satu tempat supaya mudah diubah
export const SUBMIT_WARNING =
    'Setelah dikirim, postingan tidak bisa diedit selama menunggu review Admin. ' +
    'Jika Admin menolak, kamu bisa memperbaikinya lalu mengirim ulang. ' +
    'Simpan sebagai draft jika masih ingin mengubah.';

export default function ConfirmDialog({
    open, title, message, confirmLabel = 'Ya, lanjutkan', onConfirm, onCancel,
}) {
    if (!open) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-ink/60 p-4 backdrop-blur-sm"
            onClick={onCancel}>
            <div role="alertdialog" aria-modal="true"
                onClick={(e) => e.stopPropagation()}
                className="w-full max-w-md rounded-3xl border-2 border-peach bg-white p-6 shadow-2xl">
                <div className="flex items-start gap-3">
                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-peach text-lg font-bold text-brand">!</span>
                    <div>
                        <h3 className="text-lg font-bold text-ink">{title}</h3>
                        <p className="mt-1 text-sm text-ink/80">{message}</p>
                    </div>
                </div>
                <div className="mt-6 flex justify-end gap-2">
                    <button onClick={onCancel}
                        className="rounded-xl bg-white px-4 py-2 font-semibold text-ink ring-2 ring-peach transition hover:bg-peach/40">
                        Batal
                    </button>
                    <button onClick={onConfirm}
                        className="rounded-xl bg-brand px-4 py-2 font-semibold text-white shadow-md transition hover:-translate-y-0.5 hover:shadow-lg">
                        {confirmLabel}
                    </button>
                </div>
            </div>
        </div>
    );
}