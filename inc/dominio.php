<?php
/**
 * Redirección del subdominio provisional al dominio definitivo.
 *
 * @package CoreMushroom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Obtiene la URL definitiva para una solicitud pública del sitio anterior.
 *
 * Las páginas de pago y seguimiento de pedidos antiguos se conservan en la
 * instalación original: sus claves y comprobantes no se copiaron después de
 * la migración. Las peticiones que no son GET/HEAD tampoco se redirigen.
 *
 * @param string $host   Host recibido.
 * @param string $uri    URI solicitada.
 * @param string $metodo Método HTTP.
 * @return string Destino absoluto o cadena vacía.
 */
function coremushroom_destino_dominio( $host, $uri, $metodo ) {
	if ( 'core.bancodeesporas.com' !== strtolower( (string) $host ) ) {
		return '';
	}

	if ( ! in_array( strtoupper( (string) $metodo ), array( 'GET', 'HEAD' ), true ) ) {
		return '';
	}

	if ( 0 === strpos( (string) $uri, '//' ) ) {
		return '';
	}

	$ruta = wp_parse_url( (string) $uri, PHP_URL_PATH );

	if ( ! is_string( $ruta ) || '' === $ruta || '/' !== $ruta[0] || 0 === strpos( $ruta, '//' ) || false !== strpos( $ruta, '\\' ) ) {
		return '';
	}

	// Los pedidos del WordPress antiguo siguen siendo consultables allí.
	if ( preg_match( '#^/(?:finalizar-compra/(?:order-received|order-pay)|mi-cuenta/(?:orders|view-order))(?:/|$)#i', $ruta ) ) {
		return '';
	}

	return 'https://coremushroom.com.mx' . $ruta;
}

/**
 * Aplica la redirección permanente solo en páginas públicas del host antiguo.
 */
function coremushroom_redirigir_dominio_antiguo() {
	$destino = coremushroom_destino_dominio(
		isset( $_SERVER['HTTP_HOST'] ) ? wp_unslash( $_SERVER['HTTP_HOST'] ) : '',
		isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '',
		isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : ''
	);

	if ( '' === $destino ) {
		return;
	}

	wp_redirect( $destino, 301, 'CoreMushroom' );
	exit;
}
add_action( 'template_redirect', 'coremushroom_redirigir_dominio_antiguo', 1 );
