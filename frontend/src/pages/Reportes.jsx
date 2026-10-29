import { useCallback, useState } from 'react';
import DashboardLayout from '../components/DashboardLayout';
import PageState from '../components/PageState';
import Toast, { useToast } from '../components/Toast';
import useApiResource from '../hooks/useApiResource';
import { getReportes } from '../services/asistenciaService';
import { downloadCsv, escapeHtml, printHtml } from '../utils/attendance';

export default function Reportes() {
  const [draft, setDraft] = useState({ from: '', to: '', student: '' });
  const [filters, setFilters] = useState({ from: '', to: '', student: '' });
  const load = useCallback(() => getReportes(filters), [filters]);
  const { data, error, loading, reload } = useApiResource(load);
  const { toast, showToast } = useToast();
  const max = Math.max(1, ...(data?.series ?? []).map((item) => Number(item.value) || 0));

  const exportCsv = () => {
    if (!data) return;
    downloadCsv('Reporte_GEOEDUCA.csv', data.columns, data.rows);
    showToast('Reporte descargado', 'El CSV incluye la tabla visible de este panel.');
  };

  const exportPdf = () => {
    if (!data) return;
    const head = data.columns.map((column) => `<th>${escapeHtml(column)}</th>`).join('');
    const body = data.rows
      .map((row) => `<tr>${row.map((cell) => `<td>${escapeHtml(cell)}</td>`).join('')}</tr>`)
      .join('');
    const opened = printHtml(data.title, `<h1>${escapeHtml(data.title)}</h1><p>${escapeHtml(data.subtitle)} · ${escapeHtml(data.range_label)}</p><table><thead><tr>${head}</tr></thead><tbody>${body}</tbody></table>`);
    showToast(opened ? 'Listo para guardar como PDF' : 'El navegador bloqueó la ventana de impresión.');
  };

  return (
    <DashboardLayout>
      <PageState loading={loading} error={error} onRetry={reload}>
        {data && (
          <div className="flex flex-col gap-space-lg">
            <div className="flex flex-col lg:flex-row lg:items-end justify-between gap-space-md">
              <div>
                <span className="font-label-sm text-label-sm uppercase tracking-wider text-outline">Reportes y estadísticas</span>
                <h1 className="font-headline-lg text-headline-lg text-on-surface font-bold">{data.title}</h1>
                <p className="font-body-md text-on-surface-variant">{data.subtitle}</p>
              </div>
              <div className="flex items-center gap-2">
                <span className="px-3 py-2 rounded-lg bg-surface-container-low font-label-md text-on-surface">{data.range_label}</span>
                <button type="button" onClick={exportCsv} className="px-3 py-2 rounded-lg bg-surface-container-low font-label-md font-semibold">CSV</button>
                <button type="button" onClick={exportPdf} className="px-3 py-2 rounded-lg bg-primary text-on-primary font-label-md font-semibold">Imprimir</button>
              </div>
            </div>

            <form
              className="flex flex-col sm:flex-row gap-2"
              onSubmit={(event) => {
                event.preventDefault();
                setFilters(draft);
              }}
            >
              <input type="date" value={draft.from} onChange={(event) => setDraft((current) => ({ ...current, from: event.target.value }))} className="bg-surface-container-low rounded-lg px-3 py-2" aria-label="Desde" />
              <input type="date" value={draft.to} onChange={(event) => setDraft((current) => ({ ...current, to: event.target.value }))} className="bg-surface-container-low rounded-lg px-3 py-2" aria-label="Hasta" />
              <input value={draft.student} onChange={(event) => setDraft((current) => ({ ...current, student: event.target.value }))} placeholder="Estudiante o DNI" className="bg-surface-container-low rounded-lg px-3 py-2" aria-label="Estudiante" />
              <button type="submit" className="px-4 py-2 rounded-lg bg-primary text-on-primary font-semibold">Filtrar</button>
            </form>
            <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-space-md">
              {data.kpis.map((kpi) => (
                <div key={kpi.label} className="bg-surface-container-lowest rounded-xl p-space-md shadow-sm">
                  <span className="font-label-sm text-label-sm uppercase tracking-wider text-outline">{kpi.label}</span>
                  <div className="font-metric-display text-headline-md font-bold text-on-surface mt-1">{kpi.value}</div>
                  <p className="font-body-sm text-outline mt-1">{kpi.hint}</p>
                </div>
              ))}
            </div>

            <div className="grid grid-cols-1 xl:grid-cols-5 gap-space-lg">
              <section className="xl:col-span-2 bg-surface-container-lowest rounded-xl p-space-lg shadow-sm">
                <h2 className="font-title-lg font-bold text-on-surface mb-space-md">{data.series_title}</h2>
                <div className="flex flex-col gap-3">
                  {data.series.length === 0 && <p className="text-outline font-body-sm">Sin datos para este periodo.</p>}
                  {data.series.map((item) => {
                    const width = data.series_unit === 'percent' ? item.value : (item.value / max) * 100;
                    return (
                      <div key={item.label}>
                        <div className="flex items-center justify-between gap-3 font-body-sm">
                          <span className="text-on-surface truncate">{item.label}</span>
                          <span className="font-semibold text-primary">{item.display}</span>
                        </div>
                        <div className="h-2 mt-1 rounded-full bg-surface-container overflow-hidden">
                          <div className="h-full bg-primary rounded-full" style={{ width: `${Math.max(4, Math.min(100, width))}%` }} />
                        </div>
                      </div>
                    );
                  })}
                </div>
              </section>

              <section className="xl:col-span-3 bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
                <div className="overflow-x-auto">
                  <table className="w-full text-left">
                    <thead>
                      <tr className="bg-surface-container-low text-outline font-label-sm uppercase">
                        {data.columns.map((column) => (
                          <th key={column} className="px-4 py-3">{column}</th>
                        ))}
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-surface-container-low font-body-sm">
                      {data.rows.map((row, index) => (
                        <tr key={`${row[0]}-${index}`}>
                          {row.map((cell, cellIndex) => (
                            <td key={cellIndex} className="px-4 py-3 text-on-surface">{cell}</td>
                          ))}
                        </tr>
                      ))}
                      {data.rows.length === 0 && (
                        <tr>
                          <td colSpan={data.columns.length} className="px-4 py-8 text-center text-outline">Sin filas en este reporte.</td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
              </section>
            </div>
          </div>
        )}
      </PageState>
      <Toast toast={toast} />
    </DashboardLayout>
  );
}
