<?php
/**
 * Prueba de regresion del arranque.
 *
 * WordPress incluye el functions.php del tema DESPUES de haber disparado
 * plugins_loaded. Enganchar la carga de modulos a ese gancho los deja
 * muertos sin ningun error visible.
 *
 * Esta prueba reproduce ese orden: los ganchos se registran pero NUNCA se
 * despachan, igual que le pasaria a plugins_loaded en un sitio real. Luego
 * comprueba que las funciones de los modulos existen de todos modos.
 *
 * Uso: php smoke_arranque.php <ruta-del-tema> [con-woo|sin-woo]
 */
define('ABSPATH', __DIR__);
$TEMA = $argv[1];
$MODO = $argv[2] ?? 'con-woo';

$GLOBALS['ganchos'] = [];

// Ningun add_action despacha nada. Es justo el punto de la prueba.
function add_action($h, $f, $p = 10, $a = 1) { $GLOBALS['ganchos'][$h][] = $f; }
function add_filter($h, $f, $p = 10, $a = 1) { $GLOBALS['ganchos'][$h][] = $f; }
function add_shortcode($t, $f) { $GLOBALS['ganchos']['shortcode:' . $t][] = $f; }
function add_meta_box() {}
function add_theme_support($f) {}
function add_editor_style($s) {}
function load_child_theme_textdomain() {}
function register_block_pattern_category($s, $a) {}
function register_post_status($s, $a = []) {}
function _x($t, $c, $d = '') { return $t; }
function _n($a, $b, $n, $d = '') { return 1 === $n ? $a : $b; }
function _n_noop($a, $b, $d = '') { return [$a, $b]; }
function unregister_block_pattern($s) {}
function wp_style_is($h, $l = 'enqueued') { return false; }
function wp_enqueue_style() {}
function wp_nonce_field() {}
function wp_verify_nonce() { return 1; }
function current_user_can() { return true; }
function wp_unslash($v) { return $v; }
function wp_parse_url($u, $c = -1) { return parse_url($u, $c); }
function __($t, $d = '') { return $t; }
function esc_html__($t, $d = '') { return $t; }
function esc_html_e($t, $d = '') { echo $t; }
function esc_html($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
function esc_attr($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
function esc_textarea($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
function esc_url($u) { return $u; }
function esc_url_raw($u, $p = null) { return $u; }
function sanitize_text_field($t) { return trim(strip_tags((string) $t)); }
function sanitize_textarea_field($t) { return trim(strip_tags((string) $t)); }
function sanitize_html_class($c) { return preg_replace('/[^A-Za-z0-9_-]/', '', (string) $c); }
function selected($a, $b, $e = true) { return ''; }
function date_i18n($f, $m, $g = false) { return date('Y-m-d', $m); }
function is_singular($t = '') { return false; }
function get_the_ID() { return 0; }
function get_post_type($id) { return 'product'; }
function get_post_status($id) { return 'publish'; }
function post_password_required($id = null) { return false; }
function wc_terms_and_conditions_page_id() { return 0; }
function current_time($t) { return '2026-01-01 00:00:00'; }
function get_post_modified_time($f, $g, $id) { return false; }
function get_post($id) { return null; }
function get_permalink($id) { return ''; }
function get_the_title($id) { return ''; }
function admin_url($r = '') { return ''; }
function get_post_meta($id, $k, $s = false) { return ''; }
function update_post_meta() { return true; }
function delete_post_meta() { return true; }
function shortcode_atts($p, $a, $sc = '') { return $p; }
function get_stylesheet_directory() { global $TEMA; return $TEMA; }
function get_stylesheet_directory_uri() { return 'https://x.test'; }
function get_stylesheet_uri() { return 'https://x.test/style.css'; }
function class_exists_woo() { return class_exists('WooCommerce'); }

if ('con-woo' === $MODO) {
    class WooCommerce {}
}

require $TEMA . '/functions.php';

$siempre = [
    'coremushroom_recortar',
    'coremushroom_descripcion',
    'coremushroom_metadatos',
    'coremushroom_aligerar_scripts',
    'coremushroom_quitar_tamanos',
];

$esperadas = [
    'coremushroom_campos_lote',
    'coremushroom_obtener_lote',
    'coremushroom_etiqueta_opcion',
    'coremushroom_guardar_lote',
    'coremushroom_tabla_lote_html',
    'coremushroom_shortcode_ficha',
    'coremushroom_badge_especie_bucle',
    'coremushroom_meta_producto_bucle',
    'coremushroom_clases_bucle',
    'coremushroom_clabe_valida',
    'coremushroom_instrucciones_spei',
    'coremushroom_registrar_pasarelas',
    'coremushroom_tipo_real',
    'coremushroom_puede_ver_pedido',
    'coremushroom_guardar_comprobante',
    'coremushroom_descargar_comprobante',
    'coremushroom_guardar_consentimiento',
    'coremushroom_mostrar_consentimiento',
    'coremushroom_avisar_terminos_sin_configurar',
];

$fallos = 0;
$debe_existir = ('con-woo' === $MODO);

echo "modo: $MODO\n";

// Estos cargan con o sin WooCommerce: no dependen de la tienda.
foreach ($siempre as $fn) {
    $ok = function_exists($fn);
    if (!$ok) { $fallos++; }
    printf("%s siempre   %s
", $ok ? 'OK   ' : 'FALLA', $fn);
}
foreach ($esperadas as $fn) {
    $existe = function_exists($fn);
    $ok = ($existe === $debe_existir);
    if (!$ok) { $fallos++; }
    printf("%s %s %s\n",
        $ok ? 'OK   ' : 'FALLA',
        $debe_existir ? 'cargada  ' : 'ausente  ',
        $fn);
}

// Con WooCommerce activo tambien deben haberse registrado sus ganchos.
if ($debe_existir) {
    foreach (['add_meta_boxes_product', 'save_post_product',
              'woocommerce_after_single_product_summary',
              'woocommerce_before_shop_loop_item_title',
              'woocommerce_after_shop_loop_item_title',
              'woocommerce_post_class',
              'shortcode:coremushroom_ficha',
              'woocommerce_checkout_create_order',
              'woocommerce_admin_order_data_after_billing_address',
              'admin_notices',
              'woocommerce_payment_gateways',
              'wc_order_statuses',
              'template_redirect',
              'admin_post_coremushroom_comprobante',
              'admin_post_coremushroom_confirmar_pago'] as $g) {
        $ok = !empty($GLOBALS['ganchos'][$g]);
        if (!$ok) { $fallos++; }
        printf("%s gancho    %s\n", $ok ? 'OK   ' : 'FALLA', $g);
    }
}

echo ($fallos === 0 ? "TODO OK" : "$fallos FALLOS") . "\n";
exit($fallos === 0 ? 0 : 1);
