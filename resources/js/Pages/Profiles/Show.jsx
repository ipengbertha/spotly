import { Head, Link, router } from '@inertiajs/react';
import ViewerLayout from '../../Layouts/ViewerLayout';
import Avatar from '../../Components/Avatar';
import PostCard from '../../Components/PostCard';

const joined = (iso) =>
    iso ? new Date(iso).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }) : null;

function Stat({ value, label }) {
    return (
        <div className="rounded-2xl bg-peach/30 px-5 py-3 text-center">
            <p className="text-2xl font-bold text-ink">{value}</p>
            <p className="text-xs font-medium text-ink/70">{label}</p>
        </div>
    );
}

export default function Show({ profile, posts, viewer }) {
    const fallback = viewer === 'guest' ? '/' : '/user/beranda';
    const back = () => (window.history.length > 1 ? window.history.back() : router.visit(fallback));
    const since = joined(profile.joined_at);

    return (
        <ViewerLayout viewer={viewer} title="Profil Penulis">
            <Head title={profile.name} />

            <div className="mx-auto w-full max-w-6xl">
                <button type="button" onClick={back}
                    className="mb-4 rounded-full bg-white px-4 py-1.5 text-sm font-semibold text-ink ring-2 ring-peach transition hover:bg-peach/40">
                    ← Kembali
                </button>

                <section className="rounded-3xl border-2 border-peach bg-white p-6 shadow-md sm:p-8">
                    <div className="flex flex-wrap items-center gap-5">
                        <Avatar src={profile.avatar_url} name={profile.name} className="h-24 w-24 text-4xl" />

                        <div className="min-w-0 flex-1">
                            <span className="inline-block rounded-full bg-grape px-3 py-0.5 text-xs font-bold text-white">
                                {profile.role_label}
                            </span>
                            <h1 className="mt-1 text-3xl font-bold text-ink">{profile.name}</h1>
                            {profile.bio
                                ? <p className="mt-1 max-w-prose text-ink/80">{profile.bio}</p>
                                : <p className="mt-1 text-sm text-ink/50">Belum ada bio.</p>}
                            {since && <p className="mt-1 text-xs text-ink/60">Bergabung sejak {since}</p>}
                        </div>

                        {profile.is_me && (
                            <Link href="/settings"
                                className="rounded-xl border border-ink/20 px-4 py-2 text-sm font-semibold text-ink hover:bg-ink/5">
                                Edit profil
                            </Link>
                        )}
                    </div>

                    <div className="mt-6 grid max-w-sm grid-cols-2 gap-3">
                        <Stat value={profile.posts_count} label="Postingan tayang" />
                        <Stat value={profile.likes_count} label="Suka diterima" />
                    </div>
                </section>

                <h2 className="mb-4 mt-8 text-xl font-bold text-ink">Postingan oleh {profile.name}</h2>

                {posts.data.length === 0 ? (
                    <div className="rounded-2xl border border-dashed border-peach bg-white/60 px-6 py-14 text-center">
                        <p className="text-ink/70">Belum ada postingan yang sedang tayang.</p>
                    </div>
                ) : (
                    <div className="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                        {posts.data.map((p) => <PostCard key={p.id} p={p} />)}
                    </div>
                )}

                {posts.last_page > 1 && (
                    <div className="mt-8 flex flex-wrap justify-center gap-1">
                        {posts.links.map((l, i) =>
                            l.url ? (
                                <Link key={i} href={l.url}
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
            </div>
        </ViewerLayout>
    );
}
