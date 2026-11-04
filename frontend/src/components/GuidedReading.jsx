import { useEffect, useState } from 'react';
import { speakableText } from '../accessibility/preferences';
import { useAccessibility } from '../context/AccessibilityContext';

const BLOCKS = 'h1,h2,h3,p,li,td';

function blocks() {
  const root = document.getElementById('contenido');
  if (!root) return [];
  return [...root.querySelectorAll(BLOCKS)].filter((element) => {
    if (element.closest('[data-geo-private], .geo-a11y-panel, .geo-guided-bar')) return false;
    return speakableText(element.textContent).length > 1;
  });
}

export default function GuidedReading() {
  const { preferences } = useAccessibility();
  const [index, setIndex] = useState(0);
  const [status, setStatus] = useState('detenido');
  const supported = typeof window !== 'undefined' && 'speechSynthesis' in window;

  useEffect(() => {
    if (!preferences.guided) {
      document.querySelector('.geo-read-target')?.classList.remove('geo-read-target');
      window.speechSynthesis?.cancel();
    }
  }, [preferences.guided]);

  if (!preferences.guided) return null;

  const mark = (nextIndex) => {
    const items = blocks();
    document.querySelector('.geo-read-target')?.classList.remove('geo-read-target');
    const element = items[nextIndex];
    if (!element) return null;
    element.classList.add('geo-read-target');
    element.scrollIntoView({ block: 'center' });
    setIndex(nextIndex);
    return element;
  };

  const speak = (startAt = index) => {
    const element = mark(startAt);
    if (!supported) {
      setStatus('no disponible');
      return;
    }
    const text = speakableText(element?.textContent);
    if (!text) {
      setStatus('detenido');
      return;
    }
    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'es-PE';
    utterance.onend = () => {
      const items = blocks();
      if (startAt + 1 < items.length) speak(startAt + 1);
      else setStatus('detenido');
    };
    window.speechSynthesis.speak(utterance);
    setStatus('leyendo');
  };

  return (
    <div className="geo-guided-bar fixed bottom-24 left-1/2 z-[70] flex w-[min(100%-2rem,36rem)] -translate-x-1/2 flex-wrap items-center gap-2 rounded-2xl bg-inverse-surface p-3 text-inverse-on-surface shadow-xl" role="region" aria-label="Lectura guiada">
      <p className="font-body-sm">Bloque resaltado. La voz no lee contraseñas, DNI ni tokens.</p>
      {!supported && <p className="font-body-sm">Lectura en voz alta no disponible en este navegador.</p>}
      <button type="button" className="rounded-lg bg-primary px-3 text-on-primary" onClick={() => speak(0)}>Reproducir</button>
      <button type="button" className="rounded-lg bg-surface-container px-3 text-on-surface" onClick={() => { window.speechSynthesis?.pause(); setStatus('pausa'); }} disabled={!supported}>Pausar</button>
      <button type="button" className="rounded-lg bg-surface-container px-3 text-on-surface" onClick={() => { window.speechSynthesis?.resume(); setStatus('leyendo'); }} disabled={!supported}>Continuar</button>
      <button type="button" className="rounded-lg bg-surface-container px-3 text-on-surface" onClick={() => { window.speechSynthesis?.cancel(); setStatus('detenido'); }}>Detener</button>
      <span className="font-label-sm uppercase">{status}</span>
    </div>
  );
}
