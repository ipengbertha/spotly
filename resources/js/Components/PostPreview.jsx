import { useEffect, useState } from 'react';
import { SIZE_CLASSES, useReaderPrefs } from './ReaderToolbar';

// Ukuran sengaja ditulis per mode, bukan lewat awalan sm: milik Tailwind.
// Awalan sm: membaca lebar jendela browser, jadi di laptop mode Mobile tetap
// memakai ukuran desktop. Angka di bawah disalin dari Pages/Posts/Show.jsx.
const LOOK = {
    desktop: { box: 'mx-auto w-full max-w-3xl', pad: 'p-8', h1: 'text-4xl' },
    mobile:  { box: 'w-full',                   pad: 'p-6', h1: 'text-3xl' },
};

function getCookie(name) {
    const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return m ? decodeURIComponent(m[1]) : '';
}

export default function PostPreview({ open, onClose, title, content, categoryNames = [], imageSrc = null }) {
    const [mode, setMode] = useState('desktop');
    const [html, setHtml] = useState('');
    const [state, setState] = useState('idle'); // idle | loading | ready | error
    // Ukuran teks dan mode gelap yang sama dengan pilihan pembaca di browser ini
    const [prefs] = useReaderPrefs();
    const dark = prefs.dark;

    // Ambil HTML yang sudah dibersihkan server setiap kali jendela dibuka
    useEffect(() => {
        if (!open) return;
        let cancelled = false;
        setState('loading');

        fetch('/user/posts/preview', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
            },
            body: JSON.stringify({ content }),
        })
            .then(async (res) => {
                const body = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error('gagal');
                if (!cancelled) {
                    setHtml(body.html ?? '');
                    setState('ready');
                }
            })
            .catch(() => { if (!cancelled) setState('error'); });

        return () => { cancelled = true; };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open]);

    // Tombol Esc menutup jendela
    useEffect(() => {
        if (!open) return;
        const onKey = (e) => { if (e.key === 'Escape') onClose(); };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [open, onClose]);

    if (!open) return null;

    const tab = (key, label) => (
        <button type="button" onClick={() => setMode(key)} aria-pressed={mode === key}
            className={`rounded-full px-4 py-1.5 text-sm font-semibold transition ${
                mode === key ? 'bg-ink text-white' : 'text-ink/70 hover:bg-ink/5'}`}>
            {label}
        </button>
    );

    const look = LOOK[mode];

    // Kartu artikel: struktur dan kelasnya mengikuti Pages/Posts/Show.jsx
    const article = (
        <article className={`${look.box} overflow-hidden rounded-3xl border-2 shadow-md transition-colors ${
            dark ? 'border-grape/50 bg-ink' : 'border-peach bg-white'}`}>
            {imageSrc && <img src={imageSrc} alt="" className="max-h-96 w-full object-cover" />}

            <div className={look.pad}>
                {categoryNames.length > 0 && (
                    <div className="flex flex-wrap gap-1.5">
                        {categoryNames.map((name) => (
                            <span key={name}
                                className="inline-block rounded-full bg-grape px-3 py-0.5 text-xs font-bold text-white">
                                {name}
                            </span>
                        ))}
                    </div>
                )}

                <h1 className={`mt-3 font-bold leading-tight ${look.h1} ${dark ? 'text-white' : 'text-ink'}`}>
                    {title.trim() !== '' ? title : 'Judul belum diisi'}
                </h1>

                {state === 'loading' && <p className="mt-6 text-sm opacity-50">Memuat pratinjau…</p>}
                {state === 'error' && (
                    <p className="mt-6 text-sm font-semibold text-brand">
                        Pratinjau gagal dimuat. Periksa koneksi, lalu coba buka lagi.
                    </p>
                )}
                {state === 'ready' && html === '' && (
                    <p className="mt-6 text-sm opacity-50">Isi belum ditulis.</p>
                )}
                {state === 'ready' && html !== '' && (
                    <div className={`post-body mt-6 max-w-prose wrap-break-word leading-relaxed ${
                        SIZE_CLASSES[prefs.size]} ${dark ? 'text-white/90' : 'text-ink/90'}`}
                        dangerouslySetInnerHTML={{ __html: html }} />
                )}
            </div>
        </article>
    );

    return (
        <div className="fixed inset-0 z-100 flex flex-col bg-ink/60 p-3 sm:p-6" onClick={onClose}>
            <div className="mx-auto flex min-h-0 w-full max-w-5xl flex-1 flex-col rounded-3xl bg-cream shadow-xl"
                onClick={(e) => e.stopPropagation()}>
                <div className="flex flex-wrap items-center gap-2 border-b border-ink/10 px-4 py-3">
                    <h2 className="mr-2 text-lg font-bold text-ink">Pratinjau</h2>
                    {tab('desktop', 'Desktop')}
                    {tab('mobile', 'Mobile')}
                    <button type="button" onClick={onClose}
                        className="ml-auto rounded-full border border-ink/20 px-4 py-1.5 text-sm font-semibold text-ink hover:bg-ink/5">
                        Tutup
                    </button>
                </div>

                <div className="min-h-0 flex-1 overflow-y-auto p-4">
                    {mode === 'desktop' ? (
                        article
                    ) : (
                        // Bingkai HP: lebar layar 375px, digulir di dalam bingkai seperti di HP sungguhan
                        <div className="mx-auto w-98.75 max-w-full rounded-[2.75rem] bg-ink p-2.5 shadow-xl">
                            <div className="overflow-hidden rounded-[2.2rem] bg-cream">
                                <div aria-hidden="true" className="flex h-9 items-center justify-center bg-cream">
                                    <div className="h-4 w-24 rounded-full bg-ink" />
                                </div>
                                <div className="h-[min(64vh,700px)] overflow-y-auto px-3 pb-6">
                                    {article}
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}