import { LogOut, ShieldUser } from 'lucide-react';
import { useState } from 'react';
import { useLocation } from 'react-router-dom';
import { useAdminAuth } from '../auth/AdminAuthProvider';
import { adminSections, getSectionByPath } from '../config/navigation';

function getInitials(name) {
    return (name ?? 'Admin')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

export function AdminHeader() {
    const location = useLocation();
    const { logout, user } = useAdminAuth();
    const activeSection = getSectionByPath(location.pathname) ?? adminSections[0];
    const ActiveIcon = activeSection.icon;
    const [isLoggingOut, setIsLoggingOut] = useState(false);

    const handleLogout = async () => {
        setIsLoggingOut(true);

        try {
            await logout();
        } finally {
            setIsLoggingOut(false);
        }
    };

    return (
        <header className="sticky top-0 z-20 border-b border-[color:var(--admin-border)] bg-[color:var(--admin-surface)]/92 backdrop-blur">
            <div className="flex items-center justify-between gap-4 px-4 py-4 md:px-8 lg:px-10">
                <div className="min-w-0 flex items-center gap-3">
                    <div className="hidden h-12 w-12 shrink-0 items-center justify-center rounded-[1.15rem] bg-white text-[color:var(--admin-accent)] shadow-sm md:flex">
                        <ActiveIcon className="h-6 w-6" />
                    </div>
                    <div className="min-w-0">
                        <p className="text-[0.68rem] font-semibold uppercase tracking-[0.28em] text-[color:var(--admin-accent)]">
                            Zangi Admin
                        </p>
                        <h1 className="mt-1 truncate text-xl font-semibold text-[color:var(--admin-ink)] md:text-2xl">
                            {activeSection.label}
                        </h1>
                    </div>
                </div>

                <div className="flex items-center gap-3">
                    <div className="inline-flex items-center gap-3 rounded-2xl border border-[color:var(--admin-border)] bg-white px-3 py-2.5 text-sm font-medium text-[color:var(--admin-ink)] shadow-sm"
                    >
                        <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-[color:var(--admin-accent-soft)] text-[color:var(--admin-accent)]">
                            {user?.name ? (
                                <span className="text-sm font-semibold">{getInitials(user.name)}</span>
                            ) : (
                                <ShieldUser className="h-5 w-5" />
                            )}
                        </span>
                        <span className="hidden text-left md:flex md:flex-col">
                            <span className="text-[0.68rem] uppercase tracking-[0.16em] text-[color:var(--admin-muted)]">Admin</span>
                            <span>{user?.name ?? 'Admin'}</span>
                            <span className="text-xs font-normal text-[color:var(--admin-muted)]">{user?.email ?? ''}</span>
                        </span>
                        <button
                            className="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-[color:var(--admin-border)] text-[color:var(--admin-muted)] transition hover:border-[color:var(--admin-accent)] hover:text-[color:var(--admin-accent)] disabled:cursor-not-allowed disabled:opacity-60"
                            disabled={isLoggingOut}
                            onClick={handleLogout}
                            type="button"
                        >
                            <LogOut className="h-4.5 w-4.5" />
                        </button>
                    </div>
                </div>
            </div>
        </header>
    );
}
