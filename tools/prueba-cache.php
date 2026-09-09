<?php
/**
 * Prueba de purga de LiteSpeed al desplegar una version nueva del tema.
 *
 * Uso: php tools/prueba-cache.php <ruta-del-tema>
 */
define( 'ABSPATH', __DIR__ );
define( 'COREMUSHROOM_VERSION', '0.2.0' );
$TEMA = rtrim( $argv[1] ?? '.', "/\\" );

$GLOBALS['opciones'] = array();
$GLOBALS['acciones'] = array();

function add_action( $gancho, $funcion, $prioridad = 10, $argumentos = 1 ) {}
function add_filter( $gancho, $funcion, $prioridad = 10, $argumentos = 1 ) {}
function remove_action( $gancho, $funcion, $prioridad = 10 ) {}
function remove_filter( $gancho, $funcion, $prioridad = 10 ) {}
function wp_deregister_script( $manejador ) {}
function wp_scripts() { return (object) array( 'registered' => array() ); }
function add_image_size( $nombre, $ancho, $alto, $recorte ) {}
function remove_query_arg( $clave, $url ) { return $url; }
function get_option( $clave, $predeterminado = false ) { return $GLOBALS['opciones'][ $clave ] ?? $predeterminado; }
function update_option( $clave, $valor, $autoload = null ) { $GLOBALS['opciones'][ $clave ] = $valor; return true; }
function has_action( $gancho ) { return defined( 'LSCWP_V' ) && 'litespeed_purge_all' === $gancho; }
function do_action( $gancho, ...$argumentos ) { $GLOBALS['acciones'][] = array( $gancho, $argumentos ); }

require $TEMA . '/inc/rendimiento.php';

$fallos = 0;
function af( $condicion, $mensaje ) {
	global $fallos;
	if ( $condicion ) {
		echo "OK    $mensaje\n";
	} else {
		echo "FALLA $mensaje\n";
		$fallos++;
	}
}

af( function_exists( 'coremushroom_purgar_cache_despliegue' ), 'existe el control de version del cache' );

if ( function_exists( 'coremushroom_purgar_cache_despliegue' ) ) {
	af( false === coremushroom_purgar_cache_despliegue(), 'sin LiteSpeed no escribe estado ni dispara acciones' );
	af( array() === $GLOBALS['opciones'], 'sin LiteSpeed conserva la version pendiente' );

	define( 'LSCWP_V', 'prueba' );
	af( true === coremushroom_purgar_cache_despliegue(), 'una version nueva solicita la purga' );
	af( COREMUSHROOM_VERSION === get_option( 'coremushroom_version_cache' ), 'guarda la version ya purgada' );
	af( 'litespeed_purge_all' === $GLOBALS['acciones'][0][0], 'usa la accion oficial de LiteSpeed' );

	$total_acciones = count( $GLOBALS['acciones'] );
	af( false === coremushroom_purgar_cache_despliegue(), 'la misma version no vuelve a purgar' );
	af( $total_acciones === count( $GLOBALS['acciones'] ), 'evita una purga en cada visita' );
}

echo "\n" . ( 0 === $fallos ? 'TODO OK' : "$fallos FALLOS" ) . "\n";
exit( 0 === $fallos ? 0 : 1 );
