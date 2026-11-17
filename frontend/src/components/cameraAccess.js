/** Texto usable de un fallo de getUserMedia o de html5-qrcode. */
export function cameraErrorText(error) {
  if (typeof error === 'string') return error;
  return [error?.name, error?.message].filter(Boolean).join(' ');
}

/**
 * Safari iOS oculta la cámara en HTTP que no sea localhost.
 * html5-qrcode además rechaza con un string, que no tiene `.message`.
 */
export function cameraFailureMessage(error, { secure = true, mediaDevices = true } = {}) {
  const text = cameraErrorText(error).toLowerCase();

  if (!secure || !mediaDevices || /mediaDevices not supported|camera streaming not supported|secure context|insecure context|only supported in secure/.test(text)) {
    return 'Safari solo abre la cámara en una página HTTPS. La dirección http://192.168… no sirve: abre la URL https de Tailscale.';
  }
  if (error?.name === 'NotAllowedError' || /notallowed|permission denied|permission dismissed/.test(text)) {
    return 'Safari bloqueó la cámara. En Ajustes > Safari > Cámara elige Preguntar, vuelve a abrir el sitio y acepta el permiso.';
  }
  if (error?.name === 'NotFoundError' || /notfound|requested device not found|no cameras|no video tracks/.test(text)) {
    return 'Este dispositivo no tiene una cámara que se pueda usar.';
  }
  if (error?.name === 'NotReadableError' || /notreadable|could not start video source|device in use|track start/.test(text)) {
    return 'La cámara está ocupada por otra aplicación. Ciérrala y pulsa Abrir cámara otra vez.';
  }
  if (error?.name === 'OverconstrainedError' || /overconstrained/.test(text)) {
    return 'Ninguna cámara del dispositivo aceptó el modo de video. Cierra otras apps que usen la cámara y vuelve a intentar.';
  }
  if (error?.name === 'AbortError' || /\baborterror\b|\babort\b/.test(text)) {
    return 'La apertura de la cámara se interrumpió. Pulsa Abrir cámara otra vez.';
  }
  if (error?.name === 'SecurityError' || /securityerror/.test(text)) {
    return 'El navegador bloqueó la cámara por seguridad. Entra por la dirección HTTPS de Tailscale.';
  }

  if (typeof error === 'string' && error.trim()) return error.trim();
  return error?.message || error?.name || 'No se pudo abrir la cámara.';
}

/** La trasera puede no existir en un iPad o en un simulador; se prueba la frontal. */
export function shouldTryAnotherCamera(error) {
  const text = cameraErrorText(error).toLowerCase();
  return error?.name === 'OverconstrainedError'
    || error?.name === 'NotFoundError'
    || /overconstrained|notfound|requested device not found|no video tracks/.test(text);
}
