import { ArrowLeft, Ticket } from 'lucide-react';
import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { downloadAdminTicketPass } from '../api/adminApiClient';
import { useAdminAuth } from '../auth/AdminAuthProvider';
import { AdminEmptyState } from '../components/AdminEmptyState';
import { AdminPageHeader } from '../components/AdminPageHeader';
import { AdminSectionCard } from '../components/AdminSectionCard';
import { AdminStatusBadge } from '../components/AdminStatusBadge';
import { AdminTicketDetailContent } from '../components/details/AdminTicketDetailContent';
import { useAdminMockData } from '../mocks/AdminMockDataProvider';

export function AdminTicketDetailPage() {
    const { ticketId } = useParams();
    const { accessToken, isAuthenticated } = useAdminAuth();
    const { isReadDataLoading, markUsed, readDataError, reissueTicket, resendTicket, tickets, voidTicket } = useAdminMockData();
    const [feedback, setFeedback] = useState('');
    const [actionError, setActionError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const ticket = tickets.find((entry) => entry.id === ticketId) ?? null;

    const runAction = async (handler, successText) => {
        if (!ticket) {
            return;
        }

        setActionError('');
        setIsSubmitting(true);

        try {
            const updated = await handler(ticket.id);

            if (updated) {
                setFeedback(successText(updated));
            }
        } catch (error) {
            setActionError(error.message);
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleDownload = async () => {
        if (!ticket || !accessToken || !isAuthenticated) {
            setActionError('Sign in to open the ticket.');
            return;
        }

        setActionError('');
        setIsSubmitting(true);

        try {
            await downloadAdminTicketPass(accessToken, ticket.id);
            setFeedback('Ticket opened.');
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
                        to="/admin/tickets"
                    >
                        <ArrowLeft className="h-4.5 w-4.5" />
                        <span>Back to tickets</span>
                    </Link>
                }
                title={ticket ? ticket.code : 'Ticket not found'}
            />

            {readDataError ? (
                <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {readDataError}
                </div>
            ) : null}

            {isReadDataLoading ? (
                <div className="rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-3 text-sm text-[color:var(--admin-muted)]">
                    Loading ticket...
                </div>
            ) : null}

            {actionError ? (
                <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {actionError}
                </div>
            ) : null}

            {ticket ? (
                <AdminSectionCard
                    actions={
                        <div className="flex flex-wrap gap-2">
                            <AdminStatusBadge value={ticket.status} />
                            <AdminStatusBadge value={ticket.paymentStatus} />
                            <AdminStatusBadge value={ticket.source} />
                        </div>
                    }
                    eyebrow="Ticket detail"
                    icon={Ticket}
                    title={ticket.eventTitle}
                >
                    <AdminTicketDetailContent
                        feedback={feedback}
                        isSubmitting={isSubmitting}
                        onDownload={handleDownload}
                        onMarkUsed={() => runAction(markUsed, (updatedTicket) => `${updatedTicket.code} marked used.`)}
                        onReissueTicket={() => runAction(reissueTicket, (updatedTicket) => `${updatedTicket.code} reissued.`)}
                        onResendTicket={() =>
                            runAction(resendTicket, (updatedTicket) => `Ticket email sent for ${updatedTicket.code}.`)
                        }
                        onVoidTicket={() => runAction(voidTicket, (updatedTicket) => `${updatedTicket.code} voided.`)}
                        ticket={ticket}
                    />
                </AdminSectionCard>
            ) : !isReadDataLoading ? (
                <AdminEmptyState
                    action={
                        <Link
                            className="inline-flex items-center gap-2 rounded-2xl bg-[color:var(--admin-ink)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-black"
                            to="/admin/tickets"
                        >
                            <ArrowLeft className="h-4.5 w-4.5" />
                            <span>Back to tickets</span>
                        </Link>
                    }
                    description="Check the ticket list and try again."
                    title="Ticket not found"
                />
            ) : null}
        </div>
    );
}
