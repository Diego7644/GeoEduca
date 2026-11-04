import { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { applyPreferences, DEFAULT_PREFERENCES, loadPreferences, savePreferences } from '../accessibility/preferences';

const AccessibilityContext = createContext(null);

function systemPrefersDark() {
  return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

export function AccessibilityProvider({ children }) {
  const [preferences, setPreferences] = useState(() => loadPreferences());

  useEffect(() => {
    const media = window.matchMedia('(prefers-color-scheme: dark)');
    const paint = () => applyPreferences(preferences, document.documentElement, media.matches);
    paint();
    savePreferences(preferences);
    const onChange = () => {
      if (preferences.theme === 'auto') paint();
    };
    media.addEventListener('change', onChange);
    return () => media.removeEventListener('change', onChange);
  }, [preferences]);

  const value = useMemo(() => ({
    preferences,
    setPreference: (key, next) => setPreferences((current) => ({ ...current, [key]: next })),
    reset: () => setPreferences({ ...DEFAULT_PREFERENCES }),
    toggleTheme: () => setPreferences((current) => {
      const dark = document.documentElement.dataset.theme === 'dark';
      return { ...current, theme: dark ? 'light' : 'dark' };
    }),
  }), [preferences]);

  return <AccessibilityContext.Provider value={value}>{children}</AccessibilityContext.Provider>;
}

export function useAccessibility() {
  const value = useContext(AccessibilityContext);
  if (!value) throw new Error('useAccessibility debe usarse dentro de AccessibilityProvider');
  return value;
}
