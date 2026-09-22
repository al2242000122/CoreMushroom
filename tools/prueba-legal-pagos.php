<?php
/**
 * Prueba la divulgacion del puente de tarjeta en paginas legales guardadas.
 *
 * Uso: php tools/prueba-legal-pagos.php <ruta-del-tema>
 */
define( 'ABSPATH', __DIR__ );
$TEMA = rtrim( $argv[1] ?? '.', "/\\" );

$GLOBALS['pagina']    = '';
$GLOBALS['bucle']     = true;
$GLOBALS['principal'] = true;

function add_filter( $gancho, $funcion, $prioridad = 10, $argumentos = 1 ) {}
function is_page( $pagina ) {
	if ( is_array( $pagina ) ) {
		return in_array( $GLOBALS['pagina'], $pagina, true );
	}
	return $GLOBALS['pagina'] === $pagina;
}
function in_the_loop() { return $GLOBALS['bucle']; }
function is_main_query() { return $GLOBALS['principal']; }

require $TEMA . '/inc/legal.php';

$fallos = 0;
function af( $condicion, $mensaje ) {
	global $fallos;
	echo ( $condicion ? 'OK    ' : 'FALLA ' ) . $mensaje . "\n";
	if ( ! $condicion ) {
		$fallos++;
	}
}

$GLOBALS['pagina'] = 'aviso-de-privacidad';
$privacidad = '<p>Actualmente no solicitamos ni almacenamos numeros de tarjeta. Los pedidos se pagan mediante transferencia SPEI y el comprobante que el cliente adjunta se utiliza exclusivamente para verificar el pago.</p>'
	. '<ul><li>Las instituciones bancarias que intervienen en la transferencia SPEI, para cobrar o reembolsar.</li></ul>';
$privacidad = coremushroom_actualizar_divulgacion_pagos( $privacidad );
af( str_contains( $privacidad, 'No almacenamos numeros completos de tarjeta.' ), 'actualiza la captura de tarjeta' );
af( str_contains( $privacidad, 'CoreAdaptogenos y Stripe' ), 'declara a quienes procesan el pago' );
af( ! str_contains( $privacidad, 'Actualmente no solicitamos' ), 'retira la declaracion obsoleta' );

$GLOBALS['pagina'] = 'terminos-de-uso';
$terminos = '<p>Actualmente aceptamos transferencia SPEI. El pedido se considera confirmado cuando verificamos el abono y el comprobante correspondiente. Un pedido con pago pendiente puede cancelarse si no se liquida dentro de las 24 horas siguientes a su creacion.</p>';
$terminos = coremushroom_actualizar_divulgacion_pagos( $terminos );
af( str_contains( $terminos, 'checkout identificado de CoreAdaptogenos' ), 'declara el checkout externo' );
af( str_contains( $terminos, 'webhook firmado' ), 'explica la confirmacion de tarjeta' );

$GLOBALS['principal'] = false;
af( coremushroom_actualizar_divulgacion_pagos( $terminos ) === $terminos, 'no cambia consultas secundarias' );

echo "\n" . ( 0 === $fallos ? 'TODO OK' : "$fallos FALLOS" ) . "\n";
exit( 0 === $fallos ? 0 : 1 );
