import { useEffect, useRef, useState } from 'react';
import { Html5Qrcode } from 'html5-qrcode';

export default function QrCamera({ onCode }) {
  const regionId = useRef(`qr-camera-${Math.random().toString(36).slice(2)}`);
  const scanner = useRef(null);
  const [error, setError] = useState('');
  const [active, setActive] = useState(false);

  useEffect(() => () => {
    scanner.current?.stop().catch(() => {});
  }, []);

  const start = async () => {
    setError('');
    const instance = new Html5Qrcode(regionId.current);
    scanner.current = instance;
    try {
      await instance.start(
        { facingMode: 'environment' },
        { fps: 8, qrbox: 220 },
        (text) => {
          onCode(text.trim());
          instance.stop().catch(() => {});
          setActive(false);
        }
      );
      setActive(true);
    } catch (err) {
      setError(err?.message || 'No se pudo abrir la cámara.');
      setActive(false);
    }
  };

  return (
    <div className="space-y-2">
      <div id={regionId.current} className="overflow-hidden rounded-xl bg-surface-container" />
      {!active && (
        <button type="button" onClick={start} className="px-4 py-2 rounded-lg bg-secondary text-on-secondary font-semibold">
          Abrir cámara
        </button>
      )}
      {error && <p className="font-body-sm text-error">{error}</p>}
    </div>
  );
}
