<?php
/**
 * CoreMushroom - tarjeta de producto del catalogo.
 *
 * Agrega al bucle de la tienda el badge de especie y la linea de
 * disponibilidad, usando los componentes cm-badge del sistema de diseno.
 *
 * Se hace con los ganchos de WooCommerce, no sobrescribiendo la plantilla
 * content-product.php. Sobrescribirla obligaria a mantener una copia del
 * archivo del plugin y a revisarla en cada actualizacion de WooCommerce.
 * Con ganchos, si WooCommerce cambia el marcado por dentro, esto sigue
 * funcionando.
 *
 * @package CoreMushroom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Badge de especie sobre la imagen del producto en el bucle.
 *
 * Prioridad 15: despues de la imagen, que se imprime en 10, para que el badge
 * quede dentro del enlace y encima de la foto.
 */
function coremushroom_badge_especie_bucle() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$especie = coremushroom_obtener_lote( $product->get_id(), 'especie' );

	if ( '' === $especie ) {
		return;
	}

	$etiqueta = coremushroom_etiqueta_opcion( 'especie', $especie );

	if ( '' === $etiqueta ) {
		return;
	}

	// La clase de color coincide con la clave de la especie porque los slugs
	// de la paleta se llaman igual. Se pasa por sanitize_html_class de todos
	// modos: el valor viene de la base de datos y nunca se imprime crudo.
	printf(
		'<span class="cm-tarjeta__marca"><span class="cm-badge cm-badge--%1$s">%2$s</span></span>',
		esc_attr( sanitize_html_class( $especie ) ),
		esc_html( $etiqueta )
	);
}
add_action( 'woocommerce_before_shop_loop_item_title', 'coremushroom_badge_especie_bucle', 15 );

/**
 * Linea de formato y disponibilidad debajo del titulo en el bucle.
 *
 * Prioridad 6: entre el titulo, que va en 10 sobre el gancho anterior, y el
 * precio, que va en 10 sobre este. Asi el dato de stock queda pegado al
 * nombre y no separa el precio del boton.
 *
 * Sobre disponibilidad se dice solo si hay o no hay. No se muestra el numero
 * de unidades: es un dato operativo que cambia a cada pedido y ver "quedan 2"
 * es una tecnica de presion que este proyecto no usa.
 */
function coremushroom_meta_producto_bucle() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$partes = array();

	$formato = coremushroom_obtener_lote( $product->get_id(), 'formato' );

	if ( '' !== $formato ) {
		$etiqueta_formato = coremushroom_etiqueta_opcion( 'formato', $formato );
		if ( '' !== $etiqueta_formato ) {
			$partes[] = sprintf(
				'<span class="cm-tarjeta__formato">%s</span>',
				esc_html( $etiqueta_formato )
			);
		}
	}

	$contenido = coremushroom_obtener_lote( $product->get_id(), 'contenido_neto' );

	if ( '' !== $contenido ) {
		$partes[] = sprintf(
			'<span class="cm-tarjeta__contenido">%s</span>',
			esc_html( $contenido )
		);
	}

	$disponible = $product->is_in_stock();

	// Disponible usa el badge neutro, no el de oferta. Son dos significados
	// distintos y compartir color haria pensar que todo lo disponible esta
	// rebajado. Agotado si usa su propio modificador, que va apagado.
	$partes[] = sprintf(
		'<span class="cm-badge%1$s cm-tarjeta__stock">%2$s</span>',
		$disponible ? '' : ' cm-badge--agotado',
		$disponible
			? esc_html__( 'Disponible', 'coremushroom' )
			: esc_html__( 'Agotado', 'coremushroom' )
	);

	printf(
		'<p class="cm-tarjeta__meta">%s</p>',
		implode( '', $partes )
	);
}
add_action( 'woocommerce_after_shop_loop_item_title', 'coremushroom_meta_producto_bucle', 6 );

/**
 * Marca la tarjeta con la especie para poder colorearla desde CSS.
 *
 * Agrega una clase cm-producto--<especie> al <li> del bucle. No pinta nada
 * por si sola: deja el gancho listo para que components.css decida.
 *
 * @param string[]   $clases  Clases del elemento del bucle.
 * @param WC_Product $product Producto.
 * @return string[]
 */
function coremushroom_clases_bucle( $clases, $product = null ) {
	if ( ! $product instanceof WC_Product ) {
		return $clases;
	}

	$clases[] = 'cm-producto';

	$especie = coremushroom_obtener_lote( $product->get_id(), 'especie' );

	if ( '' !== $especie && '' !== coremushroom_etiqueta_opcion( 'especie', $especie ) ) {
		$clases[] = 'cm-producto--' . sanitize_html_class( $especie );
	}

	if ( ! $product->is_in_stock() ) {
		$clases[] = 'cm-producto--agotado';
	}

	return $clases;
}
add_filter( 'woocommerce_post_class', 'coremushroom_clases_bucle', 10, 2 );
