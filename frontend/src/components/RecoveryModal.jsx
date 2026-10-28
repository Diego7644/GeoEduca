import Icon from './Icon';
import Modal from './Modal';

/**
 * La recuperación del backend existe, pero no hay correo ni SMS.
 * Esta pantalla no solicita ni confirma códigos: no debe parecer un envío real.
 */
export default function RecoveryModal({ open, onClose }) {
  return (
    <Modal open={open} onClose={onClose} maxWidth="max-w-md">
      <div className="p-6 space-y-4">
        <div className="flex items-start justify-between gap-3">
          <h2 className="font-headline-lg text-headline-lg text-on-surface">Recuperación no disponible</h2>
          <button type="button" onClick={onClose} className="text-on-surface-variant hover:text-on-surface" aria-label="Cerrar">
            <Icon name="close" className="text-xl" />
          </button>
        </div>
        <p className="font-body-sm text-body-sm text-on-surface-variant">
          GEOEDUCA no envía códigos por correo ni SMS. La recuperación automática no está disponible
          y esta pantalla no indica si una cuenta existe.
        </p>
        <p className="font-body-sm text-body-sm text-on-surface">
          Para restablecer el acceso, contacte al anexo <span className="font-semibold">204</span> o a{' '}
          <a className="text-primary-container font-semibold hover:underline" href="mailto:soporte@geoeduca.edu.pe">
            soporte@geoeduca.edu.pe
          </a>
          .
        </p>
        <button type="button" onClick={onClose} className="w-full h-11 rounded-lg bg-primary-container text-on-primary font-label-md">
          Volver al inicio de sesión
        </button>
      </div>
    </Modal>
  );
}
