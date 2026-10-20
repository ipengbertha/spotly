const STYLES = {
    draft:     ['Draft',           'bg-ink/10 text-ink/70'],
    submitted: ['Menunggu review', 'bg-grape/10 text-grape'],
    revision:  ['Perlu revisi',    'bg-amber-100 text-amber-800'],
    published: ['Tayang',          'bg-emerald-100 text-emerald-700'],
    rejected:  ['Ditolak',         'bg-brand/10 text-brand'],
    archived:  ['Arsip',           'bg-ink/10 text-ink/60'],
    suspended: ['Ditangguhkan',    'bg-red-100 text-red-700'],
};

export default function StatusBadge({ status }) {
    const [label, cls] = STYLES[status] ?? [status, 'bg-ink/10 text-ink/70'];
    return (
        <span className={`whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold ${cls}`}>
            {label}
        </span>
    );
}