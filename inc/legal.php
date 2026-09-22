<?php
/**
 * Retira el aviso temporal de revisión del contenido legal ya aprobado.
 *
 * Los patrones nuevos no contienen el aviso. Las páginas publicadas guardan
 * una copia antigua en la base de datos; este filtro retira solo ese bloque
 * mientras se actualiza el contenido desde el editor.
 *
 * @package CoreMushroom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Identifica y omite únicamente el bloque de aviso de las cuatro páginas.
 *
 * @param string $contenido HTML del bloque.
 * @param array  $bloque    Bloque analizado por WordPress.
 * @return string
 */
function coremushroom_retirar_aviso_revision_legal( $contenido, $bloque ) {
	if ( ! is_page( array( 'aviso-de-privacidad', 'terminos-de-uso', 'politica-de-envios', 'declaracion-de-uso-previsto' ) ) ) {
		return $contenido;
	}

	if ( 'core/group' !== ( $bloque['blockName'] ?? '' ) || 2 !== count( $bloque['innerBlocks'] ?? array() ) ) {
		return $contenido;
	}

	$primero = $bloque['innerBlocks'][0];
	$segundo = $bloque['innerBlocks'][1];
	if ( 'core/paragraph' !== ( $primero['blockName'] ?? '' ) || 'core/paragraph' !== ( $segundo['blockName'] ?? '' ) ) {
		return $contenido;
	}

	if ( false === strpos( $primero['innerHTML'] ?? '', 'BORRADOR SIN REVISION LEGAL' ) ||
		false === strpos( $segundo['innerHTML'] ?? '', 'Este texto incorpora los datos operativos actuales de la tienda' ) ) {
		return $contenido;
	}

	return '';
}
add_filter( 'render_block', 'coremushroom_retirar_aviso_revision_legal', 20, 2 );

/**
 * Evita que el aviso guardado se use como descripción de buscadores y redes.
 *
 * @param string $descripcion Texto elegido desde la página.
 * @return string
 */
function coremushroom_descripcion_pagina_legal( $descripcion ) {
	$paginas = array(
		'aviso-de-privacidad'          => 'Consulta cómo CoreMushroom recaba, utiliza y protege tus datos personales.',
		'terminos-de-uso'              => 'Consulta las condiciones de compra y uso de la tienda CoreMushroom.',
		'politica-de-envios'           => 'Consulta cobertura, plazos y condiciones de envío de CoreMushroom en México.',
		'declaracion-de-uso-previsto' => 'Consulta la declaración de uso previsto de los productos CoreMushroom.',
	);

	foreach ( $paginas as $pagina => $texto ) {
		if ( is_page( $pagina ) ) {
			return $texto;
		}
	}

	return $descripcion;
}
add_filter( 'coremushroom_descripcion', 'coremushroom_descripcion_pagina_legal' );

/**
 * Actualiza la divulgacion de pagos del contenido legal ya guardado.
 *
 * Los patterns se copian a la base de datos al insertarse. Esta sustitucion
 * mantiene las paginas publicadas alineadas con el puente transparente de
 * CoreAdaptogenos sin depender del editor de bloques.
 *
 * @param string $contenido Contenido renderizado por WordPress.
 * @return string
 */
function coremushroom_actualizar_divulgacion_pagos( $contenido ) {
	if ( ! in_the_loop() || ! is_main_query() ) {
		return $contenido;
	}

	if ( is_page( 'aviso-de-privacidad' ) ) {
		$reemplazos = array(
			'Actualmente no solicitamos ni almacenamos numeros de tarjeta. Los pedidos se pagan mediante transferencia SPEI y el comprobante que el cliente adjunta se utiliza exclusivamente para verificar el pago.' => 'No almacenamos numeros completos de tarjeta. Los pedidos pueden pagarse por transferencia SPEI o, cuando se elige tarjeta, en el checkout de CoreAdaptogenos mediante la extension oficial de Stripe para WooCommerce. CoreMushroom conserva el pedido comercial y CoreAdaptogenos crea un pedido espejo limitado al cobro. Stripe y las instituciones participantes reciben los datos necesarios para procesar, prevenir fraude, confirmar, reembolsar o disputar el pago.',
			'<li>Las instituciones bancarias que intervienen en la transferencia SPEI, para cobrar o reembolsar.</li>' => '<li>Las instituciones bancarias que intervienen en la transferencia SPEI, para cobrar o reembolsar.</li><li>CoreAdaptogenos y Stripe, cuando usted elige tarjeta, para iniciar el cobro, prevenir fraude, confirmar el resultado y gestionar reembolsos o disputas.</li>',
		);

		return strtr( (string) $contenido, $reemplazos );
	}

	if ( is_page( 'terminos-de-uso' ) ) {
		return str_replace(
			'Actualmente aceptamos transferencia SPEI. El pedido se considera confirmado cuando verificamos el abono y el comprobante correspondiente. Un pedido con pago pendiente puede cancelarse si no se liquida dentro de las 24 horas siguientes a su creacion.',
			'Aceptamos transferencia SPEI y, cuando el metodo este disponible, tarjeta mediante el checkout identificado de CoreAdaptogenos operado con Stripe. CoreMushroom conserva el pedido y CoreAdaptogenos realiza solamente el cobro. Los pagos SPEI se confirman al verificar el abono y el comprobante; los pagos con tarjeta se confirman por webhook firmado. Un pedido pendiente puede cancelarse si no se liquida dentro de las 24 horas siguientes a su creacion.',
			(string) $contenido
		);
	}

	return $contenido;
}
add_filter( 'the_content', 'coremushroom_actualizar_divulgacion_pagos', 25 );
