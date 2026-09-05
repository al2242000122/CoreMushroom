"""Valida los block patterns de CoreMushroom sin necesitar WordPress.

Comprueba cuatro cosas, que son donde se rompen los patterns en la practica:
  1. La cabecera del archivo trae Title, Slug y Categories, y el slug esta
     namespaced. Sin eso WordPress no lo registra o lo registra sin nombre.
  2. Los comentarios de bloque abren y cierran balanceados.
  3. Los atributos JSON de cada bloque parsean.
  4. Todo slug de color, tamano de fuente y espaciado que se referencia existe
     en theme.json. Este es el error mas comun: un preset que no existe deja
     el bloque sin estilo y sin aviso.
"""
import glob, io, json, os, re, sys

DEST = sys.argv[1]
tj = json.load(io.open(os.path.join(DEST, "theme.json"), encoding="utf-8"))
S = tj["settings"]
COLORES = {c["slug"] for c in S["color"]["palette"]}
FUENTES = {f["slug"] for f in S["typography"]["fontSizes"]}
FAMILIAS = {f["slug"] for f in S["typography"]["fontFamilies"]}
ESPACIOS = {x["slug"] for x in S["spacing"]["spacingSizes"]}

# Bloques que se cierran solos con /--> y por tanto no llevan cierre aparte
APERTURA = re.compile(r"<!--\s+wp:([a-z0-9-]+(?:/[a-z0-9-]+)?)(\s+(\{.*?\}))?\s+(/)?-->", re.S)
CIERRE = re.compile(r"<!--\s+/wp:([a-z0-9-]+(?:/[a-z0-9-]+)?)\s+-->")

fallos = 0
archivos = sorted(glob.glob(os.path.join(DEST, "patterns", "*.php")))
if not archivos:
    print("No hay patterns que validar")
    sys.exit(1)

for ruta in archivos:
    nombre = os.path.basename(ruta)
    txt = io.open(ruta, encoding="utf-8").read()
    problemas = []

    # --- 1. Cabecera ---
    cab = txt.split("?>")[0]
    for campo in ("Title:", "Slug:", "Categories:"):
        if campo not in cab:
            problemas.append("falta %s en la cabecera" % campo)
    m = re.search(r"\*\s*Slug:\s*(\S+)", cab)
    if m and "/" not in m.group(1):
        problemas.append("el slug %s no lleva espacio de nombres" % m.group(1))
    if m and not m.group(1).startswith("coremushroom/"):
        problemas.append("el slug %s no empieza por coremushroom/" % m.group(1))

    cuerpo = txt.split("?>", 1)[1] if "?>" in txt else txt

    # --- 2 y 3. Balance de bloques y JSON de atributos ---
    eventos = []
    for mo in APERTURA.finditer(cuerpo):
        eventos.append((mo.start(), "abre", mo.group(1), mo.group(3), mo.group(4) == "/"))
    for mo in CIERRE.finditer(cuerpo):
        eventos.append((mo.start(), "cierra", mo.group(1), None, False))
    eventos.sort()

    pila = []
    for pos, tipo, nombre_bloque, attrs, autocierra in eventos:
        if attrs:
            try:
                json.loads(attrs)
            except Exception as e:
                problemas.append("JSON invalido en wp:%s -> %s" % (nombre_bloque, e))
        if tipo == "abre":
            if not autocierra:
                pila.append(nombre_bloque)
        else:
            if not pila:
                problemas.append("cierre sin apertura: /wp:%s" % nombre_bloque)
            elif pila[-1] != nombre_bloque:
                problemas.append("cierre cruzado: se esperaba /wp:%s y llego /wp:%s"
                                 % (pila[-1], nombre_bloque))
                pila.pop()
            else:
                pila.pop()
    if pila:
        problemas.append("bloques sin cerrar: " + ", ".join("wp:" + x for x in pila))

    # --- 4. Presets referenciados ---
    for slug in re.findall(r'"(?:backgroundColor|textColor)":"([a-z0-9-]+)"', cuerpo):
        if slug not in COLORES:
            problemas.append("color inexistente en theme.json: %s" % slug)
    for slug in re.findall(r'"fontSize":"([a-z0-9-]+)"', cuerpo):
        if slug not in FUENTES:
            problemas.append("tamano de fuente inexistente: %s" % slug)
    for slug in re.findall(r'"fontFamily":"([a-z0-9-]+)"', cuerpo):
        if slug not in FAMILIAS:
            problemas.append("familia inexistente: %s" % slug)
    for slug in re.findall(r"var:preset\|spacing\|([a-z0-9-]+)", cuerpo):
        if slug not in ESPACIOS:
            problemas.append("espaciado inexistente: %s" % slug)
    for slug in re.findall(r"var:preset\|color\|([a-z0-9-]+)", cuerpo):
        if slug not in COLORES:
            problemas.append("color inexistente en border: %s" % slug)
    # Las clases has-X-color y has-X-background-color deben existir tambien
    for slug in re.findall(r"has-([a-z0-9-]+?)-(?:background-)?color\b", cuerpo):
        if slug in ("text", "alpha-channel-opacity", "link"):
            continue
        if slug not in COLORES:
            problemas.append("clase has-%s-color sin preset detras" % slug)

    # --- 5. Bordes sin style quedan invisibles ---
    # border-style vale none por defecto, asi que un borde con color y ancho
    # pero sin style se declara y no se pinta. No da error en ningun lado.
    for lado in ("top", "bottom", "left", "right"):
        tiene_ancho = ("border-%s-width" % lado) in cuerpo
        tiene_style = ("border-%s-style" % lado) in cuerpo
        if tiene_ancho and not tiene_style:
            problemas.append("border-%s-width sin border-%s-style: no se pinta" % (lado, lado))
    if re.search(r"border-width\s*:", cuerpo) and not re.search(r"border-style\s*:", cuerpo):
        problemas.append("border-width sin border-style: no se pinta")

    # --- 5. Cumplimiento: ninguna promesa de efecto en el copy ---
    PROHIBIDAS = ["cura", "curar", "trata", "tratamiento", "previene", "alivia",
                  "mejora tu", "beneficio", "terapeutic", "medicinal", "sana",
                  "refuerza", "estimula", "combate", "reduce el", "aumenta tu"]
    plano = re.sub(r"<[^>]+>", " ", cuerpo).lower()
    for palabra in PROHIBIDAS:
        # Limite de palabra al inicio: si no, "sana" salta dentro de "tisana"
        # y "trata" dentro de "contrata".
        if re.search(r"\b" + re.escape(palabra), plano):
            problemas.append("posible claim en el copy: '%s'" % palabra)

    estado = "OK   " if not problemas else "FALLA"
    print("%s %-24s %d bloques" % (estado, nombre, sum(1 for e in eventos if e[1] == "abre")))
    for p in problemas:
        print("        - " + p)
    fallos += len(problemas)

print("\narchivos: %d | problemas: %d" % (len(archivos), fallos))
sys.exit(1 if fallos else 0)
