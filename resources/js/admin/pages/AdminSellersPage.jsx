import { Key, Plus, Search, Shield, Trash2, UserCheck, UserX, Users } from 'lucide-react';
import { useDeferredValue, useEffect, useState } from 'react';
import { AdminEmptyState } from '../components/AdminEmptyState';
import { AdminPageHeader } from '../components/AdminPageHeader';
import { AdminSectionCard } from '../components/AdminSectionCard';
import { AdminStatCard } from '../components/AdminStatCard';
import { AdminStatusBadge } from '../components/AdminStatusBadge';
import { useAdminApiClient } from '../api/adminApiClient';

function StatusFilterButton({ active, count, label, onClick, variant = 'default' }) {
    const baseClasses =
        'inline-flex items-center justify-between gap-2 rounded-xl border px-4 py-3 text-xs font-semibold uppercase tracking-[0.15em] transition-all';
    
    const variantClasses = active
        ? variant === 'active'
            ? 'border-green-500 bg-green-50 text-green-700'
            : variant === 'suspended'
            ? 'border-red-500 bg-red-50 text-red-700'
            : variant === 'inactive'
            ? 'border-amber-500 bg-amber-50 text-amber-700'
            : 'border-[color:var(--admin-accent)] bg-[color:var(--admin-accent-light)] text-[color:var(--admin-accent)]'
        : 'border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] text-[color:var(--admin-muted)] hover:border-[color:var(--admin-accent)]';

    return (
        <button className={`${baseClasses} ${variantClasses}`} onClick={onClick} type="button">
            <span>{label}</span>
            <span className="rounded-md bg-white/50 px-2 py-0.5 text-[0.65rem]">{count}</span>
        </button>
    );
}

function ActionButton({ children, icon: Icon, onClick, variant = 'default' }) {
    const baseClasses =
        'inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold uppercase tracking-[0.14em] transition hover:scale-105';

    const variantClasses =
        variant === 'danger'
            ? 'border-red-200 bg-red-50 text-red-700 hover:border-red-300 hover:bg-red-100'
            : variant === 'primary'
            ? 'border-[color:var(--admin-accent)] bg-[color:var(--admin-accent)] text-white hover:opacity-90'
            : 'border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] text-[color:var(--admin-muted)] hover:border-[color:var(--admin-accent)] hover:text-[color:var(--admin-accent)]';

    return (
        <button className={`${baseClasses} ${variantClasses}`} onClick={onClick} type="button">
            <Icon className="h-4 w-4" />
            <span>{children}</span>
        </button>
    );
}

export function AdminSellersPage() {
    const api = useAdminApiClient();
    const [sellers, setSellers] = useState([]);
    const [stats, setStats] = useState({ total: 0, active: 0, inactive: 0, suspended: 0 });
    const [isLoading, setIsLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [selectedSeller, setSelectedSeller] = useState(null);
    const [showResetPinModal, setShowResetPinModal] = useState(false);

    const deferredSearch = useDeferredValue(searchTerm);

    const loadSellers = async () => {
        setIsLoading(true);
        try {
            const [sellersRes, statsRes] = await Promise.all([
                api.get('/admin/sellers'),
                api.get('/admin/sellers/stats'),
            ]);
            setSellers(sellersRes.data.data || []);
            setStats(statsRes.data);
        } catch (error) {
            console.error('Failed to load sellers:', error);
        } finally {
            setIsLoading(false);
        }
    };

    useEffect(() => {
        loadSellers();
    }, []);

    const filteredSellers = sellers.filter((seller) => {
        const matchesSearch =
            !deferredSearch ||
            seller.name.toLowerCase().includes(deferredSearch.toLowerCase()) ||
            seller.code.toLowerCase().includes(deferredSearch.toLowerCase()) ||
            seller.phone.includes(deferredSearch);

        const matchesStatus = statusFilter === 'all' || seller.status === statusFilter;

        return matchesSearch && matchesStatus;
    });

    const handleDelete = async (seller) => {
        if (!confirm(`Are you sure you want to delete seller ${seller.name}?`)) {
            return;
        }

        try {
            await api.delete(`/admin/sellers/${seller.id}`);
            await loadSellers();
        } catch (error) {
            alert('Failed to delete seller: ' + (error.response?.data?.message || error.message));
        }
    };

    const handleResetPin = async (sellerId, newPin) => {
        try {
            await api.post(`/admin/sellers/${sellerId}/reset-pin`, { newPin });
            setShowResetPinModal(false);
            setSelectedSeller(null);
            alert('PIN reset successfully');
        } catch (error) {
            alert('Failed to reset PIN: ' + (error.response?.data?.message || error.message));
        }
    };

    return (
        <div className="space-y-6">
            <AdminPageHeader
                actions={
                    <ActionButton icon={Plus} variant="primary" onClick={() => setShowCreateModal(true)}>
                        Add Seller
                    </ActionButton>
                }
                description="Manage field agent accounts and access"
                title="Sellers"
            />

            {/* Stats */}
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <AdminStatCard
                    icon={Users}
                    label="Total Sellers"
                    value={stats.total}
                    variant="default"
                />
                <AdminStatCard
                    icon={UserCheck}
                    label="Active"
                    value={stats.active}
                    variant="success"
                />
                <AdminStatCard
                    icon={UserX}
                    label="Inactive"
                    value={stats.inactive}
                    variant="warning"
                />
                <AdminStatCard
                    icon={Shield}
                    label="Suspended"
                    value={stats.suspended}
                    variant="danger"
                />
            </div>

            {/* Filters */}
            <AdminSectionCard>
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[color:var(--admin-muted)]" />
                        <input
                            className="w-full rounded-xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] py-2.5 pl-10 pr-4 text-sm text-[color:var(--admin-foreground)] placeholder:text-[color:var(--admin-muted)] focus:border-[color:var(--admin-accent)] focus:outline-none"
                            onChange={(e) => setSearchTerm(e.target.value)}
                            placeholder="Search by name, code, or phone..."
                            type="text"
                            value={searchTerm}
                        />
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <StatusFilterButton
                            active={statusFilter === 'all'}
                            count={stats.total}
                            label="All"
                            onClick={() => setStatusFilter('all')}
                        />
                        <StatusFilterButton
                            active={statusFilter === 'active'}
                            count={stats.active}
                            label="Active"
                            onClick={() => setStatusFilter('active')}
                            variant="active"
                        />
                        <StatusFilterButton
                            active={statusFilter === 'inactive'}
                            count={stats.inactive}
                            label="Inactive"
                            onClick={() => setStatusFilter('inactive')}
                            variant="inactive"
                        />
                        <StatusFilterButton
                            active={statusFilter === 'suspended'}
                            count={stats.suspended}
                            label="Suspended"
                            onClick={() => setStatusFilter('suspended')}
                            variant="suspended"
                        />
                    </div>
                </div>
            </AdminSectionCard>

            {/* Table */}
            <AdminSectionCard>
                {isLoading ? (
                    <div className="flex items-center justify-center py-12">
                        <div className="h-8 w-8 animate-spin rounded-full border-4 border-[color:var(--admin-accent)] border-t-transparent" />
                    </div>
                ) : filteredSellers.length === 0 ? (
                    <AdminEmptyState
                        message={
                            searchTerm || statusFilter !== 'all'
                                ? 'No sellers match your filters'
                                : 'No sellers found. Add your first seller to get started.'
                        }
                        title="No Sellers"
                    />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b border-[color:var(--admin-border)]">
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.15em] text-[color:var(--admin-muted)]">
                                        Seller
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.15em] text-[color:var(--admin-muted)]">
                                        Code
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.15em] text-[color:var(--admin-muted)]">
                                        Phone
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.15em] text-[color:var(--admin-muted)]">
                                        Status
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.15em] text-[color:var(--admin-muted)]">
                                        Last Login
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-[0.15em] text-[color:var(--admin-muted)]">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {filteredSellers.map((seller) => (
                                    <tr
                                        className="border-b border-[color:var(--admin-border)] last:border-0 hover:bg-[color:var(--admin-surface)]"
                                        key={seller.id}
                                    >
                                        <td className="px-4 py-3">
                                            <div className="font-medium text-[color:var(--admin-foreground)]">
                                                {seller.name}
                                            </div>
                                            <div className="text-xs text-[color:var(--admin-muted)]">
                                                ID: {seller.id}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <code className="rounded-md bg-[color:var(--admin-code-bg)] px-2 py-1 text-xs font-mono text-[color:var(--admin-foreground)]">
                                                {seller.code}
                                            </code>
                                        </td>
                                        <td className="px-4 py-3 text-[color:var(--admin-foreground)]">
                                            {seller.phone}
                                        </td>
                                        <td className="px-4 py-3">
                                            <AdminStatusBadge status={seller.status} />
                                        </td>
                                        <td className="px-4 py-3 text-sm text-[color:var(--admin-muted)]">
                                            {seller.lastLoginAt
                                                ? new Date(seller.lastLoginAt).toLocaleString()
                                                : 'Never'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex items-center justify-end gap-2">
                                                <ActionButton
                                                    icon={Key}
                                                    onClick={() => {
                                                        setSelectedSeller(seller);
                                                        setShowResetPinModal(true);
                                                    }}
                                                >
                                                    Reset PIN
                                                </ActionButton>
                                                <ActionButton
                                                    icon={Trash2}
                                                    onClick={() => handleDelete(seller)}
                                                    variant="danger"
                                                >
                                                    Delete
                                                </ActionButton>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </AdminSectionCard>

            {/* Create Seller Modal */}
            {showCreateModal && (
                <CreateSellerModal
                    onClose={() => setShowCreateModal(false)}
                    onSuccess={async () => {
                        setShowCreateModal(false);
                        await loadSellers();
                    }}
                />
            )}

            {/* Reset PIN Modal */}
            {showResetPinModal && selectedSeller && (
                <ResetPinModal
                    seller={selectedSeller}
                    onClose={() => {
                        setShowResetPinModal(false);
                        setSelectedSeller(null);
                    }}
                    onReset={handleResetPin}
                />
            )}
        </div>
    );
}

function CreateSellerModal({ onClose, onSuccess }) {
    const api = useAdminApiClient();
    const [formData, setFormData] = useState({
        name: '',
        code: '',
        phone: '',
        pin: '',
        status: 'active',
    });
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [error, setError] = useState('');

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        setIsSubmitting(true);

        try {
            await api.post('/admin/sellers', formData);
            onSuccess();
        } catch (err) {
            setError(err.response?.data?.message || 'Failed to create seller');
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div className="w-full max-w-md rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-strong)] p-6 shadow-2xl">
                <h2 className="mb-4 text-lg font-bold text-[color:var(--admin-foreground)]">
                    Create New Seller
                </h2>

                <form className="space-y-4" onSubmit={handleSubmit}>
                    {error && (
                        <div className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</div>
                    )}

                    <div>
                        <label className="mb-2 block text-xs font-semibold uppercase tracking-[0.15em] text-[color:var(--admin-muted)]">
                            Name
                        </label>
                        <input
                            className="w-full rounded-xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-2.5 text-sm text-[color:var(--admin-foreground)] focus:border-[color:var(--admin-accent)] focus:outline-none"
                            onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                            required
                            type="text"
                            value={formData.name}
                        />
                    </div>

                    <div>
                        <label className="mb-2 block text-xs font-semibold uppercase tracking-[0.15em] text-[color:var(--admin-muted)]">
                            Agent Code
                        </label>
                        <input
                            className="w-full rounded-xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-2.5 text-sm text-[color:var(--admin-foreground)] focus:border-[color:var(--admin-accent)] focus:outline-none"
                            onChange={(e) => setFormData({ ...formData, code: e.target.value })}
                            placeholder="AGT-001"
                            required
                            type="text"
                            value={formData.code}
                        />
                    </div>

                    <div>
                        <label className="mb-2 block text-xs font-semibold uppercase tracking-[0.15em] text-[color:var(--admin-muted)]">
                            Phone Number
                        </label>
                        <input
                            className="w-full rounded-xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-2.5 text-sm text-[color:var(--admin-foreground)] focus:border-[color:var(--admin-accent)] focus:outline-none"
                            onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                            placeholder="0971000001"
                            required
                            type="tel"
                            value={formData.phone}
                        />
                    </div>

                    <div>
                        <label className="mb-2 block text-xs font-semibold uppercase tracking-[0.15em] text-[color:var(--admin-muted)]">
                            PIN (4-6 digits)
                        </label>
                        <input
                            className="w-full rounded-xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-2.5 text-sm text-[color:var(--admin-foreground)] focus:border-[color:var(--admin-accent)] focus:outline-none"
                            maxLength={6}
                            minLength={4}
                            onChange={(e) => setFormData({ ...formData, pin: e.target.value })}
                            placeholder="1234"
                            required
                            type="password"
                            value={formData.pin}
                        />
                    </div>

                    <div>
                        <label className="mb-2 block text-xs font-semibold uppercase tracking-[0.15em] text-[color:var(--admin-muted)]">
                            Status
                        </label>
                        <select
                            className="w-full rounded-xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-2.5 text-sm text-[color:var(--admin-foreground)] focus:border-[color:var(--admin-accent)] focus:outline-none"
                            onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                            value={formData.status}
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>

                    <div className="flex gap-3 pt-4">
                        <button
                            className="flex-1 rounded-xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] py-2.5 text-sm font-semibold text-[color:var(--admin-foreground)] hover:bg-[color:var(--admin-surface-hover)]"
                            onClick={onClose}
                            type="button"
                        >
                            Cancel
                        </button>
                        <button
                            className="flex-1 rounded-xl bg-[color:var(--admin-accent)] py-2.5 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-50"
                            disabled={isSubmitting}
                            type="submit"
                        >
                            {isSubmitting ? 'Creating...' : 'Create Seller'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

function ResetPinModal({ seller, onClose, onReset }) {
    const [newPin, setNewPin] = useState('');
    const [confirmPin, setConfirmPin] = useState('');
    const [error, setError] = useState('');

    const handleSubmit = (e) => {
        e.preventDefault();

        if (newPin.length < 4 || newPin.length > 6) {
            setError('PIN must be 4-6 digits');
            return;
        }

        if (newPin !== confirmPin) {
            setError('PINs do not match');
            return;
        }

        onReset(seller.id, newPin);
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div className="w-full max-w-md rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-strong)] p-6 shadow-2xl">
                <h2 className="mb-4 text-lg font-bold text-[color:var(--admin-foreground)]">
                    Reset PIN for {seller.name}
                </h2>
                <p className="mb-4 text-sm text-[color:var(--admin-muted)]">
                    Agent Code: <code className="font-mono">{seller.code}</code>
                </p>

                <form className="space-y-4" onSubmit={handleSubmit}>
                    {error && (
                        <div className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</div>
                    )}

                    <div>
                        <label className="mb-2 block text-xs font-semibold uppercase tracking-[0.15em] text-[color:var(--admin-muted)]">
                            New PIN
                        </label>
                        <input
                            className="w-full rounded-xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-2.5 text-sm text-[color:var(--admin-foreground)] focus:border-[color:var(--admin-accent)] focus:outline-none"
                            maxLength={6}
                            minLength={4}
                            onChange={(e) => setNewPin(e.target.value)}
                            placeholder="1234"
                            required
                            type="password"
                            value={newPin}
                        />
                    </div>

                    <div>
                        <label className="mb-2 block text-xs font-semibold uppercase tracking-[0.15em] text-[color:var(--admin-muted)]">
                            Confirm PIN
                        </label>
                        <input
                            className="w-full rounded-xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] px-4 py-2.5 text-sm text-[color:var(--admin-foreground)] focus:border-[color:var(--admin-accent)] focus:outline-none"
                            maxLength={6}
                            minLength={4}
                            onChange={(e) => setConfirmPin(e.target.value)}
                            placeholder="1234"
                            required
                            type="password"
                            value={confirmPin}
                        />
                    </div>

                    <div className="flex gap-3 pt-4">
                        <button
                            className="flex-1 rounded-xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface)] py-2.5 text-sm font-semibold text-[color:var(--admin-foreground)] hover:bg-[color:var(--admin-surface-hover)]"
                            onClick={onClose}
                            type="button"
                        >
                            Cancel
                        </button>
                        <button
                            className="flex-1 rounded-xl bg-[color:var(--admin-accent)] py-2.5 text-sm font-semibold text-white hover:opacity-90"
                            type="submit"
                        >
                            Reset PIN
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
