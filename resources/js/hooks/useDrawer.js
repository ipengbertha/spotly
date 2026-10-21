import { useEffect } from 'react';

// Drawer menu di layar kecil: saat terbuka, halaman di belakangnya tidak ikut
// tergulir, dan tombol Esc menutupnya.
export default function useDrawer(open, setOpen) {
    useEffect(() => {
        if (!open) return;
        const prev = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        const onKey = (e) => { if (e.key === 'Escape') setOpen(false); };
        window.addEventListener('keydown', onKey);
        return () => {
            document.body.style.overflow = prev;
            window.removeEventListener('keydown', onKey);
        };
    }, [open, setOpen]);
}