const STYLES = {
    draft:     ['Draft', 'bg-slate-200 text-slate-700'],
    submitted: ['Menunggu review', 'bg-blue-100 text-blue-700'],
    published: ['Tayang', 'bg-green-100 text-green-700'],
    rejected:  ['Ditolak', 'bg-red-100 text-red-700'],
    archived:  ['Arsip', 'bg-amber-100 text-amber-800'],
};

export default function StatusBadge({ status }) {
    const [label, cls] = STYLES[status] ?? [status, 'bg-slate-200'];
    return <span className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${cls}`}>{label}</span>;
}