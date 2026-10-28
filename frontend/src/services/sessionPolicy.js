/**
 * Un 401 solo cierra la sesión si pertenece al token que sigue guardado.
 * Así una respuesta tardía no pisa un login nuevo ni el formulario de acceso.
 */
export function shouldDropSession(error) {
  if (error?.response?.status !== 401) return false;

  const url = String(error?.config?.url ?? '');
  if (url.includes('/auth/login')) return false;

  const failed = bearerToken(error?.config);
  const current = localStorage.getItem('geoeduca_token');
  if (!failed || !current) return false;

  return failed === current;
}

function bearerToken(config) {
  const headers = config?.headers;
  const raw = headers?.Authorization ?? headers?.authorization ?? '';
  const match = String(raw).match(/^Bearer\s+(\S+)/i);
  return match ? match[1] : '';
}
