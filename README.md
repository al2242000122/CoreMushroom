# CoreMushroom

Tema hijo de [Blocksy](https://creativethemes.com/blocksy/) para la tienda
CoreMushroom. Derivados funcionales de Cordyceps, Hericium y Trametes en
formato chocolate, tisana, cápsula y microdosis.

**El tema padre no se toca nunca.** Todo lo propio vive en este repositorio.

---

## Requisitos

| Componente | Versión mínima | Nota |
|---|---|---|
| WordPress | 6.6 | `theme.json` versión 3 necesita 6.6 o superior |
| PHP | 7.4 | Recomendado 8.1 o superior en Hostinger |
| Blocksy | última estable | Tema padre, se instala desde el repositorio de WordPress |
| WooCommerce | última estable | |

---

## Estructura

La raíz del repositorio **es** la carpeta del tema hijo. Se despliega tal cual
dentro de `wp-content/themes/coremushroom`.

```
coremushroom/
├── style.css              Cabecera del tema. Sin reglas de estilo.
├── functions.php          Encolado de estilos, soportes y precarga de fuentes.
├── theme.json             Paleta, escala tipográfica, espaciado y estilos base.
├── assets/
│   ├── css/
│   │   ├── fonts.css      Declaraciones @font-face. Se genera, no se edita a mano.
│   │   └── base.css       Base del sistema de diseño.
│   └── fonts/             Fraunces y Figtree en woff2 variable, más sus licencias.
└── README.md
```

### Dónde vive cada cosa

- **Color, tipografía, espaciado y radios** se declaran en `theme.json`.
  WordPress los publica solo como custom properties. Nunca escribas un hex
  suelto en un archivo CSS.
- **Comportamiento y componentes** van en `assets/css/`.
- `style.css` solo lleva la cabecera del tema. Se encola de último por si hace
  falta un parche urgente que deba ganarle a todo lo demás.

Las variables disponibles siguen el patrón de WordPress:

```css
var(--wp--preset--color--cordyceps)
var(--wp--preset--font-size--xl)
var(--wp--preset--font-family--display)
var(--wp--preset--spacing--50)
var(--wp--custom--radio--pildora)
```

---

## Despliegue por Git a Hostinger

Se hace una sola vez y después cada cambio se publica con un `git push`.

### 1. Preparar el destino

Instala WordPress, luego Blocksy y WooCommerce desde el panel de WordPress.
Deja Blocksy **instalado pero sin activar**: se activa CoreMushroom, que lo
usa como padre.

### 2. Conectar el repositorio en hPanel

Entra a hPanel y ve a **Avanzado → GIT**. Crea un repositorio nuevo con:

| Campo | Valor |
|---|---|
| Repository URL | `https://github.com/al2242000122/CoreMushroom.git` |
| Branch | `main` |
| Directory | `public_html/wp-content/themes/coremushroom` |

Si el repositorio es privado, hPanel muestra una clave SSH pública en esa misma
pantalla. Cópiala y agrégala en GitHub como *deploy key* dentro de
**Settings → Deploy keys** del repositorio, con permiso de solo lectura.

### 3. Activar el despliegue automático

En la misma pantalla de GIT, Hostinger genera una **Webhook URL**. Agrégala en
GitHub en **Settings → Webhooks → Add webhook**, con tipo de contenido
`application/json` y el evento `push`. A partir de ahí cada push a `main`
publica solo.

Mientras no configures el webhook, el botón **Deploy** de hPanel hace el pull
manualmente.

### 4. Activar el tema

En el panel de WordPress, **Apariencia → Temas → CoreMushroom → Activar**.

---

## Flujo de trabajo desde Termux

```bash
pkg install git
git clone https://github.com/al2242000122/CoreMushroom.git
cd CoreMushroom

# editar
git status          # revisa SIEMPRE antes de agregar
git add -A
git commit -m "fase2: componente de tarjeta de producto"
git push origin main
```

`git add -A` agrega todo lo que no esté ignorado. El `.gitignore` bloquea
volcados de base de datos, archivos comprimidos y llaves privadas justo por
esto: si un plugin de respaldo escribe dentro de la carpeta del tema, ese
archivo no debe terminar en GitHub ni servido por HTTP.

El webhook publica el cambio en segundos.

### Regla importante

No edites archivos desde **Apariencia → Editor de archivos del tema** en
WordPress. Ese editor escribe directo en el servidor y el siguiente `git pull`
sobrescribe el cambio sin avisar. Todo pasa por el repositorio.

---

## Verificar antes de subir

Comprobar sintaxis de PHP evita dejar el sitio en pantalla blanca:

```bash
find . -name "*.php" -print0 | xargs -0 -n1 php -l
```

Validar que `theme.json` sigue siendo JSON correcto:

```bash
python3 -c "import json; json.load(open('theme.json')); print('ok')"
```

---

## Fuentes

Fraunces y Figtree se sirven desde este repositorio, no desde Google Fonts.
Son fuentes variables en formato woff2, subconjuntos `latin` y `latin-ext`.
Ambas se distribuyen bajo la SIL Open Font License 1.1. Las licencias completas
están en `assets/fonts/`.

Solo se precargan los subconjuntos `latin`. El `latin-ext` se descarga bajo
demanda gracias al `unicode-range` declarado en `fonts.css`.

---

## Paleta y contraste

| Slug | Hex | Uso |
|---|---|---|
| `crema` | `#FFF6EA` | Fondo base |
| `hueso` | `#F6E9D6` | Superficie elevada, secciones alternas |
| `blanco` | `#FFFFFF` | Tarjetas |
| `tinta` | `#17110D` | Texto principal |
| `grafito` | `#6B5B4D` | Texto secundario |
| `bosque` | `#103A2C` | Ancla de marca, pie, botón primario |
| `cordyceps` | `#FF6A13` | Línea Cordyceps, hover, foco |
| `hericium` | `#F4557E` | Línea Hericium |
| `trametes` | `#16A3A3` | Línea Trametes |
| `lima` | `#CBE84E` | Acento de energía, badges |

Contraste verificado con WCAG 2.1. La regla dura:

- Sobre `crema`, `hueso`, `blanco` y `lima` va texto `tinta`.
- Sobre `bosque` va texto `crema`.
- Sobre `cordyceps`, `hericium` y `trametes` va texto `tinta`.

**Nunca texto blanco sobre los tres colores de línea.** Blanco sobre
`cordyceps` da 2.87:1 y el mínimo AA para texto normal es 4.5:1.

---

## Archivos protegidos por HTTP

El repositorio se clona dentro de `public_html`, así que todo lo que hay aquí
quedaría servido por la web. El `.htaccess` del tema bloquea los `.md`, los
dotfiles, los volcados de base de datos, los archivos comprimidos, las llaves
privadas y el directorio `.git`.

Esa lista es un espejo de la del `.gitignore`. **Si editas una, edita la
otra.** Los archivos que Git ignora son justo los que pueden existir en el
servidor sin estar en GitHub, así que nadie los revisaría nunca.

Después del primer despliegue, corre esta comprobación. Es obligatoria, no
opcional: si el hosting ignora el `.htaccess` no aparece ningún error, el
archivo simplemente queda público.

```bash
BASE=https://TU-DOMINIO/wp-content/themes/coremushroom

# Deben devolver 403 o 404
for r in .git/config .git/HEAD CLAUDE.md README.md .gitignore CLAUDE; do
  printf '%-16s %s
' "$r" "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/$r")"
done

# Deben devolver 200
for r in style.css assets/css/base.css assets/fonts/fraunces-latin.woff2; do
  printf '%-34s %s
' "$r" "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/$r")"
done
```

`CLAUDE` sin extensión está en la lista a propósito. Si el servidor tiene
activada la negociación de contenido, esa URL puede entregar `CLAUDE.md`
saltándose el bloqueo por nombre de archivo.

Un `500` en el segundo bloque significa que el hosting no permite las
directivas del `.htaccess`. En ese caso quita la línea `Options` y vuelve a
probar: el sitio se cae entero si eso queda sin resolver.

---

## Licencia

GPL v2 o posterior, igual que WordPress y que Blocksy.
