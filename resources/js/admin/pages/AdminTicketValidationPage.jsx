import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { AdminPageHeader } from '../components/AdminPageHeader';
import { AdminSectionCard } from '../components/AdminSectionCard';
import { AdminStatusBadge } from '../components/AdminStatusBadge';
import { useAdminMockData } from '../mocks/AdminMockDataProvider';
import { formatDateTime } from '../mocks/adminMockData';

function FieldLabel({ children }) {
    return (
        <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">
            {children}
        </span>
    );
}

export function AdminTicketValidationPage() {
    const { ticketEvents, validationAttempts, validateTicket, markUsed } = useAdminMockData();
    const [selectedEventSlug, setSelectedEventSlug] = useState(ticketEvents[0]?.slug ?? '');
    const [ticketCode, setTicketCode] = useState('');
    const [result, setResult] = useState(null);
    const [actionError, setActionError] = useState('');
    const [isValidating, setIsValidating] = useState(false);
    const [isMarkingUsed, setIsMarkingUsed] = useState(false);

    const selectedEvent = ticketEvents.find((event) => event.slug === selectedEventSlug) ?? ticketEvents[0];

    useEffect(() => {
        const selectedEventExists = ticketEvents.some((event) => event.slug === selectedEventSlug);

        if (!selectedEventExists && ticketEvents[0]?.slug) {
            setSelectedEventSlug(ticketEvents[0].slug);
        }
    }, [selectedEventSlug, ticketEvents]);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setActionError('');

        const trimmedCode = ticketCode.trim();

        if (!trimmedCode) {
            setResult({
                code: '',
                state: 'invalid',
                ticket: null,
                checkedAt: '2026-03-19T18:20:00',
                message: 'Enter a ticket code before validating.',
            });
            return;
        }

        setIsValidating(true);

        try {
            const nextResult = await validateTicket(trimmedCode, selectedEventSlug);
            setResult(nextResult);
        } catch (error) {
            setActionError(error.message);
        } finally {
            setIsValidating(false);
        }
    };

    const handleMarkUsed = async () => {
        if (!result?.ticket) {
            return;
        }

        setActionError('');
        setIsMarkingUsed(true);

        try {
            const updatedTicket = await markUsed(result.ticket.id);

            if (!updatedTicket) {
                return;
            }

            setResult({
                code: updatedTicket.code,
                state: 'already_used',
                ticket: updatedTicket,
                checkedAt: updatedTicket.usedAt,
                message: `Ticket marked as used at ${formatDateTime(updatedTicket.usedAt)}.`,
            });
        } catch (error) {
            setActionError(error.message);
        } finally {
            setIsMarkingUsed(false);
        }
    };

    return (
        <div className="min-w-0 space-y-6">
            <AdminPageHeader title="Ticket Validation" description="Check a ticket code before entry." />

            {actionError ? (
                <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {actionError}
                </div>
            ) : null}

            <section className="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,0.92fr)_minmax(0,1.08fr)]">
                <AdminSectionCard eyebrow="Manual First" title="Validate by ticket code">
                    <form className="space-y-4" onSubmit={handleSubmit}>
                        <label className="block">
                            <FieldLabel>Event context</FieldLabel>
                            <select
                                className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
                                onChange={(event) => setSelectedEventSlug(event.target.value)}
                                value={selectedEventSlug}
                            >
                                {ticketEvents.map((option) => (
                                    <option key={option.slug} value={option.slug}>
                                        {option.title}
                                    </option>
                                ))}
                            </select>
                        </label>

                        <label className="block">
                            <FieldLabel>Ticket code</FieldLabel>
                            <input
                                autoFocus
                                className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-4 text-base outline-none transition focus:border-[color:var(--admin-accent)]"
                                onChange={(event) => setTicketCode(event.target.value.toUpperCase())}
                                placeholder="ZAN-TK-1001"
                                type="text"
                                value={ticketCode}
                            />
                        </label>

                        <button
                            className="inline-flex w-full items-center justify-center rounded-2xl bg-[color:var(--admin-ink)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-black disabled:cursor-not-allowed disabled:opacity-70"
                            disabled={isValidating}
                            type="submit"
                        >
                            {isValidating ? 'Validating...' : 'Validate ticket'}
                        </button>
                    </form>

                    <div className="mt-6 rounded-[1.5rem] bg-stone-50 px-4 py-4">
                        <p className="text-sm font-semibold text-[color:var(--admin-ink)]">{selectedEvent.title}</p>
                        <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">
                            {selectedEvent.dateLabel} - {selectedEvent.venue}
                        </p>
                        <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                            Check the event before admitting a guest.
                        </p>
                    </div>
                </AdminSectionCard>

                <AdminSectionCard eyebrow="Result" title="Validation outcome">
                    {result ? (
                        <div className="space-y-5">
                            <div className="flex flex-wrap items-center gap-3">
                                <AdminStatusBadge value={result.state} />
                                {result.ticket ? <AdminStatusBadge value={result.ticket.status} /> : null}
                                {result.ticket ? <AdminStatusBadge value={result.ticket.paymentStatus} /> : null}
                            </div>

                            <div className="rounded-[1.5rem] border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-4">
                                <p className="text-sm font-semibold text-[color:var(--admin-ink)]">
                                    {result.code || 'No code submitted yet'}
                                </p>
                                <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">{result.message}</p>
                            </div>

                            {result.ticket ? (
                                <div className="grid gap-3 md:grid-cols-2">
                                    <div className="rounded-2xl bg-stone-50 px-4 py-4">
                                        <p className="text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">
                                            Ticket holder
                                        </p>
                                        <p className="mt-3 text-sm font-semibold text-[color:var(--admin-ink)]">
                                            {result.ticket.holderName}
                                        </p>
                                        <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">
                                            {result.ticket.email}
                                        </p>
                                        <p className="text-sm leading-6 text-[color:var(--admin-muted)]">
                                            {result.ticket.phone}
                                        </p>
                                    </div>

                                    <div className="rounded-2xl bg-stone-50 px-4 py-4">
                                        <p className="text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">
                                            Event
                                        </p>
                                        <p className="mt-3 text-sm font-semibold text-[color:var(--admin-ink)]">
                                            {result.ticket.eventTitle}
                                        </p>
                                        <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">
                                            {result.ticket.eventDateLabel} - {result.ticket.venue}
                                        </p>
                                        <p className="text-sm leading-6 text-[color:var(--admin-muted)]">
                                            {result.ticket.ticketType} - {result.ticket.deliveryMethod}
                                        </p>
                                    </div>

                                    <div className="rounded-2xl bg-stone-50 px-4 py-4">
                                        <p className="text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">
                                            Payment
                                        </p>
                                        <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                                            {result.ticket.currency} {result.ticket.amount.toLocaleString()} - {result.ticket.paymentMethod}
                                        </p>
                                        <p className="text-sm leading-6 text-[color:var(--admin-muted)]">
                                            Issued: {formatDateTime(result.ticket.issuedAt)}
                                        </p>
                                        <p className="text-sm leading-6 text-[color:var(--admin-muted)]">
                                            Used: {formatDateTime(result.ticket.usedAt)}
                                        </p>
                                    </div>

                                    <div className="rounded-2xl bg-stone-50 px-4 py-4">
                                        <p className="text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">
                                            Notes
                                        </p>
                                        <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                                            {result.ticket.notes}
                                        </p>
                                    </div>
                                </div>
                            ) : null}

                            <div className="flex flex-wrap gap-2">
                                {result.state === 'valid' ? (
                                    <button
                                        className="inline-flex items-center justify-center rounded-2xl bg-[color:var(--admin-ink)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-black disabled:cursor-not-allowed disabled:opacity-70"
                                        disabled={isMarkingUsed}
                                        onClick={handleMarkUsed}
                                        type="button"
                                    >
                                        {isMarkingUsed ? 'Marking used...' : 'Admit and mark used'}
                                    </button>
                                ) : null}
                                <Link
                                    className="inline-flex items-center justify-center rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm font-semibold text-[color:var(--admin-ink)] transition hover:border-[color:var(--admin-accent)] hover:text-[color:var(--admin-accent)]"
                                    to="/admin/tickets"
                                >
                                    Open tickets list
                                </Link>
                            </div>
                        </div>
                    ) : (
                        <div className="rounded-[1.5rem] border border-dashed border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-6 py-8">
                            <p className="text-lg font-semibold text-[color:var(--admin-ink)]">Awaiting ticket code</p>
                            <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                                Enter a code to continue.
                            </p>
                        </div>
                    )}
                </AdminSectionCard>
            </section>

            <section className="grid min-w-0 gap-4 xl:grid-cols-[0.9fr_1.1fr]">
                <AdminSectionCard eyebrow="Recent" title="Validation activity">
                    <div className="space-y-3">
                        {validationAttempts.map((attempt) => (
                            <div
                                className="rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-4"
                                key={attempt.id}
                            >
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <p className="text-sm font-semibold text-[color:var(--admin-ink)]">{attempt.code}</p>
                                    <AdminStatusBadge value={attempt.state} />
                                </div>
                                <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">{attempt.eventTitle}</p>
                                <p className="text-sm leading-6 text-[color:var(--admin-muted)]">
                                    {formatDateTime(attempt.checkedAt)}
                                </p>
                            </div>
                        ))}
                    </div>
                </AdminSectionCard>

                <AdminSectionCard eyebrow="Notes" title="Gate notes">
                    <div className="grid gap-3 md:grid-cols-2">
                        <div className="rounded-2xl bg-stone-50 px-4 py-4">
                            <p className="text-sm font-semibold text-[color:var(--admin-ink)]">Event check</p>
                            <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">
                                Make sure the selected event matches the guest ticket.
                            </p>
                        </div>
                        <div className="rounded-2xl bg-stone-50 px-4 py-4">
                            <p className="text-sm font-semibold text-[color:var(--admin-ink)]">Admit guest</p>
                            <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">
                                Use Admit and mark used once the ticket is confirmed.
                            </p>
                        </div>
                    </div>
                </AdminSectionCard>
            </section>
        </div>
    );
}
