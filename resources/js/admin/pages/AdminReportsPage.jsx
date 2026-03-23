import { useEffect, useState } from 'react';
import {
    exportAdminReportFile,
    extractApiErrorMessage,
    fetchAdminReportsSummary,
} from '../api/adminApiClient';
import { useAdminAuth } from '../auth/AdminAuthProvider';
import { AdminEmptyState } from '../components/AdminEmptyState';
import { AdminPageHeader } from '../components/AdminPageHeader';
import { AdminSectionCard } from '../components/AdminSectionCard';

function SummaryCard({ label, value, detail }) {
    return (
        <div className="rounded-[1.5rem] border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-strong)] p-4 shadow-[0_14px_34px_rgba(23,33,38,0.05)]">
            <p className="text-sm font-medium text-[color:var(--admin-muted)]">{label}</p>
            <p className="mt-3 text-2xl font-semibold tracking-tight text-[color:var(--admin-ink)]">{value}</p>
            <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">{detail}</p>
        </div>
    );
}

const periodOptions = [
    { value: 'daily', label: 'Daily' },
    { value: 'weekly', label: 'Weekly' },
    { value: 'monthly', label: 'Monthly' },
];

const defaultExportOptions = [
    { format: 'CSV', description: 'Orders, tickets, and payment status summary' },
    { format: 'Print', description: 'Printable management snapshot for the selected reporting window' },
];

export function AdminReportsPage() {
    const { accessToken, isAuthenticated } = useAdminAuth();
    const [period, setPeriod] = useState('weekly');
    const [feedback, setFeedback] = useState('');
    const [reportError, setReportError] = useState('');
    const [exportError, setExportError] = useState('');
    const [exportingFormat, setExportingFormat] = useState('');
    const [isReportLoading, setIsReportLoading] = useState(false);
    const [liveReport, setLiveReport] = useState(null);

    useEffect(() => {
        if (!isAuthenticated || !accessToken) {
            setLiveReport(null);
            setReportError('');
            setIsReportLoading(false);
            return;
        }

        let cancelled = false;

        setIsReportLoading(true);
        setReportError('');

        fetchAdminReportsSummary(accessToken, { period })
            .then((response) => {
                if (!cancelled) {
                    setLiveReport(response);
                }
            })
            .catch((error) => {
                if (!cancelled) {
                    setReportError(extractApiErrorMessage(error, 'Unable to load the report summary.'));
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setIsReportLoading(false);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [accessToken, isAuthenticated, period]);

    const report = isAuthenticated && liveReport?.period === period ? liveReport : null;
    const exportOptions = report?.exports ?? defaultExportOptions;

    const handleExport = async (format) => {
        if (!accessToken || !isAuthenticated) {
            setExportError('Sign in to export report files.');
            return;
        }

        setExportError('');
        setExportingFormat(format);

        try {
            await exportAdminReportFile(accessToken, { format, period });
            setFeedback(`${format.toUpperCase()} opened.`);
        } catch (error) {
            setExportError(error.message);
        } finally {
            setExportingFormat('');
        }
    };

    return (
        <div className="min-w-0 space-y-6">
            <AdminPageHeader title="Reports" />

            {reportError ? (
                <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {reportError}
                </div>
            ) : null}

            {exportError ? (
                <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {exportError}
                </div>
            ) : null}

            {isReportLoading ? (
                <div className="rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-3 text-sm text-[color:var(--admin-muted)]">
                    Loading report...
                </div>
            ) : null}

            <section className="flex flex-wrap gap-2">
                {periodOptions.map((option) => (
                    <button
                        className={[
                            'rounded-full border px-4 py-2 text-sm font-semibold transition',
                            period === option.value
                                ? 'border-[color:var(--admin-accent)] bg-[color:var(--admin-accent-soft)] text-[color:var(--admin-accent)]'
                                : 'border-[color:var(--admin-border)] bg-white text-[color:var(--admin-ink)] hover:border-[color:var(--admin-accent)]',
                        ].join(' ')}
                        key={option.value}
                        onClick={() => setPeriod(option.value)}
                        type="button"
                    >
                        {option.label}
                    </button>
                ))}
            </section>

            <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                {report ? report.cards.map((card) => <SummaryCard key={card.label} {...card} />) : null}
            </section>

            <section className="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,1.05fr)_420px]">
                <AdminSectionCard eyebrow="Summary" title="Breakdown">
                    {report ? (
                        <div className="grid gap-3 md:grid-cols-2">
                            {report.splits.map((item) => (
                                <div className="rounded-2xl bg-stone-50 px-4 py-4" key={item.label}>
                                    <p className="text-sm font-semibold text-[color:var(--admin-ink)]">{item.label}</p>
                                    <p className="mt-3 text-2xl font-semibold tracking-tight text-[color:var(--admin-ink)]">{item.value}</p>
                                    <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">{item.detail}</p>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <AdminEmptyState
                            description="Try another period."
                            title="No report found"
                        />
                    )}
                </AdminSectionCard>

                <AdminSectionCard eyebrow="Exports" title="Exports">
                    {feedback ? (
                        <div className="mb-4 rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-accent-soft)] px-4 py-3 text-sm text-[color:var(--admin-accent)]">
                            {feedback}
                        </div>
                    ) : null}

                    <div className="space-y-3">
                        {exportOptions.map((item) => (
                            <div className="rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-4" key={item.format}>
                                <p className="text-sm font-semibold text-[color:var(--admin-ink)]">{item.format} export</p>
                                <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">{item.description}</p>
                                <button
                                    className="mt-4 inline-flex items-center justify-center rounded-2xl bg-[color:var(--admin-ink)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-black disabled:cursor-not-allowed disabled:opacity-70"
                                    disabled={exportingFormat !== '' && exportingFormat !== item.format.toLowerCase()}
                                    onClick={() => handleExport(item.format.toLowerCase())}
                                    type="button"
                                >
                                    {exportingFormat === item.format.toLowerCase()
                                        ? `Opening ${item.format}...`
                                        : `Open ${item.format}`}
                                </button>
                            </div>
                        ))}
                    </div>
                </AdminSectionCard>
            </section>
        </div>
    );
}
