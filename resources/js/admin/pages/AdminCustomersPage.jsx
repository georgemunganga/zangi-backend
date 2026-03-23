import { Eye, UserRound, Users } from 'lucide-react';
import { useDeferredValue, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { AdminDetailDrawer } from '../components/AdminDetailDrawer';
import { AdminEmptyState } from '../components/AdminEmptyState';
import { AdminFilterBar } from '../components/AdminFilterBar';
import { AdminPageHeader } from '../components/AdminPageHeader';
import { AdminSectionCard } from '../components/AdminSectionCard';
import { AdminStatCard } from '../components/AdminStatCard';
import { AdminStatusBadge } from '../components/AdminStatusBadge';
import { AdminCustomerDetailContent } from '../components/details/AdminCustomerDetailContent';
import { useAdminMockData } from '../mocks/AdminMockDataProvider';
import { formatCurrency } from '../mocks/adminOrderMockData';
import { formatDateTime } from '../mocks/adminMockData';

function matchesCustomer(customer, query) {
    if (!query) {
        return true;
    }

    const haystack = [
        customer.name,
        customer.email,
        customer.phone,
        customer.customerType,
        customer.relationshipType,
        ...customer.tags,
    ]
        .join(' ')
        .toLowerCase();

    return haystack.includes(query.toLowerCase());
}

function FilterField({ className = '', label, children }) {
    return (
        <label className={['block', className].join(' ')}>
            <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">
                {label}
            </span>
            {children}
        </label>
    );
}

function RowAction({ children, icon: Icon, onClick, to }) {
    const className =
        'inline-flex items-center justify-center gap-2 rounded-xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-3 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-[color:var(--admin-muted)] transition hover:border-[color:var(--admin-accent)] hover:text-[color:var(--admin-accent)]';

    if (to) {
        return (
            <Link className={className} onClick={onClick} to={to}>
                <Icon className="h-4 w-4" />
                <span>{children}</span>
            </Link>
        );
    }

    return (
        <button className={className} onClick={onClick} type="button">
            <Icon className="h-4 w-4" />
            <span>{children}</span>
        </button>
    );
}

export function AdminCustomersPage() {
    const { customers, isReadDataLoading, readDataError } = useAdminMockData();
    const [searchTerm, setSearchTerm] = useState('');
    const deferredSearch = useDeferredValue(searchTerm);
    const [filters, setFilters] = useState({
        customerType: 'all',
        relationshipType: 'all',
    });
    const [selectedCustomerId, setSelectedCustomerId] = useState(customers[0]?.id ?? null);
    const [showDrawer, setShowDrawer] = useState(false);

    const filteredCustomers = customers.filter((customer) => {
        if (!matchesCustomer(customer, deferredSearch)) {
            return false;
        }

        if (filters.customerType !== 'all' && customer.customerType !== filters.customerType) {
            return false;
        }

        if (filters.relationshipType !== 'all' && customer.relationshipType !== filters.relationshipType) {
            return false;
        }

        return true;
    });

    useEffect(() => {
        if (filteredCustomers.length === 0) {
            setSelectedCustomerId(null);
            setShowDrawer(false);
            return;
        }

        const stillVisible = filteredCustomers.some((customer) => customer.id === selectedCustomerId);

        if (!stillVisible) {
            setSelectedCustomerId(filteredCustomers[0].id);
        }
    }, [filteredCustomers, selectedCustomerId]);

    const selectedCustomer = customers.find((customer) => customer.id === selectedCustomerId) ?? null;
    const customerTypeOptions = [...new Set(customers.map((customer) => customer.customerType))];
    const relationshipOptions = [...new Set(customers.map((customer) => customer.relationshipType))];

    const walkInCount = filteredCustomers.filter((customer) => customer.customerType === 'Walk-in').length;
    const existingCount = filteredCustomers.filter((customer) => customer.relationshipType === 'Existing').length;
    const organizationCount = filteredCustomers.filter((customer) =>
        ['Corporate', 'Wholesale'].includes(customer.customerType),
    ).length;

    const openCustomerDetail = (customerId) => {
        setSelectedCustomerId(customerId);
        setShowDrawer(true);
    };

    return (
        <div className="min-w-0 space-y-6">
            <AdminPageHeader title="Customers" />

            {readDataError ? (
                <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {readDataError}
                </div>
            ) : null}

            {isReadDataLoading ? (
                <div className="rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-3 text-sm text-[color:var(--admin-muted)]">
                    Loading customers...
                </div>
            ) : null}

            <section className="grid gap-4 md:grid-cols-3">
                <AdminStatCard icon={Users} label="Visible customers" value={String(filteredCustomers.length)} />
                <AdminStatCard icon={UserRound} label="Walk-in customers" value={String(walkInCount)} />
                <AdminStatCard icon={Users} label="Organizations" value={String(organizationCount)} />
            </section>

            <AdminFilterBar
                secondaryChildren={
                    <>
                        <FilterField label="Customer type">
                            <select
                                className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
                                onChange={(event) => setFilters((current) => ({ ...current, customerType: event.target.value }))}
                                value={filters.customerType}
                            >
                                <option value="all">All customer types</option>
                                {customerTypeOptions.map((value) => (
                                    <option key={value} value={value}>
                                        {value}
                                    </option>
                                ))}
                            </select>
                        </FilterField>
                        <FilterField label="Relationship">
                            <select
                                className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
                                onChange={(event) =>
                                    setFilters((current) => ({ ...current, relationshipType: event.target.value }))
                                }
                                value={filters.relationshipType}
                            >
                                <option value="all">All relationships</option>
                                {relationshipOptions.map((value) => (
                                    <option key={value} value={value}>
                                        {value}
                                    </option>
                                ))}
                            </select>
                        </FilterField>
                    </>
                }
                summary={`Showing ${filteredCustomers.length} customers.`}
            >
                <FilterField className="xl:col-span-4" label="Search customers">
                    <input
                        className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
                        onChange={(event) => setSearchTerm(event.target.value)}
                        placeholder="Customer name, email, phone, tag, or type"
                        type="search"
                        value={searchTerm}
                    />
                </FilterField>
            </AdminFilterBar>

            <AdminSectionCard eyebrow="Directory" icon={Users} title="Customer list">
                {filteredCustomers.length === 0 ? (
                    <AdminEmptyState
                        description="Clear filters and try again."
                        title="No customers found"
                    />
                ) : (
                    <>
                        <div className="hidden overflow-hidden rounded-[1.5rem] border border-[color:var(--admin-border)] md:block">
                            <div className="overflow-x-auto">
                                <table className="min-w-[1100px] divide-y divide-[color:var(--admin-border)]">
                                    <thead className="bg-[color:var(--admin-surface)]">
                                        <tr className="text-left text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">
                                            <th className="px-4 py-3">Customer</th>
                                            <th className="px-4 py-3">Customer type</th>
                                            <th className="px-4 py-3">Relationship</th>
                                            <th className="px-4 py-3">Contact</th>
                                            <th className="px-4 py-3">Total spent</th>
                                            <th className="px-4 py-3">Last activity</th>
                                            <th className="sticky right-0 bg-[color:var(--admin-surface)] px-4 py-3">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-[color:var(--admin-border)] bg-white">
                                        {filteredCustomers.map((customer) => {
                                            const isSelected = customer.id === selectedCustomerId && showDrawer;

                                            return (
                                                <tr
                                                    className={[
                                                        'cursor-pointer transition hover:bg-[color:var(--admin-surface)]',
                                                        isSelected ? 'bg-[color:var(--admin-accent-soft)]/50' : '',
                                                    ].join(' ')}
                                                    key={customer.id}
                                                    onClick={() => openCustomerDetail(customer.id)}
                                                >
                                                    <td className="px-4 py-4 align-top">
                                                        <p className="font-semibold text-[color:var(--admin-ink)]">{customer.name}</p>
                                                        <p className="mt-1 text-sm text-[color:var(--admin-muted)]">
                                                            {customer.tags.slice(0, 2).join(' | ') || 'No tags yet'}
                                                        </p>
                                                    </td>
                                                    <td className="px-4 py-4 align-top">
                                                        <AdminStatusBadge value={customer.customerType} />
                                                    </td>
                                                    <td className="px-4 py-4 align-top">
                                                        <AdminStatusBadge value={customer.relationshipType} />
                                                    </td>
                                                    <td className="px-4 py-4 align-top">
                                                        <p className="text-sm text-[color:var(--admin-muted)]">{customer.email || 'No email recorded'}</p>
                                                        <p className="mt-1 text-sm text-[color:var(--admin-muted)]">{customer.phone || 'No phone recorded'}</p>
                                                    </td>
                                                    <td className="px-4 py-4 align-top">
                                                        <p className="font-medium text-[color:var(--admin-ink)]">{formatCurrency(customer.totalSpent)}</p>
                                                    </td>
                                                    <td className="px-4 py-4 align-top">
                                                        <p className="text-sm text-[color:var(--admin-muted)]">
                                                            {formatDateTime(customer.lastActivityAt)}
                                                        </p>
                                                    </td>
                                                    <td className="sticky right-0 bg-white px-4 py-4 align-top">
                                                        <div className="flex flex-wrap gap-2">
                                                            <RowAction
                                                                icon={Eye}
                                                                onClick={(event) => {
                                                                    event.stopPropagation();
                                                                    openCustomerDetail(customer.id);
                                                                }}
                                                            >
                                                                Open
                                                            </RowAction>
                                                            <RowAction
                                                                icon={Users}
                                                                onClick={(event) => event.stopPropagation()}
                                                                to={`/admin/customers/${customer.id}`}
                                                            >
                                                                Detail
                                                            </RowAction>
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div className="space-y-3 md:hidden">
                            {filteredCustomers.map((customer) => (
                                <Link
                                    className="block rounded-[1.5rem] border border-[color:var(--admin-border)] bg-white p-4 transition hover:border-[color:var(--admin-accent)] hover:bg-[color:var(--admin-surface)]"
                                    key={customer.id}
                                    to={`/admin/customers/${customer.id}`}
                                >
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p className="font-semibold text-[color:var(--admin-ink)]">{customer.name}</p>
                                            <p className="mt-1 text-sm text-[color:var(--admin-muted)]">
                                                {customer.email || customer.phone || 'No contact recorded'}
                                            </p>
                                        </div>
                                        <p className="text-sm font-semibold text-[color:var(--admin-ink)]">
                                            {formatCurrency(customer.totalSpent)}
                                        </p>
                                    </div>
                                    <div className="mt-3 flex flex-wrap gap-2">
                                        <AdminStatusBadge value={customer.customerType} />
                                        <AdminStatusBadge value={customer.relationshipType} />
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </>
                )}
            </AdminSectionCard>

            <AdminDetailDrawer
                badges={
                    selectedCustomer
                        ? [
                              <AdminStatusBadge key="type" value={selectedCustomer.customerType} />,
                              <AdminStatusBadge key="relationship" value={selectedCustomer.relationshipType} />,
                          ]
                        : null
                }
                description={selectedCustomer ? `${formatCurrency(selectedCustomer.totalSpent)} settled spend` : ''}
                eyebrow="Customer detail"
                isOpen={showDrawer && Boolean(selectedCustomer)}
                onClose={() => setShowDrawer(false)}
                title={selectedCustomer?.name ?? 'Customer detail'}
            >
                <AdminCustomerDetailContent customer={selectedCustomer} />
            </AdminDetailDrawer>
        </div>
    );
}
