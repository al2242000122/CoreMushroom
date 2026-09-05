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
import glob, io, json, os, re, sys, unicodedata

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

def normaliza(t):
    """Une espacios y quita acentos para que la comparacion no dependa de eso."""
    t = unicodedata.normalize("NFD", t)
    t = "".join(c for c in t if unicodedata.category(c) != "Mn")
    return re.sub(r"\s+", " ", t).strip().lower()


RUTA_REVISADAS = os.path.join(DEST, "tools", "compliance-revisado.txt")
REVISADAS = set()
if os.path.isfile(RUTA_REVISADAS):
    for linea in io.open(RUTA_REVISADAS, encoding="utf-8"):
        linea = linea.strip()
        if linea and not linea.startswith("#"):
            REVISADAS.add(normaliza(linea))

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

    # Sin guarda de ABSPATH, pedir el archivo por URL devuelve su marcado.
    if "ABSPATH" not in cab:
        problemas.append("falta la guarda de ABSPATH: el archivo se sirve por URL")

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

    # --- 6. Cumplimiento: ninguna promesa de efecto en el copy ---
    # Estas palabras no estan prohibidas en si. En una pagina legal aparecen
    # dentro de negaciones que son justo lo que hay que decir: "no se venden
    # para prevenir, aliviar, tratar ni curar". Lo que no se puede es que
    # aparezcan afirmando algo.
    #
    # Por eso no se marca la palabra sino la ORACION que la contiene, y se
    # compara contra tools/compliance-revisado.txt, que es la lista de frases
    # ya revisadas. Una frase nueva con una de estas palabras falla hasta que
    # alguien la lea y la agregue a esa lista a proposito. El archivo se
    # revisa como cualquier otro cambio.
    # La lista peca de amplia a proposito. Un falso positivo cuesta leer una
    # frase y agregarla a la lista de revisadas. Un falso negativo publica un
    # claim. No se ponen frases de dos palabras como "mejora tu", porque basta
    # cambiar el articulo para esquivarlas: se pone el verbo solo.
    VIGILADAS = [
        # Accion sobre una enfermedad o un sintoma
        "cura", "curar", "trata", "tratar", "tratamiento", "previene",
        "prevenir", "alivia", "aliviar", "sana", "sanar", "diagnostic",
        "combate", "remedia", "corrige", "elimina",
        # Accion sobre el cuerpo
        "refuerza", "fortalece", "estimula", "regenera", "purifica",
        "desintoxica", "detox", "tonifica", "revitaliza", "equilibra",
        "protege", "repara", "oxigena",
        # Verbos de promesa
        "mejora", "aumenta", "reduce", "favorece", "contribuye", "ayuda",
        "potencia", "optimiza", "acelera", "disminuye", "incrementa",
        "enriquece", "aporta",
        # Sustantivos que casi siempre acompanan a un claim
        "beneficio", "terapeutic", "medicinal", "dosis", "efecto",
        "propiedades", "bienestar", "vitalidad", "energia", "inmun",
        "antioxidante", "adaptogen", "nootropic", "concentracion", "memoria",
        "estres", "ansiedad", "sueno", "digestion", "defensas", "rendimiento",
        "salud", "enfermedad", "sintoma",
    ]

    # El cierre de cada bloque de texto cuenta como fin de oracion. Sin esto
    # un titular sin punto se pega al parrafo siguiente y la frase marcada
    # sale ilegible en el informe.
    plano = re.sub(r"</(p|h[1-6]|li|td|th|caption|div)>", ". ", cuerpo)
    plano = re.sub(r"<[^>]+>", " ", plano)
    plano = re.sub(r"\s+", " ", plano)
    # El cierre agrega un punto aunque la frase ya lo tuviera.
    plano = re.sub(r"\.\s*\.", ". ", plano)
    oraciones = [o.strip() for o in re.split(r"(?<=[.!?])\s+", plano) if o.strip()]

    for oracion in oraciones:
        bajo = oracion.lower()
        golpes = [w for w in VIGILADAS if re.search(r"\b" + re.escape(w), bajo)]
        if not golpes:
            continue
        if normaliza(oracion) in REVISADAS:
            continue
        problemas.append(
            "frase sin revisar (menciona: %s). Si es correcta, copiala tal cual a tools/compliance-revisado.txt. Frase: %s"
            % (", ".join(golpes), oracion))

    estado = "OK   " if not problemas else "FALLA"
    print("%s %-24s %d bloques" % (estado, nombre, sum(1 for e in eventos if e[1] == "abre")))
    for p in problemas:
        print("        - " + p)
    fallos += len(problemas)

print("\narchivos: %d | problemas: %d" % (len(archivos), fallos))
sys.exit(1 if fallos else 0)
