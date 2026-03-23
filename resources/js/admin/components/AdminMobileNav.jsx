import { MoreHorizontal, X } from 'lucide-react';
import { useState } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { adminSections, getSectionByPath, mobilePrimaryKeys } from '../config/navigation';

const mobileSections = adminSections.filter((section) => mobilePrimaryKeys.includes(section.key));
const moreSections = adminSections.filter((section) => !mobilePrimaryKeys.includes(section.key));

export function AdminMobileNav() {
    const location = useLocation();
    const activeSection = getSectionByPath(location.pathname);
    const [showMore, setShowMore] = useState(false);

    const moreActive = moreSections.some((section) => section.key === activeSection?.key);

    return (
        <>
            {showMore ? (
                <button
                    aria-label="Close more navigation"
                    className="fixed inset-0 z-30 bg-[color:var(--admin-overlay)] md:hidden"
                    onClick={() => setShowMore(false)}
                    type="button"
                />
            ) : null}

            {showMore ? (
                <div className="fixed inset-x-4 bottom-24 z-40 rounded-[1.8rem] border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-strong)] p-4 shadow-[0_28px_60px_rgba(23,33,38,0.18)] md:hidden">
                    <div className="flex items-center justify-between gap-3">
                        <p className="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-[color:var(--admin-accent)]">
                            More
                        </p>
                        <button
                            className="flex h-10 w-10 items-center justify-center rounded-[1rem] border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] text-[color:var(--admin-muted)]"
                            onClick={() => setShowMore(false)}
                            type="button"
                        >
                            <X className="h-5 w-5" />
                        </button>
                    </div>

                    <div className="mt-4 grid gap-2">
                        {moreSections.map((section) => {
                            const Icon = section.icon;

                            return (
                                <NavLink
                                    key={section.key}
                                    className={({ isActive }) =>
                                        [
                                            'flex items-start gap-3 rounded-[1.25rem] border px-4 py-3 transition',
                                            isActive
                                                ? 'border-[color:var(--admin-accent)] bg-[color:var(--admin-accent-soft)]/70'
                                                : 'border-[color:var(--admin-border)] bg-[color:var(--admin-surface)]',
                                        ].join(' ')
                                    }
                                    onClick={() => setShowMore(false)}
                                    to={section.href}
                                >
                                    <span className="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-[1rem] bg-white text-[color:var(--admin-accent)]">
                                        <Icon className="h-5 w-5" />
                                    </span>
                                    <span className="min-w-0">
                                        <span className="block text-sm font-semibold text-[color:var(--admin-ink)]">
                                            {section.label}
                                        </span>
                                    </span>
                                </NavLink>
                            );
                        })}
                    </div>
                </div>
            ) : null}

            <nav className="fixed inset-x-4 bottom-4 z-30 rounded-[1.75rem] border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-strong)] p-2 shadow-[0_24px_50px_rgba(23,33,38,0.12)] md:hidden">
                <div className="grid grid-cols-5 gap-2">
                    {mobileSections.map((section) => {
                        const Icon = section.icon;

                        return (
                            <NavLink
                                key={section.key}
                                className={({ isActive }) =>
                                    [
                                        'flex flex-col items-center rounded-[1.1rem] px-2 py-2 text-center text-[0.72rem] font-semibold transition',
                                        isActive
                                            ? 'bg-[color:var(--admin-accent-soft)] text-[color:var(--admin-accent)]'
                                            : 'text-[color:var(--admin-muted)]',
                                    ].join(' ')
                                }
                                to={section.href}
                            >
                                <Icon className="h-5 w-5" />
                                <span className="mt-1">{section.shortLabel}</span>
                            </NavLink>
                        );
                    })}

                    <button
                        className={[
                            'flex flex-col items-center rounded-[1.1rem] px-2 py-2 text-center text-[0.72rem] font-semibold transition',
                            moreActive || showMore
                                ? 'bg-[color:var(--admin-accent-soft)] text-[color:var(--admin-accent)]'
                                : 'text-[color:var(--admin-muted)]',
                        ].join(' ')}
                        onClick={() => setShowMore(true)}
                        type="button"
                    >
                        <MoreHorizontal className="h-5 w-5" />
                        <span className="mt-1">More</span>
                    </button>
                </div>
            </nav>
        </>
    );
}
