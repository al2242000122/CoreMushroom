# CoreMushroom

Tema hijo de [Blocksy](https://creativethemes.com/blocksy/) para la tienda
CoreMushroom. Derivados funcionales de Cordyceps, Hericium y Trametes en
formato chocolate, tisana, cápsula y microdosis.

**El tema padre no se toca nunca.** Todo lo propio vive en este repositorio.

> ¿Retomas el proyecto? Empieza por **[CONTINUAR.md](CONTINUAR.md)**.

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

Los cuatro comprobadores del proyecto, en orden de rapidez:

```bash
python3 tools/valida-css.py .          # CSS, tokens, contraste y clases
python3 tools/valida-patterns.py .     # block patterns
php tools/prueba-arranque.php . con-woo   # los módulos se cargan
php tools/prueba-arranque.php . sin-woo   # y no se cargan sin WooCommerce
php tools/prueba-lote.php .            # campos de lote, tabla y tarjeta
php tools/prueba-patterns.php .        # los patterns renderizan limpio
php tools/prueba-checkout.php .        # constancia de terminos
php tools/prueba-spei.php .            # CLABE, estado e instrucciones
php tools/prueba-comprobante.php .     # subida y acceso al comprobante
php tools/prueba-seo.php .             # descripcion y Open Graph
```

Qué cubre cada uno:

- **valida-css** comprueba que el CSS parsea, que toda referencia a un token
  existe en `theme.json`, que no hay colores escritos a mano, que ninguna
  regla baja del mínimo de contraste, que ninguna pone texto claro sobre los
  tres colores de línea, que no hay bytes de control, y que toda clase `cm-`
  que imprime el PHP tiene regla en el CSS.
- **valida-patterns** comprueba la cabecera de cada pattern, el balance de los
  comentarios de bloque, el JSON de atributos, los presets, la guarda de
  acceso directo, los bordes sin `style` y las promesas de efecto en el copy.
- **prueba-arranque** carga `functions.php` con WordPress simulado y confirma
  que los módulos de `inc/` quedan cargados y sus ganchos registrados. Existe
  por un motivo concreto: WordPress incluye el `functions.php` del tema
  **después** de disparar `plugins_loaded`, así que enganchar la carga ahí la
  deja muerta sin ningún error visible.
- **prueba-lote** ejercita el guardado y el renderizado de los campos de lote
  contra WordPress y WooCommerce simulados: nonce, capacidad, autoguardado,
  saneado por tipo, escapado de salida y las guardas del shortcode.
- **prueba-patterns** incluye cada pattern igual que hace WordPress, con
  captura de salida, y comprueba que no quedan avisos, ni PHP sin ejecutar, ni
  variables sin resolver, y que los bloques siguen balanceados después de
  ejecutarse el PHP.

Un pattern con marcado roto no falla al desplegarse: falla en el editor,
cuando ya lo insertaste. Lo mismo con un token que no existe. Por eso se
valida antes de subir.

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
<a class="cm-btn cm-btn--texto" href="/politica-de-envios">Cómo enviamos</a>
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

Los enlaces a la tienda, el carrito y la cuenta **no están escritos a mano**.
Se resuelven al registrarse el pattern con `wc_get_page_permalink()`, porque
los slugs de esas páginas se pueden traducir y un enlace fijo se convierte en
un 404 en cuanto alguien lo hace.

Los enlaces legales del pie sí son rutas fijas. Apuntan a páginas que se
crean en la Fase 6 y hoy devuelven 404 a propósito: es preferible a borrarlos
y olvidarlos.

Si editas un pattern a mano, valida antes de subir:

```bash
python3 tools/valida-patterns.py .
php tools/prueba-patterns.php .
```

---

## Campos de lote

Cada producto lleva su ficha. Se edita en **Productos → editar → Ficha de
lote**, la caja que aparece bajo el editor.

| Campo | Tipo |
|---|---|
| Código de lote | Texto |
| Especie | Cordyceps, Hericium o Trametes |
| Formato | Chocolate, tisana o cápsula |
| Contenido neto | Texto |
| Ingredientes | Texto largo |
| Extracto por pieza | Texto |
| Alérgenos | Texto largo |
| Fecha de elaboración | Fecha |
| Consumir preferentemente antes de | Fecha |
| Certificado de análisis | Enlace |

Todo se define en una sola función, `coremushroom_campos_lote()` en
`inc/lote-campos.php`. El formulario, el guardado y la tabla pública leen de
ahí. Para agregar un campo, se agrega en esa función y los tres se enteran
solos.

**Estos campos describen lo que hay dentro del producto y nada más.** No hay
ni debe haber campos de efectos, beneficios, indicaciones ni resultados. Esa
es la línea que no se cruza, y la caja del editor lo dice en pantalla.

### Dónde aparece

La tabla se imprime sola bajo el resumen del producto. Si la ficha se arma
con bloques, donde ese gancho no llega, hay un shortcode:

```
[coremushroom_ficha]
[coremushroom_ficha id="123"]
```

El shortcode solo muestra productos publicados y sin contraseña. Un borrador,
una página o un producto protegido devuelven vacío.

### En el catálogo

Cada tarjeta del bucle recibe el badge de especie sobre la imagen, y bajo el
título una línea con formato, contenido neto y disponibilidad. Se hace con
ganchos de WooCommerce, no sobrescribiendo `content-product.php`, para no
tener que revisar una copia de esa plantilla en cada actualización del
plugin.

Sobre disponibilidad se dice solo si hay o no hay. Nunca cuántas quedan.

---

## Páginas legales

Cuatro patterns más, en la misma categoría CoreMushroom:

| Pattern | Página |
|---|---|
| `legal-privacidad.php` | Aviso de privacidad |
| `legal-terminos.php` | Términos de uso |
| `legal-envios.php` | Política de envíos |
| `legal-uso-previsto.php` | Declaración de uso previsto |

**Ninguno está listo para publicarse.** Los cuatro empiezan con un bloque
verde limón que dice BORRADOR SIN REVISIÓN LEGAL. Ese bloque se borra a mano
justo antes de publicar, y solo después de que un abogado haya revisado el
texto.

### Antes de mandarlos a revisión

Hay que sustituir todos los marcadores entre dobles corchetes por los datos
reales. Para listarlos:

```bash
grep -oh "\[\[[^]]*\]\]" patterns/legal-*.php | sort -u
```

Son datos que solo tú tienes: razón social, RFC, domicilio, correos de
contacto, paquetería, plazos de entrega y de reclamación, y la ciudad para la
jurisdicción.

### El control de cumplimiento

`tools/valida-patterns.py` marca **cualquier oración** que mencione uno de
unos sesenta términos vigilados: curar, tratar, prevenir, aliviar, mejorar,
aumentar, favorecer, contribuir, beneficio, dosis, energía, bienestar,
salud y demás.

La lista peca de amplia a propósito. Un falso positivo cuesta leer una frase.
Un falso negativo publica un claim.

Una oración marcada solo pasa si está escrita, tal cual, en
`tools/compliance-revisado.txt`. Eso significa que **toda frase nueva que use
una de esas palabras falla** hasta que alguien la lea y decida agregarla. Ese
cambio se ve en el historial como cualquier otro.

No agregues una frase a esa lista solo para que el validador se calle. Si la
frase afirma algo sobre lo que el producto hace, corrige la frase.

Y una advertencia honesta: el validador es una red, no una garantía. Detecta
lo que se le enseñó a detectar. La revisión de un abogado sigue siendo
obligatoria antes de publicar.

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
