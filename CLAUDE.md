# CoreMushroom - memoria del proyecto

## Qué es

Tienda WooCommerce de derivados funcionales de hongo. Tema hijo de Blocksy
sobre Hostinger. El desarrollo se hace desde Termux en Android con Git, así que
todo lo declarable en código se declara en código, no en interfaces de
configuración.

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
  y afines.
- **No se copia texto, CSS ni imágenes de ningún sitio existente.** El modelo
  comercial de la competencia sí se puede replicar. Sus activos no.
- **No se toca el tema padre.** Blocksy se actualiza y arrasaría el cambio.

## Decisiones tomadas

- Dirección estética: saturada y lúdica, cercana a mushlove.mx.
- La línea de cápsulas se llama **microdosis**. Decisión tomada y cerrada.
  El copy alrededor de esa línea se mantiene estrictamente sensorial: nada de
  efecto, beneficio ni resultado.
- Envío gratis a partir de 900 pesos.
- Pagos vía plugin oficial de Mercado Pago: tarjeta, meses sin intereses,
  depósito en OXXO y SPEI. Sin BTCPay. Sin pasarela escrita a mano.
- Tipografía: Fraunces para titulares, Figtree para texto. Autoalojadas.
- La raíz del repositorio es la carpeta del tema hijo. Se despliega a
  `public_html/wp-content/themes/coremushroom`.

## Fase 5, pagos y checkout: TERMINADA

Estado: Fases 1 a 6 terminadas y desplegadas. Falta la Fase 7,
rendimiento, y todo lo que depende de datos o contenido del cliente.

### Decisiones de pago, actualizadas

- **Nada de Mercado Pago.** El cliente lo descartó por comisiones.
- **SPEI manual con comprobante** es el método principal. Comisión cero. El
  cliente transfiere, sube su comprobante, el dueño verifica y el pedido
  avanza. Es lo que permite vender sin depender de ninguna aprobación.
- **Tarjeta**: se solicita a Conekta con el nombre y el catálogo reales de
  CoreMushroom, declarando alimentos y suplementos alimenticios. Si rechaza,
  Stripe, y después Openpay.

### Petición rechazada y por qué

El cliente pidió replicar lo que hace la competencia: cobrar a través de una
tienda con nombre neutro en otro dominio para que el procesador no vea qué se
vende. Eso es lavado transaccional. Se rechazó y no se implementa, aunque se
reitere. Las consecuencias reales son retención del saldo por meses e
inscripción en la lista de comercios terminados, que bloquea abrir cuenta con
cualquier procesador durante años.

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

- **Nunca enganchar nada a `plugins_loaded` desde `functions.php`.** WordPress
  incluye el `functions.php` del tema después de haber disparado ese gancho,
  así que la llamada no se ejecuta jamás y el código queda muerto sin ningún
  error. Los módulos de `inc/` se cargan con una llamada directa.
  `tools/prueba-arranque.php` existe para que esto no vuelva a pasar.
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

Este archivo y el README se despliegan dentro de `public_html`. El `.htaccess`
del tema bloquea los `.md` y el directorio `.git` por HTTP. Aun así, no
escribas aquí nada que no soportaría ser leído por un tercero: sin
credenciales, sin rutas locales, sin valoraciones sobre clientes o
proveedores.

## SIGUIENTE PASO: Fase 7, rendimiento

Es lo único que queda de código y no depende de nadie. Incluye optimización
de imágenes, carga diferida, CSS crítico, títulos y descripciones bien
formados, y LiteSpeed Cache, que el hosting ofrece y todavía no está
instalado.

## Pendientes conocidos

### Bloquean vender

1. **Datos de la cuenta bancaria**: beneficiario, banco y CLABE. Sin CLABE la
   pasarela SPEI no aparece en el checkout, a propósito. Se configura en
   WooCommerce, Ajustes, Pagos, Transferencia SPEI.
2. **Productos reales** con su ficha de lote llena. Hoy hay cero.
3. **Página de términos asignada** en WooCommerce, Ajustes, Avanzado. Sin
   ella la casilla de aceptación no aparece en el checkout, y el propio
   código avisa de eso en el panel.

### Necesitan datos del cliente

4. Los marcadores entre dobles corchetes de las cuatro páginas legales.
   Listarlos con
   `grep -oh "\[\[[^]]*\]\]" patterns/legal-*.php | sort -u`.
5. Revisión de un abogado antes de publicar esas cuatro páginas.

### Configuración de WordPress

6. Cabecera y pie de Blocksy. El pie sigue diciendo que el tema es de
   WordPress. Se hace desde el personalizador, que guarda en base de datos y
   no en el repositorio: es una excepción consciente al criterio de tenerlo
   todo en código.
7. Menú de navegación. Hoy muestra Cart, Checkout, My account y Shop, que es
   lo que WordPress puso solo.
8. Borrar las páginas Privacy Policy y Refund and Returns Policy que creó
   WooCommerce en inglés, para no tener dos avisos de privacidad.
9. Quitar el modo "Store coming soon" el día de abrir.

### Probar en el navegador

10. Flujo completo de compra: pedido con SPEI, subida del comprobante y
    confirmación desde el panel.

### Mejoras sugeridas, no bloqueantes

- El panel de WordPress está en inglés. Cambiarlo a Español de México hace
  que los menús coincidan con la documentación.
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
- LiteSpeed Cache no está instalado todavía. Se usará en la Fase 7.
