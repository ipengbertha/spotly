function Avatar({ name, url }) {
    if (url) {
        return <img src={url} alt="" className="h-11 w-11 rounded-full object-cover ring-2 ring-white" />;
    }
    return (
        <span className="flex h-11 w-11 items-center justify-center rounded-full bg-grape text-base font-bold text-white ring-2 ring-white">
            {(name ?? '?').trim().charAt(0).toUpperCase()}
        </span>
    );
}

export default function ContributorCard({ data }) {
    if (!data?.items?.length) return null;

    return (
        <section className="mb-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-ink/5">
            <h2 className="text-lg text-ink">Kontributor {data.label}</h2>
            <p className="mt-0.5 text-sm text-ink/60">
                Siswa yang paling rajin membagikan karya bulan itu.
            </p>

            <ul className="mt-4 flex flex-wrap gap-x-6 gap-y-4">
                {data.items.map((c) => (
                    <li key={c.id} className="flex items-center gap-3">
                        <Avatar name={c.name} url={c.avatar_url} />
                        <div className="min-w-0">
                            <p className="max-w-40 truncate text-sm font-semibold text-ink">{c.name}</p>
                            <p className="text-xs text-ink/60">
                                {c.posts_count} karya, {c.likes_count} suka
                            </p>
                        </div>
                    </li>
                ))}
            </ul>
        </section>
    );
}
