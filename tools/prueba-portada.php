<?php
/**
 * Prueba de compatibilidad para el contenido guardado de la portada.
 *
 * Uso: php tools/prueba-portada.php <ruta-del-tema>
 */
define( 'ABSPATH', __DIR__ );
$TEMA = rtrim( $argv[1] ?? '.', "/\\" );

$GLOBALS['portada'] = true;
$GLOBALS['bucle']   = true;
$GLOBALS['principal'] = true;

function add_filter( $gancho, $funcion, $prioridad = 10, $argumentos = 1 ) {}
function is_front_page() { return $GLOBALS['portada']; }
function in_the_loop() { return $GLOBALS['bucle']; }
function is_main_query() { return $GLOBALS['principal']; }
function home_url( $ruta = '/' ) { return 'https://ejemplo.test' . $ruta; }
function esc_url( $url ) { return htmlspecialchars( (string) $url, ENT_QUOTES, 'UTF-8' ); }

require $TEMA . '/inc/portada.php';

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

$viejo = '<h1>Cordyceps, Hericium y Trametes en tres formatos</h1>'
	. '<p>Tarjeta, transferencia SPEI y deposito en OXXO.</p>'
	. '<a href="/envios">Como enviamos</a>'
	. '<a href="/uso-previsto">Declaracion de uso previsto</a>';
$nuevo = coremushroom_actualizar_portada_publicada( $viejo );

af( str_contains( $nuevo, 'El lado más amable de los hongos' ), 'actualiza el titular' );
af( str_contains( $nuevo, 'Transferencia directa y comprobante protegido.' ), 'no promete pagos que no existen' );
af( ! str_contains( $nuevo, 'Tarjeta' ) && ! str_contains( $nuevo, 'OXXO' ), 'retira tarjeta y OXXO' );
af( str_contains( $nuevo, 'https://ejemplo.test/politica-de-envios/' ), 'corrige el enlace de envios' );
af( str_contains( $nuevo, 'https://ejemplo.test/declaracion-de-uso-previsto/' ), 'corrige el enlace legal' );

$GLOBALS['portada'] = false;
af( coremushroom_actualizar_portada_publicada( $viejo ) === $viejo, 'no cambia paginas interiores' );
$GLOBALS['portada'] = true;
$GLOBALS['principal'] = false;
af( coremushroom_actualizar_portada_publicada( $viejo ) === $viejo, 'no cambia consultas secundarias' );

$imagen = $TEMA . '/assets/images/amanita-hero-v1.webp';
$bytes  = is_readable( $imagen ) ? file_get_contents( $imagen, false, null, 0, 16 ) : '';
af( is_string( $bytes ) && str_starts_with( $bytes, 'RIFF' ) && 'WEBP' === substr( $bytes, 8, 4 ), 'la ilustracion existe y es WebP real' );
af(
	str_contains( (string) file_get_contents( $TEMA . '/assets/css/home.css' ), 'amanita-hero-v1.webp' ),
	'la portada referencia la ilustracion'
);

echo "\n" . ( 0 === $fallos ? 'TODO OK' : "$fallos FALLOS" ) . "\n";
exit( 0 === $fallos ? 0 : 1 );
