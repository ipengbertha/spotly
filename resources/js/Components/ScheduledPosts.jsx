import { useState } from 'react';
import { router } from '@inertiajs/react';
import StatusBadge from './StatusBadge';
import ConfirmDialog from './ConfirmDialog';

// Daftar postingan yang sudah disetujui tapi belum waktunya tayang.
// Dipakai di halaman kelola postingan admin. Tidak tampil kalau daftarnya kosong.
export default function ScheduledPosts({ items = [] }) {
    const [target, setTarget] = useState(null);

    if (items.length === 0) return null;

    return (
        <section className="mb-8">
            <div className="mb-2 flex items-center gap-2">
                <h2 className="text-lg text-ink">Terjadwal</h2>
                <StatusBadge status="scheduled" />
                <span className="text-sm text-ink/60">({items.length})</span>
            </div>
            <p className="mb-3 text-sm text-ink/60">
                Sudah disetujui dan akan tampil di mading otomatis pada tanggal mulainya. Batalkan jadwal
                untuk mengembalikan postingan ke antrean review.
            </p>

            <div className="overflow-x-auto rounded-3xl bg-white shadow-md ring-1 ring-ink/5">
                <table className="w-full text-left text-sm">
                    <thead className="text-ink/50">
                        <tr>
                            <th className="px-5 py-4 font-medium">Judul</th>
                            <th className="px-5 py-4 font-medium">Penulis</th>
                            <th className="px-5 py-4 font-medium">Kategori</th>
                            <th className="px-5 py-4 font-medium">Tayang mulai</th>
                            <th className="px-5 py-4 font-medium">Tayang sampai</th>
                            <th className="px-5 py-4 font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-ink/5">
                        {items.map((p) => (
                            <tr key={p.id}>
                                <td className="px-5 py-3 font-semibold text-ink">{p.title}</td>
                                <td className="px-5 py-3 text-ink/70">{p.author}</td>
                                <td className="px-5 py-3 text-ink/70">{p.category}</td>
                                <td className="whitespace-nowrap px-5 py-3 text-ink/70">{p.publish_label}</td>
                                <td className="whitespace-nowrap px-5 py-3 text-ink/70">{p.expired_label}</td>
                                <td className="whitespace-nowrap px-5 py-3">
                                    <button onClick={() => setTarget(p)}
                                        className="rounded-full border border-brand/40 px-3 py-1 text-xs font-semibold text-brand hover:bg-brand/10">
                                        Batalkan jadwal
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <ConfirmDialog
                open={target !== null}
                title="Batalkan jadwal?"
                message={`"${target?.title ?? ''}" akan kembali ke antrean review dan tidak akan tayang sampai disetujui lagi.`}
                confirmLabel="Ya, batalkan"
                onCancel={() => setTarget(null)}
                onConfirm={() => {
                    router.post(`/admin/posts/${target.id}/unschedule`, {}, { preserveScroll: true });
                    setTarget(null);
                }}
            />
        </section>
    );
}