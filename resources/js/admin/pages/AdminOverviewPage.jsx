import { CalendarDays, ClipboardList, CreditCard, Ticket, TriangleAlert } from 'lucide-react';
import { AdminPageHeader } from '../components/AdminPageHeader';
import { AdminSectionCard } from '../components/AdminSectionCard';
import { AdminStatCard } from '../components/AdminStatCard';
import { useAdminMockData } from '../mocks/AdminMockDataProvider';

export function AdminOverviewPage() {
    const { isReadDataLoading, overview, readDataError } = useAdminMockData();
    const iconMap = {
        Revenue: CreditCard,
        'Tickets Sold': Ticket,
        'Manual Sales': ClipboardList,
        Orders: ClipboardList,
        'Unpaid Sales': TriangleAlert,
        'Failed Payments': TriangleAlert,
    };

    return (
        <div className="min-w-0 space-y-6">
            <AdminPageHeader title="Overview" />

            {readDataError ? (
                <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {readDataError}
                </div>
            ) : null}

            {isReadDataLoading ? (
                <div className="rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-3 text-sm text-[color:var(--admin-muted)]">
                    Loading overview...
                </div>
            ) : null}

            <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {overview.stats.map((stat) => (
                    <AdminStatCard icon={iconMap[stat.label]} key={stat.label} {...stat} />
                ))}
            </section>

            <section className="grid min-w-0 gap-4 xl:grid-cols-[1.2fr_0.9fr]">
                <AdminSectionCard
                    eyebrow="Recent"
                    icon={ClipboardList}
                    title="Recent activity"
                >
                    <div className="space-y-4">
                        <div>
                            <p className="text-sm font-semibold text-[color:var(--admin-ink)]">Orders</p>
                            <ul className="mt-3 space-y-2 text-sm leading-6 text-[color:var(--admin-muted)]">
                                {overview.recentOrders.map((item) => (
                                    <li key={item} className="rounded-2xl bg-stone-50 px-4 py-3">
                                        {item}
                                    </li>
                                ))}
                            </ul>
                        </div>
                        <div>
                            <p className="text-sm font-semibold text-[color:var(--admin-ink)]">Contact messages</p>
                            <ul className="mt-3 space-y-2 text-sm leading-6 text-[color:var(--admin-muted)]">
                                {overview.recentMessages.map((item) => (
                                    <li key={item} className="rounded-2xl bg-stone-50 px-4 py-3">
                                        {item}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                </AdminSectionCard>

                <div className="space-y-4">
                    <AdminSectionCard eyebrow="Upcoming" icon={CalendarDays} title="Events">
                        <div className="space-y-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                            {overview.upcomingEvents.map((event) => (
                                <div
                                    className="rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-3"
                                    key={event.slug}
                                >
                                    {event.dateLabel}: {event.title}, {event.venue}
                                </div>
                            ))}
                        </div>
                    </AdminSectionCard>

                    <AdminSectionCard eyebrow="Attention" icon={TriangleAlert} title="Needs attention">
                        <ul className="space-y-2 text-sm leading-6 text-[color:var(--admin-muted)]">
                            {overview.actionQueue.map((item) => (
                                <li key={item} className="rounded-2xl bg-stone-50 px-4 py-3">
                                    {item}
                                </li>
                            ))}
                        </ul>
                    </AdminSectionCard>
                </div>
            </section>
        </div>
    );
}
