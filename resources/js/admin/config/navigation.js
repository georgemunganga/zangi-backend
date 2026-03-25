import {
    ArrowRightLeft,
    BarChart3,
    ClipboardList,
    CreditCard,
    LayoutDashboard,
    MessageSquareMore,
    ScanLine,
    Settings2,
    ShoppingCart,
    Ticket,
    Users,
} from 'lucide-react';

export const adminSections = [
    {
        key: 'overview',
        label: 'Overview',
        shortLabel: 'Home',
        href: '/admin/overview',
        icon: LayoutDashboard,
        summary: 'Daily activity',
    },
    {
        key: 'tickets',
        label: 'Tickets',
        shortLabel: 'Tickets',
        href: '/admin/tickets',
        icon: Ticket,
        summary: 'Tickets',
    },
    {
        key: 'validation',
        label: 'Validation',
        shortLabel: 'Scan',
        href: '/admin/tickets/validation',
        icon: ScanLine,
        summary: 'Validation',
    },
    {
        key: 'sellers',
        label: 'Sellers',
        shortLabel: 'Sellers',
        href: '/admin/sellers',
        icon: ArrowRightLeft,
        summary: 'Field agents',
    },
    {
        key: 'manual-sales',
        label: 'Manual Sales',
        shortLabel: 'POS',
        href: '/admin/manual-sales',
        icon: ShoppingCart,
        summary: 'Manual sales',
    },
    {
        key: 'orders',
        label: 'Orders',
        shortLabel: 'Orders',
        href: '/admin/orders',
        icon: ClipboardList,
        summary: 'Orders',
    },
    {
        key: 'customers',
        label: 'Customers',
        shortLabel: 'People',
        href: '/admin/customers',
        icon: Users,
        summary: 'Customers',
    },
    {
        key: 'contact',
        label: 'Contact',
        shortLabel: 'Inbox',
        href: '/admin/contact',
        icon: MessageSquareMore,
        summary: 'Messages',
    },
    {
        key: 'payments',
        label: 'Payments',
        shortLabel: 'Pay',
        href: '/admin/payments',
        icon: CreditCard,
        summary: 'Payments',
    },
    {
        key: 'reports',
        label: 'Reports',
        shortLabel: 'Reports',
        href: '/admin/reports',
        icon: BarChart3,
        summary: 'Reports',
    },
    {
        key: 'settings',
        label: 'Settings',
        shortLabel: 'Settings',
        href: '/admin/settings',
        icon: Settings2,
        summary: 'Settings',
    },
];

export const mobilePrimaryKeys = ['overview', 'tickets', 'orders', 'payments'];

export const pageBlueprints = {
    tickets: {
        title: 'Tickets',
        description: 'Review tickets and take action.',
        primaryItems: [
            'Search by code, name, email, or phone',
            'Filter by event, ticket type, status, payment status, and source',
            'Open ticket details',
        ],
        deliveryItems: [
            'Validate, mark used, void, reissue, resend, and download',
            'Quick status badges',
            'Clear ticket history',
        ],
    },
    validation: {
        title: 'Ticket Validation',
        description: 'Check ticket codes.',
        primaryItems: [
            'Enter a ticket code',
            'See clear result states',
            'Check the selected event before entry',
        ],
        deliveryItems: [
            'Clear result card',
            'Recent checks',
            'Wrong-event warning',
        ],
    },
    'manual-sales': {
        title: 'Manual Sales',
        description: 'Create manual sales.',
        primaryItems: [
            'Choose the customer',
            'Choose tickets or books',
            'Capture only what is needed',
        ],
        deliveryItems: [
            'Step-by-step flow',
            'Fast item selection',
            'Recent sale summary',
        ],
    },
    orders: {
        title: 'Orders',
        description: 'Review and update orders.',
        primaryItems: [
            'Filter by type, status, payment method, and source',
            'Inspect order details',
            'Confirm, cancel, refund, or send',
        ],
        deliveryItems: [
            'Status badges',
            'Receipts',
            'Order history',
        ],
    },
    customers: {
        title: 'Customers',
        description: 'Find customers and review their history.',
        primaryItems: ['Contact info', 'Purchase history', 'Attendance history'],
        deliveryItems: ['Customer summary', 'Recent activity', 'Notes and tags'],
    },
    contact: {
        title: 'Contact Messages',
        description: 'Read and reply to messages.',
        primaryItems: [
            'Update message status',
            'Read the full thread',
            'Reply from one place',
        ],
        deliveryItems: [
            'Search the inbox',
            'Quick actions',
            'Reply history',
        ],
    },
    payments: {
        title: 'Payments',
        description: 'Review and update payments.',
        primaryItems: [
            'Review payment details',
            'Reconcile or refund',
            'Add notes',
        ],
        deliveryItems: [
            'Status badges',
            'Quick filters',
            'Receipt access',
        ],
    },
    reports: {
        title: 'Reports',
        description: 'View totals and export reports.',
        primaryItems: [
            'Daily, weekly, and monthly toggles',
            'Summary cards',
            'Exports',
        ],
        deliveryItems: [
            'Period switch',
            'Export buttons',
            'Summary totals',
        ],
    },
    settings: {
        title: 'Settings',
        description: 'Manage your account.',
        primaryItems: ['Profile details', 'Change password', 'Recent account info'],
        deliveryItems: [
            'Account details',
            'Password updates',
            'Sign-in details',
        ],
    },
};

export function getSectionByPath(pathname) {
    if (pathname === '/admin') {
        return adminSections[0];
    }

    const rankedSections = [...adminSections].sort((left, right) => right.href.length - left.href.length);

    return (
        rankedSections.find(
            (section) => pathname === section.href || (pathname.startsWith(`${section.href}/`) && section.href !== '/admin/overview'),
        ) ?? null
    );
}
