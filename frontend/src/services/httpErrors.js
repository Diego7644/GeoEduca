/** Extrae errores 422 de Laravel ({ message, errors }) para pintarlos por campo. */
export function toApiError(error) {
  const status = error.response?.status;
  const data = error.response?.data;

  if (status === 422 && data?.errors) {
    const fieldErrors = Object.fromEntries(
      Object.entries(data.errors).map(([field, messages]) => [
        field,
        Array.isArray(messages) ? messages[0] : String(messages),
      ])
    );
    const apiError = new Error(data.message || 'Revise los campos del formulario.');
    apiError.status = 422;
    apiError.fieldErrors = fieldErrors;
    return apiError;
  }

  if (!error.response) {
    const apiError = new Error(
      'No se pudo conectar con el servidor. Deje en marcha: cd backend && php artisan serve'
    );
    apiError.status = 0;
    apiError.fieldErrors = {};
    return apiError;
  }

  const apiError = new Error(
    data?.message || error.message || 'No se pudo completar la operación.'
  );
  apiError.status = status;
  apiError.fieldErrors = {};
  return apiError;
}
