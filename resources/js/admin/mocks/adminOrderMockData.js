export const adminBooks = [
    {
        slug: 'zangi-flag-of-kindness',
        title: 'Zangi: The Flag of Kindness',
        allowedBuyerTypes: ['Individual', 'Corporate', 'Wholesale'],
        formats: [
            {
                label: 'Digital',
                price: 300,
                fulfillment: 'Sent by email when ready.',
            },
            {
                label: 'Hardcopy',
                price: 360,
                fulfillment: 'Prepared for print and delivery.',
            },
        ],
    },
    {
        slug: 'zangi-adventure-activity-book',
        title: 'Zangi Adventure Activity Book',
        allowedBuyerTypes: ['Individual', 'Corporate', 'Wholesale'],
        formats: [
            {
                label: 'Digital',
                price: 280,
                fulfillment: 'Sent by email when ready.',
            },
            {
                label: 'Hardcopy',
                price: 530,
                fulfillment: 'Prepared for print and delivery.',
            },
        ],
    },
];

const DEFAULT_CREATED_AT = '2026-03-19T13:30:00';

export const initialAdminOrders = [
    {
        id: 'order_2001',
        reference: 'ZAN-ORD-2001',
        type: 'ticket_only',
        status: 'completed',
        paymentStatus: 'paid',
        paymentMethod: 'Card',
        source: 'online',
        customerName: 'Temwani Daka',
        customerType: 'Individual',
        relationshipType: 'Existing',
        email: 'temwani@zangi.africa',
        phone: '+260971111001',
        currency: 'ZMW',
        total: 850,
        createdAt: '2026-03-18T15:12:00',
        notes: 'Receipt copy requested.',
        fulfillment: 'E-ticket delivered by email.',
        lines: [{ kind: 'ticket', label: 'Lusaka Book Launch Evening - VIP', quantity: 1, unitPrice: 850 }],
        linkedTicketIds: ['ticket_1001'],
    },
    {
        id: 'order_2002',
        reference: 'ZAN-ORD-2002',
        type: 'manual',
        status: 'completed',
        paymentStatus: 'paid',
        paymentMethod: 'Cash',
        source: 'admin_manual',
        customerName: 'Lusungu Phiri',
        customerType: 'Walk-in',
        relationshipType: 'Walk-in',
        email: 'lusungu@example.com',
        phone: '+260971111002',
        currency: 'ZMW',
        total: 420,
        createdAt: '2026-03-18T17:45:00',
        notes: 'Walk-in sale at the office.',
        fulfillment: 'Printed receipt issued at point of sale.',
        lines: [{ kind: 'ticket', label: 'Creative Workshop - Standard', quantity: 1, unitPrice: 420 }],
        linkedTicketIds: ['ticket_1002'],
    },
    {
        id: 'order_2003',
        reference: 'ZAN-ORD-2003',
        type: 'book_only',
        status: 'processing',
        paymentStatus: 'paid',
        paymentMethod: 'Card',
        source: 'online',
        customerName: 'Blessings Tembo',
        customerType: 'Individual',
        relationshipType: 'Existing',
        email: 'blessings@reader.africa',
        phone: '+260971111222',
        currency: 'ZMW',
        total: 300,
        createdAt: '2026-03-18T18:15:00',
        notes: 'Digital delivery confirmed.',
        fulfillment: 'Digital book sent by email.',
        lines: [{ kind: 'book', label: 'Zangi: The Flag of Kindness - Digital', quantity: 1, unitPrice: 300 }],
        linkedTicketIds: [],
    },
    {
        id: 'order_2004',
        reference: 'ZAN-ORD-2004',
        type: 'manual',
        status: 'pending',
        paymentStatus: 'pending',
        paymentMethod: 'Mobile Money',
        source: 'admin_manual',
        customerName: 'Alick Mwila',
        customerType: 'Walk-in',
        relationshipType: 'Walk-in',
        email: 'alick@zangishop.com',
        phone: '+260971111006',
        currency: 'ZMW',
        total: 420,
        createdAt: '2026-03-19T09:05:00',
        notes: 'Reserved pending same-day follow-up payment.',
        fulfillment: 'Awaiting payment before send.',
        lines: [{ kind: 'ticket', label: 'Creative Workshop - Standard', quantity: 1, unitPrice: 420 }],
        linkedTicketIds: ['ticket_1006'],
    },
    {
        id: 'order_2005',
        reference: 'ZAN-ORD-2005',
        type: 'ticket_only',
        status: 'refunded',
        paymentStatus: 'refunded',
        paymentMethod: 'Card',
        source: 'online',
        customerName: 'Mutinta Sakala',
        customerType: 'Individual',
        relationshipType: 'Existing',
        email: 'mutinta@example.com',
        phone: '+260971111004',
        currency: 'ZMW',
        total: 850,
        createdAt: '2026-03-17T08:20:00',
        notes: 'Refund processed after duplicate order claim.',
        fulfillment: 'Order closed after refund.',
        lines: [{ kind: 'ticket', label: 'Lusaka Book Launch Evening - VIP', quantity: 1, unitPrice: 850 }],
        linkedTicketIds: ['ticket_1004'],
    },
    {
        id: 'order_2006',
        reference: 'ZAN-ORD-2006',
        type: 'mixed',
        status: 'failed',
        paymentStatus: 'failed',
        paymentMethod: 'Card',
        source: 'online',
        customerName: 'Precious Sitali',
        customerType: 'Individual',
        relationshipType: 'Existing',
        email: 'precious@example.com',
        phone: '+260971111007',
        currency: 'ZMW',
        total: 1250,
        createdAt: '2026-03-19T11:40:00',
        notes: 'Payment verification failed after retry limit.',
        fulfillment: 'No fulfillment issued.',
        lines: [
            { kind: 'ticket', label: 'VIP Signing Session - VIP', quantity: 1, unitPrice: 950 },
            { kind: 'book', label: 'Zangi: The Flag of Kindness - Digital', quantity: 1, unitPrice: 300 },
        ],
        linkedTicketIds: ['ticket_1007'],
    },
];

function getNextSequence(values, extractor) {
    return values.reduce((max, value) => {
        const extracted = Number.parseInt(extractor(value), 10);

        return Number.isNaN(extracted) ? max : Math.max(max, extracted);
    }, 0) + 1;
}

function buildTicketTimestamp(sequence) {
    const hour = 13 + (sequence % 5);
    const minute = 10 + (sequence % 5) * 6;

    return `2026-03-19T${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}:00`;
}

function getTicketTypeForEvent(events, eventSlug, ticketTypeLabel) {
    const event = events.find((entry) => entry.slug === eventSlug);
    const ticketType = event?.ticketTypes.find((entry) => entry.label === ticketTypeLabel);

    if (!event || !ticketType) {
        throw new Error('Invalid event or ticket type for manual sale.');
    }

    return { event, ticketType };
}

function getBookFormatForSale(books, bookSlug, formatLabel) {
    const book = books.find((entry) => entry.slug === bookSlug);
    const format = book?.formats.find((entry) => entry.label === formatLabel);

    if (!book || !format) {
        throw new Error('Invalid book or format for manual sale.');
    }

    return { book, format };
}

function resolveCustomerProfile(payload) {
    return {
        customerId: payload.customerMode === 'existing' ? payload.existingCustomerId || payload.customerId || '' : '',
        customerName: payload.buyerName,
        customerType: payload.customerType || (payload.customerMode === 'walk_in' ? 'Walk-in' : 'Individual'),
        relationshipType: payload.relationshipType || (payload.customerMode === 'walk_in' ? 'Walk-in' : 'Existing'),
        email: payload.email,
        phone: payload.phone,
    };
}

function resolveSaleConfiguration(issueStatus, saleType, context = {}) {
    if (issueStatus === 'paid') {
        if (saleType === 'ticket') {
            return {
                orderStatus: 'completed',
                ticketStatus: 'issued',
                paymentStatus: 'paid',
                fulfillment: 'Manual tickets issued and ready to print or send.',
                deliveryMethod: 'Email and print ready',
            };
        }

        return {
            orderStatus: 'processing',
            paymentStatus: 'paid',
            fulfillment:
                context.formatLabel === 'Digital'
                    ? 'Digital book queued for delivery.'
                    : 'Book sale captured and hardcopy fulfillment queued.',
        };
    }

    if (issueStatus === 'unpaid') {
        return {
            orderStatus: 'pending',
            ticketStatus: 'pending',
            paymentStatus: 'pending',
            fulfillment:
                saleType === 'ticket'
                    ? 'Awaiting payment before ticket send.'
                    : 'Awaiting payment before book fulfillment.',
            deliveryMethod: saleType === 'ticket' ? 'Hold until payment confirmation' : null,
        };
    }

    return {
        orderStatus: 'pending',
        ticketStatus: 'pending',
        paymentStatus: 'pending',
        fulfillment:
            saleType === 'ticket'
                ? 'Reserved for follow-up payment and send.'
                : 'Reserved for follow-up payment and fulfillment.',
        deliveryMethod: saleType === 'ticket' ? 'Reserved, send later' : null,
    };
}

function updateLinkedTickets(tickets, linkedTicketIds, updater) {
    return tickets.map((ticket) => {
        if (!linkedTicketIds.includes(ticket.id)) {
            return ticket;
        }

        return updater(ticket);
    });
}

export function buildRecentOrders(orders) {
    return [...orders]
        .sort((left, right) => new Date(right.createdAt).getTime() - new Date(left.createdAt).getTime())
        .slice(0, 3)
        .map((order) => `${order.reference} - ${order.customerName} - ${formatCurrency(order.total, order.currency)}`);
}

export function createManualSale({ events, books, orders, tickets, payload }) {
    const nextOrderSequence = getNextSequence(orders, (order) => order.reference.split('-').at(-1));
    const nextTicketSequence = getNextSequence(tickets, (ticket) => ticket.code.split('-').at(-1));
    const createdAt = buildTicketTimestamp(nextOrderSequence);
    const customerProfile = resolveCustomerProfile(payload);

    if (payload.saleType === 'book') {
        const { book, format } = getBookFormatForSale(books, payload.bookSlug, payload.bookFormat);
        const saleConfig = resolveSaleConfiguration(payload.issueStatus, 'book', { formatLabel: format.label });
        const unitPrice = payload.priceMode === 'custom' ? payload.customUnitPrice : format.price;
        const total = unitPrice * payload.quantity;

        const createdOrder = {
            id: `order_${nextOrderSequence}`,
            reference: `ZAN-ORD-${String(nextOrderSequence).padStart(4, '0')}`,
            type: 'book_only',
            status: saleConfig.orderStatus,
            paymentStatus: saleConfig.paymentStatus,
            paymentMethod: payload.paymentMethod,
            source: 'admin_manual',
            ...customerProfile,
            currency: 'ZMW',
            total,
            createdAt,
            notes: payload.notes || 'Created via manual sales workflow.',
            fulfillment: saleConfig.fulfillment || format.fulfillment,
            lines: [
                {
                    kind: 'book',
                    label: `${book.title} - ${format.label}`,
                    quantity: payload.quantity,
                    unitPrice,
                },
            ],
            linkedTicketIds: [],
        };

        return {
            nextOrders: [createdOrder, ...orders],
            nextTickets: tickets,
            createdOrder,
            createdTickets: [],
        };
    }

    const { event, ticketType } = getTicketTypeForEvent(events, payload.eventSlug, payload.ticketType);
    const saleConfig = resolveSaleConfiguration(payload.issueStatus, 'ticket');
    const unitPrice = payload.priceMode === 'custom' ? payload.customUnitPrice : ticketType.price;
    const total = unitPrice * payload.quantity;

    const createdTickets = Array.from({ length: payload.quantity }, (_, index) => {
        const ticketSequence = nextTicketSequence + index;
        const ticketCode = `ZAN-TK-${String(ticketSequence).padStart(4, '0')}`;
        const ticketId = `ticket_${ticketSequence}`;

        return {
            id: ticketId,
            code: ticketCode,
            holderName: payload.quantity > 1 ? `${payload.buyerName} ${index + 1}` : payload.buyerName,
            buyerName: payload.buyerName,
            customerType: customerProfile.customerType,
            relationshipType: customerProfile.relationshipType,
            email: payload.email,
            phone: payload.phone,
            eventSlug: event.slug,
            eventTitle: event.title,
            eventDate: event.date,
            eventDateLabel: event.dateLabel,
            venue: event.venue,
            ticketType: ticketType.label,
            status: saleConfig.ticketStatus,
            paymentStatus: saleConfig.paymentStatus,
            source: 'admin_manual',
            amount: unitPrice,
            currency: 'ZMW',
            paymentMethod: payload.paymentMethod,
            deliveryMethod: saleConfig.deliveryMethod,
            issuedAt: createdAt,
            usedAt: null,
            notes: payload.notes || 'Created via manual sales workflow.',
        };
    });

    const createdOrder = {
        id: `order_${nextOrderSequence}`,
        reference: `ZAN-ORD-${String(nextOrderSequence).padStart(4, '0')}`,
        type: 'manual',
        status: saleConfig.orderStatus,
        paymentStatus: saleConfig.paymentStatus,
        paymentMethod: payload.paymentMethod,
        source: 'admin_manual',
        ...customerProfile,
        currency: 'ZMW',
        total,
        createdAt,
        notes: payload.notes || 'Created via manual sales workflow.',
        fulfillment: saleConfig.fulfillment,
        lines: [
            {
                kind: 'ticket',
                label: `${event.title} - ${ticketType.label}`,
                quantity: payload.quantity,
                unitPrice,
            },
        ],
        linkedTicketIds: createdTickets.map((ticket) => ticket.id),
    };

    return {
        nextOrders: [createdOrder, ...orders],
        nextTickets: [...createdTickets, ...tickets],
        createdOrder,
        createdTickets,
    };
}

export function updateOrderStatus(orders, orderId, nextStatus) {
    let updatedOrder = null;

    const nextOrders = orders.map((order) => {
        if (order.id !== orderId) {
            return order;
        }

        updatedOrder = {
            ...order,
            status: nextStatus,
        };

        return updatedOrder;
    });

    return { nextOrders, updatedOrder };
}

export function confirmOrderPayment(orders, tickets, orderId) {
    let updatedOrder = null;

    const nextOrders = orders.map((order) => {
        if (order.id !== orderId) {
            return order;
        }

        updatedOrder = {
            ...order,
            status: order.type === 'manual' ? 'completed' : 'processing',
            paymentStatus: 'paid',
            fulfillment:
                order.type === 'manual'
                    ? 'Manual tickets issued and ready to print or send.'
                    : 'Payment confirmed. Fulfillment queued.',
        };

        return updatedOrder;
    });

    if (!updatedOrder) {
        return { nextOrders: orders, nextTickets: tickets, updatedOrder: null };
    }

    const nextTickets = updateLinkedTickets(tickets, updatedOrder.linkedTicketIds ?? [], (ticket) => ({
        ...ticket,
        status: ticket.status === 'pending' ? 'issued' : ticket.status,
        paymentStatus: 'paid',
        deliveryMethod:
            ticket.deliveryMethod === 'Reserved, send later' || ticket.deliveryMethod === 'Hold until payment confirmation'
                ? 'Email and print ready'
                : ticket.deliveryMethod,
    }));

    return { nextOrders, nextTickets, updatedOrder };
}

export function cancelOrder(orders, tickets, orderId) {
    let updatedOrder = null;

    const nextOrders = orders.map((order) => {
        if (order.id !== orderId) {
            return order;
        }

        updatedOrder = {
            ...order,
            status: 'cancelled',
            fulfillment: 'Order cancelled by admin action.',
        };

        return updatedOrder;
    });

    if (!updatedOrder) {
        return { nextOrders: orders, nextTickets: tickets, updatedOrder: null };
    }

    const nextTickets = updateLinkedTickets(tickets, updatedOrder.linkedTicketIds ?? [], (ticket) => ({
        ...ticket,
        status: 'cancelled',
    }));

    return { nextOrders, nextTickets, updatedOrder };
}

export function refundOrder(orders, tickets, orderId) {
    let updatedOrder = null;

    const nextOrders = orders.map((order) => {
        if (order.id !== orderId) {
            return order;
        }

        updatedOrder = {
            ...order,
            status: 'refunded',
            paymentStatus: 'refunded',
            fulfillment: 'Refund processed and order closed.',
        };

        return updatedOrder;
    });

    if (!updatedOrder) {
        return { nextOrders: orders, nextTickets: tickets, updatedOrder: null };
    }

    const nextTickets = updateLinkedTickets(tickets, updatedOrder.linkedTicketIds ?? [], (ticket) => ({
        ...ticket,
        status: 'refunded',
        paymentStatus: 'refunded',
    }));

    return { nextOrders, nextTickets, updatedOrder };
}

export function formatCurrency(amount, currency = 'ZMW') {
    return `${currency} ${Number(amount ?? 0).toLocaleString()}`;
}

export function getDefaultManualSaleForm(events, books) {
    const defaultEvent = events[0];
    const defaultTicketType = defaultEvent?.ticketTypes[0]?.label ?? '';
    const defaultBook = books[0];
    const defaultBookFormat = defaultBook?.formats[0]?.label ?? '';

    return {
        customerMode: 'walk_in',
        saleType: 'ticket',
        existingCustomerId: '',
        customerType: 'Walk-in',
        relationshipType: 'Walk-in',
        eventSlug: defaultEvent?.slug ?? '',
        ticketType: defaultTicketType,
        bookSlug: defaultBook?.slug ?? '',
        bookFormat: defaultBookFormat,
        buyerName: '',
        email: '',
        phone: '',
        quantity: 1,
        priceMode: 'standard',
        customUnitPrice: defaultEvent?.ticketTypes[0]?.price ?? defaultBook?.formats[0]?.price ?? 0,
        paymentMethod: 'Cash',
        issueStatus: 'paid',
        notes: '',
    };
}
