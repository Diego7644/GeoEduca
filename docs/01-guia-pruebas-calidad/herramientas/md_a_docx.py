#!/usr/bin/env python3
"""Convierte INFORME-TECNICO-FINAL.md en un .docx usando solo la biblioteca estándar de Python.

Uso:  python md_a_docx.py <entrada.md> <salida.docx>

Admite el subconjunto de Markdown que usa el informe: encabezados (#, ##, ###), párrafos, listas con
viñetas y numeradas, citas (>), bloques de código (```), tablas con tuberías, imágenes PNG y
formato en línea (**negrita**, *cursiva*, `código`, [texto](enlace)). No instala ni requiere nada más.
Las imágenes se resuelven respecto de la carpeta del archivo de entrada.
"""
import re
import struct
import sys
import zipfile
from pathlib import Path
from xml.sax.saxutils import escape

PAGE_W, PAGE_H, MARGIN = 11906, 16838, 1000          # A4 en twips
TEXT_W = PAGE_W - 2 * MARGIN
EMU_PER_TWIP = 635

NS = (
    'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
    'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" '
    'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" '
    'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" '
    'xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"'
)


class Documento:
    def __init__(self, base: Path):
        self.base = base
        self.cuerpo = []
        self.imagenes = []          # (rId, nombre_en_zip, bytes)
        self.contador_img = 0
        self.faltantes = []

    # ---------- imágenes ----------
    def registrar_imagen(self, ruta_rel: str):
        ruta = (self.base / ruta_rel).resolve()
        if not ruta.is_file():
            self.faltantes.append(ruta_rel)
            return None
        datos = ruta.read_bytes()
        if datos[:8] != b"\x89PNG\r\n\x1a\n":
            self.faltantes.append(ruta_rel + " (no es PNG)")
            return None
        ancho, alto = struct.unpack(">II", datos[16:24])
        self.contador_img += 1
        rid = f"rIdImg{self.contador_img}"
        nombre = f"media/imagen{self.contador_img}.png"
        self.imagenes.append((rid, nombre, datos))
        return rid, nombre, ancho, alto

    def xml_imagen(self, ruta_rel, alt, max_ancho_twips, max_alto_emu=4_300_000):
        info = self.registrar_imagen(ruta_rel)
        if info is None:
            return rpr_texto(f"[imagen no encontrada: {ruta_rel}]", italic=True)
        rid, nombre, ancho, alto = info
        cx = min(ancho * 9525, max_ancho_twips * EMU_PER_TWIP)
        cy = int(cx * alto / ancho)
        if cy > max_alto_emu:
            cy = max_alto_emu
            cx = int(cy * ancho / alto)
        n = self.contador_img
        return (
            '<w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0">'
            f'<wp:extent cx="{cx}" cy="{cy}"/><wp:docPr id="{n}" name="Imagen {n}" descr="{escape(alt, {chr(34): "&quot;"})}"/>'
            '<wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr>'
            '<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            f'<pic:pic><pic:nvPicPr><pic:cNvPr id="{n}" name="{escape(Path(ruta_rel).name)}"/><pic:cNvPicPr/></pic:nvPicPr>'
            f'<pic:blipFill><a:blip r:embed="{rid}"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            f'<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="{cx}" cy="{cy}"/></a:xfrm>'
            '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic>'
            '</a:graphicData></a:graphic></wp:inline></w:drawing></w:r>'
        )


# ---------- inline ----------
def rpr_texto(texto, bold=False, italic=False, code=False, size=None):
    # Orden exigido por el esquema: rFonts, b, i, sz, szCs, shd
    props = ""
    if code:
        props += '<w:rFonts w:ascii="Consolas" w:hAnsi="Consolas" w:cs="Consolas"/>'
    if bold:
        props += "<w:b/>"
    if italic:
        props += "<w:i/>"
    if size:
        props += f'<w:sz w:val="{size}"/><w:szCs w:val="{size}"/>'
    if code:
        props += '<w:shd w:val="clear" w:color="auto" w:fill="EDEDED"/>'
    rpr = f"<w:rPr>{props}</w:rPr>" if props else ""
    return f'<w:r>{rpr}<w:t xml:space="preserve">{escape(texto)}</w:t></w:r>'


TOKEN = re.compile(
    r"(!\[[^\]]*\]\([^)]+\)|\*\*.+?\*\*|`[^`]+`|\[[^\]]+\]\([^)]+\)|(?<![\*\w])\*[^*\n]+?\*(?![\*\w]))"
)


def inline(doc, texto, bold=False, italic=False, size=None, max_img=None):
    salida = []
    pos = 0
    for m in TOKEN.finditer(texto):
        if m.start() > pos:
            salida.append(rpr_texto(texto[pos:m.start()], bold, italic, size=size))
        tok = m.group(0)
        if tok.startswith("!["):
            alt, ruta = re.match(r"!\[([^\]]*)\]\(([^)]+)\)", tok).groups()
            salida.append(doc.xml_imagen(ruta, alt, max_img or (TEXT_W - 400)))
        elif tok.startswith("**"):
            salida.append(inline(doc, tok[2:-2], True, italic, size, max_img))
        elif tok.startswith("`"):
            salida.append(rpr_texto(tok[1:-1], bold, italic, code=True, size=size))
        elif tok.startswith("["):
            txt, url = re.match(r"\[([^\]]+)\]\(([^)]+)\)", tok).groups()
            salida.append(rpr_texto(f"{txt} ({url})", bold, italic, size=size))
        else:
            salida.append(inline(doc, tok[1:-1], bold, True, size, max_img))
        pos = m.end()
    if pos < len(texto):
        salida.append(rpr_texto(texto[pos:], bold, italic, size=size))
    return "".join(salida)


# ---------- bloques ----------
def parrafo(contenido, estilo=None, extra_ppr="", jc=None):
    ppr = ""
    if estilo:
        ppr += f'<w:pStyle w:val="{estilo}"/>'
    ppr += extra_ppr
    if jc:
        ppr += f'<w:jc w:val="{jc}"/>'
    ppr = f"<w:pPr>{ppr}</w:pPr>" if ppr else ""
    return f"<w:p>{ppr}{contenido}</w:p>"


def dividir_fila(linea):
    celdas, actual, en_codigo = [], "", False
    linea = linea.strip()
    if linea.startswith("|"):
        linea = linea[1:]
    if linea.endswith("|"):
        linea = linea[:-1]
    for ch in linea:
        if ch == "`":
            en_codigo = not en_codigo
        if ch == "|" and not en_codigo:
            celdas.append(actual.strip())
            actual = ""
        else:
            actual += ch
    celdas.append(actual.strip())
    return celdas


def es_separador(linea):
    return bool(re.match(r"^\s*\|?\s*:?-{3,}:?\s*(\|\s*:?-{3,}:?\s*)*\|?\s*$", linea))


def tabla(doc, filas, alineaciones):
    ncol = max(len(f) for f in filas)
    filas = [f + [""] * (ncol - len(f)) for f in filas]
    plano = [re.sub(r"!\[[^\]]*\]\([^)]+\)", "", c) for f in filas for c in f]
    hay_imagenes = any("![" in c for f in filas for c in f)
    # pesos por columna según la longitud media del contenido
    pesos = []
    for j in range(ncol):
        largos = [len(re.sub(r"[`*]", "", filas[i][j])) for i in range(len(filas))]
        medio = sum(largos) / len(largos)
        pesos.append(min(max(medio, 7), 70) + (30 if hay_imagenes and any("![" in filas[i][j] for i in range(len(filas))) else 0))
    total = sum(pesos)
    anchos = [max(int(TEXT_W * p / total), 700) for p in pesos]
    anchos[-1] += TEXT_W - sum(anchos)
    sz = 15 if ncol >= 6 else (16 if ncol >= 4 else 18)
    if hay_imagenes:
        sz = 17
    xml = [
        "<w:tbl><w:tblPr>"
        f'<w:tblW w:w="{TEXT_W}" w:type="dxa"/>'
        '<w:tblBorders>'
        + "".join(f'<w:{b} w:val="single" w:sz="4" w:space="0" w:color="8C8C8C"/>' for b in ("top", "left", "bottom", "right", "insideH", "insideV"))
        + "</w:tblBorders>"
        '<w:tblLayout w:type="fixed"/>'
        '<w:tblCellMar><w:top w:w="40" w:type="dxa"/><w:left w:w="70" w:type="dxa"/><w:bottom w:w="40" w:type="dxa"/><w:right w:w="70" w:type="dxa"/></w:tblCellMar>'
        "</w:tblPr><w:tblGrid>" + "".join(f'<w:gridCol w:w="{a}"/>' for a in anchos) + "</w:tblGrid>"
    ]
    for i, fila in enumerate(filas):
        cab = i == 0
        trpr = "<w:trPr><w:cantSplit/>" + ("<w:tblHeader/>" if cab else "") + "</w:trPr>"
        xml.append(f"<w:tr>{trpr}")
        for j, celda in enumerate(fila):
            sombra = '<w:shd w:val="clear" w:color="auto" w:fill="DCE6F1"/>' if cab else ""
            jc = alineaciones[j] if j < len(alineaciones) and alineaciones[j] else None
            partes = [p for p in re.split(r"<br\s*/?>", celda)] or [""]
            parrafos = "".join(
                parrafo(inline(doc, p, bold=cab, size=sz, max_img=anchos[j] - 200), extra_ppr='<w:spacing w:before="0" w:after="20" w:line="240" w:lineRule="auto"/>', jc=jc)
                for p in partes
            )
            xml.append(f'<w:tc><w:tcPr><w:tcW w:w="{anchos[j]}" w:type="dxa"/>{sombra}</w:tcPr>{parrafos}</w:tc>')
        xml.append("</w:tr>")
    xml.append("</w:tbl>")
    xml.append(parrafo("", extra_ppr='<w:spacing w:before="0" w:after="60"/>'))
    return "".join(xml)


def convertir(doc, lineas):
    i, n = 0, len(lineas)
    primer_h1 = True
    while i < n:
        linea = lineas[i].rstrip("\n")
        s = linea.strip()
        # bloque de código
        if s.startswith("```"):
            i += 1
            codigo = []
            while i < n and not lineas[i].strip().startswith("```"):
                codigo.append(lineas[i].rstrip("\n"))
                i += 1
            i += 1
            for c in codigo:
                doc.cuerpo.append(parrafo(
                    f'<w:r><w:rPr><w:rFonts w:ascii="Consolas" w:hAnsi="Consolas" w:cs="Consolas"/><w:sz w:val="16"/><w:szCs w:val="16"/></w:rPr><w:t xml:space="preserve">{escape(c) if c else " "}</w:t></w:r>',
                    extra_ppr='<w:shd w:val="clear" w:color="auto" w:fill="F2F2F2"/><w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="auto"/><w:ind w:left="200"/>'))
            doc.cuerpo.append(parrafo("", extra_ppr='<w:spacing w:before="0" w:after="80"/>'))
            continue
        # vacío y separadores
        if not s:
            i += 1
            continue
        if re.fullmatch(r"-{3,}", s):
            i += 1
            continue
        # encabezados
        m = re.match(r"^(#{1,3})\s+(.*)$", s)
        if m:
            nivel = len(m.group(1))
            extra = '<w:pageBreakBefore w:val="0"/>' if (nivel == 1 and primer_h1) else ""
            if nivel == 1:
                primer_h1 = False
            doc.cuerpo.append(parrafo(inline(doc, m.group(2)), f"Heading{nivel}", extra))
            i += 1
            continue
        # tabla
        if s.startswith("|") and i + 1 < n and es_separador(lineas[i + 1]):
            cab = dividir_fila(s)
            seps = dividir_fila(lineas[i + 1])
            alin = []
            for sp in seps:
                if sp.startswith(":") and sp.endswith(":"):
                    alin.append("center")
                elif sp.endswith(":"):
                    alin.append("right")
                else:
                    alin.append(None)
            filas = [cab]
            i += 2
            while i < n and lineas[i].strip().startswith("|"):
                filas.append(dividir_fila(lineas[i]))
                i += 1
            doc.cuerpo.append(tabla(doc, filas, alin))
            continue
        # cita
        if s.startswith(">"):
            bloque = []
            while i < n and lineas[i].strip().startswith(">"):
                bloque.append(re.sub(r"^>\s?", "", lineas[i].strip()))
                i += 1
            texto = " ".join(b for b in bloque if b)
            doc.cuerpo.append(parrafo(inline(doc, texto, italic=False), extra_ppr='<w:pBdr><w:left w:val="single" w:sz="18" w:space="8" w:color="C0504D"/></w:pBdr><w:shd w:val="clear" w:color="auto" w:fill="FBEFEE"/><w:spacing w:before="60" w:after="120"/><w:ind w:left="240"/>'))
            continue
        # listas
        m = re.match(r"^(\s*)([-*]|\d+\.)\s+(.*)$", linea)
        if m:
            sangria = len(m.group(1)) // 2
            marca = "•" if m.group(2) in "-*" else m.group(2)
            texto = m.group(3)
            i += 1
            while i < n and lineas[i].startswith("  ") and lineas[i].strip() and not re.match(r"^\s*([-*]|\d+\.)\s+", lineas[i]) and not lineas[i].strip().startswith("|"):
                texto += " " + lineas[i].strip()
                i += 1
            izq = 360 + 360 * sangria
            doc.cuerpo.append(parrafo(rpr_texto(marca + "\t") + inline(doc, texto),
                                      extra_ppr=f'<w:tabs><w:tab w:val="left" w:pos="{izq}"/></w:tabs><w:spacing w:before="0" w:after="60"/><w:ind w:left="{izq}" w:hanging="300"/>'))
            continue
        # imagen sola
        if re.fullmatch(r"!\[[^\]]*\]\([^)]+\)", s):
            doc.cuerpo.append(parrafo(inline(doc, s), jc="center", extra_ppr='<w:keepNext/><w:spacing w:before="120" w:after="40"/>'))
            i += 1
            continue
        # párrafo (une líneas contiguas)
        texto = s
        i += 1
        while i < n:
            sig = lineas[i].strip()
            if (not sig or sig.startswith(("|", ">", "```", "#")) or re.match(r"^([-*]|\d+\.)\s+", sig) or re.fullmatch(r"-{3,}", sig)):
                break
            texto += " " + sig
            i += 1
        centrado = texto.startswith("**") and texto.endswith("**") and len(texto) < 120
        doc.cuerpo.append(parrafo(inline(doc, texto), jc="center" if centrado else None))


ESTILOS = f"""<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
<w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:eastAsia="Calibri" w:cs="Calibri"/><w:sz w:val="21"/><w:szCs w:val="21"/><w:lang w:val="es-PE" w:eastAsia="es-PE" w:bidi="ar-SA"/></w:rPr></w:rPrDefault>
<w:pPrDefault><w:pPr><w:spacing w:after="100" w:line="264" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults>
<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:qFormat/></w:style>
<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:qFormat/><w:pPr><w:keepNext/><w:pageBreakBefore/><w:spacing w:before="240" w:after="160"/><w:outlineLvl w:val="0"/></w:pPr><w:rPr><w:b/><w:color w:val="1F3864"/><w:sz w:val="34"/><w:szCs w:val="34"/></w:rPr></w:style>
<w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:qFormat/><w:pPr><w:keepNext/><w:spacing w:before="220" w:after="100"/><w:outlineLvl w:val="1"/></w:pPr><w:rPr><w:b/><w:color w:val="2F5496"/><w:sz w:val="27"/><w:szCs w:val="27"/></w:rPr></w:style>
<w:style w:type="paragraph" w:styleId="Heading3"><w:name w:val="heading 3"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:qFormat/><w:pPr><w:keepNext/><w:spacing w:before="160" w:after="80"/><w:outlineLvl w:val="2"/></w:pPr><w:rPr><w:b/><w:color w:val="2F5496"/><w:sz w:val="23"/><w:szCs w:val="23"/></w:rPr></w:style>
</w:styles>"""


def construir(md: Path, salida: Path):
    doc = Documento(md.parent)
    lineas = md.read_text(encoding="utf-8").splitlines()
    convertir(doc, lineas)
    sect = (f'<w:sectPr><w:pgSz w:w="{PAGE_W}" w:h="{PAGE_H}"/>'
            f'<w:pgMar w:top="{MARGIN}" w:right="{MARGIN}" w:bottom="{MARGIN}" w:left="{MARGIN}" w:header="500" w:footer="500" w:gutter="0"/></w:sectPr>')
    documento = f'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document {NS}><w:body>{"".join(doc.cuerpo)}{sect}</w:body></w:document>'
    rels = ('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            '<Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            + "".join(f'<Relationship Id="{rid}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="{nom}"/>' for rid, nom, _ in doc.imagenes)
            + "</Relationships>")
    tipos = ('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
             '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
             '<Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="image/png"/>'
             '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
             '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
             "</Types>")
    raiz = ('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>')
    with zipfile.ZipFile(salida, "w", zipfile.ZIP_DEFLATED) as z:
        z.writestr("[Content_Types].xml", tipos)
        z.writestr("_rels/.rels", raiz)
        z.writestr("word/document.xml", documento)
        z.writestr("word/styles.xml", ESTILOS)
        z.writestr("word/_rels/document.xml.rels", rels)
        for _, nom, datos in doc.imagenes:
            z.writestr(f"word/{nom}", datos)
    return doc


if __name__ == "__main__":
    if len(sys.argv) != 3:
        sys.exit(__doc__)
    d = construir(Path(sys.argv[1]), Path(sys.argv[2]))
    print(f"DOCX generado: {sys.argv[2]} | imágenes incrustadas: {len(d.imagenes)} | imágenes no encontradas: {len(d.faltantes)}")
    for f in d.faltantes:
        print("  FALTA:", f)
