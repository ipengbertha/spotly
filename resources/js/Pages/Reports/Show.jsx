import { Head, Link } from '@inertiajs/react';
import UserLayout from '../../Layouts/UserLayout';

const OUTCOME = {
    pending:   ['Menunggu ditinjau', 'bg-grape/10 text-grape',
        'Laporanmu sudah diterima dan sedang menunggu ditinjau admin.'],
    dismissed: ['Tidak ada pelanggaran', 'bg-emerald-100 text-emerald-700',
        'Admin sudah meninjau postingan ini dan tidak menemukan pelanggaran, jadi postingan tetap tayang.'],
    upheld:    ['Ditindaklanjuti', 'bg-brand/10 text-brand',
        'Admin menemukan pelanggaran dan postingan ini sudah ditangguhkan. Terima kasih sudah melapor.'],
};

const fmt = (iso) =>
    iso ? new Date(iso).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : null;

export default function Show({ report }) {
    const [label, cls, text] = OUTCOME[report.status] ?? OUTCOME.pending;

    return (
        <UserLayout>
            <Head title="Hasil Laporan" />

            <div className="mx-auto w-full max-w-2xl rounded-3xl border-2 border-peach bg-white p-6 shadow-md sm:p-8">
                <span className={`inline-block rounded-full px-3 py-0.5 text-xs font-bold ${cls}`}>{label}</span>
                <h1 className="mt-2 text-2xl font-bold text-ink">Hasil laporanmu</h1>
                <p className="mt-2 text-ink/80">{text}</p>

                <dl className="mt-6 space-y-4 text-sm">
                    <div>
                        <dt className="font-semibold text-ink/60">Postingan</dt>
                        <dd className="mt-0.5 text-ink">
                            {report.post_url
                                ? <Link href={report.post_url} className="font-semibold hover:text-brand hover:underline">{report.post_title}</Link>
                                : report.post_title}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold text-ink/60">Alasan yang kamu pilih</dt>
                        <dd className="mt-0.5 text-ink">{report.reason_label}</dd>
                    </div>
                    {report.note && (
                        <div>
                            <dt className="font-semibold text-ink/60">Catatanmu</dt>
                            <dd className="mt-0.5 whitespace-pre-line text-ink">{report.note}</dd>
                        </div>
                    )}
                    {report.admin_note && (
                        <div className="rounded-xl bg-grape/10 p-3">
                            <dt className="font-semibold text-grape">Catatan admin</dt>
                            <dd className="mt-0.5 whitespace-pre-line text-ink">{report.admin_note}</dd>
                        </div>
                    )}
                    <div className="text-xs text-ink/50">
                        Dilaporkan {fmt(report.created_at)}
                        {report.reviewed_at && <> &middot; Ditinjau {fmt(report.reviewed_at)}</>}
                    </div>
                </dl>

                <Link href="/user/beranda"
                    className="mt-6 inline-block rounded-full bg-ink px-5 py-2 text-sm font-semibold text-white hover:opacity-90">
                    Kembali ke Mading
                </Link>
            </div>
        </UserLayout>
    );
}
