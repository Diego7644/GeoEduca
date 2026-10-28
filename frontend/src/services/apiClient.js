import axios from 'axios';

export const TOKEN_KEY = 'geoeduca_token';

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
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Sesión expirada o inválida: limpiar y volver al login.
      localStorage.removeItem(TOKEN_KEY);
      localStorage.removeItem('geoeduca_user');
      if (window.location.pathname !== '/login') window.location.assign('/login');
    }
    return Promise.reject(error);
  }
);

export default apiClient;
