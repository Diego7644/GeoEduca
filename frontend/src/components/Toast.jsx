import { useCallback, useEffect, useRef, useState } from 'react';
import Icon from './Icon';

/** Hook: const { toast, showToast } = useToast(); showToast('Título', 'Descripción opcional') */
export function useToast(duration = 3500) {
  const [toast, setToast] = useState({ visible: false, title: '', description: '' });
  const timer = useRef(null);

  const showToast = useCallback(
    (title, description = '') => {
      clearTimeout(timer.current);
      setToast({ visible: true, title, description });
      timer.current = setTimeout(() => setToast((t) => ({ ...t, visible: false })), duration);
    },
    [duration]
  );

  useEffect(() => () => clearTimeout(timer.current), []);

  return { toast, showToast };
}

export default function Toast({ toast }) {
  return (
    <div
      className={`fixed bottom-6 right-6 z-[60] pointer-events-none transition-all duration-300 transform ${
        toast.visible ? 'translate-y-0 opacity-100' : 'translate-y-24 opacity-0'
      }`}
    >
      <div className="bg-inverse-surface text-inverse-on-surface px-4 py-3 rounded-xl shadow-xl flex items-center gap-3 max-w-sm">
        <Icon name="check_circle" filled className="text-secondary-fixed-dim text-[24px]" />
        <div className="flex flex-col">
          <span className="font-title-lg text-body-sm font-bold leading-tight">{toast.title}</span>
          {toast.description && (
            <span className="font-body-sm text-label-sm text-inverse-on-surface/80">{toast.description}</span>
          )}
        </div>
      </div>
    </div>
  );
}
