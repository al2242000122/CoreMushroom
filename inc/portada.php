<?php
/**
 * CoreMushroom - compatibilidad del contenido guardado de la portada.
 *
 * Los patterns se copian a la base de datos cuando se insertan. Por eso,
 * cambiar el archivo del pattern no corrige la portada que ya fue publicada.
 * Este filtro mantiene el texto visible alineado con los medios de pago y la
 * direccion visual actuales, sin reescribir ni guardar contenido del usuario.
 *
 * @package CoreMushroom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Actualiza frases antiguas solo al renderizar la portada principal.
 *
 * @param string $contenido Contenido renderizado por WordPress.
 * @return string
 */
function coremushroom_actualizar_portada_publicada( $contenido ) {
	if ( ! is_front_page() || ! in_the_loop() || ! is_main_query() ) {
		return $contenido;
	}

	$reemplazos = array(
		'Envio gratis en pedidos desde 900 pesos' => 'Envío gratis en pedidos desde $900',
		'HONGOS FUNCIONALES'                     => 'HONGOS PARA TODOS LOS DÍAS',
		'Hongos funcionales'                     => 'Hongos para todos los días',
		'Cordyceps, Hericium y Trametes en tres formatos' => 'El lado más amable de los hongos',
		'Chocolate, tisana y capsula. Cada empaque lleva su codigo de lote y la ficha completa de lo que hay dentro.' => 'Chocolate, tisanas y cápsulas con Cordyceps, Hericium, Trametes y Ganoderma.',
		'>Ver catalogo<'                         => '>Ver catálogo<',
		'>Como enviamos<'                        => '>Cómo enviamos<',
		'Cacao 70% con extracto. En barra y en pieza individual.' => 'Cacao 70% con extractos de hongos, en barras para disfrutar y compartir.',
		'Hebra suelta y en sobre. Para preparar en caliente.' => 'Una pausa cálida, en hebras sueltas o en sobres.',
		'>Capsula<'                              => '>Cápsulas<',
		'Extracto en capsula vegetal. Frasco de 60 y de 120.' => 'Un formato práctico y sencillo para acompañar tu rutina.',
		'Envio a todo Mexico'                    => 'Envíos a todo México',
		'Paqueteria con guia rastreable. Envio gratis desde 900 pesos.' => 'Paquetería con guía rastreable. Envío gratis desde $900.',
		'Pago seguro'                            => 'SPEI directo',
		'Tarjeta, transferencia SPEI y deposito en OXXO.' => 'Transferencia directa y comprobante protegido.',
		'Producto lote a lote'                   => 'Ingredientes a la vista',
		'Cada empaque lleva su codigo de lote y su ficha de contenido.' => 'Contenido, alérgenos y origen claramente indicados.',
		'Derivados de Cordyceps, Hericium y Trametes. Chocolate, tisana y capsula.' => 'Chocolate, tisanas y cápsulas con Cordyceps, Hericium, Trametes y Ganoderma.',
		'href="/envios"'                        => 'href="' . esc_url( home_url( '/politica-de-envios/' ) ) . '"',
		'href="/uso-previsto"'                  => 'href="' . esc_url( home_url( '/declaracion-de-uso-previsto/' ) ) . '"',
	);

	return strtr( (string) $contenido, $reemplazos );
}
add_filter( 'the_content', 'coremushroom_actualizar_portada_publicada', 20 );
