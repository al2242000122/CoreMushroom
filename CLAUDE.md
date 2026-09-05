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
