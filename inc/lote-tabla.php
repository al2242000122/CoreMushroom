<?php
/**
 * CoreMushroom - tabla de lote en la ficha de producto.
 *
 * Renderiza los campos definidos en lote-campos.php con el componente
 * cm-datos del sistema de diseno.
 *
 * La tabla solo describe lo que hay dentro del producto. No lleva ni debe
 * llevar filas sobre efectos, beneficios, indicaciones ni resultados.
 *
 * Se expone por dos caminos a proposito:
 *   1. El gancho clasico de WooCommerce, para las plantillas PHP de siempre.
 *   2. Un shortcode, para poder colocarla en cualquier sitio si la ficha de
 *      producto se arma con bloques, donde ese gancho no se dispara.
 *
 * @package CoreMushroom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Construye el HTML de la tabla de lote de un producto.
 *
 * Devuelve cadena vacia si no hay ni un solo campo con valor, para que la
 * ficha no muestre una tabla hueca.
 *
 * @param int $producto_id ID del producto.
 * @return string HTML listo para imprimir, ya escapado.
 */
function coremushroom_tabla_lote_html( $producto_id ) {
	$producto_id = (int) $producto_id;
	$filas       = array();

	foreach ( coremushroom_campos_lote() as $clave => $campo ) {
		$valor = coremushroom_obtener_lote( $producto_id, $clave );

		if ( '' === $valor ) {
			continue;
		}

		switch ( $campo['tipo'] ) {
			case 'select':
				// Se guarda la clave y se muestra la etiqueta. Si la opcion ya
				// no existe, etiqueta_opcion devuelve vacio y la fila se omite
				// en vez de imprimir la clave cruda.
				$etiqueta = coremushroom_etiqueta_opcion( $clave, $valor );
				if ( '' === $etiqueta ) {
					continue 2;
				}
				$celda = esc_html( $etiqueta );
				break;

			case 'date':
				// date_i18n espera una marca de tiempo. El valor guardado ya
				// paso por checkdate al guardarse, asi que strtotime no puede
				// fallar aqui, pero se comprueba igual.
				// El valor es una fecha sin hora. strtotime la interpreta en la
				// zona de PHP, que WordPress fija en UTC, asi que la marca ya
				// es UTC. El tercer argumento de date_i18n en true evita que
				// vuelva a sumarle el desfase del sitio: sin el, en una zona
				// UTG+13 la fecha se imprimiria un dia adelantada.
				$marca = strtotime( $valor . ' 12:00:00 UTC' );
				if ( false === $marca ) {
					continue 2;
				}
				$celda = esc_html( date_i18n( 'j \d\e F \d\e Y', $marca, true ) );
				break;

			case 'url':
				$celda = sprintf(
					'<a href="%1$s" rel="noopener noreferrer" target="_blank">%2$s</a>',
					esc_url( $valor ),
					esc_html__( 'Ver certificado', 'coremushroom' )
				);
				break;

			case 'textarea':
				// nl2br sobre texto ya escapado: los saltos de linea del
				// administrador se respetan sin abrir la puerta a etiquetas.
				$celda = nl2br( esc_html( $valor ) );
				break;

			default:
				$celda = esc_html( $valor );
				break;
		}

		$filas[] = sprintf(
			'<tr><th scope="row">%1$s</th><td>%2$s</td></tr>',
			esc_html( $campo['etiqueta'] ),
			$celda
		);
	}

	if ( empty( $filas ) ) {
		return '';
	}

	return sprintf(
		'<div class="cm-datos-envoltorio"><table class="cm-datos"><caption>%1$s</caption><tbody>%2$s</tbody></table></div>',
		esc_html__( 'Información del producto', 'coremushroom' ),
		implode( '', $filas )
	);
}

/**
 * Imprime la tabla debajo del resumen del producto.
 *
 * Prioridad 15: despues de la descripcion larga y las pestañas, que van en 10,
 * y antes de los productos relacionados, que van en 20.
 */
function coremushroom_imprimir_tabla_lote() {
	if ( ! is_singular( 'product' ) ) {
		return;
	}

	$html = coremushroom_tabla_lote_html( get_the_ID() );

	if ( '' === $html ) {
		return;
	}

	printf(
		'<section class="cm-ficha-lote" aria-labelledby="cm-ficha-lote-titulo">'
		. '<h2 id="cm-ficha-lote-titulo" class="cm-ficha-lote__titulo">%1$s</h2>%2$s</section>',
		esc_html__( 'Ficha del producto', 'coremushroom' ),
		$html
	);
}
add_action( 'woocommerce_after_single_product_summary', 'coremushroom_imprimir_tabla_lote', 15 );

/**
 * Shortcode [coremushroom_ficha] para colocar la tabla donde haga falta.
 *
 * Sirve para las plantillas de producto hechas con bloques, donde el gancho
 * woocommerce_after_single_product_summary no llega a dispararse.
 *
 * Acepta un atributo id para mostrar la ficha de otro producto. Si no se
 * pasa, usa el producto actual.
 *
 * @param array<string, string>|string $atributos Atributos del shortcode.
 * @return string
 */
function coremushroom_shortcode_ficha( $atributos ) {
	$atributos = shortcode_atts(
		array( 'id' => 0 ),
		$atributos,
		'coremushroom_ficha'
	);

	$producto_id = (int) $atributos['id'];

	if ( $producto_id <= 0 ) {
		$producto_id = (int) get_the_ID();
	}

	// Solo se acepta un producto publicado. Sin esto, pasar un id cualquiera
	// dejaria leer metadatos de borradores o de otros tipos de contenido.
	if ( 'product' !== get_post_type( $producto_id ) || 'publish' !== get_post_status( $producto_id ) ) {
		return '';
	}

	// Un producto publicado pero con contrasena tambien queda fuera. La
	// plantilla de WooCommerce corta antes de llegar aqui, pero el shortcode
	// se salta esa plantilla: sin esta linea, poner el shortcode con el id de
	// un producto protegido publicaria su ficha completa.
	if ( post_password_required( $producto_id ) ) {
		return '';
	}

	return coremushroom_tabla_lote_html( $producto_id );
}
add_shortcode( 'coremushroom_ficha', 'coremushroom_shortcode_ficha' );
