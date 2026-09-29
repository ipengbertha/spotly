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
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            onClick={onCancel}>
            <div role="alertdialog" aria-modal="true"
                onClick={(e) => e.stopPropagation()}
                className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl ring-1 ring-red-200">
                <div className="flex items-start gap-3">
                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-100 font-bold text-red-600">!</span>
                    <div>
                        <h3 className="text-lg font-bold text-slate-900">{title}</h3>
                        <p className="mt-1 text-sm text-slate-600">{message}</p>
                    </div>
                </div>
                <div className="mt-5 flex justify-end gap-2">
                    <button onClick={onCancel}
                        className="rounded-lg bg-white px-4 py-2 font-semibold ring-1 ring-slate-300">Batal</button>
                    <button onClick={onConfirm}
                        className="rounded-lg bg-red-600 px-4 py-2 font-semibold text-white hover:bg-red-700">
                        {confirmLabel}
                    </button>
                </div>
            </div>
        </div>
    );
}