<?php
/**
 * Prueba aislada del protocolo de tarjeta con CoreAdaptogenos.
 *
 * Uso: php tools/prueba-pago-coreadaptogenos.php <ruta-del-tema>
 */
define( 'ABSPATH', __DIR__ );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
$tema = $argv[1] ?? '.';

$GLOBALS['opciones']   = array();
$GLOBALS['transients'] = array();
$GLOBALS['pedidos']    = array();

function add_action( $h, $f, $p = 10, $a = 1 ) {}
function add_filter( $h, $f, $p = 10, $a = 1 ) {}
function __( $t, $d = '' ) { return $t; }
function get_option( $k, $d = array() ) { return $GLOBALS['opciones'][ $k ] ?? $d; }
function add_option( $k, $v, $deprecated = '', $autoload = false ) { if ( array_key_exists( $k, $GLOBALS['opciones'] ) ) { return false; } $GLOBALS['opciones'][ $k ] = $v; return true; }
function update_option( $k, $v, $autoload = false ) { $GLOBALS['opciones'][ $k ] = $v; return true; }
function delete_option( $k ) { unset( $GLOBALS['opciones'][ $k ] ); return true; }
function wp_parse_url( $u ) { return parse_url( $u ); }
function untrailingslashit( $s ) { return rtrim( $s, '/\\' ); }
function register_rest_route( $n, $r, $a ) {}
function get_transient( $k ) { return $GLOBALS['transients'][ $k ] ?? false; }
function set_transient( $k, $v, $ttl ) { $GLOBALS['transients'][ $k ] = $v; return true; }
function absint( $v ) { return abs( (int) $v ); }
function sanitize_text_field( $v ) { return trim( strip_tags( (string) $v ) ); }
function wc_format_decimal( $v, $d = 2 ) { return number_format( (float) $v, $d, '.', '' ); }
function wc_get_order( $id ) { return $GLOBALS['pedidos'][ $id ] ?? false; }
function get_woocommerce_currency() { return 'MXN'; }
function current_user_can( $capability ) { return 'manage_woocommerce' === $capability && ! empty( $GLOBALS['puente_admin'] ); }

class WP_Error {
	public $code;
	public function __construct( $code, $message = '', $data = array() ) { $this->code = $code; }
}
class WP_REST_Response {
	public $data;
	public $status;
	public function __construct( $data, $status ) { $this->data = $data; $this->status = $status; }
}
class PeticionPrueba {
	private $headers;
	private $body;
	private $json;
	public function __construct( $headers, $body, $json = array() ) { $this->headers = $headers; $this->body = $body; $this->json = $json; }
	public function get_header( $name ) { return $this->headers[ strtolower( $name ) ] ?? ''; }
	public function get_body() { return $this->body; }
	public function get_json_params() { return $this->json; }
}
class PedidoPrueba {
	private $meta;
	public $pagos = 0;
	public function __construct( $meta ) { $this->meta = $meta; }
	public function get_meta( $k ) { return $this->meta[ $k ] ?? ''; }
	public function get_id() { return 44; }
	public function get_payment_method() { return 'coremushroom_tarjeta'; }
	public function get_status() { return 'pending'; }
	public function is_paid() { return $this->pagos > 0; }
	public function payment_complete( $id ) { $this->pagos++; }
	public function add_order_note( $n ) {}
	public function save() {}
}

require rtrim( $tema, '/\\' ) . '/inc/pago-coreadaptogenos.php';

$fallos = 0;
function af( $cond, $msg ) {
	global $fallos;
	if ( $cond ) { echo "OK    $msg\n"; } else { echo "FALLA $msg\n"; $fallos++; }
}

echo "--- Importes y configuracion ---\n";
af( 12345 === coremushroom_importe_menor( '123.45' ), 'convierte pesos a centavos exactos' );
af( 100 === coremushroom_importe_menor( '1' ), 'completa decimales faltantes' );
$config = array( 'enabled' => 'yes', 'receiver_url' => 'https://coreadaptogenos.app', 'shared_secret' => str_repeat( 'a', 32 ), 'environment' => 'test' );
af( coremushroom_puente_configurado( $config ), 'acepta la configuracion cerrada esperada' );
$mala = $config; $mala['receiver_url'] = 'https://coreadaptogenos.app.evil.test';
af( ! coremushroom_puente_configurado( $mala ), 'rechaza un host parecido' );
$mala = $config; $mala['shared_secret'] = 'corto';
af( ! coremushroom_puente_configurado( $mala ), 'rechaza secretos cortos' );

echo "\n--- Destino y respuesta firmada ---\n";
$checkout = 'https://coreadaptogenos.app/finalizar-compra/order-pay/87/?pay_for_order=true&key=wc_x';
af( coremushroom_url_receptor_valida( $checkout, $config['receiver_url'] ), 'acepta order-pay del host exacto' );
af( ! coremushroom_url_receptor_valida( 'https://evil.test/finalizar-compra/order-pay/87/', $config['receiver_url'] ), 'rechaza otro host' );
af( ! coremushroom_url_receptor_valida( 'http://coreadaptogenos.app/finalizar-compra/order-pay/87/', $config['receiver_url'] ), 'exige HTTPS' );
af( ! coremushroom_url_receptor_valida( 'https://coreadaptogenos.app:8443/finalizar-compra/order-pay/87/', $config['receiver_url'], 87 ), 'rechaza otro puerto' );
af( ! coremushroom_url_receptor_valida( 'https://coreadaptogenos.app/finalizar-compra/order-pay/88/', $config['receiver_url'], 87 ), 'exige el pedido receptor exacto' );
$respuesta = array(
	'session' => str_repeat( 'b', 64 ), 'receiver_order_id' => 87, 'amount_minor' => 90000,
	'currency' => 'MXN', 'environment' => 'test', 'checkout_url' => $checkout,
);
$canon = implode( '|', array( $respuesta['session'], 87, 90000, 'MXN', 'test', $checkout ) );
$respuesta['signature'] = hash_hmac( 'sha256', $canon, $config['shared_secret'] );
af( coremushroom_respuesta_puente_valida( $respuesta, $respuesta['session'], 90000, $config['receiver_url'], $config['shared_secret'] ), 'acepta respuesta integra' );
$alterada = $respuesta; $alterada['amount_minor'] = 89999;
af( ! coremushroom_respuesta_puente_valida( $alterada, $respuesta['session'], 90000, $config['receiver_url'], $config['shared_secret'] ), 'rechaza importe alterado' );

echo "\n--- Callback autenticado e idempotente ---\n";
$GLOBALS['opciones']['woocommerce_coremushroom_tarjeta_settings'] = $config;
$cuerpo = '{"event":"paid"}';
$ts = (string) time();
$nonce = str_repeat( 'c', 32 );
$headers = array(
	'x-ca-timestamp' => $ts,
	'x-ca-nonce' => $nonce,
	'x-ca-signature' => coremushroom_firma_puente( $ts, $nonce, $cuerpo, $config['shared_secret'] ),
);
$peticion = new PeticionPrueba( $headers, $cuerpo );
af( true === coremushroom_autorizar_evento_pago( $peticion ), 'acepta HMAC vigente' );
af( coremushroom_autorizar_evento_pago( $peticion ) instanceof WP_Error, 'rechaza nonce repetido' );
$viejo = (string) ( time() - 301 );
$headers['x-ca-timestamp'] = $viejo;
$headers['x-ca-nonce'] = str_repeat( 'd', 32 );
$headers['x-ca-signature'] = coremushroom_firma_puente( $viejo, $headers['x-ca-nonce'], $cuerpo, $config['shared_secret'] );
af( coremushroom_autorizar_evento_pago( new PeticionPrueba( $headers, $cuerpo ) ) instanceof WP_Error, 'rechaza timestamp vencido' );

$sesion = str_repeat( 'e', 64 );
$GLOBALS['pedidos'][44] = new PedidoPrueba( array(
	'_cm_bridge_session_hash' => hash( 'sha256', $sesion ),
	'_cm_bridge_receiver_order' => 87,
	'_cm_bridge_amount_minor' => 90000,
	'_cm_bridge_currency' => 'MXN',
	'_cm_bridge_environment' => 'test',
	'_cm_bridge_expires_at' => time() + 600,
) );
$evento = array( 'event' => 'paid', 'event_id' => str_repeat( 'f', 64 ), 'source_order_id' => 44, 'session' => $sesion, 'receiver_order_id' => 87, 'amount_minor' => 90000, 'currency' => 'MXN', 'environment' => 'test', 'transaction_id' => 'pi_test' );
$r1 = coremushroom_recibir_evento_pago( new PeticionPrueba( array(), '{}', $evento ) );
$r2 = coremushroom_recibir_evento_pago( new PeticionPrueba( array(), '{}', $evento ) );
af( $r1 instanceof WP_REST_Response && 200 === $r1->status, 'confirma un evento que coincide' );
af( 1 === $GLOBALS['pedidos'][44]->pagos, 'un callback duplicado no cobra dos veces' );
$evento['amount_minor'] = 1;
af( coremushroom_recibir_evento_pago( new PeticionPrueba( array(), '{}', $evento ) ) instanceof WP_Error, 'rechaza pago con importe distinto' );

echo "\n--- Registro de pasarela ---\n";
if ( true ) {
	abstract class WC_Payment_Gateway {
		public $id, $has_fields, $method_title, $method_description, $form_fields, $title, $description, $settings = array();
		public function init_settings() {}
		public function get_option( $k, $d = '' ) { return $this->settings[ $k ] ?? $d; }
		public function get_field_key( $k ) { return 'gateway_' . $k; }
		public function is_available() { return true; }
		public function get_return_url( $o = null ) { return ''; }
	}
}
$pasarelas = coremushroom_registrar_pasarela_coreadaptogenos( array() );
af( class_exists( 'CoreMushroom_Gateway_Tarjeta' ), 'declara la pasarela cuando WooCommerce esta listo' );
af( in_array( 'CoreMushroom_Gateway_Tarjeta', $pasarelas, true ), 'entrega la clase a WooCommerce' );
$pasarela = new CoreMushroom_Gateway_Tarjeta();
$pasarela->settings = $config;
$GLOBALS['puente_admin'] = false;
af( ! $pasarela->is_available(), 'oculta el entorno de pruebas a clientes' );
$GLOBALS['puente_admin'] = true;
af( $pasarela->is_available(), 'permite probar el puente a un administrador' );
$GLOBALS['puente_admin'] = false;
$pasarela->settings['environment'] = 'live';
af( $pasarela->is_available(), 'permite el entorno en vivo al publico cuando esta configurado' );

echo "\n" . ( 0 === $fallos ? 'TODO OK' : "$fallos FALLOS" ) . "\n";
exit( 0 === $fallos ? 0 : 1 );
