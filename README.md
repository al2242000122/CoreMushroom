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

Se configura una vez y después cada cambio se publica con un `git push`. Los
valores de abajo están verificados contra la instalación real, no supuestos.

### Cómo está montado el hosting

Hostinger trata `bancodeesporas.com` como la **cuenta de hosting** y
`core.bancodeesporas.com` como una **instalación de WordPress dentro** de ella.
Consecuencias que importan:

- Hay **una sola** pantalla de GIT para toda la cuenta. No hay una por
  subdominio. Está en **Avanzado → GIT**, en el menú lateral de hPanel, no
  dentro de la sección de WordPress.
- El campo `Directory` es relativo al `public_html` de la **cuenta**, que es
  el del dominio principal.
- El subdominio vive en `public_html/core/`. Por eso la ruta lleva el prefijo
  `core/`. Sin ese prefijo el despliegue cae en el sitio del banco de esporas,
  que es otro negocio y está en producción.

### 1. Preparar el destino

Instala WordPress en el subdominio, luego Blocksy y WooCommerce desde el panel
de WordPress. Deja Blocksy **instalado pero sin activar**: se activa
CoreMushroom, que lo usa como padre.

### 2. Conectar el repositorio en hPanel

**Avanzado → GIT**, crea un repositorio con:

| Campo | Valor |
|---|---|
| Repository | `https://github.com/al2242000122/CoreMushroom.git` |
| Branch | `main` |
| Directory | `core/wp-content/themes/coremushroom` |

Sin barra al inicio. hPanel la agrega al mostrarlo. La carpeta de destino debe
estar vacía o no existir.

Si el repositorio es privado, esa misma pantalla muestra una clave SSH pública.
Cópiala y agrégala en GitHub como *deploy key* en **Settings → Deploy keys**,
con permiso de solo lectura.

Termina pulsando **Create** y después **Deploy**. Create solo registra el
repositorio; el primer despliegue va aparte.

### 3. Activar el despliegue automático

En la fila del repositorio, el menú de tres puntos tiene la opción de
despliegue automático. Da una URL con un token. Agrégala en GitHub en
**Settings → Webhooks → Add webhook** con:

- **Payload URL**: la URL de Hostinger
- **Content type**: `application/json`, no el valor por omisión
- **Secret**: vacío
- **Events**: solo el evento `push`
- **Active**: marcado

El webhook solo dispara en los push posteriores a su creación. Un commit que
ya estaba subido hay que desplegarlo con el botón **Deploy**.

### 4. Activar el tema

**Apariencia → Temas → CoreMushroom → Activar**.

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

Validar los block patterns. Comprueba la cabecera, que los bloques abran y
cierren balanceados, que el JSON de atributos parsee, que todo preset de
color, tipografía y espaciado exista en `theme.json`, y que el copy no
contenga promesas de efecto:

```bash
python3 tools/valida-patterns.py .
```

Un pattern con marcado roto no falla al desplegarse: falla en el editor,
cuando ya lo insertaste. Por eso se valida antes.

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

## Sistema de diseño

Los componentes viven en `assets/css/components.css` y se usan con clases,
sin ningún atajo de estilo en línea. Todos los valores salen de `theme.json`.

### Botón

```html
<a class="cm-btn cm-btn--primario" href="/tienda">Ver catálogo</a>
<button class="cm-btn cm-btn--secundario">Seguir comprando</button>
<a class="cm-btn cm-btn--texto" href="/envios">Cómo enviamos</a>
```

Modificadores: `--primario`, `--secundario`, `--texto` para la variante;
`--sm` y `--lg` para el tamaño; `--bloque` para ancho completo.

Para deshabilitar, usa el atributo `disabled` en un `<button>` real. En un
`<a>`, que no admite `disabled`, agrega `cm-btn--deshabilitado` **y**
`aria-disabled="true"`. La clase bloquea el clic con `pointer-events` y el
atributo se lo comunica a los lectores de pantalla. Una sin la otra deja un
enlace que se ve gris pero sigue navegando.

### Badge

```html
<span class="cm-badge cm-badge--cordyceps">Cordyceps</span>
<span class="cm-badge cm-badge--oferta">Oferta</span>
<span class="cm-badge cm-badge--agotado">Agotado</span>
```

Modificadores: `--cordyceps`, `--hericium`, `--trametes`, `--oferta`,
`--agotado`. Sin modificador queda neutro sobre hueso.

### Tarjeta

```html
<article class="cm-tarjeta cm-tarjeta--enlace">
  <div class="cm-tarjeta__medio">
    <span class="cm-tarjeta__marca"><span class="cm-badge cm-badge--hericium">Hericium</span></span>
    <img src="..." alt="...">
  </div>
  <div class="cm-tarjeta__cuerpo">
    <h3 class="cm-tarjeta__titulo">Chocolate de Hericium</h3>
    <p class="cm-tarjeta__texto">60 g, 12 piezas</p>
    <div class="cm-tarjeta__pie">...</div>
  </div>
</article>
```

`cm-tarjeta--enlace` se levanta al pasar el cursor. Úsalo solo si la tarjeta
entera es pulsable. Una tarjeta informativa no debe moverse.

La zona de imagen tiene proporción fija de 4 a 3, para que la retícula del
catálogo no salte mientras cargan las fotos.

### Tabla de datos

Muestra la misma información que la etiqueta física del producto.

```html
<div class="cm-datos-envoltorio">
  <table class="cm-datos">
    <caption>Información del producto</caption>
    <tbody>
      <tr><th scope="row">Contenido neto</th><td>60 g, 12 piezas</td></tr>
      <tr><th scope="row">Ingredientes</th><td>Cacao 70%, extracto de Cordyceps militaris, azúcar de coco</td></tr>
      <tr><th scope="row">Alérgenos</th><td>Puede contener trazas de leche y frutos secos</td></tr>
      <tr><th scope="row">Lote</th><td>CM-2609-C4</td></tr>
    </tbody>
  </table>
</div>
```

El envoltorio es obligatorio: le da desplazamiento propio a la tabla para que
en móvil nunca provoque barra horizontal en toda la página.

**Esta tabla solo describe lo que hay dentro del producto.** Nunca lleva
filas sobre efectos, beneficios, indicaciones ni resultados. Esa es la línea
que no se cruza.

### WooCommerce

El botón de agregar al carrito, el precio, el distintivo de oferta y los tres
avisos ya heredan el sistema. No hace falta ponerles clases: se sobrescriben
por selector. Los avisos se distinguen por una barra lateral además del
color, para que se lean también sin percepción de color.

---

## Block patterns

Viven en `patterns/`. WordPress recorre ese directorio solo y lee la cabecera
de cada archivo: no se registran con PHP. Lo único que hace `functions.php` es
crear la categoría bajo la que se agrupan y retirar el de productos si
WooCommerce no está activo.

Para insertarlos: en el editor, botón **+**, pestaña **Patrones**, categoría
**CoreMushroom**.

| Archivo | Qué es |
|---|---|
| `barra-envio.php` | Franja superior con el umbral de envío gratis |
| `hero.php` | Apertura del home con titular y dos acciones |
| `tiles-formato.php` | Tres tiles: chocolate, tisana y cápsula |
| `grid-productos.php` | Título de sección y retícula de productos |
| `franja-confianza.php` | Envío, pago seguro y trazabilidad por lote |
| `pie.php` | Pie con navegación y enlaces legales |

Tres cosas que conviene saber antes de editarlos.

**Los tiles dividen por formato, no por especie.** Los colores conservan los
nombres de las especies porque así se llaman en la paleta, y porque los badges
de producto sí van por especie. En los tiles el color solo distingue el
formato.

**El copy es provisional.** El definitivo se escribe en la Fase 6 y lo revisa
un abogado. Ningún pattern lleva ni debe llevar afirmaciones sobre efectos,
beneficios o resultados.

**La retícula usa el shortcode `[products]`, no el bloque de WooCommerce.**
El shortcode es API estable; el marcado del bloque cambia entre versiones y un
pattern guardado con marcado viejo se rompe en el editor al actualizar el
plugin. Cuando ya tengas productos cargados puedes sustituirlo por el bloque
desde el editor, sin tocar el archivo.

Si editas un pattern a mano, valida antes de subir:

```bash
python3 tools/valida-patterns.py .
```

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
