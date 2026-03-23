import { ArrowLeft, Users } from 'lucide-react';
import { Link, useParams } from 'react-router-dom';
import { AdminEmptyState } from '../components/AdminEmptyState';
import { AdminPageHeader } from '../components/AdminPageHeader';
import { AdminSectionCard } from '../components/AdminSectionCard';
import { AdminStatusBadge } from '../components/AdminStatusBadge';
import { AdminCustomerDetailContent } from '../components/details/AdminCustomerDetailContent';
import { useAdminMockData } from '../mocks/AdminMockDataProvider';

export function AdminCustomerDetailPage() {
    const { customerId } = useParams();
    const { customers, isReadDataLoading, readDataError } = useAdminMockData();

    const customer = customers.find((entry) => entry.id === customerId) ?? null;

    return (
        <div className="min-w-0 space-y-6">
            <AdminPageHeader
                actions={
                    <Link
                        className="inline-flex items-center gap-2 rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm font-semibold text-[color:var(--admin-ink)] transition hover:border-[color:var(--admin-accent)] hover:text-[color:var(--admin-accent)]"
                        to="/admin/customers"
                    >
                        <ArrowLeft className="h-4.5 w-4.5" />
                        <span>Back to customers</span>
                    </Link>
                }
                title={customer ? customer.name : 'Customer not found'}
            />

            {readDataError ? (
                <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {readDataError}
                </div>
            ) : null}

            {isReadDataLoading ? (
                <div className="rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-3 text-sm text-[color:var(--admin-muted)]">
                    Loading customer...
                </div>
            ) : null}

            {customer ? (
                <AdminSectionCard
                    actions={
                        <div className="flex flex-wrap gap-2">
                            <AdminStatusBadge value={customer.customerType} />
                            <AdminStatusBadge value={customer.relationshipType} />
                        </div>
                    }
                    eyebrow="Customer detail"
                    icon={Users}
                    title={customer.email || customer.phone || 'Customer profile'}
                >
                    <AdminCustomerDetailContent customer={customer} />
                </AdminSectionCard>
            ) : !isReadDataLoading ? (
                <AdminEmptyState
                    action={
                        <Link
                            className="inline-flex items-center gap-2 rounded-2xl bg-[color:var(--admin-ink)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-black"
                            to="/admin/customers"
                        >
                            <ArrowLeft className="h-4.5 w-4.5" />
                            <span>Back to customers</span>
                        </Link>
                    }
                    description="Check the customer list and try again."
                    title="Customer not found"
                />
            ) : null}
        </div>
    );
}
