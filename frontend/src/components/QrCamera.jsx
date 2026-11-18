import { useEffect, useId, useRef, useState } from 'react';
import { Html5Qrcode } from 'html5-qrcode';
import { cameraFailureMessage, shouldTryAnotherCamera } from './cameraAccess';

const FACING_MODES = ['environment', 'user'];

function scanConfig() {
  return {
    fps: 10,
    disableFlip: true,
    qrbox: (viewfinderWidth, viewfinderHeight) => {
      const edge = Math.floor(Math.min(viewfinderWidth, viewfinderHeight) * 0.72);
      const size = Math.max(50, edge);
      return { width: size, height: size };
    },
  };
}

async function releaseScanner(instance, region) {
  if (instance) {
    try {
      await instance.stop();
    } catch {
      /* El escáner puede no haber llegado a estado activo. */
    }
    try {
      instance.clear();
    } catch {
      /* clear exige que el escaneo ya esté cerrado. */
    }
  }
  region?.querySelectorAll('video').forEach((video) => {
    const stream = video.srcObject;
    if (stream && typeof stream.getTracks === 'function') {
      stream.getTracks().forEach((track) => track.stop());
    }
    video.pause();
    video.srcObject = null;
    video.remove();
  });
}

export default function QrCamera({ onCode }) {
  const regionId = useId().replace(/:/g, '');
  const scanner = useRef(null);
  const busy = useRef(false);
  const seen = useRef(false);
  const [error, setError] = useState('');
  const [active, setActive] = useState(false);

  const stop = async () => {
    const instance = scanner.current;
    scanner.current = null;
    await releaseScanner(instance, document.getElementById(regionId));
    setActive(false);
  };

  useEffect(() => {
    const id = regionId;
    return () => {
      const instance = scanner.current;
      scanner.current = null;
      releaseScanner(instance, document.getElementById(id)).catch(() => {
        /* Al desmontar ya no hay pantalla donde mostrar el fallo de cierre. */
      });
    };
  }, [regionId]);

  const start = async () => {
    if (busy.current || active) return;
    busy.current = true;
    seen.current = false;
    setError('');

    const secure = window.isSecureContext;
    const mediaDevices = Boolean(navigator.mediaDevices?.getUserMedia);
    if (!secure || !mediaDevices) {
      setError(cameraFailureMessage(null, { secure, mediaDevices }));
      busy.current = false;
      return;
    }

    const onScan = (text) => {
      if (seen.current) return;
      seen.current = true;
      const value = String(text || '').trim();
      stop()
        .catch(() => {
          /* El código leído se entrega aunque el cierre de la cámara falle. */
        })
        .then(() => onCode(value))
        .catch(() => {
          /* El panel ya muestra el error de validación si onCode lo rechaza. */
        });
    };

    let lastError = null;
    try {
      for (const facingMode of FACING_MODES) {
        const instance = new Html5Qrcode(regionId, {
          verbose: false,
          useBarCodeDetectorIfSupported: false,
        });
        scanner.current = instance;
        try {
          await instance.start(
            { facingMode },
            scanConfig(),
            onScan,
            () => {},
          );
          const video = document.getElementById(regionId)?.querySelector('video');
          if (video) {
            video.setAttribute('playsinline', 'true');
            video.setAttribute('webkit-playsinline', 'true');
            video.muted = true;
          }
          setActive(true);
          lastError = null;
          break;
        } catch (err) {
          lastError = err;
          await releaseScanner(instance, document.getElementById(regionId));
          if (scanner.current === instance) scanner.current = null;
          if (!shouldTryAnotherCamera(err) || facingMode === 'user') break;
        }
      }
      if (lastError) {
        setActive(false);
        setError(cameraFailureMessage(lastError, { secure, mediaDevices }));
      }
    } finally {
      busy.current = false;
    }
  };

  return (
    <div className="space-y-3">
      <div>
        <h2 className="font-title-lg font-bold">Escanear QR del estudiante</h2>
        <p className="font-body-sm text-on-surface-variant">
          Se usa la cámara trasera. Al leer un código vigente, el panel lo envía a la I.E. Mariscal Castilla y cierra la cámara.
        </p>
      </div>
      <div
        id={regionId}
        className="w-full overflow-hidden rounded-xl bg-surface-container min-h-16 [&_video]:!w-full [&_video]:!h-auto [&_video]:max-h-[70vh] [&_video]:object-cover [&_video]:bg-black"
      />
      <div className="flex flex-col sm:flex-row gap-2">
        {!active && (
          <button
            type="button"
            onClick={() => {
              start().catch((err) => {
                busy.current = false;
                setActive(false);
                setError(cameraFailureMessage(err, { secure: window.isSecureContext, mediaDevices: Boolean(navigator.mediaDevices?.getUserMedia) }));
              });
            }}
            className="min-h-11 px-4 py-2 rounded-lg bg-secondary text-on-secondary font-semibold"
          >
            Abrir cámara
          </button>
        )}
        {active && (
          <button
            type="button"
            onClick={() => {
              stop().catch(() => setError('No se pudo cerrar la cámara. Vuelve a abrirla o recarga el panel.'));
            }}
            className="min-h-11 px-4 py-2 rounded-lg bg-surface-container-high font-semibold"
          >
            Cerrar cámara
          </button>
        )}
      </div>
      {error && <p role="alert" className="font-body-sm text-error">{error}</p>}
    </div>
  );
}
