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
| Sitio escaparate | `core.bancodeesporas.com` |
| Sitio receptor de pagos | `coreadaptogenos.app` (HTTPS activo) |
| Repositorio público | `github.com/al2242000122/CoreMushroom` |
| Hosting | Hostinger, LiteSpeed, PHP 8.3 |
| Tema padre | Blocksy 2.1.57; sus archivos no se editan |
| Tienda | WooCommerce |

El dominio receptor `coreadaptogenos.app` está registrado en Name.com y ya
resuelve al WordPress de Hostinger por HTTPS. CoreMushroom vive en
`core.bancodeesporas.com` y conserva la indexación bloqueada mientras se
terminan catálogo y cobro con tarjeta.

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

Para búsquedas rápidas y sesiones que sobreviven a una compactación de
contexto, instala las herramientas locales del proyecto:

```powershell
.\tools\instalar-herramientas.ps1 -Indexar
```

La explicación y el uso diario están en
[docs/herramientas-desarrollo.md](docs/herramientas-desarrollo.md). El índice
`.tgrep/` es local y no se sube al repositorio.

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

# Puente firmado de tarjeta hacia CoreAdaptogenos
php tools/prueba-pago-coreadaptogenos.php .

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

# Avisos legales antiguos y fichas sin fotografías
php tools/prueba-legal.php .
php tools/prueba-sin-fotos.php .

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
- Las cuatro páginas legales publicadas. El dueño confirmó terminada la
  revisión legal el 19 de septiembre de 2026.
- Un producto publicado: Cordyceps · Microdosis 30 cápsulas, en la categoría
  Microdosis y con su slug definitivo.
- CLABE y beneficiario capturados en la pasarela SPEI.
- LiteSpeed Cache instalado y activo.
- Contacto WhatsApp Business público de ambas marcas: 220 644 6651;
  enlace `https://wa.me/522206446651`. CoreMushroom lo declara en
  `inc/contacto.php` y aparece al pie de todas sus páginas. El número
  anterior 669 163 0086 se sustituyó.
- La tienda dejó el modo “Próximamente” y ya es pública. El checkout muestra
  únicamente SPEI, términos y mayoría de edad.
- El plugin receptor propio de CoreAdaptogenos está instalado y activo en el
  WordPress temporal. Su endpoint está habilitado, con origen permitido en
  `https://core.bancodeesporas.com`.
- El mismo secreto compartido quedó guardado en ambos paneles. Nunca se escribe
  en Git ni se muestra en esta memoria.
- La pasarela “Tarjeta mediante CoreAdaptógenos” usa el receptor
  `https://coreadaptogenos.app`, entorno de pruebas y el secreto compartido.
  Está activada solo para administradores; el público sigue viendo SPEI.
- Ventas y envíos limitados a México. La zona México ofrece envío terrestre
  gratis desde $900 y precio fijo de $120 por debajo de ese monto. El costo
  fijo se guardó en WooCommerce el 19 de septiembre de 2026. Se comprobó un
  carrito de $450 con total de $570 y otro de $900 con envío gratis y total
  de $900. En el segundo, el cliente también puede elegir el método de $120.
- Las páginas de WooCommerce se llaman Catálogo, Carrito, Finalizar compra y
  Mi cuenta, con slugs en español. Los textos de privacidad del checkout
  también están en español.
- El checkout público muestra únicamente SPEI. Los pagos con cheque y
  WooPayments están desactivados. La casilla obligatoria de mayoría de edad se valida por
  separado de la aceptación de términos y ambas constancias quedan en el
  pedido. La validación cubre el checkout clásico, Checkout Blocks y Store
  API.
- Solo siguen activos WooCommerce, LiteSpeed Cache y Hostinger Tools. Reach,
  Hostinger AI y Easy Onboarding están desactivados.
- La portada se puede abrir sin iniciar sesión. WooCommerce todavía intercepta
  la ficha del producto con su pantalla inglesa de próxima apertura.
- Portada rediseñada con una paleta neutra, textos más cercanos y una
  ilustración original inspirada en Amanita muscaria. La ilustración es un
  motivo de marca y no representa las especies vendidas.

---

## Lo que falta, por orden

### Bloquea abrir la tienda

1. **Completar los nueve borradores importados.** Se cargaron desde
   [imports/catalogo-borradores.csv](imports/catalogo-borradores.csv) el 10 de
   septiembre de 2026. Están sin precio, inventario ni datos variables de
   producción. Solo precargan especie, formato y tipo de preparación; todo lo
   demás tiene que coincidir con la etiqueta física antes de publicar.

4. **Llenar la ficha de lote del producto que ya existe.** Se publicó sin
   ella, así que su tabla no aparece y su tarjeta no lleva badge de especie.
   Eso es el comportamiento correcto con campos vacíos, no un error.

5. **Conseguir datos físicos de cada producto.** El dueño pidió mantener los
   nueve sin precio en borrador y no agregar fotografías por ahora. El tema
   omite el marcador de imagen cuando no hay foto propia. Faltan precio,
   contenido real, ingredientes, alérgenos y lote antes de ponerlos a la venta.

6. **Completar la habilitación comercial de Stripe.** El dueño
   confirmó WordPress/WooCommerce y eligió Stripe el 15 de septiembre de 2026.
   El dominio definitivo `coreadaptogenos.app` se registró en Name.com y se
   conectó al WordPress receptor de Hostinger el 16 de septiembre. Name.com
   conserva como únicos nameservers `aurora.dns-parking.com` y
   `nebula.dns-parking.com`. El 19 de septiembre se comprobaron registros A,
   HTTPS 200 en raíz y www, WordPress en la raíz y la ruta REST del puente.
   Stripe está conectado en vivo y en pruebas; falta su aprobación del catálogo
   real y de la relación entre los dos dominios.
   Se usa la extensión oficial de Stripe y el checkout nativo de WooCommerce.
   CoreMushroom conservará el pedido y redirigirá mediante una
   sesión opaca; el monto se recuperará de servidor a servidor y el pago solo
   se confirmará con webhook firmado. El cliente verá antes de salir que
   CoreAdaptogenos es la razón social cobradora.
   La metodología acordada está en
   [docs/pagos-coreadaptogenos.md](docs/pagos-coreadaptogenos.md).

   El puente servidor ya existe en ambos repositorios. CoreMushroom emite una
   sesión firmada y recibe el callback; el plugin de CoreAdaptogenos crea un
   pedido espejo transparente y abre el `order-pay` oficial de WooCommerce con
   Stripe como única pasarela. El plugin y el secreto ya están configurados.
   Stripe en pruebas ya está conectado y el recorrido aprobado se completó.
   La pasarela de pruebas de CoreMushroom solo se muestra a administradores.
   No mostrar tarjeta al público antes de que todo eso pase. El dueño aclaró el
   19 de septiembre que Stripe cobrará los pedidos de CoreMushroom en el
   WooCommerce receptor; Conekta se reserva para ventas propias de
   CoreAdaptogenos en otra fase. La restricción a Stripe del pedido espejo
   impide que una futura instalación de Conekta altere este flujo. OXXO
   queda para una fase posterior.

   Se confirmó acceso al WordPress receptor mediante el host temporal de
   Hostinger y se instalaron y activaron el plugin propio y la extensión
   oficial WooCommerce Stripe Gateway 11.0.0. El 19 de septiembre se
   conectaron la cuenta en vivo y la de pruebas. El webhook en vivo está
   configurado. El de pruebas rechazó sus primeras firmas; se usó el botón
   oficial «Reconfigurar los webhooks», con autorización del dueño. Tras la
   compra de prueba, WooCommerce confirmó que el webhook de las 21:37:48 UTC
   se procesó correctamente; la pantalla aún indicaba al menos uno pendiente.
   CoreMushroom
   solo redirige y conserva el pedido; Stripe cobra en CoreAdaptogenos.
   El 19 de septiembre se probó con datos ficticios y tarjeta de pruebas:
   CoreMushroom creó el pedido #55, el puente abrió el pedido espejo #27 en
   CoreAdaptogenos y ambos quedaron en «Procesando» con $900 MXN pagados.
   La página de gracias regresó a CoreMushroom; allí constan el callback de
   pago y las aceptaciones de términos y mayoría de edad. No hubo cargo real.
   El 21 de septiembre se completó el rechazo controlado: CoreMushroom #56
   quedó pendiente de pago y el espejo #32 quedó fallido con el mensaje de
   tarjeta rechazada. También se reembolsó íntegramente el cargo simulado del
   pedido #55: el espejo #27 y el origen #55 quedaron «Reembolsado» tras los
   webhooks y el callback firmado. No hubo dinero real. Para pasar a vivo solo
   queda la confirmación comercial de Stripe y cambiar, en ese orden, Stripe y
   el emisor CoreMushroom a producción.
   El 21 de septiembre se comprobó además que la sección «Productos» de
   Stripe está vacía; no hay que llenarla para este flujo, porque WooCommerce
   conserva el catálogo y genera el cobro del pedido espejo. El resumen de
   saldo mostraba MXN 0.00 y sin transferencias. Ese mismo día se activaron
   transferencias automáticas diarias, sin saldo mínimo retenido. No se
   documenta en Git ningún dato de la cuenta bancaria vinculada.
   El receptor React está guardado en la rama
   `codex/coremushroom-payment-receiver` del otro repo; no está desplegado.

### CoreAdaptogenos público

El 21 de septiembre de 2026 se sustituyó la portada predeterminada por una
página estática propia. Explica de forma visible que CoreAdaptogenos procesa
con Stripe los pagos de pedidos iniciados en CoreMushroom, muestra el importe
verificado, el regreso al pedido original y el WhatsApp 220 644 6651. El pie
se reescribió por completo y ya no contiene marcadores `trans-*`. El título
del sitio quedó como “CoreAdaptógenos” y la descripción corta como “Pagos con
tarjeta para pedidos CoreMushroom”.

### Configuración pendiente

7. **Afinar el nombre del envío de $120 si procede.** El dueño definió $120
   para pedidos menores de $900 y se configuró «Precio fijo» en la zona México.
   El 19 de septiembre se verificaron los dos lados del umbral en el carrito,
   además del envío gratis y SPEI en el checkout. Queda decidir si el método
   pagado debe describirse como exprés y qué plazo real ofrecerá.
8. **Cabecera y pie de Blocksy.** La portada usa el pie propio del tema; falta
   revisar las plantillas internas. Se hace desde el personalizador de Blocksy, que guarda en base
   de datos y no en este repositorio. Es una excepción consciente al criterio
   de tenerlo todo en código; está anotada en AGENTS.md.

El nombre, la descripción corta, el idioma Español de México, la zona horaria
de Ciudad de México, las unidades métricas, la página de privacidad y las
reseñas limitadas a compradores verificados quedaron configurados. Las tres
páginas inglesas antiguas ya están en la papelera.

### Probar en el navegador

12. **Completar el flujo de compra.** El 12 de septiembre de 2026 se verificó
    hasta el checkout: producto, cantidad, total de $900, envío gratis, SPEI,
    privacidad y términos. Falta crear un pedido de prueba, subir un
    comprobante marcado SIN VALOR, verificarlo y confirmarlo desde el panel.

13. **Revisar la ficha pública del producto.** La tienda ya está publicada;
    comprobar la ficha, los datos de lote y el recorrido de SPEI después de
    cargar el catálogo real.

### Mejoras que no bloquean

- Sacar los comprobantes de la raíz web definiendo
  `COREMUSHROOM_DIR_COMPROBANTES` en `wp-config.php`.
- Borrar los plugins y temas inactivos solo después de decidir cuáles se
  conservarán como respaldo. Desactivarlos ya retiró su código del sitio.

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
