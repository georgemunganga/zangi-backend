import { useDeferredValue, useEffect, useState } from 'react';
import { AdminEmptyState } from '../components/AdminEmptyState';
import { AdminFilterBar } from '../components/AdminFilterBar';
import { AdminPageHeader } from '../components/AdminPageHeader';
import { AdminSectionCard } from '../components/AdminSectionCard';
import { AdminStatusBadge } from '../components/AdminStatusBadge';
import { useAdminMockData } from '../mocks/AdminMockDataProvider';
import { formatDateTime } from '../mocks/adminMockData';

const cannedTemplates = {
    ticket_resend: 'We have received your request. We will resend the ticket details shortly once the delivery action is confirmed.',
    payment_follow_up: 'We have received your payment question and are checking the current payment status with our team.',
    event_info: 'Thank you for reaching out. We are confirming the latest event details and will update you shortly.',
};

function matchesMessage(message, query) {
    if (!query) {
        return true;
    }

    const haystack = [message.subject, message.customerName, message.email, message.preview]
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

export function AdminContactPage() {
    const { contactMessages, isReadDataLoading, readDataError, replyToMessage, setContactMessageStatus } = useAdminMockData();
    const [searchTerm, setSearchTerm] = useState('');
    const deferredSearch = useDeferredValue(searchTerm);
    const [statusFilter, setStatusFilter] = useState('all');
    const [selectedMessageId, setSelectedMessageId] = useState(contactMessages[0]?.id ?? null);
    const [replyBody, setReplyBody] = useState('');
    const [templateKey, setTemplateKey] = useState('');
    const [feedback, setFeedback] = useState('');
    const [actionError, setActionError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const orderedMessages = [...contactMessages].sort(
        (left, right) => new Date(right.receivedAt).getTime() - new Date(left.receivedAt).getTime(),
    );

    const filteredMessages = orderedMessages.filter((message) => {
        if (!matchesMessage(message, deferredSearch)) {
            return false;
        }

        if (statusFilter !== 'all' && message.status !== statusFilter) {
            return false;
        }

        return true;
    });

    useEffect(() => {
        if (filteredMessages.length === 0) {
            setSelectedMessageId(null);
            return;
        }

        const stillVisible = filteredMessages.some((message) => message.id === selectedMessageId);

        if (!stillVisible) {
            setSelectedMessageId(filteredMessages[0].id);
        }
    }, [filteredMessages, selectedMessageId]);

    const selectedMessage = filteredMessages.find((message) => message.id === selectedMessageId) ?? null;

    const unreadCount = filteredMessages.filter((message) => message.status === 'unread').length;
    const inProgressCount = filteredMessages.filter((message) => message.status === 'in_progress').length;
    const repliedCount = filteredMessages.filter((message) => message.status === 'replied').length;
    const statusOptions = [...new Set(contactMessages.map((message) => message.status))];

    const applyStatus = async (status) => {
        if (!selectedMessage) {
            return;
        }

        setActionError('');
        setIsSubmitting(true);

        try {
            const updatedMessage = await setContactMessageStatus(selectedMessage.id, status);

            if (updatedMessage) {
                setFeedback(`${updatedMessage.subject} marked ${updatedMessage.status}.`);
            }
        } catch (error) {
            setActionError(error.message);
        } finally {
            setIsSubmitting(false);
        }
    };

    const applyTemplate = () => {
        if (!templateKey) {
            return;
        }

        setReplyBody(cannedTemplates[templateKey]);
    };

    const handleReply = async () => {
        if (!selectedMessage || !replyBody.trim()) {
            return;
        }

        setActionError('');
        setIsSubmitting(true);

        try {
            const updatedMessage = await replyToMessage(selectedMessage.id, replyBody.trim());

            if (updatedMessage) {
                setFeedback('Reply sent.');
                setReplyBody('');
                setTemplateKey('');
            }
        } catch (error) {
            setActionError(error.message);
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="min-w-0 space-y-6">
            <AdminPageHeader title="Contact Messages" />

            {readDataError ? (
                <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {readDataError}
                </div>
            ) : null}

            {isReadDataLoading ? (
                <div className="rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-3 text-sm text-[color:var(--admin-muted)]">
                    Loading messages...
                </div>
            ) : null}

            {actionError ? (
                <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {actionError}
                </div>
            ) : null}

            <section className="grid gap-4 md:grid-cols-3">
                <SummaryCard label="Unread" value={String(unreadCount)} />
                <SummaryCard label="In progress" value={String(inProgressCount)} />
                <SummaryCard label="Replied" value={String(repliedCount)} />
            </section>

            <AdminFilterBar summary={`Showing ${filteredMessages.length} messages.`}>
                <label className="block xl:col-span-4">
                    <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">
                        Search inbox
                    </span>
                    <input
                        className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
                        onChange={(event) => setSearchTerm(event.target.value)}
                        placeholder="Subject, customer name, email"
                        type="search"
                        value={searchTerm}
                    />
                </label>
                <label className="block xl:col-span-2">
                    <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">
                        Status
                    </span>
                    <select
                        className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
                        onChange={(event) => setStatusFilter(event.target.value)}
                        value={statusFilter}
                    >
                        <option value="all">All statuses</option>
                        {statusOptions.map((status) => (
                            <option key={status} value={status}>
                                {status}
                            </option>
                        ))}
                    </select>
                </label>
            </AdminFilterBar>

            <section className="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
                <AdminSectionCard eyebrow="Inbox" title="Messages">
                    {filteredMessages.length === 0 ? (
                        <AdminEmptyState
                            description="Clear filters and try again."
                            title="No messages found"
                        />
                    ) : (
                        <div className="space-y-3">
                            {filteredMessages.map((message) => (
                                <button
                                    className={[
                                        'w-full rounded-[1.5rem] border p-4 text-left transition',
                                        message.id === selectedMessageId
                                            ? 'border-[color:var(--admin-accent)] bg-[color:var(--admin-accent-soft)]/60'
                                            : 'border-[color:var(--admin-border)] bg-white hover:bg-[color:var(--admin-surface)]',
                                    ].join(' ')}
                                    key={message.id}
                                    onClick={() => setSelectedMessageId(message.id)}
                                    type="button"
                                >
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="font-semibold text-[color:var(--admin-ink)]">{message.subject}</p>
                                            <p className="mt-1 text-sm text-[color:var(--admin-muted)]">{message.customerName}</p>
                                        </div>
                                        <AdminStatusBadge value={message.status} />
                                    </div>
                                    <p className="mt-3 line-clamp-2 text-sm leading-6 text-[color:var(--admin-muted)]">{message.preview}</p>
                                    <p className="mt-3 text-sm text-[color:var(--admin-muted)]">{formatDateTime(message.receivedAt)}</p>
                                </button>
                            ))}
                        </div>
                    )}
                </AdminSectionCard>

                <AdminSectionCard eyebrow="Thread" title={selectedMessage ? selectedMessage.subject : 'No message selected'}>
                    {selectedMessage ? (
                        <div className="space-y-5">
                            <div className="flex flex-wrap items-center gap-2">
                                <AdminStatusBadge value={selectedMessage.status} />
                                <span className="rounded-full border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.16em] text-[color:var(--admin-muted)]">
                                    {selectedMessage.customerName}
                                </span>
                            </div>

                            {feedback ? (
                                <div className="rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-accent-soft)] px-4 py-3 text-sm text-[color:var(--admin-accent)]">
                                    {feedback}
                                </div>
                            ) : null}

                            <div className="rounded-2xl bg-stone-50 px-4 py-4">
                                <p className="text-sm font-semibold text-[color:var(--admin-ink)]">{selectedMessage.customerName}</p>
                                <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">{selectedMessage.email}</p>
                                <p className="text-sm leading-6 text-[color:var(--admin-muted)]">{selectedMessage.phone || 'No phone recorded'}</p>
                                <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">{selectedMessage.notes}</p>
                            </div>

                            <div className="space-y-3">
                                {selectedMessage.thread.map((entry) => (
                                    <div
                                        className={[
                                            'rounded-[1.5rem] px-4 py-4',
                                            entry.author === 'admin'
                                                ? 'bg-[color:var(--admin-accent-soft)] sm:ml-6'
                                                : 'border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] sm:mr-6',
                                        ].join(' ')}
                                        key={entry.id}
                                    >
                                        <div className="flex flex-wrap items-center justify-between gap-3">
                                            <p className="text-sm font-semibold text-[color:var(--admin-ink)]">{entry.name}</p>
                                            <p className="text-sm text-[color:var(--admin-muted)]">{formatDateTime(entry.sentAt)}</p>
                                        </div>
                                        <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">{entry.body}</p>
                                    </div>
                                ))}
                            </div>

                            <div className="space-y-3">
                                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">
                                    Status
                                </p>
                                <div className="flex flex-wrap gap-2">
                                    <ActionButton disabled={isSubmitting} onClick={() => applyStatus('unread')}>Mark unread</ActionButton>
                                    <ActionButton disabled={isSubmitting} onClick={() => applyStatus('read')}>Mark read</ActionButton>
                                    <ActionButton disabled={isSubmitting} onClick={() => applyStatus('in_progress')}>In progress</ActionButton>
                                    <ActionButton disabled={isSubmitting} onClick={() => applyStatus('closed')}>Close</ActionButton>
                                    <ActionButton disabled={isSubmitting} onClick={() => applyStatus('spam')}>Spam</ActionButton>
                                </div>
                            </div>

                            <div className="rounded-2xl border border-[color:var(--admin-border)] bg-white p-4">
                                <p className="text-sm font-semibold text-[color:var(--admin-ink)]">Reply</p>
                                <div className="mt-4 grid gap-3 md:grid-cols-[1fr_auto]">
                                    <select
                                        className="rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
                                        onChange={(event) => setTemplateKey(event.target.value)}
                                        value={templateKey}
                                    >
                                        <option value="">Choose template</option>
                                        <option value="ticket_resend">Ticket resend</option>
                                        <option value="payment_follow_up">Payment follow-up</option>
                                        <option value="event_info">Event info</option>
                                    </select>
                                    <button
                                        className="inline-flex items-center justify-center rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-3 text-sm font-semibold text-[color:var(--admin-ink)] transition hover:border-[color:var(--admin-accent)]"
                                        disabled={isSubmitting}
                                        onClick={applyTemplate}
                                        type="button"
                                    >
                                        Apply template
                                    </button>
                                </div>
                                <textarea
                                    className="mt-3 min-h-32 w-full rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
                                    disabled={isSubmitting}
                                    onChange={(event) => setReplyBody(event.target.value)}
                                    placeholder="Write a reply"
                                    value={replyBody}
                                />
                                <div className="mt-3 flex justify-end">
                                    <button
                                        className="inline-flex items-center justify-center rounded-2xl bg-[color:var(--admin-ink)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-black disabled:cursor-not-allowed disabled:opacity-70"
                                        disabled={isSubmitting || !replyBody.trim()}
                                        onClick={handleReply}
                                        type="button"
                                    >
                                        {isSubmitting ? 'Sending...' : 'Send reply'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    ) : (
                        <AdminEmptyState
                            description="Choose a message to read or reply."
                            title="No message selected"
                        />
                    )}
                </AdminSectionCard>
            </section>
        </div>
    );
}
