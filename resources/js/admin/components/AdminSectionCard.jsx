export function AdminSectionCard({ title, eyebrow, description, icon: Icon, actions, children, className = '' }) {
    return (
        <section
            className={[
                'min-w-0 rounded-[1.75rem] border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-strong)] p-5 shadow-[0_16px_40px_rgba(23,33,38,0.06)]',
                className,
            ].join(' ')}
        >
            <div className="mb-5 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div className="flex items-start gap-3">
                    {Icon ? (
                        <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-[1.1rem] bg-[color:var(--admin-accent-soft)] text-[color:var(--admin-accent)]">
                            <Icon className="h-5 w-5" />
                        </div>
                    ) : null}
                    <div>
                        {eyebrow ? (
                            <p className="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-[color:var(--admin-accent)]">
                                {eyebrow}
                            </p>
                        ) : null}
                        <h3 className="mt-2 text-lg font-semibold text-[color:var(--admin-ink)]">{title}</h3>
                        {description ? (
                            <p className="mt-2 max-w-2xl text-sm leading-6 text-[color:var(--admin-muted)]">{description}</p>
                        ) : null}
                    </div>
                </div>
                {actions ? <div className="flex flex-wrap items-center gap-2">{actions}</div> : null}
            </div>
            {children}
        </section>
    );
}
