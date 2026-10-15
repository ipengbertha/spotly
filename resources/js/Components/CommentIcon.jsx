// Ikon gelembung komentar, gayanya sama dengan ikon hati di LikeButton
export default function CommentIcon({ className = 'h-5 w-5' }) {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true" className={className}
            fill="none" stroke="currentColor"
            strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
        </svg>
    );
}
