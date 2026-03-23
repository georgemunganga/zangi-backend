import { AdminPageHeader } from '../components/AdminPageHeader';
import { AdminSectionCard } from '../components/AdminSectionCard';
import { pageBlueprints } from '../config/navigation';

function Checklist({ items }) {
    return (
        <ul className="space-y-2 text-sm leading-6 text-[color:var(--admin-muted)]">
            {items.map((item) => (
                <li key={item} className="rounded-2xl bg-stone-50 px-4 py-3">
                    {item}
                </li>
            ))}
        </ul>
    );
}

export function AdminPlaceholderPage({ sectionKey }) {
    const page = pageBlueprints[sectionKey];

    return (
        <div className="space-y-6">
            <AdminPageHeader title={page.title} />

            <section className="grid gap-4 xl:grid-cols-2">
                <AdminSectionCard eyebrow="Tasks" title="What to manage">
                    <Checklist items={page.primaryItems} />
                </AdminSectionCard>
                <AdminSectionCard eyebrow="Checklist" title="Coming next">
                    <Checklist items={page.deliveryItems} />
                </AdminSectionCard>
            </section>

            <AdminSectionCard eyebrow="Status" title="Section status">
                <div className="grid gap-3 md:grid-cols-3">
                    <div className="rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-4">
                        <p className="text-sm font-semibold text-[color:var(--admin-ink)]">Screen</p>
                        <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">
                            This section is available in the admin menu.
                        </p>
                    </div>
                    <div className="rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-4">
                        <p className="text-sm font-semibold text-[color:var(--admin-ink)]">Data</p>
                        <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">
                            This section still needs full data and actions.
                        </p>
                    </div>
                    <div className="rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-4">
                        <p className="text-sm font-semibold text-[color:var(--admin-ink)]">Next step</p>
                        <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">
                            Finish the working screen for this section.
                        </p>
                    </div>
                </div>
            </AdminSectionCard>
        </div>
    );
}
