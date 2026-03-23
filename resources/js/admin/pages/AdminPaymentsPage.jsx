import { useDeferredValue, useEffect, useState } from 'react';
import { openAdminOrderDocument } from '../api/adminApiClient';
import { useAdminAuth } from '../auth/AdminAuthProvider';
import { AdminEmptyState } from '../components/AdminEmptyState';
import { AdminFilterBar } from '../components/AdminFilterBar';
import { AdminPageHeader } from '../components/AdminPageHeader';
import { AdminSectionCard } from '../components/AdminSectionCard';
import { AdminStatusBadge } from '../components/AdminStatusBadge';
import { useAdminMockData } from '../mocks/AdminMockDataProvider';
import { formatCurrency } from '../mocks/adminOrderMockData';
import { formatDateTime } from '../mocks/adminMockData';

function matchesPayment(payment, query) {
    if (!query) {
        return true;
    }

    const haystack = [payment.reference, payment.orderReference, payment.customerName, payment.email]
        .join(' ')
        .toLowerCase();

    return haystack.includes(query.toLowerCase());
}

function SummaryCard({ label, value, detail }) {
    return (
        <div className="rounded-[1.5rem] border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-strong)] p-4 shadow-[0_14px_34px_rgba(23,33,38,0.05)]">
            <p className="text-sm font-medium text-[color:var(--admin-muted)]">{label}</p>
            <p className="mt-3 text-2xl font-semibold tracking-tight text-[color:var(--admin-ink)]">{value}</p>
            {detail ? <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">{detail}</p> : null}
        </div>
    );
}

function ActionButton({ children, disabled = false, onClick }) {
    return (
        <button
            className={[
                'inline-flex items-center justify-center rounded-2xl border px-3 py-2 text-sm font-semibold transition',
                disabled
                    ? 'cursor-not-allowed border-stone-200 bg-stone-100 text-stone-400'
                    : 'border-[color:var(--admin-border)] bg-white text-[color:var(--admin-ink)] hover:border-[color:var(--admin-accent)] hover:text-[color:var(--admin-accent)]',
            ].join(' ')}
            disabled={disabled}
            onClick={onClick}
            type="button"
        >
            {children}
        </button>
    );
}

export function AdminPaymentsPage() {
    const { accessToken, isAuthenticated } = useAdminAuth();
    const {
        attachPaymentNote,
        isReadDataLoading,
        markPaymentFailed,
        payments,
        readDataError,
        reconcilePayment,
        refundPayment,
    } = useAdminMockData();
    const [searchTerm, setSearchTerm] = useState('');
    const deferredSearch = useDeferredValue(searchTerm);
    const [filters, setFilters] = useState({
        status: 'all',
        method: 'all',
        source: 'all',
    });
    const [selectedPaymentId, setSelectedPaymentId] = useState(payments[0]?.id ?? null);
    const [noteDraft, setNoteDraft] = useState('');
    const [feedback, setFeedback] = useState('');
    const [actionError, setActionError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const filteredPayments = payments.filter((payment) => {
        if (!matchesPayment(payment, deferredSearch)) {
            return false;
        }

        if (filters.status !== 'all' && payment.status !== filters.status) {
            return false;
        }

        if (filters.method !== 'all' && payment.method !== filters.method) {
            return false;
        }

        if (filters.source !== 'all' && payment.source !== filters.source) {
            return false;
        }

        return true;
    });

    useEffect(() => {
        if (filteredPayments.length === 0) {
            setSelectedPaymentId(null);
            return;
        }

        const stillVisible = filteredPayments.some((payment) => payment.id === selectedPaymentId);

        if (!stillVisible) {
            setSelectedPaymentId(filteredPayments[0].id);
        }
    }, [filteredPayments, selectedPaymentId]);

    const selectedPayment = filteredPayments.find((payment) => payment.id === selectedPaymentId) ?? null;

    useEffect(() => {
        setNoteDraft(selectedPayment?.notes ?? '');
    }, [selectedPayment]);

    const statusOptions = [...new Set(payments.map((payment) => payment.status))];
    const methodOptions = [...new Set(payments.map((payment) => payment.method))];
    const sourceOptions = [...new Set(payments.map((payment) => payment.source))];

    const paidCount = filteredPayments.filter((payment) => payment.status === 'paid').length;
    const pendingCount = filteredPayments.filter((payment) => payment.status === 'pending').length;
    const runAction = async (handler, successText) => {
        if (!selectedPayment) {
            return;
        }

        setActionError('');
        setIsSubmitting(true);

        try {
            const updatedPayment = await handler(selectedPayment.id);

            if (updatedPayment) {
                setFeedback(successText(updatedPayment));
            }
        } catch (error) {
            setActionError(error.message);
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleAttachNote = async () => {
        if (!selectedPayment || !noteDraft.trim()) {
            return;
        }

        setActionError('');
        setIsSubmitting(true);

        try {
            const updatedPayment = await attachPaymentNote(selectedPayment.id, noteDraft.trim());

            if (updatedPayment) {
                setFeedback('Note saved.');
            }
        } catch (error) {
            setActionError(error.message);
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleOpenReceipt = async () => {
        if (!selectedPayment || !accessToken || !isAuthenticated) {
            setActionError('Sign in to open the receipt.');
            return;
        }

        setActionError('');
        setIsSubmitting(true);

        try {
            await openAdminOrderDocument(accessToken, selectedPayment.orderId);
            setFeedback('Receipt opened.');
        } catch (error) {
            setActionError(error.message);
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="min-w-0 space-y-6">
            <AdminPageHeader title="Payments" />

            {readDataError ? (
                <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {readDataError}
                </div>
            ) : null}

            {isReadDataLoading ? (
                <div className="rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-3 text-sm text-[color:var(--admin-muted)]">
                    Loading payments...
                </div>
            ) : null}

            {actionError ? (
                <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {actionError}
                </div>
            ) : null}

            <section className="grid gap-4 md:grid-cols-3">
                <SummaryCard label="Payments" value={String(filteredPayments.length)} />
                <SummaryCard label="Paid" value={String(paidCount)} />
                <SummaryCard label="Pending" value={String(pendingCount)} />
            </section>

            <AdminFilterBar summary={`Showing ${filteredPayments.length} payments.`}>
                <label className="block xl:col-span-3">
                    <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">
                        Search payments
                    </span>
                    <input
                        className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
                        onChange={(event) => setSearchTerm(event.target.value)}
                        placeholder="Payment ref, order ref, customer"
                        type="search"
                        value={searchTerm}
                    />
                </label>

                <label className="block">
                    <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">
                        Status
                    </span>
                    <select
                        className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
                        onChange={(event) => setFilters((current) => ({ ...current, status: event.target.value }))}
                        value={filters.status}
                    >
                        <option value="all">All statuses</option>
                        {statusOptions.map((status) => (
                            <option key={status} value={status}>
                                {status}
                            </option>
                        ))}
                    </select>
                </label>

                <label className="block">
                    <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">
                        Method
                    </span>
                    <select
                        className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
                        onChange={(event) => setFilters((current) => ({ ...current, method: event.target.value }))}
                        value={filters.method}
                    >
                        <option value="all">All methods</option>
                        {methodOptions.map((method) => (
                            <option key={method} value={method}>
                                {method}
                            </option>
                        ))}
                    </select>
                </label>

                <label className="block">
                    <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">
                        Source
                    </span>
                    <select
                        className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
                        onChange={(event) => setFilters((current) => ({ ...current, source: event.target.value }))}
                        value={filters.source}
                    >
                        <option value="all">All sources</option>
                        {sourceOptions.map((source) => (
                            <option key={source} value={source}>
                                {source}
                            </option>
                        ))}
                    </select>
                </label>
            </AdminFilterBar>

            <section className="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,1.55fr)_390px]">
                <AdminSectionCard eyebrow="Payments" title="Payment list">
                    {filteredPayments.length === 0 ? (
                        <AdminEmptyState
                            description="Clear filters and try again."
                            title="No payments found"
                        />
                    ) : (
                        <div className="space-y-3">
                            {filteredPayments.map((payment) => (
                                <button
                                    className={[
                                        'w-full rounded-[1.5rem] border p-4 text-left transition',
                                        payment.id === selectedPaymentId
                                            ? 'border-[color:var(--admin-accent)] bg-[color:var(--admin-accent-soft)]/60'
                                            : 'border-[color:var(--admin-border)] bg-white hover:bg-[color:var(--admin-surface)]',
                                    ].join(' ')}
                                    key={payment.id}
                                    onClick={() => setSelectedPaymentId(payment.id)}
                                    type="button"
                                >
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p className="font-semibold text-[color:var(--admin-ink)]">{payment.reference}</p>
                                            <p className="mt-1 text-sm text-[color:var(--admin-muted)]">{payment.customerName}</p>
                                        </div>
                                        <AdminStatusBadge value={payment.status} />
                                    </div>
                                    <p className="mt-3 text-sm text-[color:var(--admin-ink)]">{formatCurrency(payment.amount, payment.currency)}</p>
                                    <div className="mt-3 flex flex-wrap gap-2">
                                        <AdminStatusBadge value={payment.method} />
                                        <AdminStatusBadge value={payment.source} />
                                    </div>
                                </button>
                            ))}
                        </div>
                    )}
                </AdminSectionCard>

                <AdminSectionCard eyebrow="Details" title={selectedPayment ? selectedPayment.reference : 'No payment selected'}>
                    {selectedPayment ? (
                        <div className="space-y-5">
                            <div className="flex flex-wrap gap-2">
                                <AdminStatusBadge value={selectedPayment.status} />
                                <AdminStatusBadge value={selectedPayment.method} />
                                <AdminStatusBadge value={selectedPayment.source} />
                            </div>

                            {feedback ? (
                                <div className="rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-accent-soft)] px-4 py-3 text-sm text-[color:var(--admin-accent)]">
                                    {feedback}
                                </div>
                            ) : null}

                            <div className="rounded-2xl bg-stone-50 px-4 py-4">
                                <p className="text-sm font-semibold text-[color:var(--admin-ink)]">{selectedPayment.customerName}</p>
                                <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">{selectedPayment.email}</p>
                                <p className="text-sm leading-6 text-[color:var(--admin-muted)]">Order ref: {selectedPayment.orderReference}</p>
                                <p className="text-sm leading-6 text-[color:var(--admin-muted)]">
                                    Created: {formatDateTime(selectedPayment.createdAt)}
                                </p>
                                <p className="mt-3 text-lg font-semibold text-[color:var(--admin-ink)]">
                                    {formatCurrency(selectedPayment.amount, selectedPayment.currency)}
                                </p>
                            </div>

                            <div className="space-y-3">
                                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">
                                    Actions
                                </p>
                                <div className="grid gap-2 sm:grid-cols-2">
                                    <ActionButton
                                        disabled={isSubmitting || selectedPayment.status === 'paid' || selectedPayment.status === 'refunded'}
                                        onClick={() =>
                                            runAction(
                                                reconcilePayment,
                                                (updatedPayment) => `${updatedPayment.orderReference} reconciled as paid.`,
                                            )
                                        }
                                    >
                                        Reconcile
                                    </ActionButton>
                                    <ActionButton
                                        disabled={isSubmitting || selectedPayment.status === 'failed' || selectedPayment.status === 'refunded'}
                                        onClick={() =>
                                            runAction(
                                                markPaymentFailed,
                                                (updatedPayment) => `${updatedPayment.orderReference} marked failed.`,
                                            )
                                        }
                                    >
                                        Mark failed
                                    </ActionButton>
                                    <ActionButton
                                        disabled={isSubmitting || selectedPayment.status !== 'paid'}
                                        onClick={() =>
                                            runAction(
                                                refundPayment,
                                                (updatedPayment) => `${updatedPayment.orderReference} refunded from payments view.`,
                                            )
                                        }
                                    >
                                        Refund
                                    </ActionButton>
                                    <ActionButton disabled={isSubmitting} onClick={handleOpenReceipt}>
                                        Open receipt
                                    </ActionButton>
                                </div>
                            </div>

                            <div className="rounded-2xl border border-[color:var(--admin-border)] bg-white p-4">
                                <p className="text-sm font-semibold text-[color:var(--admin-ink)]">Note</p>
                                <textarea
                                    className="mt-3 min-h-28 w-full rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
                                    disabled={isSubmitting}
                                    onChange={(event) => setNoteDraft(event.target.value)}
                                    placeholder="Add a note"
                                    value={noteDraft}
                                />
                                <div className="mt-3 flex justify-end">
                                    <button
                                        className="inline-flex items-center justify-center rounded-2xl bg-[color:var(--admin-ink)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-black disabled:cursor-not-allowed disabled:opacity-70"
                                        disabled={isSubmitting || !noteDraft.trim()}
                                        onClick={handleAttachNote}
                                        type="button"
                                    >
                                        {isSubmitting ? 'Saving...' : 'Save note'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    ) : (
                        <AdminEmptyState
                            description="Choose a payment to review."
                            title="No payment selected"
                        />
                    )}
                </AdminSectionCard>
            </section>
        </div>
    );
}
