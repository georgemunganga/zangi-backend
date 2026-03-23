import { CheckCircle2, CircleOff, CreditCard, Download, Mail, RefreshCcw, ScanLine, Ticket } from 'lucide-react';
import { Link } from 'react-router-dom';
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
            <div className="mt-3 space-y-2 text-sm leading-6 text-[color:var(--admin-muted)]">{children}</div>
        </div>
    );
}

function normalizeStatus(value) {
    return String(value ?? '')
        .trim()
        .toLowerCase()
        .replace(/[\s-]+/g, '_');
}

function ActionButton({ children, disabled = false, icon: Icon, onClick, to }) {
    const content = (
        <>
            <Icon className="h-4.5 w-4.5" />
            <span>{children}</span>
        </>
    );

    if (to) {
        return (
            <Link
                className="inline-flex items-center justify-center gap-2 rounded-2xl border border-[color:var(--admin-border)] bg-white px-3 py-2.5 text-sm font-semibold text-[color:var(--admin-ink)] transition hover:border-[color:var(--admin-accent)] hover:text-[color:var(--admin-accent)]"
                to={to}
            >
                {content}
            </Link>
        );
    }

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
            {content}
        </button>
    );
}

export function AdminTicketDetailContent({
    feedback,
    isSubmitting = false,
    onDownload,
    onMarkUsed,
    onReissueTicket,
    onResendTicket,
    onVoidTicket,
    ticket,
}) {
    if (!ticket) {
        return null;
    }

    const normalizedStatus = normalizeStatus(ticket.status);
    const normalizedPaymentStatus = normalizeStatus(ticket.paymentStatus);
    const cannotMarkUsed =
        isSubmitting ||
        normalizedPaymentStatus !== 'paid' ||
        ['used', 'cancelled', 'voided', 'refunded', 'expired', 'pending', 'failed'].includes(normalizedStatus);
    const cannotVoid = isSubmitting || ['used', 'cancelled', 'voided', 'refunded'].includes(normalizedStatus);
    const cannotReissue =
        isSubmitting ||
        normalizedPaymentStatus !== 'paid' ||
        ['used', 'cancelled', 'voided', 'refunded'].includes(normalizedStatus);
    const cannotResend =
        isSubmitting ||
        normalizedPaymentStatus !== 'paid' ||
        ['cancelled', 'voided', 'refunded'].includes(normalizedStatus);

    return (
        <div className="space-y-5">
            <div className="flex flex-wrap gap-2 md:hidden">
                <AdminStatusBadge value={ticket.status} />
                <AdminStatusBadge value={ticket.paymentStatus} />
                <AdminStatusBadge value={ticket.source} />
            </div>

            {feedback ? (
                <div className="rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-accent-soft)] px-4 py-3 text-sm text-[color:var(--admin-accent)]">
                    {feedback}
                </div>
            ) : null}

            <DetailBlock icon={Ticket} title="Lifecycle">
                <p>Issued: {formatDateTime(ticket.issuedAt)}</p>
                <p>Used: {formatDateTime(ticket.usedAt)}</p>
                <p>Delivery: {ticket.deliveryMethod}</p>
            </DetailBlock>

            <DetailBlock icon={Mail} title="Buyer and contact">
                <p>{ticket.buyerName}</p>
                <p>{ticket.email || 'No email recorded'}</p>
                <p>{ticket.phone || 'No phone recorded'}</p>
            </DetailBlock>

            <DetailBlock icon={ScanLine} title="Event and pass">
                <p>{ticket.eventTitle}</p>
                <p>{ticket.eventDateLabel}</p>
                <p>{ticket.venue}</p>
                <div className="flex flex-wrap gap-2 pt-1">
                    <AdminStatusBadge value={ticket.ticketType} />
                </div>
            </DetailBlock>

            <DetailBlock icon={CreditCard} title="Payment">
                <p>{formatCurrency(ticket.amount, ticket.currency)}</p>
                <p>{ticket.paymentMethod}</p>
                <div className="flex flex-wrap gap-2 pt-1">
                    <AdminStatusBadge value={ticket.paymentStatus} />
                    <AdminStatusBadge value={ticket.source} />
                </div>
            </DetailBlock>

            <DetailBlock icon={RefreshCcw} title="Notes">
                <p>{ticket.notes || 'No notes recorded for this ticket.'}</p>
            </DetailBlock>

            <div className="space-y-3">
                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">
                    Quick actions
                </p>
                <div className="grid gap-2 sm:grid-cols-2">
                    <ActionButton icon={ScanLine} to="/admin/tickets/validation">
                        Validate
                    </ActionButton>
                    <ActionButton disabled={cannotMarkUsed} icon={CheckCircle2} onClick={onMarkUsed}>
                        Mark used
                    </ActionButton>
                    <ActionButton disabled={cannotVoid} icon={CircleOff} onClick={onVoidTicket}>
                        Void
                    </ActionButton>
                    <ActionButton disabled={cannotReissue} icon={RefreshCcw} onClick={onReissueTicket}>
                        Reissue
                    </ActionButton>
                    <ActionButton disabled={cannotResend} icon={Mail} onClick={onResendTicket}>
                        Resend ticket
                    </ActionButton>
                    <ActionButton disabled={isSubmitting || !onDownload} icon={Download} onClick={onDownload}>
                        Download
                    </ActionButton>
                </div>
                <p className="text-sm leading-6 text-[color:var(--admin-muted)]">
                    Resend is available for paid tickets with a customer email.
                </p>
            </div>
        </div>
    );
}
