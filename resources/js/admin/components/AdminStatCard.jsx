export function AdminStatCard({ label, value, trend, detail, icon: Icon }) {
    return (
        <div className="rounded-[1.5rem] border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-strong)] p-5 shadow-[0_16px_32px_rgba(23,33,38,0.05)]">
            <div className="flex items-center justify-between gap-3">
                <p className="text-sm font-medium text-[color:var(--admin-muted)]">{label}</p>
                {Icon ? (
                    <span className="flex h-10 w-10 items-center justify-center rounded-[1rem] bg-[color:var(--admin-surface-soft)] text-[color:var(--admin-accent)]">
                        <Icon className="h-5 w-5" />
                    </span>
                ) : null}
            </div>
            <div className="mt-4 flex items-end justify-between gap-3">
                <p className="text-3xl font-semibold tracking-tight text-[color:var(--admin-ink)]">{value}</p>
                {trend ? (
                    <span className="rounded-full bg-[color:var(--admin-accent-soft)] px-2.5 py-1 text-xs font-semibold text-[color:var(--admin-accent)]">
                        {trend}
                    </span>
                ) : null}
            </div>
            {detail ? <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">{detail}</p> : null}
        </div>
    );
}
