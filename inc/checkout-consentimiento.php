<?php
/**
 * CoreMushroom - constancia de aceptacion de terminos en el pedido.
 *
 * La casilla NO se escribe aqui. WooCommerce ya la dibuja y la valida solo,
 * en cuanto se define una pagina de terminos en Ajustes, Avanzado, Ajustes de
 * pagina. Reescribirla seria duplicar codigo bien probado y quedarse sin sus
 * traducciones.
 *
 * Lo que WooCommerce no hace es dejar constancia. Si un cliente reclama, "el
 * pedido se hizo con la casilla marcada" no prueba gran cosa si los terminos
 * cambiaron despues. Este modulo guarda en el pedido la fecha de aceptacion y
 * la version exacta del texto que estaba publicado en ese momento.
 *
 * @package CoreMushroom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Claves de los metadatos de consentimiento.
 *
 * Sin guion bajo inicial a proposito, al reves que los campos de lote: estos
 * son constancia de una operacion y conviene que se vean en el pedido.
 */
const COREMUSHROOM_CONSENT_ACEPTADO = '_coremushroom_terminos_aceptados';
const COREMUSHROOM_CONSENT_FECHA    = '_coremushroom_terminos_fecha';
const COREMUSHROOM_CONSENT_PAGINA   = '_coremushroom_terminos_pagina';
const COREMUSHROOM_CONSENT_VERSION  = '_coremushroom_terminos_version';

/**
 * Avisa en el panel si la pagina de terminos no esta configurada.
 *
 * Sin esa pagina, WooCommerce no dibuja la casilla y el checkout se completa
 * sin que nadie acepte nada. No da error: simplemente la casilla no aparece,
 * que es la clase de fallo que nadie nota hasta que hay una reclamacion.
 */
function coremushroom_avisar_terminos_sin_configurar() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	if ( wc_terms_and_conditions_page_id() ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p><strong>%1$s</strong> %2$s <a href="%3$s">%4$s</a></p></div>',
		esc_html__( 'CoreMushroom:', 'coremushroom' ),
		esc_html__(
			'no hay una página de términos configurada, así que el checkout no está pidiendo aceptarlos. Defínela en WooCommerce, Ajustes, Avanzado.',
			'coremushroom'
		),
		esc_url( admin_url( 'admin.php?page=wc-settings&tab=advanced' ) ),
		esc_html__( 'Ir a los ajustes', 'coremushroom' )
	);
}
add_action( 'admin_notices', 'coremushroom_avisar_terminos_sin_configurar' );

/**
 * Guarda la constancia de aceptacion al crear el pedido.
 *
 * Se engancha a woocommerce_checkout_create_order, que corre con el pedido ya
 * construido pero antes de guardarlo, asi que basta con poner los metadatos
 * en el objeto y WooCommerce los persiste en la misma escritura.
 *
 * La version que se guarda es la fecha de ultima modificacion de la pagina de
 * terminos. Es lo que permite decir despues "el cliente acepto el texto
 * vigente el 4 de septiembre", aunque el texto haya cambiado tres veces.
 *
 * @param WC_Order $pedido Pedido en construccion.
 * @param array    $datos  Datos enviados por el checkout.
 */
function coremushroom_guardar_consentimiento( $pedido, $datos ) {
	if ( ! $pedido instanceof WC_Order ) {
		return;
	}

	$pagina_id = (int) wc_terms_and_conditions_page_id();

	if ( ! $pagina_id ) {
		// Sin pagina de terminos no hubo casilla, asi que no hay nada que
		// atestiguar. Se deja constancia de eso mismo, que es informacion.
		$pedido->update_meta_data( COREMUSHROOM_CONSENT_ACEPTADO, 'sin-pagina' );
		return;
	}

	// WooCommerce ya valido la casilla antes de llegar aqui: si el cliente no
	// la marco, el checkout se detuvo. Llegar a este punto significa aceptada.
	$pedido->update_meta_data( COREMUSHROOM_CONSENT_ACEPTADO, 'si' );
	$pedido->update_meta_data( COREMUSHROOM_CONSENT_FECHA, current_time( 'mysql' ) );
	$pedido->update_meta_data( COREMUSHROOM_CONSENT_PAGINA, $pagina_id );

	$modificada = get_post_modified_time( 'Y-m-d H:i:s', true, $pagina_id );

	if ( $modificada ) {
		$pedido->update_meta_data( COREMUSHROOM_CONSENT_VERSION, $modificada );
	}
}
add_action( 'woocommerce_checkout_create_order', 'coremushroom_guardar_consentimiento', 10, 2 );

/**
 * Muestra la constancia en la pantalla del pedido, en el panel.
 *
 * Va debajo de los datos de facturacion, donde quien atiende una reclamacion
 * ya esta mirando.
 *
 * @param WC_Order $pedido Pedido que se esta viendo.
 */
function coremushroom_mostrar_consentimiento( $pedido ) {
	if ( ! $pedido instanceof WC_Order ) {
		return;
	}

	$aceptado = $pedido->get_meta( COREMUSHROOM_CONSENT_ACEPTADO );

	if ( '' === $aceptado ) {
		return;
	}

	echo '<h4>' . esc_html__( 'Aceptación de términos', 'coremushroom' ) . '</h4>';

	if ( 'si' !== $aceptado ) {
		printf(
			'<p><strong>%s</strong></p>',
			esc_html__(
				'Este pedido se completó sin casilla de términos, porque no había una página configurada en ese momento.',
				'coremushroom'
			)
		);
		return;
	}

	$fecha     = $pedido->get_meta( COREMUSHROOM_CONSENT_FECHA );
	$pagina_id = (int) $pedido->get_meta( COREMUSHROOM_CONSENT_PAGINA );
	$version   = $pedido->get_meta( COREMUSHROOM_CONSENT_VERSION );

	$filas = array();

	if ( $fecha ) {
		$filas[] = sprintf(
			'%s: %s',
			esc_html__( 'Aceptados el', 'coremushroom' ),
			esc_html( $fecha )
		);
	}

	if ( $pagina_id && get_post( $pagina_id ) ) {
		$filas[] = sprintf(
			'%s: <a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_html__( 'Página', 'coremushroom' ),
			esc_url( (string) get_permalink( $pagina_id ) ),
			esc_html( get_the_title( $pagina_id ) )
		);
	}

	if ( $version ) {
		$filas[] = sprintf(
			'%s: %s',
			esc_html__( 'Versión del texto', 'coremushroom' ),
			esc_html( $version )
		);
	}

	printf( '<p>%s</p>', implode( '<br>', $filas ) );
}
add_action( 'woocommerce_admin_order_data_after_billing_address', 'coremushroom_mostrar_consentimiento' );
