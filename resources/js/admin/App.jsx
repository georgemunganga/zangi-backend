import { AdminAuthProvider } from './auth/AdminAuthProvider';
import { AdminMockDataProvider } from './mocks/AdminMockDataProvider';
import { AdminRoutes } from './routes/AdminRoutes';

export function App() {
    return (
        <AdminAuthProvider>
            <AdminMockDataProvider>
                <AdminRoutes />
            </AdminMockDataProvider>
        </AdminAuthProvider>
    );
}
