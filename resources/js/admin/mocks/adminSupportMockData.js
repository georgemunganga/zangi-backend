export const initialContactMessages = [
    {
        id: 'contact_3001',
        subject: 'Please resend my launch ticket',
        customerName: 'Temwani Daka',
        email: 'temwani@zangi.africa',
        phone: '+260971111001',
        status: 'unread',
        receivedAt: '2026-03-19T08:30:00',
        preview: 'I paid yesterday but cannot find the PDF ticket in my inbox.',
        notes: 'Likely resolved by resending the ticket.',
        thread: [
            {
                id: 'contact_3001_msg_1',
                author: 'customer',
                name: 'Temwani Daka',
                sentAt: '2026-03-19T08:30:00',
                body: 'I paid yesterday but cannot find the PDF ticket in my inbox. Please resend it before the event.',
            },
        ],
    },
    {
        id: 'contact_3002',
        subject: 'Offline invoice for group booking',
        customerName: 'Corporate Reads Zambia',
        email: 'bookings@corporatereads.africa',
        phone: '+260971333222',
        status: 'in_progress',
        receivedAt: '2026-03-19T09:05:00',
        preview: 'We need an offline invoice for six workshop seats.',
        notes: 'High-value lead. Follow up in sales and orders.',
        thread: [
            {
                id: 'contact_3002_msg_1',
                author: 'customer',
                name: 'Corporate Reads Zambia',
                sentAt: '2026-03-19T09:05:00',
                body: 'We need an offline invoice for six workshop seats and would like to pay by bank transfer.',
            },
            {
                id: 'contact_3002_msg_2',
                author: 'admin',
                name: 'Admin',
                sentAt: '2026-03-19T09:28:00',
                body: 'Received. We are preparing an invoice and will confirm seat availability shortly.',
            },
        ],
    },
    {
        id: 'contact_3003',
        subject: 'Can I upgrade to VIP?',
        customerName: 'Faith Chanda',
        email: 'faith@mediahub.africa',
        phone: '+260971111008',
        status: 'read',
        receivedAt: '2026-03-19T10:12:00',
        preview: 'I currently have a standard allocation and want to switch if VIP is available.',
        notes: 'VIP availability check needed.',
        thread: [
            {
                id: 'contact_3003_msg_1',
                author: 'customer',
                name: 'Faith Chanda',
                sentAt: '2026-03-19T10:12:00',
                body: 'I currently have a standard allocation and want to switch if VIP is available.',
            },
        ],
    },
    {
        id: 'contact_3004',
        subject: 'Refund follow-up',
        customerName: 'Mutinta Sakala',
        email: 'mutinta@example.com',
        phone: '+260971111004',
        status: 'replied',
        receivedAt: '2026-03-18T16:40:00',
        preview: 'Thank you for the refund, please confirm the final settlement date.',
        notes: 'Awaiting bank settlement confirmation.',
        thread: [
            {
                id: 'contact_3004_msg_1',
                author: 'customer',
                name: 'Mutinta Sakala',
                sentAt: '2026-03-18T16:40:00',
                body: 'Thank you for the refund. Please confirm the final settlement date to my bank account.',
            },
            {
                id: 'contact_3004_msg_2',
                author: 'admin',
                name: 'Admin',
                sentAt: '2026-03-18T17:05:00',
                body: 'The refund has been processed and should reflect within 3 to 5 business days.',
            },
        ],
    },
    {
        id: 'contact_3005',
        subject: 'Suspicious payment link',
        customerName: 'Unknown sender',
        email: 'noreply@bad-link.example',
        phone: '',
        status: 'spam',
        receivedAt: '2026-03-19T07:55:00',
        preview: 'This looks unrelated to customer support and should be ignored.',
        notes: 'Marked spam during triage.',
        thread: [
            {
                id: 'contact_3005_msg_1',
                author: 'customer',
                name: 'Unknown sender',
                sentAt: '2026-03-19T07:55:00',
                body: 'This looks unrelated to customer support and should be ignored.',
            },
        ],
    },
];

function getCustomerKey(entry) {
    return entry.customerId || entry.email || entry.phone || entry.customerName || entry.buyerName || entry.holderName;
}

const customerTypePriority = {
    '': 0,
    Individual: 1,
    'Walk-in': 2,
    Wholesale: 3,
    Corporate: 4,
};

const relationshipTypePriority = {
    '': 0,
    'Walk-in': 1,
    Existing: 2,
};

function preferValue(current, nextValue, priorityMap) {
    const currentPriority = priorityMap[current ?? ''] ?? 0;
    const nextPriority = priorityMap[nextValue ?? ''] ?? 0;

    return nextPriority > currentPriority ? nextValue : current;
}

function inferCustomerType(entry) {
    if (entry.customerType) {
        return entry.customerType;
    }

    const identity = [entry.customerName, entry.buyerName, entry.email].join(' ').toLowerCase();

    if (entry.source === 'admin_manual') {
        return 'Walk-in';
    }

    if (identity.includes('corporate') || identity.includes('ltd') || identity.includes('plc')) {
        return 'Corporate';
    }

    return 'Individual';
}

function inferRelationshipType(entry) {
    if (entry.relationshipType) {
        return entry.relationshipType;
    }

    return entry.source === 'admin_manual' ? 'Walk-in' : 'Existing';
}

function createCustomerShell(entry) {
    return {
        id: entry.customerId || `customer_${getCustomerKey(entry).replace(/[^a-zA-Z0-9]/g, '_')}`,
        name: entry.customerName || entry.buyerName || entry.holderName || 'Unknown customer',
        customerType: inferCustomerType(entry),
        relationshipType: inferRelationshipType(entry),
        email: entry.email || '',
        phone: entry.phone || '',
        notes: [],
        tags: new Set(),
        purchaseHistory: [],
        attendanceHistory: [],
        totalSpent: 0,
        lastActivityAt: entry.createdAt || entry.issuedAt || null,
    };
}

export function buildCustomersFromData(orders, tickets) {
    const customers = new Map();

    for (const order of orders) {
        const key = getCustomerKey(order);

        if (!customers.has(key)) {
            customers.set(key, createCustomerShell(order));
        }

        const customer = customers.get(key);
        customer.name = order.customerName || customer.name;
        customer.customerType = preferValue(customer.customerType, inferCustomerType(order), customerTypePriority);
        customer.relationshipType = preferValue(
            customer.relationshipType,
            inferRelationshipType(order),
            relationshipTypePriority,
        );
        customer.email = order.email || customer.email;
        customer.phone = order.phone || customer.phone;
        customer.purchaseHistory.push(order);
        customer.totalSpent += order.paymentStatus === 'paid' ? order.total : 0;
        customer.lastActivityAt =
            !customer.lastActivityAt || new Date(order.createdAt) > new Date(customer.lastActivityAt)
                ? order.createdAt
                : customer.lastActivityAt;

        if (order.source === 'admin_manual') {
            customer.tags.add('Manual buyer');
        }

        if (order.type === 'mixed') {
            customer.tags.add('Mixed order');
        }

        if (order.notes) {
            customer.notes.push(order.notes);
        }

        for (const tag of order.tags || []) {
            customer.tags.add(tag);
        }
    }

    for (const ticket of tickets) {
        const key = getCustomerKey(ticket);

        if (!customers.has(key)) {
            customers.set(key, createCustomerShell(ticket));
        }

        const customer = customers.get(key);
        customer.name = ticket.buyerName || ticket.holderName || customer.name;
        customer.customerType = preferValue(customer.customerType, inferCustomerType(ticket), customerTypePriority);
        customer.relationshipType = preferValue(
            customer.relationshipType,
            inferRelationshipType(ticket),
            relationshipTypePriority,
        );
        customer.email = ticket.email || customer.email;
        customer.phone = ticket.phone || customer.phone;
        customer.attendanceHistory.push(ticket);
        customer.lastActivityAt =
            !customer.lastActivityAt || new Date(ticket.issuedAt) > new Date(customer.lastActivityAt)
                ? ticket.issuedAt
                : customer.lastActivityAt;

        if (ticket.ticketType === 'VIP') {
            customer.tags.add('VIP');
        }

        if (ticket.status === 'used') {
            customer.tags.add('Attended');
        }

        if (ticket.paymentStatus === 'pending') {
            customer.tags.add('Pending payment');
        }

        if (ticket.notes) {
            customer.notes.push(ticket.notes);
        }

        for (const tag of ticket.tags || []) {
            customer.tags.add(tag);
        }
    }

    return [...customers.values()]
        .map((customer) => ({
            ...customer,
            tags: [...customer.tags].slice(0, 4),
            notes: customer.notes.filter(Boolean),
            purchaseHistory: [...customer.purchaseHistory].sort(
                (left, right) => new Date(right.createdAt).getTime() - new Date(left.createdAt).getTime(),
            ),
            attendanceHistory: [...customer.attendanceHistory].sort(
                (left, right) => new Date(right.issuedAt).getTime() - new Date(left.issuedAt).getTime(),
            ),
        }))
        .sort((left, right) => new Date(right.lastActivityAt).getTime() - new Date(left.lastActivityAt).getTime());
}

export function updateContactStatus(contactMessages, messageId, nextStatus) {
    let updatedMessage = null;

    const nextMessages = contactMessages.map((message) => {
        if (message.id !== messageId) {
            return message;
        }

        updatedMessage = {
            ...message,
            status: nextStatus,
        };

        return updatedMessage;
    });

    return { nextMessages, updatedMessage };
}

export function replyToContactMessage(contactMessages, messageId, body) {
    let updatedMessage = null;

    const nextMessages = contactMessages.map((message) => {
        if (message.id !== messageId) {
            return message;
        }

        updatedMessage = {
            ...message,
            status: 'replied',
            thread: [
                ...message.thread,
                {
                    id: `${message.id}_reply_${message.thread.length + 1}`,
                    author: 'admin',
                    name: 'Admin',
                    sentAt: '2026-03-19T18:45:00',
                    body,
                },
            ],
        };

        return updatedMessage;
    });

    return { nextMessages, updatedMessage };
}
