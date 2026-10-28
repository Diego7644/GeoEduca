import { useEffect, useRef, useState } from 'react';
import DashboardLayout from '../components/DashboardLayout';
import FieldError from '../components/FieldError';
import Icon from '../components/Icon';
import Modal from '../components/Modal';
import SafeImage from '../components/SafeImage';
import Toast, { useToast } from '../components/Toast';
import { IMAGES } from '../config/images';
import useValidatedField from '../hooks/useValidatedField';
import { approveManualEntry } from '../services/asistenciaService';
import { validateStudentId } from '../validation/rules';

const INITIAL_CASES = [
  {
    id: 'sofia', name: 'Sofía Morales Vega', short: 'Sofía Morales', grade: '5to Secundaria - Sección A', gradeShort: '5to Sec A',
    code: 'SEC-5021', attempt: '07:48 AM (Molinete 01)', dni: '74829104', img: IMAGES.sofia,
    bar: 'bg-error', overlayIcon: 'wrong_location', overlayColor: 'text-error',
    badge: { label: 'Falla GPS: Fuera de Radio', box: 'bg-error-container text-on-error-container', dot: 'bg-error' },
    diag: { icon: 'location_off', color: 'text-error', title: 'Anomalía de Geocerca:', text: <>Dispositivo reportó coordenadas a <strong>350m</strong> del perímetro escolar por rebote de celda celular LTE.</> },
    evidence: { icon: 'verified', title: 'Verificación del Auxiliar:', text: 'Alumna físicamente presente en el control. Presenta tarjeta estudiantil física correcta.' },
    secondary: { kind: 'retry', label: 'Reintentar', icon: 'refresh' },
    approve: { label: 'Aprobar Ingreso Manual', icon: 'check_circle', tone: 'secondary', reason: 'Falla GPS (Cobertura Móvil)' },
  },
  {
    id: 'rodrigo', name: 'Rodrigo Mendoza Castro', short: 'Rodrigo Mendoza', grade: '3ro Secundaria - Sección C', gradeShort: '3ro Sec C',
    code: 'SEC-3108', attempt: '07:49 AM (Molinete 02)', dni: '71294812', img: IMAGES.rodrigo, safe: true, safeReason: 'QR Expirado Resuelto',
    bar: 'bg-tertiary', overlayIcon: 'hourglass_disabled', overlayColor: 'text-tertiary',
    badge: { label: 'QR Expirado / TOTP Desync', box: 'bg-tertiary-fixed text-on-tertiary-fixed', dot: 'bg-tertiary' },
    diag: { icon: 'sync_problem', color: 'text-tertiary', title: 'Cripto-token Inválido:', text: 'El código dinámico de 30s caducó durante el paso del torniquete por desfase de reloj local del teléfono.' },
    evidence: { icon: 'contactless', title: 'Alternativa Rápida:', text: 'Se puede regenerar pase temporal en tótem o autorizar bypass de auxiliar en un clic.' },
    secondary: { kind: 'regenerate', label: 'Regenerar en Tótem', icon: 'point_of_sale' },
    approve: { label: 'Aprobar Ingreso Manual', icon: 'check_circle', tone: 'secondary', reason: 'QR Expirado / Re-sync' },
  },
  {
    id: 'valentina', name: 'Valentina Ramos Quispe', short: 'Valentina Ramos', grade: '2do Secundaria - Sección B', gradeShort: '2do Sec B',
    code: 'SEC-2419', attempt: '07:51 AM (Mesa Auxiliar)', dni: '76390145', img: IMAGES.valentina,
    bar: 'bg-error', overlayIcon: 'battery_alert', overlayColor: 'text-error',
    badge: { label: 'Sin Smartphone / Batería 0%', box: 'bg-error-container text-on-error-container', dot: 'bg-error' },
    diag: { icon: 'mobile_friendly', color: 'text-error', title: 'Falta de terminal digital:', text: 'Alumna no cuenta con teléfono disponible hoy. Muestra carnet físico plastificado oficial 2025.' },
    evidence: { select: true },
    approve: { label: 'Aprobar Ingreso por DNI', icon: 'verified_user', tone: 'secondary', reason: null },
  },
  {
    id: 'lucas', name: 'Lucas Fernández Gómez', short: 'Lucas Fernández', grade: '4to Primaria - Sección A', gradeShort: '4to Prim A',
    code: 'PRI-4092', attempt: '07:53 AM (Molinete Familiar)', dni: '89310492', img: IMAGES.lucas, safe: true, safeReason: 'Tardanza Médica Aprobada',
    bar: 'bg-primary', overlayIcon: 'medical_services', overlayColor: 'text-primary',
    badge: { label: 'Tardanza Médica Pre-Justificada', box: 'bg-primary-fixed text-on-primary-fixed', dot: 'bg-primary' },
    diag: { icon: 'receipt_long', color: 'text-primary', title: 'Tutor Adjuntó Receta:', text: 'Constancia pediátrica cargada a las 07:15 AM por la madre (Sra. Patricia Gómez).' },
    evidence: { attachment: 'Certificado_Pediatrico_24Oct.pdf' },
    approve: { label: 'Validar Ingreso con Justificación', icon: 'verified', tone: 'primary', reason: 'Tardanza Justificada Médica' },
  },
];

const CONTINGENCY_OPTIONS = [
  ['Batería Agotada', 'Batería Agotada (Carnet Físico Presentado)'],
  ['Sin Celular', 'Sin Teléfono (Autorizado por Tutor)'],
  ['Extravío', 'Extravío de Credencial Digital'],
];

const INITIAL_AUDIT = [
  { id: 'a1', time: '07:44:12 AM', name: 'Mateo Salazar Peña', grade: '1ro Secundaria B', reason: 'Olvido de Smartphone (Carnet Físico)', dot: 'bg-tertiary', notif: ['sms', 'SMS Enviado'], status: 'Ingresó' },
  { id: 'a2', time: '07:41:05 AM', name: 'Camila Torres Rios', grade: '6to Primaria C', reason: 'Error de Lector RFID Puerta B', dot: 'bg-error', notif: ['notifications_active', 'Push App'], status: 'Ingresó' },
  { id: 'a3', time: '07:38:50 AM', name: 'Joaquín Silva Arce', grade: '4to Secundaria A', reason: 'Pase de Transporte Escolar Demorado', dot: 'bg-primary', notif: ['mark_email_read', 'Email Tutor'], status: 'Ingresó' },
];

const APPROVE_BTN = {
  secondary: 'bg-secondary hover:bg-secondary/90 text-on-secondary',
  primary: 'bg-primary hover:bg-primary-container text-on-primary',
};

const nowTime = () =>
  new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });

const ROLE_ROW = 'px-space-md';

export default function DashboardAuxiliar() {
  const { toast, showToast } = useToast();
  const searchRef = useRef(null);

  const [cases, setCases] = useState(INITIAL_CASES);
  const [leaving, setLeaving] = useState([]);
  const [approvedCount, setApprovedCount] = useState(7);
  const [audit, setAudit] = useState(INITIAL_AUDIT);
  const [contingency, setContingency] = useState(CONTINGENCY_OPTIONS[0][0]);

  const [modalOpen, setModalOpen] = useState(false);
  const studentId = useValidatedField(validateStudentId);
  const [form, setForm] = useState({ reason: 'Falla de Batería / Sin Dispositivo', notes: '', notify: true });

  const safeCount = cases.filter((c) => c.safe).length;

  // Atajo F2: enfoca el buscador / lector físico.
  useEffect(() => {
    const onKey = (e) => {
      if (e.key !== 'F2') return;
      e.preventDefault();
      searchRef.current?.focus();
      searchRef.current?.select();
      showToast('Modo Escaneo Activado', 'Lector óptico listo para capturar código de barras o QR.');
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [showToast]);

  const approve = (kase, reason) => {
    setLeaving((l) => [...l, kase.id]);
    setTimeout(() => {
      setCases((prev) => prev.filter((c) => c.id !== kase.id));
      setLeaving((l) => l.filter((id) => id !== kase.id));
      setApprovedCount((n) => n + 1);
      setAudit((prev) => [
        {
          id: `n${Date.now()}${kase.id}`, time: nowTime(), name: kase.name, grade: kase.gradeShort, reason,
          dot: 'bg-secondary', notif: ['send', 'Push/SMS OK'], status: 'Liberado', fresh: 'secondary',
        },
        ...prev,
      ]);
      showToast('Acceso Autorizado en Molinete 1', `${kase.name} habilitado con motivo: ${reason}`);
    }, 300);
  };

  const resolveAllSafe = () => {
    cases
      .filter((c) => c.safe)
      .forEach((c, i) => setTimeout(() => approve(c, c.safeReason), i * 350));
  };

  const submitForced = async () => {
    studentId.onBlur();
    if (!studentId.isValid) return;

    try {
      await approveManualEntry({
        student_id: studentId.value.trim(),
        reason: form.reason,
      });
    } catch (err) {
      if (err.fieldErrors?.student_id) studentId.setError(err.fieldErrors.student_id);
      showToast(err.message);
      return;
    }

    const student = studentId.value.trim();
    setModalOpen(false);
    studentId.setValue('');
    studentId.setError('');
    setApprovedCount((n) => n + 1);
    setAudit((prev) => [
      {
        id: `f${Date.now()}`, time: nowTime(), name: student, grade: 'Registro Excepcional', reason: form.reason,
        dot: 'bg-primary', notif: ['check', 'Registrado'], status: 'Forzado', fresh: 'primary',
      },
      ...prev,
    ]);
    setForm((f) => ({ ...f, notes: '' }));
    showToast('Registro Forzado Exitoso', `El estudiante ${student} ha sido registrado.`);
  };

  return (
    <DashboardLayout>
      <div className="flex flex-col w-full space-y-space-lg">
        {/* Operational header */}
        <section className="bg-surface-container-lowest rounded-xl p-space-md lg:p-space-lg shadow-sm flex flex-col gap-space-md">
          <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md pb-space-sm border-b border-surface-container">
            <div className="flex flex-col space-y-1">
              <div className="flex items-center gap-space-xs text-primary">
                <Icon name="shield_person" filled className="text-[20px]" />
                <span className="font-label-sm text-label-sm uppercase tracking-wider text-outline font-semibold">Consola Operativa en Vivo</span>
                <span className="inline-block w-1.5 h-1.5 rounded-full bg-secondary" />
                <span className="font-label-sm text-label-sm text-secondary font-semibold">Conexión Puerta Activa</span>
              </div>
              <h1 className="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight">
                Monitor Operativo de Acceso &amp; Contingencias
              </h1>
              <div className="flex flex-wrap items-center gap-space-sm pt-0.5 text-on-surface-variant font-body-sm text-body-sm">
                <span className="flex items-center gap-1 font-semibold text-primary">
                  <Icon name="meeting_room" className="text-[18px]" />
                  Puerta Principal Nº 1 y Molinetes Peatonales
                </span>
                <span className="text-outline">•</span>
                <span>Operador en Turno: <strong className="text-on-surface font-semibold">Aux. Ricardo Paz (ID-4820)</strong></span>
                <span className="text-outline">•</span>
                <span className="font-mono text-outline">Lote Sincronizado: #GT-2025-1024</span>
              </div>
            </div>

            <div className="flex items-center gap-space-sm self-start lg:self-auto">
              <button
                type="button"
                onClick={() => {
                  searchRef.current?.focus();
                  showToast('Modo Escaneo Activado', 'Lector óptico listo para capturar código de barras o QR.');
                }}
                className="flex items-center gap-space-xs bg-surface-container-high hover:bg-surface-container-highest text-primary font-label-md text-label-md px-3.5 py-2.5 rounded-lg transition-colors shadow-sm"
              >
                <Icon name="barcode_scanner" className="text-[18px]" />
                <span>Escanear Lector F2</span>
                <kbd className="bg-surface-container-lowest text-outline px-1.5 py-0.5 rounded font-mono text-[10px]">F2</kbd>
              </button>
              <button
                type="button"
                onClick={() => setModalOpen(true)}
                className="flex items-center gap-space-xs bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md px-4 py-2.5 rounded-lg transition-all shadow-sm"
              >
                <Icon name="add_moderator" className="text-[18px]" />
                <span>Registro Forzado DNI</span>
              </button>
            </div>
          </div>

          {/* Metrics */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-space-md">
            <div className="bg-surface-container-low rounded-xl p-space-md flex flex-col justify-between">
              <div className="flex items-center justify-between">
                <span className="font-label-md text-label-md text-outline uppercase tracking-wider">Flujo Total Ingresados</span>
                <span className="p-1.5 bg-secondary-container/40 text-on-secondary-container rounded-lg">
                  <Icon name="how_to_reg" className="text-[20px]" />
                </span>
              </div>
              <div className="my-space-xs">
                <div className="flex items-baseline gap-1.5">
                  <span className="font-metric-display text-metric-display text-on-surface font-bold">842</span>
                  <span className="font-body-md text-body-md text-outline">/ 920</span>
                </div>
                <div className="w-full bg-surface-container-highest h-2 rounded-full mt-2 overflow-hidden">
                  <div className="bg-secondary h-full rounded-full transition-all duration-700" style={{ width: '91.5%' }} />
                </div>
              </div>
              <div className="flex items-center justify-between text-on-secondary-container font-label-sm text-label-sm font-semibold pt-1">
                <span>91.5% del aforo proyectado</span>
                <span className="text-outline font-normal">78 restantes</span>
              </div>
            </div>

            <div className="bg-error-container/25 rounded-xl p-space-md flex flex-col justify-between">
              <div className="flex items-center justify-between">
                <span className="font-label-md text-label-md text-error uppercase tracking-wider font-semibold">Alertas Críticas Puerta</span>
                <span className="p-1.5 bg-error text-on-error rounded-lg animate-pulse">
                  <Icon name="warning" className="text-[20px]" />
                </span>
              </div>
              <div className="my-space-xs">
                <div className="flex items-baseline gap-2">
                  <span className="font-metric-display text-metric-display text-error font-bold">{cases.length}</span>
                  <span className="font-body-sm text-body-sm text-on-surface-variant font-medium">requieren resolución</span>
                </div>
                <p className="font-body-sm text-body-sm text-error/90 mt-1 line-clamp-1">Fallas GPS, token expirado y carnet físico</p>
              </div>
              <div className="flex items-center gap-1 font-label-sm text-label-sm text-error font-semibold">
                <span className="relative flex h-2 w-2">
                  <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-error opacity-75" />
                  <span className="relative inline-flex rounded-full h-2 w-2 bg-error" />
                </span>
                <span>Atención prioritaria en fila</span>
              </div>
            </div>

            <div className="bg-surface-container-low rounded-xl p-space-md flex flex-col justify-between">
              <div className="flex items-center justify-between">
                <span className="font-label-md text-label-md text-outline uppercase tracking-wider">Ingresos Manuales</span>
                <span className="p-1.5 bg-surface-container-highest text-primary rounded-lg">
                  <Icon name="assignment_turned_in" className="text-[20px]" />
                </span>
              </div>
              <div className="my-space-xs">
                <div className="flex items-baseline gap-2">
                  <span className="font-metric-display text-metric-display text-primary font-bold">{approvedCount}</span>
                  <span className="font-body-sm text-body-sm text-outline">consignados</span>
                </div>
                <p className="font-body-sm text-body-sm text-on-surface-variant mt-1">Con firma digital y respaldo en bitácora</p>
              </div>
              <div className="font-label-sm text-label-sm text-outline flex items-center gap-1">
                <Icon name="lock_clock" className="text-[14px]" />
                <span>Auditoría abierta para supervisor</span>
              </div>
            </div>

            <div className="bg-surface-container-low rounded-xl p-space-md flex flex-col justify-between">
              <div className="flex items-center justify-between">
                <span className="font-label-md text-label-md text-outline uppercase tracking-wider">Hardware Molinetes</span>
                <span className="p-1.5 bg-secondary-container/40 text-on-secondary-container rounded-lg">
                  <Icon name="sensors" className="text-[20px]" />
                </span>
              </div>
              <div className="my-space-xs flex flex-col gap-1.5">
                {['Molinete A (QR Óptico)', 'Molinete B (RFID/NFC)'].map((label) => (
                  <div key={label} className="flex items-center justify-between font-body-sm text-body-sm">
                    <span className="text-on-surface font-medium">{label}</span>
                    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-secondary-container/40 text-on-secondary-container font-label-sm text-label-sm font-semibold">
                      <span className="w-1.5 h-1.5 rounded-full bg-secondary" />OK
                    </span>
                  </div>
                ))}
              </div>
              <div className="font-label-sm text-label-sm text-outline">Latencia de sincronización: 42ms</div>
            </div>
          </div>

          {/* Search */}
          <div className="relative w-full pt-space-xs">
            <div className="relative flex items-center w-full">
              <div className="absolute left-4 flex items-center pointer-events-none text-primary">
                <Icon name="qr_code_scanner" className="text-[24px]" />
              </div>
              <input
                ref={searchRef}
                type="text"
                placeholder="Buscar estudiante por DNI, Apellidos, Código de Estudiante o Aula... (Presione F2 para pistola lectora física)"
                className="w-full bg-surface-container-low focus:bg-surface-container-lowest text-on-surface placeholder:text-outline font-body-lg text-body-lg pl-14 pr-32 py-3.5 rounded-xl shadow-inner focus:outline-none focus:ring-2 focus:ring-primary transition-all"
              />
              <div className="absolute right-3 flex items-center gap-2">
                <span className="hidden md:inline-flex items-center px-2 py-1 rounded bg-surface-container-highest font-mono text-[11px] font-semibold text-outline">
                  AUTO-SCAN ON
                </span>
                <button type="button" className="bg-primary hover:bg-primary-container text-on-primary p-2 rounded-lg flex items-center justify-center transition-colors">
                  <Icon name="search" className="text-[20px]" />
                </button>
              </div>
            </div>
          </div>
        </section>

        {/* Case queue */}
        <section className="flex flex-col space-y-space-md">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-space-xs">
              <Icon name="notification_important" className="text-error text-[22px]" />
              <h2 className="font-headline-md text-headline-md text-on-surface font-bold tracking-tight">
                Casos en Cola de Revisión Presencial
              </h2>
              <span className="bg-error text-on-error font-label-sm text-label-sm px-2 py-0.5 rounded-full font-bold">
                {cases.length} Activos
              </span>
            </div>
            <button
              type="button"
              onClick={resolveAllSafe}
              disabled={safeCount === 0}
              className="text-primary hover:text-primary-container disabled:opacity-40 font-label-md text-label-md font-semibold flex items-center gap-1 transition-colors px-2 py-1"
            >
              <Icon name="checklist_rtl" className="text-[18px]" />
              <span>Aprobar Casos Sin Riesgo ({safeCount})</span>
            </button>
          </div>

          {cases.length === 0 && (
            <div className="bg-surface-container-lowest rounded-xl p-space-lg text-center text-outline shadow-sm">
              <Icon name="task_alt" className="text-secondary text-[32px]" />
              <p className="font-title-lg text-body-md font-semibold text-on-surface mt-1">Cola despejada</p>
              <p className="font-body-sm text-body-sm">No hay casos pendientes de revisión presencial.</p>
            </div>
          )}

          <div className="grid grid-cols-1 md:grid-cols-2 gap-space-md">
            {cases.map((c) => (
              <article
                key={c.id}
                className={`bg-surface-container-lowest rounded-xl p-space-md lg:p-space-lg shadow-sm flex flex-col justify-between hover:shadow-md relative overflow-hidden transition-all duration-300 ${
                  leaving.includes(c.id) ? 'scale-95 opacity-40 pointer-events-none' : ''
                }`}
              >
                <div className={`absolute top-0 left-0 bottom-0 w-1.5 ${c.bar}`} />
                <div className="flex flex-col space-y-space-md pl-2">
                  <div className="flex items-start justify-between gap-space-sm">
                    <div className="flex items-center gap-space-md">
                      <div className="relative">
                        <SafeImage alt={c.name} className="w-16 h-16 rounded-xl object-cover shadow-sm bg-surface-container" src={c.img} />
                        <span className="absolute -bottom-1 -right-1 bg-surface-container-lowest rounded-full p-0.5 shadow">
                          <Icon name={c.overlayIcon} className={`${c.overlayColor} text-[18px]`} />
                        </span>
                      </div>
                      <div className="flex flex-col">
                        <span className="font-headline-md text-title-lg text-on-surface font-bold leading-tight">{c.name}</span>
                        <span className="font-label-md text-label-md text-outline">
                          {c.grade} • Código: <strong className="text-on-surface font-mono">{c.code}</strong>
                        </span>
                        <span className="font-label-sm text-label-sm text-outline mt-0.5 flex items-center gap-1 font-mono">
                          <Icon name="schedule" className="text-[14px]" /> Intento: {c.attempt}
                        </span>
                      </div>
                    </div>
                    <span className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full font-label-md text-label-md font-semibold shrink-0 ${c.badge.box}`}>
                      <span className={`w-2 h-2 rounded-full ${c.badge.dot}`} />
                      {c.badge.label}
                    </span>
                  </div>

                  <div className="bg-surface-container-low rounded-lg p-space-sm space-y-space-xs text-body-sm font-body-sm">
                    <div className="flex items-start gap-2 text-on-surface-variant">
                      <Icon name={c.diag.icon} className={`${c.diag.color} text-[18px] shrink-0 mt-0.5`} />
                      <p>
                        <strong className={`${c.diag.color} font-semibold`}>{c.diag.title}</strong> {c.diag.text}
                      </p>
                    </div>

                    {c.evidence.select && (
                      <div className="flex items-center gap-2 pt-1">
                        <label className="font-label-sm text-label-sm text-outline font-semibold" htmlFor={`contingency-${c.id}`}>
                          Motivo de Registro:
                        </label>
                        <select
                          id={`contingency-${c.id}`}
                          value={contingency}
                          onChange={(e) => setContingency(e.target.value)}
                          className="bg-surface-container-lowest text-on-surface font-body-sm text-body-sm rounded-lg px-2.5 py-1 focus:ring-1 focus:ring-primary"
                        >
                          {CONTINGENCY_OPTIONS.map(([value, label]) => (
                            <option key={value} value={value}>{label}</option>
                          ))}
                        </select>
                      </div>
                    )}

                    {c.evidence.attachment && (
                      <div className="flex items-center justify-between bg-surface-container-lowest/80 p-2 rounded">
                        <span className="flex items-center gap-1 text-outline font-label-sm text-label-sm">
                          <Icon name="attachment" className="text-[16px] text-secondary" /> {c.evidence.attachment}
                        </span>
                        <button
                          type="button"
                          onClick={() => showToast('Constancia médica adjunta', 'Dr. Andrés Morales - CMP 49102')}
                          className="text-primary hover:underline font-label-sm text-label-sm font-semibold"
                        >
                          Ver Documento
                        </button>
                      </div>
                    )}

                    {c.evidence.title && (
                      <div className="flex items-start gap-2 text-on-surface bg-surface-container-lowest/80 p-2 rounded">
                        <Icon name={c.evidence.icon} className="text-primary text-[18px] shrink-0 mt-0.5" />
                        <p className="font-medium text-body-sm">
                          <strong>{c.evidence.title}</strong> {c.evidence.text}
                        </p>
                      </div>
                    )}
                  </div>

                  <div className="flex flex-col sm:flex-row items-center justify-between gap-space-sm pt-space-xs">
                    <span className="font-label-sm text-label-sm text-outline font-mono">DNI: {c.dni}</span>
                    <div className="flex items-center gap-space-xs w-full sm:w-auto">
                      {c.secondary && (
                        <button
                          type="button"
                          onClick={() =>
                            c.secondary.kind === 'retry'
                              ? showToast('Re-escaneo Solicitado', `Enviando comando de lectura rápida para ${c.short}...`)
                              : showToast('Token Reexpedido', `Código temporal enviado a pantalla de Tótem 1 para ${c.short}`)
                          }
                          className="flex-1 sm:flex-none px-3 py-2 rounded-lg bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-label-md text-label-md font-medium transition-colors flex items-center justify-center gap-1"
                        >
                          <Icon name={c.secondary.icon} className="text-[16px]" />
                          <span>{c.secondary.label}</span>
                        </button>
                      )}
                      <button
                        type="button"
                        onClick={() => approve(c, c.approve.reason ?? contingency)}
                        className={`${c.secondary ? 'flex-1 sm:flex-none' : 'w-full sm:w-auto'} px-4 py-2 rounded-lg font-label-md text-label-md font-semibold transition-all shadow-sm flex items-center justify-center gap-1.5 ${APPROVE_BTN[c.approve.tone]}`}
                      >
                        <Icon name={c.approve.icon} className="text-[18px]" />
                        <span>{c.approve.label}</span>
                      </button>
                    </div>
                  </div>
                </div>
              </article>
            ))}
          </div>
        </section>

        {/* Audit log */}
        <section className="bg-surface-container-lowest rounded-xl p-space-md lg:p-space-lg shadow-sm flex flex-col space-y-space-md">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm">
            <div className="flex items-center gap-space-xs">
              <Icon name="history_edu" className="text-primary text-[22px]" />
              <h3 className="font-title-lg text-title-lg text-on-surface font-bold">
                Historial Rápido de Aprobaciones del Auxiliar (Auditoría en Tiempo Real)
              </h3>
            </div>
            <div className="flex items-center gap-2">
              <span className="inline-flex items-center gap-1 text-outline font-body-sm text-body-sm">
                <Icon name="lock" className="text-[16px] text-secondary" />
                Sesión Certificada por Dirección
              </span>
              <button
                type="button"
                onClick={() => showToast('Exportación Iniciada', 'Descargando reporte de contingencias de puerta en formato PDF/XLSX...')}
                className="px-3 py-1.5 rounded-lg bg-surface-container-high hover:bg-surface-container-highest text-primary font-label-md text-label-md font-semibold flex items-center gap-1 transition-colors"
              >
                <Icon name="download" className="text-[16px]" />
                <span>Exportar Acta</span>
              </button>
            </div>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left font-body-md text-body-md border-collapse">
              <thead>
                <tr className="bg-surface-container-low text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wider h-10">
                  <th className={`${ROLE_ROW} rounded-l-lg`}>Hora</th>
                  <th className={ROLE_ROW}>Estudiante</th>
                  <th className={ROLE_ROW}>Grado / Aula</th>
                  <th className={ROLE_ROW}>Motivo Registrado</th>
                  <th className={ROLE_ROW}>Auxiliar Responsable</th>
                  <th className={ROLE_ROW}>Notif. Tutor</th>
                  <th className={`${ROLE_ROW} text-right rounded-r-lg`}>Estado</th>
                </tr>
              </thead>
              <tbody className="text-on-surface divide-y divide-surface-container">
                {audit.map((row) => {
                  const forced = row.fresh === 'primary';
                  return (
                    <tr
                      key={row.id}
                      className={`h-12 hover:bg-surface-container-low/60 transition-colors ${
                        row.fresh === 'secondary' ? 'bg-secondary-container/10' : forced ? 'bg-primary-container/10' : ''
                      }`}
                    >
                      <td className={`${ROLE_ROW} font-mono text-body-sm font-semibold whitespace-nowrap ${row.fresh ? 'text-primary font-bold' : 'text-outline'}`}>
                        {row.time}
                      </td>
                      <td className={`${ROLE_ROW} font-semibold text-on-surface`}>{row.name}</td>
                      <td className={`${ROLE_ROW} text-outline`}>{row.grade}</td>
                      <td className={ROLE_ROW}>
                        <span className="inline-flex items-center gap-1 font-body-sm text-body-sm text-on-surface">
                          <span className={`w-1.5 h-1.5 rounded-full ${row.dot}`} />
                          {row.reason}
                        </span>
                      </td>
                      <td className={`${ROLE_ROW} font-body-sm text-body-sm text-primary font-medium`}>Aux. Ricardo Paz</td>
                      <td className={ROLE_ROW}>
                        <span className="inline-flex items-center gap-1 text-secondary font-label-sm text-label-sm font-semibold">
                          <Icon name={row.notif[0]} className="text-[14px]" /> {row.notif[1]}
                        </span>
                      </td>
                      <td className={`${ROLE_ROW} text-right`}>
                        <span
                          className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-label-sm text-label-sm font-semibold ${
                            forced ? 'bg-primary-fixed text-on-primary-fixed' : 'bg-secondary-container/40 text-on-secondary-container'
                          }`}
                        >
                          <span className={`w-1.5 h-1.5 rounded-full ${forced ? 'bg-primary' : 'bg-secondary'}`} />
                          {row.status}
                        </span>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </section>
      </div>

      {/* Forced registration modal */}
      <Modal open={modalOpen} onClose={() => setModalOpen(false)}>
        <div className="p-space-lg flex flex-col space-y-space-md">
          <div className="flex items-center justify-between pb-space-sm border-b border-surface-container">
            <div className="flex items-center gap-2 text-primary font-title-lg text-title-lg font-bold">
              <Icon name="how_to_reg" />
              <span>Registro Forzado de Ingreso por DNI</span>
            </div>
            <button type="button" onClick={() => setModalOpen(false)} className="p-1 rounded-lg text-outline hover:text-on-surface hover:bg-surface-container">
              <Icon name="close" className="text-[20px]" />
            </button>
          </div>

          <div className="space-y-space-sm font-body-sm text-body-sm">
            <div>
              <label className="block font-label-sm text-label-sm text-outline font-semibold mb-1" htmlFor="forced-student">
                DNI o Código de Matrícula:
              </label>
              <input
                id="forced-student"
                type="text"
                value={studentId.value}
                onChange={studentId.onChange}
                onBlur={studentId.onBlur}
                aria-invalid={!!studentId.error}
                placeholder="Ej: 78945612 o SEC-5040"
                className={`w-full bg-surface-container-low px-3 py-2 rounded-lg text-on-surface focus:outline-none font-mono ${
                  studentId.error ? 'ring-2 ring-error' : 'focus:ring-2 focus:ring-primary'
                }`}
              />
              <FieldError id="forced-student-error" message={studentId.error} />
            </div>
            <div>
              <label className="block font-label-sm text-label-sm text-outline font-semibold mb-1">Motivo de Contingencia:</label>
              <select
                value={form.reason}
                onChange={(e) => setForm({ ...form, reason: e.target.value })}
                className="w-full bg-surface-container-low px-3 py-2 rounded-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option>Falla de Batería / Sin Dispositivo</option>
                <option>Pérdida de Carnet Escolar</option>
                <option>Cita Médica / Justificación Externa</option>
                <option>Error Técnico de Molinete Biométrico</option>
              </select>
            </div>
            <div>
              <label className="block font-label-sm text-label-sm text-outline font-semibold mb-1">Notas de Observación del Auxiliar:</label>
              <textarea
                rows={2}
                value={form.notes}
                onChange={(e) => setForm({ ...form, notes: e.target.value })}
                placeholder="Escriba detalle físico o número de comprobante..."
                className="w-full bg-surface-container-low p-2 rounded-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary"
              />
            </div>
            <div className="flex items-center gap-2 pt-1">
              <input
                id="notifyParentCheck"
                type="checkbox"
                checked={form.notify}
                onChange={(e) => setForm({ ...form, notify: e.target.checked })}
                className="w-4 h-4 accent-primary rounded"
              />
              <label className="text-on-surface font-body-sm text-body-sm" htmlFor="notifyParentCheck">
                Enviar notificación instantánea al tutor por WhatsApp/SMS
              </label>
            </div>
          </div>

          <div className="flex items-center justify-end gap-space-sm pt-space-xs">
            <button type="button" onClick={() => setModalOpen(false)} className="px-4 py-2 rounded-lg text-outline hover:bg-surface-container font-label-md text-label-md">
              Cancelar
            </button>
            <button
              type="button"
              onClick={submitForced}
              disabled={!studentId.isValid}
              className="px-5 py-2 rounded-lg bg-primary hover:bg-primary-container disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-primary text-on-primary font-label-md text-label-md font-semibold shadow"
            >
              Confirmar y Liberar Molinete
            </button>
          </div>
        </div>
      </Modal>

      <Toast toast={toast} />
    </DashboardLayout>
  );
}
