# Cómo continuar este proyecto

Este archivo es para quien retome el trabajo: otra persona, otro asistente, o
el mismo dentro de seis meses. Se escribió en septiembre de 2026, al terminar
las siete fases de desarrollo.

**Léelo entero antes de tocar nada.** Después lee [AGENTS.md](AGENTS.md), que
es la memoria del proyecto y contiene las decisiones y las reglas duras.

---

## Lo primero: tres reglas que no se negocian

Están explicadas en AGENTS.md, pero si solo lees tres líneas, que sean estas.

1. **Cero afirmaciones sobre lo que el producto hace.** Ni efectos, ni
   beneficios, ni dosis, ni resultados, ni insinuados. Es una restricción
   sanitaria mexicana y hay un validador que la comprueba.
2. **Nunca `add_action( 'plugins_loaded', ... )` en este tema.** WordPress
   carga el tema después de disparar ese gancho. El código queda muerto sin
   dar ningún error. Ya pasó dos veces en este proyecto.
3. **El repositorio es público y cada `git push` a `main` publica en el sitio
   real en unos diez segundos.** Hay un webhook de despliegue automático. No
   subas nada sin correr las pruebas y nunca confirmes secretos o datos de
   clientes en Git.

---

## Qué es esto

Un tema hijo de WordPress para una tienda de derivados de hongos comestibles
en México. La raíz de este repositorio **es** la carpeta del tema: se despliega
tal cual a `wp-content/themes/coremushroom`.

| Dato | Valor |
|---|---|
| Sitio de desarrollo | `core.bancodeesporas.com` |
| Repositorio público | `github.com/al2242000122/CoreMushroom` |
| Hosting | Hostinger, LiteSpeed, PHP 8.3 |
| Tema padre | Blocksy 2.1.56, no se toca nunca |
| Tienda | WooCommerce |

El dominio definitivo todavía no se compra. El sitio vive en un subdominio con
la indexación bloqueada a propósito. **No quites esa casilla** hasta que el
sitio esté en su dominio real.

---

## Prepara las herramientas

Sin esto no puedes verificar nada, y en este proyecto no se sube código sin
verificar.

**PHP.** Hace falta para comprobar sintaxis y correr las pruebas.

```bash
# Termux, que es donde trabaja el dueño del proyecto
pkg install php

# Debian o Ubuntu
sudo apt install php-cli php-mbstring

# Windows sin PHP: descarga el zip NTS x64 de windows.php.net,
# descomprímelo fuera del repositorio y habilita mbstring en php.ini
```

Comprueba que `mb_substr` existe. El código lo usa y sin la extensión
`mbstring` las pruebas fallan por un motivo que no tiene que ver con el
código.

**Python 3 y tinycss2**, para los validadores.

```bash
pip install tinycss2
```

Si no puedes instalarlo, el validador de CSS sigue funcionando con una
comprobación más débil y te lo dice en la salida.

---

## Verifica antes de subir. Siempre

Estas verificaciones son el contrato del proyecto. Si alguna falla, no subas.

```bash
# Sintaxis de PHP en todos los archivos
find . -name "*.php" -not -path "./.git/*" -print0 | xargs -0 -n1 php -l

# theme.json sigue siendo JSON
python3 -c "import json; json.load(open('theme.json')); print('ok')"

# CSS, tokens, contraste y clases
python3 tools/valida-css.py .

# Block patterns
python3 tools/valida-patterns.py .

# Los módulos se cargan, con y sin WooCommerce
php tools/prueba-arranque.php . con-woo
php tools/prueba-arranque.php . sin-woo

# Los patterns renderizan limpio
php tools/prueba-patterns.php .

# Campos de lote, tabla y tarjeta
php tools/prueba-lote.php .

# Cobro por SPEI
php tools/prueba-spei.php .

# Subida y acceso al comprobante
php tools/prueba-comprobante.php .

# Constancia de aceptación de términos
php tools/prueba-checkout.php .

# Metadatos para buscadores
php tools/prueba-seo.php .

# Compatibilidad del contenido guardado de la portada
php tools/prueba-portada.php .

# Purga de LiteSpeed después de un despliegue
php tools/prueba-cache.php .

```

Todos imprimen `TODO OK` o el número de fallos y salen con código 0 si pasan.

**Si agregas código, agrega su prueba.** Y comprueba que la prueba falla
cuando rompes el código a propósito. Este proyecto tiene varios casos en los
que una prueba daba verde sin comprobar nada; están anotados en AGENTS.md.

---

## Estado al momento del traspaso

### Terminado y desplegado

Las siete fases de desarrollo. En concreto:

- Tema hijo con `theme.json`: paleta de once colores, escala tipográfica
  fluida y espaciado, todo como tokens. Ningún color escrito a mano en CSS.
- Fraunces y Figtree autoalojadas, con licencia abierta incluida.
- Componentes: botón, badge, tarjeta, tabla de datos, y la sobrescritura de
  los componentes de WooCommerce.
- Diez block patterns: seis del home y cuatro páginas legales.
- Campos de lote por producto, con su tabla pública y su tarjeta de catálogo.
- Cobro por transferencia SPEI, con estado de pedido propio, validación de
  CLABE, subida de comprobante y confirmación desde el panel.
- Constancia de aceptación de términos guardada en el pedido.
- Metadatos para buscadores y para compartir.
- Protección por HTTP de la documentación, los dotfiles y las herramientas.

### Configurado en el sitio

- Portada publicada y asignada.
- Las cuatro páginas legales publicadas, todavía en borrador legal.
- Un producto publicado: Cordyceps · Cápsulas 30.
- CLABE y beneficiario capturados en la pasarela SPEI.
- LiteSpeed Cache instalado y activo.
- La portada se puede abrir sin iniciar sesión. WooCommerce todavía intercepta
  la ficha del producto con su pantalla inglesa de próxima apertura.
- Portada rediseñada con una paleta neutra, textos más cercanos y una
  ilustración original inspirada en Amanita muscaria. La ilustración es un
  motivo de marca y no representa las especies vendidas.

---

## Lo que falta, por orden

### Bloquea abrir la tienda

1. **Revisar jurídicamente las páginas legales.** Los datos operativos y el
   contacto ya están completos tanto en los patterns como en las cuatro páginas
   guardadas en WordPress. Conservan sus revisiones nativas y el aviso visible
   de borrador.

2. **Revisión de un abogado** de esas cuatro páginas. Cada una abre con un
   bloque verde que dice BORRADOR SIN REVISION LEGAL. Ese bloque se borra
   cuando el abogado apruebe, no antes. Mientras esté, también aparece en la
   descripción de la página para buscadores.

3. **Completar los nueve borradores importados.** Se cargaron desde
   [imports/catalogo-borradores.csv](imports/catalogo-borradores.csv) el 10 de
   septiembre de 2026. Están sin precio, inventario ni datos variables de
   producción. Solo precargan especie, formato y tipo de preparación; todo lo
   demás tiene que coincidir con la etiqueta física antes de publicar.

4. **Llenar la ficha de lote del producto que ya existe.** Se publicó sin
   ella, así que su tabla no aparece y su tarjeta no lleva badge de especie.
   Eso es el comportamiento correcto con campos vacíos, no un error. También
   falta cambiar su título de “Cordyceps · Cápsulas 30” a “Cordyceps ·
   Microdosis 30 cápsulas” para que coincida con la línea acordada.

5. **Conseguir fotografías y datos físicos de cada producto.** Sin precio,
   contenido real, ingredientes, alérgenos y lote, los borradores no deben
   publicarse.

6. **Definir el checkout de CoreAdaptogenos para tarjeta y OXXO.** Falta la URL
   definitiva, saber qué plataforma usa y confirmar qué pasarela aprobó el
   catálogo real. CoreMushroom conservará el pedido y redirigirá mediante una
   sesión opaca; el monto se recuperará de servidor a servidor y el pago solo
   se confirmará con webhook firmado. El cliente verá antes de salir que
   CoreAdaptogenos es la razón social cobradora.
   La metodología acordada está en
   [docs/pagos-coreadaptogenos.md](docs/pagos-coreadaptogenos.md).

### Configuración pendiente

7. **Cabecera y pie de Blocksy.** La portada usa el pie propio del tema; falta
   revisar las plantillas internas. Se hace desde el personalizador de Blocksy, que guarda en base
   de datos y no en este repositorio. Es una excepción consciente al criterio
   de tenerlo todo en código; está anotada en AGENTS.md.
8. **Traducir los títulos y slugs de Shop, Cart, Checkout y My account.** El
   menú ya muestra etiquetas en español y está asignado a cabecera y móvil.

El nombre, la descripción corta, el idioma Español de México, la zona horaria
de Ciudad de México, las unidades métricas, la página de privacidad y las
reseñas limitadas a compradores verificados quedaron configurados. Las tres
páginas inglesas antiguas ya están en la papelera.

### Probar en el navegador

12. **Flujo completo de compra.** Pedido con SPEI, subida del comprobante,
    verificación y confirmación desde el panel. Nunca se ha probado entero.

13. **Revisar el modo de próxima apertura de WooCommerce.** El 9 de septiembre
    de 2026 la portada era pública, pero una visita sin sesión a la ficha del
    producto todavía mostraba el mensaje inglés "Great things are on the
    horizon". No abrir el catálogo hasta terminar los datos legales y de lote.

### Mejoras que no bloquean

- Sacar los comprobantes de la raíz web definiendo
  `COREMUSHROOM_DIR_COMPROBANTES` en `wp-config.php`.
- Traducir los slugs de las páginas de tienda, hoy en inglés. Ya no rompe
  nada: los enlaces de los patterns se resuelven solos.
- Desactivar el plugin Hostinger Reach si no se usa. Es la única dependencia
  externa del sitio y ve la IP de cada visitante.

---

## Trampas que ya costaron caro

Todas están explicadas con su motivo en AGENTS.md. Aquí van resumidas para que
no se repitan.

| Trampa | Qué pasa |
|---|---|
| `add_action( 'plugins_loaded', ... )` | El código no se ejecuta nunca y no da error |
| Orden de la paleta de Blocksy | El hueco 4 son los titulares y el 7 es un fondo. Meter ahí un acento saturado pinta media pantalla de ese color |
| Puente de color a `:root` | Blocksy carga después. Hay que usar `:root:root` |
| Borde uniforme con color de paleta | Va como atributo `borderColor` más clases, no como `border-color` en línea. Del otro modo el editor lo marca como bloque inválido |
| Borde sin `style` | `border-style` vale `none` por defecto y el borde no se pinta |
| Editar un pattern ya insertado | El marcado se guardó en la página. Cambiar el archivo no la toca |
| Comprobantes que se sustituyen | La clave del pedido viaja en la URL. Si el archivo nuevo pisa al anterior, quien tenga esa clave puede destruir la prueba de pago |

El antiguo enlace `/envios/` de la portada se conserva mediante una
redirección permanente a `/politica-de-envios/`. El pattern actualizado ya
apunta directamente a la página existente.

---

## Cómo se despliega

Está conectado por Git en hPanel. Cada `push` a `main` publica en unos diez
segundos gracias a un webhook.

El webhook solo cambia archivos. Para que LiteSpeed no siga sirviendo HTML de
una versión anterior, cada publicación funcional debe subir el número de
versión en `functions.php` y en la cabecera de `style.css`. La primera petición
sin cache llama una sola vez a la acción oficial `litespeed_purge_all` y guarda
la versión purgada.

La URL del webhook contiene un token y no pertenece al repositorio. Tampoco
se guardan aquí datos bancarios, comprobantes, credenciales, respaldos ni
información de clientes.

El detalle que costó una tarde: en Hostinger, `bancodeesporas.com` es la
cuenta de hosting y `core.bancodeesporas.com` es una instalación de WordPress
dentro de ella. Hay **una sola** pantalla de GIT para toda la cuenta, y el
campo `Directory` es relativo al `public_html` del dominio principal. Por eso
la ruta lleva el prefijo `core/`:

```
core/wp-content/themes/coremushroom
```

Sin ese prefijo, el tema se despliega en el sitio del banco de esporas, que es
otro negocio y está en producción.

---

## Qué NO hacer

- **No montar una tienda con nombre neutro en otro dominio para que el
  procesador de pagos no vea qué se vende.** Se pidió y se rechazó. Es lavado
  transaccional, y las consecuencias son retención del saldo durante meses e
  inscripción en la lista de comercios terminados, que bloquea abrir cuenta
  con cualquier procesador durante años. El motivo completo está en AGENTS.md.
- **No escribir reseñas.** Es publicidad engañosa y PROFECO la sanciona.
- **No tocar el tema padre.** Blocksy se actualiza y arrasaría el cambio.
- **No agregar una frase a `tools/compliance-revisado.txt` solo para que el
  validador se calle.** Si la frase afirma algo sobre lo que el producto hace,
  se corrige la frase.
