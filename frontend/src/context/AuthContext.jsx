import { createContext, useCallback, useContext, useMemo, useState } from 'react';
import * as authService from '../services/authService';
import { TOKEN_KEY } from '../services/apiClient';

const USER_KEY = 'geoeduca_user';
const AuthContext = createContext(null);

function readStoredUser() {
  try {
    return JSON.parse(localStorage.getItem(USER_KEY));
  } catch {
    return null;
  }
}

export function AuthProvider({ children }) {
  const [user, setUser] = useState(readStoredUser);

  const persist = useCallback((token, loggedUser) => {
    localStorage.setItem(TOKEN_KEY, token);
    localStorage.setItem(USER_KEY, JSON.stringify(loggedUser));
    setUser(loggedUser);
    return loggedUser;
  }, []);

  const login = useCallback(async (credentials) => {
    const { token, user: loggedUser } = await authService.login(credentials);
    return persist(token, loggedUser);
  }, [persist]);

  const loginWithAccess = useCallback(async (payload) => {
    const { token, user: loggedUser } = await authService.loginWithAccess(payload);
    if (!token || !loggedUser) {
      const error = new Error('El carnet no inicia sesión en el portal. Use DNI y contraseña.');
      error.status = 403;
      throw error;
    }
    return persist(token, loggedUser);
  }, [persist]);

  const logout = useCallback(async () => {
    try {
      await authService.logout();
    } catch {
      // La sesión local se cierra aunque el servidor no responda.
    }
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
    setUser(null);
  }, []);

  const value = useMemo(
    () => ({ user, login, loginWithAccess, logout, isAuthenticated: !!user }),
    [user, login, loginWithAccess, logout]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth debe usarse dentro de <AuthProvider>');
  return ctx;
}
