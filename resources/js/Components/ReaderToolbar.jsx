import { useState } from 'react';

const KEY = 'spotly.reader';

// Empat ukuran teks isi artikel. Indeks 1 (text-base) adalah ukuran awal.
export const SIZE_CLASSES = ['text-sm', 'text-base', 'text-lg', 'text-xl'];
const DEFAULTS = { size: 1, dark: false };

function load() {
    try {
        const v = JSON.parse(localStorage.getItem(KEY) ?? 'null');
        if (!v) return DEFAULTS;
        const size = Number.isInteger(v.size) && v.size >= 0 && v.size < SIZE_CLASSES.length ? v.size : DEFAULTS.size;
        return { size, dark: v.dark === true };
    } catch {
        return DEFAULTS;
    }
}

// Pilihan pembaca (ukuran teks dan mode gelap), diingat di browser ini saja.
export function useReaderPrefs() {
    const [prefs, setPrefs] = useState(load);

    const update = (patch) => {
        const next = { ...prefs, ...patch };
        setPrefs(next);
        try {
            localStorage.setItem(KEY, JSON.stringify(next));
        } catch {
            // penyimpanan penuh atau diblokir: pilihan tetap berlaku sampai halaman ditutup
        }
    };

    return [prefs, update];
}

function MoonIcon() {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true" className="h-4 w-4" fill="none" stroke="currentColor"
            strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z" />
        </svg>
    );
}

function SunIcon() {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true" className="h-4 w-4" fill="none" stroke="currentColor"
            strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <circle cx="12" cy="12" r="4" />
            <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
        </svg>
    );
}

// Panel kecil di atas isi artikel: ukuran teks (A− / A+) dan saklar mode gelap.
export default function ReaderToolbar({ prefs, onChange }) {
    const { size, dark } = prefs;
    const max = SIZE_CLASSES.length - 1;

    const ring = dark ? 'ring-white/25 text-white/80' : 'ring-ink/15 text-ink/70';
    const hover = dark ? 'hover:bg-white/10' : 'hover:bg-peach/40';
    const btn = `px-3 py-1.5 text-sm font-bold transition disabled:cursor-not-allowed disabled:opacity-35 ${hover}`;

    return (
        <div className="mt-5 flex flex-wrap items-center gap-2" aria-label="Pengaturan membaca" role="group">
            <div className={`inline-flex items-center overflow-hidden rounded-full ring-1 ${ring}`}>
                <button type="button" className={btn} disabled={size === 0}
                    onClick={() => onChange({ size: size - 1 })} aria-label="Perkecil teks">
                    A<span aria-hidden="true">−</span>
                </button>
                <span className={`h-5 w-px ${dark ? 'bg-white/25' : 'bg-ink/15'}`} aria-hidden="true" />
                <button type="button" className={btn} disabled={size === max}
                    onClick={() => onChange({ size: size + 1 })} aria-label="Perbesar teks">
                    A<span aria-hidden="true">+</span>
                </button>
            </div>

            <button type="button" role="switch" aria-checked={dark}
                onClick={() => onChange({ dark: !dark })}
                className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-semibold ring-1 transition ${ring} ${hover}`}>
                {dark ? <SunIcon /> : <MoonIcon />}
                {dark ? 'Mode terang' : 'Mode gelap'}
            </button>
        </div>
    );
}
