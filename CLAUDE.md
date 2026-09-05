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

## SIGUIENTE PASO: Fase 5, pagos y checkout

Estado: Fases 1 a 4 terminadas, verificadas y desplegadas.

Decisiones ya tomadas para la Fase 5:

- Pagos con el plugin oficial de Mercado Pago: tarjeta, meses sin intereses,
  depósito en OXXO y transferencia SPEI. Sin BTCPay. Ninguna pasarela escrita
  a mano.
- Falta acordar: si se usa además la pasarela de transferencia bancaria
  directa de WooCommerce para SPEI manual, o si todo va por Mercado Pago.
- Falta acordar la casilla obligatoria del checkout. El brief original pedía
  mayoría de edad y aceptación de términos, con registro del consentimiento
  en el pedido. Sin Psilocybe, la mayoría de edad ya no es obligatoria.
  Proponer: casilla única de aceptación de términos de uso, con el
  consentimiento guardado en el pedido y visible en el panel.
- Aviso importante que dar al cliente: Mercado Pago revisa el dominio y el
  copy al aprobar la cuenta. Conviene tener las páginas legales publicadas
  antes de solicitarla, aunque sean borrador.

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

## Pendientes conocidos

Ninguno abierto en la Fase 1.

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
