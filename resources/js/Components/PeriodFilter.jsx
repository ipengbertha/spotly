import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';

// Nilai harus sama dengan FeedController::PERIOD_OPTIONS
export const PERIODS = [
    { value: '7',           label: '7 hari terakhir',  phrase: 'dalam 7 hari terakhir' },
    { value: '30',          label: '30 hari terakhir', phrase: 'dalam 30 hari terakhir' },
    { value: '90', label: '3 bulan terakhir', phrase: 'dalam 3 bulan terakhir' },
    { value: 'month',       label: 'Bulan ini',        phrase: 'bulan ini' },
    { value: 'school_year', label: 'Tahun ajaran ini', phrase: 'pada tahun ajaran ini' },
];

// Ikon slider: empat garis dengan bulatan geser
function SlidersIcon({ knobClass }) {
    return (
        <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2"
            strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            <path d="M3 4.5h18M3 9.5h18M3 14.5h18M3 19.5h18" />
            <circle cx="16"   cy="4.5"  r="2.2" className={knobClass} />
            <circle cx="7"    cy="9.5"  r="2.2" className={knobClass} />
            <circle cx="12.5" cy="14.5" r="2.2" className={knobClass} />
            <circle cx="9.5"  cy="19.5" r="2.2" className={knobClass} />
        </svg>
    );
}

// Tombol filter "tayang kapan" di samping kolom search.
// Membaca filter aktif dari alamat halaman, jadi bisa dipasang di layout mana pun.
export default function PeriodFilter() {
    const { url } = usePage();
    const [open, setOpen] = useState(false);

    const current = new URLSearchParams(url.split('?')[1] ?? '');
    const active = PERIODS.find((x) => x.value === current.get('period'));

    // Alamat Mading dengan kata kunci/kategori yang sedang aktif; hanya period yang diganti
    const hrefFor = (period) => {
        const params = new URLSearchParams();
        ['q', 'category', 'spotlight'].forEach((k) => {
            if (current.get(k)) params.set(k, current.get(k));
        });
        if (period) params.set('period', period);
        const qs = params.toString();
        return qs ? `/user/beranda?${qs}` : '/user/beranda';
    };

    return (
        <div className="relative shrink-0">
            <button type="button" onClick={() => setOpen((o) => !o)}
                aria-haspopup="true" aria-expanded={open}
                aria-label="Filter waktu tayang"
                title={active ? `Filter: ${active.label}` : 'Filter berdasarkan waktu tayang'}
                className={`flex h-10 w-10 items-center justify-center rounded-full transition ${
                    active ? 'bg-ink text-white shadow-sm' : 'bg-white text-ink/70 ring-1 ring-ink/10 hover:bg-peach/40'}`}>
                <SlidersIcon knobClass={active ? 'fill-ink' : 'fill-white'} />
            </button>

            {open && (
                <>
                    <button type="button" aria-label="Tutup filter" onClick={() => setOpen(false)}
                        className="fixed inset-0 z-40 cursor-default" />
                    <div className="absolute left-0 z-50 mt-2 w-56 rounded-2xl bg-white p-2 shadow-lg ring-1 ring-ink/10">
                        <p className="px-3 pb-1 pt-2 text-xs font-semibold text-ink/50">Tayang kapan?</p>
                        <Link href={hrefFor(null)} onClick={() => setOpen(false)}
                            className={`block rounded-xl px-3 py-2 text-sm font-semibold hover:bg-peach/40 ${
                                !active ? 'text-brand' : 'text-ink'}`}>
                            Semua
                        </Link>
                        {PERIODS.map((x) => (
                            <Link key={x.value} href={hrefFor(x.value)} onClick={() => setOpen(false)}
                                className={`block rounded-xl px-3 py-2 text-sm font-semibold hover:bg-peach/40 ${
                                    active?.value === x.value ? 'text-brand' : 'text-ink'}`}>
                                {x.label}
                            </Link>
                        ))}
                    </div>
                </>
            )}
        </div>
    );
}