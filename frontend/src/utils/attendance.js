export function downloadCsv(filename, headers, rows) {
  const escape = (value) => {
    const text = String(value ?? '');
    return /[",\n]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
  };
  const lines = [headers, ...rows].map((row) => row.map(escape).join(','));
  const blob = new Blob([`\uFEFF${lines.join('\n')}`], { type: 'text/csv;charset=utf-8' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);
}

export function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
  }[char]));
}

export function printHtml(title, body) {
  const popup = window.open('', '_blank', 'noopener,noreferrer,width=900,height=700');
  if (!popup) return false;
  popup.document.write(`<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>${escapeHtml(title)}</title>
    <style>
      body{font-family:Inter,Arial,sans-serif;padding:24px;color:#131b2e}
      h1{font-size:20px;margin:0}
      p{color:#434654}
      table{width:100%;border-collapse:collapse;margin-top:16px}
      td,th{border-bottom:1px solid #e2e7ff;padding:8px;text-align:left;font-size:13px}
    </style></head><body>${body}</body></html>`);
  popup.document.close();
  popup.focus();
  popup.print();
  return true;
}

export function distanceMeters(lat1, lon1, lat2, lon2) {
  const toRad = (deg) => (deg * Math.PI) / 180;
  const dLat = toRad(lat2 - lat1);
  const dLon = toRad(lon2 - lon1);
  const a = Math.sin(dLat / 2) ** 2
    + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
  return 6371000 * 2 * Math.asin(Math.min(1, Math.sqrt(a)));
}

export function formatDistance(meters) {
  if (meters == null || Number.isNaN(meters)) return '';
  if (meters < 1000) return `${Math.round(meters)} m`;
  return `${(meters / 1000).toFixed(1)} km`;
}

export function shortHash(text) {
  let hash = 2166136261;
  const source = String(text);
  for (let i = 0; i < source.length; i += 1) {
    hash = Math.imul(hash ^ source.charCodeAt(i), 16777619);
  }
  return (hash >>> 0).toString(16).padStart(8, '0');
}

export function inRange(iso, range) {
  const date = new Date(`${iso}T12:00:00`);
  const now = new Date();
  const day = 86400000;
  if (range === '30days') return now - date <= 30 * day && date <= now;
  if (range === 'currentMonth') return date.getMonth() === now.getMonth() && date.getFullYear() === now.getFullYear();
  if (range === 'trimester') return now - date <= 92 * day && date <= now;
  return date.getFullYear() === now.getFullYear();
}
