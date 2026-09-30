const ITEMS = [
    { icon: '📌', title: 'Mudah Berbagi', desc: 'Bagikan informasi dan karya dengan mudah.' },
    { icon: '🎨', title: 'Tampilkan Karya', desc: 'Jadikan karya siswa lebih terlihat.' },
    { icon: '🔍', title: 'Mudah Ditemukan', desc: 'Informasi tersusun berdasarkan kategori.' },
    { icon: '💡', title: 'Ruang Berinovasi', desc: 'Wadah kreativitas dan ide siswa.' },
];

export default function WhySpotly() {
    return (
        <section id="kenapa-spotly" className="mx-auto max-w-5xl px-6 py-12">
            <h2 className="text-center text-2xl font-bold text-ink">Kenapa Spotly?</h2>
            <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {ITEMS.map((i) => (
                    <div key={i.title} className="rounded-2xl border border-peach/40 bg-white p-5">
                        <span className="text-2xl">{i.icon}</span>
                        <h3 className="mt-3 font-semibold text-ink">{i.title}</h3>
                        <p className="mt-1 text-sm text-ink/70">{i.desc}</p>
                    </div>
                ))}
            </div>
        </section>
    );
}