# CoreMushroom - memoria del proyecto

> **Si acabas de llegar a este proyecto, empieza por [CONTINUAR.md](CONTINUAR.md).**
> Ahí está el estado actual, cómo preparar las herramientas, los comandos de
> verificación y lo que falta por orden. Este archivo es la memoria: las
> decisiones tomadas y las reglas que no se negocian.

## Qué es

Tienda WooCommerce de derivados funcionales de hongo. Tema hijo de Blocksy
sobre Hostinger. El desarrollo se hace desde Termux en Android con Git, así que
todo lo declarable en código se declara en código, no en interfaces de
configuración.

El repositorio de GitHub es **público**. Nunca se guardan en él credenciales,
la URL secreta del webhook, datos bancarios, comprobantes de clientes, copias
de la base de datos ni datos personales. Cada `push` a `main` dispara el
webhook de Hostinger y publica automáticamente en producción.

## Qué NO es

Este repositorio corresponde a un solo proyecto. El banco de esporas es un
negocio distinto, con su propio marco de cumplimiento, y no se mezcla con
este. Mientras no se compre el dominio definitivo, el sitio vive en un
subdominio de desarrollo con la indexación bloqueada.

## Restricciones duras

- **Cero claims terapéuticos.** Restricción COFEPRIS. Aplica a todo el copy,
  incluidos nombres de categoría, textos de producto y metadatos SEO. El
  lenguaje sensorial y de ritual sí se puede usar. El de efecto no.
- **No se vende hongo entero ni fresco.** Solo derivados: chocolate, tisana,
  cápsula, microdosis.
- **Nada de Psilocybe.** Solo especies legales: Cordyceps, Hericium, Trametes
  y Ganoderma.
- **El catálogo no se estructura por gramos ni por potencia.** Un suplemento
  se vende por miligramos de extracto por pieza, por número de piezas y por
  concentración del extracto. Vender por tramos de gramos con potencia baja,
  media y alta comunica un producto psicoactivo aunque la etiqueta diga otra
  cosa, y eso es publicidad engañosa además del patrón que hace que un
  procesador de pagos cierre la cuenta.
- **Ninguna dosis ni protocolo en el sitio.** El cliente tiene su propio
  protocolo de uso personal. No se publica: una dosis recomendada es un claim
  y además convierte el producto en otra categoría regulatoria.
- **No se copia texto, CSS ni imágenes de ningún sitio existente.** El modelo
  comercial de la competencia sí se puede replicar. Sus activos no.
- **No se toca el tema padre.** Blocksy se actualiza y arrasaría el cambio.

## Decisiones tomadas

- Dirección estética: amable, orgánica y editorial. Base crema y arena,
  acentos apagados en terracota, rosa arcilla, salvia y malva. La ilustración
  original inspirada en Amanita muscaria es un motivo decorativo; nunca se
  presenta como ingrediente ni como producto a la venta.
- La línea de cápsulas se llama **microdosis**. Decisión tomada y cerrada.
  El copy alrededor de esa línea se mantiene estrictamente sensorial: nada de
  efecto, beneficio ni resultado.
- Envío gratis a partir de 900 pesos.
- Pago activo: SPEI manual con comprobante. La opción de tarjeta permanece
  oculta hasta integrar el plugin oficial de un procesador aprobado.
- Tipografía: Fraunces para titulares, Figtree para texto. Autoalojadas.
- La raíz del repositorio es la carpeta del tema hijo. Se despliega a
  `public_html/core/wp-content/themes/coremushroom`.

## Fase 5, pagos y checkout: TERMINADA

Estado: las siete fases de código están terminadas y desplegadas. Falta lo
que depende de datos, contenido, revisión legal y configuración de WordPress.

### Decisiones de pago, actualizadas

- **Nada de Mercado Pago.** El cliente lo descartó por comisiones.
- **SPEI manual con comprobante** es el método principal. Comisión cero. El
  cliente transfiere, sube su comprobante, el dueño verifica y el pedido
  avanza. Es lo que permite vender sin depender de ninguna aprobación.
- **Tarjeta**: se solicita a Conekta con el nombre y el catálogo reales de
  CoreMushroom, declarando alimentos y suplementos alimenticios. Si rechaza,
  Stripe, y después Openpay.

### Arquitectura prevista para tarjeta y OXXO

La metodología completa y su contrato de seguridad están en
[`docs/pagos-coreadaptogenos.md`](docs/pagos-coreadaptogenos.md).

- CoreMushroom crea y conserva el pedido. SPEI manual se cobra y verifica en
  este sitio.
- Los pagos procesados con tarjeta u OXXO pueden redirigirse a un checkout de
  CoreAdaptogenos, que es la razón social cobradora. Antes de salir, el cliente
  debe ver claramente quién realizará el cobro y cuál será el descriptor.
- El procesador y el adquirente deben conocer el dominio de origen, el catálogo
  real y la relación entre CoreMushroom y CoreAdaptogenos. La redirección nunca
  se usa para ocultar productos o el origen de la operación.
- CoreMushroom envía únicamente un identificador opaco de sesión. El servidor
  de CoreAdaptogenos recupera pedido, monto y moneda mediante una solicitud
  autenticada; no confía en importes recibidos en la URL.
- El regreso del navegador solo sirve para mostrar el resultado. Un pedido se
  marca pagado después de validar un webhook firmado y comprobar del lado del
  servidor el identificador, importe, moneda y estado. Cada evento debe ser
  idempotente para tolerar reintentos.
- La entrada de tarjeta sigue oculta hasta conocer el dominio, la plataforma,
  la pasarela aprobada y las credenciales de pruebas de CoreAdaptogenos.

### Petición rechazada y por qué

El cliente pidió inicialmente replicar lo que hace la competencia: cobrar a través de una
tienda con nombre neutro en otro dominio para que el procesador no vea qué se
vende. Eso es lavado transaccional. Se rechazó y no se implementa, aunque se
reitere. Las consecuencias reales son retención del saldo por meses e
inscripción en la lista de comercios terminados, que bloquea abrir cuenta con
cualquier procesador durante años.

La arquitectura transparente descrita arriba es distinta: CoreAdaptogenos se
identifica como cobrador y el procesador conoce el catálogo y el dominio que
originan el pedido.

Para una categoría que de verdad sea difícil, como el banco de esporas, la
salida legal es un adquirente de alto riesgo que suscriba la categoría a
sabiendas, no un dominio pantalla.

### Hecho

- Constancia de aceptación de términos, guardada en el pedido.
- Pasarela SPEI propia, en `inc/pago-spei.php`. Estado de pedido propio
  `wc-cm-spei`, "esperando comprobante", para poder filtrarlos en el panel.
  Instrucciones con banco, beneficiario, CLABE, monto exacto y referencia,
  tanto en la página de gracias como en el correo, incluida versión de texto
  plano. La CLABE se valida con su dígito verificador antes de guardarse: una
  CLABE mal capturada manda el dinero de los clientes a otra cuenta.
- Hueco de tarjeta registrado pero con `is_available()` en false siempre. No
  se le muestra al cliente un método que no cobra. El día que haya cuenta con
  un procesador se sustituye por su plugin oficial y esta entrada se retira.

- Subida del comprobante y verificación desde el panel, en
  `inc/comprobante.php`.

### Reglas del comprobante, que costaron una ronda de revisión

- **Los comprobantes se acumulan, no se sustituyen.** La clave del pedido
  viaja en la URL de la página de gracias y puede filtrarse por el
  encabezado de referencia. Si el archivo nuevo pisara al anterior, quien
  tuviera esa clave podría destruir la prueba de pago del cliente subiendo
  cualquier cosa encima. Se guardan hasta cinco por pedido.
- **Si el directorio no se puede blindar, no se guarda nada.** El `.htaccess`
  se verifica por contenido en cada subida, no por existencia: un restore
  puede dejarlo vacío. Fallar cerrado, no abierto.
- Se puede sacar el directorio de la raíz web definiendo
  `COREMUSHROOM_DIR_COMPROBANTES` en `wp-config.php`. Es lo recomendable en
  cuanto haya una ruta escribible fuera de `public_html`.
- El tipo de archivo se decide leyendo los primeros bytes. Ni la extensión ni
  lo que declare el navegador cuentan: los dos los controla quien sube.
- El nombre se genera con `random_bytes`, no con `wp_generate_password`. Esa
  función pasa por un filtro público que cualquier plugin puede cambiar.
- El endpoint de descarga se registra solo para sesión iniciada. Registrar la
  versión anónima sería anunciar una entrada sin uso a un lector de
  documentos bancarios.

### Lo que falta

- Configurar la pasarela requiere que el cliente dé beneficiario, banco y
  CLABE. Sin CLABE la pasarela no se ofrece en el checkout, a propósito.
- Probar el flujo completo en el navegador: pedido, transferencia, subida y
  confirmación.

## Reglas del código PHP

- **Nunca enganchar nada a `plugins_loaded`, ni desde `functions.php` ni
  desde ningún archivo de `inc/`.** WordPress incluye el `functions.php` del
  tema después de haber disparado ese gancho, así que la llamada no se
  ejecuta jamás y el código queda muerto sin ningún error. Este error se ha
  cometido **dos veces** en el proyecto: primero con la carga de módulos y
  después con la declaración de las clases de pasarela, que dejó la
  Transferencia SPEI sin aparecer en el panel. Antes de escribir
  `add_action( 'plugins_loaded', ... )`, no lo escribas.
- Las clases de pasarela se declaran dentro del propio filtro
  `woocommerce_payment_gateways`, que corre justo cuando WooCommerce las
  necesita. Es el único momento garantizado.
- Una prueba que solo comprueba que una función existe no comprueba que su
  efecto ocurra. `tools/prueba-arranque.php` daba verde mientras las clases
  de pasarela no se declaraban nunca.
- Guarda de `ABSPATH` en todo archivo PHP del tema, incluidos los patterns.
- Todo campo que se guarde pasa por: autoguardado, tipo de contenido, nonce
  atado al ID, capacidad sobre ese post, y saneado por tipo. En ese orden.
- Todo lo que salga a pantalla se escapa en el punto de salida, aunque ya se
  haya saneado al entrar. La base de datos puede traer basura de antes.
- El directorio `tools/` está bloqueado por HTTP. Las pruebas en PHP definen
  `ABSPATH` por su cuenta, así que la guarda no las protegería.

## Reglas de los block patterns

- Viven en `patterns/`. WordPress los detecta solo por la cabecera del
  archivo. En PHP solo se registra la categoría y se retira el de productos
  si WooCommerce no está activo.
- Todo borde declarado en atributos de bloque necesita `style` explícito.
  Sin él, `border-style` vale `none` y el borde se declara pero no se pinta.
- **Un borde uniforme con un color de la paleta NO lleva `border-color` en el
  estilo en línea.** WordPress lo serializa como atributo `borderColor` de
  primer nivel más las clases `has-border-color` y `has-<slug>-border-color`.
  Escrito del otro modo, el editor marca el bloque como contenido inválido.
  Los bordes por lado sí van en línea, y por eso la franja de confianza, que
  usa `border.top` y `border.bottom`, sí era correcta.
- La retícula usa el shortcode `[products]`, no el bloque Product Collection,
  porque el marcado del bloque cambia entre versiones y rompe el pattern.
- Antes de subir un pattern editado a mano: `python3 tools/valida-patterns.py .`
  Comprueba cabecera, balance de bloques, JSON de atributos, presets contra
  `theme.json`, bordes sin `style` y promesas de efecto en el copy.

## Reglas del sistema de diseño

- Ningún color, espaciado, radio ni sombra se escribe a mano en CSS. Todo sale
  de `theme.json`. Si algo se ve mal, se corrige el token.
- El bloque de botón deshabilitado tiene que quedar **al final** de su sección
  en `components.css`. Empata en especificidad con las variantes en hover y
  con el empate decide el orden.
- `pointer-events: none` en ese bloque no es decoración. Sin él, un `<a>` con
  `cm-btn--deshabilitado` sigue navegando aunque se vea gris.
- Los componentes de WooCommerce repiten declaraciones de `.cm-btn` a
  propósito: WooCommerce genera su marcado y no le pone esa clase. Lo que se
  comparte son los tokens, así que un cambio de token mueve los dos.
- Sin modo oscuro. Una sola paleta, decisión tomada.
- La portada vive en `assets/css/home.css`. Incluye compatibilidad por posición
  para el contenido que ya estaba guardado en WordPress y clases `cm-home-*`
  para futuras inserciones de patterns.
- `inc/portada.php` corrige al renderizar las frases antiguas de la portada.
  No escribe en la base de datos y solo actúa dentro del loop principal del
  home. Es necesario porque editar un pattern no modifica los bloques que ya
  fueron insertados.
- La ilustración de la portada es original y vive en
  `assets/images/amanita-hero-v1.webp`. No debe sustituirse por una imagen de
  catálogo: su función es exclusivamente decorativa.
- Componentes disponibles: `cm-btn`, `cm-badge`, `cm-tarjeta`, `cm-datos`.
  Su uso está documentado en el README, sección Sistema de diseño.

## Comandos de verificación

No hay build. La verificación es sintáctica y estructural.

```bash
# PHP: binario portátil en el scratchpad de la sesión, no está en el PATH
find . -name "*.php" -print0 | xargs -0 -n1 php -l

# theme.json
python3 -c "import json; json.load(open('theme.json')); print('ok')"
```

En Windows sin PHP instalado se usa un binario portátil de PHP 8.2 NTS x64
descargado a un directorio temporal fuera del repositorio. En Termux basta
`pkg install php`.

## Convenciones

- Comentarios de PHP en español.
- Prefijo `coremushroom_` para funciones de PHP, `cm-` para clases de CSS.
- Ningún hex suelto en CSS. Todo color sale de `theme.json` como
  `var(--wp--preset--color--<slug>)`.
- Commits pequeños y descriptivos.

## Nota sobre este archivo

Este archivo y el README se despliegan dentro de `public_html`, y además el
repositorio completo es público en GitHub. El `.htaccess` bloquea los `.md` y
el directorio `.git` por HTTP, pero eso no protege el contenido de GitHub. No
escribas aquí credenciales, la URL del webhook, datos bancarios, rutas locales,
datos de clientes ni valoraciones sobre clientes o proveedores.

## Fase 7, rendimiento: la parte de código, hecha

Se midió la portada antes de tocar nada. Lo que ya estaba bien no se tocó: el
sitio no cargaba el script de emojis ni resolvía dominios ajenos.

Lo que se hizo, en `inc/rendimiento.php` y `inc/seo.php`:

- Descripción meta y etiquetas Open Graph, que no existían. Sin ellas, al
  compartir un enlace no sale ni título ni imagen.
- Se respeta la casilla de disuadir buscadores: mientras esté marcada se
  añade `nofollow` además del `noindex` que pone WordPress. **No quitarla
  hasta que el sitio esté en su dominio definitivo.**
- Fuera `wp-embed`, que solo sirve para que otros sitios incrusten este.
- Fuera `jquery-migrate`, conservando jQuery, del que WooCommerce depende.
- Fuera los tamaños de imagen que el tema no usa, y registrado uno recortado
  a la proporción 4 a 3 de la tarjeta del catálogo.
- Reglas de precarga que excluyen carrito, checkout y cuenta.

### Medido después, en el servidor

- `wp-embed` de WordPress: retirado, confirmado en el HTML servido.
- `jquery-migrate`: retirado, conservando jQuery.
- Descripción meta: **sigue ausente y no es culpa del código.** El sitio se
  llama `core` y no tiene descripción corta configurada, así que no hay de
  dónde sacar el texto. Un aviso en el panel lo explica. Se arregla en
  Ajustes generales, no tocando el tema.

### Hallazgo: el plugin Hostinger Reach

La portada carga un script desde `cdn-reach.hostinger.com`, más una hoja y un
script propios. Son unos 18 kB en total, una resolución de dominio y una
conexión a un servidor ajeno en cada visita.

Viene preinstalado con el hosting y sirve para captar suscriptores por
correo. Si no se usa, conviene desactivarlo: quita la única dependencia
externa que tiene el sitio. Si se usa, ese servicio ve la dirección IP de
cada visitante y eso hay que declararlo en el aviso de privacidad.

### Estado de la caché

LiteSpeed Cache ya está instalado y activo. La configuración fina se revisa
cuando la tienda tenga imágenes y catálogo completos, porque optimizar una
portada vacía produciría una medición engañosa.

## Pendientes conocidos

### Bloquean vender

1. **Obtener la revisión de un abogado** de las cuatro páginas legales. Los
   datos operativos ya están completos en los patterns y en las páginas
   guardadas de WordPress. El aviso visible de borrador se conserva.
2. **Completar el catálogo.** Hay un producto publicado y nueve borradores
   importados desde `imports/catalogo-borradores.csv`. Todos siguen sin precio,
   inventario ni datos variables de producción. El producto existente también
   necesita su ficha de lote y cambiar su título de “Cápsulas” a “Microdosis”.
3. **Probar el flujo completo de compra** con SPEI, carga de comprobante y
   confirmación desde el panel.

### Necesitan datos del cliente

4. Precio, fotografía, ingredientes, alérgenos, contenido y datos reales de
   lote para cada producto.
5. Revisión de un abogado antes de retirar el aviso visible de borrador.

### Configuración de WordPress

6. Revisar cabecera y pie de las plantillas internas desde Blocksy.
7. Traducir los títulos y slugs de Shop, Cart, Checkout y My account. El menú
   principal ya muestra Inicio, Catálogo, Política de envíos y Mi cuenta, y
   está asignado a escritorio y móvil.

El sitio ya usa Español de México, zona horaria de Ciudad de México, unidades
métricas, descripción corta y reseñas solo para compradores verificados. El
aviso de privacidad está asignado y las antiguas páginas inglesas están en la
papelera.

### Probar en el navegador

10. Flujo completo de compra: pedido con SPEI, subida del comprobante y
    confirmación desde el panel.

### Mejoras sugeridas, no bloqueantes

- Las páginas de la tienda están en inglés: `/shop/`, `/cart/`, `/checkout/`
  y `/my-account/`. Traducirlas ya no rompe nada, porque los enlaces de los
  patterns se resuelven solos.
- Sacar los comprobantes de la raíz web con la constante
  `COREMUSHROOM_DIR_COMPROBANTES` en `wp-config.php`.

## Hechos verificados contra el servidor

- Hosting: Hostinger, LiteSpeed, PHP 8.3.30, copias diarias activas.
- El subdominio de desarrollo vive en `public_html/core/`, no en
  `domains/core.bancodeesporas.com/`. El campo Directory del despliegue por
  Git lleva el prefijo `core/`. Es relativo al `public_html` de la cuenta,
  que es compartida entre el dominio principal y el subdominio.
- Blocksy 2.1.56. Su handle de estilos es `ct-main-styles` y usa las
  variables `--theme-palette-color-1` a `-8`, `--theme-font-family`,
  `--theme-text-color` y `--theme-normal-container-max-width`.
- Blocksy declara su paleta en un bloque en línea con id
  `ct-main-styles-inline-css` que llega **después** de nuestras hojas, aunque
  estas dependan de su handle. Por eso el puente de color usa `:root:root`.
  No lo bajes a `:root` o Blocksy vuelve a ganar.
- El `.htaccess` funciona en LiteSpeed: documentación y dotfiles bloqueados,
  activos servidos, `functions.php` ejecutado y no expuesto como texto.
- LiteSpeed Cache está instalado y activo.
- El webhook de Hostinger actualiza archivos pero no purga LiteSpeed. Cada
  publicación funcional sube `COREMUSHROOM_VERSION` en `functions.php` y el
  campo `Version` de `style.css`. `inc/rendimiento.php` detecta esa versión,
  llama una sola vez a `litespeed_purge_all` y evita que la URL pública siga
  mostrando HTML anterior.
- Verificación pública del 9 de septiembre de 2026: la portada está accesible,
  el sitio usa español y el menú principal está asignado. WooCommerce todavía
  intercepta la ficha del producto con su pantalla de próxima apertura.
- Verificación del 10 de septiembre de 2026: las cuatro páginas legales están
  sincronizadas y no contienen marcadores; conservan el aviso de revisión
  pendiente. WooCommerce muestra 10 productos: 1 publicado y 9 borradores.
- El enlace histórico `/envios/` redirige de forma permanente a
  `/politica-de-envios/`. El pattern nuevo ya usa el destino correcto.
