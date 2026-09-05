<?php
/**
 * Title: Franja de confianza
 * Slug: coremushroom/franja-confianza
 * Categories: coremushroom
 * Description: Tres puntos: envio, pago seguro y trazabilidad por lote.
 * Keywords: confianza, envio, pago, lote
 * Viewport Width: 1400
 *
 * Los tres puntos son hechos operativos verificables. No hay garantias de
 * resultado ni promesas sobre el producto: una garantia mal redactada se lee
 * como promesa de efecto.
 *
 * El borde lleva style solid de forma explicita. Sin el, border-style vale
 * none por defecto y las dos lineas se declaran pero no se pintan: el ancho
 * y el color quedan inertes.
 *
 * COPY PROVISIONAL.
 */

// Corta si el archivo se pide por URL. WordPress lo incluye durante init,
// cuando ABSPATH ya existe, asi que al registrarse el patron no se corta.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:group {"backgroundColor":"crema","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}},"border":{"top":{"color":"var:preset|color|tinta","width":"2px","style":"solid"},"bottom":{"color":"var:preset|color|tinta","width":"2px","style":"solid"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-crema-background-color has-background" style="border-top-color:var(--wp--preset--color--tinta);border-top-style:solid;border-top-width:2px;border-bottom-color:var(--wp--preset--color--tinta);border-bottom-style:solid;border-bottom-width:2px;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|55","left":"var:preset|spacing|55"}}}} -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3,"fontSize":"md"} -->
<h3 class="wp-block-heading has-md-font-size">Envio a todo Mexico</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"grafito","fontSize":"sm"} -->
<p class="has-grafito-color has-text-color has-sm-font-size">Paqueteria con guia rastreable. Envio gratis desde 900 pesos.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3,"fontSize":"md"} -->
<h3 class="wp-block-heading has-md-font-size">Pago seguro</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"grafito","fontSize":"sm"} -->
<p class="has-grafito-color has-text-color has-sm-font-size">Tarjeta, transferencia SPEI y deposito en OXXO.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3,"fontSize":"md"} -->
<h3 class="wp-block-heading has-md-font-size">Producto lote a lote</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"grafito","fontSize":"sm"} -->
<p class="has-grafito-color has-text-color has-sm-font-size">Cada empaque lleva su codigo de lote y su ficha de contenido.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
