import { useCallback, useState } from 'react';
import DashboardLayout from '../components/DashboardLayout';
import PageState from '../components/PageState';
import Toast, { useToast } from '../components/Toast';
import useApiResource from '../hooks/useApiResource';
import { getDirectorio, linkGuardian } from '../services/asistenciaService';

export default function DashboardAdmin() {
  const load = useCallback(() => getDirectorio(), []);
  const { data, setData, error, loading, reload } = useApiResource(load);
  const { toast, showToast } = useToast();
  const [guardianDni, setGuardianDni] = useState('');
  const [studentDni, setStudentDni] = useState('');
  const [saving, setSaving] = useState(false);

  const link = async (event) => {
    event.preventDefault();
    setSaving(true);
    try {
      const result = await linkGuardian({ guardian_dni: guardianDni, student_dni: studentDni });
      setData(result.directory);
      showToast('Vínculo guardado');
    } catch (err) {
      showToast(err.message);
    } finally {
      setSaving(false);
    }
  };

  return (
    <DashboardLayout>
      <PageState loading={loading} error={error} onRetry={reload}>
        {data && (
          <div className="flex flex-col gap-space-lg">
            <div>
              <span className="font-label-sm uppercase tracking-wider text-outline">Administración</span>
              <h1 className="font-headline-lg font-bold text-on-surface">Personas y vínculos</h1>
              <p className="text-on-surface-variant">El id de la cuenta no es el id del estudiante. El vínculo es explícito.</p>
            </div>
            <form onSubmit={link} className="bg-surface-container-lowest rounded-xl p-6 shadow-sm flex flex-col sm:flex-row gap-3">
              <input value={guardianDni} onChange={(e) => setGuardianDni(e.target.value.replace(/\D/g, '').slice(0, 8))} placeholder="DNI del apoderado" className="bg-surface-container-low rounded-lg px-3 py-2" />
              <input value={studentDni} onChange={(e) => setStudentDni(e.target.value.replace(/\D/g, '').slice(0, 8))} placeholder="DNI del estudiante" className="bg-surface-container-low rounded-lg px-3 py-2" />
              <button type="submit" disabled={saving} className="px-4 py-2 rounded-lg bg-primary text-on-primary font-semibold disabled:opacity-50">Vincular</button>
            </form>
            <div className="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
              {data.students.map((student) => (
                <div key={student.id} className="px-6 py-4 border-b border-surface-container">
                  <p className="font-semibold text-on-surface">{student.name} · {student.dni}</p>
                  <p className="font-body-sm text-on-surface-variant">
                    Cuenta {student.user_id ?? 'sin usuario'} · Apoderados: {student.guardians.map((item) => item.name).join(', ') || 'ninguno'}
                  </p>
                </div>
              ))}
            </div>
            <div className="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
              {data.staff.map((member) => (
                <div key={member.id} className="px-6 py-3 border-b border-surface-container flex justify-between">
                  <span className="text-on-surface">{member.name}</span>
                  <span className="font-label-sm uppercase text-primary">{member.kind}</span>
                </div>
              ))}
            </div>
          </div>
        )}
      </PageState>
      <Toast toast={toast} />
    </DashboardLayout>
  );
}
