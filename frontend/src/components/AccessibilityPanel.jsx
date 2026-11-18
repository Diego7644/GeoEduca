import { useEffect, useId, useRef, useState } from 'react';
import Icon from './Icon';
import { useAccessibility } from '../context/AccessibilityContext';

const SIZES = [
  ['md', 'Normal'],
  ['lg', 'Grande'],
  ['xl', 'Muy grande'],
  ['xxl', 'Máximo'],
];

export default function AccessibilityPanel() {
  const panelId = useId();
  const titleId = useId();
  const buttonRef = useRef(null);
  const panelRef = useRef(null);
  const { preferences, setPreference, reset, toggleTheme } = useAccessibility();
  const [open, setOpen] = useState(false);

  useEffect(() => {
    if (!open) return undefined;
    const node = panelRef.current;
    const focusable = () => [...node.querySelectorAll('button, select')];
    focusable()[0]?.focus();
    const onKey = (event) => {
      if (event.key === 'Escape') {
        setOpen(false);
        buttonRef.current?.focus();
      }
      if (event.key !== 'Tab') return;
      const items = focusable();
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
    node.addEventListener('keydown', onKey);
    return () => node.removeEventListener('keydown', onKey);
  }, [open, setOpen]);

  return (
    <>
      <button
        ref={buttonRef}
        type="button"
        className="geo-a11y-button fixed bottom-4 left-4 z-[75] flex items-center justify-center rounded-full bg-primary text-on-primary shadow-lg"
        aria-label="Opciones de accesibilidad"
        aria-expanded={open}
        aria-controls={panelId}
        onClick={() => setOpen((value) => !value)}
      >
        <Icon name="accessibility_new" className="text-[28px]" />
      </button>
      {open && (
        <div
          ref={panelRef}
          id={panelId}
          role="dialog"
          aria-modal="true"
          aria-labelledby={titleId}
          className="geo-a11y-panel fixed bottom-20 left-4 z-[75] flex max-h-[70vh] w-[min(100%-2rem,22rem)] flex-col gap-3 overflow-y-auto rounded-2xl bg-surface-container-lowest p-4 text-on-surface shadow-2xl"
        >
          <h2 id={titleId} className="font-title-lg font-bold">GeoEduca inclusivo</h2>
          <label className="flex flex-col gap-1 font-body-sm">
            Tema
            <select value={preferences.theme} onChange={(event) => setPreference('theme', event.target.value)} className="rounded-lg bg-surface-container-low px-3">
              <option value="light">Claro</option>
              <option value="dark">Oscuro</option>
              <option value="auto">Automático</option>
            </select>
          </label>
          <button type="button" className="rounded-lg bg-primary px-3 text-on-primary" onClick={toggleTheme}>Cambiar claro/oscuro</button>
          <button type="button" aria-pressed={preferences.contrast === 'high'} className="rounded-lg bg-surface-container px-3" onClick={() => setPreference('contrast', preferences.contrast === 'high' ? 'normal' : 'high')}>Alto contraste</button>
          <div className="flex flex-wrap gap-2" role="group" aria-label="Tamaño del texto">
            {SIZES.map(([value, label]) => (
              <button key={value} type="button" aria-pressed={preferences.textSize === value} className="rounded-lg bg-surface-container px-3" onClick={() => setPreference('textSize', value)}>{label}</button>
            ))}
          </div>
          <button type="button" aria-pressed={preferences.spacing === 'roomy'} className="rounded-lg bg-surface-container px-3" onClick={() => setPreference('spacing', preferences.spacing === 'roomy' ? 'normal' : 'roomy')}>Más espacio entre letras y líneas</button>
          <button type="button" aria-pressed={preferences.links} className="rounded-lg bg-surface-container px-3" onClick={() => setPreference('links', !preferences.links)}>Resaltar enlaces</button>
          <button type="button" aria-pressed={preferences.motion === 'reduce'} className="rounded-lg bg-surface-container px-3" onClick={() => setPreference('motion', preferences.motion === 'reduce' ? 'system' : 'reduce')}>Reducir animaciones</button>
          <button type="button" aria-pressed={preferences.easy} className="rounded-lg bg-surface-container px-3" onClick={() => setPreference('easy', !preferences.easy)}>Lectura fácil</button>
          <button type="button" aria-pressed={preferences.guided} className="rounded-lg bg-surface-container px-3" onClick={() => setPreference('guided', !preferences.guided)}>Modo lectura guiada</button>
          <button type="button" className="rounded-lg bg-surface-container px-3" onClick={reset}>Restablecer</button>
        </div>
      )}
    </>
  );
}
