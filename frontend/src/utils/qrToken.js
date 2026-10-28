const DARK = '#003692';
const LIGHT = '#004BC3';

export function qrModules(seed) {
  let hash = 2166136261;
  const source = String(seed || 'GEOEDUCA');
  for (let i = 0; i < source.length; i += 1) {
    hash = Math.imul(hash ^ source.charCodeAt(i), 16777619);
  }
  const modules = [];
  for (let row = 0; row < 8; row += 1) {
    for (let col = 0; col < 8; col += 1) {
      hash = Math.imul(hash ^ (row * 8 + col + 3), 16777619);
      const bucket = hash >>> 0;
      const x = 76 + col * 12;
      const y = 76 + row * 12;
      const inLogo = x > 90 && x < 150 && y > 90 && y < 150;
      const inFinder = (x < 78 && y < 78) || (x > 150 && y < 78) || (x < 78 && y > 150);
      if (inLogo || inFinder || bucket % 3 === 0) continue;
      modules.push([x, y, 9, 9, bucket % 2 ? DARK : LIGHT]);
    }
  }
  return modules;
}

export const QR_COLORS = { dark: DARK, light: LIGHT };
