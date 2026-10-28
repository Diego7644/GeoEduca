import { useCallback, useEffect, useState } from 'react';
import DashboardLayout from '../components/DashboardLayout';
import PageState from '../components/PageState';
import Icon from '../components/Icon';
import Toast, { useToast } from '../components/Toast';
import useApiResource from '../hooks/useApiResource';
import { getConfiguracion, saveConfiguracion } from '../services/asistenciaService';

const FIELDS = [
  ['school', 'Colegio', 'text'],
  ['campus', 'Sede', 'text'],
  ['address', 'Dirección', 'text'],
  ['year', 'Año lectivo', 'text'],
  ['entry_limit', 'Límite de ingreso', 'time'],
  ['exit_time', 'Salida regular', 'time'],
  ['tolerance_min', 'Tolerancia (minutos)', 'number'],
  ['geofence_m', 'Perímetro GPS (metros)', 'number'],
  ['promotion_min', 'Meta de promoción (%)', 'number'],
];

export default function Configuracion() {
  const load = useCallback(() => getConfiguracion(), []);
  const { data, setData, error, loading, reload } = useApiResource(load);
  const { toast, showToast } = useToast();
  const [form, setForm] = useState(null);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (data?.config) setForm(data.config);
  }, [data]);

  const setField = (key, value) => setForm((current) => ({ ...current, [key]: value }));

  const save = async (event) => {
    event.preventDefault();
    if (!form) return;
    setSaving(true);
    try {
      const result = await saveConfiguracion({
        ...form,
        geofence_m: Number(form.geofence_m),
        promotion_min: Number(form.promotion_min),
        tolerance_min: Number(form.tolerance_min),
      });
      setData(result);
      showToast(result.message || 'Configuración actualizada');
    } catch (err) {
      showToast(err.message);
    } finally {
      setSaving(false);
    }
  };

  return (
    <DashboardLayout>
      <PageState loading={loading} error={error} onRetry={reload}>
        {form && (
          <form onSubmit={save} className="max-w-3xl flex flex-col gap-space-lg">
            <div>
              <span className="font-label-sm text-label-sm uppercase tracking-wider text-outline">Configuración del colegio</span>
              <h1 className="font-headline-lg text-headline-lg text-on-surface font-bold">Parámetros de la jornada</h1>
              <p className="font-body-md text-on-surface-variant">
                {data.can_edit
                  ? 'Estos valores cambian el límite de ingreso, la salida y la puntualidad que ven estudiantes y familias.'
                  : 'Solo el auxiliar de puerta puede modificar estos parámetros. El resto de perfiles los consulta.'}
              </p>
            </div>

            <div className="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm grid grid-cols-1 sm:grid-cols-2 gap-4">
              {FIELDS.map(([key, label, type]) => (
                <label key={key} className={`flex flex-col gap-1 ${key === 'address' ? 'sm:col-span-2' : ''}`}>
                  <span className="font-label-md font-semibold text-on-surface">{label}</span>
                  <input
                    type={type}
                    min={type === 'number' ? 0 : undefined}
                    value={form[key] ?? ''}
                    disabled={!data.can_edit}
                    onChange={(event) => setField(key, event.target.value)}
                    className="bg-surface-container-low text-on-surface p-2.5 rounded-lg font-body-md disabled:opacity-70"
                  />
                </label>
              ))}
            </div>

            <div className="flex items-center gap-3 text-on-surface-variant font-body-sm">
              <Icon name="my_location" className="text-primary" />
              <span>
                Geocerca de referencia: {form.lat}, {form.lng}. El estudiante compara su GPS con este punto.
              </span>
            </div>

            {data.can_edit && (
              <button
                type="submit"
                disabled={saving}
                className="self-start px-5 py-2.5 rounded-lg bg-primary text-on-primary font-label-md font-bold disabled:opacity-50"
              >
                {saving ? 'Guardando...' : 'Guardar configuración'}
              </button>
            )}
          </form>
        )}
      </PageState>
      <Toast toast={toast} />
    </DashboardLayout>
  );
}
