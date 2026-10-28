import { useCallback, useEffect, useState } from 'react';
import Icon from './Icon';
import { getNotificaciones, markNotificacionesRead } from '../services/asistenciaService';

export default function NotificationBell() {
  const [open, setOpen] = useState(false);
  const [items, setItems] = useState([]);

  const load = useCallback(async () => {
    try {
      setItems(await getNotificaciones());
    } catch {
      setItems([]);
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const unread = items.filter((item) => !item.read).length;

  const toggle = async () => {
    const next = !open;
    setOpen(next);
    if (next && unread) {
      try {
        setItems(await markNotificacionesRead());
      } catch {
        // La lista sigue visible aunque el marcado falle.
      }
    }
  };

  return (
    <div className="relative">
      <button type="button" onClick={toggle} aria-label="Notificaciones" className="p-2 rounded-full text-on-surface-variant hover:bg-surface-container relative">
        <Icon name="notifications" className="text-[22px]" />
        {unread > 0 && (
          <span className="absolute top-1 right-1 flex h-4 min-w-4 px-1 items-center justify-center rounded-full bg-error text-[10px] font-bold text-on-error">
            {unread}
          </span>
        )}
      </button>
      {open && (
        <div className="absolute right-0 mt-2 w-80 max-w-[90vw] rounded-xl bg-surface-container-lowest shadow-xl z-50 p-3">
          <p className="font-semibold text-on-surface mb-2">Notificaciones</p>
          {items.length === 0 && <p className="text-outline font-body-sm">No hay avisos.</p>}
          <ul className="max-h-72 overflow-auto space-y-2">
            {items.map((item) => (
              <li key={item.id} className="rounded-lg bg-surface-container-low px-3 py-2">
                <p className="font-semibold text-on-surface">{item.title}</p>
                <p className="font-body-sm text-on-surface-variant">{item.body}</p>
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  );
}
