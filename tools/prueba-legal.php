<?php
/** Prueba del aviso retirado del contenido legal ya publicado. */
define( 'ABSPATH', __DIR__ );
$tema = rtrim( $argv[1] ?? '.', '/\\' );
$GLOBALS['pagina_legal'] = 'aviso-de-privacidad';
function is_page( $slugs ) { return in_array( $GLOBALS['pagina_legal'], (array) $slugs, true ); }
function add_filter( $nombre, $funcion, $prioridad = 10, $argumentos = 1 ) {}
require $tema . '/inc/legal.php';

$aviso = array(
	'blockName'   => 'core/group',
	'innerBlocks' => array(
		array( 'blockName' => 'core/paragraph', 'innerHTML' => '<p>BORRADOR SIN REVISION LEGAL</p>' ),
		array( 'blockName' => 'core/paragraph', 'innerHTML' => '<p>Este texto incorpora los datos operativos actuales de la tienda</p>' ),
	),
);
$normal = $aviso;
$normal['innerBlocks'][0]['innerHTML'] = '<p>Datos de envío</p>';

if ( '' !== coremushroom_retirar_aviso_revision_legal( '<div>aviso</div>', $aviso ) ||
	'<div>contenido</div>' !== coremushroom_retirar_aviso_revision_legal( '<div>contenido</div>', $normal ) ) {
	exit( "FALLA aviso legal\n" );
}
$GLOBALS['pagina_legal'] = false;
if ( '<div>aviso</div>' !== coremushroom_retirar_aviso_revision_legal( '<div>aviso</div>', $aviso ) ) {
	exit( "FALLA filtro fuera de páginas legales\n" );
}
if ( 'original' !== coremushroom_descripcion_pagina_legal( 'original' ) ) {
	exit( "FALLA descripción de otras páginas\n" );
}
$GLOBALS['pagina_legal'] = 'aviso-de-privacidad';
if ( false !== strpos( coremushroom_descripcion_pagina_legal( 'BORRADOR SIN REVISION LEGAL' ), 'BORRADOR' ) ) {
	exit( "FALLA descripción legal\n" );
}
echo "TODO OK\n";
