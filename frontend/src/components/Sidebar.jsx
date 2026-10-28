import { NavLink } from 'react-router-dom';
import Icon from './Icon';
import SafeImage from './SafeImage';
import { useAuth } from '../context/AuthContext';
import { NAV_ITEMS } from '../config/roles';
import { IMAGES } from '../config/images';

const BASE = 'flex items-center gap-space-sm px-space-md py-2.5 rounded-lg transition-all';
const ACTIVE = 'bg-primary-container text-on-primary font-bold shadow-sm';
const IDLE = 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface font-body-md text-body-md';

/**
 * Sidebar reutilizable. En móvil funciona como drawer (`open` / `onClose`).
 * Muestra los módulos permitidos para el rol de la sesión.
 */
export default function Sidebar({ open = false, onClose = () => {} }) {
  const { user } = useAuth();
  const items = NAV_ITEMS.filter((item) => !item.roles || item.roles.includes(user?.role));

  return (
    <>
      {open && <div className="fixed inset-0 z-40 bg-inverse-surface/40 md:hidden" onClick={onClose} />}

      <aside
        className={`fixed left-0 top-0 h-full w-64 bg-surface-container-lowest shadow-[0_1px_8px_rgba(0,0,0,0.04)] z-50 flex flex-col justify-between transition-transform duration-200 md:translate-x-0 ${
          open ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        <div className="flex flex-col flex-1">
          <div className="h-16 px-space-md flex items-center gap-space-sm bg-surface-container-lowest">
            <SafeImage alt="GEOEDUCA Logo" className="h-8 w-auto object-contain" src={IMAGES.logo} />
            <div className="flex flex-col">
              <span className="font-headline-md text-title-lg text-primary leading-tight font-bold tracking-tight">
                GEOEDUCA
              </span>
              <span className="font-label-sm text-label-sm text-outline uppercase tracking-wider">Control Escolar</span>
            </div>
          </div>

          <div className="px-space-md py-space-sm">
            <p className="font-label-sm text-label-sm text-outline uppercase tracking-wider px-space-xs mb-space-xs">
              Módulos Principales
            </p>
          </div>

          <nav className="flex-1 px-space-md space-y-1">
            {items.map((item) =>
              item.path ? (
                <NavLink
                  key={item.key}
                  to={item.path}
                  onClick={onClose}
                  className={({ isActive }) => `${BASE} ${isActive ? ACTIVE : IDLE}`}
                >
                  <Icon name={item.icon} className="text-[20px]" />
                  <span>{item.label}</span>
                </NavLink>
              ) : (
                <span
                  key={item.key}
                  title="Próximamente"
                  className={`${BASE} ${IDLE} opacity-50 cursor-not-allowed hover:bg-transparent`}
                >
                  <Icon name={item.icon} className="text-[20px]" />
                  <span>{item.label}</span>
                </span>
              )
            )}
          </nav>
        </div>

        <div className="p-space-md">
          <div className="bg-surface-container-low rounded-xl p-space-md flex flex-col gap-space-xs">
            <div className="flex items-center gap-space-xs text-primary">
              <Icon name="domain" className="text-[18px]" />
              <span className="font-label-md text-label-md uppercase tracking-wider font-semibold">Sede Académica</span>
            </div>
            <div className="font-title-lg text-body-md font-semibold text-on-surface leading-tight">
              Colegio San Gabriel
            </div>
            <div className="flex items-center justify-between text-outline font-label-sm text-label-sm pt-space-xs">
              <span>Sede Central</span>
              <span className="bg-surface-container-highest text-primary font-semibold px-1.5 py-0.5 rounded">2025</span>
            </div>
          </div>
        </div>
      </aside>
    </>
  );
}
