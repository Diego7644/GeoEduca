import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import DashboardLayout from '../components/DashboardLayout';
import Icon from '../components/Icon';
import Modal from '../components/Modal';
import PageState from '../components/PageState';
import SafeImage from '../components/SafeImage';
import Toast, { useToast } from '../components/Toast';
import { IMAGES } from '../config/images';
import useApiResource from '../hooks/useApiResource';
import { getEstudiante, refreshStudentToken } from '../services/asistenciaService';
import { distanceMeters, formatDistance } from '../utils/attendance';
import { QR_COLORS, qrModules } from '../utils/qrToken';

const TOTAL_TIME = 60;
const CIRCUMFERENCE = 2 * Math.PI * 20;
const QR_FINDERS = [[20, 20], [164, 20], [20, 164]];
const KPI = 'bg-surface-container-lowest rounded-xl p-space-md shadow-sm flex flex-col justify-between';

function QrGraphic({ seed, dimmed }) {
  const modules = qrModules(seed);
  return (
    <svg
      className={`w-56 h-56 sm:w-64 sm:h-64 text-on-surface transition-opacity ${dimmed ? 'opacity-40' : ''}`}
      fill="none"
      viewBox="0 0 240 240"
      xmlns="http://www.w3.org/2000/svg"
    >
      <rect fill="#FAF8FF" height="240" rx="16" width="240" />
      {QR_FINDERS.map(([x, y]) => (
        <g key={`${x}-${y}`}>
          <rect fill={QR_COLORS.dark} height="56" rx="8" width="56" x={x} y={y} />
          <rect fill="#FFFFFF" height="40" rx="4" width="40" x={x + 8} y={y + 8} />
          <rect fill={QR_COLORS.dark} height="24" rx="3" width="24" x={x + 16} y={y + 16} />
        </g>
      ))}
      {modules.map(([x, y, w, h, fill]) => (
        <rect key={`${x}-${y}`} fill={fill} height={h} rx="2" width={w} x={x} y={y} />
      ))}
      <g filter="drop-shadow(0 2px 4px rgba(0,0,0,0.15))">
        <rect fill="#004BC3" height="48" rx="12" width="48" x="96" y="96" />
        <path d="M120 108L106 115L120 122L134 115L120 108Z" fill="#FFFFFF" />
        <path d="M111 120.5V126.5C111 129 115 131 120 131C125 131 129 129 129 126.5V120.5L120 125L111 120.5Z" fill="#B7C8FF" />
        <circle cx="132" cy="125" fill="#6CF8BB" r="3" />
      </g>
    </svg>
  );
}

export default function DashboardEstudiante() {
  const load = useCallback(() => getEstudiante(), []);
  const { data, setData, error, loading, reload } = useApiResource(load);
  const { toast, showToast } = useToast();
  const refreshingRef = useRef(false);
  const [timeLeft, setTimeLeft] = useState(TOTAL_TIME);
  const [flash, setFlash] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [weeklyOpen, setWeeklyOpen] = useState(false);
  const [rfidOpen, setRfidOpen] = useState(false);
  const [position, setPosition] = useState({ status: 'locating', meters: null });

  const refresh = useCallback(async () => {
    if (refreshingRef.current) return;
    refreshingRef.current = true;
    setRefreshing(true);
    try {
      const token = await refreshStudentToken();
      setData((current) => (current ? { ...current, token } : current));
      setTimeLeft(token.expires_in || TOTAL_TIME);
    } catch (err) {
      setTimeLeft(TOTAL_TIME);
      showToast('No se pudo renovar el QR', err.message);
    } finally {
      refreshingRef.current = false;
      setRefreshing(false);
    }
  }, [setData, showToast]);

  useEffect(() => {
    if (data?.token?.expires_in) setTimeLeft(data.token.expires_in);
  }, [data?.token?.value, data?.token?.expires_in]);

  useEffect(() => {
    const id = setInterval(() => setTimeLeft((current) => (current <= 1 ? 0 : current - 1)), 1000);
    return () => clearInterval(id);
  }, []);

  useEffect(() => {
    if (timeLeft !== 0 || !data?.token) return undefined;
    refresh();
    return undefined;
  }, [timeLeft, data?.token, refresh]);

  useEffect(() => {
    if (!data?.token?.value) return undefined;
    setFlash(true);
    const id = setTimeout(() => setFlash(false), 250);
    return () => clearTimeout(id);
  }, [data?.token?.value]);

  useEffect(() => {
    if (!data?.gps || !navigator.geolocation) {
      if (data?.gps) setPosition({ status: 'unsupported', meters: null });
      return undefined;
    }
    const watch = navigator.geolocation.watchPosition(
      (pos) => {
        const meters = distanceMeters(pos.coords.latitude, pos.coords.longitude, data.gps.lat, data.gps.lng);
        setPosition({
          status: meters <= data.gps.radius ? 'inside' : 'outside',
          meters,
        });
      },
      () => setPosition({ status: 'denied', meters: null }),
      { enableHighAccuracy: true, maximumAge: 15000, timeout: 8000 }
    );
    return () => navigator.geolocation.clearWatch(watch);
  }, [data?.gps]);

  const offset = CIRCUMFERENCE - (timeLeft / TOTAL_TIME) * CIRCUMFERENCE;
  const gpsCopy = useMemo(() => {
    if (!data?.gps) return { label: 'GPS', detail: '' };
    if (position.status === 'locating') return { label: 'Buscando señal', detail: 'Esperando la ubicación de este dispositivo.' };
    if (position.status === 'denied') return { label: 'Ubicación no disponible', detail: 'Autoriza el GPS o presenta tu carnet en la mesa del auxiliar.' };
    if (position.status === 'unsupported') return { label: 'Sin GPS', detail: 'Este navegador no informa coordenadas.' };
    const where = position.status === 'inside' ? 'Dentro del rango' : 'Fuera del perímetro';
    return {
      label: where,
      detail: `${data.gps.gate} • a ${formatDistance(position.meters)} (radio ${data.gps.radius} m)`,
    };
  }, [data?.gps, position]);

  const today = data?.today;
  const positive = today?.status === 'puntual';

  return (
    <DashboardLayout>
      <PageState loading={loading} error={error} onRetry={reload}>
        {data && (
          <div className="flex flex-col w-full">
            <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-space-md mb-space-lg">
              <div className="flex items-center gap-space-md">
                <div className="relative">
                  <SafeImage alt={data.profile.name} className="w-16 h-16 rounded-2xl object-cover shadow-sm bg-surface-container" src={IMAGES[data.profile.avatar]} />
                  <div className={`absolute -bottom-1 -right-1 w-5 h-5 rounded-full flex items-center justify-center text-on-secondary shadow-sm ${positive ? 'bg-secondary' : 'bg-tertiary'}`}>
                    <Icon name={positive ? 'check' : 'schedule'} filled className="text-[13px]" />
                  </div>
                </div>
                <div>
                  <div className="flex items-center gap-2 flex-wrap">
                    <h1 className="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight">
                      ¡{data.profile.greeting}, {data.profile.name}!
                    </h1>
                    <span className="px-2 py-0.5 rounded-full bg-primary-container/10 text-primary font-label-sm font-semibold">{data.profile.code}</span>
                  </div>
                  <p className="font-body-md text-on-surface-variant flex items-center gap-2 mt-0.5 flex-wrap">
                    <span>{data.profile.grade}</span>
                    <span className="w-1 h-1 rounded-full bg-outline" />
                    <span className="text-primary font-semibold">Tutor: {data.profile.tutor}</span>
                  </p>
                </div>
              </div>
              <div className="flex items-center gap-space-sm px-space-md py-space-sm bg-surface-container-low rounded-xl shadow-sm self-start lg:self-center">
                <div className="w-2.5 h-2.5 rounded-full bg-secondary animate-pulse" />
                <div className="flex flex-col">
                  <span className="font-label-sm uppercase tracking-wider text-outline">{data.shift.label}</span>
                  <span className="font-title-lg text-body-sm font-semibold text-on-surface">{data.shift.detail}</span>
                </div>
              </div>
            </div>

            <div className="grid grid-cols-1 xl:grid-cols-12 gap-gutter items-start">
              <div className="xl:col-span-5 flex flex-col gap-space-lg">
                <div className="bg-surface-container-lowest rounded-2xl p-space-lg shadow-sm flex flex-col items-center relative overflow-hidden">
                  <div className="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-primary via-primary-container to-secondary" />
                  <div className="w-full flex items-center justify-between mb-space-md">
                    <div>
                      <span className="font-label-sm text-outline uppercase tracking-wider">Credencial dinámica</span>
                      <h2 className="font-title-lg text-title-lg text-on-surface font-bold">Tu token de asistencia</h2>
                    </div>
                    <div className="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-secondary-container/40 text-on-secondary-container">
                      <Icon name="shield" filled className="text-[15px]" />
                      <span className="font-label-sm font-semibold">Token {data.token.hint}</span>
                    </div>
                  </div>
                  <p className="font-body-sm text-on-surface-variant text-center mb-space-md">
                    Presenta este código en los torniquetes. Cambia solo con cada renovación.
                  </p>
                  <div className="relative p-space-md bg-surface-container-lowest rounded-2xl shadow-md">
                    <QrGraphic seed={data.token.value} dimmed={flash} />
                  </div>
                  <div className="flex items-center gap-space-md mt-space-md py-2 px-space-md bg-surface-container-low rounded-xl w-full">
                    <div className="relative w-12 h-12 flex-shrink-0 flex items-center justify-center">
                      <svg className="w-12 h-12 transform -rotate-90" viewBox="0 0 48 48">
                        <circle className="text-surface-container-highest" cx="24" cy="24" fill="none" r="20" stroke="currentColor" strokeWidth="3.5" />
                        <circle className="text-primary-container transition-all duration-1000 ease-linear" cx="24" cy="24" fill="none" r="20" stroke="currentColor" strokeDasharray={CIRCUMFERENCE} strokeDashoffset={offset} strokeLinecap="round" strokeWidth="3.5" />
                      </svg>
                      <span className="absolute font-bold text-primary text-label-md">{String(timeLeft).padStart(2, '0')}s</span>
                    </div>
                    <div className="flex flex-col flex-1 min-w-0">
                      <span className="font-label-md font-semibold text-on-surface">Rotación de token</span>
                      <span className="font-body-sm text-outline truncate">Se renueva cada 60 segundos</span>
                    </div>
                  </div>
                  <button type="button" onClick={refresh} className="w-full mt-space-md h-10 rounded-lg bg-primary-container hover:bg-primary text-on-primary font-semibold flex items-center justify-center gap-2">
                    <Icon name={refreshing ? 'refresh' : 'autorenew'} className={`text-[18px] ${refreshing ? 'animate-spin' : ''}`} />
                    {refreshing ? 'Actualizando...' : 'Generar nuevo código QR'}
                  </button>
                  <div className="w-full mt-space-md p-space-sm bg-surface-container rounded-xl flex items-start gap-space-sm">
                    <div className={`p-2 rounded-lg mt-0.5 ${position.status === 'inside' ? 'bg-secondary-container/40 text-on-secondary-container' : 'bg-tertiary-fixed text-on-tertiary-fixed'}`}>
                      <Icon name="near_me" filled className="text-[20px]" />
                    </div>
                    <div>
                      <div className="flex items-center gap-2">
                        <span className="font-label-sm font-bold uppercase">GPS del dispositivo</span>
                        <span className={`font-label-sm font-semibold ${position.status === 'inside' ? 'text-secondary' : 'text-tertiary'}`}>{gpsCopy.label}</span>
                      </div>
                      <p className="font-body-sm text-on-surface-variant mt-0.5">{gpsCopy.detail}</p>
                    </div>
                  </div>
                </div>

                <div className="bg-surface-container-low rounded-xl p-space-md flex items-center justify-between shadow-sm gap-3">
                  <div className="flex items-center gap-space-sm">
                    <Icon name="contactless" className="text-primary text-[24px]" />
                    <div>
                      <span className="font-semibold text-on-surface block">¿Sin batería o sin señal?</span>
                      <span className="font-body-sm text-outline">Puedes usar tu carnet RFID físico con el auxiliar.</span>
                    </div>
                  </div>
                  <button type="button" onClick={() => setRfidOpen(true)} className="px-3 py-1.5 rounded-lg bg-surface-container-highest text-primary font-semibold">Info</button>
                </div>
              </div>

              <div className="xl:col-span-7 flex flex-col gap-space-lg">
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-space-md">
                  <div className={KPI}>
                    <div className="flex items-center justify-between"><span className="font-label-sm uppercase text-outline">Puntualidad</span><Icon name="timelapse" className="text-secondary" /></div>
                    <div className="flex items-baseline gap-2">
                      <span className="font-metric-display text-headline-md font-bold">{Number(data.kpis.punctuality).toFixed(1)}%</span>
                      <span className={`font-label-sm font-bold flex items-center ${data.kpis.delta >= 0 ? 'text-secondary' : 'text-tertiary'}`}>
                        <Icon name={data.kpis.delta >= 0 ? 'arrow_upward' : 'arrow_downward'} className="text-[14px]" />
                        {Math.abs(data.kpis.delta).toFixed(1)}%
                      </span>
                    </div>
                    <span className="font-body-sm text-outline">Contra el mes anterior</span>
                  </div>
                  <div className={KPI}>
                    <div className="flex items-center justify-between"><span className="font-label-sm uppercase text-outline">Asistencias</span><Icon name="event_available" className="text-primary" /></div>
                    <div className="flex items-baseline gap-2">
                      <span className="font-metric-display text-headline-md font-bold">{data.kpis.present}</span>
                      <span className="font-body-sm text-outline">/ {data.kpis.expected} hábiles</span>
                    </div>
                    <span className="font-body-sm text-secondary font-medium">{data.kpis.unjustified} faltas injustificadas</span>
                  </div>
                  <div className={KPI}>
                    <div className="flex items-center justify-between"><span className="font-label-sm uppercase text-outline">Tardanzas</span><Icon name="history_toggle_off" className="text-tertiary-fixed-dim" /></div>
                    <div className="flex items-baseline gap-2">
                      <span className="font-metric-display text-headline-md font-bold">{data.kpis.lates}</span>
                      {data.kpis.lates > 0 && (
                        <span className="px-2 py-0.5 rounded-full bg-surface-container-high text-primary font-label-sm">
                          {data.kpis.lateJustified ? 'Justificada' : 'Pendiente'}
                        </span>
                      )}
                    </div>
                    <span className="font-body-sm text-outline">{data.kpis.lateNote}</span>
                  </div>
                </div>

                <div className="bg-surface-container-lowest rounded-2xl p-space-lg shadow-sm">
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-space-md">
                    <div className="flex items-start gap-space-md">
                      <div className={`w-14 h-14 rounded-2xl flex items-center justify-center ${positive ? 'bg-secondary-container/40 text-on-secondary-container' : 'bg-tertiary-fixed text-on-tertiary-fixed'}`}>
                        <Icon name={positive ? 'check_circle' : 'schedule'} filled className="text-[32px]" />
                      </div>
                      <div>
                        <div className="flex items-center gap-2">
                          <span className="font-label-sm uppercase text-outline font-semibold">Registro de hoy</span>
                          <span className={`px-2 py-0.5 rounded-full font-label-sm font-bold ${positive ? 'bg-secondary-container/50 text-on-secondary-container' : 'bg-tertiary-fixed text-on-tertiary-fixed'}`}>{today.statusLabel}</span>
                        </div>
                        <h3 className="font-headline-md text-on-surface font-bold mt-0.5">
                          {today.time ? `Ingreso marcado: ${today.time}` : 'Todavía no hay marcación de ingreso'}
                        </h3>
                        <p className="font-body-sm text-on-surface-variant mt-1">
                          {today.gate ? <>Verificado en <strong>{today.gate}</strong> mediante {today.method}.</> : data.shift.detail}
                        </p>
                      </div>
                    </div>
                    <div className="text-right">
                      <span className="font-label-sm text-outline block">Notificación familiar</span>
                      <span className={`font-label-md font-semibold ${today.notified ? 'text-secondary' : 'text-outline'}`}>
                        {today.notified ? 'Enviada al tutor' : 'Se envía al marcar'}
                      </span>
                    </div>
                  </div>
                  <div className="mt-space-md p-space-md rounded-xl bg-gradient-to-r from-primary/10 to-transparent flex items-center justify-between gap-3">
                    <div>
                      <span className="font-bold text-primary">{today.streak > 0 ? `Llevas ${today.streak} días lectivos seguidos a tiempo.` : 'Hoy no sumas racha de puntualidad.'}</span>
                      <p className="font-body-sm text-on-surface-variant">La racha se calcula con tus marcaciones reales del periodo.</p>
                    </div>
                    {today.rank && <span className="hidden md:inline-flex px-3 py-1 rounded-lg bg-surface-container-lowest text-primary font-bold">{today.rank}</span>}
                  </div>
                </div>

                <div className="bg-surface-container-lowest rounded-2xl p-space-lg shadow-sm">
                  <div className="flex items-center justify-between mb-space-md">
                    <div>
                      <span className="font-label-sm uppercase text-outline">Cronograma del día</span>
                      <h3 className="font-title-lg font-bold text-on-surface">{data.scheduleTitle}</h3>
                    </div>
                    <span className="font-label-md text-outline">{data.subjectCount} asignaturas</span>
                  </div>
                  <div className="space-y-space-sm">
                    {data.classes.length === 0 && <p className="text-outline font-body-sm">No hay clases programadas hoy.</p>}
                    {data.classes.map((item) => item.recess ? (
                      <div key={item.recess} className="flex items-center gap-3 py-1 px-4 text-outline font-label-sm">
                        <Icon name="coffee" className="text-[16px]" />
                        <span>{item.recess}</span>
                        <div className="flex-1 h-0.5 bg-surface-container" />
                      </div>
                    ) : (
                      <div key={item.name} className={`p-space-md rounded-xl flex items-center justify-between ${item.next ? 'bg-surface-container-high/60' : 'bg-surface-container-low'} ${item.done ? 'opacity-70' : ''}`}>
                        <div className="flex items-center gap-space-md">
                          <div className={`flex flex-col items-center justify-center w-14 py-1.5 rounded-lg ${item.next ? 'bg-primary-container text-on-primary' : 'bg-surface-container'}`}>
                            <span className="font-label-sm font-bold">{item.start}</span>
                            <span className="text-[10px]">{item.period}</span>
                          </div>
                          <div>
                            <div className="flex items-center gap-2 flex-wrap">
                              <h4 className="font-semibold text-on-surface">{item.name}</h4>
                              {item.badge && <span className="px-2 py-0.5 rounded-full bg-primary-container text-on-primary font-label-sm">{item.badge}</span>}
                            </div>
                            <p className="font-body-sm text-on-surface-variant">{item.place}</p>
                          </div>
                        </div>
                        <div className="text-right hidden sm:block">
                          <span className={`block font-label-md ${item.next ? 'text-primary font-semibold' : 'text-on-surface-variant'}`}>{item.range}</span>
                          <span className="font-label-sm text-outline">{item.note}</span>
                        </div>
                      </div>
                    ))}
                  </div>
                  <div className="mt-space-md pt-space-sm border-t border-surface-container flex items-center justify-between">
                    <span className="font-body-sm text-outline">Salida estimada: <strong className="text-on-surface">{data.exitLabel}</strong></span>
                    <button type="button" onClick={() => setWeeklyOpen(true)} className="font-label-md text-primary font-semibold flex items-center gap-1">
                      Ver horario semanal <Icon name="arrow_forward" className="text-[16px]" />
                    </button>
                  </div>
                </div>

                <div className="relative rounded-2xl overflow-hidden shadow-sm h-36 bg-surface-container">
                  <div className="w-full h-full bg-cover bg-center" style={{ backgroundImage: `url('${IMAGES.campus}')` }} />
                  <div className="absolute inset-0 bg-gradient-to-t from-inverse-surface/85 via-inverse-surface/40 to-transparent flex items-end p-space-md text-surface-bright">
                    <div>
                      <span className="font-label-sm uppercase">Ubicación del colegio</span>
                      <p className="font-bold">{data.campus.name}</p>
                      <p className="font-body-sm opacity-80">{data.campus.address}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </PageState>

      <Modal open={weeklyOpen} onClose={() => setWeeklyOpen(false)} maxWidth="max-w-2xl">
        <div className="p-space-lg">
          <div className="flex items-center justify-between mb-4">
            <h3 className="font-title-lg font-bold">Horario semanal</h3>
            <button type="button" onClick={() => setWeeklyOpen(false)}><Icon name="close" /></button>
          </div>
          <div className="grid gap-3">
            {data?.weekly?.map((day) => (
              <div key={day.day} className="bg-surface-container-low rounded-xl p-3">
                <p className="font-semibold text-primary">{day.day}</p>
                <ul className="mt-1 font-body-sm text-on-surface-variant">
                  {day.items.map((item) => <li key={item}>{item}</li>)}
                </ul>
              </div>
            ))}
          </div>
        </div>
      </Modal>

      <Modal open={rfidOpen} onClose={() => setRfidOpen(false)}>
        <div className="p-space-lg">
          <div className="flex items-center justify-between mb-3">
            <h3 className="font-title-lg font-bold">{data?.rfid?.title}</h3>
            <button type="button" onClick={() => setRfidOpen(false)}><Icon name="close" /></button>
          </div>
          <ol className="list-decimal pl-5 space-y-2 font-body-md text-on-surface-variant">
            {data?.rfid?.steps?.map((step) => <li key={step}>{step}</li>)}
          </ol>
        </div>
      </Modal>
      <Toast toast={toast} />
    </DashboardLayout>
  );
}
