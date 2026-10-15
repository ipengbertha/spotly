import { Head, Link } from '@inertiajs/react';
import UserLayout from '../../Layouts/UserLayout';
import PostCard from '../../Components/PostCard';

export default function Saved({ posts }) {
    return (
        <UserLayout>
            <Head title="Tersimpan" />

            <div className="mb-6">
                <h1 className="text-3xl text-ink">Tersimpan</h1>
                <p className="mt-1 text-sm text-ink/60">
                    Artikel yang kamu simpan untuk dibaca nanti dan masih tayang di mading.
                </p>
            </div>

            {posts.data.length === 0 ? (
                <div className="rounded-2xl border border-dashed border-peach bg-white/60 px-6 py-16 text-center">
                    <h2 className="text-xl text-ink">Belum ada artikel tersimpan</h2>
                    <p className="mx-auto mt-2 max-w-sm text-sm text-ink/60">
                        Tekan ikon pita (bookmark) pada postingan di Mading, maka artikelnya muncul di sini.
                    </p>
                    <Link href="/user/beranda"
                        className="mt-5 inline-block rounded-full bg-brand px-5 py-2 text-sm font-semibold text-white shadow-md transition hover:-translate-y-0.5">
                        Buka Mading
                    </Link>
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
        </UserLayout>
    );
}
