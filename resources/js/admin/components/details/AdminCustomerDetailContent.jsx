import { Clock3, NotebookPen, ShoppingBag, Ticket, UserRound } from 'lucide-react';
import { AdminEmptyState } from '../AdminEmptyState';
import { AdminStatusBadge } from '../AdminStatusBadge';
import { formatDateTime } from '../../mocks/adminMockData';
import { formatCurrency } from '../../mocks/adminOrderMockData';

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

export function AdminCustomerDetailContent({ customer }) {
    if (!customer) {
        return null;
    }

    return (
        <div className="space-y-5">
            <div className="flex flex-wrap gap-2 md:hidden">
                <AdminStatusBadge value={customer.customerType} />
                <AdminStatusBadge value={customer.relationshipType} />
            </div>

            <DetailBlock icon={UserRound} title="Identity and contact">
                <div className="space-y-2 text-sm leading-6 text-[color:var(--admin-muted)]">
                    <p className="font-semibold text-[color:var(--admin-ink)]">{customer.name}</p>
                    <p>{customer.email || 'No email recorded'}</p>
                    <p>{customer.phone || 'No phone recorded'}</p>
                    <p>Last activity: {formatDateTime(customer.lastActivityAt)}</p>
                </div>
            </DetailBlock>

            <DetailBlock icon={Clock3} title="Type and relationship">
                <div className="flex flex-wrap gap-2">
                    <AdminStatusBadge value={customer.customerType} />
                    <AdminStatusBadge value={customer.relationshipType} />
                </div>
                <p className="text-sm leading-6 text-[color:var(--admin-muted)]">
                    Total settled spend: {formatCurrency(customer.totalSpent)}
                </p>
            </DetailBlock>

            <DetailBlock icon={NotebookPen} title="Notes and tags">
                <div className="flex flex-wrap gap-2">
                    {customer.tags.length > 0 ? (
                        customer.tags.map((tag) => <AdminStatusBadge key={`${customer.id}-${tag}`} label={tag} value={tag} />)
                    ) : (
                        <span className="rounded-full border border-dashed border-[color:var(--admin-border)] px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.16em] text-[color:var(--admin-muted)]">
                            No tags yet
                        </span>
                    )}
                </div>

                {customer.notes.length > 0 ? (
                    <div className="space-y-2">
                        {customer.notes.slice(0, 4).map((note, index) => (
                            <p
                                className="rounded-2xl bg-white px-4 py-3 text-sm leading-6 text-[color:var(--admin-muted)]"
                                key={`${customer.id}-note-${index}`}
                            >
                                {note}
                            </p>
                        ))}
                    </div>
                ) : (
                    <p className="text-sm leading-6 text-[color:var(--admin-muted)]">No notes yet.</p>
                )}
            </DetailBlock>

            <DetailBlock icon={ShoppingBag} title="Purchase history">
                {customer.purchaseHistory.length > 0 ? (
                    <div className="space-y-2">
                        {customer.purchaseHistory.map((order) => (
                            <div className="rounded-2xl bg-white px-4 py-3" key={order.id}>
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p className="text-sm font-semibold text-[color:var(--admin-ink)]">{order.reference}</p>
                                        <p className="mt-1 text-sm text-[color:var(--admin-muted)]">
                                            {order.lines.map((line) => line.label).join(' | ')}
                                        </p>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm font-semibold text-[color:var(--admin-ink)]">
                                            {formatCurrency(order.total, order.currency)}
                                        </p>
                                        <p className="mt-1 text-sm text-[color:var(--admin-muted)]">
                                            {formatDateTime(order.createdAt)}
                                        </p>
                                    </div>
                                </div>
                                <div className="mt-3 flex flex-wrap gap-2">
                                    <AdminStatusBadge value={order.status} />
                                    <AdminStatusBadge value={order.paymentStatus} />
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <AdminEmptyState
                        description="No orders yet."
                        title="No purchase history"
                    />
                )}
            </DetailBlock>

            <DetailBlock icon={Ticket} title="Attendance history">
                {customer.attendanceHistory.length > 0 ? (
                    <div className="space-y-2">
                        {customer.attendanceHistory.map((ticket) => (
                            <div className="rounded-2xl bg-white px-4 py-3" key={ticket.id}>
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p className="text-sm font-semibold text-[color:var(--admin-ink)]">{ticket.code}</p>
                                        <p className="mt-1 text-sm text-[color:var(--admin-muted)]">
                                            {ticket.eventTitle} | {ticket.ticketType}
                                        </p>
                                    </div>
                                    <p className="text-sm text-[color:var(--admin-muted)]">{ticket.eventDateLabel}</p>
                                </div>
                                <div className="mt-3 flex flex-wrap gap-2">
                                    <AdminStatusBadge value={ticket.status} />
                                    <AdminStatusBadge value={ticket.paymentStatus} />
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <AdminEmptyState
                        description="No ticket activity yet."
                        title="No attendance history"
                    />
                )}
            </DetailBlock>
        </div>
    );
}
