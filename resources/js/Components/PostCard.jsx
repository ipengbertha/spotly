import Icon from './Icon';

export default function PostCard({ p, big = false }) {
    const h = big ? 'h-72' : 'h-52';
    const names = p.categories?.length ? p.categories : [p.category];

    return (
        <article className="group h-full overflow-hidden rounded-2xl border-2 border-peach bg-white shadow-md transition duration-300 hover:-translate-y-2 hover:border-brand hover:shadow-2xl">
            <div className="relative overflow-hidden">
                {p.image_url
                    ? <img src={p.image_url} alt="" className={`w-full object-cover transition duration-500 group-hover:scale-110 ${h}`} />
                    : <div className={`flex items-center justify-center bg-linear-to-br from-peach to-grape/50 font-medium text-white ${h}`}>Tanpa gambar</div>}
                {p.is_pinned && (
                    <span className="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-brand px-3 py-1 text-xs font-bold text-white shadow">
                        <Icon name="pin" className="h-4 w-4" /> Disematkan
                    </span>
                )}
            </div>
            <div className="space-y-2 p-5">
                <div className="flex flex-wrap gap-1.5">
                    {names.map((name) => (
                        <span key={name} className="inline-block rounded-full bg-grape px-3 py-0.5 text-xs font-bold text-white">
                            {name}
                        </span>
                    ))}
                </div>
                <h3 className={`font-bold leading-snug text-ink ${big ? 'text-xl' : 'text-lg'}`}>{p.title}</h3>
                <p className="text-sm text-ink/85">{p.excerpt}</p>
                <p className="pt-1 text-xs font-medium text-ink/70">{p.author} · {p.published_at}</p>
            </div>
        </article>
    );
}