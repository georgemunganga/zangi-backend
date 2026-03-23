export function buildPaymentsFromOrders(orders) {
    return [...orders]
        .map((order) => ({
            id: `payment_${order.id}`,
            orderId: order.id,
            reference: `PAY-${order.reference}`,
            orderReference: order.reference,
            customerName: order.customerName,
            email: order.email,
            amount: order.total,
            currency: order.currency,
            method: order.paymentMethod,
            status: order.paymentStatus,
            source: order.source,
            type: order.type,
            createdAt: order.createdAt,
            notes: order.notes,
        }))
        .sort((left, right) => new Date(right.createdAt).getTime() - new Date(left.createdAt).getTime());
}

function updateLinkedTickets(tickets, linkedTicketIds, updater) {
    return tickets.map((ticket) => {
        if (!linkedTicketIds.includes(ticket.id)) {
            return ticket;
        }

        return updater(ticket);
    });
}

export function markOrderPaymentFailed(orders, tickets, orderId) {
    let updatedOrder = null;

    const nextOrders = orders.map((order) => {
        if (order.id !== orderId) {
            return order;
        }

        updatedOrder = {
            ...order,
            status: 'failed',
            paymentStatus: 'failed',
            fulfillment: 'Payment marked failed during reconciliation review.',
        };

        return updatedOrder;
    });

    if (!updatedOrder) {
        return { nextOrders: orders, nextTickets: tickets, updatedOrder: null };
    }

    const nextTickets = updateLinkedTickets(tickets, updatedOrder.linkedTicketIds, (ticket) => ({
        ...ticket,
        status: ['used', 'refunded', 'cancelled'].includes(ticket.status) ? ticket.status : 'voided',
        paymentStatus: 'failed',
    }));

    return { nextOrders, nextTickets, updatedOrder };
}

export function appendOrderNote(orders, orderId, note) {
    let updatedOrder = null;

    const nextOrders = orders.map((order) => {
        if (order.id !== orderId) {
            return order;
        }

        updatedOrder = {
            ...order,
            notes: order.notes ? `${order.notes} | ${note}` : note,
        };

        return updatedOrder;
    });

    return { nextOrders, updatedOrder };
}
