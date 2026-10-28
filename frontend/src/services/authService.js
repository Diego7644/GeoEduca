import apiClient from './apiClient';
import { AUTH_RULES, validateIdentifier, validatePassword } from '../validation/rules';
import { toApiError } from './httpErrors';

const ROLE_LABELS = {
  student: 'Estudiante 4to Secundaria B',
  parent: 'Padre / Tutor',
  staff: 'Auxiliar de Puerta',
};

function assertClientRules({ identifier, password, role }) {
  const identifierError = validateIdentifier(identifier);
  const passwordError = validatePassword(password);
  if (identifierError || passwordError) {
    const error = new Error('Revise los campos del formulario.');
    error.status = 422;
    error.fieldErrors = {
      ...(identifierError ? { identifier: identifierError } : {}),
      ...(passwordError ? { password: passwordError } : {}),
    };
    throw error;
  }
  if (!AUTH_RULES.roles.includes(role)) {
    const error = new Error('El perfil de acceso no es válido.');
    error.status = 422;
    error.fieldErrors = { role: error.message };
    throw error;
  }
}

/**
 * Login de producto: valida en cliente y llama a POST /auth/login.
 * No hay autenticación de demostración en el cliente: el backend resuelve DNI+rol contra la BD.
 */
async function postAuth(url, payload) {
  try {
    const { data } = await apiClient.post(url, payload);
    return data;
  } catch (error) {
    throw toApiError(error);
  }
}

export function detectAccess(payload) {
  return postAuth('/auth/acceso/detectar', payload);
}

/**
 * El carnet ya no inicia sesión. Si el backend no emite token, se rechaza
 * en el cliente para que AuthContext no persista una sesión falsa.
 */
export async function loginWithAccess(payload) {
  const data = await postAuth('/auth/acceso', payload);
  if (!data?.token) {
    const error = new Error(
      data?.message || 'El carnet no inicia sesión en el portal. Use DNI y contraseña.'
    );
    error.status = 403;
    error.fieldErrors = {};
    throw error;
  }
  return data;
}

export function requestRecovery(payload) {
  return postAuth('/auth/recuperar', payload);
}

export function confirmRecovery(payload) {
  return postAuth('/auth/recuperar/confirmar', payload);
}

export function logout() {
  return postAuth('/auth/logout', {});
}

export async function login(credentials) {
  assertClientRules(credentials);

  try {
    const { data } = await apiClient.post('/auth/login', credentials);
    return {
      token: data.token,
      user: {
        roleLabel: ROLE_LABELS[credentials.role],
        ...data.user,
        role: data.user?.role ?? credentials.role,
      },
    };
  } catch (error) {
    throw toApiError(error);
  }
}
