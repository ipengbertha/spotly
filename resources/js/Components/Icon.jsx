export default function Icon({ name, className = 'h-10 w-10' }) {
    return (
        <img src={`/images/icons/${name}.png`} alt=""
            className={`inline-block shrink-0 object-contain ${className}`} />
    );
}