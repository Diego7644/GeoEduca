import { useState } from 'react';

function initialsFallback(text = '?') {
  const initials = text
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((w) => w[0].toUpperCase())
    .join('');
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96"><rect width="96" height="96" fill="#dae2fd"/><text x="50%" y="54%" font-family="Inter,Arial" font-size="36" font-weight="700" fill="#003692" text-anchor="middle" dominant-baseline="middle">${initials}</text></svg>`;
  return `data:image/svg+xml;utf8,${encodeURIComponent(svg)}`;
}

/** <img> que muestra un avatar con iniciales si la URL remota falla. */
export default function SafeImage({ src, alt = '', ...props }) {
  const [failed, setFailed] = useState(false);
  return <img src={failed || !src ? initialsFallback(alt) : src} alt={alt} onError={() => setFailed(true)} {...props} />;
}
