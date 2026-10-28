import { useEffect, useState } from 'react';
import DashboardLayout from '../components/DashboardLayout';
import Icon from '../components/Icon';
import SafeImage from '../components/SafeImage';
import { IMAGES } from '../config/images';

const TOTAL_TIME = 60;
const CIRCUMFERENCE = 2 * Math.PI * 20;

const DARK = '#003692';
const LIGHT = '#004BC3';
// [x, y, width, height, color]
const QR_MODULES = [
  [88, 24, 12, 12, DARK], [108, 24, 20, 12, LIGHT], [136, 24, 16, 12, DARK],
  [88, 44, 24, 12, LIGHT], [124, 44, 12, 12, DARK], [144, 44, 12, 12, LIGHT],
  [24, 88, 16, 12, LIGHT], [48, 88, 28, 12, DARK], [24, 108, 20, 20, DARK],
  [52, 112, 24, 16, LIGHT], [24, 136, 36, 12, LIGHT],
  [88, 88, 16, 16, DARK], [136, 88, 16, 16, LIGHT], [88, 136, 16, 16, LIGHT], [136, 136, 16, 16, DARK],
  [164, 88, 24, 12, DARK], [196, 88, 24, 12, LIGHT], [164, 108, 16, 24, LIGHT],
  [188, 108, 32, 16, DARK], [172, 132, 24, 16, LIGHT], [204, 136, 16, 12, DARK],
  [88, 164, 20, 24, DARK], [116, 164, 32, 12, LIGHT], [92, 196, 24, 20, LIGHT],
  [124, 184, 16, 32, DARK], [148, 172, 20, 20, LIGHT], [176, 168, 44, 12, DARK],
  [176, 188, 16, 28, LIGHT], [200, 188, 20, 28, DARK],
];
const QR_FINDERS = [[20, 20], [164, 20], [20, 164]];

function QrGraphic({ dimmed }) {
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
          <rect fill={DARK} height="56" rx="8" width="56" x={x} y={y} />
          <rect fill="#FFFFFF" height="40" rx="4" width="40" x={x + 8} y={y + 8} />
          <rect fill={DARK} height="24" rx="3" width="24" x={x + 16} y={y + 16} />
        </g>
      ))}
      {QR_MODULES.map(([x, y, w, h, fill]) => (
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

const KPI = 'bg-surface-container-lowest rounded-xl p-space-md shadow-sm flex flex-col justify-between';

const CLASSES = [
  {
    start: '08:00', period: 'AM', name: 'Física Elemental', place: 'Laboratorio 3 (Pabellón B) • Prof. Héctor Vega',
    range: '08:00 - 09:30', note: '90 minutos', next: true,
  },
  {
    start: '09:30', period: 'AM', name: 'Matemáticas Avanzadas', place: 'Aula 402 • Prof. Lucía Santander',
    range: '09:30 - 11:00', note: 'Evaluación corta',
  },
  { recess: '11:00 AM - 11:30 AM • Receso General & Refrigerio' },
  {
    start: '11:30', period: 'AM', name: 'Literatura Universal & Redacción', place: 'Aula 402 • Prof. Fernando Quiroga',
    range: '11:30 - 01:00', note: '90 minutos',
  },
];

export default function DashboardEstudiante() {
  const [timeLeft, setTimeLeft] = useState(42);
  const [flash, setFlash] = useState(false);
  const [refreshing, setRefreshing] = useState(false);

  useEffect(() => {
    const id = setInterval(() => setTimeLeft((t) => (t <= 1 ? TOTAL_TIME : t - 1)), 1000);
    return () => clearInterval(id);
  }, []);

  // Parpadeo del QR cada vez que el token se renueva.
  useEffect(() => {
    if (timeLeft !== TOTAL_TIME) return undefined;
    setFlash(true);
    const id = setTimeout(() => setFlash(false), 250);
    return () => clearTimeout(id);
  }, [timeLeft]);

  const handleRefresh = () => {
    setTimeLeft(TOTAL_TIME);
    setRefreshing(true);
    setTimeout(() => setRefreshing(false), 800);
  };

  const offset = CIRCUMFERENCE - (timeLeft / TOTAL_TIME) * CIRCUMFERENCE;

  return (
    <DashboardLayout>
      <div className="flex flex-col w-full">
        {/* Welcome & status header */}
        <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-space-md mb-space-lg">
          <div className="flex items-center gap-space-md">
            <div className="relative">
              <SafeImage
                alt="Mateo Valenzuela"
                className="w-16 h-16 rounded-2xl object-cover shadow-sm bg-surface-container"
                src={IMAGES.studentMateo}
              />
              <div className="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-secondary flex items-center justify-center text-on-secondary shadow-sm">
                <Icon name="check" filled className="text-[13px]" />
              </div>
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h1 className="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight">
                  ¡Buenos días, Mateo Valenzuela!
                </h1>
                <span className="px-2 py-0.5 rounded-full bg-primary-container/10 text-primary font-label-sm text-label-sm font-semibold hidden sm:inline-block">
                  ID #STU-88219
                </span>
              </div>
              <p className="font-body-md text-body-md text-on-surface-variant flex items-center gap-2 mt-0.5">
                <span>Estudiante de 4to Secundaria • Sección B</span>
                <span className="w-1 h-1 rounded-full bg-outline" />
                <span className="text-primary font-semibold">Tutor: Prof. R. Morales</span>
              </p>
            </div>
          </div>

          <div className="flex items-center gap-space-sm px-space-md py-space-sm bg-surface-container-low rounded-xl shadow-sm self-start lg:self-center">
            <div className="w-2.5 h-2.5 rounded-full bg-secondary animate-pulse" />
            <div className="flex flex-col">
              <span className="font-label-sm text-label-sm uppercase tracking-wider text-outline">
                Jornada Matutina en Curso
              </span>
              <span className="font-title-lg text-body-sm font-semibold text-on-surface">
                Límite regular: <span className="text-primary font-bold">08:00 AM</span> (Faltan 16 min)
              </span>
            </div>
          </div>
        </div>

        {/* Main grid */}
        <div className="grid grid-cols-1 xl:grid-cols-12 gap-gutter items-start">
          {/* LEFT: QR token & geolocation */}
          <div className="xl:col-span-5 flex flex-col gap-space-lg">
            <div className="bg-surface-container-lowest rounded-2xl p-space-lg shadow-sm flex flex-col items-center relative overflow-hidden">
              <div className="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-primary via-primary-container to-secondary" />

              <div className="w-full flex items-center justify-between mb-space-md">
                <div className="flex flex-col">
                  <span className="font-label-sm text-label-sm text-outline uppercase tracking-wider">
                    Credencial Dinámica
                  </span>
                  <h2 className="font-title-lg text-title-lg text-on-surface font-bold">Tu Token de Asistencia</h2>
                </div>
                <div className="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-secondary-container/40 text-on-secondary-container">
                  <Icon name="shield" filled className="text-[15px]" />
                  <span className="font-label-sm text-label-sm font-semibold">Anti-captura Activa</span>
                </div>
              </div>

              <p className="font-body-sm text-body-sm text-on-surface-variant text-center mb-space-md">
                Presenta este código en los torniquetes o al lector óptico del auxiliar en puerta.
              </p>

              <div className="relative p-space-md bg-surface-container-lowest rounded-2xl shadow-md flex items-center justify-center">
                <div className="absolute inset-x-4 top-4 h-0.5 bg-gradient-to-r from-transparent via-primary to-transparent opacity-80 animate-pulse pointer-events-none" />
                <QrGraphic dimmed={flash} />
              </div>

              <div className="flex items-center gap-space-md mt-space-md py-2 px-space-md bg-surface-container-low rounded-xl w-full">
                <div className="relative w-12 h-12 flex-shrink-0 flex items-center justify-center">
                  <svg className="w-12 h-12 transform -rotate-90" viewBox="0 0 48 48">
                    <circle className="text-surface-container-highest" cx="24" cy="24" fill="none" r="20" stroke="currentColor" strokeWidth="3.5" />
                    <circle
                      className="text-primary-container transition-all duration-1000 ease-linear"
                      cx="24" cy="24" fill="none" r="20" stroke="currentColor"
                      strokeDasharray={CIRCUMFERENCE} strokeDashoffset={offset}
                      strokeLinecap="round" strokeWidth="3.5"
                    />
                  </svg>
                  <span className="absolute font-headline-md text-label-md font-bold text-primary">
                    {String(timeLeft).padStart(2, '0')}s
                  </span>
                </div>
                <div className="flex flex-col flex-1 min-w-0">
                  <span className="font-label-md text-label-md text-on-surface font-semibold">Rotación de Cripto-Token</span>
                  <span className="font-body-sm text-body-sm text-outline truncate">
                    Se renueva cada 60 seg para evitar capturas
                  </span>
                </div>
              </div>

              <button
                type="button"
                onClick={handleRefresh}
                className="w-full mt-space-md h-10 px-space-md rounded-lg bg-primary-container hover:bg-primary text-on-primary font-title-lg text-body-sm font-semibold flex items-center justify-center gap-space-xs transition-colors shadow-sm active:scale-[0.99]"
              >
                <Icon name={refreshing ? 'refresh' : 'autorenew'} className={`text-[18px] ${refreshing ? 'animate-spin' : ''}`} />
                <span>{refreshing ? 'Actualizado...' : 'Generar Nuevo Código QR'}</span>
              </button>

              <div className="w-full mt-space-md p-space-sm bg-surface-container rounded-xl flex items-start gap-space-sm">
                <div className="p-2 rounded-lg bg-secondary-container/40 text-on-secondary-container mt-0.5">
                  <Icon name="near_me" filled className="text-[20px]" />
                </div>
                <div className="flex flex-col flex-1">
                  <div className="flex items-center justify-between">
                    <span className="font-label-sm text-label-sm font-bold text-on-surface uppercase tracking-wide">
                      GPS del Dispositivo
                    </span>
                    <span className="font-label-sm text-label-sm text-secondary font-semibold">Dentro del Rango</span>
                  </div>
                  <p className="font-body-sm text-body-sm text-on-surface-variant mt-0.5 leading-snug">
                    Campus Principal • Puerta 2 peatonal (
                    <span className="font-semibold text-on-surface">a 12 metros de distancia</span>)
                  </p>
                </div>
              </div>
            </div>

            <div className="bg-surface-container-low rounded-xl p-space-md flex items-center justify-between shadow-sm">
              <div className="flex items-center gap-space-sm">
                <Icon name="contactless" className="text-primary text-[24px]" />
                <div>
                  <span className="font-title-lg text-body-sm font-semibold text-on-surface block leading-tight">
                    ¿Sin batería o sin señal?
                  </span>
                  <span className="font-body-sm text-body-sm text-outline">
                    Puedes usar tu carnet RFID físico con el Auxiliar.
                  </span>
                </div>
              </div>
              <button
                type="button"
                className="px-3 py-1.5 rounded-lg bg-surface-container-highest text-primary font-label-md text-label-md font-semibold hover:bg-surface-container-high transition-colors"
              >
                Info
              </button>
            </div>
          </div>

          {/* RIGHT: metrics, today's check-in, schedule */}
          <div className="xl:col-span-7 flex flex-col gap-space-lg">
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-space-md">
              <div className={KPI}>
                <div className="flex items-center justify-between mb-space-xs">
                  <span className="font-label-sm text-label-sm text-outline uppercase tracking-wider">Puntualidad</span>
                  <Icon name="timelapse" className="text-secondary text-[20px]" />
                </div>
                <div className="flex items-baseline gap-2">
                  <span className="font-metric-display text-metric-display text-on-surface font-bold">96.4%</span>
                  <span className="font-label-sm text-label-sm text-secondary font-bold flex items-center">
                    <Icon name="arrow_upward" className="text-[14px]" />2.1%
                  </span>
                </div>
                <span className="font-body-sm text-body-sm text-outline mt-space-xs">Rango del mes (Octubre)</span>
              </div>

              <div className={KPI}>
                <div className="flex items-center justify-between mb-space-xs">
                  <span className="font-label-sm text-label-sm text-outline uppercase tracking-wider">Asistencias</span>
                  <Icon name="event_available" className="text-primary text-[20px]" />
                </div>
                <div className="flex items-baseline gap-2">
                  <span className="font-metric-display text-metric-display text-on-surface font-bold">18</span>
                  <span className="font-body-sm text-body-sm text-outline">/ 19 hábiles</span>
                </div>
                <span className="font-body-sm text-body-sm text-secondary font-medium mt-space-xs">
                  0 Faltas Injustificadas
                </span>
              </div>

              <div className={KPI}>
                <div className="flex items-center justify-between mb-space-xs">
                  <span className="font-label-sm text-label-sm text-outline uppercase tracking-wider">Tardanzas</span>
                  <Icon name="history_toggle_off" className="text-tertiary-fixed-dim text-[20px]" />
                </div>
                <div className="flex items-baseline gap-2">
                  <span className="font-metric-display text-metric-display text-on-surface font-bold">1</span>
                  <span className="px-2 py-0.5 rounded-full bg-surface-container-high text-primary font-label-sm text-label-sm">
                    Justificada
                  </span>
                </div>
                <span className="font-body-sm text-body-sm text-outline mt-space-xs">14 Oct (Tráfico vehicular)</span>
              </div>
            </div>

            {/* Today's check-in */}
            <div className="bg-surface-container-lowest rounded-2xl p-space-lg shadow-sm relative overflow-hidden">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-space-md">
                <div className="flex items-start gap-space-md">
                  <div className="w-14 h-14 rounded-2xl bg-secondary-container/40 text-on-secondary-container flex items-center justify-center flex-shrink-0 shadow-sm">
                    <Icon name="check_circle" filled className="text-[32px]" />
                  </div>
                  <div className="flex flex-col">
                    <div className="flex items-center gap-2">
                      <span className="font-label-sm text-label-sm uppercase tracking-wider text-outline font-semibold">
                        Registro de Hoy
                      </span>
                      <span className="px-2 py-0.5 rounded-full bg-secondary-container/50 text-on-secondary-container font-label-sm text-label-sm font-bold">
                        A Tiempo
                      </span>
                    </div>
                    <h3 className="font-headline-md text-headline-md text-on-surface font-bold mt-0.5">
                      Ingreso Marcado: 07:44 AM
                    </h3>
                    <p className="font-body-sm text-body-sm text-on-surface-variant mt-1">
                      Verificado en <strong className="text-on-surface">Torniquete #03 - Acceso Norte</strong> mediante
                      código QR dinámico.
                    </p>
                  </div>
                </div>
                <div className="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center pt-2 sm:pt-0 border-t sm:border-t-0 border-surface-container">
                  <span className="font-label-sm text-label-sm text-outline">Notificación Familiar</span>
                  <span className="inline-flex items-center gap-1 font-label-md text-label-md text-secondary font-semibold">
                    <Icon name="done_all" className="text-[16px]" /> Enviada a Tutor
                  </span>
                </div>
              </div>

              <div className="mt-space-md p-space-md rounded-xl bg-gradient-to-r from-primary/10 via-primary-container/5 to-transparent flex items-center justify-between">
                <div className="flex items-center gap-space-sm">
                  <div className="w-8 h-8 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold">
                    <Icon name="local_fire_department" className="text-[18px]" />
                  </div>
                  <div className="flex flex-col">
                    <span className="font-title-lg text-body-sm font-bold text-primary">¡Gran racha de puntualidad!</span>
                    <span className="font-body-sm text-body-sm text-on-surface-variant">
                      Llevas 12 días lectivos consecutivos llegando a tiempo.
                    </span>
                  </div>
                </div>
                <span className="hidden md:inline-flex px-3 py-1 rounded-lg bg-surface-container-lowest text-primary font-label-md text-label-md font-bold shadow-sm">
                  Top 5% del Aula
                </span>
              </div>
            </div>

            {/* Schedule */}
            <div className="bg-surface-container-lowest rounded-2xl p-space-lg shadow-sm flex flex-col">
              <div className="flex items-center justify-between mb-space-md">
                <div className="flex flex-col">
                  <span className="font-label-sm text-label-sm text-outline uppercase tracking-wider">Cronograma del Día</span>
                  <h3 className="font-title-lg text-title-lg text-on-surface font-bold">Horario de Clases • Lunes 24</h3>
                </div>
                <span className="font-label-md text-label-md text-outline">5 Asignaturas programadas</span>
              </div>

              <div className="space-y-space-sm">
                {CLASSES.map((c) =>
                  c.recess ? (
                    <div key="recess" className="flex items-center gap-3 py-1 px-4 text-outline font-label-sm text-label-sm">
                      <Icon name="coffee" className="text-[16px]" />
                      <span>{c.recess}</span>
                      <div className="flex-1 h-0.5 bg-surface-container" />
                    </div>
                  ) : (
                    <div
                      key={c.name}
                      className={`p-space-md rounded-xl flex items-center justify-between ${
                        c.next ? 'bg-surface-container-high/60' : 'bg-surface-container-low hover:bg-surface-container transition-colors'
                      }`}
                    >
                      <div className="flex items-center gap-space-md">
                        <div
                          className={`flex flex-col items-center justify-center w-14 py-1.5 rounded-lg ${
                            c.next ? 'bg-primary-container text-on-primary' : 'bg-surface-container text-on-surface'
                          }`}
                        >
                          <span className="font-label-sm text-label-sm font-bold">{c.start}</span>
                          <span className={`font-label-sm text-[10px] ${c.next ? 'opacity-80' : 'text-outline'}`}>{c.period}</span>
                        </div>
                        <div>
                          <div className="flex items-center gap-2">
                            <h4 className={`font-title-lg text-body-md ${c.next ? 'font-bold' : 'font-semibold'} text-on-surface`}>
                              {c.name}
                            </h4>
                            {c.next && (
                              <span className="px-2 py-0.5 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm font-semibold animate-pulse">
                                Próxima Clase
                              </span>
                            )}
                          </div>
                          <p className="font-body-sm text-body-sm text-on-surface-variant mt-0.5">{c.place}</p>
                        </div>
                      </div>
                      <div className="text-right hidden sm:block">
                        <span
                          className={`font-label-md text-label-md block ${
                            c.next ? 'text-primary font-semibold' : 'text-on-surface-variant font-medium'
                          }`}
                        >
                          {c.range}
                        </span>
                        <span className="font-label-sm text-label-sm text-outline">{c.note}</span>
                      </div>
                    </div>
                  )
                )}
              </div>

              <div className="mt-space-md pt-space-sm border-t border-surface-container flex items-center justify-between text-body-sm">
                <span className="font-body-sm text-body-sm text-outline">
                  Salida estimada: <strong className="text-on-surface">02:30 PM</strong>
                </span>
                <button type="button" className="font-label-md text-label-md text-primary font-semibold hover:underline flex items-center gap-1">
                  <span>Ver horario semanal completo</span>
                  <Icon name="arrow_forward" className="text-[16px]" />
                </button>
              </div>
            </div>

            {/* Campus location */}
            <div className="relative rounded-2xl overflow-hidden shadow-sm h-36 bg-surface-container">
              <div
                className="w-full h-full bg-cover bg-center"
                style={{ backgroundImage: `url('${IMAGES.campus}')` }}
              />
              <div className="absolute inset-0 bg-gradient-to-t from-inverse-surface/85 via-inverse-surface/40 to-transparent flex items-end p-space-md text-on-tertiary">
                <div className="flex items-center justify-between w-full">
                  <div>
                    <span className="font-label-sm text-label-sm uppercase tracking-wider text-secondary-fixed opacity-90">
                      Ubicación Actual
                    </span>
                    <p className="font-title-lg text-body-md font-bold text-surface-bright leading-tight">
                      Colegio San Gabriel • Sede Central
                    </p>
                    <p className="font-body-sm text-body-sm text-surface-variant opacity-80">
                      Av. Las Palmeras 450, Urb. Monterrico
                    </p>
                  </div>
                  <Icon name="place" className="text-[28px] text-surface-bright" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </DashboardLayout>
  );
}
