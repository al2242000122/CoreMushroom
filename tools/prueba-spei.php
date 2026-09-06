<?php
/**
 * Prueba del cobro por SPEI.
 *
 * Simula lo justo de WordPress y WooCommerce para ejercitar la validacion de
 * la CLABE, el estado de pedido propio y las instrucciones de pago.
 *
 * Uso: php tools/prueba-spei.php <ruta-del-tema>
 */
define('ABSPATH', __DIR__);
$TEMA = $argv[1] ?? '.';

$GLOBALS['ganchos']  = [];
$GLOBALS['estados']  = [];
$GLOBALS['opciones'] = [];
$GLOBALS['pedidos']  = [];

function add_action($h, $f, $p = 10, $a = 1) { $GLOBALS['ganchos'][$h][] = $f; }
function add_filter($h, $f, $p = 10, $a = 1) { $GLOBALS['ganchos'][$h][] = $f; }
function register_post_status($s, $a = []) { $GLOBALS['estados'][$s] = $a; }
function __($t, $d = '') { return $t; }
function _x($t, $c, $d = '') { return $t; }
function _n($a, $b, $n, $d = '') { return 1 === $n ? $a : $b; }
function _n_noop($a, $b, $d = '') { return [$a, $b]; }
function esc_html($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
function esc_html__($t, $d = '') { return esc_html($t); }
function get_option($k, $d = []) { return $GLOBALS['opciones'][$k] ?? $d; }
function wp_strip_all_tags($t) { return strip_tags((string) $t); }
function wc_price($n, $a = []) { return '$' . number_format((float) $n, 2); }
function wpautop($t) { return '<p>' . $t . '</p>'; }
function wptexturize($t) { return $t; }
function wp_kses_post($t) { return strip_tags((string) $t, '<p><a><strong><em><br>'); }

class WC_Order {
    private $id, $metodo, $total, $numero, $estado;
    public function __construct($id, $metodo, $total, $numero, $estado = 'cm-spei') {
        $this->id = $id; $this->metodo = $metodo; $this->total = $total;
        $this->numero = $numero; $this->estado = $estado;
    }
    public function get_id() { return $this->id; }
    public function get_payment_method() { return $this->metodo; }
    public function get_total() { return $this->total; }
    public function get_currency() { return 'MXN'; }
    public function get_order_number() { return $this->numero; }
    public function has_status($e) { return $this->estado === $e; }
}
function wc_get_order($id) { return $GLOBALS['pedidos'][$id] ?? false; }

require rtrim($TEMA, '/\\') . '/inc/pago-spei.php';

$fallos = 0;
function af($cond, $msg) {
    global $fallos;
    if ($cond) { echo "OK    $msg\n"; } else { echo "FALLA $msg\n"; $fallos++; }
}

echo "--- Validacion de CLABE ---\n";
// CLABE real de ejemplo publicada por Banxico en su documentacion.
af(coremushroom_clabe_valida('032180000118359719') === true, 'acepta una CLABE con digito verificador correcto');
af(coremushroom_clabe_valida('032180000118359710') === false, 'rechaza la misma CLABE con el verificador cambiado');
af(coremushroom_clabe_valida('') === false, 'rechaza cadena vacia');
af(coremushroom_clabe_valida('12345') === false, 'rechaza menos de 18 digitos');
af(coremushroom_clabe_valida('0321800001183597199') === false, 'rechaza mas de 18 digitos');
af(coremushroom_clabe_valida('abcdefghijklmnopqr') === false, 'rechaza letras');
af(coremushroom_clabe_valida('03218000011835971 ') === false, 'rechaza espacio final');
af(coremushroom_clabe_valida("032180000118359719\n") === false, 'rechaza salto de linea pegado');

echo "\n--- Estado de pedido propio ---\n";
foreach ($GLOBALS['ganchos']['init'] as $f) { $f(); }
af(isset($GLOBALS['estados']['wc-cm-spei']), 'registra el estado wc-cm-spei');
af($GLOBALS['estados']['wc-cm-spei']['show_in_admin_status_list'] === true, 'el estado se puede filtrar en el panel');

$lista = coremushroom_agregar_estado_spei([
    'wc-pending' => 'Pendiente', 'wc-processing' => 'Procesando', 'wc-completed' => 'Completado',
]);
$claves = array_keys($lista);
af(in_array('wc-cm-spei', $claves, true), 'el estado aparece en la lista de WooCommerce');
af(array_search('wc-cm-spei', $claves, true) === 1, 'queda justo despues de pendiente de pago');

// Si WooCommerce renombrara wc-pending, el estado no debe perderse.
$lista2 = coremushroom_agregar_estado_spei(['wc-otra-cosa' => 'Otra']);
af(in_array('wc-cm-spei', array_keys($lista2), true), 'no se pierde si cambia el nombre de wc-pending');

$pend = coremushroom_estado_spei_es_pendiente(['pending', 'failed']);
af(in_array('cm-spei', $pend, true), 'cuenta como pedido pendiente de pago');

echo "\n--- Instrucciones de pago ---\n";
$GLOBALS['opciones']['woocommerce_coremushroom_spei_settings'] = [
    'clabe'         => '032180000118359719',
    'banco'         => 'BBVA',
    'beneficiario'  => 'Nombre del beneficiario',
    'plazo_horas'   => '48',
    'instrucciones' => 'Manda tu comprobante si tarda mas de un dia.',
];
$GLOBALS['pedidos'][10] = new WC_Order(10, 'coremushroom_spei', 1130.00, 'CM-10');
$GLOBALS['pedidos'][11] = new WC_Order(11, 'otra_pasarela', 500.00, 'CM-11');

$h = coremushroom_instrucciones_spei(10);
af(str_contains($h, '032180000118359719'), 'muestra la CLABE');
af(str_contains($h, 'CM-10'), 'muestra la referencia del pedido');
af(str_contains($h, '1,130.00'), 'muestra el monto exacto');
af(str_contains($h, 'BBVA'), 'muestra el banco');
af(str_contains($h, 'cm-datos'), 'usa el componente de tabla del sistema de diseno');
af(str_contains($h, '48 horas'), 'muestra el plazo en plural');
af(coremushroom_instrucciones_spei(11) === '', 'no imprime nada para otra pasarela');
af(coremushroom_instrucciones_spei(999) === '', 'no imprime nada para un pedido inexistente');

$GLOBALS['opciones']['woocommerce_coremushroom_spei_settings']['plazo_horas'] = '1';
af(str_contains(coremushroom_instrucciones_spei(10), '1 hora') &&
   !str_contains(coremushroom_instrucciones_spei(10), '1 horas'), 'concuerda el singular del plazo');

$GLOBALS['opciones']['woocommerce_coremushroom_spei_settings']['clabe'] = '';
af(coremushroom_instrucciones_spei(10) === '', 'sin CLABE configurada no imprime instrucciones a medias');
$GLOBALS['opciones']['woocommerce_coremushroom_spei_settings']['clabe'] = '032180000118359719';

echo "\n--- Escapado ---\n";
$GLOBALS['opciones']['woocommerce_coremushroom_spei_settings']['beneficiario'] = '<script>alert(1)</script>';
$GLOBALS['opciones']['woocommerce_coremushroom_spei_settings']['banco'] = '" onmouseover="x';
$h2 = coremushroom_instrucciones_spei(10);
af(!str_contains($h2, '<script'), 'no deja pasar una etiqueta script en el beneficiario');
af(str_contains($h2, '&lt;script&gt;'), 'la imprime escapada');
af(!preg_match('/<t[dh][^>]*\son\w+=/i', $h2), 'ninguna celda queda con atributo de evento');
$GLOBALS['opciones']['woocommerce_coremushroom_spei_settings']['beneficiario'] = 'Nombre';
$GLOBALS['opciones']['woocommerce_coremushroom_spei_settings']['banco'] = 'BBVA';

echo "\n--- Version de texto plano para el correo ---\n";
$t = coremushroom_instrucciones_spei(10, true);
af(!str_contains($t, '<'), 'el texto plano no lleva etiquetas HTML');
af(str_contains($t, '032180000118359719') && str_contains($t, 'CM-10'), 'lleva CLABE y referencia');

echo "\n--- Registro de pasarelas ---\n";
// Sin WC_Payment_Gateway no hay clase padre. No se declara nada y no se le
// pasa a WooCommerce el nombre de una clase que no existe.
$g = coremushroom_registrar_pasarelas([]);
af(!class_exists('CoreMushroom_Gateway_SPEI'), 'sin WC_Payment_Gateway no se declara ninguna clase');
af($g === [], 'y no se registra un nombre de clase inexistente');

// Con la clase padre presente, el filtro tiene que dejarlas listas. En esta
// prueba NINGUN gancho se despacha, que es justo lo que le pasa a
// plugins_loaded cuando WordPress incluye el functions.php del tema. Esta
// es la comprobacion que faltaba y por la que la pasarela no aparecia.
// Va dentro de un if a proposito. PHP adelanta al principio del archivo las
// declaraciones de clase que estan sueltas en el nivel superior, y entonces
// la clase padre ya existiria arriba y la prueba de "sin clase padre" no
// probaria nada. Dentro de un bloque, la declaracion ocurre cuando toca.
if (true) {
    abstract class WC_Payment_Gateway {
        public $id, $has_fields, $method_title, $method_description, $form_fields, $title, $description;
        public function init_settings() {}
        public function get_option($k, $d = '') { return $d; }
        public function is_available() { return true; }
        public function get_return_url($o = null) { return ''; }
    }
}

$g2 = coremushroom_registrar_pasarelas([]);
af(class_exists('CoreMushroom_Gateway_SPEI'), 'con la clase padre, la pasarela SPEI queda declarada');
af(class_exists('CoreMushroom_Gateway_Tarjeta'), 'y tambien el hueco de tarjeta');
af(in_array('CoreMushroom_Gateway_SPEI', $g2, true), 'WooCommerce recibe la pasarela SPEI');
af(in_array('CoreMushroom_Gateway_Tarjeta', $g2, true), 'WooCommerce recibe el hueco de tarjeta');

$g3 = coremushroom_registrar_pasarelas([]);
af(count($g3) === 2, 'llamarlo dos veces no duplica ni vuelve a declarar');

$spei = new CoreMushroom_Gateway_SPEI();
af($spei->id === 'coremushroom_spei', 'la pasarela SPEI se puede instanciar');
af($spei->method_title === 'Transferencia SPEI', 'su nombre en el panel es Transferencia SPEI');
$tarjeta = new CoreMushroom_Gateway_Tarjeta();
af($tarjeta->is_available() === false, 'el hueco de tarjeta nunca esta disponible');

echo "\n" . (0 === $fallos ? 'TODO OK' : "$fallos FALLOS") . "\n";
exit(0 === $fallos ? 0 : 1);
