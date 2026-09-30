const SRC = {
    wordmark: '/images/spotly.png',      // navbar, sidebar, hero
    full:     '/images/logospotly.png',  // footer
    icon:     '/images/logo.png',        // hero (ikon besar)
};

export default function Logo({ variant = 'wordmark', className = 'h-10' }) {
    return <img src={SRC[variant]} alt="Spotly" className={`block w-auto ${className}`} />;
}