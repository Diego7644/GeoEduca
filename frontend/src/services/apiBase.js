const LOOPBACK = new Set(['localhost', '127.0.0.1']);

/**
 * El teléfono no puede llamar a localhost de la laptop.
 * Si la API configurada apunta al bucle local y la página no, se usa el proxy /api.
 */
export function resolveApiBase(configured, pageHostname) {
  const value = configured || '/api';
  if (!pageHostname) return value;

  let apiHost = '';
  try {
    apiHost = new URL(value, 'http://geoeduca.local').hostname;
  } catch {
    return '/api';
  }

  if (LOOPBACK.has(apiHost) && !LOOPBACK.has(pageHostname)) return '/api';
  return value;
}
