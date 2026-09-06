"""Valida el CSS del tema contra theme.json, sin necesitar WordPress.

Comprueba cinco cosas:
  1. El CSS parsea y no tiene declaraciones invalidas.
  2. Toda referencia var(--wp--...) existe en theme.json. Un token que no
     existe no da error en ningun lado: la propiedad simplemente no se aplica.
  3. Ningun color escrito a mano. Todo sale de la paleta.
  4. Contraste WCAG AA en toda regla que fije fondo y texto a la vez, mas la
     regla dura del proyecto: nunca texto claro sobre los tres colores de
     linea de producto.
  5. Toda clase cm- que imprime el PHP tiene una regla en el CSS. Si no la
     tiene, o sobra la clase o falta el estilo.

Uso:  python3 tools/valida-css.py .

tinycss2 es opcional. Sin el, el parseo se sustituye por un balance de llaves,
que es mas debil, y el script lo dice en la salida.
"""
import glob, io, json, os, re, sys

RAIZ = sys.argv[1] if len(sys.argv) > 1 else "."
HOJAS = ["assets/css/base.css", "assets/css/components.css", "style.css"]
FUENTES_PHP = ["inc/*.php", "functions.php"]

# Sobre estos tres el texto va siempre oscuro. Blanco da entre 2.87 y 3.26
# a 1, por debajo del minimo AA de 4.5 para texto normal.
LINEAS_PRODUCTO = ("cordyceps", "hericium", "trametes", "ganoderma")

fallos = 0


def falla(msg):
    global fallos
    fallos += 1
    print("FALLA " + msg)


def ok(msg):
    print("OK    " + msg)


# --- theme.json: la fuente de verdad -----------------------------------------
tj = json.load(io.open(os.path.join(RAIZ, "theme.json"), encoding="utf-8"))
S = tj["settings"]
PALETA = {c["slug"]: c["color"] for c in S["color"]["palette"]}


def kebab(x):
    """Replica _wp_to_kebab_case() de WordPress."""
    for patron, sust in ((r"(?<=[a-z])([A-Z])", r"-\1"),
                         (r"(?<=[0-9])([a-zA-Z])", r"-\1"),
                         (r"(?<=[A-Za-z])([0-9])", r"-\1")):
        x = re.sub(patron, sust, x)
    return x.replace("/", "-").lower()


declarados = set()
for c in S["color"]["palette"]:
    declarados.add("--wp--preset--color--" + c["slug"])
for f in S["typography"]["fontSizes"]:
    declarados.add("--wp--preset--font-size--" + f["slug"])
for f in S["typography"]["fontFamilies"]:
    declarados.add("--wp--preset--font-family--" + f["slug"])
for x in S["spacing"]["spacingSizes"]:
    declarados.add("--wp--preset--spacing--" + x["slug"])


def recorrer(nodo, ruta):
    for clave, valor in nodo.items():
        sub = ruta + [kebab(clave)]
        if isinstance(valor, dict):
            recorrer(valor, sub)
        else:
            declarados.add("--wp--custom--" + "--".join(sub))


recorrer(S["custom"], [])

# Slug con mayuscula o digito inicial se transforma al pasar por kebab y la
# variable resultante no coincide con la que uno escribio.
for grupo, slugs in (
    ("color", [c["slug"] for c in S["color"]["palette"]]),
    ("tamano", [f["slug"] for f in S["typography"]["fontSizes"]]),
    ("familia", [f["slug"] for f in S["typography"]["fontFamilies"]]),
    ("espaciado", [x["slug"] for x in S["spacing"]["spacingSizes"]]),
):
    malos = [s for s in slugs if kebab(s) != s]
    if malos:
        falla("slugs de %s que cambian al pasar por kebab-case: %s" % (grupo, malos))

# --- 0. Bytes de control ------------------------------------------------------
# Un escape mal escrito, por ejemplo "\00B7" al que se le pierde la barra,
# deja un byte nulo dentro del CSS. El navegador lo sustituye por el caracter
# de reemplazo y el simbolo sale mal, pero el archivo parsea sin quejarse.
for rel in HOJAS:
    ruta = os.path.join(RAIZ, rel)
    if not os.path.isfile(ruta):
        continue
    crudo = open(ruta, "rb").read()
    malos = sorted({b for b in crudo if b < 9 or 11 <= b <= 12 or 14 <= b <= 31})
    if malos:
        falla("%s: bytes de control %s" % (rel, [hex(b) for b in malos]))
    else:
        ok("%s sin bytes de control" % rel)

# --- 1. Parseo ---------------------------------------------------------------
try:
    import tinycss2
    tiene_parser = True
except ImportError:
    tiene_parser = False
    print("AVISO tinycss2 no instalado. El parseo se sustituye por un balance")
    print("      de llaves. Instalalo con: pip install tinycss2")

reglas_por_hoja = {}
for rel in HOJAS:
    ruta = os.path.join(RAIZ, rel)
    if not os.path.isfile(ruta):
        continue
    css = io.open(ruta, encoding="utf-8").read()
    if tiene_parser:
        todas, _ = tinycss2.parse_stylesheet_bytes(
            css.encode("utf-8"), skip_whitespace=True, skip_comments=True)
        errores = [r for r in todas if r.type == "error"]
        reglas = [r for r in todas if r.type == "qualified-rule"]
        invalidas = 0
        for r in reglas:
            for d in tinycss2.parse_blocks_contents(r.content):
                if d.type == "error":
                    invalidas += 1
                    falla("%s: declaracion invalida en %s"
                          % (rel, tinycss2.serialize(r.prelude).strip()[:60]))
        for e in errores:
            falla("%s: error de sintaxis %s" % (rel, e))
        if not errores and not invalidas:
            ok("%s parsea: %d reglas" % (rel, len(reglas)))
        reglas_por_hoja[rel] = reglas
    else:
        sin_com = re.sub(r"/\*.*?\*/", "", css, flags=re.S)
        if sin_com.count("{") != sin_com.count("}"):
            falla("%s: llaves desbalanceadas" % rel)
        else:
            ok("%s: llaves balanceadas" % rel)

# --- 2 y 3. Tokens y colores a mano -----------------------------------------
usados = set()
for rel in HOJAS + ["theme.json"]:
    ruta = os.path.join(RAIZ, rel)
    if not os.path.isfile(ruta):
        continue
    txt = io.open(ruta, encoding="utf-8").read()
    usados |= set(re.findall(r"var\(\s*(--wp--[a-z0-9-]+)", txt))

rotos = sorted(usados - declarados)
if rotos:
    for t in rotos:
        falla("token que no existe en theme.json: %s" % t)
else:
    ok("los %d tokens referenciados existen en theme.json" % len(usados))

for rel in HOJAS:
    ruta = os.path.join(RAIZ, rel)
    if not os.path.isfile(ruta):
        continue
    sin_com = re.sub(r"/\*.*?\*/", "",
                     io.open(ruta, encoding="utf-8").read(), flags=re.S)
    sueltos = re.findall(r"#[0-9A-Fa-f]{3,8}\b", sin_com)
    if sueltos:
        falla("%s: colores escritos a mano %s" % (rel, sueltos))
if not any(re.findall(r"#[0-9A-Fa-f]{3,8}\b",
                      re.sub(r"/\*.*?\*/", "",
                             io.open(os.path.join(RAIZ, r), encoding="utf-8").read(),
                             flags=re.S))
           for r in HOJAS if os.path.isfile(os.path.join(RAIZ, r))):
    ok("ningun color escrito a mano en el CSS")

# --- 4. Contraste ------------------------------------------------------------
def lineal(canal):
    canal /= 255
    return canal / 12.92 if canal <= 0.04045 else ((canal + 0.055) / 1.055) ** 2.4


def luminancia(hexa):
    r, g, b = (int(hexa[i:i + 2], 16) for i in (1, 3, 5))
    return 0.2126 * lineal(r) + 0.7152 * lineal(g) + 0.0722 * lineal(b)


def razon(a, b):
    la, lb = luminancia(a), luminancia(b)
    alto, bajo = max(la, lb), min(la, lb)
    return (alto + 0.05) / (bajo + 0.05)


def color_de(valor):
    m = re.search(r"--wp--preset--color--([a-z]+)", valor or "")
    return PALETA.get(m.group(1)) if m else None


if tiene_parser:
    pares = 0
    peor = (99.0, "")
    for rel, reglas in reglas_por_hoja.items():
        for r in reglas:
            props = {}
            for d in tinycss2.parse_blocks_contents(r.content):
                if d.type == "declaration" and d.lower_name in ("background-color", "color"):
                    props[d.lower_name] = tinycss2.serialize(d.value).strip()
            fondo = color_de(props.get("background-color"))
            texto = color_de(props.get("color"))
            if not fondo or not texto:
                continue
            sel = re.sub(r"\s+", " ", tinycss2.serialize(r.prelude)).strip()[:52]
            valor = razon(fondo, texto)
            pares += 1
            if valor < peor[0]:
                peor = (valor, sel)
            if valor < 4.5:
                falla("contraste %.2f en %s (fondo %s, texto %s)" % (valor, sel, fondo, texto))
    if pares:
        ok("contraste AA en %d reglas que fijan fondo y texto, la peor %.2f (%s)"
           % (pares, peor[0], peor[1]))

    # Regla dura del proyecto
    for slug in LINEAS_PRODUCTO:
        fondo = PALETA[slug]
        for claro in ("blanco", "crema", "hueso"):
            if razon(fondo, PALETA[claro]) >= 4.5:
                continue
            for rel, reglas in reglas_por_hoja.items():
                for r in reglas:
                    props = {}
                    for d in tinycss2.parse_blocks_contents(r.content):
                        if d.type == "declaration" and d.lower_name in ("background-color", "color"):
                            props[d.lower_name] = tinycss2.serialize(d.value).strip()
                    if color_de(props.get("background-color")) == fondo \
                            and color_de(props.get("color")) == PALETA[claro]:
                        falla("texto %s sobre %s: prohibido en este proyecto" % (claro, slug))
    ok("ninguna regla pone texto claro sobre cordyceps, hericium o trametes")

# --- 5. Clases cm- que imprime el PHP ---------------------------------------
php = ""
for patron in FUENTES_PHP:
    for ruta in sorted(glob.glob(os.path.join(RAIZ, patron))):
        php += io.open(ruta, encoding="utf-8").read()

css_todo = "".join(
    io.open(os.path.join(RAIZ, r), encoding="utf-8").read()
    for r in HOJAS if os.path.isfile(os.path.join(RAIZ, r)))

clases = set()
for grupo in re.findall(r'class="([^"]*cm-[^"]*)"', php):
    for token in grupo.split():
        if token.startswith("cm-") and "%" not in token and "$" not in token:
            clases.add(token)

huerfanas = sorted(c for c in clases if ("." + c) not in css_todo)
if huerfanas:
    for c in huerfanas:
        falla("el PHP imprime .%s pero el CSS no la define" % c)
else:
    ok("las %d clases cm- que imprime el PHP tienen regla en el CSS" % len(clases))

print("\nFALLOS: %d" % fallos)
sys.exit(1 if fallos else 0)
