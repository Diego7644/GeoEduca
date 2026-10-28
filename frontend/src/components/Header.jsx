import { useNavigate } from 'react-router-dom';
import Icon from './Icon';
import SafeImage from './SafeImage';
import { useAuth } from '../context/AuthContext';
import { IMAGES } from '../config/images';

export default function Header({ onMenuClick }) {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login', { replace: true });
  };

  return (
    <header className="fixed top-0 left-0 md:left-64 right-0 h-16 bg-surface-container-lowest/90 backdrop-blur-xl shadow-[0_1px_8px_rgba(0,0,0,0.04)] z-30 flex items-center justify-between px-space-md lg:px-space-lg">
      <div className="flex items-center gap-space-md">
        <button
          type="button"
          onClick={onMenuClick}
          aria-label="Abrir menú"
          className="md:hidden flex items-center justify-center p-2 rounded-lg text-on-surface-variant hover:bg-surface-container hover:text-on-surface"
        >
          <Icon name="menu" className="text-[24px]" />
        </button>
        <div className="flex items-center gap-2">
          <SafeImage alt="GEOEDUCA Logo" className="h-7 w-auto object-contain md:hidden" src={IMAGES.logo} />
          <div className="hidden sm:flex items-center gap-space-xs font-body-sm text-body-sm text-outline">
            <span>GEOEDUCA</span>
            <Icon name="chevron_right" className="text-[16px]" />
            <span className="text-primary font-semibold">Panel Institucional</span>
          </div>
        </div>
      </div>

      <div className="hidden xl:flex items-center gap-2 px-3 py-1.5 rounded-full bg-secondary-container/30">
        <span className="relative flex h-2.5 w-2.5">
          <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-secondary opacity-75" />
          <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-secondary" />
        </span>
        <span className="font-label-md text-label-md text-on-secondary-container font-semibold">
          Geo-Verificación Activa: Perímetro Escolar 50m (GPS OK)
        </span>
      </div>

      <div className="flex items-center gap-space-md">
        <div className="hidden lg:flex flex-col text-right">
          <span className="font-label-md text-label-md font-semibold text-on-surface">Lunes, 24 Octubre 2025</span>
          <span className="font-label-sm text-label-sm text-outline font-mono">07:42 AM (Sincronizado)</span>
        </div>

        <button
          type="button"
          className="p-2 rounded-full text-on-surface-variant hover:bg-surface-container hover:text-on-surface transition-colors relative"
        >
          <Icon name="notifications" className="text-[22px]" />
          <span className="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-error text-[10px] font-bold text-on-error">
            3
          </span>
        </button>

        <div className="flex items-center gap-space-sm pl-space-sm">
          <SafeImage
            alt={user?.name ?? 'Perfil'}
            className="w-8 h-8 rounded-full object-cover ring-2 ring-surface-container-high"
            src={IMAGES.adminProfile}
          />
          <div className="hidden md:flex flex-col text-left">
            <span className="font-title-lg text-body-sm font-semibold text-on-surface leading-snug">{user?.name}</span>
            <span className="font-label-sm text-label-sm text-primary leading-none">{user?.roleLabel}</span>
          </div>
          <button
            type="button"
            onClick={handleLogout}
            title="Cerrar sesión"
            aria-label="Cerrar sesión"
            className="p-2 rounded-full text-on-surface-variant hover:bg-surface-container hover:text-error transition-colors"
          >
            <Icon name="logout" className="text-[20px]" />
          </button>
        </div>
      </div>
    </header>
  );
}
