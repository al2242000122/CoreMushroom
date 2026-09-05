<?php
/**
 * Prueba de humo de la Fase 4: campos de lote, tabla y tarjeta.
 * Simula lo justo de WordPress y WooCommerce para ejecutar el codigo real.
 *
 * Cuidado con los nombres de variable en el ambito global: aqui $meta seria
 * el mismo $GLOBALS['meta'] que usa el almacen simulado. Por eso todo local
 * de prueba lleva prefijo t_.
 */
define('ABSPATH', __DIR__);
$TEMA = $argv[1];

$GLOBALS['meta']       = [];
$GLOBALS['posts']      = [];
$GLOBALS['acciones']   = [];
$GLOBALS['filtros']    = [];
$GLOBALS['shortcodes'] = [];
$GLOBALS['cap']        = true;
$GLOBALS['nonce_ok']   = true;

// --- Nucleo simulado ---
function add_action($h, $f, $p = 10, $a = 1) { $GLOBALS['acciones'][$h][] = $f; }
function add_filter($h, $f, $p = 10, $a = 1) { $GLOBALS['filtros'][$h][] = $f; }
function add_shortcode($t, $f) { $GLOBALS['shortcodes'][$t] = $f; }
function add_meta_box() {}
function wp_nonce_field() {}
function wp_verify_nonce($n, $a) {
    if (!$GLOBALS['nonce_ok']) { return false; }
    // El nonce solo vale para la accion exacta con la que se emitio.
    return ($n === $a) ? 1 : false;
}
function current_user_can($c, $id = 0) { return $GLOBALS['cap']; }
function wp_unslash($v) { return is_string($v) ? stripslashes($v) : $v; }
function __($t, $d = '') { return $t; }
function esc_html__($t, $d = '') { return htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); }
function esc_html_e($t, $d = '') { echo esc_html__($t); }
function esc_html($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
function esc_attr($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
function esc_textarea($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
function esc_url($u) {
    $u = trim((string) $u);
    $esq = strtolower((string) parse_url($u, PHP_URL_SCHEME));
    if (!in_array($esq, ['http', 'https'], true)) { return ''; }
    return htmlspecialchars($u, ENT_QUOTES, 'UTF-8');
}
function esc_url_raw($u, $protocolos = null) {
    $u = trim((string) $u);
    $permitidos = $protocolos ?: ['http', 'https'];
    $esq = strtolower((string) parse_url($u, PHP_URL_SCHEME));
    if (!in_array($esq, $permitidos, true)) { return ''; }
    return $u;
}
function sanitize_text_field($t) { return trim(preg_replace('/\s+/', ' ', strip_tags((string) $t))); }
function sanitize_textarea_field($t) { return trim(strip_tags((string) $t)); }
function sanitize_html_class($c) { return preg_replace('/[^A-Za-z0-9_-]/', '', (string) $c); }
function selected($a, $b, $e = true) { return $a === $b ? ' selected' : ''; }
function date_i18n($f, $m) { return date('Y-m-d', $m); }
function is_singular($t = '') { return true; }
function get_the_ID() { return $GLOBALS['post_actual'] ?? 0; }
function get_post_type($id) { return $GLOBALS['posts'][$id]['type'] ?? false; }
function get_post_status($id) { return $GLOBALS['posts'][$id]['status'] ?? false; }
function post_password_required($id = null) { return !empty($GLOBALS['posts'][$id]['pass']); }
function wp_parse_url($u, $c = -1) { return parse_url($u, $c); }
function get_stylesheet_directory() { global $TEMA; return $TEMA; }
function shortcode_atts($pares, $atts, $sc = '') {
    $atts = (array) $atts; $out = [];
    foreach ($pares as $k => $v) { $out[$k] = array_key_exists($k, $atts) ? $atts[$k] : $v; }
    return $out;
}
function get_post_meta($id, $k, $single = false) { return $GLOBALS['meta'][$id][$k] ?? ''; }
function update_post_meta($id, $k, $v) { $GLOBALS['meta'][$id][$k] = $v; return true; }
function delete_post_meta($id, $k) { unset($GLOBALS['meta'][$id][$k]); return true; }

class WP_Post { public $ID; public $post_type; function __construct($id, $t = 'product') { $this->ID = $id; $this->post_type = $t; } }
class WC_Product {
    private $id; private $stock;
    function __construct($id, $stock = true) { $this->id = $id; $this->stock = $stock; }
    function get_id() { return $this->id; }
    function is_in_stock() { return $this->stock; }
}
class WooCommerce {}

require $TEMA . '/inc/lote-campos.php';
require $TEMA . '/inc/lote-tabla.php';
require $TEMA . '/inc/tarjeta-producto.php';

$GLOBALS['fallos'] = 0;
function af($cond, $msg) {
    if ($cond) { echo "OK    $msg\n"; } else { echo "FALLA $msg\n"; $GLOBALS['fallos']++; }
}
function guardar($id, array $campos, $tipo = 'product') {
    $_POST = ['coremushroom_lote_nonce' => 'coremushroom_guardar_lote_' . $id];
    foreach ($campos as $k => $v) { $_POST[COREMUSHROOM_META_PREFIJO . $k] = $v; }
    coremushroom_guardar_lote($id, new WP_Post($id, $tipo));
}
function leer($id, $k) { return $GLOBALS['meta'][$id][COREMUSHROOM_META_PREFIJO . $k] ?? null; }

echo "--- Guardado: puertas de seguridad ---\n";
$_POST = [COREMUSHROOM_META_PREFIJO . 'lote' => 'SIN-NONCE'];
coremushroom_guardar_lote(1, new WP_Post(1));
af(leer(1, 'lote') === null, 'sin nonce no escribe nada');

$GLOBALS['nonce_ok'] = false;
guardar(1, ['lote' => 'NONCE-MALO']);
af(leer(1, 'lote') === null, 'con nonce invalido no escribe nada');
$GLOBALS['nonce_ok'] = true;

$GLOBALS['cap'] = false;
guardar(1, ['lote' => 'SIN-PERMISO']);
af(leer(1, 'lote') === null, 'sin capacidad de editar no escribe nada');
$GLOBALS['cap'] = true;

guardar(1, ['lote' => 'OTRO-TIPO'], 'page');
af(leer(1, 'lote') === null, 'sobre un tipo que no es producto no escribe nada');

echo "\n--- Guardado: saneado ---\n";
guardar(2, [
    'lote'               => '  CM-2609-C4  ',
    'especie'            => 'cordyceps',
    'formato'            => 'chocolate',
    'contenido_neto'     => '60 g, 12 piezas',
    'ingredientes'       => "Cacao 70%\nExtracto de Cordyceps",
    'alergenos'          => 'Puede contener leche',
    'fecha_elaboracion'  => '2026-09-01',
    'consumo_preferente' => '2027-03-15',
    'certificado_url'    => 'https://ejemplo.test/coa.pdf',
]);
af(leer(2, 'lote') === 'CM-2609-C4', 'guarda el texto recortado');
af(leer(2, 'especie') === 'cordyceps', 'guarda una opcion valida del select');
af(leer(2, 'ingredientes') === "Cacao 70%\nExtracto de Cordyceps", 'el textarea conserva el salto de linea');
af(leer(2, 'consumo_preferente') === '2027-03-15', 'guarda una fecha valida');
af(leer(2, 'certificado_url') === 'https://ejemplo.test/coa.pdf', 'guarda una URL https');

guardar(3, ['especie' => 'psilocybe']);
af(leer(3, 'especie') === null, 'descarta un valor de select que no esta en las opciones');

guardar(3, ['fecha_elaboracion' => '2026-02-31']);
af(leer(3, 'fecha_elaboracion') === null, 'descarta el 31 de febrero, que encaja con el patron pero no existe');
guardar(3, ['fecha_elaboracion' => '01/09/2026']);
af(leer(3, 'fecha_elaboracion') === null, 'descarta una fecha que no viene en formato ISO');

guardar(3, ['certificado_url' => 'javascript:alert(1)']);
af(leer(3, 'certificado_url') === null, 'descarta una URL con esquema javascript');
guardar(3, ['certificado_url' => 'data:text/html;base64,PHNjcmlwdD4=']);
af(leer(3, 'certificado_url') === null, 'descarta una URL con esquema data');

guardar(3, ['lote' => '<script>alert(1)</script>ABC']);
af(leer(3, 'lote') === 'alert(1)ABC', 'quita las etiquetas del texto, guardado = ' . var_export(leer(3, 'lote'), true));

guardar(4, ['lote' => 'A-1', 'especie' => 'hericium']);
guardar(4, ['lote' => '', 'especie' => 'hericium']);
af(leer(4, 'lote') === null, 'un campo vaciado borra el metadato en vez de guardar cadena vacia');
af(leer(4, 'especie') === 'hericium', 'los demas campos no se tocan al vaciar uno');

echo "\n--- Guardado: hallazgos de la revision ---\n";
$_POST = ['coremushroom_lote_nonce' => 'coremushroom_guardar_lote_2',
          COREMUSHROOM_META_PREFIJO . 'lote' => 'CRUZADO'];
coremushroom_guardar_lote(5, new WP_Post(5));
af(leer(5, 'lote') === null, 'un nonce emitido para otro producto no sirve');

guardar(6, ['fecha_elaboracion' => "2026-01-01\n"]);
af(leer(6, 'fecha_elaboracion') === null, 'descarta una fecha con salto de linea pegado');

guardar(6, ['certificado_url' => '//otrodominio.tld/coa.pdf']);
af(leer(6, 'certificado_url') === null, 'descarta una URL sin esquema');
guardar(6, ['certificado_url' => '/wp-admin/algo']);
af(leer(6, 'certificado_url') === null, 'descarta una ruta relativa como URL');

guardar(6, ['lote' => str_repeat('A', 5000)]);
af(strlen(leer(6, 'lote')) === 300, 'recorta el texto al tope, quedo ' . strlen(leer(6, 'lote')));
guardar(6, ['ingredientes' => str_repeat('B', 5000)]);
af(strlen(leer(6, 'ingredientes')) === 2000, 'recorta el textarea al tope, quedo ' . strlen(leer(6, 'ingredientes')));

echo "\n--- Tabla ---\n";
$GLOBALS['posts'][2] = ['type' => 'product', 'status' => 'publish'];
$t_html = coremushroom_tabla_lote_html(2);
af(str_contains($t_html, 'cm-datos-envoltorio'), 'usa el envoltorio con desplazamiento');
af(str_contains($t_html, 'class="cm-datos"'), 'usa el componente cm-datos');
af(substr_count($t_html, '<tr>') === 9, 'una fila por campo con valor, hay ' . substr_count($t_html, '<tr>'));
af(str_contains($t_html, 'Cordyceps') && !str_contains($t_html, '>cordyceps<'), 'muestra la etiqueta, no la clave');
af(str_contains($t_html, '<br />') || str_contains($t_html, '<br>'), 'respeta los saltos de linea de los ingredientes');
af(str_contains($t_html, 'href="https://ejemplo.test/coa.pdf"'), 'enlaza el certificado');
af(str_contains($t_html, 'rel="noopener noreferrer"'), 'el enlace externo lleva rel de seguridad');
af(coremushroom_tabla_lote_html(999) === '', 'producto sin datos devuelve vacio, no una tabla hueca');

echo "\n--- Escapado en la salida ---\n";
// Se inyecta directo en el almacen, saltandose el saneado de guardado, para
// comprobar que la salida escapa aunque la base traiga basura de antes.
$GLOBALS['meta'][7] = [
    COREMUSHROOM_META_PREFIJO . 'lote'            => '<script>alert(1)</script>',
    COREMUSHROOM_META_PREFIJO . 'ingredientes'    => '<img src=x onerror=alert(1)>',
    COREMUSHROOM_META_PREFIJO . 'certificado_url' => 'javascript:alert(1)',
    COREMUSHROOM_META_PREFIJO . 'contenido_neto'  => '" onmouseover="alert(1)',
];
$t_h7 = coremushroom_tabla_lote_html(7);
af(!str_contains($t_h7, '<script'), 'no deja pasar una etiqueta script');
af(!str_contains($t_h7, '<img'), 'no deja pasar una etiqueta img');
af(str_contains($t_h7, '&lt;script&gt;'), 'la imprime escapada');
af(!preg_match('/href="javascript:/i', $t_h7), 'no genera un enlace con esquema javascript');
af(!preg_match('/<t[dh][^>]*\son\w+=/i', $t_h7), 'ninguna celda queda con un atributo de evento');

echo "\n--- Shortcode ---\n";
$t_sc = $GLOBALS['shortcodes']['coremushroom_ficha'];
$GLOBALS['post_actual'] = 2;
af($t_sc(['id' => 2]) !== '', 'con producto publicado devuelve la tabla');
$GLOBALS['posts'][8] = ['type' => 'product', 'status' => 'draft'];
$GLOBALS['meta'][8] = [COREMUSHROOM_META_PREFIJO . 'lote' => 'BORRADOR-SECRETO'];
af($t_sc(['id' => 8]) === '', 'no filtra datos de un borrador');
$GLOBALS['posts'][9] = ['type' => 'page', 'status' => 'publish'];
$GLOBALS['meta'][9] = [COREMUSHROOM_META_PREFIJO . 'lote' => 'OTRA-COSA'];
af($t_sc(['id' => 9]) === '', 'no lee metadatos de otro tipo de contenido');
af($t_sc(['id' => 999999]) === '', 'con id inexistente devuelve vacio');
$GLOBALS['posts'][11] = ['type' => 'product', 'status' => 'publish', 'pass' => 1];
$GLOBALS['meta'][11] = [COREMUSHROOM_META_PREFIJO . 'lote' => 'PROTEGIDO'];
af($t_sc(['id' => 11]) === '', 'no publica la ficha de un producto con contrasena');

echo "\n--- Tarjeta del bucle ---\n";
$GLOBALS['product'] = new WC_Product(2, true);
ob_start(); coremushroom_badge_especie_bucle(); $t_badge = ob_get_clean();
af(str_contains($t_badge, 'cm-badge--cordyceps'), 'el badge toma el color de la especie');
af(str_contains($t_badge, '>Cordyceps<'), 'el badge muestra la etiqueta legible');

ob_start(); coremushroom_meta_producto_bucle(); $t_meta = ob_get_clean();
af(str_contains($t_meta, 'Chocolate'), 'la meta muestra el formato');
af(str_contains($t_meta, '60 g, 12 piezas'), 'la meta muestra el contenido neto');
af(str_contains($t_meta, 'Disponible'), 'la meta muestra disponibilidad');
af(!str_contains($t_meta, 'cm-badge--oferta'), 'disponible no reutiliza el color de oferta');
af(!preg_match('/\d+\s*(en existencia|disponibles|unidades)/i', $t_meta), 'no muestra el numero de unidades');

$GLOBALS['product'] = new WC_Product(2, false);
ob_start(); coremushroom_meta_producto_bucle(); $t_meta2 = ob_get_clean();
af(str_contains($t_meta2, 'Agotado') && str_contains($t_meta2, 'cm-badge--agotado'), 'agotado usa su propio badge');

$GLOBALS['product'] = new WC_Product(999, true);
ob_start(); coremushroom_badge_especie_bucle(); $t_vacio = ob_get_clean();
af($t_vacio === '', 'producto sin especie no imprime badge');

$t_clases = coremushroom_clases_bucle(['product'], new WC_Product(2, true));
af(in_array('cm-producto', $t_clases, true), 'agrega la clase base a la tarjeta');
af(in_array('cm-producto--cordyceps', $t_clases, true), 'agrega la clase de especie');
$t_clases2 = coremushroom_clases_bucle(['product'], new WC_Product(2, false));
af(in_array('cm-producto--agotado', $t_clases2, true), 'marca la tarjeta agotada');
af(coremushroom_clases_bucle(['product'], null) === ['product'], 'sin producto devuelve las clases intactas');

// El autoguardado va al final porque la constante no se puede deshacer.
echo "\n--- Autoguardado ---\n";
define('DOING_AUTOSAVE', true);
guardar(10, ['lote' => 'AUTOGUARDADO']);
af(leer(10, 'lote') === null, 'en autoguardado no escribe nada');

echo "\n" . ($GLOBALS['fallos'] === 0 ? "TODO OK" : $GLOBALS['fallos'] . " FALLOS") . "\n";
exit($GLOBALS['fallos'] === 0 ? 0 : 1);
