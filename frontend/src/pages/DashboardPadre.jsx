import { useCallback, useEffect, useMemo, useState } from 'react';
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
import { getJustificaciones, getPadre, linkChild, submitJustification } from '../services/asistenciaService';
import { downloadCsv, escapeHtml, inRange, printHtml, shortHash } from '../utils/attendance';
import { validateEventDate, validateIdentifier, validateJustificationReason } from '../validation/rules';

const PAGE_SIZE = 5;
const TODAY_TONE = {
  ok: 'bg-secondary-container/50 text-on-secondary-container',
  late: 'bg-tertiary-fixed text-on-tertiary-fixed',
  miss: 'bg-error-container text-on-error-container',
  wait: 'bg-surface-container text-on-surface-variant',
};
const ALERT = {
  secondary: { dot: 'bg-secondary', ring: 'ring-secondary-container/40', text: 'text-secondary' },
  primary: { dot: 'bg-primary', ring: 'ring-primary-container/40', text: 'text-primary' },
  tertiary: { dot: 'bg-tertiary', ring: 'ring-tertiary-fixed/60', text: 'text-tertiary' },
};

const matchesFilter = (row, filter) =>
  filter === 'all' || row.status === filter || (filter === 'justificada' && row.justified);

const photo = (key) => (key ? IMAGES[key] : null);

export default function DashboardPadre() {
  const load = useCallback(() => getPadre(), []);
  const { data, setData, error, loading, reload } = useApiResource(load);
  const { toast, showToast } = useToast();
  const [childId, setChildId] = useState(null);
  const [menuOpen, setMenuOpen] = useState(false);
  const [filter, setFilter] = useState('all');
  const [range, setRange] = useState('currentMonth');
  const [page, setPage] = useState(1);
  const [liveAlerts, setLiveAlerts] = useState(true);
  const [voucher, setVoucher] = useState(null);
  const [justifyOpen, setJustifyOpen] = useState(false);
  const [linkOpen, setLinkOpen] = useState(false);
  const [requestType, setRequestType] = useState('medical');
  const [eventTime, setEventTime] = useState('11:30');
  const [attachmentName, setAttachmentName] = useState('');
  const [saving, setSaving] = useState(false);
  const [requests, setRequests] = useState([]);
  const eventDate = useValidatedField(validateEventDate, new Date().toLocaleDateString('en-CA', { timeZone: 'America/Lima' }));
  const reason = useValidatedField(validateJustificationReason);
  const linkDni = useValidatedField(validateIdentifier);

  const child = useMemo(() => {
    const children = data?.children ?? [];
    return children.find((item) => item.id === childId) ?? children[0] ?? null;
  }, [data, childId]);

  useEffect(() => {
    if (!menuOpen) return undefined;
    const close = () => setMenuOpen(false);
    document.addEventListener('click', close);
    return () => document.removeEventListener('click', close);
  }, [menuOpen]);

  useEffect(() => {
    setPage(1);
  }, [filter, range, child?.id]);

  useEffect(() => {
    getJustificaciones().then((result) => setRequests(result.justifications ?? [])).catch(() => setRequests([]));
  }, [data]);

  const rangeRows = useMemo(
    () => (child?.attendance ?? []).filter((row) => inRange(row.iso, range)),
    [child, range]
  );
  const filtered = rangeRows.filter((row) => matchesFilter(row, filter));
  const pages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
  const safePage = Math.min(page, pages);
  const visible = filtered.slice((safePage - 1) * PAGE_SIZE, safePage * PAGE_SIZE);
  const count = (key) => rangeRows.filter((row) => matchesFilter(row, key)).length;
  const filters = [
    { key: 'all', label: `Todos (${rangeRows.length})` },
    { key: 'puntual', label: `Puntual (${count('puntual')})` },
    { key: 'tardanza', label: `Tardanza (${count('tardanza')})` },
    { key: 'falta', label: `Falta (${count('falta')})` },
    { key: 'justificada', label: `Justificada (${count('justificada')})` },
  ];

  const openVoucher = (row) => {
    if (!row?.voucher) return;
    const [date, time, gate, method, gps] = row.voucher;
    setVoucher({ date, time, gate, method, gps });
  };

  const selectChild = (next) => {
    setChildId(next.id);
    setMenuOpen(false);
    showToast(`Mostrando el registro de ${next.name}`);
  };

  const downloadReport = (format) => {
    if (!child) return;
    const headers = ['Fecha', 'Ingreso', 'Acceso', 'Método', 'Salida', 'Estado'];
    const body = filtered.map((row) => [row.date, row.time, row.gate, row.method, row.exit, row.status]);
    const filename = `Asistencia_${child.name.replace(/\s+/g, '')}`;
    if (format === 'excel') {
      downloadCsv(`${filename}.csv`, headers, body);
      showToast('Reporte descargado', 'El CSV abre en Excel e incluye el filtro actual.');
      return;
    }
    const opened = printHtml(
      filename,
      `<h1>Asistencia de ${escapeHtml(child.name)}</h1><p>${escapeHtml(child.grade)} · ${escapeHtml(child.code)}</p>
       <table><thead><tr>${headers.map((item) => `<th>${item}</th>`).join('')}</tr></thead>
       <tbody>${body.map((row) => `<tr>${row.map((cell) => `<td>${escapeHtml(cell)}</td>`).join('')}</tr>`).join('')}</tbody></table>`
    );
    showToast(opened ? 'Puede guardar el reporte como PDF' : 'El navegador bloqueó la ventana de impresión.');
  };

  const submit = async (event) => {
    event.preventDefault();
    eventDate.onBlur();
    reason.onBlur();
    if (!child || !eventDate.isValid || !reason.isValid) return;
    setSaving(true);
    try {
      const result = await submitJustification({
        child_id: child.id,
        type: requestType,
        event_date: eventDate.value,
        event_time: eventTime || undefined,
        reason: reason.value.trim(),
      });
      setData(result.panel);
      setJustifyOpen(false);
      reason.setValue('');
      setAttachmentName('');
      showToast(result.message || 'Solicitud enviada a Coordinación Académica.');
    } catch (err) {
      if (err.fieldErrors?.reason) reason.setError(err.fieldErrors.reason);
      if (err.fieldErrors?.event_date) eventDate.setError(err.fieldErrors.event_date);
      showToast(err.message);
    } finally {
      setSaving(false);
    }
  };

  const linkStudent = async (event) => {
    event.preventDefault();
    linkDni.onBlur();
    if (!linkDni.isValid) return;
    setSaving(true);
    try {
      const result = await linkChild(linkDni.value);
      setData(result.panel);
      const linked = result.panel.children.find((item) => item.dni === linkDni.value);
      if (linked) setChildId(linked.id);
      setLinkOpen(false);
      linkDni.setValue('');
      showToast(result.message);
    } catch (err) {
      showToast(err.message);
    } finally {
      setSaving(false);
    }
  };

  const pageNumbers = [];
  for (let n = Math.max(1, safePage - 2); n <= Math.min(pages, Math.max(1, safePage - 2) + 4); n += 1) pageNumbers.push(n);

  return (
    <DashboardLayout>
      <PageState loading={loading} error={error} onRetry={reload}>
        {data && !child && (
          <div className="bg-surface-container-lowest rounded-xl p-8 text-center shadow-sm">
            <h2 className="font-title-lg font-bold">No hay estudiantes vinculados</h2>
            <p className="mt-4 text-on-surface-variant">La vinculación la realiza administración. No puede reclamar un estudiante por su cuenta.</p>
          </div>
        )}
        {child && (
          <div className="flex flex-col w-full">
            <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md mb-space-lg">
              <div className="flex flex-col sm:flex-row items-start sm:items-center gap-space-md">
                <div className="relative">
                  <button type="button" onClick={(event) => { event.stopPropagation(); setMenuOpen((open) => !open); }} className="flex items-center gap-3 bg-surface-container-lowest px-4 py-2.5 rounded-xl shadow-sm text-left">
                    <SafeImage alt={child.name} className="w-11 h-11 rounded-full object-cover" src={photo(child.avatar)} />
                    <div>
                      <span className="font-title-lg font-semibold flex items-center gap-1">{child.name}<Icon name="expand_more" /></span>
                      <span className="font-label-sm text-outline">{child.grade}</span>
                    </div>
                  </button>
                  {menuOpen && (
                    <div className="absolute left-0 mt-2 w-72 rounded-xl bg-surface-container-lowest shadow-xl z-20 py-2">
                      <div className="px-3 py-1.5 text-outline font-label-sm uppercase">Hijos matriculados</div>
                      {data.children.map((item) => (
                        <button key={item.id} type="button" onClick={() => selectChild(item)} className={`w-full flex items-center gap-3 px-3 py-2 text-left ${item.id === child.id ? 'bg-surface-container-high/60' : ''}`}>
                          <SafeImage alt={item.name} className="w-8 h-8 rounded-full object-cover" src={photo(item.listAvatar)} />
                          <div className="flex-1 min-w-0">
                            <p className="font-semibold truncate">{item.name}</p>
                            <p className="font-label-sm text-secondary">{item.short} · {item.today.badge}</p>
                          </div>
                          {item.id === child.id && <Icon name="check" className="text-secondary" />}
                        </button>
                      ))}
                      <p className="mt-2 px-3 text-outline font-body-sm">La vinculación de otro estudiante la realiza administración.</p>
                    </div>
                  )}
                </div>
                <div className="flex items-center gap-2">
                  <div className="bg-surface-container-low px-3 py-2 rounded-lg">
                    <span className="font-label-sm text-outline block">Asistencia mensual</span>
                    <span className="font-semibold">{child.stats.monthlyLabel}</span>
                  </div>
                  <div className="bg-surface-container-low px-3 py-2 rounded-lg">
                    <span className="font-label-sm text-outline block">Tardanzas</span>
                    <span className="font-semibold">{child.stats.latesLabel}</span>
                  </div>
                </div>
              </div>
              <div className="bg-surface-container-lowest px-4 py-3 rounded-xl shadow-sm flex items-center justify-between gap-4">
                <div>
                  <div className="flex items-center gap-2">
                    <span className="font-label-sm uppercase text-secondary font-bold">Estado de hoy ({child.today.dateLabel})</span>
                    <span className={`px-2 py-0.5 rounded-full font-label-sm font-semibold ${TODAY_TONE[child.today.tone]}`}>{child.today.badge}</span>
                  </div>
                  <p className="font-body-sm text-on-surface-variant mt-0.5">{child.today.summary}</p>
                </div>
                {child.today.rowId && (
                  <button type="button" onClick={() => openVoucher(child.attendance.find((row) => row.id === child.today.rowId))} className="hidden md:flex items-center gap-1 px-3 py-1.5 bg-surface-container rounded-lg text-primary">
                    <Icon name="location_on" /> Ver GPS
                  </button>
                )}
              </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-12 gap-space-lg items-start">
              <div className="lg:col-span-8 flex flex-col gap-space-md min-w-0">
                <div className="bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3">
                  <div className="flex flex-wrap items-center gap-2">
                    <select value={range} onChange={(event) => setRange(event.target.value)} className="bg-surface-container-low rounded-lg py-2 px-3">
                      <option value="30days">Últimos 30 días</option>
                      <option value="currentMonth">Mes actual</option>
                      <option value="trimester">Último trimestre</option>
                      <option value="year">Año lectivo</option>
                    </select>
                    <div className="flex bg-surface-container-low p-1 rounded-lg gap-1 overflow-x-auto">
                      {filters.map((item) => (
                        <button key={item.key} type="button" onClick={() => setFilter(item.key)} className={`px-3 py-1 rounded font-label-sm whitespace-nowrap ${filter === item.key ? 'bg-primary text-on-primary' : 'text-on-surface-variant'}`}>
                          {item.label}
                        </button>
                      ))}
                    </div>
                  </div>
                  <div className="flex gap-2">
                    <button type="button" onClick={() => downloadReport('excel')} className="px-3 py-2 rounded-lg bg-surface-container-low font-semibold">Excel</button>
                    <button type="button" onClick={() => downloadReport('pdf')} className="px-3 py-2 rounded-lg bg-primary text-on-primary font-semibold">Descargar reporte</button>
                  </div>
                </div>

                <div className="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
                  <div className="overflow-x-auto">
                    <table className="w-full text-left">
                      <thead>
                        <tr className="bg-surface-container-low/70 h-10 text-outline font-label-sm uppercase">
                          {['Fecha', 'Ingreso', 'Acceso', 'Método', 'Salida', 'Estado', ''].map((label) => <th key={label} className="px-4 py-2">{label}</th>)}
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-surface-container-low text-body-sm">
                        {visible.map((row) => (
                          <tr key={row.id} className={row.today ? 'bg-secondary-container/10' : row.status === 'tardanza' ? 'bg-tertiary-fixed/20' : row.status === 'falta' ? 'bg-error-container/20' : ''}>
                            <td className="px-4 py-2"><span className="font-semibold block">{row.date}</span><span className="text-outline text-label-sm">{row.sub}</span></td>
                            <td className="px-4 py-2 font-medium">{row.time}</td>
                            <td className="px-4 py-2"><span className="inline-flex items-center gap-1"><Icon name={row.gateIcon} className="text-[16px]" />{row.gate}</span></td>
                            <td className="px-4 py-2"><span className="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-surface-container-high font-label-sm"><Icon name={row.methodIcon} className="text-[14px]" />{row.method}</span></td>
                            <td className="px-4 py-2 whitespace-nowrap">{row.exit}</td>
                            <td className="px-4 py-2">
                              {row.status === 'puntual' && <span className="px-2 py-0.5 rounded-full bg-secondary-container/40 text-on-secondary-container font-label-sm">Puntual</span>}
                              {row.status === 'tardanza' && <span className="px-2 py-0.5 rounded-full bg-tertiary-fixed font-label-sm">Tardanza ({row.minutes} min)</span>}
                              {row.status === 'falta' && <span className="px-2 py-0.5 rounded-full bg-error-container font-label-sm">{row.justified ? 'Falta justificada' : 'Inasistencia'}</span>}
                            </td>
                            <td className="px-4 py-2 text-right">
                              {row.justified && (
                                <button type="button" onClick={() => showToast(row.justificationNote || 'Justificación registrada')} className="px-2 py-1 mr-1 bg-surface-container rounded text-label-sm text-primary">Adjunto</button>
                              )}
                              <button type="button" onClick={() => openVoucher(row)} className="p-1.5 text-primary"><Icon name="verified_user" /></button>
                            </td>
                          </tr>
                        ))}
                        {visible.length === 0 && <tr><td colSpan={7} className="px-4 py-8 text-center text-outline">Sin marcaciones para este filtro.</td></tr>}
                      </tbody>
                    </table>
                  </div>
                  <div className="px-4 py-3 flex items-center justify-between gap-3">
                    <span className="font-body-sm text-outline">
                      {filtered.length === 0 ? '0 marcaciones' : `${(safePage - 1) * PAGE_SIZE + 1} a ${Math.min(safePage * PAGE_SIZE, filtered.length)} de ${filtered.length}`}
                    </span>
                    <div className="flex items-center gap-1">
                      <button type="button" disabled={safePage === 1} onClick={() => setPage(safePage - 1)} className="p-1.5 disabled:opacity-40"><Icon name="chevron_left" /></button>
                      {pageNumbers.map((n) => (
                        <button key={n} type="button" onClick={() => setPage(n)} className={`w-8 h-8 rounded-lg font-semibold ${n === safePage ? 'bg-primary text-on-primary' : ''}`}>{n}</button>
                      ))}
                      <button type="button" disabled={safePage === pages} onClick={() => setPage(safePage + 1)} className="p-1.5 disabled:opacity-40"><Icon name="chevron_right" /></button>
                    </div>
                  </div>
                </div>

                <div className="bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                  <div className="flex items-center gap-4">
                    <div className="relative w-16 h-16">
                      <svg className="w-full h-full -rotate-90" viewBox="0 0 36 36">
                        <path className="text-surface-container-highest" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" strokeWidth="3.5" />
                        <path className="text-secondary" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" strokeDasharray={`${child.compliance.percent}, 100`} strokeLinecap="round" strokeWidth="3.5" />
                      </svg>
                      <div className="absolute inset-0 flex items-center justify-center font-bold">{Math.round(child.compliance.percent)}%</div>
                    </div>
                    <div>
                      <h4 className="font-bold">Cumplimiento de asistencia</h4>
                      <p className="font-body-sm text-outline">Meta del reglamento: {child.compliance.minimum}% mínimo para promoción.</p>
                    </div>
                  </div>
                  <div className="font-body-sm text-on-surface-variant">
                    {requests.filter((item) => item.student_id === child.id).map((item) => (
                      <p key={item.id}>{item.event_date}: {item.status}</p>
                    ))}
                  </div>
                  <button type="button" onClick={() => setJustifyOpen(true)} className="px-3 py-2 rounded-lg bg-surface-container-low text-primary font-semibold flex items-center gap-1">
                    <Icon name="time_to_leave" /> Retiro anticipado
                  </button>
                </div>
              </div>

              <div className="lg:col-span-4 flex flex-col gap-space-md">
                <div className={`bg-surface-container-lowest p-space-md rounded-xl shadow-sm ${liveAlerts ? '' : 'opacity-60'}`}>
                  <div className="flex items-center justify-between pb-3 border-b border-surface-container-low">
                    <div>
                      <h3 className="font-bold">Alertas en vivo</h3>
                      <p className="font-label-sm text-outline">{data.phoneMasked}</p>
                    </div>
                    <input type="checkbox" checked={liveAlerts} onChange={(event) => { setLiveAlerts(event.target.checked); showToast(event.target.checked ? 'Alertas activadas' : 'Alertas en pausa'); }} className="w-4 h-4 accent-secondary" />
                  </div>
                  <div className="mt-4 flex flex-col gap-4 relative pl-4 before:absolute before:left-1.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-surface-container">
                    {child.alerts.length === 0 && <p className="text-outline font-body-sm">Sin alertas para este estudiante.</p>}
                    {child.alerts.map((alert) => {
                      const tone = ALERT[alert.tone] ?? ALERT.primary;
                      return (
                        <div key={alert.id} className="relative">
                          <div className={`absolute -left-[22px] top-0.5 w-4 h-4 rounded-full ring-4 ${tone.dot} ${tone.ring}`} />
                          <div className="flex justify-between gap-2">
                            <span className={`font-bold flex items-center gap-1 ${tone.text}`}><Icon name={alert.icon} className="text-[16px]" />{alert.title}</span>
                            <span className="font-label-sm text-outline">{alert.time}</span>
                          </div>
                          <p className="font-body-sm text-on-surface-variant mt-1">{alert.text}</p>
                          <div className="mt-1 flex items-center gap-2 text-label-sm text-outline">
                            <span>{alert.meta}</span>
                            {alert.rowId && <button type="button" className="text-primary" onClick={() => openVoucher(child.attendance.find((row) => row.id === alert.rowId))}>Ver detalle</button>}
                          </div>
                        </div>
                      );
                    })}
                  </div>
                  <button type="button" onClick={() => setJustifyOpen(true)} className="w-full mt-4 py-2.5 rounded-lg bg-primary text-on-primary font-bold">Solicitar justificación / permiso</button>
                </div>

                <div className="bg-surface-container-lowest p-space-md rounded-xl shadow-sm">
                  {child.bus ? (
                    <>
                      <div className="flex items-center justify-between">
                        <span className="font-bold flex items-center gap-2"><Icon name="directions_bus" className="text-secondary" />{child.bus.name}</span>
                        <span className="font-label-sm bg-secondary-container/40 px-2 py-0.5 rounded-full">{child.bus.status}</span>
                      </div>
                      <p className="font-body-sm text-on-surface-variant mt-2">{child.bus.detail}</p>
                      <div className="mt-3 h-28 rounded-lg bg-cover bg-center" style={{ backgroundImage: `url('${IMAGES.busDropoff}')` }} />
                      <p className="font-label-sm mt-2">{child.bus.stop}</p>
                    </>
                  ) : (
                    <p className="font-body-sm text-on-surface-variant"><strong className="text-on-surface">{child.name.split(' ')[0]}</strong> no tiene ruta escolar asignada. El ingreso es por cuenta familiar.</p>
                  )}
                </div>
              </div>
            </div>
          </div>
        )}
      </PageState>

      <Modal open={!!voucher} onClose={() => setVoucher(null)}>
        {voucher && child && (
          <>
            <div className="bg-primary p-space-md text-on-primary flex items-center justify-between">
              <h3 className="font-bold">Comprobante de marcación</h3>
              <button type="button" onClick={() => setVoucher(null)}><Icon name="close" /></button>
            </div>
            <div className="p-space-lg flex flex-col gap-3">
              <p className="font-semibold">{child.name} · {child.code}</p>
              <div className="grid grid-cols-2 gap-3 text-body-sm">
                {[['Fecha y hora', `${voucher.date} · ${voucher.time}`], ['Punto de control', voucher.gate], ['Método', voucher.method], ['GPS', voucher.gps]].map(([label, value]) => (
                  <div key={label} className="p-2.5 bg-surface-container rounded-lg">
                    <span className="text-outline block font-label-sm">{label}</span>
                    <span className="font-semibold">{value}</span>
                  </div>
                ))}
              </div>
              <p className="font-mono text-[11px] text-outline">SHA256: {shortHash(`${voucher.date}${voucher.time}${voucher.gps}`)}</p>
            </div>
            <div className="p-space-md bg-surface-container-low flex justify-end gap-2">
              <button type="button" onClick={() => setVoucher(null)} className="px-4 py-2 rounded-lg bg-surface-container-lowest">Cerrar</button>
              <button type="button" onClick={() => printHtml('Comprobante', `<h1>Comprobante</h1><p>${escapeHtml(child.name)}</p><p>${escapeHtml(voucher.date)} ${escapeHtml(voucher.time)}</p><p>${escapeHtml(voucher.gate)} · ${escapeHtml(voucher.method)}</p><p>${escapeHtml(voucher.gps)}</p>`)} className="px-4 py-2 rounded-lg bg-primary text-on-primary">Imprimir constancia</button>
            </div>
          </>
        )}
      </Modal>

      <Modal open={justifyOpen} onClose={() => setJustifyOpen(false)}>
        {child && (
          <form onSubmit={submit} noValidate>
            <div className="bg-primary-container p-space-md text-on-primary flex justify-between">
              <div>
                <h3 className="font-bold">Solicitud de justificación o permiso</h3>
                <p className="font-label-sm">Para: {child.name}</p>
              </div>
              <button type="button" onClick={() => setJustifyOpen(false)}><Icon name="close" /></button>
            </div>
            <div className="p-space-lg flex flex-col gap-4">
              <label className="font-semibold">Tipo
                <select value={requestType} onChange={(event) => setRequestType(event.target.value)} className="mt-1 w-full bg-surface-container-low p-2.5 rounded-lg font-normal">
                  <option value="medical">Justificación médica</option>
                  <option value="early_exit">Salida anticipada</option>
                  <option value="tardy_excuse">Tardanza excepcional</option>
                  <option value="absence">Inasistencia programada</option>
                </select>
              </label>
              <div className="grid grid-cols-2 gap-3">
                <label>Fecha
                  <input type="date" value={eventDate.value} onChange={eventDate.onChange} onBlur={eventDate.onBlur} className="mt-1 w-full bg-surface-container-low p-2 rounded-lg" />
                  <FieldError message={eventDate.error} />
                </label>
                <label>Hora
                  <input type="time" value={eventTime} onChange={(event) => setEventTime(event.target.value)} className="mt-1 w-full bg-surface-container-low p-2 rounded-lg" />
                </label>
              </div>
              <label>Motivo
                <textarea rows={3} value={reason.value} onChange={reason.onChange} onBlur={reason.onBlur} className="mt-1 w-full bg-surface-container-low p-2.5 rounded-lg" placeholder="Describa el motivo con al menos 10 caracteres" />
                <FieldError message={reason.error} />
              </label>
              <p className="font-body-sm text-outline">La solicitud no admite archivos. El motivo y la fecha quedan registrados.</p>
              <div className="flex justify-end gap-2">
                <button type="button" onClick={() => setJustifyOpen(false)} className="px-4 py-2 rounded-lg bg-surface-container">Cancelar</button>
                <button type="submit" disabled={saving || !eventDate.isValid || !reason.isValid} className="px-5 py-2 rounded-lg bg-primary text-on-primary disabled:opacity-50">{saving ? 'Enviando...' : 'Enviar a dirección'}</button>
              </div>
            </div>
          </form>
        )}
      </Modal>

      <Modal open={linkOpen} onClose={() => setLinkOpen(false)}>
        <form onSubmit={linkStudent} className="p-space-lg flex flex-col gap-3" noValidate>
          <h3 className="font-bold">Vincular estudiante</h3>
          <p className="font-body-sm text-outline">Ingrese el DNI de un hijo a su cargo. En la demostración puede usar 70998877.</p>
          <input value={linkDni.value} onChange={(event) => linkDni.onChange({ target: { value: event.target.value.replace(/\D/g, '').slice(0, 8) } })} onBlur={linkDni.onBlur} inputMode="numeric" placeholder="DNI del estudiante" className="bg-surface-container-low p-2.5 rounded-lg" />
          <FieldError message={linkDni.error} />
          <button type="submit" disabled={saving || !linkDni.isValid} className="px-4 py-2 rounded-lg bg-primary text-on-primary disabled:opacity-50">Vincular</button>
        </form>
      </Modal>
      <Toast toast={toast} />
    </DashboardLayout>
  );
}
