import { describe, expect, it } from 'vitest';
import { contrastRatio, TEXT_PAIRS } from './contrast';
import { applyPreferences, loadPreferences, resolveTheme, savePreferences, speakableText } from './preferences';

describe('preferencias de accesibilidad', () => {
  it('resuelve el tema automático con la preferencia del sistema', () => {
    expect(resolveTheme('auto', true)).toBe('dark');
    expect(resolveTheme('auto', false)).toBe('light');
    expect(resolveTheme('light', true)).toBe('light');
  });

  it('guarda y recupera la preferencia', () => {
    const memory = {
      value: null,
      getItem() { return this.value; },
      setItem(_key, next) { this.value = next; },
    };
    savePreferences({ theme: 'dark', textSize: 'xl' }, memory);
    expect(loadPreferences(memory).theme).toBe('dark');
    expect(loadPreferences(memory).textSize).toBe('xl');
    expect(loadPreferences(memory).guided).toBe(false);
  });

  it('aplica los atributos en el documento', () => {
    const root = document.createElement('html');
    applyPreferences({ theme: 'dark', contrast: 'high', textSize: 'lg', spacing: 'roomy', links: true, motion: 'reduce', easy: true, guided: true }, root, false);
    expect(root.dataset.theme).toBe('dark');
    expect(root.dataset.contrast).toBe('high');
    expect(root.dataset.textSize).toBe('lg');
    expect(root.dataset.guided).toBe('on');
  });

  it('no deja DNI ni tokens en el texto leído', () => {
    expect(speakableText('Alumno 88001122 token 11fb55e813c04ac1004852096d7d599b')).toBe('Alumno token');
  });
});

describe('contraste de los temas', () => {
  it('mantiene al menos 4.5:1 en el texto normal', () => {
    Object.values(TEXT_PAIRS).flat().forEach(([front, back]) => {
      expect(contrastRatio(front, back)).toBeGreaterThanOrEqual(4.5);
    });
  });
});
