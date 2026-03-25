import axios from 'axios';

const adminApi = axios.create({
    baseURL: '/api/v1/admin',
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

function authConfig(accessToken) {
    return {
        headers: {
            Authorization: `Bearer ${accessToken}`,
        },
    };
}

function authQueryConfig(accessToken, params = {}) {
    return {
        ...authConfig(accessToken),
        params,
    };
}

function parseDownloadFilename(contentDisposition, fallbackFilename) {
    const utf8Match = contentDisposition?.match(/filename\*=UTF-8''([^;]+)/i);

    if (utf8Match?.[1]) {
        return decodeURIComponent(utf8Match[1]);
    }

    const basicMatch = contentDisposition?.match(/filename="?([^";]+)"?/i);

    if (basicMatch?.[1]) {
        return basicMatch[1];
    }

    return fallbackFilename;
}

async function fetchAdminDownload(accessToken, path, options = {}) {
    const {
        fallbackError = 'Unable to download the requested file.',
        fallbackFilename = 'download',
        mode = 'download',
        params = {},
    } = options;
    const url = new URL(`/api/v1/admin${path}`, window.location.origin);

    for (const [key, value] of Object.entries(params)) {
        if (value === undefined || value === null || value === '') {
            continue;
        }

        url.searchParams.set(key, String(value));
    }

    const response = await fetch(url.toString(), {
        headers: {
            Accept: '*/*',
            Authorization: `Bearer ${accessToken}`,
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        try {
            const payload = await response.json();
            const firstError = Object.values(payload?.errors ?? {}).flat().find(Boolean);
            const message = payload?.message || firstError || fallbackError;

            throw new Error(message);
        } catch (error) {
            if (error instanceof Error && error.message !== fallbackError) {
                throw error;
            }

            throw new Error(fallbackError);
        }
    }

    const blob = await response.blob();
    const filename = parseDownloadFilename(response.headers.get('content-disposition'), fallbackFilename);
    const blobUrl = window.URL.createObjectURL(blob);

    if (mode === 'open') {
        const openedWindow = window.open(blobUrl, '_blank', 'noopener,noreferrer');

        if (!openedWindow) {
            const anchor = document.createElement('a');
            anchor.href = blobUrl;
            anchor.download = filename;
            document.body.appendChild(anchor);
            anchor.click();
            anchor.remove();
        }
    } else {
        const anchor = document.createElement('a');
        anchor.href = blobUrl;
        anchor.download = filename;
        document.body.appendChild(anchor);
        anchor.click();
        anchor.remove();
    }

    window.setTimeout(() => {
        window.URL.revokeObjectURL(blobUrl);
    }, 60000);

    return filename;
}

export function extractApiErrorMessage(error, fallbackMessage) {
    const responseData = error?.response?.data;

    if (typeof responseData?.message === 'string' && responseData.message.trim()) {
        return responseData.message;
    }

    const firstError = Object.values(responseData?.errors ?? {}).flat().find(Boolean);

    if (typeof firstError === 'string' && firstError.trim()) {
        return firstError;
    }

    return fallbackMessage;
}

export async function loginAdmin(credentials) {
    const { data } = await adminApi.post('/auth/login', credentials);

    return data;
}

export async function fetchAdminMe(accessToken) {
    const { data } = await adminApi.get('/auth/me', authConfig(accessToken));

    return data;
}

export async function logoutAdmin(accessToken) {
    const { data } = await adminApi.post('/auth/logout', {}, authConfig(accessToken));

    return data;
}

export async function changeAdminPassword(accessToken, payload) {
    const { data } = await adminApi.post('/auth/change-password', payload, authConfig(accessToken));

    return data;
}

export async function fetchAdminOverview(accessToken) {
    const { data } = await adminApi.get('/overview', authConfig(accessToken));

    return data;
}

export async function fetchAdminEvents(accessToken) {
    const { data } = await adminApi.get('/events', authConfig(accessToken));

    return data;
}

export async function fetchAdminTickets(accessToken, query = {}) {
    const { data } = await adminApi.get('/tickets', authQueryConfig(accessToken, query));

    return data;
}

export async function validateAdminTicket(accessToken, payload) {
    const { data } = await adminApi.post('/tickets/validate', payload, authConfig(accessToken));

    return data;
}

export async function markAdminTicketUsed(accessToken, ticketId) {
    const { data } = await adminApi.post(`/tickets/${ticketId}/mark-used`, {}, authConfig(accessToken));

    return data;
}

export async function voidAdminTicket(accessToken, ticketId) {
    const { data } = await adminApi.post(`/tickets/${ticketId}/void`, {}, authConfig(accessToken));

    return data;
}

export async function reissueAdminTicket(accessToken, ticketId) {
    const { data } = await adminApi.post(`/tickets/${ticketId}/reissue`, {}, authConfig(accessToken));

    return data;
}

export async function resendAdminTicket(accessToken, ticketId) {
    const { data } = await adminApi.post(`/tickets/${ticketId}/resend`, {}, authConfig(accessToken));

    return data;
}

export async function downloadAdminTicketPass(accessToken, ticketId) {
    return fetchAdminDownload(accessToken, `/tickets/${ticketId}/download`, {
        fallbackError: 'Unable to download the ticket pass.',
        fallbackFilename: `ticket-${ticketId}.pdf`,
    });
}

export async function fetchAdminOrders(accessToken, query = {}) {
    const { data } = await adminApi.get('/orders', authQueryConfig(accessToken, query));

    return data;
}

export async function updateAdminOrderStatus(accessToken, orderId, payload) {
    const { data } = await adminApi.post(`/orders/${orderId}/status`, payload, authConfig(accessToken));

    return data;
}

export async function confirmAdminOrderPayment(accessToken, orderId) {
    const { data } = await adminApi.post(`/orders/${orderId}/confirm-payment`, {}, authConfig(accessToken));

    return data;
}

export async function refundAdminOrder(accessToken, orderId) {
    const { data } = await adminApi.post(`/orders/${orderId}/refund`, {}, authConfig(accessToken));

    return data;
}

export async function cancelAdminOrder(accessToken, orderId) {
    const { data } = await adminApi.post(`/orders/${orderId}/cancel`, {}, authConfig(accessToken));

    return data;
}

export async function resendAdminOrderDelivery(accessToken, orderId) {
    const { data } = await adminApi.post(`/orders/${orderId}/resend`, {}, authConfig(accessToken));

    return data;
}

export async function openAdminOrderDocument(accessToken, orderId) {
    return fetchAdminDownload(accessToken, `/orders/${orderId}/invoice`, {
        fallbackError: 'Unable to open the order document.',
        fallbackFilename: `order-${orderId}.html`,
        mode: 'open',
    });
}

export async function fetchAdminCustomers(accessToken, query = {}) {
    const { data } = await adminApi.get('/customers', authQueryConfig(accessToken, query));

    return data;
}

export async function fetchAdminContactMessages(accessToken, query = {}) {
    const { data } = await adminApi.get('/contact-messages', authQueryConfig(accessToken, query));

    return data;
}

export async function replyToAdminContactMessage(accessToken, messageId, payload) {
    const { data } = await adminApi.post(`/contact-messages/${messageId}/reply`, payload, authConfig(accessToken));

    return data;
}

export async function updateAdminContactMessageStatus(accessToken, messageId, payload) {
    const { data } = await adminApi.post(`/contact-messages/${messageId}/status`, payload, authConfig(accessToken));

    return data;
}

export async function fetchAdminPayments(accessToken, query = {}) {
    const { data } = await adminApi.get('/payments', authQueryConfig(accessToken, query));

    return data;
}

export async function reconcileAdminPayment(accessToken, paymentId) {
    const { data } = await adminApi.post(`/payments/${paymentId}/reconcile`, {}, authConfig(accessToken));

    return data;
}

export async function markAdminPaymentFailed(accessToken, paymentId) {
    const { data } = await adminApi.post(`/payments/${paymentId}/mark-failed`, {}, authConfig(accessToken));

    return data;
}

export async function refundAdminPayment(accessToken, paymentId) {
    const { data } = await adminApi.post(`/payments/${paymentId}/refund`, {}, authConfig(accessToken));

    return data;
}

export async function attachAdminPaymentNote(accessToken, paymentId, payload) {
    const { data } = await adminApi.post(`/payments/${paymentId}/note`, payload, authConfig(accessToken));

    return data;
}

export async function fetchAdminReportsSummary(accessToken, query = {}) {
    const { data } = await adminApi.get('/reports/summary', authQueryConfig(accessToken, query));

    return data;
}

export async function exportAdminReportFile(accessToken, query = {}) {
    const format = String(query.format ?? 'csv').toLowerCase();

    return fetchAdminDownload(accessToken, '/reports/export', {
        fallbackError: 'Unable to export the report.',
        fallbackFilename: `admin-report-${query.period ?? 'weekly'}.${format === 'csv' ? 'csv' : 'html'}`,
        mode: format === 'print' ? 'open' : 'download',
        params: query,
    });
}

export async function createAdminManualSale(accessToken, payload) {
    const { data } = await adminApi.post('/manual-sales', payload, authConfig(accessToken));

    return data;
}
