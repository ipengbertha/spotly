import { useEffect, useRef, useState } from 'react';
import { Link } from '@inertiajs/react';

function Arrow({ dir, disabled, onClick }) {
    return (
        <button type="button" onClick={onClick} disabled={disabled}
            aria-label={dir === 'left' ? 'Geser ke kiri' : 'Geser ke kanan'}
            className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-brand transition hover:bg-peach/40 disabled:cursor-default disabled:text-ink/20 disabled:hover:bg-transparent">
            <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2.5"
                strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                <path d={dir === 'left' ? 'M15 5l-7 7 7 7' : 'M9 5l7 7-7 7'} />
            </svg>
        </button>
    );
}

// Bilah kategori: kotak putih dengan panah kiri/kanan, tanpa scrollbar bawaan
export default function CategoryBar({ categories, filters, buildHref }) {
    const scroller = useRef(null);
    const [canLeft, setCanLeft] = useState(false);
    const [canRight, setCanRight] = useState(false);

    const update = () => {
        const el = scroller.current;
        if (!el) return;
        setCanLeft(el.scrollLeft > 4);
        setCanRight(el.scrollLeft + el.clientWidth < el.scrollWidth - 4);
    };

    useEffect(() => {
        const el = scroller.current;
        if (!el) return;

        // Kategori yang sedang aktif digulir ke tengah supaya langsung terlihat
        const active = el.querySelector('[data-active="true"]');
        if (active) {
            el.scrollLeft = active.offsetLeft - el.clientWidth / 2 + active.clientWidth / 2;
        }

        update();
        window.addEventListener('resize', update);
        return () => window.removeEventListener('resize', update);
    }, [categories.length]);

    const scrollBy = (dir) => {
        const el = scroller.current;
        el?.scrollBy({ left: dir * el.clientWidth * 0.6, behavior: 'smooth' });
    };

    const noCategory = !filters.category && !filters.spotlight;

    const item = (active) =>
        `shrink-0 rounded-full px-4 py-1.5 text-sm font-semibold transition ${
            active ? 'bg-ink text-white shadow-sm' : 'text-ink/70 hover:bg-peach/40'}`;

    return (
        <div className="mb-8 flex items-center gap-1 rounded-2xl bg-white px-2 py-2 shadow-md ring-1 ring-ink/5">
            <Arrow dir="left" disabled={!canLeft} onClick={() => scrollBy(-1)} />

            <div ref={scroller} onScroll={update}
                style={{ scrollbarWidth: 'none' }}
                className="flex min-w-0 flex-1 items-center gap-1 overflow-x-auto [&::-webkit-scrollbar]:hidden">
                <Link href={buildHref({ category: null, spotlight: null })}
                    data-active={noCategory} className={item(noCategory)}>
                    Semua
                </Link>
                {categories.map((c) => {
                    const active = String(c.id) === String(filters.category);
                    return (
                        <Link key={c.id} href={buildHref({ category: c.id, spotlight: null })}
                            data-active={active} className={item(active)}>
                            {c.name}
                        </Link>
                    );
                })}
            </div>

            <Arrow dir="right" disabled={!canRight} onClick={() => scrollBy(1)} />
        </div>
    );
}