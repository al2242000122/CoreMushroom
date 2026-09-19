<?php
/** Verifica que solo se impriman fotografías propias, nunca rellenos. */
define( 'ABSPATH', __DIR__ );
$tema = rtrim( $argv[1] ?? '.', '/\\' );
function add_action( $nombre, $funcion, $prioridad = 10 ) {}
function add_filter( $nombre, $funcion, $prioridad = 10, $argumentos = 1 ) {}
function remove_action( $nombre, $funcion, $prioridad = 10 ) {}
function coremushroom_obtener_lote( $id, $campo ) { return ''; }
function woocommerce_template_loop_product_thumbnail() { echo 'FOTO'; }
function woocommerce_show_product_images() { echo 'GALERIA'; }
class WC_Product {
	private $imagen;
	private $galeria;
	public function __construct( $imagen, $galeria = array() ) { $this->imagen = $imagen; $this->galeria = $galeria; }
	public function get_image_id() { return $this->imagen; }
	public function get_gallery_image_ids() { return $this->galeria; }
	public function get_id() { return 1; }
	public function is_in_stock() { return true; }
}
require $tema . '/inc/tarjeta-producto.php';

function salida( $funcion ) { ob_start(); $funcion(); return ob_get_clean(); }
$GLOBALS['product'] = new WC_Product( 0 );
if ( '' !== salida( 'coremushroom_imagen_producto_si_existe' ) ||
	'' !== salida( 'coremushroom_galeria_producto_si_existe' ) ||
	! in_array( 'cm-producto--sin-imagen', coremushroom_clases_bucle( array(), $GLOBALS['product'] ), true ) ) {
	exit( "FALLA producto sin fotografía\n" );
}
$GLOBALS['product'] = new WC_Product( 42 );
if ( 'FOTO' !== salida( 'coremushroom_imagen_producto_si_existe' ) ||
	'GALERIA' !== salida( 'coremushroom_galeria_producto_si_existe' ) ||
	in_array( 'cm-producto--sin-imagen', coremushroom_clases_bucle( array(), $GLOBALS['product'] ), true ) ) {
	exit( "FALLA producto con fotografía\n" );
}
echo "TODO OK\n";
