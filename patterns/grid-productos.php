<?php
/**
 * Title: Reticula de productos
 * Slug: coremushroom/grid-productos
 * Categories: coremushroom
 * Description: Titulo de seccion y reticula de productos de WooCommerce.
 * Keywords: productos, catalogo, reticula, tienda
 * Viewport Width: 1400
 *
 * Se usa el shortcode [products] de WooCommerce en vez del bloque Product
 * Collection a proposito. El shortcode es API estable y no cambia entre
 * versiones; el marcado del bloque si cambia, y un patron guardado con
 * marcado viejo se rompe en el editor al actualizar el plugin.
 *
 * Cuando haya productos cargados se puede sustituir por el bloque desde el
 * editor, sin tocar este archivo.
 */

// Corta si el archivo se pide por URL. WordPress lo incluye durante init,
// cuando ABSPATH ya existe, asi que al registrarse el patron no se corta.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:group {"backgroundColor":"hueso","style":{"spacing":{"padding":{"top":"var:preset|spacing|65","bottom":"var:preset|spacing|65"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-hueso-background-color has-background" style="padding-top:var(--wp--preset--spacing--65);padding-bottom:var(--wp--preset--spacing--65)"><!-- wp:heading {"level":2,"fontSize":"xxl"} -->
<h2 class="wp-block-heading has-xxl-font-size">Lo mas reciente</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"grafito","fontSize":"md","style":{"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|55"}}}} -->
<p class="has-grafito-color has-text-color has-md-font-size" style="margin-top:var(--wp--preset--spacing--40);margin-bottom:var(--wp--preset--spacing--55)">Cada producto muestra su formato, su especie y su codigo de lote.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[products limit="8" columns="4" orderby="date" order="DESC"]
<!-- /wp:shortcode --></div>
<!-- /wp:group -->
