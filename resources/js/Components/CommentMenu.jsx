import { useEffect, useRef, useState } from 'react';
import { router, useForm } from '@inertiajs/react';

function ThumbIcon({ filled = false, className = 'h-4 w-4' }) {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true" className={className}
            fill={filled ? 'currentColor' : 'none'} stroke="currentColor"
            strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M7 10v12" />
            <path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z" />
        </svg>
    );
}

// Jempol di komentar.
// - Siswa (komentar orang lain): bisa menekan (beri / cabut)
// - Tamu: menekan -> diarahkan ke login
// - Admin dan penulis komentar sendiri: hanya melihat jumlahnya
export function CommentLikeButton({ comment, viewer }) {
    const [busy, setBusy] = useState(false);
    const interactive = comment.can_like || viewer === 'guest';

    if (!interactive) {
        if (comment.likes_count === 0) return null;
        return (
            <span title="Jumlah jempol" className="inline-flex items-center gap-1 text-ink/60">
                <ThumbIcon /> {comment.likes_count}
            </span>
        );
    }

    const press = () => {
        if (busy) return;
        if (viewer === 'guest') {
            router.visit('/login');
            return;
        }
        router.post(`/comments/${comment.id}/like`, {}, {
            preserveScroll: true,
            preserveState: true,
            onStart: () => setBusy(true),
            onFinish: () => setBusy(false),
        });
    };

    return (
        <button type="button" onClick={press} disabled={busy}
            aria-pressed={comment.liked}
            aria-label={comment.liked ? 'Cabut jempol' : 'Beri jempol'}
            className={`inline-flex items-center gap-1 transition disabled:opacity-60 ${
                comment.liked ? 'text-brand' : 'hover:text-grape'}`}>
            <ThumbIcon filled={comment.liked} /> {comment.likes_count > 0 ? comment.likes_count : 'Suka'}
        </button>
    );
}

// Dialog pilihan alasan untuk melaporkan satu komentar
function ReportCommentDialog({ commentId, reasons, onClose }) {
    const form = useForm({ reason: '', note: '' });

    const close = () => {
        form.reset();
        form.clearErrors();
        onClose();
    };

    const submit = (e) => {
        e.preventDefault();
        form.post(`/comments/${commentId}/report`, { preserveScroll: true, onSuccess: close });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 p-4" onClick={close}>
            <form onSubmit={submit} onClick={(e) => e.stopPropagation()}
                className="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-3xl bg-white p-6 shadow-xl">
                <h2 className="text-lg text-ink">Laporkan komentar</h2>
                <p className="mt-1 text-sm text-ink/60">
                    Admin akan meninjau laporanmu. Identitasmu tidak ditampilkan kepada penulis komentar.
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

                <label htmlFor={`report-comment-note-${commentId}`} className="mb-1 mt-4 block text-sm font-semibold text-ink">
                    Catatan <span className="font-normal text-ink/50">(opsional)</span>
                </label>
                <textarea id={`report-comment-note-${commentId}`} rows={3} maxLength={300}
                    value={form.data.note}
                    onChange={(e) => form.setData('note', e.target.value)}
                    className="w-full rounded-xl border border-ink/15 px-3 py-2 text-sm outline-none focus:border-grape focus:ring-2 focus:ring-grape/20" />
                {form.errors.note && <p className="mt-1 text-sm text-brand">{form.errors.note}</p>}

                <div className="mt-6 flex justify-end gap-2">
                    <button type="button" onClick={close}
                        className="rounded-full border border-ink/20 px-4 py-2 text-sm font-semibold text-ink hover:bg-ink/5">
                        Batal
                    </button>
                    <button type="submit" disabled={form.processing || !form.data.reason}
                        className="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-60">
                        Kirim laporan
                    </button>
                </div>
            </form>
        </div>
    );
}

// Menu titik tiga di pojok kanan komentar.
// - Komentar sendiri (siswa): Edit (selama belum 24 jam) dan Hapus
// - Komentar orang lain (siswa): Laporkan
// - Admin: Hapus
// - Tamu: tidak ada menu
export default function CommentMenu({ comment, reasons = [], onEdit, onDelete }) {
    const [open, setOpen] = useState(false);
    const [reporting, setReporting] = useState(false);
    const box = useRef(null);

    useEffect(() => {
        if (!open) return;
        const onDown = (e) => { if (box.current && !box.current.contains(e.target)) setOpen(false); };
        const onKey = (e) => { if (e.key === 'Escape') setOpen(false); };
        document.addEventListener('mousedown', onDown);
        document.addEventListener('keydown', onKey);
        return () => {
            document.removeEventListener('mousedown', onDown);
            document.removeEventListener('keydown', onKey);
        };
    }, [open]);

    const hasItems = comment.can_edit || comment.delete_url || comment.can_report || comment.reported;
    if (!hasItems) return null;

    const item = 'block w-full px-4 py-2 text-left text-sm font-semibold hover:bg-ink/5 disabled:cursor-default disabled:text-ink/40 disabled:hover:bg-transparent';

    return (
        <div ref={box} className="relative h-fit shrink-0">
            <button type="button" onClick={() => setOpen((v) => !v)}
                aria-haspopup="menu" aria-expanded={open} aria-label="Menu komentar"
                className="flex h-8 w-8 items-center justify-center rounded-full text-ink/50 hover:bg-ink/5 hover:text-ink">
                <svg viewBox="0 0 24 24" aria-hidden="true" className="h-5 w-5" fill="currentColor">
                    <circle cx="12" cy="5" r="1.8" /><circle cx="12" cy="12" r="1.8" /><circle cx="12" cy="19" r="1.8" />
                </svg>
            </button>

            {open && (
                <div role="menu"
                    className="absolute right-0 top-full z-20 mt-1 w-52 overflow-hidden rounded-2xl bg-white py-1 shadow-lg ring-1 ring-ink/10">
                    {comment.can_edit && (
                        <button type="button" role="menuitem" className={`${item} text-ink`}
                            onClick={() => { setOpen(false); onEdit(); }}>
                            Edit komentar
                        </button>
                    )}
                    {comment.can_report && (
                        <button type="button" role="menuitem" className={`${item} text-ink`}
                            onClick={() => { setOpen(false); setReporting(true); }}>
                            Laporkan komentar
                        </button>
                    )}
                    {comment.reported && (
                        <button type="button" role="menuitem" disabled className={item}>
                            Sudah dilaporkan
                        </button>
                    )}
                    {comment.delete_url && (
                        <button type="button" role="menuitem" className={`${item} text-brand`}
                            onClick={() => { setOpen(false); onDelete(comment); }}>
                            Hapus komentar
                        </button>
                    )}
                </div>
            )}

            {reporting && (
                <ReportCommentDialog commentId={comment.id} reasons={reasons} onClose={() => setReporting(false)} />
            )}
        </div>
    );
}
