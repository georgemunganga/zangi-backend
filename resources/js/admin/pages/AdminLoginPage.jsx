import { ArrowRight } from 'lucide-react';
import { useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { useAdminAuth } from '../auth/AdminAuthProvider';

function LoginInput({ label, onChange, placeholder, type, value }) {
    return (
        <label className="block">
            <span className="mb-2 block text-sm font-medium text-[color:var(--admin-ink)]">{label}</span>
            <input
                className="w-full rounded-2xl border border-[color:var(--admin-border)] bg-white px-4 py-3 text-sm outline-none transition focus:border-[color:var(--admin-accent)]"
                onChange={onChange}
                placeholder={placeholder}
                required
                type={type}
                value={value}
            />
        </label>
    );
}

export function AdminLoginPage() {
    const navigate = useNavigate();
    const location = useLocation();
    const { login, isAuthenticating } = useAdminAuth();
    const [credentials, setCredentials] = useState({
        email: '',
        password: '',
    });
    const [errorMessage, setErrorMessage] = useState('');
    const redirectTo =
        typeof location.state?.from === 'string' && location.state.from !== '/admin/login'
            ? location.state.from
            : '/admin/overview';

    const handleSubmit = async (event) => {
        event.preventDefault();
        setErrorMessage('');

        try {
            await login(credentials);
            navigate(redirectTo, { replace: true });
        } catch (error) {
            setErrorMessage(error.message);
        }
    };

    return (
        <div className="min-h-screen overflow-x-hidden bg-[radial-gradient(circle_at_top,_rgba(138,75,47,0.16),_transparent_30%),linear-gradient(180deg,_#f6f1e8_0%,_#ece6dd_100%)] px-4 py-8 sm:py-10">
            <div className="mx-auto flex min-h-[calc(100vh-5rem)] min-w-0 max-w-md items-center">
                <section className="min-w-0 w-full rounded-[2rem] border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-strong)] p-6 shadow-[0_28px_70px_rgba(23,33,38,0.10)] md:p-8">
                    <div className="mb-6">
                        <p className="text-[0.72rem] font-semibold uppercase tracking-[0.28em] text-[color:var(--admin-accent)]">
                            Zangi Admin
                        </p>
                        <h1 className="mt-3 text-3xl font-semibold text-[color:var(--admin-ink)]">Sign in</h1>
                        <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">
                            Use your admin email and password.
                        </p>
                    </div>
                    <form className="space-y-4" onSubmit={handleSubmit}>
                        {errorMessage ? (
                            <div className="rounded-[1.25rem] border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                                {errorMessage}
                            </div>
                        ) : null}
                        <LoginInput
                            label="Email"
                            onChange={(event) =>
                                setCredentials((current) => ({ ...current, email: event.target.value }))
                            }
                            placeholder="admin@example.com"
                            type="email"
                            value={credentials.email}
                        />
                        <LoginInput
                            label="Password"
                            onChange={(event) =>
                                setCredentials((current) => ({ ...current, password: event.target.value }))
                            }
                            placeholder="Enter your password"
                            type="password"
                            value={credentials.password}
                        />
                        <button
                            className="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-[color:var(--admin-ink)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-black disabled:cursor-not-allowed disabled:opacity-70"
                            disabled={isAuthenticating}
                            type="submit"
                        >
                            <span>{isAuthenticating ? 'Signing in...' : 'Sign in'}</span>
                            <ArrowRight className="h-4 w-4" />
                        </button>
                    </form>
                </section>
            </div>
        </div>
    );
}
