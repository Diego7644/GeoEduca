import apiClient from './apiClient';
import { AUTH_RULES, validateIdentifier, validatePassword } from '../validation/rules';
import { toApiError } from './httpErrors';

const USE_MOCK = import.meta.env.VITE_USE_MOCK_AUTH === 'true';

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
 * El backend vuelve a validar y, si pasa, devuelve el mock (o la BD más adelante).
 * VITE_USE_MOCK_AUTH=true solo como respaldo offline; mismas reglas.
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

export function loginWithAccess(payload) {
  return postAuth('/auth/acceso', payload);
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

  if (USE_MOCK) {
    await new Promise((resolve) => setTimeout(resolve, 350));
    const accounts = {
      student: { dni: '71405255', password: 'estudiante123', id: 1, name: 'Mateo Valenzuela' },
      parent: { dni: '71405255', password: 'padre123', id: 2, name: 'Rosa Valenzuela' },
      staff: { dni: '40111222', password: 'docente123', id: 3, name: 'Aux. Ricardo Paz' },
    };
    const account = accounts[credentials.role];
    if (!account || account.dni !== credentials.identifier || account.password !== credentials.password) {
      const error = new Error('DNI o contraseña incorrectos para este perfil.');
      error.status = 401;
      error.fieldErrors = {
        identifier: 'Revise el DNI.',
        password: 'Revise la contraseña.',
      };
      throw error;
    }
    return {
      token: `mock-token-${credentials.role}`,
      user: {
        id: account.id,
        name: account.name,
        role: credentials.role,
        roleLabel: ROLE_LABELS[credentials.role],
        identifier: account.dni,
      },
    };
  }

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
