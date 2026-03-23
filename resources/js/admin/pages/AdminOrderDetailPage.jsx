import { ArrowLeft, ClipboardList } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { openAdminOrderDocument } from '../api/adminApiClient';
import { useAdminAuth } from '../auth/AdminAuthProvider';
import { AdminEmptyState } from '../components/AdminEmptyState';
import { AdminPageHeader } from '../components/AdminPageHeader';
import { AdminSectionCard } from '../components/AdminSectionCard';
import { AdminStatusBadge } from '../components/AdminStatusBadge';
import { AdminOrderDetailContent } from '../components/details/AdminOrderDetailContent';
import { useAdminMockData } from '../mocks/AdminMockDataProvider';
import { getOrderDeliveryMeta } from '../utils/orderDelivery';

function getOrderStatusOptions(order) {
    if (!order) {
        return ['pending'];
    }

    const isBookOrder = order.recordType === 'book_order' || order.type === 'book_only';

    return isBookOrder
        ? ['pending', 'processing', 'completed', 'cancelled', 'refunded', 'failed']
        : ['pending', 'completed', 'cancelled', 'refunded', 'used', 'failed'];
}

export function AdminOrderDetailPage() {
    const { orderId } = useParams();
    const { accessToken, isAuthenticated } = useAdminAuth();
    const { applyOrderStatus, cancelOrderById, confirmPayment, isReadDataLoading, orders, readDataError, refundOrderById, resendOrderDelivery } = useAdminMockData();
    const [statusDraft, setStatusDraft] = useState('pending');
    const [feedback, setFeedback] = useState('');
    const [actionError, setActionError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const order = orders.find((entry) => entry.id === orderId) ?? null;
    const statusOptions = getOrderStatusOptions(order);

    useEffect(() => {
        if (order) {
            setStatusDraft(order.status);
        }
    }, [order]);

    const runAction = async (handler, successText) => {
        if (!order) {
            return;
        }

        setActionError('');
        setIsSubmitting(true);

        try {
            const updated = await handler(order.id);

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
        if (!order || !accessToken || !isAuthenticated) {
            setActionError('Sign in to open the receipt.');
            return;
        }

        setActionError('');
        setIsSubmitting(true);

        try {
            await openAdminOrderDocument(accessToken, order.id);
            setFeedback('Receipt opened.');
        } catch (error) {
            setActionError(error.message);
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleResendDelivery = async () => {
        if (!order) {
            return;
        }

        setActionError('');
        setIsSubmitting(true);

        try {
            const response = await resendOrderDelivery(order.id);
            const meta = getOrderDeliveryMeta(response?.order ?? order);
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
                        className="inline-flex items-center gap-2 rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm font-semibold text-[color:var(--admin-ink)] transition hover:border-[color:var(--admin-accent)] hover:text-[color:var(--admin-accent)]"
                        to="/admin/orders"
                    >
                        <ArrowLeft className="h-4.5 w-4.5" />
                        <span>Back to orders</span>
                    </Link>
                }
                title={order ? order.reference : 'Order not found'}
            />

            {readDataError ? (
                <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {readDataError}
                </div>
            ) : null}

            {isReadDataLoading ? (
                <div className="rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-3 text-sm text-[color:var(--admin-muted)]">
                    Loading order...
                </div>
            ) : null}

            {actionError ? (
                <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {actionError}
                </div>
            ) : null}

            {order ? (
                <AdminSectionCard
                    actions={
                        <div className="flex flex-wrap gap-2">
                            <AdminStatusBadge value={order.type} />
                            <AdminStatusBadge value={order.status} />
                            <AdminStatusBadge value={order.paymentStatus} />
                            <AdminStatusBadge value={order.source} />
                        </div>
                    }
                    eyebrow="Order detail"
                    icon={ClipboardList}
                    title={order.customerName}
                >
                    <AdminOrderDetailContent
                        feedback={feedback}
                        isSubmitting={isSubmitting}
                        onApplyStatus={() =>
                            runAction(
                                (targetOrderId) => applyOrderStatus(targetOrderId, statusDraft),
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
                        order={order}
                        setStatusDraft={setStatusDraft}
                        statusDraft={statusDraft}
                        statusOptions={statusOptions}
                    />
                </AdminSectionCard>
            ) : !isReadDataLoading ? (
                <AdminEmptyState
                    action={
                        <Link
                            className="inline-flex items-center gap-2 rounded-2xl bg-[color:var(--admin-ink)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-black"
                            to="/admin/orders"
                        >
                            <ArrowLeft className="h-4.5 w-4.5" />
                            <span>Back to orders</span>
                        </Link>
                    }
                    description="Check the order list and try again."
                    title="Order not found"
                />
            ) : null}
        </div>
    );
}
