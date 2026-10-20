import { useRef, useState } from 'react';
import { useEditor, useEditorState, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { Table, TableRow, TableHeader, TableCell } from '@tiptap/extension-table';
import { Placeholder } from '@tiptap/extensions';
import ImageExtension from '@tiptap/extension-image';
import Youtube from '@tiptap/extension-youtube';

const UPLOAD_URL = '/user/post-images';
const MAX_IMAGE_BYTES = 2 * 1024 * 1024;

// Video yang tersimpan berupa <div class="post-video"><iframe ...>, jadi editor perlu
// tahu cara membacanya kembali saat artikel dibuka untuk diedit.
const SafeYoutube = Youtube.extend({
    parseHTML() {
        return [
            { tag: 'div[data-youtube-video] iframe' },
            { tag: 'iframe[src^="https://www.youtube-nocookie.com/embed/"]' },
        ];
    },
});

// Ambil ID video dari tautan YouTube. Selain YouTube asli, hasilnya null (ditolak).
function youtubeId(input) {
    let url;
    try {
        url = new URL(/^https?:\/\//i.test(input) ? input : `https://${input}`);
    } catch {
        return null;
    }
    const host = url.hostname.toLowerCase();
    let id = null;

    if (host === 'youtu.be') {
        id = url.pathname.slice(1);
    } else if (['youtube.com', 'www.youtube.com', 'm.youtube.com'].includes(host)) {
        if (url.pathname === '/watch') {
            id = url.searchParams.get('v');
        } else {
            const m = url.pathname.match(/^\/(embed|shorts|live)\/([^/]+)/);
            id = m ? m[2] : null;
        }
    }
    return id && /^[A-Za-z0-9_-]{11}$/.test(id) ? id : null;
}

function getCookie(name) {
    const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return m ? decodeURIComponent(m[1]) : '';
}

async function uploadImage(file) {
    const body = new FormData();
    body.append('image', file);

    const res = await fetch(UPLOAD_URL, {
        method: 'POST',
        body,
        credentials: 'same-origin',
        headers: { 'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'), Accept: 'application/json' },
    });
    const data = await res.json().catch(() => ({}));

    if (res.status === 429) throw new Error('Terlalu banyak unggahan. Tunggu sebentar lalu coba lagi.');
    if (!res.ok) throw new Error(data?.errors?.image?.[0] ?? data?.message ?? 'Gagal mengunggah gambar.');
    return data.url;
}

// Tombol toolbar. onMouseDown + preventDefault supaya editor tidak kehilangan pilihan teks.
function Btn({ label, title, active, disabled, onClick }) {
    return (
        <button type="button" title={title ?? label} disabled={disabled}
            onMouseDown={(e) => e.preventDefault()} onClick={onClick}
            className={`rounded-lg px-2.5 py-1 text-sm font-semibold transition disabled:opacity-30 ${
                active ? 'bg-ink text-white' : 'text-ink/70 hover:bg-ink/5'}`}>
            {label}
        </button>
    );
}

const Sep = () => <span aria-hidden="true" className="mx-1 h-5 w-px bg-ink/15" />;

export default function RichTextEditor({ value, onChange, placeholder = 'Tulis isi postingan' }) {
    const fileRef = useRef(null);
    const [uploading, setUploading] = useState(false);
    const [notice, setNotice] = useState('');

    const editor = useEditor({
        content: value || '',
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3] },
                // Hanya fitur yang diizinkan pembersih di server (PostHtml::ALLOWED)
                code: false,
                codeBlock: false,
                link: { openOnClick: false, autolink: true, HTMLAttributes: { rel: 'noopener noreferrer nofollow' } },
            }),
            Table.configure({ resizable: false }),
            TableRow,
            TableHeader,
            TableCell,
            ImageExtension.configure({ inline: false, allowBase64: false }),
            SafeYoutube.configure({ nocookie: true, allowFullscreen: true, width: 640, height: 360 }),
            Placeholder.configure({ placeholder }),
        ],
        editorProps: {
            attributes: { class: 'post-body leading-relaxed text-ink/90' },
        },
        onUpdate: ({ editor }) => onChange(editor.isEmpty ? '' : editor.getHTML()),
    });

    // Toolbar ikut berubah mengikuti posisi kursor
    const s = useEditorState({
        editor,
        selector: ({ editor }) => editor && ({
            bold: editor.isActive('bold'),
            italic: editor.isActive('italic'),
            underline: editor.isActive('underline'),
            h2: editor.isActive('heading', { level: 2 }),
            h3: editor.isActive('heading', { level: 3 }),
            bullet: editor.isActive('bulletList'),
            ordered: editor.isActive('orderedList'),
            quote: editor.isActive('blockquote'),
            link: editor.isActive('link'),
            table: editor.isActive('table'),
            canUndo: editor.can().undo(),
            canRedo: editor.can().redo(),
        }),
    });

    if (!editor || !s) return null;
    const run = () => editor.chain().focus();

    const setLink = () => {
        const previous = editor.getAttributes('link').href ?? 'https://';
        const url = window.prompt('Alamat tautan (kosongkan untuk menghapus tautan):', previous);
        if (url === null) return;

        const raw = url.trim();
        if (raw === '' || raw === 'https://') {
            run().extendMarkRange('link').unsetLink().run();
            return;
        }

        // Tanpa awalan, anggap https:// supaya tidak jadi tautan relatif
        const href = /^(https?:\/\/|mailto:)/i.test(raw) ? raw : `https://${raw}`;

        if (editor.state.selection.empty && !editor.isActive('link')) {
            // Tidak ada teks terpilih: sisipkan alamatnya sebagai teks bertautan
            run().insertContent({ type: 'text', text: href, marks: [{ type: 'link', attrs: { href } }] }).run();
        } else {
            run().extendMarkRange('link').setLink({ href }).run();
        }
    };

    const onPickImage = async (e) => {
        const file = e.target.files?.[0];
        e.target.value = ''; // supaya file yang sama bisa dipilih lagi
        if (!file) return;

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            setNotice('Gambar harus berformat JPG, PNG, atau WEBP.');
            return;
        }
        if (file.size > MAX_IMAGE_BYTES) {
            setNotice('Ukuran gambar maksimal 2 MB.');
            return;
        }

        setNotice('');
        setUploading(true);
        try {
            const url = await uploadImage(file);
            run().setImage({ src: url }).run();
        } catch (err) {
            setNotice(err.message);
        } finally {
            setUploading(false);
        }
    };

    const setVideo = () => {
        const raw = window.prompt('Tempel tautan video YouTube:');
        if (raw === null || raw.trim() === '') return;

        const id = youtubeId(raw.trim());
        if (!id) {
            setNotice('Tautan ditolak. Hanya tautan YouTube yang diterima.');
            return;
        }
        setNotice('');
        run().setYoutubeVideo({ src: `https://www.youtube.com/watch?v=${id}` }).run();
    };

    return (
        <div className="post-editor rounded-xl border border-ink/15 focus-within:border-grape focus-within:ring-2 focus-within:ring-grape/20">
            <div className="flex flex-wrap items-center gap-0.5 border-b border-ink/10 bg-cream/50 px-2 py-1.5">
                <Btn label={<b>B</b>} title="Tebal" active={s.bold} onClick={() => run().toggleBold().run()} />
                <Btn label={<i>I</i>} title="Miring" active={s.italic} onClick={() => run().toggleItalic().run()} />
                <Btn label={<u>U</u>} title="Garis bawah" active={s.underline} onClick={() => run().toggleUnderline().run()} />
                <Sep />
                <Btn label="H2" title="Judul besar" active={s.h2} onClick={() => run().toggleHeading({ level: 2 }).run()} />
                <Btn label="H3" title="Judul kecil" active={s.h3} onClick={() => run().toggleHeading({ level: 3 }).run()} />
                <Sep />
                <Btn label="• Daftar" title="Daftar berpoin" active={s.bullet} onClick={() => run().toggleBulletList().run()} />
                <Btn label="1. Daftar" title="Daftar bernomor" active={s.ordered} onClick={() => run().toggleOrderedList().run()} />
                <Btn label="❝" title="Kutipan" active={s.quote} onClick={() => run().toggleBlockquote().run()} />
                <Sep />
                <Btn label="Tautan" active={s.link} onClick={setLink} />
                <Btn label="Tabel" title="Sisipkan tabel 3x3" active={s.table}
                    onClick={() => run().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()} />
                <Btn label={uploading ? 'Mengunggah…' : 'Gambar'} title="Sisipkan gambar (JPG, PNG, WEBP, maks 2 MB)"
                    disabled={uploading} onClick={() => fileRef.current?.click()} />
                <Btn label="Video" title="Sisipkan video YouTube" onClick={setVideo} />
                <Sep />
                <Btn label="↶" title="Urungkan" disabled={!s.canUndo} onClick={() => run().undo().run()} />
                <Btn label="↷" title="Ulangi" disabled={!s.canRedo} onClick={() => run().redo().run()} />
                <input ref={fileRef} type="file" accept="image/jpeg,image/png,image/webp"
                    className="hidden" onChange={onPickImage} />
            </div>

            {notice && (
                <p role="alert" className="border-b border-ink/10 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600">
                    {notice}
                </p>
            )}

            {s.table && (
                <div className="flex flex-wrap items-center gap-0.5 border-b border-ink/10 bg-grape/5 px-2 py-1">
                    <span className="mr-1 text-xs font-semibold text-grape">Tabel:</span>
                    <Btn label="+ Baris" onClick={() => run().addRowAfter().run()} />
                    <Btn label="+ Kolom" onClick={() => run().addColumnAfter().run()} />
                    <Btn label="− Baris" onClick={() => run().deleteRow().run()} />
                    <Btn label="− Kolom" onClick={() => run().deleteColumn().run()} />
                    <Btn label="Hapus tabel" onClick={() => run().deleteTable().run()} />
                </div>
            )}

            <EditorContent editor={editor} className="px-3 py-3 text-sm" />
        </div>
    );
}