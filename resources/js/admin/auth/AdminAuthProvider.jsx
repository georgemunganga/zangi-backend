import { createContext, useContext, useEffect, useMemo, useRef, useState } from 'react';
import {
    changeAdminPassword,
    extractApiErrorMessage,
    fetchAdminMe,
    loginAdmin,
    logoutAdmin,
} from '../api/adminApiClient';

const ADMIN_ACCESS_TOKEN_KEY = 'zangi.admin.access_token';
const AdminAuthContext = createContext(null);

function persistAccessToken(accessToken) {
    window.localStorage.setItem(ADMIN_ACCESS_TOKEN_KEY, accessToken);
}

function clearPersistedAccessToken() {
    window.localStorage.removeItem(ADMIN_ACCESS_TOKEN_KEY);
}

export function AdminAuthProvider({ children }) {
    const restoredRef = useRef(false);
    const [accessToken, setAccessToken] = useState(() => window.localStorage.getItem(ADMIN_ACCESS_TOKEN_KEY) ?? '');
    const [user, setUser] = useState(null);
    const [isBootstrapping, setIsBootstrapping] = useState(true);
    const [isAuthenticating, setIsAuthenticating] = useState(false);
    const [isChangingPassword, setIsChangingPassword] = useState(false);

    useEffect(() => {
        if (restoredRef.current) {
            return;
        }

        restoredRef.current = true;

        if (!accessToken) {
            setIsBootstrapping(false);
            return;
        }

        let cancelled = false;

        fetchAdminMe(accessToken)
            .then((response) => {
                if (cancelled) {
                    return;
                }

                setUser(response.user);
            })
            .catch(() => {
                if (cancelled) {
                    return;
                }

                clearPersistedAccessToken();
                setAccessToken('');
                setUser(null);
            })
            .finally(() => {
                if (!cancelled) {
                    setIsBootstrapping(false);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [accessToken]);

    const login = async (credentials) => {
        setIsAuthenticating(true);

        try {
            const response = await loginAdmin(credentials);

            persistAccessToken(response.accessToken);
            setAccessToken(response.accessToken);
            setUser(response.user);

            return response.user;
        } catch (error) {
            throw new Error(extractApiErrorMessage(error, 'Unable to sign in.'));
        } finally {
            setIsAuthenticating(false);
            setIsBootstrapping(false);
        }
    };

    const logout = async () => {
        const token = accessToken;

        try {
            if (token) {
                await logoutAdmin(token);
            }
        } finally {
            clearPersistedAccessToken();
            setAccessToken('');
            setUser(null);
        }
    };

    const changePassword = async (payload) => {
        if (!accessToken) {
            throw new Error('Sign in to change your password.');
        }

        setIsChangingPassword(true);

        try {
            const response = await changeAdminPassword(accessToken, payload);

            setUser(response.user);

            return response;
        } catch (error) {
            throw new Error(extractApiErrorMessage(error, 'Unable to change password.'));
        } finally {
            setIsChangingPassword(false);
        }
    };

    const value = useMemo(
        () => ({
            accessToken,
            user,
            isAuthenticated: Boolean(accessToken && user),
            isBootstrapping,
            isAuthenticating,
            isChangingPassword,
            login,
            logout,
            changePassword,
        }),
        [accessToken, isAuthenticating, isBootstrapping, isChangingPassword, user],
    );

    return <AdminAuthContext.Provider value={value}>{children}</AdminAuthContext.Provider>;
}

export function useAdminAuth() {
    const context = useContext(AdminAuthContext);

    if (!context) {
        throw new Error('useAdminAuth must be used within AdminAuthProvider');
    }

    return context;
}
