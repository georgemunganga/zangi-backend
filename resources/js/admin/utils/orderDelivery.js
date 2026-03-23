export function getOrderDeliveryMeta(order) {
    if (!order) {
        return {
            actionLabel: 'Resend order detail',
            disabled: true,
            helperText: 'Choose an order first.',
            successFallback: 'Order detail email sent.',
        };
    }

    if (order.deliveryKind === 'ticket') {
        const disabled =
            !order.email ||
            order.paymentStatus !== 'paid' ||
            ['cancelled', 'refunded', 'voided', 'failed'].includes(order.status);

        return {
            actionLabel: 'Resend ticket',
            disabled,
            helperText: 'Sends the ticket to the customer email on file.',
            successFallback: 'Ticket email sent.',
        };
    }

    if (order.deliveryKind === 'digital_book') {
        return {
            actionLabel: 'Send digital book',
            disabled: !order.email,
            helperText: 'Sends the book to the customer email on file.',
            successFallback: 'Digital book email sent.',
        };
    }

    return {
        actionLabel: 'Resend order detail',
        disabled: !order.email,
        helperText: 'Sends the order details to the customer email on file.',
        successFallback: 'Order detail email sent.',
    };
}
