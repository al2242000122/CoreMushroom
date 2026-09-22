<?php
/** Prueba de las rutas que se migran y de los pedidos que se conservan. */
define( 'ABSPATH', __DIR__ );

function wp_parse_url( $url, $component ) {
	return parse_url( $url, $component );
}

function add_action() {}

require __DIR__ . '/../inc/dominio.php';

$casos = array(
	array( 'core.bancodeesporas.com', '/', 'GET', 'https://coremushroom.com.mx/' ),
	array( 'core.bancodeesporas.com', '/catalogo/?orden=precio', 'GET', 'https://coremushroom.com.mx/catalogo/' ),
	array( 'core.bancodeesporas.com', '/finalizar-compra/', 'GET', 'https://coremushroom.com.mx/finalizar-compra/' ),
	array( 'core.bancodeesporas.com', '/finalizar-compra/order-received/55/?key=abc', 'GET', '' ),
	array( 'core.bancodeesporas.com', '/finalizar-compra/order-pay/55/', 'GET', '' ),
	array( 'core.bancodeesporas.com', '/mi-cuenta/view-order/55/', 'GET', '' ),
	array( 'core.bancodeesporas.com', '/', 'POST', '' ),
	array( 'coremushroom.com.mx', '/', 'GET', '' ),
	array( 'core.bancodeesporas.com.evil', '/', 'GET', '' ),
	array( 'core.bancodeesporas.com', '//evil.test/', 'GET', '' ),
);

foreach ( $casos as $caso ) {
	$real = coremushroom_destino_dominio( $caso[0], $caso[1], $caso[2] );
	if ( $caso[3] !== $real ) {
		fwrite( STDERR, 'Fallo: ' . $caso[1] . ' => ' . $real . PHP_EOL );
		exit( 1 );
	}
}

echo "TODO OK\n";
