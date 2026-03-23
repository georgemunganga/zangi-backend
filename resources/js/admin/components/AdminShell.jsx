import { Outlet } from 'react-router-dom';
import { AdminHeader } from './AdminHeader';
import { AdminMobileNav } from './AdminMobileNav';
import { AdminSidebar } from './AdminSidebar';

export function AdminShell() {
    return (
        <div className="min-h-screen overflow-x-hidden bg-[color:var(--admin-bg)] text-[color:var(--admin-ink)]">
            <div className="grid min-h-screen md:grid-cols-[295px_minmax(0,1fr)]">
                <AdminSidebar />
                <div className="flex min-h-screen min-w-0 flex-col">
                    <AdminHeader />
                    <main className="min-w-0 flex-1 overflow-x-hidden px-4 py-6 pb-28 md:px-8 md:py-8 md:pb-10 lg:px-10">
                        <Outlet />
                    </main>
                </div>
            </div>
            <AdminMobileNav />
        </div>
    );
}
