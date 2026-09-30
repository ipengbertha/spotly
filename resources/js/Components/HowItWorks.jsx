const STEPS = [
    { n: '1', title: 'Daftar & masuk', desc: 'Buat akun siswa gratis untuk mulai berkontribusi.' },
    { n: '2', title: 'Tulis postingan', desc: 'Simpan sebagai draft, tambahkan gambar, lalu kirim.' },
    { n: '3', title: 'Direview admin', desc: 'Admin memeriksa. Kalau ditolak, kamu dapat alasannya dan bisa memperbaiki.' },
    { n: '4', title: 'Tayang di mading', desc: 'Postingan tampil selama masa tayang, lalu masuk arsip.' },
];

export default function HowItWorks() {
    return (
        <section className="mx-auto max-w-5xl px-6 py-12">
            <h2 className="text-center text-2xl font-bold text-ink">Bagaimana cara kerjanya?</h2>
            <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {STEPS.map((s) => (
                    <div key={s.n} className="rounded-2xl border border-peach/40 bg-white p-5">
                        <span className="flex h-9 w-9 items-center justify-center rounded-full bg-brand font-bold text-white">
                            {s.n}
                        </span>
                        <h3 className="mt-3 font-semibold text-ink">{s.title}</h3>
                        <p className="mt-1 text-sm text-ink/70">{s.desc}</p>
                    </div>
                ))}
            </div>
        </section>
    );
}