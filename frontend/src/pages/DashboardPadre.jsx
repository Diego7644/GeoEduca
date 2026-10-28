import { useEffect, useState } from 'react';
import DashboardLayout from '../components/DashboardLayout';
import FieldError from '../components/FieldError';
import Icon from '../components/Icon';
import Modal from '../components/Modal';
import SafeImage from '../components/SafeImage';
import Toast, { useToast } from '../components/Toast';
import { IMAGES } from '../config/images';
import useValidatedField from '../hooks/useValidatedField';
import { submitJustification as sendJustification } from '../services/asistenciaService';
import { validateEventDate, validateJustificationReason } from '../validation/rules';

const CHILDREN = [
  { name: 'Mateo Valenzuela', grade: '4to Secundaria B • Turno Mañana', short: '4to Sec B', avatar: IMAGES.childMateo, listAvatar: IMAGES.childMateoList },
  { name: 'Lucía Valenzuela', grade: '1ro Secundaria A • Turno Mañana', short: '1ro Sec A', avatar: null, listAvatar: null },
];

const ATTENDANCE = [
  {
    id: 1, date: '24 Oct', sub: 'Hoy • Viernes', time: '07:44 AM', gate: 'Puerta Peatonal 2', gateIcon: 'door_sliding',
    method: 'QR Dinámico', methodIcon: 'qr_code_scanner', exit: '02:30 PM', status: 'puntual', today: true,
    voucher: ['24-OCT-2025', '07:44 AM', 'Puerta Peatonal 2', 'QR Móvil Seguro', '-12.0862, -77.0543'],
  },
  {
    id: 2, date: '23 Oct', sub: 'Ayer • Jueves', time: '07:40 AM', gate: 'Puerta Principal 1', gateIcon: 'meeting_room',
    method: 'QR Dinámico', methodIcon: 'qr_code_scanner', exit: '02:32 PM (Marcada)', status: 'puntual',
    voucher: ['23-OCT-2025', '07:40 AM', 'Puerta Principal 1', 'QR Móvil Seguro', '-12.0860, -77.0541'],
  },
  {
    id: 3, date: '22 Oct', sub: 'Miércoles', time: '07:51 AM', gate: 'Puerta Peatonal 2', gateIcon: 'door_sliding',
    method: 'Carnet NFC', methodIcon: 'contactless', exit: '02:30 PM (Marcada)', status: 'puntual',
    voucher: ['22-OCT-2025', '07:51 AM', 'Puerta Peatonal 2', 'Carnet NFC Físico', '-12.0863, -77.0544'],
  },
  {
    id: 4, date: '21 Oct', sub: 'Martes', time: '08:12 AM', gate: 'Puerta Principal 1', gateIcon: 'meeting_room',
    method: 'Ingreso Asistido', methodIcon: 'face', assisted: true, exit: '02:30 PM (Marcada)', status: 'tardanza',
    minutes: 12, justified: true,
    voucher: ['21-OCT-2025', '08:12 AM', 'Puerta Principal 1', 'Reconocimiento Auxiliar', '-12.0861, -77.0540'],
  },
  {
    id: 5, date: '20 Oct', sub: 'Lunes', time: '07:45 AM', gate: 'Puerta Peatonal 2', gateIcon: 'door_sliding',
    method: 'QR Dinámico', methodIcon: 'qr_code_scanner', exit: '02:30 PM (Marcada)', status: 'puntual',
    voucher: ['20-OCT-2025', '07:45 AM', 'Puerta Peatonal 2', 'QR Móvil Seguro', '-12.0862, -77.0543'],
  },
];

const FILTERS = [
  { key: 'all', label: 'Todos (42)' },
  { key: 'puntual', label: 'Puntual (39)' },
  { key: 'tardanza', label: 'Tardanza (2)' },
  { key: 'justificada', label: 'Justificada (1)' },
];

const matchesFilter = (row, filter) =>
  filter === 'all' || row.status === filter || (filter === 'justificada' && row.justified);

export default function DashboardPadre() {
  const { toast, showToast } = useToast();

  const [child, setChild] = useState(CHILDREN[0]);
  const [menuOpen, setMenuOpen] = useState(false);
  const [filter, setFilter] = useState('all');
  const [range, setRange] = useState('currentMonth');
  const [liveAlerts, setLiveAlerts] = useState(true);
  const [voucher, setVoucher] = useState(null);
  const [justifyOpen, setJustifyOpen] = useState(false);
  const [requestType, setRequestType] = useState('medical');
  const [eventTime, setEventTime] = useState('11:30');
  const eventDate = useValidatedField(validateEventDate, '2025-10-24');
  const reason = useValidatedField(validateJustificationReason);

  // Cierra el selector de hijos al hacer clic fuera.
  useEffect(() => {
    if (!menuOpen) return undefined;
    const close = () => setMenuOpen(false);
    document.addEventListener('click', close);
    return () => document.removeEventListener('click', close);
  }, [menuOpen]);

  const rows = ATTENDANCE.filter((r) => matchesFilter(r, filter));

  const openVoucher = (row) => {
    const [date, time, gate, method, gps] = row.voucher;
    setVoucher({ date, time, gate, method, gps });
  };

  const selectChild = (c) => {
    setChild(c);
    setMenuOpen(false);
    showToast(`Mostrando registro de asistencia de ${c.name}`);
  };

  const downloadReport = (format) => {
    showToast(`Generando reporte institucional en formato ${format.toUpperCase()}...`);
    setTimeout(
      () =>
        showToast(
          `Descarga completada: Asistencia_${child.name.replace(' ', '')}_Oct2025.${format === 'excel' ? 'xlsx' : 'pdf'}`
        ),
      1200
    );
  };

  const printVoucher = () => {
    showToast('Enviando comprobante digital a la impresora escolar...');
    setTimeout(() => setVoucher(null), 1000);
  };

  const submitJustification = async (e) => {
    e.preventDefault();
    eventDate.onBlur();
    reason.onBlur();
    if (!eventDate.isValid || !reason.isValid) return;

    try {
      const result = await sendJustification({
        type: requestType,
        event_date: eventDate.value,
        event_time: eventTime || undefined,
        reason: reason.value.trim(),
      });
      setJustifyOpen(false);
      showToast(result.message || 'Solicitud enviada a Coordinación Académica.');
    } catch (err) {
      if (err.fieldErrors?.reason) reason.setError(err.fieldErrors.reason);
      if (err.fieldErrors?.event_date) eventDate.setError(err.fieldErrors.event_date);
      showToast(err.message);
    }
  };

  const toggleAlerts = (e) => {
    setLiveAlerts(e.target.checked);
    showToast(
      e.target.checked ? 'Notificaciones Push y WhatsApp activadas' : 'Notificaciones en vivo silenciadas temporalmente'
    );
  };

  return (
    <DashboardLayout>
      <div className="flex flex-col w-full">
        {/* Header & child switcher */}
        <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md mb-space-lg">
          <div className="flex flex-col sm:flex-row items-start sm:items-center gap-space-md">
            <div className="relative inline-block text-left">
              <button
                type="button"
                aria-expanded={menuOpen}
                onClick={(e) => {
                  e.stopPropagation();
                  setMenuOpen((v) => !v);
                }}
                className="flex items-center gap-3 bg-surface-container-lowest hover:bg-surface-container-low px-4 py-2.5 rounded-xl shadow-sm transition-colors text-left"
              >
                <div className="relative">
                  <SafeImage alt={child.name} className="w-11 h-11 rounded-full object-cover shadow-sm" src={child.avatar} />
                  <span className="absolute bottom-0 right-0 w-3 h-3 bg-secondary rounded-full ring-2 ring-surface-container-lowest" />
                </div>
                <div className="flex flex-col">
                  <div className="flex items-center gap-1.5">
                    <span className="font-title-lg text-title-lg text-on-surface font-semibold">{child.name}</span>
                    <Icon name="expand_more" className="text-[18px] text-outline" />
                  </div>
                  <span className="font-label-sm text-label-sm text-outline">{child.grade}</span>
                </div>
              </button>

              {menuOpen && (
                <div className="absolute left-0 mt-2 w-72 rounded-xl bg-surface-container-lowest shadow-xl z-20 py-2">
                  <div className="px-3 py-1.5 text-outline font-label-sm text-label-sm uppercase tracking-wider">
                    Hijos Matriculados
                  </div>
                  {CHILDREN.map((c) => {
                    const selected = c.name === child.name;
                    return (
                      <button
                        key={c.name}
                        type="button"
                        onClick={() => selectChild(c)}
                        className={`w-full flex items-center gap-3 px-3 py-2 text-left transition-colors ${
                          selected ? 'bg-surface-container-high/60' : 'hover:bg-surface-container-low'
                        }`}
                      >
                        <SafeImage alt={c.name} className="w-8 h-8 rounded-full object-cover" src={c.listAvatar} />
                        <div className="flex-1 min-w-0">
                          <p className="font-title-lg text-body-sm font-semibold text-on-surface truncate">{c.name}</p>
                          <p className="font-label-sm text-label-sm text-secondary font-medium">{c.short} • En el Colegio</p>
                        </div>
                        {selected && <Icon name="check" className="text-secondary text-[20px]" />}
                      </button>
                    );
                  })}
                  <div className="mt-2 pt-2 px-3">
                    <a className="text-primary hover:underline font-label-md text-label-md flex items-center gap-1" href="#">
                      <Icon name="add_circle" className="text-[16px]" />
                      Vincular otro estudiante
                    </a>
                  </div>
                </div>
              )}
            </div>

            <div className="hidden sm:flex items-center gap-2">
              <div className="bg-surface-container-low px-3 py-2 rounded-lg flex items-center gap-2">
                <Icon name="verified" className="text-[18px] text-secondary" />
                <div className="flex flex-col">
                  <span className="font-label-sm text-label-sm text-outline">Asistencia Mensual</span>
                  <span className="font-title-lg text-body-sm font-bold text-on-surface">97.8% (Puntual)</span>
                </div>
              </div>
              <div className="bg-surface-container-low px-3 py-2 rounded-lg flex items-center gap-2">
                <Icon name="schedule" className="text-[18px] text-tertiary-container" />
                <div className="flex flex-col">
                  <span className="font-label-sm text-label-sm text-outline">Tardanzas acumuladas</span>
                  <span className="font-title-lg text-body-sm font-bold text-on-surface">1 este mes</span>
                </div>
              </div>
            </div>
          </div>

          <div className="bg-surface-container-lowest px-4 py-3 rounded-xl shadow-sm flex items-center justify-between sm:justify-start gap-4">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-full bg-secondary-container/40 flex items-center justify-center text-on-secondary-container">
                <Icon name="domain_verification" className="text-[24px]" />
              </div>
              <div>
                <div className="flex items-center gap-2">
                  <span className="font-label-sm text-label-sm uppercase tracking-wide text-secondary font-bold">
                    Estado de Hoy (24 Oct)
                  </span>
                  <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-secondary-container/50 text-on-secondary-container font-label-sm text-label-sm font-semibold">
                    <span className="w-1.5 h-1.5 rounded-full bg-secondary animate-pulse" /> PRESENTE
                  </span>
                </div>
                <p className="font-body-sm text-body-sm text-on-surface-variant font-medium mt-0.5">
                  Ingreso verificado <span className="font-semibold text-on-surface">07:44 AM</span> por{' '}
                  <span className="text-primary font-semibold">Puerta 2</span>
                </p>
              </div>
            </div>
            <button
              type="button"
              onClick={() => openVoucher(ATTENDANCE[0])}
              title="Ver ubicación y telemetría de marcación"
              className="hidden md:flex items-center gap-1.5 px-3 py-1.5 bg-surface-container hover:bg-surface-container-high rounded-lg text-primary font-label-md text-label-md transition-colors"
            >
              <Icon name="location_on" className="text-[18px]" />
              <span>Ver GPS</span>
            </button>
          </div>
        </div>

        {/* Main grid */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-space-lg items-start">
          {/* LEFT: attendance data */}
          <div className="lg:col-span-8 flex flex-col gap-space-md min-w-0">
            <div className="bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-space-md">
              <div className="flex flex-wrap items-center gap-2">
                <div className="relative">
                  <select
                    value={range}
                    onChange={(e) => setRange(e.target.value)}
                    className="bg-surface-container-low text-on-surface font-label-md text-label-md py-2 pl-3 pr-8 rounded-lg appearance-none cursor-pointer focus:outline-none focus:bg-surface-container-high"
                  >
                    <option value="30days">Últimos 30 días</option>
                    <option value="currentMonth">Mes Actual (Octubre)</option>
                    <option value="trimester">3er Trimestre 2025</option>
                    <option value="year">Año Lectivo 2025</option>
                  </select>
                  <Icon name="expand_more" className="pointer-events-none absolute right-2 top-2.5 text-[18px] text-outline" />
                </div>

                <div className="flex items-center bg-surface-container-low p-1 rounded-lg gap-1 overflow-x-auto">
                  {FILTERS.map((f) => (
                    <button
                      key={f.key}
                      type="button"
                      onClick={() => setFilter(f.key)}
                      className={`px-3 py-1 rounded font-label-sm text-label-sm whitespace-nowrap transition-all ${
                        filter === f.key
                          ? 'text-on-primary bg-primary font-semibold'
                          : 'text-on-surface-variant hover:text-on-surface font-medium'
                      }`}
                    >
                      {f.label}
                    </button>
                  ))}
                </div>
              </div>

              <div className="flex items-center gap-2 self-end md:self-auto">
                <button
                  type="button"
                  onClick={() => downloadReport('excel')}
                  title="Descargar planilla XLS"
                  className="flex items-center gap-1.5 px-3 py-2 bg-surface-container-low hover:bg-surface-container text-on-surface rounded-lg font-label-md text-label-md font-semibold transition-colors"
                >
                  <Icon name="table_view" className="text-[18px] text-secondary" />
                  <span className="hidden sm:inline">Excel</span>
                </button>
                <button
                  type="button"
                  onClick={() => downloadReport('pdf')}
                  title="Descargar comprobante oficial PDF"
                  className="flex items-center gap-1.5 px-3 py-2 bg-primary-container text-on-primary hover:bg-primary rounded-lg font-label-md text-label-md font-semibold shadow-sm transition-colors"
                >
                  <Icon name="picture_as_pdf" className="text-[18px]" />
                  <span>Descargar Reporte</span>
                </button>
              </div>
            </div>

            {/* Table */}
            <div className="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
              <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse">
                  <thead>
                    <tr className="bg-surface-container-low/70 h-10 text-outline font-label-sm text-label-sm uppercase tracking-wider select-none">
                      <th className="px-4 py-2">Fecha y Día</th>
                      <th className="px-4 py-2">Hora Ingreso</th>
                      <th className="px-4 py-2">Punto de Acceso</th>
                      <th className="px-4 py-2">Método de Marcación</th>
                      <th className="px-4 py-2">Salida Estimada</th>
                      <th className="px-4 py-2">Estado</th>
                      <th className="px-4 py-2 text-right">Comprobante</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-surface-container-low font-body-md text-body-sm text-on-surface">
                    {rows.map((r) => {
                      const late = r.status === 'tardanza';
                      return (
                        <tr
                          key={r.id}
                          className={`hover:bg-surface-container-low/50 transition-colors h-12 ${
                            r.today ? 'bg-secondary-container/10' : late ? 'bg-tertiary-fixed/20' : ''
                          }`}
                        >
                          <td className="px-4 py-2 font-medium">
                            <div className="flex items-center gap-2">
                              {r.today && <span className="w-2 h-2 rounded-full bg-secondary" />}
                              <div>
                                <span className="font-semibold text-on-surface">{r.date}</span>
                                <span className="text-outline text-label-sm block">{r.sub}</span>
                              </div>
                            </div>
                          </td>
                          <td className={`px-4 py-2 ${r.today ? 'font-bold text-secondary' : late ? 'font-bold text-tertiary' : 'font-medium'}`}>
                            {r.time}
                          </td>
                          <td className="px-4 py-2 text-on-surface-variant">
                            <div className="flex items-center gap-1.5">
                              <Icon name={r.gateIcon} className="text-[16px] text-outline" />
                              <span>{r.gate}</span>
                            </div>
                          </td>
                          <td className="px-4 py-2">
                            <span
                              className={`inline-flex items-center gap-1 px-2 py-0.5 rounded bg-surface-container-high font-label-sm text-label-sm ${
                                r.assisted ? 'text-outline' : 'text-primary'
                              }`}
                            >
                              <Icon name={r.methodIcon} className="text-[14px]" />
                              {r.method}
                            </span>
                          </td>
                          <td className="px-4 py-2 text-on-surface-variant whitespace-nowrap">{r.exit}</td>
                          <td className="px-4 py-2">
                            {late ? (
                              <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-label-sm text-label-sm font-semibold whitespace-nowrap">
                                <span className="w-1.5 h-1.5 rounded-full bg-tertiary" /> Tardanza ({r.minutes} min)
                              </span>
                            ) : (
                              <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-secondary-container/40 text-on-secondary-container font-label-sm text-label-sm font-semibold">
                                <span className="w-1.5 h-1.5 rounded-full bg-secondary" /> Puntual
                              </span>
                            )}
                          </td>
                          <td className="px-4 py-2">
                            <div className="flex items-center justify-end gap-1">
                              {late && (
                                <button
                                  type="button"
                                  onClick={() => showToast('Visualizando documento médico #MED-994 adjunto por tutor el 21 Oct')}
                                  title="Justificación Aprobada"
                                  className="px-2 py-1 bg-surface-container text-primary rounded text-label-sm font-semibold hover:bg-surface-container-high"
                                >
                                  Adjunto
                                </button>
                              )}
                              <button
                                type="button"
                                onClick={() => openVoucher(r)}
                                title="Ver Comprobante Geo-localizado"
                                className="p-1.5 text-primary hover:bg-surface-container rounded-lg transition-colors"
                              >
                                <Icon name="verified_user" className="text-[20px]" />
                              </button>
                            </div>
                          </td>
                        </tr>
                      );
                    })}
                    {rows.length === 0 && (
                      <tr>
                        <td colSpan={7} className="px-4 py-8 text-center text-outline">
                          Sin marcaciones para este filtro.
                        </td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>

              <div className="px-4 py-3 bg-surface-container-low/40 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div className="font-body-sm text-body-sm text-outline">
                  Mostrando <span className="font-semibold text-on-surface">1 a {rows.length}</span> de{' '}
                  <span className="font-semibold text-on-surface">42</span> marcaciones registradas
                </div>
                <div className="flex items-center gap-1">
                  <button type="button" disabled className="p-1.5 rounded-lg text-outline hover:bg-surface-container disabled:opacity-40">
                    <Icon name="chevron_left" className="text-[20px]" />
                  </button>
                  <button type="button" className="w-8 h-8 rounded-lg bg-primary text-on-primary font-label-md text-label-md font-semibold">1</button>
                  {[2, 3].map((n) => (
                    <button key={n} type="button" className="w-8 h-8 rounded-lg hover:bg-surface-container text-on-surface font-label-md text-label-md font-semibold">
                      {n}
                    </button>
                  ))}
                  <span className="px-1 text-outline font-label-md">...</span>
                  <button type="button" className="w-8 h-8 rounded-lg hover:bg-surface-container text-on-surface font-label-md text-label-md font-semibold">9</button>
                  <button type="button" className="p-1.5 rounded-lg text-on-surface hover:bg-surface-container">
                    <Icon name="chevron_right" className="text-[20px]" />
                  </button>
                </div>
              </div>
            </div>

            {/* Compliance card */}
            <div className="bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-space-md">
              <div className="flex items-center gap-space-md">
                <div className="relative w-16 h-16 shrink-0">
                  <svg className="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                    <path className="text-surface-container-highest" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" strokeWidth="3.5" />
                    <path className="text-secondary" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" strokeDasharray="95, 100" strokeLinecap="round" strokeWidth="3.5" />
                  </svg>
                  <div className="absolute inset-0 flex items-center justify-center font-title-lg text-title-lg font-bold text-on-surface">95%</div>
                </div>
                <div>
                  <h4 className="font-title-lg text-title-lg font-bold text-on-surface">Cumplimiento de Asistencia</h4>
                  <p className="font-body-sm text-body-sm text-outline">
                    Meta bimestral exigida por el reglamento escolar: 85% mínimo para promoción.
                  </p>
                </div>
              </div>
              <div className="flex items-center gap-space-sm">
                <div className="text-right">
                  <span className="font-label-sm text-label-sm text-outline block">Próxima Salida</span>
                  <span className="font-title-lg text-body-md font-bold text-primary">Hoy 02:30 PM</span>
                </div>
                <div className="h-8 w-px bg-surface-container" />
                <button
                  type="button"
                  onClick={() => setJustifyOpen(true)}
                  className="px-3 py-2 bg-surface-container-low hover:bg-surface-container text-primary font-label-md text-label-md font-semibold rounded-lg transition-colors flex items-center gap-1.5"
                >
                  <Icon name="time_to_leave" className="text-[18px]" />
                  <span>Retiro Anticipado</span>
                </button>
              </div>
            </div>
          </div>

          {/* RIGHT: alerts */}
          <div className="lg:col-span-4 flex flex-col gap-space-md">
            <div className="bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex flex-col gap-space-md">
              <div className="flex items-center justify-between pb-space-sm border-b border-surface-container-low">
                <div className="flex items-center gap-2">
                  <Icon name="notifications_active" className="text-[22px] text-primary" />
                  <div>
                    <h3 className="font-title-lg text-title-lg font-bold text-on-surface">Alertas en Vivo</h3>
                    <p className="font-label-sm text-label-sm text-outline">Canal instantáneo a familiares</p>
                  </div>
                </div>
                <label className="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" checked={liveAlerts} onChange={toggleAlerts} className="sr-only peer" />
                  <div className="w-11 h-6 bg-surface-container-highest rounded-full peer peer-focus:outline-none peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-secondary" />
                </label>
              </div>

              <div className="flex items-center justify-between bg-surface-container-low px-3 py-2 rounded-lg">
                <div className="flex items-center gap-2 text-secondary">
                  <Icon name="chat" className="text-[18px]" />
                  <span className="font-label-sm text-label-sm font-semibold text-on-surface">
                    {liveAlerts ? 'WhatsApp + Push Activos' : 'Alertas en pausa'}
                  </span>
                </div>
                <span className="font-label-sm text-label-sm text-outline font-mono">+51 987***210</span>
              </div>

              <div className="flex flex-col gap-space-md relative pl-4 before:content-[''] before:absolute before:left-1.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-surface-container">
                <div className="relative flex flex-col gap-1">
                  <div className="absolute -left-[22px] top-0.5 w-4 h-4 rounded-full bg-secondary ring-4 ring-secondary-container/40" />
                  <div className="flex items-center justify-between">
                    <span className="font-label-md text-label-md font-bold text-secondary flex items-center gap-1">
                      <Icon name="check_circle" className="text-[16px]" /> Ingreso Confirmado
                    </span>
                    <span className="font-label-sm text-label-sm text-outline">Hace 15 min</span>
                  </div>
                  <p className="font-body-sm text-body-sm text-on-surface-variant">
                    Mateo cruzó el molinete de <span className="font-semibold text-on-surface">Puerta 2</span> a las{' '}
                    <span className="font-semibold text-on-surface">07:44 AM</span>. Marcación válida con geolocalización
                    confirmada por GPS institucional.
                  </p>
                  <div className="mt-1 flex items-center gap-2 text-label-sm text-outline">
                    <span className="flex items-center gap-1">
                      <Icon name="pin_drop" className="text-[14px]" /> Perímetro OK (50m)
                    </span>
                    <span>•</span>
                    <button type="button" onClick={() => openVoucher(ATTENDANCE[0])} className="text-primary font-medium hover:underline">
                      Ver detalle
                    </button>
                  </div>
                </div>

                <div className="relative flex flex-col gap-1">
                  <div className="absolute -left-[22px] top-0.5 w-4 h-4 rounded-full bg-primary ring-4 ring-primary-container/40" />
                  <div className="flex items-center justify-between">
                    <span className="font-label-md text-label-md font-bold text-primary flex items-center gap-1">
                      <Icon name="logout" className="text-[16px]" /> Salida Registrada
                    </span>
                    <span className="font-label-sm text-label-sm text-outline">Ayer 02:32 PM</span>
                  </div>
                  <p className="font-body-sm text-body-sm text-on-surface-variant">
                    Fin de jornada académica regular registrado por lector de salida de Puerta Principal 1.
                  </p>
                  <div className="mt-1 flex items-center gap-2 text-label-sm text-outline">
                    <span className="flex items-center gap-1">
                      <Icon name="shield" className="text-[14px]" /> Retiro sin novedades
                    </span>
                  </div>
                </div>

                <div className="relative flex flex-col gap-1">
                  <div className="absolute -left-[22px] top-0.5 w-4 h-4 rounded-full bg-tertiary ring-4 ring-tertiary-fixed/60" />
                  <div className="flex items-center justify-between">
                    <span className="font-label-md text-label-md font-bold text-tertiary flex items-center gap-1">
                      <Icon name="warning" className="text-[16px]" /> Alerta de Tardanza
                    </span>
                    <span className="font-label-sm text-label-sm text-outline">21 Oct 08:14 AM</span>
                  </div>
                  <p className="font-body-sm text-body-sm text-on-surface-variant">
                    Mateo ingresó a las <span className="font-semibold text-on-surface">08:12 AM</span> (Tolerancia máxima
                    08:00 AM). Justificación médica enviada por el tutor y aceptada por Coordinación.
                  </p>
                  <div className="mt-1 flex items-center gap-2 text-label-sm">
                    <span className="bg-surface-container px-2 py-0.5 rounded text-outline font-semibold">Tardanza justificada</span>
                  </div>
                </div>
              </div>

              <div className="pt-space-xs mt-2">
                <button
                  type="button"
                  onClick={() => setJustifyOpen(true)}
                  className="w-full py-2.5 px-4 bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md font-bold rounded-lg shadow-sm flex items-center justify-center gap-2 transition-all"
                >
                  <Icon name="assignment_late" className="text-[20px]" />
                  <span>Solicitar Justificación / Permiso</span>
                </button>
                <p className="text-center font-label-sm text-label-sm text-outline mt-2">
                  Plazo de regularización: hasta 48 horas post-evento
                </p>
              </div>
            </div>

            {/* School bus card */}
            <div className="bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex flex-col gap-space-sm">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <Icon name="directions_bus" className="text-secondary text-[22px]" />
                  <span className="font-title-lg text-body-md font-bold text-on-surface">Ruta Escolar GEO #04</span>
                </div>
                <span className="font-label-sm text-label-sm bg-secondary-container/40 text-on-secondary-container font-semibold px-2 py-0.5 rounded-full">
                  En Destino
                </span>
              </div>
              <p className="font-body-sm text-body-sm text-on-surface-variant">
                Unidad Volvo B8R arribó a la sede central a las 07:38 AM con 28 estudiantes verificados a bordo.
              </p>
              <div className="w-full h-28 rounded-lg overflow-hidden relative shadow-inner bg-surface-container-high">
                <div className="w-full h-full bg-cover bg-center" style={{ backgroundImage: `url('${IMAGES.busDropoff}')` }} />
                <div className="absolute bottom-2 left-2 bg-surface-container-lowest/90 backdrop-blur-md px-2 py-1 rounded text-label-sm font-bold text-on-surface flex items-center gap-1">
                  <span className="w-2 h-2 rounded-full bg-secondary" /> Puerta 2 Peatonal
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Modal: voucher */}
      <Modal open={!!voucher} onClose={() => setVoucher(null)}>
        {voucher && (
          <>
            <div className="bg-primary p-space-md text-on-primary flex items-center justify-between">
              <div className="flex items-center gap-2">
                <Icon name="verified" className="text-[24px]" />
                <div>
                  <h3 className="font-headline-md text-title-lg font-bold">Comprobante de Marcación Segura</h3>
                  <p className="font-label-sm text-label-sm text-primary-fixed">GEOEDUCA Anti-Fraude Token Cryptografico</p>
                </div>
              </div>
              <button type="button" onClick={() => setVoucher(null)} className="p-1 rounded-full hover:bg-on-primary/10 text-on-primary">
                <Icon name="close" className="text-[20px]" />
              </button>
            </div>

            <div className="p-space-lg flex flex-col gap-space-md">
              <div className="flex items-center gap-3 p-3 bg-surface-container-low rounded-xl">
                <SafeImage alt={child.name} className="w-12 h-12 rounded-full object-cover" src={child.avatar} />
                <div>
                  <div className="font-title-lg text-body-md font-bold text-on-surface">{child.name}</div>
                  <div className="font-label-sm text-label-sm text-outline">Código Estudiante: #STU-2025-8831</div>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3 text-body-sm font-body-sm">
                {[
                  ['Fecha y Hora', `${voucher.date} • ${voucher.time}`, ''],
                  ['Punto de Control', voucher.gate, ''],
                  ['Mecanismo de Firma', voucher.method, ''],
                  ['Coordenadas GPS', voucher.gps, 'text-primary font-mono text-[12px]'],
                ].map(([label, value, extra]) => (
                  <div key={label} className="p-2.5 bg-surface-container rounded-lg">
                    <span className="font-label-sm text-label-sm text-outline block">{label}</span>
                    <span className={`font-semibold ${extra || 'text-on-surface'}`}>{value}</span>
                  </div>
                ))}
              </div>

              <div className="relative rounded-xl overflow-hidden h-36 bg-surface-container-high">
                <div className="absolute inset-0 bg-primary/10 flex items-center justify-center">
                  <div className="bg-surface-container-lowest/95 px-3 py-1.5 rounded-full shadow-md flex items-center gap-2 text-label-sm font-bold text-secondary">
                    <span className="w-2 h-2 rounded-full bg-secondary animate-ping" />
                    Dentro del Geoperímetro Escolar (Radio 35m)
                  </div>
                </div>
              </div>

              <div className="p-3 bg-surface-container-lowest rounded-lg border border-surface-container-high flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <Icon name="fingerprint" className="text-secondary text-[20px]" />
                  <div className="font-mono text-[11px] text-outline truncate max-w-[260px]">
                    SHA256: 7f83b1657ff1fc53b92dc18148a1d65dfc2d4b1f
                  </div>
                </div>
                <span className="font-label-sm text-label-sm text-secondary font-bold">Autenticado</span>
              </div>
            </div>

            <div className="p-space-md bg-surface-container-low flex items-center justify-end gap-2">
              <button type="button" onClick={() => setVoucher(null)} className="px-4 py-2 bg-surface-container-lowest hover:bg-surface-container text-on-surface rounded-lg font-label-md text-label-md font-semibold transition-colors">
                Cerrar
              </button>
              <button type="button" onClick={printVoucher} className="px-4 py-2 bg-primary hover:bg-primary-container text-on-primary rounded-lg font-label-md text-label-md font-bold flex items-center gap-1.5 transition-colors">
                <Icon name="print" className="text-[18px]" />
                Imprimir Constancia
              </button>
            </div>
          </>
        )}
      </Modal>

      {/* Modal: justification */}
      <Modal open={justifyOpen} onClose={() => setJustifyOpen(false)}>
        <div className="bg-primary-container p-space-md text-on-primary flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Icon name="edit_document" className="text-[24px]" />
            <div>
              <h3 className="font-headline-md text-title-lg font-bold">Solicitud de Justificación o Permiso</h3>
              <p className="font-label-sm text-label-sm text-on-primary-container">
                Para: {child.name} ({child.short})
              </p>
            </div>
          </div>
          <button type="button" onClick={() => setJustifyOpen(false)} className="p-1 rounded-full hover:bg-on-primary/10 text-on-primary">
            <Icon name="close" className="text-[20px]" />
          </button>
        </div>
        <form className="p-space-lg flex flex-col gap-4" onSubmit={submitJustification} noValidate>
          <div>
            <label className="block font-label-md text-label-md font-semibold text-on-surface mb-1">Tipo de Solicitud</label>
            <select
              value={requestType}
              onChange={(e) => setRequestType(e.target.value)}
              className="w-full bg-surface-container-low text-on-surface p-2.5 rounded-lg border border-outline-variant focus:outline-none focus:border-primary font-body-md text-body-md"
            >
              <option value="medical">Justificación por Motivo Médico / Salud</option>
              <option value="early_exit">Permiso de Salida Anticipada</option>
              <option value="tardy_excuse">Tardanza Excepcional (Fuerza Mayor)</option>
              <option value="absence">Inasistencia Programada (Duelo / Trámite)</option>
            </select>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block font-label-md text-label-md font-semibold text-on-surface mb-1" htmlFor="event-date">
                Fecha del Evento
              </label>
              <input
                id="event-date"
                type="date"
                value={eventDate.value}
                onChange={eventDate.onChange}
                onBlur={eventDate.onBlur}
                aria-invalid={!!eventDate.error}
                className={`w-full bg-surface-container-low text-on-surface p-2 rounded-lg border font-body-md text-body-sm focus:outline-none ${
                  eventDate.error ? 'border-error' : 'border-outline-variant focus:border-primary'
                }`}
              />
              <FieldError id="event-date-error" message={eventDate.error} />
            </div>
            <div>
              <label className="block font-label-md text-label-md font-semibold text-on-surface mb-1">Hora Estimada</label>
              <input
                type="time"
                value={eventTime}
                onChange={(e) => setEventTime(e.target.value)}
                className="w-full bg-surface-container-low text-on-surface p-2 rounded-lg border border-outline-variant font-body-md text-body-sm focus:outline-none focus:border-primary"
              />
            </div>
          </div>
          <div>
            <label className="block font-label-md text-label-md font-semibold text-on-surface mb-1" htmlFor="justification-reason">
              Motivo / Explicación Detallada
            </label>
            <textarea
              id="justification-reason"
              rows={3}
              value={reason.value}
              onChange={reason.onChange}
              onBlur={reason.onBlur}
              aria-invalid={!!reason.error}
              placeholder="Escriba los detalles de la consulta médica o motivo de inasistencia..."
              className={`w-full bg-surface-container-low text-on-surface p-2.5 rounded-lg border font-body-md text-body-sm focus:outline-none ${
                reason.error ? 'border-error' : 'border-outline-variant focus:border-primary'
              }`}
            />
            <FieldError id="justification-reason-error" message={reason.error} />
          </div>
          <div>
            <label className="block font-label-md text-label-md font-semibold text-on-surface mb-1">Adjuntar Comprobante o Receta (PDF / Foto)</label>
            <label className="block border-2 border-dashed border-outline-variant p-4 rounded-xl text-center bg-surface-container-low/50 hover:bg-surface-container-low cursor-pointer transition-colors">
              <input type="file" accept=".jpg,.jpeg,.png,.pdf" className="sr-only" />
              <Icon name="cloud_upload" className="text-[32px] text-primary" />
              <p className="font-body-sm text-body-sm text-on-surface mt-1">Haga clic o arrastre su certificado médico aquí</p>
              <p className="font-label-sm text-label-sm text-outline">Formatos soportados: JPG, PNG, PDF (Máx. 5MB)</p>
            </label>
          </div>
          <div className="flex items-center justify-end gap-2 pt-2">
            <button type="button" onClick={() => setJustifyOpen(false)} className="px-4 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface rounded-lg font-label-md text-label-md font-semibold">
              Cancelar
            </button>
            <button
              type="submit"
              disabled={!eventDate.isValid || !reason.isValid}
              className="px-5 py-2 bg-primary hover:bg-primary-container disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-primary text-on-primary rounded-lg font-label-md text-label-md font-bold shadow-sm"
            >
              Enviar a Dirección
            </button>
          </div>
        </form>
      </Modal>

      <Toast toast={toast} />
    </DashboardLayout>
  );
}
