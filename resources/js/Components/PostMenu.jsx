import { useEffect, useRef, useState } from 'react';
import ReportDialog, { FlagIcon } from './ReportDialog';

function DotsIcon() {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true" className="h-5 w-5" fill="currentColor">
            <circle cx="5" cy="12" r="1.8" />
            <circle cx="12" cy="12" r="1.8" />
            <circle cx="19" cy="12" r="1.8" />
        </svg>
    );
}

function LinkIcon() {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true" className="h-4 w-4" fill="none" stroke="currentColor"
            strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M10 13a5 5 0 0 0 7.07 0l3-3a5 5 0 0 0-7.07-7.07l-1.5 1.5" />
            <path d="M14 11a5 5 0 0 0-7.07 0l-3 3a5 5 0 0 0 7.07 7.07l1.5-1.5" />
        </svg>
    );
}

function CheckIcon() {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true" className="h-4 w-4" fill="none" stroke="currentColor"
            strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
            <path d="M5 12l5 5 9-10" />
        </svg>
    );
}

function WhatsAppIcon() {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true" className="h-4 w-4" fill="none" stroke="currentColor"
            strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M3 21l1.65-4.9A8.5 8.5 0 1 1 8 19.4z" />
            <path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-.8.8c-.9-.4-1.6-1.1-2-2l.8-.8-1-2z" />
        </svg>
    );
}

const pageUrl = () => (typeof window === 'undefined' ? '' : window.location.href.split('#')[0]);

// Salin teks ke papan klip. Cadangan lama dipakai kalau Clipboard API tidak tersedia
// (misalnya situs dibuka lewat http biasa di jaringan lokal).
async function copyText(text) {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return true;
        }
    } catch {
        // lanjut ke cadangan
    }
    try {
        const area = document.createElement('textarea');
        area.value = text;
        area.setAttribute('readonly', '');
        area.style.position = 'fixed';
        area.style.opacity = '0';
        document.body.appendChild(area);
        area.select();
         const ok = /** @type {any} */ (document).execCommand('copy');
        document.body.removeChild(area);
        return ok;
    } catch {
        return false;
    }
}

const itemClass = 'flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm font-semibold text-ink transition hover:bg-peach/40';

// Menu titik tiga di pojok kanan atas artikel: Laporkan, Salin tautan, Bagikan.
// Laporkan hanya muncul untuk siswa pada postingan orang lain (post.can_report / post.reported).
export default function PostMenu({ post }) {
    const [open, setOpen] = useState(false);
    const [reportOpen, setReportOpen] = useState(false);
    const [copyState, setCopyState] = useState('idle'); // idle | copied | failed
    const box = useRef(null);

    // Tutup menu kalau klik di luar atau menekan Esc
    useEffect(() => {
        if (!open) return;
        const onDown = (e) => {
            if (box.current && !box.current.contains(e.target)) setOpen(false);
        };
        const onKey = (e) => {
            if (e.key === 'Escape') setOpen(false);
        };
        document.addEventListener('pointerdown', onDown);
        document.addEventListener('keydown', onKey);
        return () => {
            document.removeEventListener('pointerdown', onDown);
            document.removeEventListener('keydown', onKey);
        };
    }, [open]);

    const copyLink = async () => {
        const ok = await copyText(pageUrl());
        setCopyState(ok ? 'copied' : 'failed');
        setTimeout(() => {
            setCopyState('idle');
            setOpen(false);
        }, 1200);
    };

    const waHref = `https://wa.me/?text=${encodeURIComponent(`${post.title}\n${pageUrl()}`)}`;

    return (
        <div ref={box} className="relative">
            <button type="button" onClick={() => setOpen((v) => !v)}
                title="Menu postingan" aria-label="Menu postingan"
                aria-haspopup="menu" aria-expanded={open}
                className="flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-ink/60 shadow-sm ring-1 ring-ink/10 transition hover:bg-brand/10 hover:text-brand">
                <DotsIcon />
            </button>

            {open && (
                <div role="menu"
                    className="absolute right-0 top-full z-10 mt-2 w-56 overflow-hidden rounded-2xl border border-ink/10 bg-white py-1 shadow-lg">
                    {post.can_report && (
                        <button type="button" role="menuitem"
                            onClick={() => { setOpen(false); setReportOpen(true); }}
                            className={itemClass}>
                            <FlagIcon className="h-4 w-4" />
                            Laporkan
                        </button>
                    )}
                    {post.reported && (
                        <span role="menuitem" aria-disabled="true"
                            className="flex w-full items-center gap-2.5 px-4 py-2.5 text-sm font-semibold text-brand">
                            <FlagIcon filled className="h-4 w-4" />
                            Sudah dilaporkan
                        </span>
                    )}
                    <button type="button" role="menuitem" onClick={copyLink} className={itemClass}>
                        {copyState === 'copied' ? <CheckIcon /> : <LinkIcon />}
                        {copyState === 'copied' ? 'Tersalin' : copyState === 'failed' ? 'Gagal menyalin' : 'Salin tautan'}
                    </button>
                    <a role="menuitem" href={waHref} target="_blank" rel="noopener noreferrer"
                        onClick={() => setOpen(false)} className={itemClass}>
                        <WhatsAppIcon />
                        Bagikan ke WhatsApp
                    </a>
                    {/* Pembaca layar mengumumkan hasil salin */}
                    <span className="sr-only" role="status" aria-live="polite">
                        {copyState === 'copied' ? 'Tautan tersalin' : copyState === 'failed' ? 'Tautan gagal disalin' : ''}
                    </span>
                </div>
            )}

            <ReportDialog postId={post.id} reasons={post.report_reasons}
                open={reportOpen} onClose={() => setReportOpen(false)} />
        </div>
    );
}