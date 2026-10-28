#!/usr/bin/env python3
"""Tapa rectangulos de un PNG con una barra negra (redaccion de datos personales en capturas).

Uso:  python redactar_png.py <entrada.png> <salida.png> x1,y1,x2,y2 [x1,y1,x2,y2 ...]

Solo usa la biblioteca estandar. Lee PNG de 8 bits (RGB o RGBA, sin entrelazado), aplica las barras
sobre los pixeles, y vuelve a escribir un PNG limpio (sin bloques de metadatos). La entrada no se modifica.
"""
import struct
import sys
import zlib


def leer_png(ruta):
    datos = open(ruta, "rb").read()
    if datos[:8] != b"\x89PNG\r\n\x1a\n":
        raise ValueError("No es un PNG")
    pos, idat, ihdr = 8, b"", None
    while pos < len(datos):
        largo, tipo = struct.unpack(">I4s", datos[pos:pos + 8])
        cuerpo = datos[pos + 8:pos + 8 + largo]
        if tipo == b"IHDR":
            ihdr = struct.unpack(">IIBBBBB", cuerpo)
        elif tipo == b"IDAT":
            idat += cuerpo
        pos += 12 + largo
    ancho, alto, prof, color, _, _, entrelazado = ihdr
    if prof != 8 or color not in (2, 6) or entrelazado:
        raise ValueError(f"Formato no admitido: profundidad={prof} color={color} entrelazado={entrelazado}")
    canales = 3 if color == 2 else 4
    crudo = zlib.decompress(idat)
    paso = ancho * canales
    filas, previa = [], bytearray(paso)
    p = 0
    for _ in range(alto):
        f = crudo[p]
        linea = bytearray(crudo[p + 1:p + 1 + paso])
        p += 1 + paso
        if f == 1:
            for i in range(canales, paso):
                linea[i] = (linea[i] + linea[i - canales]) & 255
        elif f == 2:
            for i in range(paso):
                linea[i] = (linea[i] + previa[i]) & 255
        elif f == 3:
            for i in range(paso):
                a = linea[i - canales] if i >= canales else 0
                linea[i] = (linea[i] + ((a + previa[i]) >> 1)) & 255
        elif f == 4:
            for i in range(paso):
                a = linea[i - canales] if i >= canales else 0
                b = previa[i]
                c = previa[i - canales] if i >= canales else 0
                pa, pb, pc = abs(b - c), abs(a - c), abs(a + b - 2 * c)
                pr = a if (pa <= pb and pa <= pc) else (b if pb <= pc else c)
                linea[i] = (linea[i] + pr) & 255
        elif f != 0:
            raise ValueError(f"Filtro PNG desconocido: {f}")
        filas.append(linea)
        previa = linea
    return ancho, alto, canales, color, filas


def escribir_png(ruta, ancho, alto, color, filas):
    crudo = b"".join(b"\x00" + bytes(f) for f in filas)

    def bloque(tipo, cuerpo):
        c = struct.pack(">I", len(cuerpo)) + tipo + cuerpo
        return c + struct.pack(">I", zlib.crc32(tipo + cuerpo) & 0xFFFFFFFF)

    salida = b"\x89PNG\r\n\x1a\n"
    salida += bloque(b"IHDR", struct.pack(">IIBBBBB", ancho, alto, 8, color, 0, 0, 0))
    salida += bloque(b"IDAT", zlib.compress(crudo, 9))
    salida += bloque(b"IEND", b"")
    open(ruta, "wb").write(salida)


def main():
    if len(sys.argv) < 4:
        sys.exit(__doc__)
    entrada, salida = sys.argv[1], sys.argv[2]
    rects = [tuple(int(v) for v in a.split(",")) for a in sys.argv[3:]]
    ancho, alto, canales, color, filas = leer_png(entrada)
    tapados = 0
    for x1, y1, x2, y2 in rects:
        for y in range(max(0, y1), min(alto, y2)):
            fila = filas[y]
            for x in range(max(0, x1), min(ancho, x2)):
                i = x * canales
                fila[i] = fila[i + 1] = fila[i + 2] = 20
                if canales == 4:
                    fila[i + 3] = 255
                tapados += 1
    escribir_png(salida, ancho, alto, color, filas)
    print(f"{salida}: {ancho}x{alto}, rectangulos={len(rects)}, pixeles tapados={tapados}")


if __name__ == "__main__":
    main()
