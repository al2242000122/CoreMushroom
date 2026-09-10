# Importación inicial del catálogo

**Importado en producción el 10 de septiembre de 2026:** nueve productos
creados como borrador, sin precio y marcados sin inventario. El archivo se
conserva como registro reproducible; no debe volver a importarse en este sitio.

`catalogo-borradores.csv` contiene los nueve productos que todavía no existen
en WooCommerce. Todos entran como **borrador**, sin precio, fotografía,
concentración, contenido neto, ingredientes, alérgenos ni fechas de lote.

Esos campos se dejan vacíos a propósito: deben copiarse de la etiqueta física
y de la documentación real de la maquila antes de publicar cada producto.

Para importarlo:

1. En WordPress, entra a **Productos > Todos los productos > Importar**.
2. Selecciona `imports/catalogo-borradores.csv`.
3. Deja desactivada la actualización de productos existentes.
4. Revisa el mapeo y ejecuta el importador.
5. Confirma que creó nueve borradores y ningún producto publicado.

El archivo excluye `Cordyceps · Cápsulas 30`, porque ese producto ya existe en
el sitio.

Este archivo es de uso único. Si los SKU ya existen, no vuelvas a importarlo:
edita los borradores directamente en WooCommerce para no devolver a borrador
un producto que ya esté completo.
