import { useEffect, useRef, useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import ConfirmDialog, { SUBMIT_WARNING } from './ConfirmDialog';
import RichTextEditor from './RichTextEditor';
import PostPreview from './PostPreview';

const MAX_EXTRA = 2;
const AUTOSAVE_DELAY = 3000; // simpan 3 detik setelah berhenti mengetik

const INPUT =
    'w-full rounded-xl border border-ink/15 px-3 py-2 text-sm outline-none focus:border-grape focus:ring-2 focus:ring-grape/20';
const LABEL = 'mb-1 block text-sm font-semibold text-ink';

function getCookie(name) {
    const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return m ? decodeURIComponent(m[1]) : '';
}

// Ringkasan isi form, untuk tahu apakah ada yang berubah sejak simpan terakhir
const snapshotOf = (d) =>
    JSON.stringify([d.title, d.content, String(d.category_id), d.extra_category_ids]);

export default function PostForm({ categories, post = null }) {
    const { data, setData, post: send, transform, processing, errors } = useForm({
        title: post?.title ?? '',
        content: post?.content ?? '',
        category_id: post?.category_id ?? '',
        extra_category_ids: post?.extra_category_ids ?? [],
        image: null,
        _method: post ? 'put' : 'post', // upload file butuh POST + method spoofing
    });
    const [confirming, setConfirming] = useState(false);
    const [previewing, setPreviewing] = useState(false);
    const [autosave, setAutosave] = useState({ state: 'idle', text: '' });

    // ID postingan: dari props (mode edit), atau dari autosave pertama (mode buat)
    const postIdRef = useRef(post?.id ?? null);
    const inflightRef = useRef(null);       // autosave yang sedang berjalan
    const submittingRef = useRef(false);    // true saat Simpan/Kirim manual berjalan
    const lastSavedRef = useRef(snapshotOf(data));

    // Gambar sampul untuk pratinjau: file yang baru dipilih, atau gambar yang sudah tersimpan
    const [coverPreview, setCoverPreview] = useState(null);
    useEffect(() => {
        if (!data.image) {
            setCoverPreview(null);
            return;
        }
        const objectUrl = URL.createObjectURL(data.image);
        setCoverPreview(objectUrl);
        return () => URL.revokeObjectURL(objectUrl);
    }, [data.image]);

    const sendWith = async (action) => {
        submittingRef.current = true;
        // Tunggu autosave yang sedang berjalan supaya tidak membuat draf kembar
        if (inflightRef.current) await inflightRef.current;

        const id = postIdRef.current;
        transform((d) => ({ ...d, action, _method: id ? 'put' : 'post' }));
        send(id ? `/user/posts/${id}` : '/user/posts', {
            forceFormData: true,
            onFinish: () => { submittingRef.current = false; },
        });
    };

    const runAutosave = async (snapshot, payload) => {
        if (submittingRef.current) return;
        if (inflightRef.current) await inflightRef.current;
        if (submittingRef.current) return;

        setAutosave({ state: 'saving', text: 'Menyimpan…' });

        const job = (async () => {
            try {
                const res = await fetch('/user/posts/autosave', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
                    },
                    body: JSON.stringify({ ...payload, post_id: postIdRef.current }),
                });
                const body = await res.json().catch(() => ({}));

                if (!res.ok) {
                    const firstError = body?.errors ? Object.values(body.errors)[0]?.[0] : null;
                    throw new Error(
                        res.status === 403
                            ? 'Postingan ini tidak bisa diedit lagi.'
                            : firstError ?? 'Simpan otomatis gagal.'
                    );
                }

                postIdRef.current = body.id;
                lastSavedRef.current = snapshot;
                const time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                setAutosave({ state: 'saved', text: `Tersimpan otomatis pukul ${time}` });
            } catch (e) {
                setAutosave({
                    state: 'error',
                    text: e instanceof TypeError ? 'Tidak ada koneksi. Simpan otomatis belum berhasil.' : e.message,
                });
            } finally {
                inflightRef.current = null;
            }
        })();

        inflightRef.current = job;
    };

    useEffect(() => {
        const snapshot = snapshotOf(data);
        if (snapshot === lastSavedRef.current) return; // belum ada perubahan

        const ready = data.title.trim() !== '' && data.category_id !== '' && data.content !== '';
        if (!ready) {
            setAutosave({ state: 'idle', text: 'Simpan otomatis aktif setelah judul, kategori, dan isi terisi.' });
            return;
        }

        const timer = setTimeout(() => runAutosave(snapshot, {
            title: data.title,
            content: data.content,
            category_id: data.category_id,
            extra_category_ids: data.extra_category_ids,
        }), AUTOSAVE_DELAY);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [data.title, data.content, data.category_id, data.extra_category_ids]);

    // Kategori yang dipilih sebagai utama tidak boleh sekaligus jadi tambahan
    const changePrimary = (value) => {
        setData('category_id', value);
        setData('extra_category_ids', data.extra_category_ids.filter((id) => String(id) !== String(value)));
    };

    const toggleExtra = (id) => {
        const chosen = data.extra_category_ids;
        if (chosen.includes(id)) {
            setData('extra_category_ids', chosen.filter((x) => x !== id));
        } else if (chosen.length < MAX_EXTRA) {
            setData('extra_category_ids', [...chosen, id]);
        }
    };

    const extraOptions = categories.filter((c) => String(c.id) !== String(data.category_id));
    const extraError = Object.keys(errors)
        .filter((k) => k.startsWith('extra_category_ids'))
        .map((k) => errors[k])[0];

    // Nama kategori untuk pratinjau: utama dulu, lalu tambahan
    const previewCategoryNames = [data.category_id, ...data.extra_category_ids]
        .filter((id) => id !== '' && id !== null)
        .map((id) => categories.find((c) => String(c.id) === String(id))?.name)
        .filter(Boolean);

    return (
        <>
            <form onSubmit={(e) => { e.preventDefault(); sendWith('draft'); }}
                className="space-y-5 rounded-3xl bg-white p-6 shadow-md ring-1 ring-ink/5">
                <div>
                    <label className={LABEL}>Judul</label>
                    <input value={data.title} onChange={(e) => setData('title', e.target.value)}
                        placeholder="Tulis judul postingan" className={INPUT} />
                    {errors.title && <p className="mt-1 text-sm text-brand">{errors.title}</p>}
                </div>

                <div>
                    <label className={LABEL}>Kategori utama</label>
                    <select value={data.category_id} onChange={(e) => changePrimary(e.target.value)}
                        className={INPUT}>
                        <option value="">Pilih kategori</option>
                        {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                    </select>
                    {errors.category_id && <p className="mt-1 text-sm text-brand">{errors.category_id}</p>}
                </div>

                <div>
                    <label className={LABEL}>
                        Kategori tambahan{' '}
                        <span className="font-normal text-ink/50">(opsional, maksimal {MAX_EXTRA})</span>
                    </label>
                    {data.category_id === '' ? (
                        <p className="text-sm text-ink/50">Pilih kategori utama dulu.</p>
                    ) : (
                        <div className="mt-2 flex flex-wrap gap-2">
                            {extraOptions.map((c) => {
                                const checked = data.extra_category_ids.includes(c.id);
                                const locked = !checked && data.extra_category_ids.length >= MAX_EXTRA;
                                return (
                                    <label key={c.id}
                                        className={`flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-semibold transition ${
                                            checked
                                                ? 'bg-brand text-white shadow-sm'
                                                : 'bg-white text-ink/70 ring-1 ring-ink/10 hover:bg-peach/40'
                                        } ${locked ? 'cursor-not-allowed opacity-40' : 'cursor-pointer'}`}>
                                        <input type="checkbox" className="sr-only" checked={checked}
                                            disabled={locked} onChange={() => toggleExtra(c.id)} />
                                        {c.name}
                                    </label>
                                );
                            })}
                        </div>
                    )}
                    {extraError && <p className="mt-1 text-sm text-brand">{extraError}</p>}
                </div>

                <div>
                    <label className={LABEL}>Isi</label>
                    <RichTextEditor value={data.content} onChange={(html) => setData('content', html)} />
                    {errors.content && <p className="mt-1 text-sm text-brand">{errors.content}</p>}
                </div>

                <div>
                    <label className={LABEL}>Gambar / poster</label>
                    {post?.image_url && (
                        <img src={post.image_url} alt="" className="mb-3 h-32 rounded-2xl object-cover ring-1 ring-ink/10" />
                    )}
                    <div className="flex flex-wrap items-center gap-3">
                        <label className="cursor-pointer rounded-full bg-grape/10 px-4 py-2 text-sm font-semibold text-grape hover:bg-grape/20">
                            {post?.image_url ? 'Ganti gambar' : 'Pilih gambar'}
                            <input type="file" accept="image/jpeg,image/png,image/webp" className="hidden"
                                onChange={(e) => setData('image', e.target.files[0] ?? null)} />
                        </label>
                        <span className="min-w-0 truncate text-sm text-ink/60">
                            {data.image ? data.image.name : 'Belum ada gambar baru dipilih'}
                        </span>
                    </div>
                    <p className="mt-1.5 text-xs text-ink/50">JPG, PNG, atau WEBP. Maksimal 2 MB.</p>
                    {errors.image && <p className="mt-1 text-sm text-brand">{errors.image}</p>}
                </div>

                <div className="flex flex-wrap items-center gap-3 pt-2">
                    <button type="button" disabled={processing} onClick={() => sendWith('draft')}
                        className="rounded-full border border-ink/20 px-5 py-2 text-sm font-semibold text-ink hover:bg-ink/5 disabled:opacity-50">
                        Simpan draft
                    </button>
                    <button type="button" onClick={() => setPreviewing(true)}
                        className="rounded-full bg-grape/10 px-5 py-2 text-sm font-semibold text-grape hover:bg-grape/20">
                        Pratinjau
                    </button>
                    <button type="button" disabled={processing} onClick={() => setConfirming(true)}
                        className="rounded-full bg-brand px-5 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-50">
                        Kirim untuk review
                    </button>
                    <Link href="/user/dashboard" className="px-3 py-2 text-sm font-semibold text-ink/60 hover:text-ink">
                        Batal
                    </Link>
                    {autosave.text && (
                        <p aria-live="polite"
                            className={`text-xs sm:ml-auto ${autosave.state === 'error' ? 'font-semibold text-brand' : 'text-ink/50'}`}>
                            {autosave.text}
                        </p>
                    )}
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

            <PostPreview
                open={previewing}
                onClose={() => setPreviewing(false)}
                title={data.title}
                content={data.content}
                categoryNames={previewCategoryNames}
                imageSrc={coverPreview ?? post?.image_url ?? null}
            />
        </>
    );
}