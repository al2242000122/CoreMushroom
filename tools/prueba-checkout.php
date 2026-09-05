<?php
/**
 * Prueba de la constancia de aceptacion de terminos.
 *
 * Simula lo justo de WordPress y WooCommerce para ejecutar el modulo real.
 *
 * Uso: php tools/prueba-checkout.php <ruta-del-tema>
 */
define('ABSPATH', __DIR__);
$TEMA = $argv[1] ?? '.';

$GLOBALS['terminos_id'] = 0;
$GLOBALS['cap']         = true;
$GLOBALS['paginas']     = [];

function add_action($h, $f, $p = 10, $a = 1) { $GLOBALS['ganchos'][$h][] = $f; }
function add_filter($h, $f, $p = 10, $a = 1) { $GLOBALS['ganchos'][$h][] = $f; }
function current_user_can($c) { return $GLOBALS['cap']; }
function wc_terms_and_conditions_page_id() { return $GLOBALS['terminos_id']; }
function current_time($t) { return '2026-09-05 12:00:00'; }
function get_post_modified_time($f, $gmt, $id) { return $GLOBALS['paginas'][$id]['mod'] ?? false; }
function get_post($id) { return isset($GLOBALS['paginas'][$id]) ? (object) ['ID' => $id] : null; }
function get_permalink($id) { return 'https://ejemplo.test/terminos/'; }
function get_the_title($id) { return $GLOBALS['paginas'][$id]['titulo'] ?? ''; }
function admin_url($r = '') { return 'https://ejemplo.test/wp-admin/' . $r; }
function __($t, $d = '') { return $t; }
function esc_html__($t, $d = '') { return htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); }
function esc_html($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
function esc_attr($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
function esc_url($u) {
    $u = trim((string) $u);
    $esq = strtolower((string) parse_url($u, PHP_URL_SCHEME));
    return in_array($esq, ['http', 'https'], true) ? htmlspecialchars($u, ENT_QUOTES, 'UTF-8') : '';
}

class WC_Order {
    public $meta = [];
    public function update_meta_data($k, $v) { $this->meta[$k] = $v; }
    public function get_meta($k) { return $this->meta[$k] ?? ''; }
}

require $TEMA . '/inc/checkout-consentimiento.php';

$fallos = 0;
function af($cond, $msg) {
    global $fallos;
    if ($cond) { echo "OK    $msg\n"; } else { echo "FALLA $msg\n"; $fallos++; }
}

echo "--- Con pagina de terminos configurada ---\n";
$GLOBALS['terminos_id'] = 42;
$GLOBALS['paginas'][42] = ['mod' => '2026-08-20 09:30:00', 'titulo' => 'Terminos de uso'];

$pedido = new WC_Order();
coremushroom_guardar_consentimiento($pedido, []);
af($pedido->get_meta(COREMUSHROOM_CONSENT_ACEPTADO) === 'si', 'marca el consentimiento como aceptado');
af($pedido->get_meta(COREMUSHROOM_CONSENT_FECHA) === '2026-09-05 12:00:00', 'guarda la fecha de aceptacion');
af((int) $pedido->get_meta(COREMUSHROOM_CONSENT_PAGINA) === 42, 'guarda que pagina se acepto');
af($pedido->get_meta(COREMUSHROOM_CONSENT_VERSION) === '2026-08-20 09:30:00',
   'guarda la version del texto, no solo que se acepto');

echo "\n--- Sin pagina de terminos ---\n";
$GLOBALS['terminos_id'] = 0;
$pedido2 = new WC_Order();
coremushroom_guardar_consentimiento($pedido2, []);
af($pedido2->get_meta(COREMUSHROOM_CONSENT_ACEPTADO) === 'sin-pagina',
   'deja constancia de que no hubo casilla, en vez de callar');
af($pedido2->get_meta(COREMUSHROOM_CONSENT_FECHA) === '',
   'no inventa una fecha de aceptacion que no ocurrio');

echo "\n--- Objeto que no es un pedido ---\n";
$falso = new stdClass();
coremushroom_guardar_consentimiento($falso, []);
af(!isset($falso->meta), 'ignora lo que no sea un WC_Order sin reventar');

echo "\n--- Pantalla del pedido en el panel ---\n";
$GLOBALS['terminos_id'] = 42;
$pedido3 = new WC_Order();
coremushroom_guardar_consentimiento($pedido3, []);
ob_start(); coremushroom_mostrar_consentimiento($pedido3); $html = ob_get_clean();
af(str_contains($html, 'Aceptación de términos'), 'imprime el encabezado');
af(str_contains($html, '2026-09-05 12:00:00'), 'muestra la fecha');
af(str_contains($html, '2026-08-20 09:30:00'), 'muestra la version del texto');
af(str_contains($html, 'href="https://ejemplo.test/terminos/"'), 'enlaza la pagina aceptada');
af(str_contains($html, 'rel="noopener noreferrer"'), 'el enlace lleva rel de seguridad');

ob_start(); coremushroom_mostrar_consentimiento(new WC_Order()); $vacio = ob_get_clean();
af($vacio === '', 'un pedido sin constancia no imprime una seccion vacia');

$GLOBALS['terminos_id'] = 0;
$pedido4 = new WC_Order();
coremushroom_guardar_consentimiento($pedido4, []);
ob_start(); coremushroom_mostrar_consentimiento($pedido4); $sinpag = ob_get_clean();
af(str_contains($sinpag, 'sin casilla de términos'),
   'avisa en el pedido que se completo sin casilla');

echo "\n--- Escapado ---\n";
$GLOBALS['terminos_id'] = 43;
$GLOBALS['paginas'][43] = ['mod' => '2026-01-01 00:00:00',
                           'titulo' => '<script>alert(1)</script>'];
$pedido5 = new WC_Order();
coremushroom_guardar_consentimiento($pedido5, []);
ob_start(); coremushroom_mostrar_consentimiento($pedido5); $xss = ob_get_clean();
af(!str_contains($xss, '<script>'), 'escapa el titulo de la pagina');
af(str_contains($xss, '&lt;script&gt;'), 'lo imprime escapado');

echo "\n--- Aviso del panel ---\n";
$GLOBALS['terminos_id'] = 0;
$GLOBALS['cap'] = true;
ob_start(); coremushroom_avisar_terminos_sin_configurar(); $aviso = ob_get_clean();
af(str_contains($aviso, 'notice-warning'), 'avisa cuando falta la pagina de terminos');

$GLOBALS['terminos_id'] = 42;
ob_start(); coremushroom_avisar_terminos_sin_configurar(); $sinAviso = ob_get_clean();
af($sinAviso === '', 'no avisa si la pagina ya esta configurada');

$GLOBALS['terminos_id'] = 0;
$GLOBALS['cap'] = false;
ob_start(); coremushroom_avisar_terminos_sin_configurar(); $sinCap = ob_get_clean();
af($sinCap === '', 'no muestra el aviso a quien no administra la tienda');

echo "\n" . ($fallos === 0 ? 'TODO OK' : "$fallos FALLOS") . "\n";
exit($fallos === 0 ? 0 : 1);
