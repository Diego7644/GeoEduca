import { describe, expect, it } from 'vitest';
import { cameraFailureMessage, shouldTryAnotherCamera } from './cameraAccess';

describe('mensajes de la cámara', () => {
  it('explica que HTTP en la red local no es un contexto seguro', () => {
    const message = cameraFailureMessage('navigator.mediaDevices not supported', {
      secure: false,
      mediaDevices: false,
    });
    expect(message).toMatch(/HTTPS/);
    expect(message).not.toBe('No se pudo abrir la cámara.');
  });

  it('distingue permiso, cámara ocupada, cámara ausente e interrupción', () => {
    expect(cameraFailureMessage({ name: 'NotAllowedError', message: 'Permission denied' })).toMatch(/Safari bloqueó/);
    expect(cameraFailureMessage({ name: 'NotReadableError', message: 'Could not start video source' })).toMatch(/ocupada/);
    expect(cameraFailureMessage({ name: 'NotFoundError', message: 'Requested device not found' })).toMatch(/no tiene una cámara/);
    expect(cameraFailureMessage({ name: 'AbortError', message: 'The operation was aborted' })).toMatch(/interrumpió/);
  });

  it('conserva un mensaje desconocido y reintenta solo la otra cámara', () => {
    expect(cameraFailureMessage(new Error('fallo interno del decodificador'))).toBe('fallo interno del decodificador');
    expect(shouldTryAnotherCamera({ name: 'OverconstrainedError', message: 'facingMode' })).toBe(true);
    expect(shouldTryAnotherCamera({ name: 'NotAllowedError', message: 'Permission denied' })).toBe(false);
  });
});
