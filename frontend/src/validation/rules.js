/**
 * Reglas de negocio compartidas con el backend (LoginRequest y Form Requests).
 * Si cambia un mínimo aquí, actualizar también app/Http/Requests.
 */
export const AUTH_RULES = {
  identifier: { min: 8, max: 8 },
  password: { min: 8, max: 128 },
  roles: ['student', 'parent', 'staff', 'admin'],
};

export const JUSTIFICATION_RULES = {
  reason: { min: 10, max: 1000 },
};

export const CONTINGENCY_RULES = {
  studentId: { min: 4, max: 40 },
  reason: { min: 4, max: 255 },
};

export function requiredMessage(label) {
  return `${label} es obligatorio.`;
}

export function minLengthMessage(min) {
  return `Debe tener al menos ${min} caracteres.`;
}

export function validateRequiredMin(value, { min, label, trim = true }) {
  const raw = trim ? String(value ?? '').trim() : String(value ?? '');
  if (!raw) return requiredMessage(label);
  if (raw.length < min) return minLengthMessage(min);
  return '';
}

export function validateIdentifier(value) {
  const raw = String(value ?? '').replace(/\D/g, '');
  if (!raw) return 'El DNI es obligatorio.';
  if (raw.length !== 8) return 'El DNI debe tener 8 dígitos.';
  return '';
}

export function validatePassword(value) {
  return validateRequiredMin(value, {
    min: AUTH_RULES.password.min,
    label: 'La contraseña',
    trim: false,
  });
}

export function validateJustificationReason(value) {
  return validateRequiredMin(value, { min: JUSTIFICATION_RULES.reason.min, label: 'El motivo' });
}

export function validateEventDate(value) {
  return validateRequiredMin(value, { min: 1, label: 'La fecha del evento' });
}

export function validateRecoveryCode(value) {
  const raw = String(value ?? '').replace(/\D/g, '');
  if (!raw) return 'El código es obligatorio.';
  if (raw.length !== 6) return 'El código debe tener 6 dígitos.';
  return '';
}

export function validateStudentId(value) {
  return validateRequiredMin(value, {
    min: CONTINGENCY_RULES.studentId.min,
    label: 'El DNI o código',
  });
}
