import { useEffect, useState } from 'react';

const WIDTHS = {
    desktop: 'max-w-3xl',
    mobile: 'max-w-[375px]',
};

function getCookie(name) {
    const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return m ? decodeURIComponent(m[1]) : '';
}

export default function PostPreview({ open, onClose, title, content, categoryNames = [], imageSrc = null }) {
    const [mode, setMode] = useState('desktop');
    const [html, setHtml] = useState('');
    const [state, setState] = useState('idle'); // idle | loading | ready | error

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
        <button type="button" onClick={() => setMode(key)}
            className={`rounded-full px-4 py-1.5 text-sm font-semibold transition ${
                mode === key ? 'bg-ink text-white' : 'text-ink/70 hover:bg-ink/5'}`}>
            {label}
        </button>
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
                    <article className={`mx-auto w-full overflow-hidden rounded-3xl border-2 border-peach bg-white shadow-md transition-all ${WIDTHS[mode]}`}>
                        {imageSrc && <img src={imageSrc} alt="" className="max-h-96 w-full object-cover" />}

                        <div className="p-5 sm:p-8">
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

                            <h1 className="mt-3 text-2xl font-bold leading-tight text-ink sm:text-4xl">
                                {title.trim() !== '' ? title : 'Judul belum diisi'}
                            </h1>

                            {state === 'loading' && <p className="mt-6 text-sm text-ink/50">Memuat pratinjau…</p>}
                            {state === 'error' && (
                                <p className="mt-6 text-sm font-semibold text-brand">
                                    Pratinjau gagal dimuat. Periksa koneksi, lalu coba buka lagi.
                                </p>
                            )}
                            {state === 'ready' && html === '' && (
                                <p className="mt-6 text-sm text-ink/50">Isi belum ditulis.</p>
                            )}
                            {state === 'ready' && html !== '' && (
                                <div className="post-body mt-6 wrap-break-word leading-relaxed text-ink/90"
                                    dangerouslySetInnerHTML={{ __html: html }} />
                            )}
                        </div>
                    </article>
                </div>
            </div>
        </div>
    );
}