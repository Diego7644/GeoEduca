export const STORAGE_KEY = 'geoeduca-a11y';

export const DEFAULT_PREFERENCES = {
  theme: 'auto',
  contrast: 'normal',
  textSize: 'md',
  spacing: 'normal',
  links: false,
  motion: 'system',
  easy: false,
  guided: false,
};

export function resolveTheme(choice, prefersDark) {
  if (choice === 'dark') return 'dark';
  if (choice === 'light') return 'light';
  return prefersDark ? 'dark' : 'light';
}

export function loadPreferences(storage = globalThis.localStorage) {
  try {
    const raw = storage?.getItem(STORAGE_KEY);
    if (!raw) return { ...DEFAULT_PREFERENCES };
    const parsed = JSON.parse(raw);
    return { ...DEFAULT_PREFERENCES, ...parsed };
  } catch {
    return { ...DEFAULT_PREFERENCES };
  }
}

export function savePreferences(preferences, storage = globalThis.localStorage) {
  storage?.setItem(STORAGE_KEY, JSON.stringify(preferences));
}

export function applyPreferences(preferences, root = document.documentElement, prefersDark = false) {
  root.dataset.theme = resolveTheme(preferences.theme, prefersDark);
  root.dataset.themeChoice = preferences.theme;
  root.dataset.contrast = preferences.contrast === 'high' ? 'high' : 'normal';
  root.dataset.textSize = preferences.textSize || 'md';
  root.dataset.spacing = preferences.spacing === 'roomy' ? 'roomy' : 'normal';
  root.dataset.links = preferences.links ? 'on' : 'off';
  root.dataset.motion = preferences.motion === 'reduce' ? 'reduce' : 'system';
  root.dataset.easy = preferences.easy ? 'on' : 'off';
  root.dataset.guided = preferences.guided ? 'on' : 'off';
}

/** Quita DNI de 8 dígitos y tokens hexadecimales antes de leer en voz alta. */
export function speakableText(value) {
  return String(value ?? '')
    .replace(/\b\d{8}\b/g, '')
    .replace(/\b[a-f0-9]{32}\b/gi, '')
    .replace(/\s+/g, ' ')
    .trim();
}
