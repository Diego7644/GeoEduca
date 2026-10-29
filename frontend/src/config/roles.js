// Ruta de inicio por rol.
export const ROLE_HOME = {
  student: '/estudiante',
  parent: '/padre',
  staff: '/auxiliar',
  admin: '/admin',
};

export function homeFor(user) {
  if (!user) return '/login';
  if (user.role === 'staff' && user.staffKind === 'docente') return '/docente';
  return ROLE_HOME[user.role] ?? '/login';
}

// Módulos del Sidebar. `path: null` = módulo aún no implementado.
export const NAV_ITEMS = [
  { key: 'student', label: 'Mi Asistencia (Estudiante)', icon: 'badge', path: '/estudiante', roles: ['student'] },
  { key: 'parent', label: 'Portal Tutor / Familias', icon: 'escalator_warning', path: '/padre', roles: ['parent'] },
  { key: 'auxiliar', label: 'Panel Auxiliar / Contingencias', icon: 'verified_user', path: '/auxiliar', roles: ['staff'], staffKinds: ['auxiliar'] },
  { key: 'docente', label: 'Mi aula', icon: 'school', path: '/docente', roles: ['staff'], staffKinds: ['docente'] },
  { key: 'admin', label: 'Administración', icon: 'admin_panel_settings', path: '/admin', roles: ['admin'] },
  { key: 'reports', label: 'Reportes y Estadísticas', icon: 'monitoring', path: '/reportes' },
  { key: 'settings', label: 'Configuración del Colegio', icon: 'tune', path: '/configuracion' },
];
