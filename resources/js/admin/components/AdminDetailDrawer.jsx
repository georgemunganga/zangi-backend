import { X } from 'lucide-react';

export function AdminDetailDrawer({
    isOpen,
    eyebrow = 'Detail',
    title,
    description,
    badges,
    onClose,
    children,
    footer,
}) {
    if (!isOpen) {
        return null;
    }

    return (
        <div className="hidden md:block">
            <button
                aria-label="Close details"
                className="fixed inset-0 z-40 bg-[color:var(--admin-overlay)]"
                onClick={onClose}
                type="button"
            />
            <aside className="fixed inset-y-4 right-4 z-50 flex w-[min(460px,calc(100vw-2rem))] flex-col rounded-[2rem] border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-strong)] shadow-[0_28px_70px_rgba(23,33,38,0.18)]">
                <div className="flex items-start justify-between gap-3 border-b border-[color:var(--admin-border)] px-6 py-5">
                    <div className="min-w-0">
                        <p className="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-[color:var(--admin-accent)]">
                            {eyebrow}
                        </p>
                        <h3 className="mt-2 truncate text-xl font-semibold text-[color:var(--admin-ink)]">{title}</h3>
                        {description ? (
                            <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">{description}</p>
                        ) : null}
                        {badges?.length ? <div className="mt-4 flex flex-wrap gap-2">{badges}</div> : null}
                    </div>

                    <button
                        className="flex h-11 w-11 shrink-0 items-center justify-center rounded-[1rem] border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] text-[color:var(--admin-muted)] transition hover:border-[color:var(--admin-accent)] hover:text-[color:var(--admin-accent)]"
                        onClick={onClose}
                        type="button"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                <div className="min-h-0 flex-1 overflow-y-auto px-6 py-6">{children}</div>

                {footer ? <div className="border-t border-[color:var(--admin-border)] px-6 py-5">{footer}</div> : null}
            </aside>
        </div>
    );
}
