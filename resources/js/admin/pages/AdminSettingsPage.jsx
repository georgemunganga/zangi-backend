import { Clock3, KeyRound, ShieldCheck, UserCircle2 } from 'lucide-react';
import { useState } from 'react';
import { useAdminAuth } from '../auth/AdminAuthProvider';
import { AdminPageHeader } from '../components/AdminPageHeader';
import { AdminSectionCard } from '../components/AdminSectionCard';

function Field({ children, label }) {
    return (
        <label className="block">
            <span className="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">
                {label}
            </span>
            {children}
        </label>
    );
}

function Input(props) {
    return (
        <input
            className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
            {...props}
        />
    );
}

export function AdminSettingsPage() {
    const { changePassword, isChangingPassword, user } = useAdminAuth();
    const [passwordForm, setPasswordForm] = useState({
        currentPassword: '',
        nextPassword: '',
        confirmPassword: '',
    });
    const [feedback, setFeedback] = useState(null);

    const handlePasswordSave = async (event) => {
        event.preventDefault();
        setFeedback(null);

        if (!passwordForm.nextPassword || passwordForm.nextPassword !== passwordForm.confirmPassword) {
            setFeedback({
                tone: 'error',
                message: 'Password confirmation does not match.',
            });
            return;
        }

        try {
            const response = await changePassword({
                currentPassword: passwordForm.currentPassword,
                newPassword: passwordForm.nextPassword,
                newPasswordConfirmation: passwordForm.confirmPassword,
            });

            setFeedback({
                tone: 'success',
                message: response.message,
            });
            setPasswordForm({
                currentPassword: '',
                nextPassword: '',
                confirmPassword: '',
            });
        } catch (error) {
            setFeedback({
                tone: 'error',
                message: error.message,
            });
        }
    };

    const formattedLastLogin = user?.lastLoginAt
        ? new Intl.DateTimeFormat('en-ZA', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(user.lastLoginAt))
        : 'No login recorded yet';

    return (
        <div className="min-w-0 space-y-6">
            <AdminPageHeader title="Settings" />

            {feedback?.message ? (
                <div
                    className={`rounded-2xl px-4 py-3 text-sm ${
                        feedback.tone === 'error'
                            ? 'border border-rose-200 bg-rose-50 text-rose-700'
                            : 'border border-[color:var(--admin-border)] bg-[color:var(--admin-accent-soft)] text-[color:var(--admin-accent)]'
                    }`}
                >
                    {feedback.message}
                </div>
            ) : null}

            <section className="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,1fr)_420px]">
                <AdminSectionCard eyebrow="Profile" title="Admin identity">
                    <div className="space-y-4">
                        <div className="grid gap-3 md:grid-cols-3">
                            <div className="rounded-2xl bg-stone-50 px-4 py-4">
                                <div className="flex items-center gap-3">
                                    <span className="flex h-10 w-10 items-center justify-center rounded-2xl bg-[color:var(--admin-accent-soft)] text-[color:var(--admin-accent)]">
                                        <UserCircle2 className="h-5 w-5" />
                                    </span>
                                    <div>
                                        <p className="text-sm font-semibold text-[color:var(--admin-ink)]">Session user</p>
                                        <p className="text-sm text-[color:var(--admin-muted)]">{user?.email ?? 'Unavailable'}</p>
                                    </div>
                                </div>
                            </div>
                            <div className="rounded-2xl bg-stone-50 px-4 py-4">
                                <div className="flex items-center gap-3">
                                    <span className="flex h-10 w-10 items-center justify-center rounded-2xl bg-[color:var(--admin-accent-soft)] text-[color:var(--admin-accent)]">
                                        <ShieldCheck className="h-5 w-5" />
                                    </span>
                                    <div>
                                        <p className="text-sm font-semibold text-[color:var(--admin-ink)]">Role</p>
                                        <p className="text-sm capitalize text-[color:var(--admin-muted)]">{user?.role ?? 'Admin'}</p>
                                    </div>
                                </div>
                            </div>
                            <div className="rounded-2xl bg-stone-50 px-4 py-4">
                                <div className="flex items-center gap-3">
                                    <span className="flex h-10 w-10 items-center justify-center rounded-2xl bg-[color:var(--admin-accent-soft)] text-[color:var(--admin-accent)]">
                                        <Clock3 className="h-5 w-5" />
                                    </span>
                                    <div>
                                        <p className="text-sm font-semibold text-[color:var(--admin-ink)]">Last login</p>
                                        <p className="text-sm text-[color:var(--admin-muted)]">{formattedLastLogin}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <Field label="Name">
                                <Input disabled type="text" value={user?.name ?? ''} />
                            </Field>
                            <Field label="Email">
                                <Input disabled type="email" value={user?.email ?? ''} />
                            </Field>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <Field label="Role">
                                <Input disabled type="text" value={user?.role ?? 'admin'} />
                            </Field>
                            <Field label="Last login">
                                <Input disabled type="text" value={formattedLastLogin} />
                            </Field>
                        </div>

                        <div className="rounded-2xl border border-[color:var(--admin-border)] bg-stone-50 px-4 py-3 text-sm text-[color:var(--admin-muted)]">
                            Profile details are read-only.
                        </div>
                    </div>
                </AdminSectionCard>

                <AdminSectionCard eyebrow="Security" title="Change password">
                    <form className="space-y-4" onSubmit={handlePasswordSave}>
                        <Field label="Current password">
                            <Input
                                onChange={(event) =>
                                    setPasswordForm((current) => ({ ...current, currentPassword: event.target.value }))
                                }
                                type="password"
                                value={passwordForm.currentPassword}
                            />
                        </Field>
                        <Field label="New password">
                            <Input
                                onChange={(event) =>
                                    setPasswordForm((current) => ({ ...current, nextPassword: event.target.value }))
                                }
                                type="password"
                                value={passwordForm.nextPassword}
                            />
                        </Field>
                        <Field label="Confirm password">
                            <Input
                                onChange={(event) =>
                                    setPasswordForm((current) => ({ ...current, confirmPassword: event.target.value }))
                                }
                                type="password"
                                value={passwordForm.confirmPassword}
                            />
                        </Field>
                        <button
                            className="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-[color:var(--admin-ink)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-black disabled:cursor-not-allowed disabled:opacity-70"
                            disabled={isChangingPassword}
                            type="submit"
                        >
                            <KeyRound className="h-4 w-4" />
                            <span>{isChangingPassword ? 'Updating password...' : 'Update password'}</span>
                        </button>
                    </form>
                </AdminSectionCard>
            </section>
        </div>
    );
}
