import { useEditor, useEditorState, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { Table, TableRow, TableHeader, TableCell } from '@tiptap/extension-table';
import { Placeholder } from '@tiptap/extensions';

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
                <Sep />
                <Btn label="↶" title="Urungkan" disabled={!s.canUndo} onClick={() => run().undo().run()} />
                <Btn label="↷" title="Ulangi" disabled={!s.canRedo} onClick={() => run().redo().run()} />
            </div>

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
