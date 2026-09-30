import { useEffect, useRef, useState } from 'react';

export default function Reveal({ children, delay = 0, className = '' }) {
    const ref = useRef(null);
    const [show, setShow] = useState(false);

    useEffect(() => {
        const io = new IntersectionObserver(([e]) => {
            if (e.isIntersecting) { setShow(true); io.disconnect(); }
        }, { threshold: 0.15 });
        io.observe(ref.current);
        return () => io.disconnect();
    }, []);

    return (
        <div ref={ref} style={{ transitionDelay: `${delay}ms` }}
            className={`transition duration-700 ${show ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0'} ${className}`}>
            {children}
        </div>
    );
}  