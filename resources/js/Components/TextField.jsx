export default function TextField({ label, error, ...props }) {
    return (
        <div>
            <label className="block text-sm font-semibold text-slate-700">{label}</label>
            <input
                {...props}
                className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
            />
            {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
        </div>
    );
}