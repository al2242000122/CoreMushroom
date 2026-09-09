<?php
/**
 * Title: Hero del home
 * Slug: coremushroom/hero
 * Categories: coremushroom
 * Description: Bloque de apertura con etiqueta, titular, bajada y dos acciones.
 * Keywords: hero, portada, home, apertura
 * Viewport Width: 1400
 *
 * COPY PROVISIONAL. El definitivo se escribe en la Fase 6 y lo revisa un
 * abogado. No agregar aqui nada sobre efectos, beneficios ni resultados.
 */

// Corta si el archivo se pide por URL. WordPress lo incluye durante init,
// cuando ABSPATH ya existe, asi que al registrarse el patron no se corta.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Las URL de la tienda se resuelven en tiempo de registro, no se escriben a
// mano. WooCommerce permite cambiar los slugs de tienda, carrito y cuenta, y
// un enlace fijo se vuelve un 404 en cuanto alguien los traduce.
$cm_url = static function ( $pagina, $respaldo ) {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$url = wc_get_page_permalink( $pagina );

		if ( is_string( $url ) && '' !== $url ) {
			return $url;
		}
	}

	return home_url( $respaldo );
};
?>
<!-- wp:group {"backgroundColor":"crema","style":{"spacing":{"padding":{"top":"var:preset|spacing|65","bottom":"var:preset|spacing|65"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group cm-home-hero has-crema-background-color has-background" style="padding-top:var(--wp--preset--spacing--65);padding-bottom:var(--wp--preset--spacing--65)"><!-- wp:paragraph {"textColor":"cordyceps","fontSize":"xs","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.08em","fontWeight":"700"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
<p class="has-cordyceps-color has-text-color has-xs-font-size" style="margin-bottom:var(--wp--preset--spacing--40);font-weight:700;letter-spacing:0.08em;text-transform:uppercase">Hongos para todos los días</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"fontSize":"xxxl"} -->
<h1 class="wp-block-heading has-xxxl-font-size">El lado más amable de los hongos</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"grafito","fontSize":"md","style":{"spacing":{"margin":{"top":"var:preset|spacing|45","bottom":"var:preset|spacing|55"}}}} -->
<p class="has-grafito-color has-text-color has-md-font-size" style="margin-top:var(--wp--preset--spacing--45);margin-bottom:var(--wp--preset--spacing--55)">Chocolate, tisanas y cápsulas con Cordyceps, Hericium, Trametes y Ganoderma.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $cm_url( 'shop', '/shop/' ) ); ?>">Ver catálogo</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/politica-de-envios/' ) ); ?>">Cómo enviamos</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
