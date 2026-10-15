import { useState } from 'react';

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
        const ok = document.execCommand('copy');
        document.body.removeChild(area);
        return ok;
    } catch {
        return false;
    }
}

// Tombol bagikan: salin tautan dan kirim lewat WhatsApp.
export default function ShareButtons({ title, onDark = false }) {
    const [state, setState] = useState('idle'); // idle | copied | failed

    const copy = async () => {
        const ok = await copyText(pageUrl());
        setState(ok ? 'copied' : 'failed');
        setTimeout(() => setState('idle'), 2000);
    };

    const waHref = `https://wa.me/?text=${encodeURIComponent(`${title}\n${pageUrl()}`)}`;

    const base = 'inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-semibold ring-1 transition';
    const tone = onDark
        ? 'text-white/80 ring-white/25 hover:bg-white/10'
        : 'text-ink/70 ring-ink/15 hover:bg-peach/40';

    return (
        <div className="flex flex-wrap items-center gap-2">
            <button type="button" onClick={copy} className={`${base} ${tone}`}>
                {state === 'copied' ? <CheckIcon /> : <LinkIcon />}
                {state === 'copied' ? 'Tersalin' : state === 'failed' ? 'Gagal menyalin' : 'Salin tautan'}
            </button>
            <a href={waHref} target="_blank" rel="noopener noreferrer" className={`${base} ${tone}`}>
                <WhatsAppIcon />
                WhatsApp
            </a>
            {/* Pembaca layar mengumumkan hasil salin */}
            <span className="sr-only" role="status" aria-live="polite">
                {state === 'copied' ? 'Tautan tersalin' : state === 'failed' ? 'Tautan gagal disalin' : ''}
            </span>
        </div>
    );
}
