import { CircleDashed } from 'lucide-react';

export function AdminEmptyState({ title, description, icon: Icon = CircleDashed, action }) {
    return (
        <div className="rounded-[1.75rem] border border-dashed border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] p-8 text-center">
            <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-[1.2rem] bg-white text-[color:var(--admin-accent)] shadow-sm">
                <Icon className="h-6 w-6" />
            </div>
            <h3 className="mt-4 text-lg font-semibold text-[color:var(--admin-ink)]">{title}</h3>
            <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">{description}</p>
            {action ? <div className="mt-5 flex justify-center">{action}</div> : null}
        </div>
    );
}
