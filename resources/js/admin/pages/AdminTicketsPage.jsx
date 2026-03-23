import { ArrowUpRight, BadgeCheck, Eye, ScanLine, Ticket, TriangleAlert } from 'lucide-react';
import { useDeferredValue, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { downloadAdminTicketPass } from '../api/adminApiClient';
import { useAdminAuth } from '../auth/AdminAuthProvider';
import { AdminDetailDrawer } from '../components/AdminDetailDrawer';
import { AdminEmptyState } from '../components/AdminEmptyState';
import { AdminFilterBar } from '../components/AdminFilterBar';
import { AdminPageHeader } from '../components/AdminPageHeader';
import { AdminSectionCard } from '../components/AdminSectionCard';
import { AdminStatCard } from '../components/AdminStatCard';
import { AdminStatusBadge } from '../components/AdminStatusBadge';
import { AdminTicketDetailContent } from '../components/details/AdminTicketDetailContent';
import { useAdminMockData } from '../mocks/AdminMockDataProvider';

function matchesQuery(ticket, query) {
    if (!query) {
        return true;
    }

    const haystack = [ticket.code, ticket.holderName, ticket.buyerName, ticket.email, ticket.phone]
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

function DetailActionLink({ children, icon: Icon, onClick, to }) {
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

export function AdminTicketsPage() {
    const { accessToken, isAuthenticated } = useAdminAuth();
    const { isReadDataLoading, markUsed, readDataError, reissueTicket, resendTicket, ticketEvents, tickets, voidTicket } = useAdminMockData();
    const [searchTerm, setSearchTerm] = useState('');
    const deferredSearch = useDeferredValue(searchTerm);
    const [filters, setFilters] = useState({
        eventSlug: 'all',
        ticketType: 'all',
        status: 'all',
        paymentStatus: 'all',
        source: 'all',
    });
    const [selectedTicketId, setSelectedTicketId] = useState(tickets[0]?.id ?? null);
    const [feedback, setFeedback] = useState('');
    const [actionError, setActionError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [showDrawer, setShowDrawer] = useState(false);

    const filteredTickets = tickets.filter((ticket) => {
        if (!matchesQuery(ticket, deferredSearch)) {
            return false;
        }

        if (filters.eventSlug !== 'all' && ticket.eventSlug !== filters.eventSlug) {
            return false;
        }

        if (filters.ticketType !== 'all' && ticket.ticketType !== filters.ticketType) {
            return false;
        }

        if (filters.status !== 'all' && ticket.status !== filters.status) {
            return false;
        }

        if (filters.paymentStatus !== 'all' && ticket.paymentStatus !== filters.paymentStatus) {
            return false;
        }

        if (filters.source !== 'all' && ticket.source !== filters.source) {
            return false;
        }

        return true;
    });

    useEffect(() => {
        if (filteredTickets.length === 0) {
            setSelectedTicketId(null);
            setShowDrawer(false);
            return;
        }

        const selectedStillVisible = filteredTickets.some((ticket) => ticket.id === selectedTicketId);

        if (!selectedStillVisible) {
            setSelectedTicketId(filteredTickets[0].id);
        }
    }, [filteredTickets, selectedTicketId]);

    useEffect(() => {
        setFeedback('');
        setActionError('');
    }, [selectedTicketId]);

    const selectedTicket = tickets.find((ticket) => ticket.id === selectedTicketId) ?? null;

    const eventOptions = ticketEvents.map((event) => ({ value: event.slug, label: event.title }));
    const ticketTypeOptions = [...new Set(tickets.map((ticket) => ticket.ticketType))].map((value) => ({ value, label: value }));
    const statusOptions = [...new Set(tickets.map((ticket) => ticket.status))].map((value) => ({ value, label: value }));
    const paymentStatusOptions = [...new Set(tickets.map((ticket) => ticket.paymentStatus))].map((value) => ({
        value,
        label: value,
    }));
    const sourceOptions = [...new Set(tickets.map((ticket) => ticket.source))].map((value) => ({ value, label: value }));

    const paidTicketCount = filteredTickets.filter((ticket) => ticket.paymentStatus === 'paid').length;
    const actionNeededCount = filteredTickets.filter((ticket) => ['pending', 'cancelled', 'voided'].includes(ticket.status)).length;
    const vipTicketCount = filteredTickets.filter((ticket) => ticket.ticketType === 'VIP').length;

    const openTicketDetail = (ticketId) => {
        setSelectedTicketId(ticketId);
        setShowDrawer(true);
    };

    const runAction = async (handler, successText) => {
        if (!selectedTicket) {
            return;
        }

        setActionError('');
        setIsSubmitting(true);

        try {
            const updated = await handler(selectedTicket.id);

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
        if (!selectedTicket || !accessToken || !isAuthenticated) {
            setActionError('Sign in to open the ticket.');
            return;
        }

        setActionError('');
        setIsSubmitting(true);

        try {
            await downloadAdminTicketPass(accessToken, selectedTicket.id);
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
                    <>
                        <Link
                            className="inline-flex items-center gap-2 rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm font-semibold text-[color:var(--admin-ink)] transition hover:border-[color:var(--admin-accent)] hover:text-[color:var(--admin-accent)]"
                            to="/admin/tickets/validation"
                        >
                            <ScanLine className="h-4.5 w-4.5" />
                            <span>Validation screen</span>
                        </Link>
                        <Link
                            className="inline-flex items-center gap-2 rounded-2xl bg-[color:var(--admin-ink)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-black"
                            to="/admin/manual-sales"
                        >
                            <ArrowUpRight className="h-4.5 w-4.5" />
                            <span>Manual sale</span>
                        </Link>
                    </>
                }
                title="Tickets"
            />

            {readDataError ? (
                <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {readDataError}
                </div>
            ) : null}

            {isReadDataLoading ? (
                <div className="rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-3 text-sm text-[color:var(--admin-muted)]">
                    Loading tickets...
                </div>
            ) : null}

            {actionError ? (
                <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {actionError}
                </div>
            ) : null}

            <section className="grid gap-4 md:grid-cols-3">
                <AdminStatCard icon={Ticket} label="Visible tickets" value={String(filteredTickets.length)} />
                <AdminStatCard icon={BadgeCheck} label="Settled tickets" value={String(paidTicketCount)} />
                <AdminStatCard icon={TriangleAlert} label="Needs action" value={String(actionNeededCount)} />
            </section>

            <AdminFilterBar
                secondaryChildren={
                    <>
                        <FilterField label="Ticket type">
                            <select
                                className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
                                onChange={(event) => setFilters((current) => ({ ...current, ticketType: event.target.value }))}
                                value={filters.ticketType}
                            >
                                <option value="all">All types</option>
                                {ticketTypeOptions.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </FilterField>
                        <FilterField label="Status">
                            <select
                                className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
                                onChange={(event) => setFilters((current) => ({ ...current, status: event.target.value }))}
                                value={filters.status}
                            >
                                <option value="all">All statuses</option>
                                {statusOptions.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </FilterField>
                        <FilterField label="Payment">
                            <select
                                className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
                                onChange={(event) => setFilters((current) => ({ ...current, paymentStatus: event.target.value }))}
                                value={filters.paymentStatus}
                            >
                                <option value="all">All payment states</option>
                                {paymentStatusOptions.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
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
                                {sourceOptions.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </FilterField>
                    </>
                }
                summary={`Showing ${filteredTickets.length} tickets.`}
            >
                <FilterField className="xl:col-span-3" label="Search">
                    <input
                        className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
                        onChange={(event) => setSearchTerm(event.target.value)}
                        placeholder="Ticket code, holder, buyer, email, phone"
                        type="search"
                        value={searchTerm}
                    />
                </FilterField>
                <FilterField label="Event">
                    <select
                        className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
                        onChange={(event) => setFilters((current) => ({ ...current, eventSlug: event.target.value }))}
                        value={filters.eventSlug}
                    >
                        <option value="all">All events</option>
                        {eventOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                </FilterField>
            </AdminFilterBar>

            <AdminSectionCard eyebrow="Operational queue" icon={Ticket} title="Ticket list">
                {filteredTickets.length === 0 ? (
                    <AdminEmptyState
                        description="Clear filters and try again."
                        title="No tickets found"
                    />
                ) : (
                    <>
                        <div className="hidden overflow-hidden rounded-[1.5rem] border border-[color:var(--admin-border)] md:block">
                            <div className="overflow-x-auto">
                                <table className="min-w-[1140px] divide-y divide-[color:var(--admin-border)]">
                                    <thead className="bg-[color:var(--admin-surface)]">
                                        <tr className="text-left text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">
                                            <th className="px-4 py-3">Ticket</th>
                                            <th className="px-4 py-3">Holder</th>
                                            <th className="px-4 py-3">Event</th>
                                            <th className="px-4 py-3">Status</th>
                                            <th className="px-4 py-3">Payment</th>
                                            <th className="px-4 py-3">Source</th>
                                            <th className="sticky right-0 bg-[color:var(--admin-surface)] px-4 py-3">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-[color:var(--admin-border)] bg-white">
                                        {filteredTickets.map((ticket) => {
                                            const isSelected = ticket.id === selectedTicketId && showDrawer;

                                            return (
                                                <tr
                                                    className={[
                                                        'cursor-pointer transition hover:bg-[color:var(--admin-surface)]',
                                                        isSelected ? 'bg-[color:var(--admin-accent-soft)]/50' : '',
                                                    ].join(' ')}
                                                    key={ticket.id}
                                                    onClick={() => openTicketDetail(ticket.id)}
                                                >
                                                    <td className="px-4 py-4 align-top">
                                                        <p className="font-semibold text-[color:var(--admin-ink)]">{ticket.code}</p>
                                                        <p className="mt-1 text-sm text-[color:var(--admin-muted)]">{ticket.ticketType}</p>
                                                    </td>
                                                    <td className="px-4 py-4 align-top">
                                                        <p className="font-medium text-[color:var(--admin-ink)]">{ticket.holderName}</p>
                                                        <p className="mt-1 text-sm text-[color:var(--admin-muted)]">{ticket.email}</p>
                                                    </td>
                                                    <td className="px-4 py-4 align-top">
                                                        <p className="font-medium text-[color:var(--admin-ink)]">{ticket.eventTitle}</p>
                                                        <p className="mt-1 text-sm text-[color:var(--admin-muted)]">{ticket.eventDateLabel}</p>
                                                    </td>
                                                    <td className="px-4 py-4 align-top">
                                                        <AdminStatusBadge value={ticket.status} />
                                                    </td>
                                                    <td className="px-4 py-4 align-top">
                                                        <AdminStatusBadge value={ticket.paymentStatus} />
                                                    </td>
                                                    <td className="px-4 py-4 align-top">
                                                        <AdminStatusBadge value={ticket.source} />
                                                    </td>
                                                    <td className="sticky right-0 bg-white px-4 py-4 align-top">
                                                        <div className="flex flex-wrap gap-2">
                                                            <DetailActionLink
                                                                icon={Eye}
                                                                onClick={(event) => {
                                                                    event.stopPropagation();
                                                                    openTicketDetail(ticket.id);
                                                                }}
                                                            >
                                                                Open
                                                            </DetailActionLink>
                                                            <DetailActionLink
                                                                icon={ScanLine}
                                                                onClick={(event) => event.stopPropagation()}
                                                                to="/admin/tickets/validation"
                                                            >
                                                                Validate
                                                            </DetailActionLink>
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
                            {filteredTickets.map((ticket) => (
                                <Link
                                    className="block rounded-[1.5rem] border border-[color:var(--admin-border)] bg-white p-4 transition hover:border-[color:var(--admin-accent)] hover:bg-[color:var(--admin-surface)]"
                                    key={ticket.id}
                                    to={`/admin/tickets/${ticket.id}`}
                                >
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p className="font-semibold text-[color:var(--admin-ink)]">{ticket.code}</p>
                                            <p className="mt-1 text-sm text-[color:var(--admin-muted)]">{ticket.ticketType}</p>
                                        </div>
                                        <AdminStatusBadge value={ticket.status} />
                                    </div>
                                    <p className="mt-3 text-sm font-medium text-[color:var(--admin-ink)]">{ticket.holderName}</p>
                                    <p className="mt-1 text-sm text-[color:var(--admin-muted)]">{ticket.eventTitle}</p>
                                    <div className="mt-3 flex flex-wrap gap-2">
                                        <AdminStatusBadge value={ticket.paymentStatus} />
                                        <AdminStatusBadge value={ticket.source} />
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </>
                )}
            </AdminSectionCard>

            <AdminSectionCard eyebrow="Highlights" icon={TriangleAlert} title="Ticket summary">
                <div className="grid gap-3 md:grid-cols-3">
                    <div className="rounded-2xl bg-[color:var(--admin-surface)] px-4 py-4">
                        <p className="text-sm font-semibold text-[color:var(--admin-ink)]">
                            {filteredTickets.filter((ticket) => ticket.status === 'used').length}
                        </p>
                        <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">Used tickets</p>
                    </div>
                    <div className="rounded-2xl bg-[color:var(--admin-surface)] px-4 py-4">
                        <p className="text-sm font-semibold text-[color:var(--admin-ink)]">
                            {filteredTickets.filter((ticket) => ticket.source === 'admin_manual').length}
                        </p>
                        <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">Manual tickets</p>
                    </div>
                    <div className="rounded-2xl bg-[color:var(--admin-surface)] px-4 py-4">
                        <p className="text-sm font-semibold text-[color:var(--admin-ink)]">{vipTicketCount}</p>
                        <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">VIP tickets</p>
                    </div>
                </div>
            </AdminSectionCard>

            <AdminDetailDrawer
                badges={
                    selectedTicket
                        ? [
                              <AdminStatusBadge key="status" value={selectedTicket.status} />,
                              <AdminStatusBadge key="payment" value={selectedTicket.paymentStatus} />,
                              <AdminStatusBadge key="source" value={selectedTicket.source} />,
                          ]
                        : null
                }
                description={selectedTicket ? `${selectedTicket.holderName} | ${selectedTicket.eventDateLabel}` : ''}
                eyebrow="Ticket detail"
                isOpen={showDrawer && Boolean(selectedTicket)}
                onClose={() => setShowDrawer(false)}
                title={selectedTicket?.code ?? 'Ticket detail'}
            >
                <AdminTicketDetailContent
                    feedback={feedback}
                    isSubmitting={isSubmitting}
                    onDownload={handleDownload}
                    onMarkUsed={() => runAction(markUsed, (ticket) => `${ticket.code} marked used.`)}
                    onReissueTicket={() => runAction(reissueTicket, (ticket) => `${ticket.code} reissued.`)}
                    onResendTicket={() => runAction(resendTicket, (ticket) => `Ticket email sent for ${ticket.code}.`)}
                    onVoidTicket={() => runAction(voidTicket, (ticket) => `${ticket.code} voided.`)}
                    ticket={selectedTicket}
                />
            </AdminDetailDrawer>
        </div>
    );
}
