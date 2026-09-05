<?php
/**
 * Title: Barra superior de envio gratis
 * Slug: coremushroom/barra-envio
 * Categories: coremushroom
 * Description: Franja delgada sobre la cabecera con el umbral de envio gratis.
 * Keywords: barra, envio, aviso, cabecera
 * Viewport Width: 1400
 *
 * El umbral esta escrito en el texto a proposito. Es un dato comercial que
 * cambia sin previo aviso y debe poder editarse desde el editor sin tocar
 * codigo ni volver a desplegar.
 */

// Corta si el archivo se pide por URL. WordPress lo incluye durante init,
// cuando ABSPATH ya existe, asi que al registrarse el patron no se corta.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:group {"backgroundColor":"bosque","textColor":"crema","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|45","right":"var:preset|spacing|45"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-crema-color has-bosque-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--45);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--45)"><!-- wp:paragraph {"align":"center","fontSize":"xs","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.08em","fontWeight":"700"}}} -->
<p class="has-text-align-center has-xs-font-size" style="font-weight:700;letter-spacing:0.08em;text-transform:uppercase">Envio gratis en pedidos desde 900 pesos</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
