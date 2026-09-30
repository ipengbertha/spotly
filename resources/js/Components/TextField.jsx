import { useId, useState } from 'react';

function EyeIcon({ off }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="h-5 w-5">
            {off ? (
                <>
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                    <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24" />
                    <line x1="1" y1="1" x2="23" y2="23" />
                </>
            ) : (
                <>
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                    <circle cx="12" cy="12" r="3" />
                </>
            )}
        </svg>
    );
}

export default function TextField({ label, error, hint, type = 'text', ...props }) {
    const id = useId();
    const [show, setShow] = useState(false);
    const isPassword = type === 'password';

    return (
        <div>
            <label htmlFor={id} className="block text-sm font-semibold text-ink">{label}</label>
            <div className="relative mt-1">
                <input
                    id={id}
                    type={isPassword && show ? 'text' : type}
                    {...props}
                    className={`w-full rounded-xl border-2 bg-white px-3.5 py-2.5 text-ink outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/30 ${
                        error ? 'border-red-400' : 'border-peach'
                    } ${isPassword ? 'pr-11' : ''}`}
                />
                {isPassword && (
                    <button type="button" onClick={() => setShow(!show)}
                        aria-label={show ? 'Sembunyikan password' : 'Tampilkan password'}
                        className="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-ink/60 transition hover:text-brand">
                        <EyeIcon off={show} />
                    </button>
                )}
            </div>
            {hint && !error && <p className="mt-1 text-xs text-ink/70">{hint}</p>}
            {error && <p className="mt-1 text-sm font-medium text-red-600">{error}</p>}
        </div>
    );
}