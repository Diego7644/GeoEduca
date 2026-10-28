import axios from 'axios';
import { shouldDropSession } from './sessionPolicy';

export const TOKEN_KEY = 'geoeduca_token';
const USER_KEY = 'geoeduca_user';

let onSessionDropped = () => {};

/** AuthContext registra aquí la limpieza de estado. No redirige por sí mismo. */
export function setUnauthorizedHandler(handler) {
  onSessionDropped = typeof handler === 'function' ? handler : () => {};
}

/**
 * Cliente HTTP central. Apunta a la API de Laravel (backend/routes/api.php).
 * Todas las llamadas reales deben pasar por esta instancia.
 */
const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  timeout: 15000,
});

apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY);
  if (token && !config.headers.Authorization && !config.headers.authorization) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (shouldDropSession(error)) {
      localStorage.removeItem(TOKEN_KEY);
      localStorage.removeItem(USER_KEY);
      onSessionDropped();
    }
    return Promise.reject(error);
  }
);

export default apiClient;
