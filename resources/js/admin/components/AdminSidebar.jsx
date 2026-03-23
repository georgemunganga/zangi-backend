import { ArrowRight, ShieldCheck } from 'lucide-react';
import { NavLink } from 'react-router-dom';
import zangiLogo from '../../../views/emails/layouts/logo-dark-Zangi.png';
import { adminSections } from '../config/navigation';

function getNavLinkClass(isActive) {
    return [
        'group flex items-start gap-3 rounded-[1.35rem] border px-4 py-3 transition',
        isActive
            ? 'border-[color:var(--admin-accent)] bg-[color:var(--admin-surface-strong)] shadow-sm'
            : 'border-transparent text-[color:var(--admin-muted)] hover:border-[color:var(--admin-border)] hover:bg-white/70',
    ].join(' ');
}

export function AdminSidebar() {
    return (
        <aside className="hidden border-r border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-5 md:flex md:flex-col">
            <img
                    alt="Zangi"
                    className="h-auto w-32 max-w-full"
                    src={zangiLogo}
                />
            <div className="rounded-[1.9rem] bg-[color:var(--admin-ink)] px-5 py-5 text-[color:var(--admin-surface)] shadow-[0_20px_40px_rgba(23,33,38,0.15)]">
                <div className="mt-5 flex items-start gap-3">
                    <div className="flex h-12 w-12 items-center justify-center rounded-[1.2rem] bg-white/10 text-white">
                        <ShieldCheck className="h-6 w-6" />
                    </div>
                    <div>
                        <p className="text-[0.68rem] font-semibold uppercase tracking-[0.28em] text-white/70">
                            Operations
                        </p>
                        <h2 className="mt-2 text-2xl font-semibold">Zangi Admin</h2>
                    </div>
                </div>
            </div>

            <nav className="mt-6 flex-1 space-y-2">
                {adminSections.map((section) => {
                    const Icon = section.icon;

                    return (
                        <NavLink key={section.key} className={({ isActive }) => getNavLinkClass(isActive)} to={section.href}>
                            {({ isActive }) => (
                                <>
                                    <div
                                        className={[
                                            'flex h-11 w-11 shrink-0 items-center justify-center rounded-[1rem] border transition',
                                            isActive
                                                ? 'border-[color:var(--admin-accent)] bg-[color:var(--admin-accent-soft)] text-[color:var(--admin-accent)]'
                                                : 'border-[color:var(--admin-border)] bg-white text-[color:var(--admin-muted)]',
                                        ].join(' ')}
                                    >
                                        <Icon className="h-5 w-5" />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center justify-between gap-3">
                                            <span
                                                className={[
                                                    'text-sm font-semibold',
                                                    isActive ? 'text-[color:var(--admin-ink)]' : 'text-[color:var(--admin-muted)]',
                                                ].join(' ')}
                                            >
                                                {section.label}
                                            </span>
                                            <ArrowRight
                                                className={[
                                                    'h-4 w-4 transition',
                                                    isActive
                                                        ? 'translate-x-0 text-[color:var(--admin-accent)]'
                                                        : '-translate-x-1 text-[color:var(--admin-border)] group-hover:translate-x-0 group-hover:text-[color:var(--admin-muted)]',
                                                ].join(' ')}
                                            />
                                        </div>
                                    </div>
                                </>
                            )}
                        </NavLink>
                    );
                })}
            </nav>
        </aside>
    );
}
