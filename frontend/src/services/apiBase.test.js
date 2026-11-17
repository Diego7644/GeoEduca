import { describe, expect, it } from 'vitest';
import { resolveApiBase } from './apiBase';

describe('base de la API en el teléfono', () => {
  it('no envía al iPhone hacia el localhost de la laptop', () => {
    expect(resolveApiBase('http://127.0.0.1:8000/api', '192.168.1.161')).toBe('/api');
    expect(resolveApiBase('http://localhost:8000/api', 'mi-laptop.cola-ts.net')).toBe('/api');
  });

  it('mantiene el proxy relativo y el localhost de la propia laptop', () => {
    expect(resolveApiBase('/api', '192.168.1.161')).toBe('/api');
    expect(resolveApiBase('http://127.0.0.1:8000/api', 'localhost')).toBe('http://127.0.0.1:8000/api');
  });
});
