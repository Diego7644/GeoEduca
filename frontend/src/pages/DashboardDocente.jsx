import { useCallback, useState } from 'react';
import DashboardLayout from '../components/DashboardLayout';
import PageState from '../components/PageState';
import useApiResource from '../hooks/useApiResource';
import { getDocente, reviewJustification } from '../services/asistenciaService';

export default function DashboardDocente() {
  const [date, setDate] = useState('');
  const load = useCallback(() => getDocente(date), [date]);
  const { data, error, loading, reload } = useApiResource(load);

  return (
    <DashboardLayout>
      <PageState loading={loading} error={error} onRetry={reload}>
        {data && (
          <div className="flex flex-col gap-space-lg">
            <div>
              <span className="font-label-sm uppercase tracking-wider text-outline">Docente</span>
              <h1 className="font-headline-lg font-bold text-on-surface">{data.teacher.name}</h1>
              <p className="text-on-surface-variant">Solo ves a los estudiantes de tu tutoría. La puerta la opera el auxiliar.</p>
              <input type="date" value={date} onChange={(event) => setDate(event.target.value)} className="mt-3 bg-surface-container-low rounded-lg px-3 py-2" />
            </div>
            <div className="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
              {data.students.length === 0 && <p className="p-6 text-outline">No tienes estudiantes asignados.</p>}
              {data.students.map((student) => (
                <div key={student.id} className="px-6 py-4 border-b border-surface-container flex justify-between gap-4">
                  <div>
                    <p className="font-semibold text-on-surface">{student.name}</p>
                    <p className="text-on-surface-variant font-body-sm">{student.grade} · DNI {student.dni}</p>
                  </div>
                  <span className="font-label-md text-primary">{student.today.statusLabel}</span>
                </div>
              ))}
            </div>
            <div className="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
              <h2 className="px-6 py-4 font-semibold">Justificaciones</h2>
              {(data.justifications ?? []).length === 0 && <p className="px-6 pb-4 text-outline">No hay solicitudes.</p>}
              {(data.justifications ?? []).map((item) => (
                <div key={item.id} className="px-6 py-3 border-t border-surface-container flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                  <div>
                    <p className="font-semibold">{item.student} · {item.event_date}</p>
                    <p className="font-body-sm text-on-surface-variant">{item.reason}</p>
                    <p className="font-label-sm uppercase text-primary">{item.status}</p>
                  </div>
                  {item.status === 'submitted' && (
                    <div className="flex gap-2">
                      <button type="button" className="px-3 py-1 rounded-lg bg-secondary text-on-secondary" onClick={() => reviewJustification(item.id, 'approved').then(reload)}>Aprobar</button>
                      <button type="button" className="px-3 py-1 rounded-lg bg-surface-container" onClick={() => reviewJustification(item.id, 'rejected').then(reload)}>Rechazar</button>
                    </div>
                  )}
                </div>
              ))}
            </div>
            <div className="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
              <h2 className="px-6 py-4 font-semibold">Historial de la tutoría</h2>
              {(data.history ?? []).length === 0 && <p className="px-6 pb-4 text-outline">No hay marcaciones de tus estudiantes.</p>}
              {(data.history ?? []).map((row) => (
                <div key={row.id} className="px-6 py-3 border-t border-surface-container flex justify-between gap-3">
                  <span>{row.date} · {row.student}</span>
                  <span className="text-primary">{row.status}{row.entry ? ` · ${row.entry}` : ''}{row.exit ? `–${row.exit}` : ''}</span>
                </div>
              ))}
            </div>
          </div>
        )}
      </PageState>
    </DashboardLayout>
  );
}
