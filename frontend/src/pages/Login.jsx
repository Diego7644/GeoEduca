import { useState } from 'react';
import { Navigate, useNavigate } from 'react-router-dom';
import FieldError from '../components/FieldError';
import Icon from '../components/Icon';
import RecoveryModal from '../components/RecoveryModal';
import SafeImage from '../components/SafeImage';
import { IMAGES } from '../config/images';
import { ROLE_HOME } from '../config/roles';
import { useAuth } from '../context/AuthContext';
import useValidatedField from '../hooks/useValidatedField';
import { validateIdentifier, validatePassword } from '../validation/rules';

const ROLES = [
  { key: 'student', label: 'Estudiante', icon: 'school' },
  { key: 'parent', label: 'Padre / Tutor', icon: 'family_restroom' },
  { key: 'staff', label: 'Docente', icon: 'badge' },
];

const inputShell = (hasError) =>
  `relative flex items-center bg-surface-container-low rounded-lg transition-colors ${
    hasError
      ? 'ring-2 ring-error'
      : 'focus-within:bg-surface-container-lowest focus-within:shadow-md'
  }`;

export default function Login() {
  const { user, login } = useAuth();
  const navigate = useNavigate();

  const [role, setRole] = useState('student');
  const identifier = useValidatedField(validateIdentifier);
  const password = useValidatedField(validatePassword);
  const [showPassword, setShowPassword] = useState(false);
  const [remember, setRemember] = useState(true);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [recoveryOpen, setRecoveryOpen] = useState(false);
  const carnetMessage =
    'El carnet NFC o el QR no inician sesión en el portal. Use DNI y contraseña. El auxiliar identifica el carnet en la puerta con su propia sesión.';

  if (user) return <Navigate to={ROLE_HOME[user.role]} replace />;

  const canSubmit = identifier.isValid && password.isValid && !loading;

  const handleSubmit = async (e) => {
    e.preventDefault();
    identifier.onBlur();
    password.onBlur();
    if (!identifier.isValid || !password.isValid) return;

    setError('');
    setLoading(true);
    try {
      const loggedUser = await login({
        role,
        identifier: identifier.value.trim(),
        password: password.value,
      });
      navigate(ROLE_HOME[loggedUser.role], { replace: true });
    } catch (err) {
      if (err.fieldErrors?.identifier) identifier.setError(err.fieldErrors.identifier);
      if (err.fieldErrors?.password) password.setError(err.fieldErrors.password);
      setError(err.message || 'No se pudo iniciar sesión.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="bg-surface font-body-md text-on-surface antialiased flex items-center justify-center min-h-screen">
      <main className="w-full">
        <div className="flex flex-col w-full items-center justify-center py-10 px-4 sm:px-6 relative overflow-hidden">
          <div className="absolute -top-36 -left-36 w-96 h-96 rounded-full bg-surface-container-high/40 blur-3xl pointer-events-none" />
          <div className="absolute -bottom-36 -right-36 w-96 h-96 rounded-full bg-secondary-container/20 blur-3xl pointer-events-none" />

          <div className="relative w-full max-w-xl bg-surface-container-lowest rounded-xl shadow-xl p-6 sm:p-10 transition-all duration-300">
            <div className="flex flex-col items-center text-center">
              <div className="flex items-center justify-center mb-6">
                <SafeImage alt="GEOEDUCA Logo" className="h-16 w-auto object-contain" src={IMAGES.logo} />
              </div>
              <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-surface-container mb-3">
                <span className="w-2 h-2 rounded-full bg-secondary animate-pulse" />
                <span className="font-label-sm text-label-sm text-secondary uppercase tracking-wider">
                  Sistema Escolar Activo
                </span>
              </div>
              <h1 className="font-headline-lg text-headline-lg text-on-surface">Iniciar Sesión en GEOEDUCA</h1>
              <p className="font-body-md text-body-md text-on-surface-variant mt-1.5 max-w-md">
                Plataforma oficial de control biométrico y asistencia escolar en tiempo real.
              </p>
            </div>

            <div className="mt-8">
              <div className="bg-surface-container p-1 rounded-lg grid grid-cols-3 gap-1">
                {ROLES.map((r) => (
                  <button
                    key={r.key}
                    type="button"
                    onClick={() => setRole(r.key)}
                    className={`py-2 px-2 text-center rounded-lg font-label-md text-label-md transition-all duration-200 flex items-center justify-center gap-1.5 ${
                      role === r.key
                        ? 'bg-surface-container-lowest text-primary shadow-sm'
                        : 'text-on-surface-variant hover:text-on-surface'
                    }`}
                  >
                    <Icon name={r.icon} className="text-base" />
                    <span>{r.label}</span>
                  </button>
                ))}
              </div>
            </div>

            <form className="mt-6 space-y-5" onSubmit={handleSubmit} noValidate>
              <div className="space-y-1.5">
                <label
                  className="flex items-center justify-between font-label-md text-label-md text-on-surface"
                  htmlFor="identifier"
                >
                  <span>DNI</span>
                  <span className="font-label-sm text-label-sm text-on-surface-variant">Requerido</span>
                </label>
                <div className={inputShell(!!identifier.error)}>
                  <div className="absolute left-3.5 flex items-center pointer-events-none text-on-surface-variant">
                    <Icon name="contact_emergency" className="text-xl" />
                  </div>
                  <input
                    id="identifier"
                    type="text"
                    inputMode="numeric"
                    autoComplete="username"
                    maxLength={8}
                    value={identifier.value}
                    onChange={(e) => {
                      const digits = e.target.value.replace(/\D/g, '').slice(0, 8);
                      identifier.onChange({ target: { value: digits } });
                    }}
                    onBlur={identifier.onBlur}
                    placeholder="Ej. 12345678"
                    aria-invalid={!!identifier.error}
                    aria-describedby={identifier.error ? 'identifier-error' : undefined}
                    className="w-full h-11 pl-11 pr-4 bg-transparent font-body-md text-body-md text-on-surface placeholder:text-outline outline-none"
                  />
                </div>
                <FieldError id="identifier-error" message={identifier.error} />
              </div>

              <div className="space-y-1.5">
                <div className="flex items-center justify-between">
                  <label className="font-label-md text-label-md text-on-surface" htmlFor="password">
                    Contraseña de Acceso
                  </label>
                  <button
                    type="button"
                    className="font-label-md text-label-md text-primary-container hover:underline transition-colors"
                    onClick={() => setRecoveryOpen(true)}
                  >
                    ¿Olvidaste tu contraseña?
                  </button>
                </div>
                <div className={inputShell(!!password.error)}>
                  <div className="absolute left-3.5 flex items-center pointer-events-none text-on-surface-variant">
                    <Icon name="lock" className="text-xl" />
                  </div>
                  <input
                    id="password"
                    type={showPassword ? 'text' : 'password'}
                    autoComplete="current-password"
                    value={password.value}
                    onChange={password.onChange}
                    onBlur={password.onBlur}
                    placeholder="••••••••••••"
                    aria-invalid={!!password.error}
                    aria-describedby={password.error ? 'password-error' : undefined}
                    className="w-full h-11 pl-11 pr-11 bg-transparent font-body-md text-body-md text-on-surface placeholder:text-outline outline-none"
                  />
                  <button
                    type="button"
                    aria-label="Alternar visibilidad de contraseña"
                    onClick={() => setShowPassword((v) => !v)}
                    className="absolute right-3 text-on-surface-variant hover:text-on-surface p-1 rounded-md transition-colors"
                  >
                    <Icon name={showPassword ? 'visibility_off' : 'visibility'} className="text-xl" />
                  </button>
                </div>
                <FieldError id="password-error" message={password.error} />
              </div>

              <div className="flex items-center justify-between pt-1">
                <label className="flex items-center gap-2.5 cursor-pointer select-none">
                  <input
                    type="checkbox"
                    checked={remember}
                    onChange={(e) => setRemember(e.target.checked)}
                    className="w-4 h-4 rounded text-primary-container accent-primary-container cursor-pointer"
                  />
                  <span className="font-body-sm text-body-sm text-on-surface-variant">
                    Recordar este dispositivo seguro
                  </span>
                </label>
                <span className="font-label-sm text-label-sm text-secondary flex items-center gap-1">
                  <Icon name="verified_user" className="text-sm" />
                  Protección SSL 256-bit
                </span>
              </div>

              {error && (
                <div className="flex items-center gap-2 px-3 py-2 rounded-lg bg-error-container text-on-error-container font-body-sm text-body-sm">
                  <Icon name="error" className="text-[18px]" />
                  {error}
                </div>
              )}

              <button
                type="submit"
                disabled={!canSubmit}
                className="w-full h-11 bg-primary-container hover:bg-primary disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-primary-container text-on-primary rounded-lg font-title-lg text-title-lg flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition-all transform active:scale-[0.99] enabled:cursor-pointer"
              >
                <span>{loading ? 'Verificando...' : 'Ingresar al Portal Escolar'}</span>
                <Icon
                  name={loading ? 'progress_activity' : 'arrow_forward'}
                  className={`text-lg ${loading ? 'animate-spin' : ''}`}
                />
              </button>
            </form>

            <div className="relative my-7">
              <div className="flex items-center gap-3">
                <div className="flex-1 h-px bg-surface-container-high" />
                <span className="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant bg-surface-container-lowest px-2">
                  Métodos alternativos de ingreso
                </span>
                <div className="flex-1 h-px bg-surface-container-high" />
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <button
                type="button"
                onClick={() => setError(carnetMessage)}
                className="flex items-center gap-3 p-3 rounded-lg bg-surface-container-low hover:bg-surface-container text-left transition-colors cursor-pointer group"
              >
                <div className="w-9 h-9 rounded-lg bg-primary-fixed flex items-center justify-center text-primary group-hover:scale-105 transition-transform shrink-0">
                  <Icon name="contactless" className="text-xl" />
                </div>
                <div className="min-w-0">
                  <p className="font-label-md text-label-md text-on-surface leading-tight">Tarjeta NFC Escolar</p>
                  <p className="font-body-sm text-body-sm text-on-surface-variant truncate">Acerca tu carnet al lector</p>
                </div>
              </button>
              <button
                type="button"
                onClick={() => setError(carnetMessage)}
                className="flex items-center gap-3 p-3 rounded-lg bg-surface-container-low hover:bg-surface-container text-left transition-colors cursor-pointer group"
              >
                <div className="w-9 h-9 rounded-lg bg-secondary-fixed flex items-center justify-center text-on-secondary-fixed-variant group-hover:scale-105 transition-transform shrink-0">
                  <Icon name="qr_code_scanner" className="text-xl" />
                </div>
                <div className="min-w-0">
                  <p className="font-label-md text-label-md text-on-surface leading-tight">Código QR Físico</p>
                  <p className="font-body-sm text-body-sm text-on-surface-variant truncate">Escanear con cámara frontal</p>
                </div>
              </button>
            </div>

            <div className="mt-8 pt-6 bg-surface-container-lowest flex flex-col sm:flex-row items-center justify-between gap-3 text-center sm:text-left">
              <div className="flex items-center gap-2">
                <Icon name="support_agent" className="text-on-surface-variant text-lg" />
                <p className="font-body-sm text-body-sm text-on-surface-variant">
                  ¿Problemas de acceso? Anexo <span className="font-semibold text-on-surface">204</span> o{' '}
                  <a className="text-primary-container font-semibold hover:underline" href="mailto:soporte@geoeduca.edu.pe">
                    soporte@geoeduca.edu.pe
                  </a>
                </p>
              </div>
              <div className="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-surface-container-low text-on-surface-variant">
                <Icon name="policy" className="text-sm text-primary" />
                <span className="font-label-sm text-label-sm">Ley de Protección de Datos</span>
              </div>
            </div>
          </div>

          <RecoveryModal
            open={recoveryOpen}
            onClose={() => setRecoveryOpen(false)}
            identifier={identifier.value.trim()}
            role={role}
          />

          <div className="mt-6 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-center text-on-surface-variant font-body-sm text-body-sm">
            <span>© 2025 GEOEDUCA Technologies S.A.C.</span>
            <span>•</span>
            <a className="hover:text-on-surface transition-colors" href="#">Términos de Servicio</a>
            <span>•</span>
            <a className="hover:text-on-surface transition-colors" href="#">Política de Privacidad</a>
            <span>•</span>
            <a className="hover:text-on-surface transition-colors" href="#">Manual de Usuario</a>
          </div>
        </div>
      </main>
    </div>
  );
}
