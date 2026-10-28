import { useState } from 'react';
import FieldError from './FieldError';
import Icon from './Icon';
import Modal from './Modal';
import useValidatedField from '../hooks/useValidatedField';
import { confirmRecovery, requestRecovery } from '../services/authService';
import { validatePassword, validateRecoveryCode } from '../validation/rules';

/**
 * Recuperación de cuenta. El backend no envía correo ni SMS; el código
 * solo aparece si el entorno de desarrollo lo revela explícitamente.
 */
export default function RecoveryModal({ open, onClose, identifier, role }) {
  const code = useValidatedField(validateRecoveryCode);
  const password = useValidatedField(validatePassword);
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState('');
  const [devCode, setDevCode] = useState('');
  const [step, setStep] = useState('request');
  const [error, setError] = useState('');

  const reset = () => {
    code.setValue('');
    code.setError('');
    password.setValue('');
    password.setError('');
    setBusy(false);
    setMessage('');
    setDevCode('');
    setStep('request');
    setError('');
  };

  const close = () => {
    reset();
    onClose();
  };

  const requestCode = async () => {
    setBusy(true);
    setError('');
    try {
      const data = await requestRecovery({ identifier, role });
      setMessage(data.message);
      setDevCode(data.dev_code || '');
      setStep('confirm');
    } catch (err) {
      setError(err.message || 'No se pudo registrar la solicitud.');
    } finally {
      setBusy(false);
    }
  };

  const confirm = async (event) => {
    event.preventDefault();
    code.onBlur();
    password.onBlur();
    if (!code.isValid || !password.isValid) return;

    setBusy(true);
    setError('');
    try {
      const data = await confirmRecovery({
        identifier,
        role,
        code: code.value.trim(),
        password: password.value,
      });
      setMessage(data.message);
      setDevCode('');
      setStep('done');
    } catch (err) {
      setError(err.message || 'No se pudo confirmar la recuperación.');
    } finally {
      setBusy(false);
    }
  };

  return (
    <Modal open={open} onClose={close} maxWidth="max-w-md">
      <div className="p-6 space-y-4">
        <div className="flex items-start justify-between gap-3">
          <div>
            <h2 className="font-headline-lg text-headline-lg text-on-surface">Recuperar contraseña</h2>
            <p className="font-body-sm text-body-sm text-on-surface-variant mt-1">
              DNI {identifier || '—'} · perfil {role}. GEOEDUCA no envía el código por correo ni SMS.
            </p>
          </div>
          <button type="button" onClick={close} className="text-on-surface-variant hover:text-on-surface" aria-label="Cerrar">
            <Icon name="close" className="text-xl" />
          </button>
        </div>

        {step === 'request' && (
          <div className="space-y-4">
            <p className="font-body-sm text-body-sm text-on-surface-variant">
              Si la cuenta existe se registra una solicitud de 15 minutos. En producción el código
              solo podría entregarse cuando exista un canal real.
            </p>
            {error && <p className="font-body-sm text-body-sm text-error">{error}</p>}
            <button
              type="button"
              disabled={busy || identifier.length !== 8}
              onClick={requestCode}
              className="w-full h-11 rounded-lg bg-primary-container text-on-primary font-label-md disabled:opacity-50"
            >
              {busy ? 'Registrando…' : 'Solicitar recuperación'}
            </button>
          </div>
        )}

        {step === 'confirm' && (
          <form className="space-y-4" onSubmit={confirm} noValidate>
            <p className="font-body-sm text-body-sm text-on-surface-variant">{message}</p>
            {devCode && (
              <p className="font-body-sm text-body-sm rounded-lg bg-tertiary-fixed px-3 py-2 text-on-tertiary-fixed">
                Solo desarrollo: {devCode}. Este valor no se envía ni se muestra en producción.
              </p>
            )}
            <div>
              <label className="font-label-md text-label-md" htmlFor="recovery-code">
                Código de 6 dígitos
              </label>
              <input
                id="recovery-code"
                inputMode="numeric"
                maxLength={6}
                value={code.value}
                onChange={(e) => code.onChange({ target: { value: e.target.value.replace(/\D/g, '').slice(0, 6) } })}
                onBlur={code.onBlur}
                className="mt-1 w-full h-11 rounded-lg bg-surface-container-low px-3 outline-none"
              />
              <FieldError id="recovery-code-error" message={code.error} />
            </div>
            <div>
              <label className="font-label-md text-label-md" htmlFor="recovery-password">
                Nueva contraseña
              </label>
              <input
                id="recovery-password"
                type="password"
                value={password.value}
                onChange={password.onChange}
                onBlur={password.onBlur}
                className="mt-1 w-full h-11 rounded-lg bg-surface-container-low px-3 outline-none"
              />
              <FieldError id="recovery-password-error" message={password.error} />
            </div>
            {error && <p className="font-body-sm text-body-sm text-error">{error}</p>}
            <button
              type="submit"
              disabled={busy}
              className="w-full h-11 rounded-lg bg-primary-container text-on-primary font-label-md disabled:opacity-50"
            >
              {busy ? 'Confirmando…' : 'Actualizar contraseña'}
            </button>
          </form>
        )}

        {step === 'done' && (
          <div className="space-y-4">
            <p className="font-body-sm text-body-sm text-on-surface">{message}</p>
            <button type="button" onClick={close} className="w-full h-11 rounded-lg bg-primary-container text-on-primary font-label-md">
              Volver al inicio de sesión
            </button>
          </div>
        )}
      </div>
    </Modal>
  );
}
