import { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

// Tooltip rail: digambar di <body> (fixed) supaya tidak terpotong area scroll sidebar.
// Dipasang di dalam elemen yang di-hover; mendengarkan event milik elemen induknya.
export default function RailTip({ children }) {
    const marker = useRef(null);
    const [pos, setPos] = useState(null);

    useEffect(() => {
        const host = marker.current?.parentElement;
        if (!host) return;

        const show = () => {
            const r = host.getBoundingClientRect();
            setPos({ top: r.top + r.height / 2, left: r.right + 12 });
        };
        const hide = () => setPos(null);

        host.addEventListener('mouseenter', show);
        host.addEventListener('mouseleave', hide);
        host.addEventListener('focusin', show);
        host.addEventListener('focusout', hide);
        // scroll tidak bubble, jadi pakai capture agar scroll di dalam sidebar tertangkap
        document.addEventListener('scroll', hide, true);

        return () => {
            host.removeEventListener('mouseenter', show);
            host.removeEventListener('mouseleave', hide);
            host.removeEventListener('focusin', show);
            host.removeEventListener('focusout', hide);
            document.removeEventListener('scroll', hide, true);
        };
    }, []);

    return (
        <>
            <span ref={marker} className="hidden" />
            {pos && createPortal(
                <span role="tooltip"
                    style={{ top: `${pos.top}px`, left: `${pos.left}px` }}
                    className="pointer-events-none fixed z-50 hidden -translate-y-1/2 whitespace-nowrap rounded-lg bg-ink px-2.5 py-1 text-xs font-semibold text-white shadow-lg ring-1 ring-white/10 lg:block">
                    {children}
                </span>,
                document.body,
            )}
        </>
    );
}