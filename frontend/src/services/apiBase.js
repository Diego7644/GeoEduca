const LOOPBACK = new Set(['localhost', '127.0.0.1']);

/**
 * El teléfono no llama a la laptop por HTTP.
 * Una ruta relativa usa el proxy de Vite, que en Tailscale ya es HTTPS.
 */
export function resolveApiBase(configured, pageHostname) {
  const value = configured || '/api';
  if (!pageHostname || value.startsWith('/')) return value;

  let parsed;
  try {
    parsed = new URL(value);
  } catch {
    return '/api';
  }

  const pageIsLoopback = LOOPBACK.has(pageHostname);
  const apiIsLoopback = LOOPBACK.has(parsed.hostname);
  if (parsed.protocol !== 'https:' && !pageIsLoopback) return '/api';
  if (apiIsLoopback && !pageIsLoopback) return '/api';
  return value;
}
