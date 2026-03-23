import { ChevronDown, ChevronUp, SlidersHorizontal } from 'lucide-react';
import { useState } from 'react';

export function AdminFilterBar({
    children,
    summary,
    secondaryChildren,
    secondaryLabel = 'More filters',
    defaultExpanded = false,
}) {
    const [showSecondary, setShowSecondary] = useState(defaultExpanded);

    return (
        <div className="min-w-0 rounded-[1.75rem] border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-strong)] p-4 shadow-[0_14px_34px_rgba(23,33,38,0.05)]">
            <div className="grid gap-3 xl:grid-cols-6">{children}</div>
            {secondaryChildren ? (
                <div className="mt-3 border-t border-[color:var(--admin-border)] pt-3">
                    <button
                        className="inline-flex items-center gap-2 rounded-full border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-3 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-[color:var(--admin-muted)] transition hover:border-[color:var(--admin-accent)] hover:text-[color:var(--admin-accent)]"
                        onClick={() => setShowSecondary((current) => !current)}
                        type="button"
                    >
                        <SlidersHorizontal className="h-4 w-4" />
                        <span>{secondaryLabel}</span>
                        {showSecondary ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
                    </button>

                    {showSecondary ? <div className="mt-3 grid gap-3 md:grid-cols-3">{secondaryChildren}</div> : null}
                </div>
            ) : null}
            {summary ? (
                <p className="mt-4 text-sm leading-6 text-[color:var(--admin-muted)]">
                    {summary}
                </p>
            ) : null}
        </div>
    );
}
