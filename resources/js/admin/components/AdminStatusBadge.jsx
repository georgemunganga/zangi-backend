function normalizeStatus(value) {
    return String(value ?? '')
        .trim()
        .toLowerCase()
        .replace(/[\s-]+/g, '_');
}

function formatStatusLabel(value) {
    return normalizeStatus(value)
        .split('_')
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

const toneMap = {
    valid: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    paid: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    used: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    completed: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    issued: 'border-sky-200 bg-sky-50 text-sky-700',
    in_progress: 'border-sky-200 bg-sky-50 text-sky-700',
    processing: 'border-sky-200 bg-sky-50 text-sky-700',
    pending: 'border-amber-200 bg-amber-50 text-amber-700',
    expired: 'border-amber-200 bg-amber-50 text-amber-700',
    already_used: 'border-stone-300 bg-stone-100 text-stone-700',
    read: 'border-stone-300 bg-stone-100 text-stone-700',
    closed: 'border-stone-300 bg-stone-100 text-stone-700',
    online: 'border-stone-300 bg-stone-100 text-stone-700',
    refunded: 'border-stone-300 bg-stone-100 text-stone-700',
    admin_manual: 'border-[color:var(--admin-border)] bg-[color:var(--admin-accent-soft)] text-[color:var(--admin-accent)]',
    manual: 'border-[color:var(--admin-border)] bg-[color:var(--admin-accent-soft)] text-[color:var(--admin-accent)]',
    complimentary: 'border-[color:var(--admin-border)] bg-[color:var(--admin-accent-soft)] text-[color:var(--admin-accent)]',
    ticket_only: 'border-[color:var(--admin-border)] bg-[color:var(--admin-accent-soft)] text-[color:var(--admin-accent)]',
    book_only: 'border-[color:var(--admin-border)] bg-[color:var(--admin-accent-soft)] text-[color:var(--admin-accent)]',
    mixed: 'border-[color:var(--admin-border)] bg-[color:var(--admin-accent-soft)] text-[color:var(--admin-accent)]',
    walk_in: 'border-[color:var(--admin-border)] bg-[color:var(--admin-accent-soft)] text-[color:var(--admin-accent)]',
    individual: 'border-stone-300 bg-stone-100 text-stone-700',
    corporate: 'border-sky-200 bg-sky-50 text-sky-700',
    wholesale: 'border-purple-200 bg-purple-50 text-purple-700',
    existing: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    cash: 'border-stone-300 bg-stone-100 text-stone-700',
    card: 'border-sky-200 bg-sky-50 text-sky-700',
    mobile_money: 'border-amber-200 bg-amber-50 text-amber-700',
    digital: 'border-sky-200 bg-sky-50 text-sky-700',
    hardcopy: 'border-orange-200 bg-orange-50 text-orange-700',
    cancelled: 'border-rose-200 bg-rose-50 text-rose-700',
    voided: 'border-rose-200 bg-rose-50 text-rose-700',
    failed: 'border-rose-200 bg-rose-50 text-rose-700',
    invalid: 'border-rose-200 bg-rose-50 text-rose-700',
    spam: 'border-rose-200 bg-rose-50 text-rose-700',
    wrong_event: 'border-orange-200 bg-orange-50 text-orange-700',
    unread: 'border-orange-200 bg-orange-50 text-orange-700',
    replied: 'border-teal-200 bg-teal-50 text-teal-700',
};

export function AdminStatusBadge({ label, value }) {
    const key = normalizeStatus(value ?? label);
    const classes = toneMap[key] ?? 'border-stone-300 bg-stone-100 text-stone-700';

    return (
        <span
            className={[
                'inline-flex items-center rounded-full border px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.18em]',
                classes,
            ].join(' ')}
        >
            {label ?? formatStatusLabel(value)}
        </span>
    );
}
