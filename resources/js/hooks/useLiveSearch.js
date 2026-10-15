import { useEffect, useRef } from 'react';
import { router } from '@inertiajs/react';

// Filter di halaman Mading yang ikut terbawa saat mencari
const KEEP = ['category', 'period', 'spotlight'];

// Parameter pencarian Mading: kata kunci + filter yang sedang aktif (kategori, tanggal, spotlight).
export function madingSearchParams(url, q) {
    const u = new URL(url, 'http://localhost');
    const params = {};
    KEEP.forEach((k) => {
        const v = u.searchParams.get(k);
        if (v) params[k] = v;
    });
    const term = (q ?? '').trim();
    if (term) params.q = term;
    return params;
}

// Pencarian langsung: hasil dimuat sendiri beberapa saat setelah pengguna berhenti mengetik.
// - enabled: hanya aktif di halaman Mading
// - q: isi kotak pencarian; url: alamat halaman sekarang
export default function useLiveSearch({ enabled, q, url, path = '/user/beranda', delay = 300 }) {
    const first = useRef(true);

    useEffect(() => {
        // Lewati render pertama: isi kotak masih sama dengan alamat halaman
        if (first.current) {
            first.current = false;
            return;
        }
        if (!enabled) return;

        const current = new URL(url, 'http://localhost').searchParams.get('q') ?? '';
        if (q.trim() === current.trim()) return;

        const timer = setTimeout(() => {
            router.get(path, madingSearchParams(url, q), {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['posts', 'filters'],
            });
        }, delay);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [q]);
}
