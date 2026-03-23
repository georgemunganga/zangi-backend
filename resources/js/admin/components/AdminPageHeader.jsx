export function AdminPageHeader({ eyebrow = null, title, description, actions }) {
    return (
        <div className="flex min-w-0 flex-col gap-4 rounded-[1.9rem] border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)]/88 px-5 py-5 shadow-[0_18px_40px_rgba(23,33,38,0.05)] md:flex-row md:items-end md:justify-between md:px-6">
            <div className="min-w-0 max-w-3xl">
                <p className="text-[0.68rem] font-semibold uppercase tracking-[0.26em] text-[color:var(--admin-accent)]">
                    {eyebrow}
                </p>
                <h2 className="mt-2 break-words text-3xl font-semibold tracking-tight text-[color:var(--admin-ink)]">
                    {title}
                </h2>
                <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)] md:text-base">
                    {description}
                </p>
            </div>
            {actions ? <div className="flex min-w-0 flex-wrap items-center gap-2">{actions}</div> : null}
        </div>
    );
}
