function channel(value) {
  const scaled = value / 255;
  return scaled <= 0.03928 ? scaled / 12.92 : ((scaled + 0.055) / 1.055) ** 2.4;
}

export function contrastRatio(rgbA, rgbB) {
  const lum = (rgb) => 0.2126 * channel(rgb[0]) + 0.7152 * channel(rgb[1]) + 0.0722 * channel(rgb[2]);
  const lighter = Math.max(lum(rgbA), lum(rgbB));
  const darker = Math.min(lum(rgbA), lum(rgbB));
  return (lighter + 0.05) / (darker + 0.05);
}

/** Pares de texto normal que deben sostener 4.5:1. Mantener alineados con accessibility.css. */
export const TEXT_PAIRS = {
  light: [
    [[19, 27, 46], [250, 248, 255]],
    [[0, 54, 146], [250, 248, 255]],
    [[255, 255, 255], [0, 54, 146]],
  ],
  dark: [
    [[236, 239, 255], [15, 20, 36]],
    [[179, 197, 255], [15, 20, 36]],
    [[0, 24, 74], [179, 197, 255]],
  ],
  highLight: [
    [[0, 0, 0], [255, 255, 255]],
    [[255, 255, 255], [0, 32, 96]],
  ],
  highDark: [
    [[255, 255, 255], [0, 0, 0]],
    [[0, 0, 0], [255, 255, 255]],
  ],
};
