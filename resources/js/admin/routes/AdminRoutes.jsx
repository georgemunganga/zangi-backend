import { Navigate, Outlet, Route, Routes, useLocation } from 'react-router-dom';
import { useAdminAuth } from '../auth/AdminAuthProvider';
import { AdminShell } from '../components/AdminShell';
import { AdminContactPage } from '../pages/AdminContactPage';
import { AdminCustomerDetailPage } from '../pages/AdminCustomerDetailPage';
import { AdminCustomersPage } from '../pages/AdminCustomersPage';
import { AdminLoginPage } from '../pages/AdminLoginPage';
import { AdminManualSalesPage } from '../pages/AdminManualSalesPage';
import { AdminOverviewPage } from '../pages/AdminOverviewPage';
import { AdminOrderDetailPage } from '../pages/AdminOrderDetailPage';
import { AdminOrdersPage } from '../pages/AdminOrdersPage';
import { AdminPaymentsPage } from '../pages/AdminPaymentsPage';
import { AdminReportsPage } from '../pages/AdminReportsPage';
import { AdminSettingsPage } from '../pages/AdminSettingsPage';
import { AdminTicketDetailPage } from '../pages/AdminTicketDetailPage';
import { AdminTicketsPage } from '../pages/AdminTicketsPage';
import { AdminTicketValidationPage } from '../pages/AdminTicketValidationPage';
import { AdminSellersPage } from '../pages/AdminSellersPage';

function AdminAuthSplash() {
    return (
        <div className="flex min-h-screen items-center justify-center bg-[color:var(--admin-bg)] px-4">
            <div className="rounded-[1.9rem] border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-strong)] px-6 py-8 text-center shadow-[0_18px_40px_rgba(23,33,38,0.05)]">
                <p className="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-[color:var(--admin-accent)]">
                    Zangi Admin
                </p>
                <p className="mt-3 text-sm text-[color:var(--admin-muted)]">Opening your workspace...</p>
            </div>
        </div>
    );
}

function GuestAdminRoute() {
    const { isAuthenticated, isBootstrapping } = useAdminAuth();

    if (isBootstrapping) {
        return <AdminAuthSplash />;
    }

    if (isAuthenticated) {
        return <Navigate replace to="/admin/overview" />;
    }

    return <Outlet />;
}

function ProtectedAdminRoute() {
    const location = useLocation();
    const { isAuthenticated, isBootstrapping } = useAdminAuth();

    if (isBootstrapping) {
        return <AdminAuthSplash />;
    }

    if (!isAuthenticated) {
        return <Navigate replace state={{ from: location.pathname }} to="/admin/login" />;
    }

    return <Outlet />;
}

export function AdminRoutes() {
    return (
        <Routes>
            <Route element={<GuestAdminRoute />}>
                <Route element={<AdminLoginPage />} path="/admin/login" />
            </Route>

            <Route element={<ProtectedAdminRoute />}>
                <Route element={<AdminShell />} path="/admin">
                    <Route element={<Navigate replace to="/admin/overview" />} index />
                    <Route element={<AdminOverviewPage />} path="overview" />
                    <Route element={<AdminTicketsPage />} path="tickets" />
                    <Route element={<AdminTicketDetailPage />} path="tickets/:ticketId" />
                    <Route element={<AdminTicketValidationPage />} path="tickets/validation" />
                    <Route element={<AdminSellersPage />} path="sellers" />
                    <Route element={<AdminManualSalesPage />} path="manual-sales" />
                    <Route element={<AdminOrdersPage />} path="orders" />
                    <Route element={<AdminOrderDetailPage />} path="orders/:orderId" />
                    <Route element={<AdminCustomersPage />} path="customers" />
                    <Route element={<AdminCustomerDetailPage />} path="customers/:customerId" />
                    <Route element={<AdminContactPage />} path="contact" />
                    <Route element={<AdminPaymentsPage />} path="payments" />
                    <Route element={<AdminReportsPage />} path="reports" />
                    <Route element={<AdminSettingsPage />} path="settings" />
                </Route>
            </Route>

            <Route element={<Navigate replace to="/admin/overview" />} path="*" />
        </Routes>
    );
}
