import { formatCurrency } from './adminOrderMockData';

const TODAY = '2026-03-19';
const DEFAULT_USED_AT = '2026-03-19T18:20:00';

export const adminEvents = [
    {
        slug: 'zangi-book-launch-mulungushi-lusaka',
        title: "Zangi's Flag Book Launch",
        date: '2026-05-22',
        dateLabel: 'May 22, 2026',
        timeLabel: '6:00 PM - 7:30 PM CAT',
        venue: 'Mulungushi International Conference Centre, Lusaka',
        ticketTypes: [
            { label: 'Standard', price: 350 },
            { label: 'VIP', price: 500 },
        ],
    },
];

export const initialAdminTickets = [
    {
        id: 'ticket_1001',
        code: 'ZAN-TK-1001',
        holderName: 'Temwani Daka',
        buyerName: 'Temwani Daka',
        email: 'temwani@zangi.africa',
        phone: '+260971111001',
        eventSlug: 'lusaka-book-launch',
        eventTitle: 'Lusaka Book Launch Evening',
        eventDate: '2026-03-28',
        eventDateLabel: 'March 28, 2026',
        venue: 'Lusaka',
        ticketType: 'VIP',
        status: 'issued',
        paymentStatus: 'paid',
        source: 'online',
        amount: 850,
        currency: 'ZMW',
        paymentMethod: 'Card',
        deliveryMethod: 'Email PDF',
        issuedAt: '2026-03-18T15:10:00',
        usedAt: null,
        notes: 'Customer requested a receipt copy.',
    },
    {
        id: 'ticket_1002',
        code: 'ZAN-TK-1002',
        holderName: 'Lusungu Phiri',
        buyerName: 'Lusungu Phiri',
        email: 'lusungu@example.com',
        phone: '+260971111002',
        eventSlug: 'creative-workshop',
        eventTitle: 'Creative Workshop',
        eventDate: '2026-04-03',
        eventDateLabel: 'April 3, 2026',
        venue: 'Lusaka',
        ticketType: 'Standard',
        status: 'issued',
        paymentStatus: 'paid',
        source: 'admin_manual',
        amount: 420,
        currency: 'ZMW',
        paymentMethod: 'Cash',
        deliveryMethod: 'Printed receipt',
        issuedAt: '2026-03-18T17:45:00',
        usedAt: null,
        notes: 'Walk-in sale at the office.',
    },
    {
        id: 'ticket_1003',
        code: 'ZAN-TK-1003',
        holderName: 'Chisomo Banda',
        buyerName: 'Chisomo Banda',
        email: 'chisomo@readermail.com',
        phone: '+260971111003',
        eventSlug: 'legacy-reader-circle',
        eventTitle: 'Legacy Reader Circle',
        eventDate: '2026-03-12',
        eventDateLabel: 'March 12, 2026',
        venue: 'Johannesburg',
        ticketType: 'Standard',
        status: 'used',
        paymentStatus: 'paid',
        source: 'online',
        amount: 300,
        currency: 'ZMW',
        paymentMethod: 'Card',
        deliveryMethod: 'Apple Wallet pass',
        issuedAt: '2026-03-10T12:30:00',
        usedAt: '2026-03-12T18:22:00',
        notes: 'Checked in successfully.',
    },
    {
        id: 'ticket_1004',
        code: 'ZAN-TK-1004',
        holderName: 'Mutinta Sakala',
        buyerName: 'Mutinta Sakala',
        email: 'mutinta@example.com',
        phone: '+260971111004',
        eventSlug: 'lusaka-book-launch',
        eventTitle: 'Lusaka Book Launch Evening',
        eventDate: '2026-03-28',
        eventDateLabel: 'March 28, 2026',
        venue: 'Lusaka',
        ticketType: 'VIP',
        status: 'cancelled',
        paymentStatus: 'refunded',
        source: 'online',
        amount: 850,
        currency: 'ZMW',
        paymentMethod: 'Card',
        deliveryMethod: 'Email PDF',
        issuedAt: '2026-03-17T08:20:00',
        usedAt: null,
        notes: 'Refund approved after duplicate order claim.',
    },
    {
        id: 'ticket_1005',
        code: 'ZAN-TK-1005',
        holderName: 'Martha Ncube',
        buyerName: 'Martha Ncube',
        email: 'martha@bookclub.africa',
        phone: '+27111111005',
        eventSlug: 'legacy-reader-circle',
        eventTitle: 'Legacy Reader Circle',
        eventDate: '2026-03-12',
        eventDateLabel: 'March 12, 2026',
        venue: 'Johannesburg',
        ticketType: 'VIP',
        status: 'expired',
        paymentStatus: 'paid',
        source: 'online',
        amount: 550,
        currency: 'ZMW',
        paymentMethod: 'Card',
        deliveryMethod: 'Email PDF',
        issuedAt: '2026-03-10T19:15:00',
        usedAt: null,
        notes: 'Ticket was never used before event close.',
    },
    {
        id: 'ticket_1006',
        code: 'ZAN-TK-1006',
        holderName: 'Alick Mwila',
        buyerName: 'Alick Mwila',
        email: 'alick@zangishop.com',
        phone: '+260971111006',
        eventSlug: 'creative-workshop',
        eventTitle: 'Creative Workshop',
        eventDate: '2026-04-03',
        eventDateLabel: 'April 3, 2026',
        venue: 'Lusaka',
        ticketType: 'Standard',
        status: 'pending',
        paymentStatus: 'pending',
        source: 'admin_manual',
        amount: 420,
        currency: 'ZMW',
        paymentMethod: 'Mobile Money',
        deliveryMethod: 'Reserved, send later',
        issuedAt: '2026-03-19T09:05:00',
        usedAt: null,
        notes: 'Reserved for same-day follow-up payment.',
    },
    {
        id: 'ticket_1007',
        code: 'ZAN-TK-1007',
        holderName: 'Precious Sitali',
        buyerName: 'Precious Sitali',
        email: 'precious@example.com',
        phone: '+260971111007',
        eventSlug: 'vip-signing-session',
        eventTitle: 'VIP Signing Session',
        eventDate: '2026-04-12',
        eventDateLabel: 'April 12, 2026',
        venue: 'Pretoria',
        ticketType: 'VIP',
        status: 'voided',
        paymentStatus: 'failed',
        source: 'online',
        amount: 950,
        currency: 'ZMW',
        paymentMethod: 'Card',
        deliveryMethod: 'Email PDF',
        issuedAt: '2026-03-19T11:40:00',
        usedAt: null,
        notes: 'Payment verification failed after retry limit.',
    },
    {
        id: 'ticket_1008',
        code: 'ZAN-TK-1008',
        holderName: 'Faith Chanda',
        buyerName: 'Faith Chanda',
        email: 'faith@mediahub.africa',
        phone: '+260971111008',
        eventSlug: 'lusaka-book-launch',
        eventTitle: 'Lusaka Book Launch Evening',
        eventDate: '2026-03-28',
        eventDateLabel: 'March 28, 2026',
        venue: 'Lusaka',
        ticketType: 'Standard',
        status: 'issued',
        paymentStatus: 'paid',
        source: 'complimentary',
        amount: 0,
        currency: 'ZMW',
        paymentMethod: 'Complimentary',
        deliveryMethod: 'Manual guest list',
        issuedAt: '2026-03-18T22:05:00',
        usedAt: null,
        notes: 'Media guest allocation.',
    },
];

export const adminOverviewFeed = {
    recentOrders: [
        'Manual ticket issue for Lusaka book launch, 2 VIP tickets',
        'Online mixed order with ticket and digital book bundle',
        'Card payment verified for workshop attendee invoice',
    ],
    recentMessages: [
        'Customer asking to resend e-ticket after inbox issue',
        'Corporate client requesting offline invoice for group booking',
        'Attendee asking if standard ticket can be upgraded to VIP',
    ],
    actionQueue: [
        'Review pending payments before end-of-day reconciliation',
        'Validate guest list export requirements for Saturday event',
        'Resolve contact threads marked in progress for ticket delivery',
    ],
};

export const initialValidationAttempts = [
    {
        id: 'attempt_1',
        code: 'ZAN-TK-1003',
        state: 'already_used',
        eventTitle: 'Legacy Reader Circle',
        checkedAt: '2026-03-19T09:10:00',
    },
    {
        id: 'attempt_2',
        code: 'ZAN-TK-1005',
        state: 'expired',
        eventTitle: 'Legacy Reader Circle',
        checkedAt: '2026-03-19T10:25:00',
    },
    {
        id: 'attempt_3',
        code: 'ZAN-TK-4040',
        state: 'invalid',
        eventTitle: 'Unknown',
        checkedAt: '2026-03-19T11:42:00',
    },
];

function buildRecentOrders(orders = []) {
    return [...orders]
        .sort((left, right) => new Date(right.createdAt).getTime() - new Date(left.createdAt).getTime())
        .slice(0, 3)
        .map((order) => `${order.reference} - ${order.customerName} - ${formatCurrency(order.total, order.currency)}`);
}

function buildRecentMessages(contactMessages = []) {
    return [...contactMessages]
        .sort((left, right) => new Date(right.receivedAt).getTime() - new Date(left.receivedAt).getTime())
        .slice(0, 3)
        .map((message) => `${message.customerName}: ${message.preview}`);
}

function buildActionQueue(tickets = [], orders = [], contactMessages = []) {
    const pendingPayments = orders.filter((order) => order.paymentStatus === 'pending').length;
    const unreadMessages = contactMessages.filter((message) => message.status === 'unread').length;
    const pendingTickets = tickets.filter((ticket) => ticket.status === 'pending').length;
    const items = [];

    if (pendingPayments > 0) {
        items.push(`Review ${pendingPayments} pending payment records.`);
    }

    if (unreadMessages > 0) {
        items.push(`Respond to ${unreadMessages} unread contact messages.`);
    }

    if (pendingTickets > 0) {
        items.push(`Inspect ${pendingTickets} ticket purchases still waiting for fulfillment.`);
    }

    return items.length > 0 ? items : ['No urgent operational actions are currently outstanding.'];
}

export function buildOverviewStats(tickets, orders = []) {
    const revenue = orders.reduce((sum, order) => {
        if (order.paymentStatus !== 'paid') {
            return sum;
        }

        return sum + order.total;
    }, 0);
    const ticketsSold = tickets.reduce((sum, ticket) => {
        if (['cancelled', 'voided', 'refunded'].includes(ticket.status)) {
            return sum;
        }

        return sum + Number(ticket.quantity ?? 1);
    }, 0);
    const usedTickets = tickets.filter((ticket) => ticket.status === 'used').length;
    const pendingPayments = orders.filter((order) => order.paymentStatus === 'pending').length;
    const failedPayments = orders.filter((order) => order.paymentStatus === 'failed').length;
    const manualSales = orders.filter((order) => order.source === 'admin_manual').length;
    const processingOrders = orders.filter((order) => order.status === 'processing').length;
    const pendingManualSales = orders.filter(
        (order) => order.source === 'admin_manual' && order.paymentStatus === 'pending',
    ).length;

    return [
        { label: 'Revenue', value: formatCurrency(revenue), trend: `${orders.filter((order) => order.paymentStatus === 'paid').length} paid orders` },
        { label: 'Tickets Sold', value: String(ticketsSold), trend: `${usedTickets} used` },
        { label: 'Manual Sales', value: String(manualSales), trend: `${pendingManualSales} pending` },
        { label: 'Orders', value: String(orders.length), trend: `${processingOrders} processing` },
        { label: 'Pending Payments', value: String(pendingPayments), trend: 'Needs review' },
        { label: 'Failed Payments', value: String(failedPayments), trend: 'Below target' },
    ];
}

export function buildOverviewData(tickets, orders = [], contactMessages = [], events = adminEvents) {
    return {
        stats: buildOverviewStats(tickets, orders),
        recentOrders: buildRecentOrders(orders),
        recentMessages: buildRecentMessages(contactMessages),
        upcomingEvents: events.filter((event) => event.date >= TODAY),
        actionQueue: buildActionQueue(tickets, orders, contactMessages),
    };
}

function normalizeCode(value) {
    return value.trim().toUpperCase();
}

function isExpiredTicket(ticket) {
    return ticket.status === 'expired' || ticket.eventDate < TODAY;
}

export function resolveTicketValidation(tickets, ticketCode, eventSlug) {
    const normalizedCode = normalizeCode(ticketCode);
    const ticket = tickets.find((entry) => entry.code === normalizedCode);

    if (!ticket) {
        return {
            code: normalizedCode,
            state: 'invalid',
            ticket: null,
            checkedAt: DEFAULT_USED_AT,
            message: 'No ticket matches this code in the current ticket set.',
        };
    }

    if (eventSlug && ticket.eventSlug !== eventSlug) {
        return {
            code: normalizedCode,
            state: 'wrong_event',
            ticket,
            checkedAt: DEFAULT_USED_AT,
            message: `Ticket belongs to ${ticket.eventTitle}, not the currently selected event.`,
        };
    }

    if (ticket.status === 'used') {
        return {
            code: normalizedCode,
            state: 'already_used',
            ticket,
            checkedAt: DEFAULT_USED_AT,
            message: `Ticket was already checked in at ${formatDateTime(ticket.usedAt)}.`,
        };
    }

    if (['cancelled', 'voided'].includes(ticket.status)) {
        return {
            code: normalizedCode,
            state: 'cancelled',
            ticket,
            checkedAt: DEFAULT_USED_AT,
            message: 'This ticket is no longer active and cannot be admitted.',
        };
    }

    if (isExpiredTicket(ticket)) {
        return {
            code: normalizedCode,
            state: 'expired',
            ticket,
            checkedAt: DEFAULT_USED_AT,
            message: 'The event date has passed, so this ticket is expired.',
        };
    }

    if (ticket.paymentStatus !== 'paid' || ticket.status !== 'issued') {
        return {
            code: normalizedCode,
            state: 'invalid',
            ticket,
            checkedAt: DEFAULT_USED_AT,
            message: 'This ticket is not ready for admission yet.',
        };
    }

    return {
        code: normalizedCode,
        state: 'valid',
        ticket,
        checkedAt: DEFAULT_USED_AT,
        message: `${ticket.holderName} can be admitted for ${ticket.eventTitle}.`,
    };
}

export function createValidationAttempt(result) {
    return {
        id: `${result.code}-${result.checkedAt}`,
        code: result.code,
        state: result.state,
        eventTitle: result.ticket?.eventTitle ?? 'Unknown',
        checkedAt: result.checkedAt,
    };
}

export function markTicketAsUsed(tickets, ticketId) {
    let updatedTicket = null;

    const nextTickets = tickets.map((ticket) => {
        if (ticket.id !== ticketId) {
            return ticket;
        }

        updatedTicket = {
            ...ticket,
            status: 'used',
            usedAt: DEFAULT_USED_AT,
        };

        return updatedTicket;
    });

    return { nextTickets, updatedTicket };
}

export function voidTicketRecord(tickets, ticketId) {
    let updatedTicket = null;

    const nextTickets = tickets.map((ticket) => {
        if (ticket.id !== ticketId) {
            return ticket;
        }

        updatedTicket = {
            ...ticket,
            status: 'voided',
            usedAt: null,
            notes: ticket.notes
                ? `${ticket.notes} | Ticket voided by admin action.`
                : 'Ticket voided by admin action.',
        };

        return updatedTicket;
    });

    return { nextTickets, updatedTicket };
}

export function reissueTicketRecord(tickets, ticketId) {
    let updatedTicket = null;

    const nextTickets = tickets.map((ticket) => {
        if (ticket.id !== ticketId) {
            return ticket;
        }

        updatedTicket = {
            ...ticket,
            status: 'issued',
            code: `${ticket.code}-R`,
            deliveryMethod: 'Pass reissued',
            notes: ticket.notes
                ? `${ticket.notes} | Ticket reissued with a fresh pass code.`
                : 'Ticket reissued with a fresh pass code.',
        };

        return updatedTicket;
    });

    return { nextTickets, updatedTicket };
}

export function resendTicketRecord(tickets, ticketId) {
    let updatedTicket = null;

    const nextTickets = tickets.map((ticket) => {
        if (ticket.id !== ticketId) {
            return ticket;
        }

        updatedTicket = {
            ...ticket,
            notes: ticket.notes
                ? `${ticket.notes} | Ticket resend triggered by admin action.`
                : 'Ticket resend triggered by admin action.',
        };

        return updatedTicket;
    });

    return { nextTickets, updatedTicket };
}

export function formatDateTime(value) {
    if (!value) {
        return 'Not recorded';
    }

    return new Intl.DateTimeFormat('en-ZA', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
