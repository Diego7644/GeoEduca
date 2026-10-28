import { useCallback } from 'react';
import DashboardLayout from '../components/DashboardLayout';
import PageState from '../components/PageState';
import useApiResource from '../hooks/useApiResource';
import { getDocente } from '../services/asistenciaService';

export default function DashboardDocente() {
  const load = useCallback(() => getDocente(), []);
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
          </div>
        )}
      </PageState>
    </DashboardLayout>
  );
}
