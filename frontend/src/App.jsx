import { Navigate, Route, Routes } from 'react-router-dom';
import ProtectedRoute from './components/ProtectedRoute';
import SessionStatus from './components/SessionStatus';
import { useAuth } from './context/AuthContext';
import { ROLE_HOME } from './config/roles';
import Login from './pages/Login';
import DashboardEstudiante from './pages/DashboardEstudiante';
import DashboardPadre from './pages/DashboardPadre';
import DashboardAuxiliar from './pages/DashboardAuxiliar';
import Reportes from './pages/Reportes';
import Configuracion from './pages/Configuracion';

function HomeRedirect() {
  const { user, status, retry, abandonSession } = useAuth();
  if (status === 'checking') return <SessionStatus status="checking" />;
  if (status === 'offline' || status === 'error') {
    return <SessionStatus status={status} onRetry={retry} onAbandon={abandonSession} />;
  }
  return <Navigate to={user ? ROLE_HOME[user.role] ?? '/login' : '/login'} replace />;
}

export default function App() {
  return (
    <Routes>
      <Route path="/" element={<HomeRedirect />} />
      <Route path="/login" element={<Login />} />

      <Route element={<ProtectedRoute roles={['student']} />}>
        <Route path="/estudiante" element={<DashboardEstudiante />} />
      </Route>
      <Route element={<ProtectedRoute roles={['parent']} />}>
        <Route path="/padre" element={<DashboardPadre />} />
      </Route>
      <Route element={<ProtectedRoute roles={['staff']} />}>
        <Route path="/auxiliar" element={<DashboardAuxiliar />} />
      </Route>
      <Route element={<ProtectedRoute roles={['student', 'parent', 'staff']} />}>
        <Route path="/reportes" element={<Reportes />} />
        <Route path="/configuracion" element={<Configuracion />} />
      </Route>

      <Route path="*" element={<HomeRedirect />} />
    </Routes>
  );
}
