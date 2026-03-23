import { ArrowUpRight, ClipboardList, CreditCard, Eye, Receipt, TriangleAlert } from 'lucide-react';
import { useDeferredValue, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { openAdminOrderDocument } from '../api/adminApiClient';
import { useAdminAuth } from '../auth/AdminAuthProvider';
import { AdminDetailDrawer } from '../components/AdminDetailDrawer';
import { AdminEmptyState } from '../components/AdminEmptyState';
import { AdminFilterBar } from '../components/AdminFilterBar';
import { AdminPageHeader } from '../components/AdminPageHeader';
import { AdminSectionCard } from '../components/AdminSectionCard';
import { AdminStatCard } from '../components/AdminStatCard';
import { AdminStatusBadge } from '../components/AdminStatusBadge';
import { AdminOrderDetailContent } from '../components/details/AdminOrderDetailContent';
import { useAdminMockData } from '../mocks/AdminMockDataProvider';
import { formatCurrency } from '../mocks/adminOrderMockData';
import { formatDateTime } from '../mocks/adminMockData';
import { getOrderDeliveryMeta } from '../utils/orderDelivery';

function matchesOrderQuery(order, query) {
    if (!query) {
        return true;
    }

    const haystack = [order.reference, order.customerName, order.email, order.phone]
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

function getOrderStatusOptions(order) {
    if (!order) {
        return ['pending'];
    }

    const isBookOrder = order.recordType === 'book_order' || order.type === 'book_only';

    return isBookOrder
        ? ['pending', 'processing', 'completed', 'cancelled', 'refunded', 'failed']
        : ['pending', 'completed', 'cancelled', 'refunded', 'used', 'failed'];
}

export function AdminOrdersPage() {
    const { accessToken, isAuthenticated } = useAdminAuth();
    const { applyOrderStatus, cancelOrderById, confirmPayment, isReadDataLoading, orders, readDataError, refundOrderById, resendOrderDelivery } = useAdminMockData();
    const [searchTerm, setSearchTerm] = useState('');
    const deferredSearch = useDeferredValue(searchTerm);
    const [filters, setFilters] = useState({
        type: 'all',
        status: 'all',
        paymentMethod: 'all',
        source: 'all',
    });
    const [selectedOrderId, setSelectedOrderId] = useState(orders[0]?.id ?? null);
    const [statusDraft, setStatusDraft] = useState('pending');
    const [feedback, setFeedback] = useState('');
    const [actionError, setActionError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [showDrawer, setShowDrawer] = useState(false);

    const filteredOrders = orders.filter((order) => {
        if (!matchesOrderQuery(order, deferredSearch)) {
            return false;
        }

        if (filters.type !== 'all' && order.type !== filters.type) {
            return false;
        }

        if (filters.status !== 'all' && order.status !== filters.status) {
            return false;
        }

        if (filters.paymentMethod !== 'all' && order.paymentMethod !== filters.paymentMethod) {
            return false;
        }

        if (filters.source !== 'all' && order.source !== filters.source) {
            return false;
        }

        return true;
    });

    useEffect(() => {
        if (filteredOrders.length === 0) {
            setSelectedOrderId(null);
            setShowDrawer(false);
            return;
        }

        const selectedStillVisible = filteredOrders.some((order) => order.id === selectedOrderId);

        if (!selectedStillVisible) {
            setSelectedOrderId(filteredOrders[0].id);
        }
    }, [filteredOrders, selectedOrderId]);

    const selectedOrder = orders.find((order) => order.id === selectedOrderId) ?? null;

    useEffect(() => {
        if (selectedOrder) {
            setStatusDraft(selectedOrder.status);
        }
    }, [selectedOrder]);

    useEffect(() => {
        setFeedback('');
        setActionError('');
    }, [selectedOrderId]);

    const typeOptions = [...new Set(orders.map((order) => order.type))];
    const statusOptions = [...new Set(orders.map((order) => order.status))];
    const paymentMethodOptions = [...new Set(orders.map((order) => order.paymentMethod))];
    const sourceOptions = [...new Set(orders.map((order) => order.source))];

    const unpaidSales = filteredOrders.filter((order) => order.paymentStatus === 'pending').length;
    const manualOrders = filteredOrders.filter((order) => order.source === 'admin_manual').length;
    const refundedOrders = filteredOrders.filter((order) => order.status === 'refunded').length;

    const openOrderDetail = (orderId) => {
        setSelectedOrderId(orderId);
        setShowDrawer(true);
    };

    const runAction = async (handler, successText) => {
        if (!selectedOrder) {
            return;
        }

        setActionError('');
        setIsSubmitting(true);

        try {
            const updated = await handler(selectedOrder.id);

            if (updated) {
                setFeedback(successText(updated));
            }
        } catch (error) {
            setActionError(error.message);
        } finally {
            setIsSubmitting(false);
        }
    };

    const handlePrintDocument = async () => {
        if (!selectedOrder || !accessToken || !isAuthenticated) {
            setActionError('Sign in to open the receipt.');
            return;
        }

        setActionError('');
        setIsSubmitting(true);

        try {
            await openAdminOrderDocument(accessToken, selectedOrder.id);
            setFeedback('Receipt opened.');
        } catch (error) {
            setActionError(error.message);
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleResendDelivery = async () => {
        if (!selectedOrder) {
            return;
        }

        setActionError('');
        setIsSubmitting(true);

        try {
            const response = await resendOrderDelivery(selectedOrder.id);
            const meta = getOrderDeliveryMeta(response?.order ?? selectedOrder);
            setFeedback(response?.message || meta.successFallback);
        } catch (error) {
            setActionError(error.message);
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="min-w-0 space-y-6">
            <AdminPageHeader
                actions={
                    <Link
                        className="inline-flex items-center gap-2 rounded-2xl bg-[color:var(--admin-ink)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-black"
                        to="/admin/manual-sales"
                    >
                        <ArrowUpRight className="h-4.5 w-4.5" />
                        <span>Manual sale</span>
                    </Link>
                }
                title="Orders"
            />

            {readDataError ? (
                <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {readDataError}
                </div>
            ) : null}

            {isReadDataLoading ? (
                <div className="rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-3 text-sm text-[color:var(--admin-muted)]">
                    Loading orders...
                </div>
            ) : null}

            {actionError ? (
                <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {actionError}
                </div>
            ) : null}

            <section className="grid gap-4 md:grid-cols-3">
                <AdminStatCard icon={ClipboardList} label="Visible orders" value={String(filteredOrders.length)} />
                <AdminStatCard icon={CreditCard} label="Unpaid sales" value={String(unpaidSales)} />
                <AdminStatCard icon={TriangleAlert} label="Refunded orders" value={String(refundedOrders)} />
            </section>

            <AdminFilterBar
                secondaryChildren={
                    <>
                        <FilterField label="Status">
                            <select
                                className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
                                onChange={(event) => setFilters((current) => ({ ...current, status: event.target.value }))}
                                value={filters.status}
                            >
                                <option value="all">All statuses</option>
                                {statusOptions.map((value) => (
                                    <option key={value} value={value}>
                                        {value}
                                    </option>
                                ))}
                            </select>
                        </FilterField>
                        <FilterField label="Payment method">
                            <select
                                className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
                                onChange={(event) => setFilters((current) => ({ ...current, paymentMethod: event.target.value }))}
                                value={filters.paymentMethod}
                            >
                                <option value="all">All methods</option>
                                {paymentMethodOptions.map((value) => (
                                    <option key={value} value={value}>
                                        {value}
                                    </option>
                                ))}
                            </select>
                        </FilterField>
                        <FilterField label="Source">
                            <select
                                className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
                                onChange={(event) => setFilters((current) => ({ ...current, source: event.target.value }))}
                                value={filters.source}
                            >
                                <option value="all">All sources</option>
                                {sourceOptions.map((value) => (
                                    <option key={value} value={value}>
                                        {value}
                                    </option>
                                ))}
                            </select>
                        </FilterField>
                    </>
                }
                summary={`Showing ${filteredOrders.length} orders.`}
            >
                <FilterField className="xl:col-span-3" label="Search">
                    <input
                        className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
                        onChange={(event) => setSearchTerm(event.target.value)}
                        placeholder="Order reference, customer, email, phone"
                        type="search"
                        value={searchTerm}
                    />
                </FilterField>
                <FilterField label="Type">
                    <select
                        className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
                        onChange={(event) => setFilters((current) => ({ ...current, type: event.target.value }))}
                        value={filters.type}
                    >
                        <option value="all">All types</option>
                        {typeOptions.map((value) => (
                            <option key={value} value={value}>
                                {value}
                            </option>
                        ))}
                    </select>
                </FilterField>
            </AdminFilterBar>

            <AdminSectionCard eyebrow="Unified queue" icon={ClipboardList} title="Order list">
                {filteredOrders.length === 0 ? (
                    <AdminEmptyState
                        description="Clear filters and try again."
                        title="No orders found"
                    />
                ) : (
                    <>
                        <div className="hidden overflow-hidden rounded-[1.5rem] border border-[color:var(--admin-border)] md:block">
                            <div className="overflow-x-auto">
                                <table className="min-w-[1220px] divide-y divide-[color:var(--admin-border)]">
                                    <thead className="bg-[color:var(--admin-surface)]">
                                        <tr className="text-left text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">
                                            <th className="px-4 py-3">Order</th>
                                            <th className="px-4 py-3">Customer</th>
                                            <th className="px-4 py-3">Type</th>
                                            <th className="px-4 py-3">Status</th>
                                            <th className="px-4 py-3">Payment</th>
                                            <th className="px-4 py-3">Total</th>
                                            <th className="px-4 py-3">Created</th>
                                            <th className="sticky right-0 bg-[color:var(--admin-surface)] px-4 py-3">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-[color:var(--admin-border)] bg-white">
                                        {filteredOrders.map((order) => {
                                            const isSelected = order.id === selectedOrderId && showDrawer;

                                            return (
                                                <tr
                                                    className={[
                                                        'cursor-pointer transition hover:bg-[color:var(--admin-surface)]',
                                                        isSelected ? 'bg-[color:var(--admin-accent-soft)]/50' : '',
                                                    ].join(' ')}
                                                    key={order.id}
                                                    onClick={() => openOrderDetail(order.id)}
                                                >
                                                    <td className="px-4 py-4 align-top">
                                                        <p className="font-semibold text-[color:var(--admin-ink)]">{order.reference}</p>
                                                        <p className="mt-1 text-sm text-[color:var(--admin-muted)]">
                                                            {order.lines.map((line) => line.label).join(' | ')}
                                                        </p>
                                                    </td>
                                                    <td className="px-4 py-4 align-top">
                                                        <p className="font-medium text-[color:var(--admin-ink)]">{order.customerName}</p>
                                                        <p className="mt-1 text-sm text-[color:var(--admin-muted)]">{order.email}</p>
                                                    </td>
                                                    <td className="px-4 py-4 align-top">
                                                        <AdminStatusBadge value={order.type} />
                                                    </td>
                                                    <td className="px-4 py-4 align-top">
                                                        <AdminStatusBadge value={order.status} />
                                                    </td>
                                                    <td className="px-4 py-4 align-top">
                                                        <AdminStatusBadge value={order.paymentStatus} />
                                                    </td>
                                                    <td className="px-4 py-4 align-top">
                                                        <p className="font-medium text-[color:var(--admin-ink)]">{formatCurrency(order.total, order.currency)}</p>
                                                        <p className="mt-1 text-sm text-[color:var(--admin-muted)]">{order.paymentMethod}</p>
                                                    </td>
                                                    <td className="px-4 py-4 align-top">
                                                        <p className="text-sm text-[color:var(--admin-muted)]">{formatDateTime(order.createdAt)}</p>
                                                    </td>
                                                    <td className="sticky right-0 bg-white px-4 py-4 align-top">
                                                        <div className="flex flex-wrap gap-2">
                                                            <RowAction
                                                                icon={Eye}
                                                                onClick={(event) => {
                                                                    event.stopPropagation();
                                                                    openOrderDetail(order.id);
                                                                }}
                                                            >
                                                                Open
                                                            </RowAction>
                                                            <RowAction
                                                                icon={Receipt}
                                                                onClick={(event) => event.stopPropagation()}
                                                                to={`/admin/orders/${order.id}`}
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
                            {filteredOrders.map((order) => (
                                <Link
                                    className="block rounded-[1.5rem] border border-[color:var(--admin-border)] bg-white p-4 transition hover:border-[color:var(--admin-accent)] hover:bg-[color:var(--admin-surface)]"
                                    key={order.id}
                                    to={`/admin/orders/${order.id}`}
                                >
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p className="font-semibold text-[color:var(--admin-ink)]">{order.reference}</p>
                                            <p className="mt-1 text-sm text-[color:var(--admin-muted)]">{order.customerName}</p>
                                        </div>
                                        <AdminStatusBadge value={order.status} />
                                    </div>
                                    <p className="mt-3 text-sm font-medium text-[color:var(--admin-ink)]">
                                        {formatCurrency(order.total, order.currency)}
                                    </p>
                                    <div className="mt-3 flex flex-wrap gap-2">
                                        <AdminStatusBadge value={order.paymentStatus} />
                                        <AdminStatusBadge value={order.type} />
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </>
                )}
            </AdminSectionCard>

            <AdminSectionCard eyebrow="Highlights" icon={TriangleAlert} title="Order summary">
                <div className="grid gap-3 md:grid-cols-3">
                    <div className="rounded-2xl bg-[color:var(--admin-surface)] px-4 py-4">
                        <p className="text-sm font-semibold text-[color:var(--admin-ink)]">{manualOrders}</p>
                        <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">Manual orders</p>
                    </div>
                    <div className="rounded-2xl bg-[color:var(--admin-surface)] px-4 py-4">
                        <p className="text-sm font-semibold text-[color:var(--admin-ink)]">
                            {filteredOrders.filter((order) => order.type === 'book_only').length}
                        </p>
                        <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">Book orders</p>
                    </div>
                    <div className="rounded-2xl bg-[color:var(--admin-surface)] px-4 py-4">
                        <p className="text-sm font-semibold text-[color:var(--admin-ink)]">{unpaidSales}</p>
                        <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">Unpaid sales</p>
                    </div>
                </div>
            </AdminSectionCard>

            <AdminDetailDrawer
                badges={
                    selectedOrder
                        ? [
                              <AdminStatusBadge key="type" value={selectedOrder.type} />,
                              <AdminStatusBadge key="status" value={selectedOrder.status} />,
                              <AdminStatusBadge key="payment" value={selectedOrder.paymentStatus} />,
                              <AdminStatusBadge key="source" value={selectedOrder.source} />,
                          ]
                        : null
                }
                description={selectedOrder ? `${selectedOrder.customerName} | ${formatCurrency(selectedOrder.total, selectedOrder.currency)}` : ''}
                eyebrow="Order detail"
                isOpen={showDrawer && Boolean(selectedOrder)}
                onClose={() => setShowDrawer(false)}
                title={selectedOrder?.reference ?? 'Order detail'}
            >
                <AdminOrderDetailContent
                        feedback={feedback}
                        isSubmitting={isSubmitting}
                        onApplyStatus={() =>
                            runAction(
                                (orderId) => applyOrderStatus(orderId, statusDraft),
                            (updatedOrder) => `${updatedOrder.reference} status set to ${updatedOrder.status}.`,
                        )
                    }
                    onCancelOrder={() => runAction(cancelOrderById, (updatedOrder) => `${updatedOrder.reference} cancelled.`)}
                    onConfirmPayment={() =>
                        runAction(confirmPayment, (updatedOrder) => `${updatedOrder.reference} payment confirmed.`)
                    }
                    onPrintReceipt={handlePrintDocument}
                    onResendDelivery={handleResendDelivery}
                    onRefundOrder={() => runAction(refundOrderById, (updatedOrder) => `${updatedOrder.reference} refunded.`)}
                    order={selectedOrder}
                    setStatusDraft={setStatusDraft}
                    statusDraft={statusDraft}
                    statusOptions={getOrderStatusOptions(selectedOrder)}
                />
            </AdminDetailDrawer>
        </div>
    );
}
