import { useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import TextField from './TextField';
import ConfirmDialog, { SUBMIT_WARNING } from './ConfirmDialog';

export default function PostForm({ categories, post = null }) {
    const { data, setData, post: send, transform, processing, errors } = useForm({
        title: post?.title ?? '',
        content: post?.content ?? '',
        category_id: post?.category_id ?? '',
        image: null,
        _method: post ? 'put' : 'post', // upload file butuh POST + method spoofing
    });
    const [confirming, setConfirming] = useState(false);

    const url = post ? `/user/posts/${post.id}` : '/user/posts';

    const sendWith = (action) => {
        transform((d) => ({ ...d, action }));
        send(url, { forceFormData: true });
    };

    return (
        <>
            <form onSubmit={(e) => { e.preventDefault(); sendWith('draft'); }}
                className="space-y-4 rounded-2xl bg-white p-6 shadow ring-1 ring-slate-200">
                <TextField label="Judul" value={data.title}
                    onChange={(e) => setData('title', e.target.value)} error={errors.title} />

                <div>
                    <label className="block text-sm font-semibold text-slate-700">Kategori</label>
                    <select value={data.category_id} onChange={(e) => setData('category_id', e.target.value)}
                        className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">Pilih kategori</option>
                        {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                    </select>
                    {errors.category_id && <p className="mt-1 text-sm text-red-600">{errors.category_id}</p>}
                </div>

                <div>
                    <label className="block text-sm font-semibold text-slate-700">Isi</label>
                    <textarea rows={6} value={data.content} onChange={(e) => setData('content', e.target.value)}
                        className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    {errors.content && <p className="mt-1 text-sm text-red-600">{errors.content}</p>}
                </div>

                <div>
                    <label className="block text-sm font-semibold text-slate-700">Gambar / poster (jpg, png, webp, maks 2 MB)</label>
                    {post?.image_url && <img src={post.image_url} alt="" className="mt-2 h-32 rounded-lg object-cover" />}
                    <input type="file" accept="image/jpeg,image/png,image/webp"
                        onChange={(e) => setData('image', e.target.files[0])} className="mt-2 block text-sm" />
                    {errors.image && <p className="mt-1 text-sm text-red-600">{errors.image}</p>}
                </div>

                <div className="flex gap-3">
                    <button type="button" disabled={processing} onClick={() => sendWith('draft')}
                        className="rounded-lg bg-white px-4 py-2 font-semibold ring-1 ring-slate-300 disabled:opacity-50">
                        Simpan draft
                    </button>
                    <button type="button" disabled={processing} onClick={() => setConfirming(true)}
                        className="rounded-lg bg-slate-900 px-4 py-2 font-semibold text-white disabled:opacity-50">
                        Kirim untuk review
                    </button>
                    <Link href="/user/dashboard" className="px-4 py-2 text-slate-600">Batal</Link>
                </div>
            </form>

            <ConfirmDialog
                open={confirming}
                title="Kirim untuk review?"
                message={SUBMIT_WARNING}
                confirmLabel="Ya, kirim"
                onCancel={() => setConfirming(false)}
                onConfirm={() => { setConfirming(false); sendWith('submit'); }}
            />
        </>
    );
}