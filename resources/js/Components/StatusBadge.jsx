const STYLES = {
    draft:     ['Draft',           'bg-ink/10 text-ink/70'],
    submitted: ['Menunggu review', 'bg-grape/10 text-grape'],
    published: ['Tayang',          'bg-emerald-100 text-emerald-700'],
    rejected:  ['Ditolak',         'bg-brand/10 text-brand'],
    archived:  ['Arsip',           'bg-amber-100 text-amber-800'],
};

export default function StatusBadge({ status }) {
    const [label, cls] = STYLES[status] ?? [status, 'bg-ink/10 text-ink/70'];
    return (
        <span className={`whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold ${cls}`}>
            {label}
        </span>
    );
}