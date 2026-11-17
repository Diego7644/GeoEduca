import { useEffect, useRef } from 'react';

/** Overlay modal. Cierra con Escape, con el fondo o al llegar al borde del foco. */
export default function Modal({ open, onClose, children, maxWidth = 'max-w-lg', label = 'Ventana' }) {
  const dialogRef = useRef(null);
  const previous = useRef(null);

  useEffect(() => {
    if (!open) return undefined;
    previous.current = document.activeElement;
    const node = dialogRef.current;
    const focusable = () => [...node.querySelectorAll('button, a, input, select, textarea')];
    (focusable()[0] || node).focus();
    const onKey = (event) => {
      if (event.key === 'Escape') onClose();
      if (event.key !== 'Tab') return;
      const items = focusable();
      if (items.length === 0) return;
      const first = items[0];
      const last = items[items.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    };
    document.addEventListener('keydown', onKey);
    return () => {
      document.removeEventListener('keydown', onKey);
      previous.current?.focus?.();
    };
  }, [open, onClose]);

  if (!open) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <button
        type="button"
        aria-label="Cerrar"
        className="absolute inset-0 bg-inverse-surface/50 backdrop-blur-sm cursor-default"
        onClick={onClose}
      />
      <dialog
        ref={dialogRef}
        open
        aria-modal="true"
        aria-label={label}
        tabIndex={-1}
        className={`relative m-0 p-0 border-0 text-[inherit] bg-surface-container-lowest w-full ${maxWidth} rounded-2xl shadow-2xl overflow-hidden`}
      >
        {children}
      </dialog>
    </div>
  );
}
