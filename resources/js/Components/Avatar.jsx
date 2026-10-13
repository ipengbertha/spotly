// Foto profil bulat. Tanpa foto: lingkaran dengan huruf pertama nama.
export default function Avatar({ src, name, className = 'h-8 w-8 text-sm' }) {
    const initial = (name ?? '?').trim().charAt(0).toUpperCase();

    if (src) {
        return <img src={src} alt="" className={`shrink-0 rounded-full object-cover ${className}`} />;
    }

    return (
        <span aria-hidden="true"
            className={`inline-flex shrink-0 items-center justify-center rounded-full bg-linear-to-br from-brand to-grape font-bold text-white ${className}`}>
            {initial}
        </span>
    );
}
