import apiClient from './apiClient';
import { AUTH_RULES, validateIdentifier, validatePassword } from '../validation/rules';
import { toApiError } from './httpErrors';

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

function confirmedAccount(data) {
  const account = data?.user;
  if (!data?.token || !account?.role || !AUTH_RULES.roles.includes(account.role)) {
    const error = new Error('La sesión no se pudo confirmar.');
    error.status = 502;
    throw error;
  }
  return { token: data.token, user: account };
}

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
    throw error;
  }
  return confirmedAccount(data);
}

export function requestRecovery(payload) {
  return postAuth('/auth/recuperar', payload);
}

export function confirmRecovery(payload) {
  return postAuth('/auth/recuperar/confirmar', payload);
}

/** Revoca el PAT. El token va explícito porque el almacenamiento ya pudo limpiarse. */
export async function logout(token) {
  try {
    await apiClient.post('/auth/logout', {}, {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
  } catch (error) {
    throw toApiError(error);
  }
}

/** Usuario confirmado por GET /api/auth/me. El rol sale del backend. */
export async function currentUser() {
  try {
    const { data } = await apiClient.get('/auth/me');
    const account = data?.user;
    if (!account?.role || !AUTH_RULES.roles.includes(account.role)) {
      const error = new Error('No autenticado.');
      error.status = 401;
      throw error;
    }
    return account;
  } catch (error) {
    if (error.status) throw error;
    throw toApiError(error);
  }
}

export async function login(credentials) {
  assertClientRules(credentials);

  try {
    const { data } = await apiClient.post('/auth/login', credentials);
    return confirmedAccount(data);
  } catch (error) {
    if (error.status) throw error;
    throw toApiError(error);
  }
}
