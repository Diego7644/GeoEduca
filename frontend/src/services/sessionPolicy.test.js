import { afterEach, describe, expect, it } from 'vitest';
import { shouldDropSession } from './sessionPolicy';

afterEach(() => {
  localStorage.clear();
});

describe('shouldDropSession', () => {
  it('no cierra la sesión por un 401 de login', () => {
    localStorage.setItem('geoeduca_token', 'vigente');
    expect(shouldDropSession({
      response: { status: 401 },
      config: { url: '/auth/login', headers: { Authorization: 'Bearer vigente' } },
    })).toBe(false);
  });

  it('cierra la sesión si el 401 es del token guardado', () => {
    localStorage.setItem('geoeduca_token', 'caducado');
    expect(shouldDropSession({
      response: { status: 401 },
      config: { url: '/auth/me', headers: { Authorization: 'Bearer caducado' } },
    })).toBe(true);
  });

  it('ignora un 401 tardío de un token que ya no es el actual', () => {
    localStorage.setItem('geoeduca_token', 'nuevo');
    expect(shouldDropSession({
      response: { status: 401 },
      config: { url: '/auth/me', headers: { Authorization: 'Bearer viejo' } },
    })).toBe(false);
  });

  it('no trata un error de red como cierre de sesión', () => {
    localStorage.setItem('geoeduca_token', 'vigente');
    expect(shouldDropSession({ config: { url: '/auth/me' } })).toBe(false);
  });
});
