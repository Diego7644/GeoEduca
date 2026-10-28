import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import ProtectedRoute from '../components/ProtectedRoute';
import { AuthProvider, useAuth } from './AuthContext';
import * as authService from '../services/authService';

vi.mock('../services/authService', () => ({
  currentUser: vi.fn(),
  login: vi.fn(),
  logout: vi.fn(),
  loginWithAccess: vi.fn(),
}));

const student = {
  id: 1,
  name: 'Estudiante de desarrollo',
  role: 'student',
  roleLabel: 'Estudiante',
  identifier: '10000001',
};

function Shell({ path = '/estudiante' }) {
  return (
    <MemoryRouter initialEntries={[path]}>
      <AuthProvider>
        <Probe />
        <Routes>
          <Route path="/login" element={<p>LOGIN</p>} />
          <Route element={<ProtectedRoute roles={['student']} />}>
            <Route path="/estudiante" element={<p>PANEL-ESTUDIANTE</p>} />
          </Route>
          <Route element={<ProtectedRoute roles={['staff']} />}>
            <Route path="/auxiliar" element={<p>PANEL-AUXILIAR</p>} />
          </Route>
        </Routes>
      </AuthProvider>
    </MemoryRouter>
  );
}

function Probe() {
  const { logout } = useAuth();
  return (
    <button type="button" onClick={() => logout()}>
      salir
    </button>
  );
}

beforeEach(() => {
  localStorage.clear();
  vi.clearAllMocks();
});

describe('sesión contra /api/auth/me', () => {
  it('confirma una sesión válida y entra al panel del rol', async () => {
    localStorage.setItem('geoeduca_token', '1|valido');
    localStorage.setItem('geoeduca_user', JSON.stringify({ role: 'staff', name: 'Inventado' }));
    authService.currentUser.mockResolvedValue(student);

    render(<Shell />);

    expect(screen.getByTestId('session-status')).toHaveTextContent('Comprobando sesión');
    expect(await screen.findByText('PANEL-ESTUDIANTE')).toBeInTheDocument();
    expect(screen.queryByText('PANEL-AUXILIAR')).not.toBeInTheDocument();
  });

  it('una sesión inválida limpia el almacenamiento y redirige al login', async () => {
    localStorage.setItem('geoeduca_token', '1|invalido');
    localStorage.setItem('geoeduca_user', JSON.stringify({ role: 'staff' }));
    const error = new Error('No autenticado.');
    error.status = 401;
    authService.currentUser.mockRejectedValue(error);

    render(<Shell />);

    expect(await screen.findByText('LOGIN')).toBeInTheDocument();
    expect(localStorage.getItem('geoeduca_token')).toBeNull();
    expect(localStorage.getItem('geoeduca_user')).toBeNull();
  });

  it('un token expirado también vuelve al login', async () => {
    localStorage.setItem('geoeduca_token', '1|expirado');
    const error = new Error('No autenticado.');
    error.status = 401;
    authService.currentUser.mockRejectedValue(error);

    render(<Shell path="/auxiliar" />);

    expect(await screen.findByText('LOGIN')).toBeInTheDocument();
  });

  it('un error de red no abre el panel con el usuario guardado', async () => {
    localStorage.setItem('geoeduca_token', '1|valido');
    localStorage.setItem('geoeduca_user', JSON.stringify(student));
    const error = new Error('red');
    error.status = 0;
    authService.currentUser.mockRejectedValue(error);

    render(<Shell />);

    expect(await screen.findByText(/No se pudo comprobar la sesión/)).toBeInTheDocument();
    expect(screen.queryByText('PANEL-ESTUDIANTE')).not.toBeInTheDocument();
  });

  it('un error del servidor tampoco abre el panel', async () => {
    localStorage.setItem('geoeduca_token', '1|valido');
    const error = new Error('fallo');
    error.status = 500;
    authService.currentUser.mockRejectedValue(error);

    render(<Shell />);

    expect(await screen.findByText('No se pudo comprobar la sesión con el servidor.')).toBeInTheDocument();
  });

  it('logout revoca el token y vuelve al login', async () => {
    localStorage.setItem('geoeduca_token', '1|valido');
    authService.currentUser.mockResolvedValue(student);
    authService.logout.mockResolvedValue(undefined);
    const user = userEvent.setup();

    render(<Shell />);
    expect(await screen.findByText('PANEL-ESTUDIANTE')).toBeInTheDocument();

    await user.click(screen.getByRole('button', { name: 'salir' }));

    await waitFor(() => expect(screen.getByText('LOGIN')).toBeInTheDocument());
    expect(authService.logout).toHaveBeenCalledWith('1|valido');
    expect(localStorage.getItem('geoeduca_token')).toBeNull();
  });

  it('un estudiante confirmado no entra al panel del auxiliar', async () => {
    localStorage.setItem('geoeduca_token', '1|valido');
    authService.currentUser.mockResolvedValue(student);

    render(<Shell path="/auxiliar" />);

    expect(await screen.findByText('PANEL-ESTUDIANTE')).toBeInTheDocument();
    expect(screen.queryByText('PANEL-AUXILIAR')).not.toBeInTheDocument();
  });

  it('una respuesta tardía de /me no restaura la sesión cerrada', async () => {
    localStorage.setItem('geoeduca_token', '1|valido');
    let resolveMe;
    authService.currentUser.mockImplementation(() => new Promise((resolve) => {
      resolveMe = resolve;
    }));
    authService.logout.mockResolvedValue(undefined);
    const user = userEvent.setup();

    render(<Shell />);
    await user.click(screen.getByRole('button', { name: 'salir' }));
    resolveMe(student);

    await waitFor(() => expect(screen.getByText('LOGIN')).toBeInTheDocument());
    expect(screen.queryByText('PANEL-ESTUDIANTE')).not.toBeInTheDocument();
    expect(localStorage.getItem('geoeduca_token')).toBeNull();
  });
});
