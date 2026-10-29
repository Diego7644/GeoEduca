import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import DashboardLayout from '../components/DashboardLayout';
import FieldError from '../components/FieldError';
import Icon from '../components/Icon';
import Modal from '../components/Modal';
import PageState from '../components/PageState';
import SafeImage from '../components/SafeImage';
import Toast, { useToast } from '../components/Toast';
import { IMAGES } from '../config/images';
import useApiResource from '../hooks/useApiResource';
import useValidatedField from '../hooks/useValidatedField';
import QrCamera from '../components/QrCamera';
import { approveManualEntry, closeDay, getAuxiliar, getJustificaciones, markExit, reviewJustification, validateQr } from '../services/asistenciaService';
import { downloadCsv } from '../utils/attendance';
import { validateStudentId } from '../validation/rules';

const CONTINGENCY_OPTIONS = [
  ['Batería Agotada', 'Batería agotada (carnet físico)'],
  ['Sin Celular', 'Sin teléfono (autorizado por tutor)'],
  ['Extravío', 'Extravío de credencial digital'],
];
const APPROVE_BTN = {
  secondary: 'bg-secondary hover:bg-secondary/90 text-on-secondary',
  primary: 'bg-primary hover:bg-primary-container text-on-primary',
};

export default function DashboardAuxiliar() {
  const load = useCallback(() => getAuxiliar(), []);
  const { data, setData, error, loading, reload } = useApiResource(load);
  const { toast, showToast } = useToast();
  const searchRef = useRef(null);
  const [query, setQuery] = useState('');
  const [leaving, setLeaving] = useState([]);
  const [reasons, setReasons] = useState({});
  const [modalOpen, setModalOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [qrValue, setQrValue] = useState('');
  const [exitQuery, setExitQuery] = useState('');
  const [justifications, setJustifications] = useState([]);
  const studentId = useValidatedField(validateStudentId);
  const [form, setForm] = useState({ reason: 'Falla de Batería / Sin Dispositivo', notes: '', notify: true });

  useEffect(() => {
    if (!data) return undefined;
    let active = true;
    getJustificaciones().then((result) => {
      if (active) setJustifications(result.justifications ?? []);
    }).catch(() => {
      if (active) setJustifications([]);
    });
    return () => {
      active = false;
    };
  }, [data]);

  useEffect(() => {
    const onKey = (event) => {
      if (event.key !== 'F2') return;
      event.preventDefault();
      searchRef.current?.focus();
      searchRef.current?.select();
      showToast('Modo escaneo', 'El lector puede pegar un DNI o código en el buscador.');
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [showToast]);

  const cases = data?.cases ?? [];
  const needle = query.trim().toLowerCase();
  const visibleCases = useMemo(() => {
    if (needle.length < 2) return cases;
    return cases.filter((item) => [item.name, item.dni, item.code, item.grade].join(' ').toLowerCase().includes(needle));
  }, [cases, needle]);
  const hits = useMemo(() => {
    if (needle.length < 2) return [];
    return (data?.directory ?? []).filter((item) => [item.name, item.dni, item.code, item.grade].join(' ').toLowerCase().includes(needle)).slice(0, 6);
  }, [data, needle]);
  const safeCount = visibleCases.filter((item) => item.safe).length;

  const approve = async (kase, reason) => {
    setLeaving((ids) => [...ids, kase.id]);
    try {
      const result = await approveManualEntry({ student_id: kase.dni, reason, case_id: kase.id, notify: true });
      await new Promise((resolve) => setTimeout(resolve, 250));
      setData(result.panel);
      showToast('Acceso autorizado', `${kase.name}: ${reason}`);
    } catch (err) {
      showToast(err.message);
    } finally {
      setLeaving((ids) => ids.filter((id) => id !== kase.id));
    }
  };

  const resolveAllSafe = async () => {
    const pending = cases.filter((item) => item.safe);
    if (!pending.length) return;
    try {
      let panel = null;
      for (const item of pending) {
        setLeaving((ids) => [...ids, item.id]);
        const result = await approveManualEntry({
          student_id: item.dni,
          reason: item.safeReason,
          case_id: item.id,
          notify: true,
        });
        panel = result.panel;
      }
      setData(panel);
      showToast('Casos sin riesgo aprobados', `${pending.length} ingresos liberados.`);
    } catch (err) {
      showToast(err.message);
      reload();
    } finally {
      setLeaving([]);
    }
  };

  const openForced = (dni = '') => {
    studentId.setValue(dni);
    studentId.setError('');
    setModalOpen(true);
  };

  const submitForced = async () => {
    studentId.onBlur();
    if (!studentId.isValid || saving) return;
    setSaving(true);
    try {
      const result = await approveManualEntry({
        student_id: studentId.value.trim(),
        reason: form.reason,
        notes: form.notes,
        notify: form.notify,
      });
      setData(result.panel);
      setModalOpen(false);
      studentId.setValue('');
      setForm((current) => ({ ...current, notes: '' }));
      showToast(result.message, 'El ingreso quedó en la bitácora.');
    } catch (err) {
      if (err.fieldErrors?.student_id) studentId.setError(err.fieldErrors.student_id);
      showToast(err.message);
    } finally {
      setSaving(false);
    }
  };

  const exportActa = () => {
    if (!data) return;
    downloadCsv(`Acta_${data.syncLot}.csv`, ['Hora', 'Estudiante', 'Aula', 'Motivo', 'Auxiliar', 'Aviso', 'Estado'], data.audit.map((row) => [
      row.time, row.name, row.grade, row.reason, row.operator, row.notif[1], row.status,
    ]));
    showToast('Acta exportada', 'El CSV contiene la bitácora visible.');
  };

  return (
    <DashboardLayout>
      <PageState loading={loading} error={error} onRetry={reload}>
        {data && (
          <div className="flex flex-col w-full space-y-space-lg">
            <section className="bg-surface-container-lowest rounded-xl p-space-md lg:p-space-lg shadow-sm flex flex-col gap-space-md">
              <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md pb-space-sm border-b border-surface-container">
                <div>
                  <div className="flex items-center gap-2 text-secondary font-label-sm font-semibold">
                    <Icon name="shield_person" filled /> Conexión de puerta activa
                  </div>
                  <h1 className="font-headline-lg text-headline-lg font-bold">Monitor operativo de acceso y contingencias</h1>
                  <p className="font-body-sm text-on-surface-variant">
                    Operador: <strong>{data.operator.name}</strong> ({data.operator.code}) · Lote {data.syncLot}
                  </p>
                </div>
                <div className="flex gap-2">
                  <button type="button" onClick={() => { searchRef.current?.focus(); showToast('Modo escaneo', 'Escriba o pegue un DNI.'); }} className="px-3 py-2 rounded-lg bg-surface-container-high text-primary font-semibold">Escanear F2</button>
                  <button type="button" onClick={() => openForced()} className="px-4 py-2 rounded-lg bg-primary text-on-primary font-semibold">Registro forzado DNI</button>
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-space-md">
                <div className="bg-surface-container-low rounded-xl p-space-md">
                  <span className="font-label-sm uppercase text-outline">Ingresados</span>
                  <div className="text-headline-md font-bold">{data.metrics.entered}<span className="text-body-md text-outline font-normal"> / {data.metrics.expected}</span></div>
                  <div className="h-2 bg-surface-container-highest rounded-full mt-2"><div className="h-full bg-secondary rounded-full" style={{ width: `${data.metrics.percent}%` }} /></div>
                  <p className="font-label-sm mt-1">{data.metrics.percent}% · {data.metrics.remaining} restantes</p>
                </div>
                <div className="bg-error-container/25 rounded-xl p-space-md">
                  <span className="font-label-sm uppercase text-error font-semibold">Alertas en puerta</span>
                  <div className="text-headline-md font-bold text-error">{data.metrics.critical}</div>
                  <p className="font-body-sm">Casos que aún piden resolución</p>
                </div>
                <div className="bg-surface-container-low rounded-xl p-space-md">
                  <span className="font-label-sm uppercase text-outline">Ingresos manuales</span>
                  <div className="text-headline-md font-bold text-primary">{data.metrics.manual}</div>
                  <p className="font-body-sm">Quedan en la bitácora de esta sesión</p>
                </div>
                <div className="bg-surface-container-low rounded-xl p-space-md">
                  <span className="font-label-sm uppercase text-outline">Lectores</span>
                  <p className="font-body-sm mt-1">{data.reader || 'No hay lectores físicos conectados.'}</p>
                </div>
              </div>

              <QrCamera
                onCode={async (value) => {
                  try {
                    const result = await validateQr(value);
                    setData(result.panel);
                    showToast(result.message);
                  } catch (err) {
                    showToast(err.message);
                  }
                }}
              />
              <form
                className="mb-4 flex flex-col sm:flex-row gap-2"
                onSubmit={async (event) => {
                  event.preventDefault();
                  try {
                    const result = await validateQr(qrValue.trim());
                    setData(result.panel);
                    setQrValue('');
                    showToast(result.message);
                  } catch (err) {
                    showToast(err.message);
                  }
                }}
              >
                <input
                  value={qrValue}
                  onChange={(event) => setQrValue(event.target.value)}
                  placeholder="Código QR del estudiante"
                  className="flex-1 bg-surface-container-low rounded-lg px-3 py-2 font-mono"
                  aria-label="Código QR del estudiante"
                />
                <button type="submit" className="px-4 py-2 rounded-lg bg-primary text-on-primary font-semibold">Validar QR</button>
              </form>
              <form
                className="mb-4 flex flex-col sm:flex-row gap-2"
                onSubmit={async (event) => {
                  event.preventDefault();
                  try {
                    const result = await markExit(exitQuery.trim());
                    setData(result.panel);
                    setExitQuery('');
                    showToast(result.message);
                  } catch (err) {
                    showToast(err.message);
                  }
                }}
              >
                <input value={exitQuery} onChange={(event) => setExitQuery(event.target.value)} placeholder="DNI o código para registrar salida" className="flex-1 bg-surface-container-low rounded-lg px-3 py-2 font-mono" aria-label="Estudiante para salida" />
                <button type="submit" className="px-4 py-2 rounded-lg bg-secondary text-on-secondary font-semibold">Registrar salida</button>
                <button
                  type="button"
                  className="px-4 py-2 rounded-lg bg-surface-container font-semibold"
                  onClick={async () => {
                    try {
                      const result = await closeDay();
                      showToast(result.message);
                      reload();
                    } catch (err) {
                      showToast(err.message);
                    }
                  }}
                >
                  Cerrar jornada
                </button>
              </form>
              <div className="relative">
                <Icon name="qr_code_scanner" className="absolute left-4 top-3.5 text-primary" />
                <input
                  ref={searchRef}
                  value={query}
                  onChange={(event) => setQuery(event.target.value)}
                  placeholder="Buscar por DNI, código, apellido o aula"
                  className="w-full bg-surface-container-low rounded-xl pl-14 pr-4 py-3.5"
                />
                {needle.length >= 2 && (
                  <div className="absolute z-20 mt-2 w-full rounded-xl bg-surface-container-lowest shadow-xl overflow-hidden">
                    {hits.length === 0 && <p className="px-4 py-3 text-outline">Sin coincidencias en la matrícula.</p>}
                    {hits.map((item) => {
                      const queued = cases.some((kase) => kase.dni === item.dni);
                      return (
                        <button key={item.id} type="button" onClick={() => (queued ? setQuery(item.dni) : openForced(item.dni))} className="w-full px-4 py-3 text-left hover:bg-surface-container-low flex justify-between gap-3">
                          <span><strong>{item.name}</strong><span className="block text-outline font-body-sm">{item.grade} · {item.code} · {item.dni}</span></span>
                          <span className="font-label-sm text-primary">{queued ? 'Ver en cola' : 'Registrar'}</span>
                        </button>
                      );
                    })}
                  </div>
                )}
              </div>
            </section>

            <section className="flex flex-col gap-space-md">
              <div className="flex items-center justify-between">
                <h2 className="font-headline-md font-bold">Cola presencial <span className="bg-error text-on-error text-label-sm px-2 py-0.5 rounded-full">{visibleCases.length}</span></h2>
                <button type="button" disabled={safeCount === 0} onClick={resolveAllSafe} className="text-primary font-semibold disabled:opacity-40">Aprobar casos sin riesgo ({safeCount})</button>
              </div>
              {visibleCases.length === 0 && <div className="bg-surface-container-lowest rounded-xl p-8 text-center text-outline">No hay casos pendientes con este criterio.</div>}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-space-md">
                {visibleCases.map((item) => (
                  <article key={item.id} className={`bg-surface-container-lowest rounded-xl p-space-md shadow-sm relative overflow-hidden ${leaving.includes(item.id) ? 'opacity-40 scale-95' : ''}`}>
                    <div className={`absolute top-0 left-0 bottom-0 w-1.5 ${item.bar}`} />
                    <div className="pl-2 flex flex-col gap-3">
                      <div className="flex justify-between gap-3">
                        <div className="flex gap-3">
                          <SafeImage alt={item.name} className="w-16 h-16 rounded-xl object-cover" src={item.img ? IMAGES[item.img] : null} />
                          <div>
                            <h3 className="font-bold">{item.name}</h3>
                            <p className="font-label-md text-outline">{item.grade} · {item.code}</p>
                            <p className="font-label-sm text-outline">Intento: {item.attempt}</p>
                          </div>
                        </div>
                        <span className={`h-fit px-3 py-1 rounded-full font-label-sm font-semibold ${item.badge.box}`}>{item.badge.label}</span>
                      </div>
                      <div className="bg-surface-container-low rounded-lg p-3 text-body-sm">
                        <p><strong className={item.diag.color}>{item.diag.title}</strong> {item.diag.text}</p>
                        {item.evidence?.select && (
                          <label className="mt-2 flex items-center gap-2">Motivo
                            <select value={reasons[item.id] || CONTINGENCY_OPTIONS[0][0]} onChange={(event) => setReasons((current) => ({ ...current, [item.id]: event.target.value }))} className="bg-surface-container-lowest rounded-lg px-2 py-1">
                              {CONTINGENCY_OPTIONS.map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                            </select>
                          </label>
                        )}
                        {item.evidence?.attachment && (
                          <button type="button" className="mt-2 text-primary font-semibold" onClick={() => showToast(item.evidence.attachment, item.evidence.attachmentNote)}>{item.evidence.attachment}</button>
                        )}
                        {item.evidence?.title && <p className="mt-2"><strong>{item.evidence.title}</strong> {item.evidence.text}</p>}
                      </div>
                      <div className="flex items-center justify-between gap-2">
                        <span className="font-mono text-outline">DNI {item.dni}</span>
                        <div className="flex gap-2">
                          {item.secondary && (
                            <button type="button" onClick={() => showToast(item.secondary.kind === 'retry' ? 'Relectura pedida' : 'Pase enviado al tótem', item.short)} className="px-3 py-2 rounded-lg bg-surface-container-high">{item.secondary.label}</button>
                          )}
                          <button type="button" onClick={() => approve(item, item.approve.reason ?? (reasons[item.id] || CONTINGENCY_OPTIONS[0][0]))} className={`px-4 py-2 rounded-lg font-semibold ${APPROVE_BTN[item.approve.tone]}`}>{item.approve.label}</button>
                        </div>
                      </div>
                    </div>
                  </article>
                ))}
              </div>
            </section>

            <section className="bg-surface-container-lowest rounded-xl p-space-md shadow-sm">
              <h3 className="font-bold mb-3">Incidencias del día</h3>
              {(data.incidents ?? []).length === 0 && <p className="text-outline">No hay códigos vencidos, reutilizados o inválidos hoy.</p>}
              {(data.incidents ?? []).map((item) => (
                <p key={item.id} className="py-2 border-t border-surface-container font-body-sm">{item.time} · {item.result} · {item.student || 'sin estudiante'} · {item.detail}</p>
              ))}
            </section>
            <section className="bg-surface-container-lowest rounded-xl p-space-md shadow-sm">
              <h3 className="font-bold mb-3">Justificaciones</h3>
              {justifications.length === 0 && <p className="text-outline">No hay solicitudes.</p>}
              {justifications.map((item) => (
                <div key={item.id} className="py-3 border-t border-surface-container flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                  <div>
                    <p className="font-semibold">{item.student} · {item.event_date}</p>
                    <p className="font-body-sm text-on-surface-variant">{item.reason}</p>
                    <p className="font-label-sm uppercase text-primary">{item.status}</p>
                  </div>
                  {item.status === 'submitted' && (
                    <div className="flex gap-2">
                      <button type="button" className="px-3 py-1 rounded-lg bg-secondary text-on-secondary" onClick={() => reviewJustification(item.id, 'approved').then(() => reload())}>Aprobar</button>
                      <button type="button" className="px-3 py-1 rounded-lg bg-surface-container" onClick={() => reviewJustification(item.id, 'rejected').then(() => reload())}>Rechazar</button>
                    </div>
                  )}
                </div>
              ))}
            </section>
            <section className="bg-surface-container-lowest rounded-xl p-space-md shadow-sm">
              <div className="flex items-center justify-between mb-3">
                <h3 className="font-bold">Bitácora del auxiliar</h3>
                <button type="button" onClick={exportActa} className="px-3 py-1.5 rounded-lg bg-surface-container-high text-primary font-semibold">Exportar acta</button>
              </div>
              <div className="overflow-x-auto">
                <table className="w-full text-left text-body-sm">
                  <thead>
                    <tr className="bg-surface-container-low text-outline uppercase font-label-sm">
                      {['Hora', 'Estudiante', 'Aula', 'Motivo', 'Auxiliar', 'Aviso', 'Estado'].map((label) => <th key={label} className="px-3 py-2">{label}</th>)}
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-surface-container">
                    {data.audit.map((row) => (
                      <tr key={row.id} className={row.fresh === 'secondary' ? 'bg-secondary-container/10' : ''}>
                        <td className="px-3 py-2 font-mono">{row.time}</td>
                        <td className="px-3 py-2 font-semibold">{row.name}</td>
                        <td className="px-3 py-2">{row.grade}</td>
                        <td className="px-3 py-2">{row.reason}</td>
                        <td className="px-3 py-2 text-primary">{row.operator}</td>
                        <td className="px-3 py-2">{row.notif?.[1]}</td>
                        <td className="px-3 py-2">{row.status}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </section>
          </div>
        )}
      </PageState>

      <Modal open={modalOpen} onClose={() => setModalOpen(false)}>
        <div className="p-space-lg flex flex-col gap-3">
          <div className="flex justify-between">
            <h3 className="font-bold text-primary">Registro forzado por DNI</h3>
            <button type="button" onClick={() => setModalOpen(false)}><Icon name="close" /></button>
          </div>
          <label className="font-label-sm text-outline">DNI o código
            <input value={studentId.value} onChange={studentId.onChange} onBlur={studentId.onBlur} placeholder="78945612 o SEC-5040" className="mt-1 w-full bg-surface-container-low px-3 py-2 rounded-lg font-mono text-on-surface" />
            <FieldError message={studentId.error} />
          </label>
          <label className="font-label-sm text-outline">Motivo
            <select value={form.reason} onChange={(event) => setForm({ ...form, reason: event.target.value })} className="mt-1 w-full bg-surface-container-low px-3 py-2 rounded-lg text-on-surface">
              <option>Falla de Batería / Sin Dispositivo</option>
              <option>Pérdida de Carnet Escolar</option>
              <option>Cita Médica / Justificación Externa</option>
              <option>Error Técnico de Molinete</option>
            </select>
          </label>
          <label className="font-label-sm text-outline">Notas
            <textarea rows={2} value={form.notes} onChange={(event) => setForm({ ...form, notes: event.target.value })} className="mt-1 w-full bg-surface-container-low p-2 rounded-lg text-on-surface" />
          </label>
          <label className="flex items-center gap-2 text-body-sm">
            <input type="checkbox" checked={form.notify} onChange={(event) => setForm({ ...form, notify: event.target.checked })} />
            Avisar al tutor
          </label>
          <div className="flex justify-end gap-2">
            <button type="button" onClick={() => setModalOpen(false)} className="px-4 py-2">Cancelar</button>
            <button type="button" disabled={!studentId.isValid || saving} onClick={submitForced} className="px-4 py-2 rounded-lg bg-primary text-on-primary disabled:opacity-50">{saving ? 'Guardando...' : 'Confirmar ingreso'}</button>
          </div>
        </div>
      </Modal>
      <Toast toast={toast} />
    </DashboardLayout>
  );
}
