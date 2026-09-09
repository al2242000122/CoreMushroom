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
3. **Cada `git push` publica en el sitio real en unos diez segundos.** Hay un
   webhook de despliegue automático. No subas nada sin correr las pruebas.

---

## Qué es esto

Un tema hijo de WordPress para una tienda de derivados de hongos comestibles
en México. La raíz de este repositorio **es** la carpeta del tema: se despliega
tal cual a `wp-content/themes/coremushroom`.

| Dato | Valor |
|---|---|
| Sitio de desarrollo | `core.bancodeesporas.com` |
| Repositorio | `github.com/al2242000122/CoreMushroom` |
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

Estos nueve comandos son el contrato del proyecto. Si alguno falla, no subas.

```bash
# Sintaxis de PHP en los 26 archivos
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
- El modo "Store coming soon" ya está desactivado: la portada y el producto
  se pueden abrir sin iniciar sesión.

---

## Lo que falta, por orden

### Bloquea abrir la tienda

1. **Completar los marcadores de las páginas legales.** Son los textos entre
   dobles corchetes. Listarlos con:
   ```bash
   grep -oh "\[\[[^]]*\]\]" patterns/legal-*.php | sort -u
   ```
   Ojo: las páginas ya están publicadas, así que hay que editarlas en
   WordPress, no solo en los patterns. Cambiar un pattern no toca las copias
   ya insertadas en una página.

   Estado público verificado el 9 de septiembre de 2026: Términos de uso ya
   no muestra marcadores; Aviso de privacidad muestra 6, Política de envíos
   muestra 8 y Declaración de uso previsto muestra 2.

2. **Revisión de un abogado** de esas cuatro páginas. Cada una abre con un
   bloque verde que dice BORRADOR SIN REVISION LEGAL. Ese bloque se borra
   cuando el abogado apruebe, no antes. Mientras esté, también aparece en la
   descripción de la página para buscadores.

3. **Cargar los nueve productos que faltan.** El texto y los campos de los diez
   están en [docs/catalogo.md](docs/catalogo.md). Los contenidos netos y las
   concentraciones de ese documento son propuestas: tienen que coincidir con
   la etiqueta física antes de publicar cada producto.

4. **Llenar la ficha de lote del producto que ya existe.** Se publicó sin
   ella, así que su tabla no aparece y su tarjeta no lleva badge de especie.
   Eso es el comportamiento correcto con campos vacíos, no un error.

5. **Corregir la identidad pública del sitio.** El título sigue siendo
   `core`; falta el nombre comercial y la descripción corta.

### Configuración pendiente

6. **Cabecera y pie de Blocksy.** El pie sigue diciendo que el tema es de
   WordPress. Se hace desde el personalizador de Blocksy, que guarda en base
   de datos y no en este repositorio. Es una excepción consciente al criterio
   de tenerlo todo en código; está anotada en AGENTS.md.
7. **Menú de navegación.** Hoy muestra las páginas que WordPress puso solo.
8. **Idioma del sitio a Español de México.** El HTML público todavía declara
   `en-US` y el cliente ve "Reviews",
   "Your rating" y "Submit" en la ficha del producto.
9. **Reseñas solo de compradores verificados**, en los ajustes de productos
   de WooCommerce.
10. **Borrar las páginas Privacy Policy y Refund and Returns Policy** que
    creó WooCommerce en inglés, para no tener dos avisos de privacidad.
11. **Reasignar la página de privacidad** en los ajustes de privacidad de
    WordPress, porque sigue apuntando a la vieja en inglés.

### Probar en el navegador

12. **Flujo completo de compra.** Pedido con SPEI, subida del comprobante,
    verificación y confirmación desde el panel. Nunca se ha probado entero.

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
