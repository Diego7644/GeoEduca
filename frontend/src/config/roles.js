// Ruta de inicio por rol.
export const ROLE_HOME = {
  student: '/estudiante',
  parent: '/padre',
  staff: '/auxiliar',
};

// Módulos del Sidebar. `path: null` = módulo aún no implementado.
export const NAV_ITEMS = [
  { key: 'student', label: 'Mi Asistencia (Estudiante)', icon: 'badge', path: '/estudiante', roles: ['student'] },
  { key: 'parent', label: 'Portal Tutor / Familias', icon: 'escalator_warning', path: '/padre', roles: ['parent'] },
  { key: 'auxiliar', label: 'Panel Auxiliar / Contingencias', icon: 'verified_user', path: '/auxiliar', roles: ['staff'] },
  { key: 'reports', label: 'Reportes y Estadísticas', icon: 'monitoring', path: '/reportes' },
  { key: 'settings', label: 'Configuración del Colegio', icon: 'tune', path: '/configuracion' },
];
