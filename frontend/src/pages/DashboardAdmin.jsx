import { useCallback, useEffect, useState } from 'react';
import DashboardLayout from '../components/DashboardLayout';
import PageState from '../components/PageState';
import Toast, { useToast } from '../components/Toast';
import useApiResource from '../hooks/useApiResource';
import { createAccount, createStudent, getActividad, getDirectorio, linkGuardian, renameAccount, resetPassword, unlinkGuardian, updateStudent } from '../services/asistenciaService';

export default function DashboardAdmin() {
  const load = useCallback(() => getDirectorio(), []);
  const { data, setData, error, loading, reload } = useApiResource(load);

  useEffect(() => {
    if (!data) return undefined;
    let active = true;
    getActividad().then((result) => {
      if (active) setLogs(result.logs ?? []);
    }).catch(() => {
      if (active) setLogs([]);
    });
    return () => {
      active = false;
    };
  }, [data]);
  const { toast, showToast } = useToast();
  const [guardianDni, setGuardianDni] = useState('');
  const [studentDni, setStudentDni] = useState('');
  const [saving, setSaving] = useState(false);
  const [studentForm, setStudentForm] = useState({ dni: '', code: '', name: '', grade: '', password: '' });
  const [accountForm, setAccountForm] = useState({ dni: '', name: '', role: 'parent', staff_kind: 'auxiliar', password: '' });
  const [resetForm, setResetForm] = useState({ userId: '', password: '' });
  const [editForm, setEditForm] = useState(null);
  const [renameForm, setRenameForm] = useState({ userId: '', name: '' });
  const [logs, setLogs] = useState([]);

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
            <form
              className="bg-surface-container-lowest rounded-xl p-6 shadow-sm grid sm:grid-cols-2 gap-3"
              onSubmit={async (event) => {
                event.preventDefault();
                setSaving(true);
                try {
                  const result = await createStudent(studentForm);
                  setData(result.directory);
                  showToast(result.message);
                } catch (err) {
                  showToast(err.message);
                } finally {
                  setSaving(false);
                }
              }}
            >
              <h2 className="sm:col-span-2 font-semibold">Nuevo estudiante</h2>
              {['dni', 'code', 'name', 'grade', 'password'].map((key) => (
                <input key={key} required={key !== 'password'} value={studentForm[key]} placeholder={key === 'password' ? 'Contraseña si tendrá cuenta' : key} onChange={(event) => setStudentForm((current) => ({ ...current, [key]: event.target.value }))} className="bg-surface-container-low rounded-lg px-3 py-2" />
              ))}
              <button type="submit" disabled={saving} className="px-4 py-2 rounded-lg bg-primary text-on-primary font-semibold">Registrar estudiante</button>
            </form>
            <form
              className="bg-surface-container-lowest rounded-xl p-6 shadow-sm grid sm:grid-cols-2 gap-3"
              onSubmit={async (event) => {
                event.preventDefault();
                setSaving(true);
                try {
                  const result = await createAccount(accountForm);
                  setData(result.directory);
                  showToast(result.message);
                } catch (err) {
                  showToast(err.message);
                } finally {
                  setSaving(false);
                }
              }}
            >
              <h2 className="sm:col-span-2 font-semibold">Nuevo apoderado o personal</h2>
              <input required value={accountForm.dni} placeholder="DNI" onChange={(event) => setAccountForm((current) => ({ ...current, dni: event.target.value }))} className="bg-surface-container-low rounded-lg px-3 py-2" />
              <input required value={accountForm.name} placeholder="Nombre" onChange={(event) => setAccountForm((current) => ({ ...current, name: event.target.value }))} className="bg-surface-container-low rounded-lg px-3 py-2" />
              <select value={accountForm.role} onChange={(event) => setAccountForm((current) => ({ ...current, role: event.target.value }))} className="bg-surface-container-low rounded-lg px-3 py-2">
                <option value="parent">Apoderado</option>
                <option value="staff">Personal</option>
              </select>
              {accountForm.role === 'staff' && (
                <select value={accountForm.staff_kind} onChange={(event) => setAccountForm((current) => ({ ...current, staff_kind: event.target.value }))} className="bg-surface-container-low rounded-lg px-3 py-2">
                  <option value="auxiliar">Auxiliar</option>
                  <option value="docente">Docente</option>
                </select>
              )}
              <input required type="password" value={accountForm.password} placeholder="Contraseña inicial" onChange={(event) => setAccountForm((current) => ({ ...current, password: event.target.value }))} className="bg-surface-container-low rounded-lg px-3 py-2" />
              <button type="submit" disabled={saving} className="px-4 py-2 rounded-lg bg-primary text-on-primary font-semibold">Registrar cuenta</button>
            </form>
            <form
              className="bg-surface-container-lowest rounded-xl p-6 shadow-sm flex flex-col sm:flex-row gap-3"
              onSubmit={async (event) => {
                event.preventDefault();
                try {
                  const result = await resetPassword(resetForm.userId, resetForm.password);
                  showToast(result.message);
                } catch (err) {
                  showToast(err.message);
                }
              }}
            >
              <input required value={resetForm.userId} placeholder="Id de la cuenta" onChange={(event) => setResetForm((current) => ({ ...current, userId: event.target.value }))} className="bg-surface-container-low rounded-lg px-3 py-2" />
              <input required type="password" value={resetForm.password} placeholder="Nueva contraseña" onChange={(event) => setResetForm((current) => ({ ...current, password: event.target.value }))} className="bg-surface-container-low rounded-lg px-3 py-2" />
              <button type="submit" className="px-4 py-2 rounded-lg bg-surface-container text-on-surface font-semibold">Restablecer clave</button>
            </form>
            <form onSubmit={link} className="bg-surface-container-lowest rounded-xl p-6 shadow-sm flex flex-col sm:flex-row gap-3">
              <input value={guardianDni} onChange={(e) => setGuardianDni(e.target.value.replace(/\D/g, '').slice(0, 8))} placeholder="DNI del apoderado" className="bg-surface-container-low rounded-lg px-3 py-2" />
              <input value={studentDni} onChange={(e) => setStudentDni(e.target.value.replace(/\D/g, '').slice(0, 8))} placeholder="DNI del estudiante" className="bg-surface-container-low rounded-lg px-3 py-2" />
              <button type="submit" disabled={saving} className="px-4 py-2 rounded-lg bg-primary text-on-primary font-semibold disabled:opacity-50">Vincular</button>
              <button type="button" className="px-4 py-2 rounded-lg bg-surface-container font-semibold" onClick={async () => {
                try {
                  const result = await unlinkGuardian({ guardian_dni: guardianDni, student_dni: studentDni });
                  setData(result.directory);
                  showToast(result.message);
                } catch (err) {
                  showToast(err.message);
                }
              }}>Desvincular</button>
            </form>
            <div className="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
              {data.students.map((student) => (
                <div key={student.id} className="px-6 py-4 border-b border-surface-container">
                  <div className="flex justify-between gap-3">
                    <p className="font-semibold text-on-surface">{student.name} · {student.dni}</p>
                    <button type="button" className="text-primary font-semibold" onClick={() => setEditForm({ id: student.id, name: student.name, grade: student.grade, code: student.code })}>Editar</button>
                  </div>
                  <p className="font-body-sm text-on-surface-variant">
                    Cuenta {student.user_id ?? 'sin usuario'} · Apoderados: {student.guardians.map((item) => item.name).join(', ') || 'ninguno'}
                  </p>
                  {editForm?.id === student.id && (
                    <form className="mt-3 grid sm:grid-cols-4 gap-2" onSubmit={async (event) => {
                      event.preventDefault();
                      try {
                        const result = await updateStudent(editForm.id, { name: editForm.name, grade: editForm.grade, code: editForm.code });
                        setData(result.directory);
                        setEditForm(null);
                        showToast(result.message);
                      } catch (err) {
                        showToast(err.message);
                      }
                    }}>
                      <input value={editForm.name} onChange={(event) => setEditForm((current) => ({ ...current, name: event.target.value }))} className="bg-surface-container-low rounded-lg px-3 py-2" />
                      <input value={editForm.grade} onChange={(event) => setEditForm((current) => ({ ...current, grade: event.target.value }))} className="bg-surface-container-low rounded-lg px-3 py-2" />
                      <input value={editForm.code} onChange={(event) => setEditForm((current) => ({ ...current, code: event.target.value }))} className="bg-surface-container-low rounded-lg px-3 py-2" />
                      <button type="submit" className="px-3 py-2 rounded-lg bg-primary text-on-primary font-semibold">Guardar</button>
                    </form>
                  )}
                </div>
              ))}
            </div>
            <div className="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
              {data.staff.map((member) => (
                <div key={member.id} className="px-6 py-3 border-b border-surface-container flex justify-between gap-3">
                  <span className="text-on-surface">{member.name}</span>
                  <span className="font-label-sm uppercase text-primary">{member.kind}</span>
                  {member.user_id && (
                    <button type="button" className="text-primary font-semibold" onClick={() => setRenameForm({ userId: String(member.user_id), name: member.name })}>Editar nombre</button>
                  )}
                </div>
              ))}
            </div>
            <form className="bg-surface-container-lowest rounded-xl p-6 shadow-sm flex flex-col sm:flex-row gap-3" onSubmit={async (event) => {
              event.preventDefault();
              try {
                const result = await renameAccount(renameForm.userId, renameForm.name);
                setData(result.directory);
                showToast(result.message);
              } catch (err) {
                showToast(err.message);
              }
            }}>
              <input required value={renameForm.userId} placeholder="Id de la cuenta" onChange={(event) => setRenameForm((current) => ({ ...current, userId: event.target.value }))} className="bg-surface-container-low rounded-lg px-3 py-2" />
              <input required value={renameForm.name} placeholder="Nombre" onChange={(event) => setRenameForm((current) => ({ ...current, name: event.target.value }))} className="bg-surface-container-low rounded-lg px-3 py-2" />
              <button type="submit" className="px-4 py-2 rounded-lg bg-primary text-on-primary font-semibold">Guardar nombre</button>
            </form>
            <div className="bg-surface-container-lowest rounded-xl p-6 shadow-sm">
              <h2 className="font-semibold mb-3">Auditoría reciente</h2>
              {logs.length === 0 && <p className="text-outline">No hay movimientos registrados.</p>}
              {logs.slice(0, 8).map((log) => (
                <p key={log.id} className="font-body-sm py-1 border-t border-surface-container">{log.action} · {log.reason}</p>
              ))}
            </div>
          </div>
        )}
      </PageState>
      <Toast toast={toast} />
    </DashboardLayout>
  );
}
