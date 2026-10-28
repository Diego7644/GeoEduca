import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';
import * as authService from '../services/authService';
import { setUnauthorizedHandler, TOKEN_KEY } from '../services/apiClient';

const USER_KEY = 'geoeduca_user';
const AuthContext = createContext(null);

function clearStoredSession() {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
}

function remember(token, account) {
  localStorage.setItem(TOKEN_KEY, token);
  localStorage.setItem(USER_KEY, JSON.stringify(account));
}

/**
 * La sesión visible sale de GET /api/auth/me.
 * localStorage solo transporta el Bearer entre recargas; no autoriza la interfaz.
 * `epoch` descarta una respuesta de /me que llega después de cerrar sesión.
 */
export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [status, setStatus] = useState('checking');
  const [attempt, setAttempt] = useState(0);
  const epochRef = useRef(0);

  const dropSession = useCallback(() => {
    epochRef.current += 1;
    clearStoredSession();
    setUser(null);
    setStatus('anonymous');
  }, []);

  useEffect(() => {
    setUnauthorizedHandler(dropSession);
    return () => setUnauthorizedHandler(() => {});
  }, [dropSession]);

  useEffect(() => {
    const epoch = epochRef.current;
    const token = localStorage.getItem(TOKEN_KEY);
    if (!token) {
      setUser(null);
      setStatus('anonymous');
      return undefined;
    }

    let active = true;
    setStatus('checking');
    authService.currentUser()
      .then((account) => {
        if (!active || epoch !== epochRef.current) return;
        remember(token, account);
        setUser(account);
        setStatus('authenticated');
      })
      .catch((error) => {
        if (!active || epoch !== epochRef.current) return;
        if (error?.status === 401) {
          clearStoredSession();
          setUser(null);
          setStatus('anonymous');
          return;
        }
        setUser(null);
        setStatus(error?.status === 0 ? 'offline' : 'error');
      });

    return () => {
      active = false;
    };
  }, [attempt]);

  const login = useCallback(async (credentials) => {
    const epoch = epochRef.current;
    const { token, user: account } = await authService.login(credentials);
    if (epoch !== epochRef.current) return account;
    remember(token, account);
    setUser(account);
    setStatus('authenticated');
    return account;
  }, []);

  const loginWithAccess = useCallback(async (payload) => {
    const epoch = epochRef.current;
    const { token, user: account } = await authService.loginWithAccess(payload);
    if (epoch !== epochRef.current) return account;
    remember(token, account);
    setUser(account);
    setStatus('authenticated');
    return account;
  }, []);

  const logout = useCallback(async () => {
    const token = localStorage.getItem(TOKEN_KEY);
    epochRef.current += 1;
    clearStoredSession();
    setUser(null);
    setStatus('anonymous');
    if (!token) return;
    try {
      await authService.logout(token);
    } catch {
      // La interfaz ya no tiene sesión. El token se intentó revocar igual.
    }
  }, []);

  const retry = useCallback(() => setAttempt((value) => value + 1), []);

  const value = useMemo(
    () => ({
      user,
      status,
      login,
      loginWithAccess,
      logout,
      retry,
      abandonSession: dropSession,
      isAuthenticated: status === 'authenticated' && !!user,
    }),
    [user, status, login, loginWithAccess, logout, retry, dropSession]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth debe usarse dentro de <AuthProvider>');
  return ctx;
}
