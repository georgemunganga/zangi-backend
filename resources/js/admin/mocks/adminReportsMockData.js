import { formatCurrency } from './adminOrderMockData';

const PERIOD_DAYS = {
    daily: 1,
    weekly: 7,
    monthly: 31,
};

const REFERENCE_DATE = new Date('2026-03-19T23:59:59');

function isWithinPeriod(value, period) {
    const date = new Date(value);
    const diff = REFERENCE_DATE.getTime() - date.getTime();
    const days = diff / (1000 * 60 * 60 * 24);

    return days <= PERIOD_DAYS[period];
}

export function buildReportSummary(period, orders, tickets, contactMessages) {
    const scopedOrders = orders.filter((order) => isWithinPeriod(order.createdAt, period));
    const scopedTickets = tickets.filter((ticket) => isWithinPeriod(ticket.issuedAt, period));
    const scopedMessages = contactMessages.filter((message) => isWithinPeriod(message.receivedAt, period));

    const revenue = scopedOrders.reduce((sum, order) => {
        if (order.paymentStatus !== 'paid') {
            return sum;
        }

        return sum + order.total;
    }, 0);

    const paidPayments = scopedOrders.filter((order) => order.paymentStatus === 'paid').length;
    const pendingPayments = scopedOrders.filter((order) => order.paymentStatus === 'pending').length;
    const manualOrders = scopedOrders.filter((order) => order.source === 'admin_manual').length;
    const onlineOrders = scopedOrders.filter((order) => order.source === 'online').length;

    return {
        cards: [
            { label: 'Revenue', value: formatCurrency(revenue), detail: `${period} revenue from paid orders` },
            { label: 'Orders', value: String(scopedOrders.length), detail: `${period} order volume` },
            { label: 'Tickets', value: String(scopedTickets.length), detail: `${period} tickets issued` },
            { label: 'Support volume', value: String(scopedMessages.length), detail: `${period} contact messages received` },
        ],
        splits: [
            { label: 'Manual orders', value: String(manualOrders), detail: 'POS and office-issued orders' },
            { label: 'Online orders', value: String(onlineOrders), detail: 'Website orders' },
            { label: 'Paid payments', value: String(paidPayments), detail: 'Confirmed successful payments' },
            { label: 'Pending payments', value: String(pendingPayments), detail: 'Still awaiting reconciliation' },
        ],
        exports: [
            { format: 'CSV', description: 'Orders, tickets, and payment status summary' },
            { format: 'Print', description: 'Printable management snapshot for the selected reporting window' },
        ],
    };
}
