import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';

export default function PollCard({ poll }) {
    const { errors } = usePage().props;
    const [busy, setBusy] = useState(false);

    const voted = poll.my_option_id !== null;
    const locked = voted && !poll.allow_change;

    const vote = (id) => {
        if (!poll.can_vote || busy || locked || id === poll.my_option_id) return;
        router.post(`/polls/${poll.id}/vote`, { option_id: id }, {
            preserveScroll: true,
            onStart: () => setBusy(true),
            onFinish: () => setBusy(false),
        });
    };

    let note;
    if (!poll.can_vote) note = 'Admin hanya melihat hasil.';
    else if (!voted) note = poll.allow_change
        ? `Suara bisa diganti sampai ${poll.ends_label}.`
        : 'Suara final: tidak bisa diubah setelah kamu memilih.';
    else note = poll.allow_change
        ? `Pilihanmu tercatat. Kamu masih bisa menggantinya sampai ${poll.ends_label}.`
        : 'Pilihanmu tercatat dan tidak bisa diubah.';

    return (
        <section className="mb-8 rounded-3xl border-2 border-peach bg-white p-5 shadow-md sm:p-6">
            <div className="flex flex-wrap items-center gap-2">
                <span className="rounded-full bg-grape px-3 py-0.5 text-xs font-bold text-white">Polling</span>
                <span className="rounded-full bg-ink/10 px-3 py-0.5 text-xs font-semibold text-ink/70">
                    {poll.allow_change ? 'Suara bisa diganti' : 'Suara final'}
                </span>
                <span className="text-xs text-ink/50">Berakhir {poll.ends_label}</span>
            </div>

            <h2 className="mt-3 text-xl text-ink">{poll.question}</h2>

            {(errors.poll || errors.option_id) && (
                <p className="mt-3 rounded-2xl bg-brand/10 px-3 py-2 text-sm font-medium text-brand">
                    {errors.poll ?? errors.option_id}
                </p>
            )}

            <div className="mt-4 flex flex-col gap-2">
                {poll.options.map((o) => {
                    const mine = o.id === poll.my_option_id;
                    return (
                        <button key={o.id} type="button" onClick={() => vote(o.id)}
                            disabled={!poll.can_vote || locked || busy}
                            className={`relative w-full overflow-hidden rounded-xl border-2 px-4 py-2.5 text-left text-sm font-semibold transition disabled:cursor-default ${
                                mine ? 'border-grape text-ink' : 'border-ink/10 text-ink hover:border-grape/50'}`}>
                            {poll.show_results && (
                                <span aria-hidden="true"
                                    className={`absolute inset-y-0 left-0 ${mine ? 'bg-grape/25' : 'bg-peach/50'}`}
                                    style={{ width: `${o.percent}%` }} />
                            )}
                            <span className="relative flex items-center justify-between gap-3">
                                <span>{o.label}{mine && ' ✓'}</span>
                                {poll.show_results && (
                                    <span className="shrink-0 text-ink/70">{o.percent}% ({o.votes})</span>
                                )}
                            </span>
                        </button>
                    );
                })}
            </div>

            <p className="mt-3 text-xs text-ink/50">
                {note}
                {poll.show_results && ` ${poll.total_votes} suara.`}
            </p>
        </section>
    );
}