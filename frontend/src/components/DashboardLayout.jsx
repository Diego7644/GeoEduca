import { useState } from 'react';
import Sidebar from './Sidebar';
import Header from './Header';

/** Layout compartido de los dashboards: Sidebar + Header + contenido. */
export default function DashboardLayout({ children }) {
  const [menuOpen, setMenuOpen] = useState(false);

  return (
    <div className="bg-surface text-on-surface">
      <Sidebar open={menuOpen} onClose={() => setMenuOpen(false)} />
      <div className="md:pl-64">
        <Header onMenuClick={() => setMenuOpen(true)} />
        <main className="relative w-full pt-16 bg-surface min-h-screen px-space-md lg:px-gutter py-space-lg">
          {children}
        </main>
      </div>
    </div>
  );
}
