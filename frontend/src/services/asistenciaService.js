import apiClient from './apiClient';
import { toApiError } from './httpErrors';

/** Las validaciones de payload viven en el backend (Form Requests). Aquí solo se transportan. */
async function request(method, url, payload) {
  try {
    const { data } = await apiClient.request({ method, url, data: payload });
    return data;
  } catch (error) {
    throw toApiError(error);
  }
}

export const getEstudiante = () => request('get', '/asistencia/estudiante');
export const refreshStudentToken = () => request('post', '/asistencia/estudiante/token').then((data) => data.token);
export const sendUbicacion = (payload) => request('post', '/asistencia/estudiante/ubicacion', payload);
export const getDocente = () => request('get', '/asistencia/docente');
export const getDirectorio = () => request('get', '/admin/directorio');
export const linkGuardian = (payload) => request('post', '/admin/apoderados', payload);
export const getPadre = () => request('get', '/asistencia/padre');
export const getAuxiliar = () => request('get', '/asistencia/auxiliar/casos');
export const getReportes = () => request('get', '/asistencia/reportes');
export const getConfiguracion = () => request('get', '/asistencia/configuracion');
export const saveConfiguracion = (payload) => request('put', '/asistencia/configuracion', payload);
export const getNotificaciones = () => request('get', '/asistencia/notificaciones').then((data) => data.notifications);
export const markNotificacionesRead = () => request('post', '/asistencia/notificaciones/leidas').then((data) => data.notifications);
export const linkChild = (dni) => request('post', '/asistencia/padre/vincular', { dni });
export const submitJustification = (payload) => request('post', '/asistencia/justificacion', payload);
export const approveManualEntry = (payload) => request('post', '/asistencia/auxiliar/aprobar', payload);
export const validateQr = (value) => request('post', '/asistencia/qr/validar', { value });
