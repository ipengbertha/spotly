const INPUT =
    'w-full rounded-xl border border-ink/15 px-3 py-2 text-sm outline-none focus:border-grape focus:ring-2 focus:ring-grape/20 disabled:bg-ink/5 disabled:text-ink/40';

// Akhir tahun ajaran (30 Juni) yang berlaku untuk tanggal mulai tayang tertentu
const schoolYearEndLabel = (startDate) => {
    const d = startDate ? new Date(`${startDate}T00:00:00`) : new Date();
    const year = d.getMonth() >= 6 ? d.getFullYear() + 1 : d.getFullYear(); // getMonth(): Juli = 6
    return new Date(year, 5, 30).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

/**
 * Kolom "Tayang sampai" yang menyesuaikan aturan kategori:
 * dated (tanggal atau tanpa batas), none, school_year.
 */
export default function ExpiryField({
    mode = 'dated', categoryName, id = 'expired_at',
    value, onChange, noExpiry, onNoExpiryChange, min, startDate, error,
}) {
    if (mode === 'none') {
        return (
            <p className="mt-3 rounded-2xl bg-grape/10 px-3 py-2 text-sm text-ink ring-1 ring-grape/20">
                Kategori <b>{categoryName}</b> tidak punya batas tayang. Postingan tetap tayang sampai kamu
                mengarsipkannya.
            </p>
        );
    }

    if (mode === 'school_year') {
        return (
            <p className="mt-3 rounded-2xl bg-grape/10 px-3 py-2 text-sm text-ink ring-1 ring-grape/20">
                Kategori <b>{categoryName}</b> tayang sampai pergantian tahun ajaran, yaitu{' '}
                <b>{schoolYearEndLabel(startDate)}</b>. Diatur otomatis.
            </p>
        );
    }

    return (
        <>
            <label className="mb-1 mt-3 block text-sm font-semibold text-ink" htmlFor={id}>
                Tayang sampai
            </label>
            <input id={id} type="date" min={min} value={value} disabled={noExpiry}
                onChange={(e) => onChange(e.target.value)} className={INPUT} />
            {error && <p className="mt-1 text-sm text-brand">{error}</p>}
            <label className="mt-2 flex items-center gap-2 text-sm text-ink">
                <input type="checkbox" className="h-4 w-4 accent-brand" checked={noExpiry}
                    onChange={(e) => onNoExpiryChange(e.target.checked)} />
                Tanpa batas tayang
            </label>
        </>
    );
}