<?php
/**
 * Title: Pie de pagina
 * Slug: coremushroom/pie
 * Categories: coremushroom
 * Description: Pie con navegacion, enlaces legales y aviso de uso previsto.
 * Keywords: pie, footer, legal, avisos
 * Viewport Width: 1400
 *
 * Los cuatro enlaces legales apuntan a paginas que se redactan en la Fase 6 y
 * que revisa un abogado antes de publicarse. Mientras no existan, el enlace
 * devuelve 404: es preferible a borrarlo y olvidarlo.
 *
 * COPY PROVISIONAL.
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
<!-- wp:group {"backgroundColor":"bosque","textColor":"crema","style":{"spacing":{"padding":{"top":"var:preset|spacing|65","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-crema-color has-bosque-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--65);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|55","left":"var:preset|spacing|55"}}}} -->
<div class="wp-block-columns"><!-- wp:column {"width":"40%"} -->
<div class="wp-block-column" style="flex-basis:40%"><!-- wp:heading {"level":2,"fontSize":"xl"} -->
<h2 class="wp-block-heading has-xl-font-size">CoreMushroom</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"sm","style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
<p class="has-sm-font-size" style="margin-top:var(--wp--preset--spacing--40)">Derivados de Cordyceps, Hericium y Trametes. Chocolate, tisana y capsula.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3,"fontSize":"xs","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.08em","fontWeight":"700"}}} -->
<h3 class="wp-block-heading has-xs-font-size" style="font-weight:700;letter-spacing:0.08em;text-transform:uppercase">Tienda</h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"cm-lista-plana","style":{"typography":{"lineHeight":"2"}},"fontSize":"sm"} -->
<ul class="wp-block-list cm-lista-plana has-sm-font-size" style="line-height:2"><!-- wp:list-item -->
<li><a href="<?php echo esc_url( $cm_url( 'shop', '/shop/' ) ); ?>">Catalogo</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo esc_url( $cm_url( 'cart', '/cart/' ) ); ?>">Carrito</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo esc_url( $cm_url( 'myaccount', '/my-account/' ) ); ?>">Mi cuenta</a></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3,"fontSize":"xs","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.08em","fontWeight":"700"}}} -->
<h3 class="wp-block-heading has-xs-font-size" style="font-weight:700;letter-spacing:0.08em;text-transform:uppercase">Legal</h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"cm-lista-plana","style":{"typography":{"lineHeight":"2"}},"fontSize":"sm"} -->
<ul class="wp-block-list cm-lista-plana has-sm-font-size" style="line-height:2"><!-- wp:list-item -->
<li><a href="/aviso-de-privacidad">Aviso de privacidad</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="/terminos-de-uso">Terminos de uso</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="/politica-de-envios">Politica de envios</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="/uso-previsto">Declaracion de uso previsto</a></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:separator {"backgroundColor":"hueso","style":{"spacing":{"margin":{"top":"var:preset|spacing|55","bottom":"var:preset|spacing|45"}}}} -->
<hr class="wp-block-separator has-text-color has-hueso-color has-alpha-channel-opacity has-hueso-background-color has-background" style="margin-top:var(--wp--preset--spacing--55);margin-bottom:var(--wp--preset--spacing--45)"/>
<!-- /wp:separator -->

<!-- wp:paragraph {"fontSize":"xs"} -->
<p class="has-xs-font-size">Este sitio no ofrece informacion medica. Los productos son alimentos y suplementos alimenticios. No son medicamentos y no sustituyen la consulta con un profesional de la salud.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
