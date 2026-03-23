import { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { useAdminAuth } from '../auth/AdminAuthProvider';
import {
    attachAdminPaymentNote,
    cancelAdminOrder,
    confirmAdminOrderPayment,
    createAdminManualSale,
    extractApiErrorMessage,
    fetchAdminContactMessages,
    fetchAdminCustomers,
    fetchAdminOrders,
    fetchAdminOverview,
    fetchAdminPayments,
    fetchAdminTickets,
    markAdminTicketUsed,
    markAdminPaymentFailed,
    reconcileAdminPayment,
    reissueAdminTicket,
    resendAdminOrderDelivery,
    resendAdminTicket,
    refundAdminOrder,
    refundAdminPayment,
    replyToAdminContactMessage,
    updateAdminContactMessageStatus,
    updateAdminOrderStatus,
    validateAdminTicket,
    voidAdminTicket,
} from '../api/adminApiClient';
import { adminBooks, getDefaultManualSaleForm } from './adminOrderMockData';
import { adminEvents, createValidationAttempt } from './adminMockData';

const AdminMockDataContext = createContext(null);
const DEFAULT_LIST_QUERY = {
    page: 1,
    perPage: 100,
};
const EMPTY_OVERVIEW = {
    generatedAt: '',
    stats: [],
    recentOrders: [],
    recentMessages: [],
    upcomingEvents: [],
    actionQueue: [],
};

function toDateKey(value) {
    if (!value) {
        return '';
    }

    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return value;
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function normalizeTicketRecord(ticket) {
    return {
        ...ticket,
        id: String(ticket.id),
        customerId: ticket.customerId || '',
        eventDate: ticket.eventDate || toDateKey(ticket.eventDateLabel),
        quantity: Number(ticket.quantity ?? 1),
    };
}

function normalizeOrderRecord(order) {
    return {
        ...order,
        id: String(order.id),
        customerId: order.customerId || '',
        bookFormat: order.bookFormat || '',
        deliveryKind: order.deliveryKind || 'order_detail',
        downloadReady: Boolean(order.downloadReady),
        linkedTicketIds: (order.linkedTicketIds ?? []).map((ticketId) => String(ticketId)),
        lines: (order.lines ?? []).map((line) => ({
            ...line,
            quantity: Number(line.quantity ?? 0),
            unitPrice: Number(line.unitPrice ?? 0),
        })),
        total: Number(order.total ?? 0),
    };
}

function normalizeContactMessage(message) {
    return {
        ...message,
        id: String(message.id),
        thread: (message.thread ?? []).map((entry) => ({
            ...entry,
            id: String(entry.id),
        })),
    };
}

function normalizePaymentRecord(payment) {
    return {
        ...payment,
        id: String(payment.id),
        orderId: String(payment.orderId),
        amount: Number(payment.amount ?? 0),
    };
}

function normalizeCustomerRecord(customer) {
    return {
        ...customer,
        id: String(customer.id),
        totalSpent: Number(customer.totalSpent ?? 0),
        tags: Array.isArray(customer.tags) ? customer.tags : [],
        notes: Array.isArray(customer.notes) ? customer.notes : [],
        purchaseHistory: (customer.purchaseHistory ?? []).map(normalizeOrderRecord),
        attendanceHistory: (customer.attendanceHistory ?? []).map(normalizeTicketRecord),
    };
}

function normalizeOverviewData(overview) {
    return {
        generatedAt: overview?.generatedAt ?? '',
        stats: Array.isArray(overview?.stats) ? overview.stats : [],
        recentOrders: Array.isArray(overview?.recentOrders) ? overview.recentOrders : [],
        recentMessages: Array.isArray(overview?.recentMessages) ? overview.recentMessages : [],
        upcomingEvents: Array.isArray(overview?.upcomingEvents) ? overview.upcomingEvents : [],
        actionQueue: Array.isArray(overview?.actionQueue) ? overview.actionQueue : [],
    };
}

function mergeTicketEvents(events, tickets) {
    const eventMap = new Map(events.map((event) => [event.slug, event]));

    for (const ticket of tickets) {
        if (eventMap.has(ticket.eventSlug)) {
            continue;
        }

        eventMap.set(ticket.eventSlug, {
            slug: ticket.eventSlug,
            title: ticket.eventTitle,
            date: ticket.eventDate || toDateKey(ticket.eventDateLabel),
            dateLabel: ticket.eventDateLabel,
            timeLabel: ticket.timeLabel || '',
            venue: ticket.venue,
            ticketTypes: [],
        });
    }

    return [...eventMap.values()].sort((left, right) => {
        const leftDate = left.date || '9999-12-31';
        const rightDate = right.date || '9999-12-31';

        return leftDate.localeCompare(rightDate);
    });
}

function requireAdminToken(accessToken, message = 'Sign in to continue.') {
    if (!accessToken) {
        throw new Error(message);
    }
}

export function AdminMockDataProvider({ children }) {
    const { accessToken, isAuthenticated } = useAdminAuth();
    const [tickets, setTickets] = useState([]);
    const [orders, setOrders] = useState([]);
    const [customers, setCustomers] = useState([]);
    const [contactMessages, setContactMessages] = useState([]);
    const [payments, setPayments] = useState([]);
    const [overview, setOverview] = useState(EMPTY_OVERVIEW);
    const [validationAttempts, setValidationAttempts] = useState([]);
    const [isReadDataLoading, setIsReadDataLoading] = useState(false);
    const [readDataError, setReadDataError] = useState('');

    const resetLiveAdminData = () => {
        setTickets([]);
        setOrders([]);
        setCustomers([]);
        setContactMessages([]);
        setPayments([]);
        setOverview(EMPTY_OVERVIEW);
        setValidationAttempts([]);
    };

    const hydrateLiveAdminData = async (token) => {
        const [overviewResponse, ticketResponse, orderResponse, customerResponse, contactResponse, paymentResponse] =
            await Promise.all([
                fetchAdminOverview(token),
                fetchAdminTickets(token, DEFAULT_LIST_QUERY),
                fetchAdminOrders(token, DEFAULT_LIST_QUERY),
                fetchAdminCustomers(token, DEFAULT_LIST_QUERY),
                fetchAdminContactMessages(token, DEFAULT_LIST_QUERY),
                fetchAdminPayments(token, DEFAULT_LIST_QUERY),
            ]);

        return {
            overview: normalizeOverviewData(overviewResponse),
            tickets: (ticketResponse.data ?? []).map(normalizeTicketRecord),
            orders: (orderResponse.data ?? []).map(normalizeOrderRecord),
            customers: (customerResponse.data ?? []).map(normalizeCustomerRecord),
            contactMessages: (contactResponse.data ?? []).map(normalizeContactMessage),
            payments: (paymentResponse.data ?? []).map(normalizePaymentRecord),
        };
    };

    const applyHydratedLiveAdminData = (snapshot) => {
        setOverview(snapshot.overview);
        setTickets(snapshot.tickets);
        setOrders(snapshot.orders);
        setCustomers(snapshot.customers);
        setContactMessages(snapshot.contactMessages);
        setPayments(snapshot.payments);
    };

    const refreshLiveAdminData = async (token, fallbackMessage) => {
        try {
            const snapshot = await hydrateLiveAdminData(token);
            applyHydratedLiveAdminData(snapshot);
            setReadDataError('');

            return snapshot;
        } catch (error) {
            setReadDataError(extractApiErrorMessage(error, fallbackMessage));

            return null;
        }
    };

    const runLiveMutation = async (operation, mutationErrorMessage, refreshErrorMessage) => {
        try {
            const response = await operation();
            await refreshLiveAdminData(accessToken, refreshErrorMessage);

            return response;
        } catch (error) {
            throw new Error(extractApiErrorMessage(error, mutationErrorMessage));
        }
    };

    useEffect(() => {
        if (!isAuthenticated || !accessToken) {
            setReadDataError('');
            setIsReadDataLoading(false);
            resetLiveAdminData();
            return;
        }

        let cancelled = false;

        setIsReadDataLoading(true);
        setReadDataError('');

        hydrateLiveAdminData(accessToken)
            .then((snapshot) => {
                if (!cancelled) {
                    applyHydratedLiveAdminData(snapshot);
                }
            })
            .catch((error) => {
                if (!cancelled) {
                    resetLiveAdminData();
                    setReadDataError(extractApiErrorMessage(error, 'Unable to load data right now.'));
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setIsReadDataLoading(false);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [accessToken, isAuthenticated]);

    const ticketEvents = useMemo(() => mergeTicketEvents(adminEvents, tickets), [tickets]);

    const validateTicket = async (ticketCode, eventSlug) => {
        requireAdminToken(accessToken, 'Sign in to validate tickets.');

        try {
            const response = await validateAdminTicket(accessToken, {
                ticketCode,
                eventSlug,
            });
            const result = {
                code: response.code,
                state: response.state,
                checkedAt: response.checkedAt,
                message: response.message,
                ticket: response.ticket ? normalizeTicketRecord(response.ticket) : null,
            };

            setValidationAttempts((current) => [createValidationAttempt(result), ...current].slice(0, 8));

            return result;
        } catch (error) {
            throw new Error(extractApiErrorMessage(error, 'Unable to validate the ticket.'));
        }
    };

    const markUsed = async (ticketId) => {
        requireAdminToken(accessToken, 'Sign in to update tickets.');

        const response = await runLiveMutation(
            () => markAdminTicketUsed(accessToken, ticketId),
            'Unable to mark the ticket as used.',
            'Ticket updated, but the list could not be refreshed.',
        );

        return response?.ticket ? normalizeTicketRecord(response.ticket) : null;
    };

    const voidTicket = async (ticketId) => {
        requireAdminToken(accessToken, 'Sign in to update tickets.');

        const response = await runLiveMutation(
            () => voidAdminTicket(accessToken, ticketId),
            'Unable to void the ticket.',
            'Ticket updated, but the list could not be refreshed.',
        );

        return response?.ticket ? normalizeTicketRecord(response.ticket) : null;
    };

    const reissueTicket = async (ticketId) => {
        requireAdminToken(accessToken, 'Sign in to update tickets.');

        const response = await runLiveMutation(
            () => reissueAdminTicket(accessToken, ticketId),
            'Unable to reissue the ticket.',
            'Ticket updated, but the list could not be refreshed.',
        );

        return response?.ticket ? normalizeTicketRecord(response.ticket) : null;
    };

    const resendTicket = async (ticketId) => {
        requireAdminToken(accessToken, 'Sign in to update tickets.');

        const response = await runLiveMutation(
            () => resendAdminTicket(accessToken, ticketId),
            'Unable to resend the ticket.',
            'Ticket updated, but the list could not be refreshed.',
        );

        return response?.ticket ? normalizeTicketRecord(response.ticket) : null;
    };

    const createManualSale = async (payload) => {
        requireAdminToken(accessToken, 'Sign in to create manual sales.');

        try {
            const response = await createAdminManualSale(accessToken, payload);
            await refreshLiveAdminData(accessToken, 'Sale created, but the lists could not be refreshed.');

            return {
                createdOrder: response.order ? normalizeOrderRecord(response.order) : null,
                createdTickets: (response.tickets ?? []).map(normalizeTicketRecord),
            };
        } catch (error) {
            throw new Error(extractApiErrorMessage(error, 'Unable to create the manual sale.'));
        }
    };

    const applyOrderStatus = async (orderId, nextStatus) => {
        requireAdminToken(accessToken, 'Sign in to update orders.');

        const response = await runLiveMutation(
            () => updateAdminOrderStatus(accessToken, orderId, { status: nextStatus }),
            'Unable to update the order status.',
            'Order updated, but the list could not be refreshed.',
        );

        return response?.order ? normalizeOrderRecord(response.order) : null;
    };

    const confirmPayment = async (orderId) => {
        requireAdminToken(accessToken, 'Sign in to update orders.');

        const response = await runLiveMutation(
            () => confirmAdminOrderPayment(accessToken, orderId),
            'Unable to confirm the order payment.',
            'Payment confirmed, but the order list could not be refreshed.',
        );

        return response?.order ? normalizeOrderRecord(response.order) : null;
    };

    const cancelOrderById = async (orderId) => {
        requireAdminToken(accessToken, 'Sign in to update orders.');

        const response = await runLiveMutation(
            () => cancelAdminOrder(accessToken, orderId),
            'Unable to cancel the order.',
            'Order updated, but the list could not be refreshed.',
        );

        return response?.order ? normalizeOrderRecord(response.order) : null;
    };

    const refundOrderById = async (orderId) => {
        requireAdminToken(accessToken, 'Sign in to update orders.');

        const response = await runLiveMutation(
            () => refundAdminOrder(accessToken, orderId),
            'Unable to refund the order.',
            'Order updated, but the list could not be refreshed.',
        );

        return response?.order ? normalizeOrderRecord(response.order) : null;
    };

    const resendOrderDelivery = async (orderId) => {
        requireAdminToken(accessToken, 'Sign in to update orders.');

        const response = await runLiveMutation(
            () => resendAdminOrderDelivery(accessToken, orderId),
            'Unable to resend the order delivery email.',
            'Order updated, but the list could not be refreshed.',
        );

        return {
            message: response?.message || '',
            order: response?.order ? normalizeOrderRecord(response.order) : null,
        };
    };

    const reconcilePayment = async (paymentId) => {
        requireAdminToken(accessToken, 'Sign in to update payments.');

        const response = await runLiveMutation(
            () => reconcileAdminPayment(accessToken, paymentId),
            'Unable to reconcile the payment.',
            'Payment updated, but the list could not be refreshed.',
        );

        return response?.payment ? normalizePaymentRecord(response.payment) : null;
    };

    const markPaymentFailed = async (paymentId) => {
        requireAdminToken(accessToken, 'Sign in to update payments.');

        const response = await runLiveMutation(
            () => markAdminPaymentFailed(accessToken, paymentId),
            'Unable to mark the payment as failed.',
            'Payment updated, but the list could not be refreshed.',
        );

        return response?.payment ? normalizePaymentRecord(response.payment) : null;
    };

    const refundPayment = async (paymentId) => {
        requireAdminToken(accessToken, 'Sign in to update payments.');

        const response = await runLiveMutation(
            () => refundAdminPayment(accessToken, paymentId),
            'Unable to refund the payment.',
            'Payment updated, but the list could not be refreshed.',
        );

        return response?.payment ? normalizePaymentRecord(response.payment) : null;
    };

    const attachPaymentNote = async (paymentId, note) => {
        requireAdminToken(accessToken, 'Sign in to update payments.');

        const response = await runLiveMutation(
            () => attachAdminPaymentNote(accessToken, paymentId, { note }),
            'Unable to attach the payment note.',
            'Payment updated, but the list could not be refreshed.',
        );

        return response?.payment ? normalizePaymentRecord(response.payment) : null;
    };

    const setContactMessageStatus = async (messageId, nextStatus) => {
        requireAdminToken(accessToken, 'Sign in to update contact messages.');

        const response = await runLiveMutation(
            () => updateAdminContactMessageStatus(accessToken, messageId, { status: nextStatus }),
            'Unable to update the contact message status.',
            'Message updated, but the list could not be refreshed.',
        );

        return response?.contactMessage ? normalizeContactMessage(response.contactMessage) : null;
    };

    const replyToMessage = async (messageId, body) => {
        requireAdminToken(accessToken, 'Sign in to reply to contact messages.');

        const response = await runLiveMutation(
            () => replyToAdminContactMessage(accessToken, messageId, { body }),
            'Unable to send the contact reply.',
            'Reply sent, but the list could not be refreshed.',
        );

        return response?.contactMessage ? normalizeContactMessage(response.contactMessage) : null;
    };

    return (
        <AdminMockDataContext.Provider
            value={{
                events: adminEvents,
                ticketEvents,
                books: adminBooks,
                overview,
                defaultManualSaleForm: getDefaultManualSaleForm(adminEvents, adminBooks),
                customers,
                contactMessages,
                orders,
                payments,
                tickets,
                validationAttempts,
                isReadDataLoading,
                readDataError,
                validateTicket,
                markUsed,
                voidTicket,
                reissueTicket,
                resendTicket,
                createManualSale,
                applyOrderStatus,
                confirmPayment,
                resendOrderDelivery,
                reconcilePayment,
                cancelOrderById,
                refundOrderById,
                markPaymentFailed,
                refundPayment,
                attachPaymentNote,
                setContactMessageStatus,
                replyToMessage,
            }}
        >
            {children}
        </AdminMockDataContext.Provider>
    );
}

export function useAdminMockData() {
    const context = useContext(AdminMockDataContext);

    if (!context) {
        throw new Error('useAdminMockData must be used within AdminMockDataProvider');
    }

    return context;
}
