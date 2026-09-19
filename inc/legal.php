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
