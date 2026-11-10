import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';

function Body({ period, periodLabel, months, ranking, candidateIds, selected, announced, canAnnounce, max, minPosts }) {
    const form = useForm({ period, user_ids: selected });
    const [confirmCancel, setConfirmCancel] = useState(false);

    const toggle = (id) => {
        const has = form.data.user_ids.includes(id);
        form.setData('user_ids', has ? form.data.user_ids.filter((x) => x !== id) : [...form.data.user_ids, id]);
    };

    const submit = (e) => {
        e.preventDefault();
        form.post('/admin/contributors', { preserveScroll: true });
    };

    const cancelAnnouncement = () => {
        router.delete(`/admin/contributors/${period}`, {
            preserveScroll: true,
            onFinish: () => setConfirmCancel(false),
        });
    };

    const changePeriod = (value) => router.get('/admin/contributors', { period: value });

    const nothingChecked = form.data.user_ids.length === 0;

    return (
        <>
            <div className="mb-5 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 className="text-3xl text-ink">Kontributor {periodLabel}</h1>
                    <p className="mt-1 text-sm text-ink/60">
                        Peringkat berdasarkan jumlah karya terbit. Seri ditentukan total suka dan komentar,
                        lalu karya yang terbit lebih dulu.
                    </p>
                </div>
                <label className="flex items-center gap-2 text-sm font-semibold text-ink/70">
                    Bulan
                    <select value={period} onChange={(e) => changePeriod(e.target.value)}
                        className="rounded-lg border border-ink/15 bg-white px-3 py-1.5 text-sm font-normal text-ink">
                        {months.map((m) => <option key={m.value} value={m.value}>{m.label}</option>)}
                    </select>
                </label>
            </div>

            <div className={`mb-5 rounded-xl px-4 py-3 text-sm ring-1 ${
                announced ? 'bg-grape/10 text-grape ring-grape/20' : 'bg-white text-ink/70 ring-ink/10'}`}>
                {announced
                    ? 'Sudah diumumkan di Mading. Kamu masih bisa mengubah pilihan atau membatalkannya.'
                    : canAnnounce
                        ? 'Belum diumumkan. Bulan ini bisa dilewati kalau tidak ingin mengumumkan siapa pun.'
                        : 'Bulan ini belum selesai, jadi peringkatnya masih bisa berubah dan belum bisa diumumkan.'}
            </div>

            <form onSubmit={submit}>
                <div className="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-ink/5">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b border-ink/10 text-xs text-ink/60">
                            <tr>
                                <th className="w-12 px-4 py-3 font-semibold">Pilih</th>
                                <th className="w-16 px-4 py-3 font-semibold">Peringkat</th>
                                <th className="px-4 py-3 font-semibold">Siswa</th>
                                <th className="px-4 py-3 text-right font-semibold">Karya</th>
                                <th className="px-4 py-3 text-right font-semibold">Suka</th>
                                <th className="px-4 py-3 text-right font-semibold">Komentar</th>
                            </tr>
                        </thead>
                        <tbody>
                            {ranking.data.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="px-4 py-12 text-center text-ink/60">
                                        Belum ada karya terbit di bulan ini.
                                    </td>
                                </tr>
                            )}
                            {ranking.data.map((r) => {
                                const isCandidate = candidateIds.includes(r.user_id);
                                return (
                                    <tr key={r.user_id} className={`border-b border-ink/5 last:border-0 ${r.eligible ? '' : 'text-ink/40'}`}>
                                        <td className="px-4 py-3">
                                            {isCandidate ? (
                                                <input type="checkbox" className="h-4 w-4 accent-brand"
                                                    checked={form.data.user_ids.includes(r.user_id)}
                                                    onChange={() => toggle(r.user_id)}
                                                    disabled={!canAnnounce}
                                                    aria-label={`Pilih ${r.name}`} />
                                            ) : (
                                                <span title={r.eligible
                                                    ? `Di luar ${max} teratas`
                                                    : `Belum memenuhi minimum ${minPosts} karya`}>-</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 font-semibold">{r.rank}</td>
                                        <td className="px-4 py-3">
                                            <span className="font-semibold">{r.name}</span>
                                            {!r.eligible && (
                                                <span className="ml-2 text-xs">belum {minPosts} karya</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right">{r.posts_count}</td>
                                        <td className="px-4 py-3 text-right">{r.likes_count}</td>
                                        <td className="px-4 py-3 text-right">{r.comments_count}</td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>

                {ranking.last_page > 1 && (
                    <div className="mt-5 flex flex-wrap justify-center gap-1">
                        {ranking.links.map((l, i) =>
                            l.url ? (
                                <Link key={i} href={l.url} preserveState preserveScroll
                                    className={`rounded-full px-3.5 py-1.5 text-sm font-semibold ${
                                        l.active ? 'bg-ink text-white' : 'bg-white text-ink/70 ring-1 ring-ink/10 hover:bg-peach/40'}`}
                                    dangerouslySetInnerHTML={{ __html: l.label }} />
                            ) : (
                                <span key={i} className="px-3.5 py-1.5 text-sm text-ink/30"
                                    dangerouslySetInnerHTML={{ __html: l.label }} />
                            )
                        )}
                    </div>
                )}

                {(form.errors.user_ids || form.errors.period) && (
                    <p className="mt-4 text-sm font-semibold text-brand">{form.errors.user_ids || form.errors.period}</p>
                )}

                {canAnnounce && (
                    <div className="mt-5 flex flex-wrap items-center gap-3">
                        <button type="submit" disabled={form.processing || nothingChecked}
                            className="rounded-full bg-brand px-5 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90 disabled:opacity-40">
                            {announced ? 'Simpan perubahan' : 'Umumkan'} ({form.data.user_ids.length}/{max})
                        </button>

                        {announced && !confirmCancel && (
                            <button type="button" onClick={() => setConfirmCancel(true)}
                                className="rounded-full border border-ink/20 px-4 py-2 text-sm font-semibold text-ink hover:bg-ink/5">
                                Batalkan pengumuman
                            </button>
                        )}
                        {announced && confirmCancel && (
                            <span className="flex items-center gap-2 text-sm">
                                Hapus pengumuman dari Mading?
                                <button type="button" onClick={cancelAnnouncement}
                                    className="rounded-full bg-ink px-4 py-1.5 font-semibold text-white">Ya, batalkan</button>
                                <button type="button" onClick={() => setConfirmCancel(false)}
                                    className="rounded-full px-3 py-1.5 font-semibold text-ink/70 hover:bg-ink/5">Tidak</button>
                            </span>
                        )}
                    </div>
                )}
            </form>
        </>
    );
}

export default function Contributors(props) {
    return (
        <AdminLayout title="Kontributor">
            <Head title="Kontributor" />
            {/* key: pilihan dicentang direset saat bulan diganti */}
            <Body key={props.period} {...props} />
        </AdminLayout>
    );
}
