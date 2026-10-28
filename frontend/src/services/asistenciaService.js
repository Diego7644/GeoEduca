import apiClient from './apiClient';
import { toApiError } from './httpErrors';

/** Las validaciones de payload viven en el backend (Form Requests). Aquí solo se transportan. */
export async function submitJustification(payload) {
  try {
    const { data } = await apiClient.post('/asistencia/justificacion', payload);
    return data;
  } catch (error) {
    throw toApiError(error);
  }
}

export async function approveManualEntry(payload) {
  try {
    const { data } = await apiClient.post('/asistencia/auxiliar/aprobar', payload);
    return data;
  } catch (error) {
    throw toApiError(error);
  }
}
