import { Navigate, Outlet } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { homeFor } from '../config/roles';
import SessionStatus from './SessionStatus';

/** Exige una sesión confirmada por el backend y, si se pide, un rol de esa sesión. */
export default function ProtectedRoute({ roles }) {
  const { user, status, retry, abandonSession } = useAuth();

  if (status === 'checking') return <SessionStatus status="checking" />;
  if (status === 'offline' || status === 'error') {
    return <SessionStatus status={status} onRetry={retry} onAbandon={abandonSession} />;
  }
  if (!user) return <Navigate to="/login" replace />;
  if (roles && !roles.includes(user.role)) return <Navigate to={homeFor(user)} replace />;

  return <Outlet />;
}
