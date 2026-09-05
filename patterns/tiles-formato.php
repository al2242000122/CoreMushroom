<?php
/**
 * Title: Tres tiles por formato
 * Slug: coremushroom/tiles-formato
 * Categories: coremushroom
 * Description: Chocolate, tisana y capsula. Divide el catalogo por formato.
 * Keywords: tiles, formato, categorias, chocolate, tisana, capsula
 * Viewport Width: 1400
 *
 * Los tiles dividen POR FORMATO, no por especie. Los colores conservan los
 * nombres de las especies porque asi se llaman en la paleta y porque los
 * badges de producto si van por especie. Aqui el color solo distingue el
 * formato: no significa Cordyceps ni Hericium ni Trametes.
 *
 * Sobre estos tres colores el texto va SIEMPRE en tinta. Blanco sobre ellos
 * queda entre 2.87 y 3.26 a 1, por debajo del minimo de 4.5.
 *
 * COPY PROVISIONAL.
 */

// Corta si el archivo se pide por URL. WordPress lo incluye durante init,
// cuando ABSPATH ya existe, asi que al registrarse el patron no se corta.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|50","left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"backgroundColor":"cordyceps","textColor":"tinta","style":{"border":{"radius":"20px"},"spacing":{"padding":{"top":"var:preset|spacing|55","bottom":"var:preset|spacing|55","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-tinta-color has-cordyceps-background-color has-text-color has-background" style="border-radius:20px;padding-top:var(--wp--preset--spacing--55);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--55);padding-left:var(--wp--preset--spacing--50)"><!-- wp:heading {"level":3,"fontSize":"xl"} -->
<h3 class="wp-block-heading has-xl-font-size">Chocolate</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"sm"} -->
<p class="has-sm-font-size">Cacao 70% con extracto. En barra y en pieza individual.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"backgroundColor":"hericium","textColor":"tinta","style":{"border":{"radius":"20px"},"spacing":{"padding":{"top":"var:preset|spacing|55","bottom":"var:preset|spacing|55","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-tinta-color has-hericium-background-color has-text-color has-background" style="border-radius:20px;padding-top:var(--wp--preset--spacing--55);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--55);padding-left:var(--wp--preset--spacing--50)"><!-- wp:heading {"level":3,"fontSize":"xl"} -->
<h3 class="wp-block-heading has-xl-font-size">Tisana</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"sm"} -->
<p class="has-sm-font-size">Hebra suelta y en sobre. Para preparar en caliente.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"backgroundColor":"trametes","textColor":"tinta","style":{"border":{"radius":"20px"},"spacing":{"padding":{"top":"var:preset|spacing|55","bottom":"var:preset|spacing|55","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-tinta-color has-trametes-background-color has-text-color has-background" style="border-radius:20px;padding-top:var(--wp--preset--spacing--55);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--55);padding-left:var(--wp--preset--spacing--50)"><!-- wp:heading {"level":3,"fontSize":"xl"} -->
<h3 class="wp-block-heading has-xl-font-size">Capsula</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"sm"} -->
<p class="has-sm-font-size">Extracto en capsula vegetal. Frasco de 60 y de 120.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
