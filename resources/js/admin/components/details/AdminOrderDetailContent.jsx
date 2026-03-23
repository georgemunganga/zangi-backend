import {
    BadgeCheck,
    CreditCard,
    FileText,
    HandCoins,
    Mail,
    Receipt,
    ScrollText,
    ShoppingBag,
    UserRound,
    XCircle,
} from 'lucide-react';
import { AdminStatusBadge } from '../AdminStatusBadge';
import { formatDateTime } from '../../mocks/adminMockData';
import { formatCurrency } from '../../mocks/adminOrderMockData';
import { getOrderDeliveryMeta } from '../../utils/orderDelivery';

function DetailBlock({ icon: Icon, title, children }) {
    return (
        <div className="rounded-[1.45rem] border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-4">
            <div className="flex items-center gap-2">
                <Icon className="h-4.5 w-4.5 text-[color:var(--admin-accent)]" />
                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">
                    {title}
                </p>
            </div>
            <div className="mt-3 space-y-3">{children}</div>
        </div>
    );
}

function ActionButton({ children, disabled = false, icon: Icon, onClick }) {
    return (
        <button
            className={[
                'inline-flex items-center justify-center gap-2 rounded-2xl border px-3 py-2.5 text-sm font-semibold transition',
                disabled
                    ? 'cursor-not-allowed border-stone-200 bg-stone-100 text-stone-400'
                    : 'border-[color:var(--admin-border)] bg-white text-[color:var(--admin-ink)] hover:border-[color:var(--admin-accent)] hover:text-[color:var(--admin-accent)]',
            ].join(' ')}
            disabled={disabled}
            onClick={onClick}
            type="button"
        >
            <Icon className="h-4.5 w-4.5" />
            <span>{children}</span>
        </button>
    );
}

export function AdminOrderDetailContent({
    feedback,
    isSubmitting = false,
    onApplyStatus,
    onCancelOrder,
    onConfirmPayment,
    onPrintReceipt,
    onResendDelivery,
    onRefundOrder,
    order,
    setStatusDraft,
    statusDraft,
    statusOptions,
}) {
    if (!order) {
        return null;
    }

    const deliveryMeta = getOrderDeliveryMeta(order);

    return (
        <div className="space-y-5">
            <div className="flex flex-wrap gap-2 md:hidden">
                <AdminStatusBadge value={order.type} />
                <AdminStatusBadge value={order.status} />
                <AdminStatusBadge value={order.paymentStatus} />
                <AdminStatusBadge value={order.source} />
            </div>

            {feedback ? (
                <div className="rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-accent-soft)] px-4 py-3 text-sm text-[color:var(--admin-accent)]">
                    {feedback}
                </div>
            ) : null}

            <DetailBlock icon={ScrollText} title="Summary">
                <div className="flex items-start justify-between gap-3">
                    <div className="space-y-1 text-sm leading-6 text-[color:var(--admin-muted)]">
                        <p>{order.reference}</p>
                        <p>{formatDateTime(order.createdAt)}</p>
                        <p>{formatCurrency(order.total, order.currency)}</p>
                    </div>
                    <div className="flex flex-wrap justify-end gap-2">
                        <AdminStatusBadge value={order.type} />
                        <AdminStatusBadge value={order.status} />
                    </div>
                </div>
            </DetailBlock>

            <DetailBlock icon={UserRound} title="Customer">
                <div className="space-y-2 text-sm leading-6 text-[color:var(--admin-muted)]">
                    <p>{order.customerName}</p>
                    <p>{order.email || 'No email recorded'}</p>
                    <p>{order.phone || 'No phone recorded'}</p>
                    <div className="flex flex-wrap gap-2 pt-1">
                        <AdminStatusBadge value={order.customerType} />
                        <AdminStatusBadge value={order.relationshipType} />
                    </div>
                </div>
            </DetailBlock>

            <DetailBlock icon={ShoppingBag} title="Line items">
                {order.lines.map((line) => (
                    <div className="rounded-2xl bg-white px-4 py-3" key={`${order.id}-${line.label}`}>
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="text-sm font-semibold text-[color:var(--admin-ink)]">{line.label}</p>
                                <p className="mt-1 text-sm leading-6 text-[color:var(--admin-muted)]">
                                    Qty {line.quantity}
                                </p>
                            </div>
                            <div className="text-right">
                                <p className="text-sm font-semibold text-[color:var(--admin-ink)]">
                                    {formatCurrency(line.unitPrice, order.currency)}
                                </p>
                                <p className="mt-1 text-sm text-[color:var(--admin-muted)]">each</p>
                            </div>
                        </div>
                        <div className="mt-3 flex flex-wrap gap-2">
                            <AdminStatusBadge value={line.kind} />
                        </div>
                    </div>
                ))}
            </DetailBlock>

            <DetailBlock icon={CreditCard} title="Payment state">
                <div className="space-y-2 text-sm leading-6 text-[color:var(--admin-muted)]">
                    <p>{order.paymentMethod}</p>
                    <div className="flex flex-wrap gap-2">
                        <AdminStatusBadge value={order.paymentStatus} />
                        <AdminStatusBadge value={order.source} />
                    </div>
                </div>
            </DetailBlock>

            <DetailBlock icon={Receipt} title="Fulfillment">
                <p className="text-sm leading-6 text-[color:var(--admin-muted)]">{order.fulfillment}</p>
            </DetailBlock>

            <DetailBlock icon={FileText} title="Notes">
                <p className="text-sm leading-6 text-[color:var(--admin-muted)]">{order.notes || 'No notes recorded.'}</p>
            </DetailBlock>

            <div className="space-y-3">
                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">
                    Common actions
                </p>
                <div className="grid gap-2 sm:grid-cols-2">
                    <ActionButton
                        disabled={isSubmitting || order.paymentStatus === 'paid' || order.paymentStatus === 'refunded'}
                        icon={BadgeCheck}
                        onClick={onConfirmPayment}
                    >
                        Confirm payment
                    </ActionButton>
                    <ActionButton
                        disabled={isSubmitting || order.status === 'cancelled'}
                        icon={XCircle}
                        onClick={onCancelOrder}
                    >
                        Cancel order
                    </ActionButton>
                    <ActionButton
                        disabled={isSubmitting || order.paymentStatus !== 'paid'}
                        icon={HandCoins}
                        onClick={onRefundOrder}
                    >
                        Refund
                    </ActionButton>
                    <ActionButton disabled={isSubmitting || deliveryMeta.disabled} icon={Mail} onClick={onResendDelivery}>
                        {deliveryMeta.actionLabel}
                    </ActionButton>
                    <ActionButton disabled={isSubmitting} icon={Receipt} onClick={onPrintReceipt}>
                        Print / invoice
                    </ActionButton>
                </div>
                <p className="text-sm leading-6 text-[color:var(--admin-muted)]">{deliveryMeta.helperText}</p>
            </div>

            <div className="rounded-[1.45rem] border border-[color:var(--admin-border)] bg-white p-4">
                <p className="text-sm font-semibold text-[color:var(--admin-ink)]">Status update</p>
                <div className="mt-3 flex flex-col gap-2 sm:flex-row">
                    <select
                        className="min-w-0 flex-1 rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)] disabled:cursor-not-allowed disabled:bg-stone-100"
                        disabled={isSubmitting}
                        onChange={(event) => setStatusDraft(event.target.value)}
                        value={statusDraft}
                    >
                        {statusOptions.map((value) => (
                            <option key={value} value={value}>
                                {value}
                            </option>
                        ))}
                    </select>
                    <button
                        className="inline-flex items-center justify-center rounded-2xl bg-[color:var(--admin-ink)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-black disabled:cursor-not-allowed disabled:opacity-70"
                        disabled={isSubmitting}
                        onClick={onApplyStatus}
                        type="button"
                    >
                        {isSubmitting ? 'Applying...' : 'Apply'}
                    </button>
                </div>
            </div>
        </div>
    );
}
