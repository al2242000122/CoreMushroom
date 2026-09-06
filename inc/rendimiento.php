<?php
/**
 * CoreMushroom - rendimiento.
 *
 * Lo que hay aqui sale de medir la portada, no de una lista generica de
 * consejos. Lo que ya estaba bien no se toca: el sitio no carga el script de
 * emojis ni resuelve dominios ajenos, asi que no hay nada que quitar ahi.
 *
 * Lo que si sobraba:
 *   - wp-embed, que solo sirve para que OTROS sitios incrusten el tuyo.
 *   - jquery-migrate, una capa de compatibilidad para codigo de hace diez
 *     anos que ni el tema ni WooCommerce necesitan.
 *   - Tamanos de imagen que WordPress genera y este tema no usa nunca.
 *
 * La cache de servidor no se hace desde aqui. LiteSpeed Cache es un plugin
 * gratuito del propio hosting y resuelve el tiempo de respuesta mucho mejor
 * que cualquier cosa que se escriba en el tema.
 *
 * @package CoreMushroom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retira scripts que el sitio carga y no usa.
 */
function coremushroom_aligerar_scripts() {
	// Belt and braces por si algun plugin lo encola por su cuenta. Lo que de
	// verdad lo quita es el remove_action de mas abajo: WordPress lo engancha
	// en wp_head, no aqui, asi que deregistrarlo desde wp_enqueue_scripts no
	// servia de nada. Medido en el sitio: seguia cargando.
	wp_deregister_script( 'wp-embed' );

	// jquery-migrate avisa en consola de funciones de jQuery retiradas hace
	// anos. WooCommerce y Blocksy usan jQuery moderno. Se quita conservando
	// jQuery, del que WooCommerce si depende.
	$jquery = wp_scripts()->registered['jquery'] ?? null;

	if ( $jquery && ! empty( $jquery->deps ) ) {
		$jquery->deps = array_diff( $jquery->deps, array( 'jquery-migrate' ) );
	}
}
add_action( 'wp_enqueue_scripts', 'coremushroom_aligerar_scripts', 99 );

/**
 * Quita el script que permite a otros sitios incrustar paginas de este.
 *
 * WordPress lo engancha en wp_head con wp_oembed_add_host_js. Es la unica
 * forma de retirarlo: deregistrarlo desde wp_enqueue_scripts no funciona
 * porque todavia no se ha encolado.
 */
function coremushroom_quitar_oembed_host() {
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	remove_filter( 'embed_oembed_html', 'wp_maybe_enqueue_oembed_host_js' );
}
add_action( 'init', 'coremushroom_quitar_oembed_host' );

/**
 * Retira los tamanos de imagen que este tema no usa.
 *
 * WordPress genera un archivo por tamano registrado cada vez que se sube una
 * foto. Los que no se usan ocupan disco y alargan la subida sin que nadie
 * los pida nunca.
 *
 * @param array<string, mixed> $tamanos Tamanos intermedios.
 * @return array<string, mixed>
 */
function coremushroom_quitar_tamanos( $tamanos ) {
	unset( $tamanos['medium_large'], $tamanos['1536x1536'], $tamanos['2048x2048'] );

	return $tamanos;
}
add_filter( 'intermediate_image_sizes_advanced', 'coremushroom_quitar_tamanos' );

/**
 * Registra el tamano que usa la tarjeta del catalogo.
 *
 * La zona de imagen de la tarjeta tiene proporcion 4 a 3 fijada en CSS. Sin
 * un tamano recortado a esa proporcion, el navegador descarga la foto
 * completa y la recorta al pintarla: se transfieren bytes que no se ven.
 */
function coremushroom_tamanos_imagen() {
	add_image_size( 'coremushroom-tarjeta', 600, 450, true );
}
add_action( 'after_setup_theme', 'coremushroom_tamanos_imagen' );

/**
 * Agrega decoding asincrono a las imagenes del contenido.
 *
 * WordPress ya pone loading lazy. decoding async le dice ademas al navegador
 * que no bloquee el pintado mientras descomprime la imagen.
 *
 * @param array<string, string> $atributos Atributos de la imagen.
 * @return array<string, string>
 */
function coremushroom_decoding_async( $atributos ) {
	if ( ! isset( $atributos['decoding'] ) ) {
		$atributos['decoding'] = 'async';
	}

	return $atributos;
}
add_filter( 'wp_get_attachment_image_attributes', 'coremushroom_decoding_async' );

/**
 * Quita la version de WordPress de las URL de scripts y hojas.
 *
 * No es rendimiento, es no anunciar en cada archivo que version corre el
 * sitio. Se conserva la version de los archivos del tema, que ahi sirve para
 * invalidar la cache del navegador.
 *
 * @param string $url URL del recurso.
 * @return string
 */
function coremushroom_quitar_version_wp( $url ) {
	if ( ! is_string( $url ) || false === strpos( $url, 'ver=' ) ) {
		return $url;
	}

	// Solo se limpia lo que sirve el propio WordPress, no los assets del
	// tema ni de los plugins, que necesitan su version para el cache busting.
	if ( false === strpos( $url, '/wp-includes/' ) && false === strpos( $url, '/wp-admin/' ) ) {
		return $url;
	}

	return remove_query_arg( 'ver', $url );
}
add_filter( 'style_loader_src', 'coremushroom_quitar_version_wp', 20 );
add_filter( 'script_loader_src', 'coremushroom_quitar_version_wp', 20 );

/**
 * Reglas de precarga para el navegador.
 *
 * Le dice al navegador que empiece a traer la siguiente pagina cuando el
 * visitante deja el cursor sobre un enlace. WordPress 6.8 lo trae de serie;
 * esto solo excluye lo que no tiene sentido precargar.
 *
 * @param array<string, mixed> $config Configuracion de reglas.
 * @return array<string, mixed>
 */
function coremushroom_excluir_de_precarga( $config ) {
	if ( ! isset( $config['prefetch'] ) || ! is_array( $config['prefetch'] ) ) {
		return $config;
	}

	// El carrito, el checkout y la cuenta cambian en cada visita y precargarlos
	// puede disparar acciones que el visitante no pidio.
	$fuera = array( '/carrito/*', '/cart/*', '/checkout/*', '/finalizar-compra/*', '/mi-cuenta/*', '/my-account/*' );

	foreach ( $config['prefetch'] as $i => $regla ) {
		if ( ! isset( $regla['where']['and'] ) ) {
			continue;
		}

		$config['prefetch'][ $i ]['where']['and'][] = array(
			'not' => array( 'href_matches' => $fuera ),
		);
	}

	return $config;
}
add_filter( 'wp_speculation_rules_configuration', 'coremushroom_excluir_de_precarga' );
