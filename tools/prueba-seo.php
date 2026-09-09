<?php
/**
 * Prueba de los metadatos para buscadores y para compartir.
 *
 * Uso: php tools/prueba-seo.php <ruta-del-tema>
 */
define('ABSPATH', __DIR__);
$TEMA = rtrim($argv[1] ?? '.', "/\\");

$GLOBALS['ctx'] = [
    'front' => false, 'singular' => false, 'producto' => false,
    'tax' => false, 'shop' => false, 'publico' => 1,
    'excerpt' => '', 'contenido' => '', 'thumb' => '',
];
$GLOBALS['bloginfo'] = ['name' => 'CoreMushroom', 'description' => 'Derivados funcionales de hongo'];

function add_action($h, $f, $p = 10, $a = 1) {}
function add_filter($h, $f, $p = 10, $a = 1) {}
function apply_filters($h, $v) { return $v; }
function __($t, $d = '') { return $t; }
function esc_attr($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
function esc_url($u) { return htmlspecialchars((string) $u, ENT_QUOTES, 'UTF-8'); }
function wp_strip_all_tags($t, $br = false) { return trim(strip_tags((string) $t)); }
function get_bloginfo($k) { return $GLOBALS['bloginfo'][$k] ?? ''; }
function get_locale() { return 'es_MX'; }
function get_option($k, $d = false) { return 'blog_public' === $k ? $GLOBALS['ctx']['publico'] : $d; }
function is_front_page() { return $GLOBALS['ctx']['front']; }
function is_singular($t = '') { return $GLOBALS['ctx']['singular']; }
function is_tax() { return $GLOBALS['ctx']['tax']; }
function is_category() { return false; }
function is_tag() { return false; }
function is_product() { return $GLOBALS['ctx']['producto']; }
function is_shop() { return $GLOBALS['ctx']['shop']; }
function get_queried_object_id() { return 5; }
function get_queried_object() { return null; }
function get_the_excerpt($id = 0) { return $GLOBALS['ctx']['excerpt']; }
function get_post($id = 0) { return (object) ['post_content' => $GLOBALS['ctx']['contenido']]; }
function has_post_thumbnail() { return '' !== $GLOBALS['ctx']['thumb']; }
function get_the_post_thumbnail_url($id, $t) { return $GLOBALS['ctx']['thumb']; }
function get_stylesheet_directory() { global $TEMA; return $TEMA; }
function current_user_can($c) { return true; }
function get_current_screen() { return (object) ['id' => 'dashboard']; }
function admin_url($p = '') { return 'https://ejemplo.test/wp-admin/' . $p; }
function esc_html($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
function get_stylesheet_directory_uri() { return 'https://ejemplo.test/tema'; }
function home_url($r = '/') { return 'https://ejemplo.test' . $r; }
function wp_parse_url($u, $c = -1) { return parse_url($u, $c); }
function trailingslashit($s) { return rtrim((string) $s, '/') . '/'; }
function wp_unslash($s) { return $s; }
function is_404() { return $GLOBALS['ctx']['es_404'] ?? false; }
function wp_safe_redirect($u, $s = 302, $x = '') { return true; }
function get_permalink($id = 0) { return 'https://ejemplo.test/producto-5/'; }
function get_term_link($t) { return ''; }
function is_wp_error($t) { return false; }
function wp_get_document_title() { return 'Chocolate de Cordyceps - CoreMushroom'; }
function wc_get_page_id($p) { return 6; }
function wc_get_product($id) { return $GLOBALS['producto_obj'] ?? false; }
class WP_Term { public $description = ''; }

class ProductoFalso {
    public $corta, $larga;
    public function __construct($c, $l = '') { $this->corta = $c; $this->larga = $l; }
    public function get_short_description() { return $this->corta; }
    public function get_description() { return $this->larga; }
}

require $TEMA . '/inc/seo.php';

$fallos = 0;
function af($cond, $msg) {
    global $fallos;
    if ($cond) { echo "OK    $msg\n"; } else { echo "FALLA $msg\n"; $fallos++; }
}

echo "--- Nombre provisional ---\n";
af(coremushroom_nombre_provisional('core') === 'CoreMushroom', 'corrige el nombre de instalacion');
af(coremushroom_nombre_provisional('Bosque Vivo') === 'Bosque Vivo', 'respeta cualquier nombre definitivo');

echo "--- Recorte de la descripcion ---\n";
af(coremushroom_recortar('Texto corto') === 'Texto corto', 'un texto corto no se toca');
af(coremushroom_recortar('') === '', 'una cadena vacia devuelve vacia');
af(coremushroom_recortar('  espacios   de   sobra  ') === 'espacios de sobra', 'colapsa espacios');
af(coremushroom_recortar('<p>Con <strong>etiquetas</strong></p>') === 'Con etiquetas', 'quita etiquetas HTML');

$largo = str_repeat('palabra ', 60);
$r = coremushroom_recortar($largo);
af(mb_strlen($r) <= COREMUSHROOM_DESCRIPCION_MAX + 1, 'respeta el limite, quedo ' . mb_strlen($r));
af(str_ends_with($r, '…'), 'termina en puntos suspensivos');
af(!str_contains($r, 'palabr…'), 'no parte una palabra por la mitad');

$acentos = str_repeat('cápsula de Cordyceps con extracto ', 10);
$ra = coremushroom_recortar($acentos);
af(mb_strlen($ra) <= COREMUSHROOM_DESCRIPCION_MAX + 1, 'cuenta caracteres, no bytes, con acentos');
af(mb_substr($ra, -2, 1) !== "\xc3", 'no corta un caracter acentuado por la mitad');

af(coremushroom_recortar('Una frase sin espacios' . str_repeat('x', 200)) !== '',
   'un texto sin espacios se recorta igual');

echo "\n--- De donde sale la descripcion ---\n";
$GLOBALS['ctx']['front'] = true;
af(coremushroom_descripcion() === 'Derivados funcionales de hongo', 'en la portada usa la bajada del sitio');
$GLOBALS['ctx']['front'] = false;

$GLOBALS['ctx']['singular'] = true;
$GLOBALS['ctx']['producto'] = true;
$GLOBALS['producto_obj'] = new ProductoFalso('Cacao 70% con extracto de Cordyceps militaris.');
af(coremushroom_descripcion() === 'Cacao 70% con extracto de Cordyceps militaris.',
   'en un producto usa su descripcion corta');

$GLOBALS['producto_obj'] = new ProductoFalso('', 'La descripcion larga del producto.');
af(coremushroom_descripcion() === 'La descripcion larga del producto.',
   'si no hay descripcion corta usa la larga');

$GLOBALS['producto_obj'] = new ProductoFalso('', '');
af(coremushroom_descripcion() === 'Derivados funcionales de hongo',
   'si el producto no tiene ninguna, cae a la bajada del sitio');

$GLOBALS['ctx']['producto'] = false;
$GLOBALS['ctx']['excerpt'] = 'El extracto de la entrada.';
af(coremushroom_descripcion() === 'El extracto de la entrada.', 'en una entrada usa su extracto');
$GLOBALS['ctx']['excerpt'] = '';
$GLOBALS['ctx']['contenido'] = 'El cuerpo de la entrada, que es largo.';
af(coremushroom_descripcion() === 'El cuerpo de la entrada, que es largo.',
   'sin extracto usa el cuerpo');

echo "\n--- Titulo para compartir ---\n";
$GLOBALS['ctx']['front'] = true;
af(coremushroom_titulo() === 'CoreMushroom', 'en la portada es el nombre del sitio');
$GLOBALS['ctx']['front'] = false;
af(coremushroom_titulo() === 'Chocolate de Cordyceps',
   'en el resto quita el nombre del sitio, que ya va en og:site_name');

echo "\n--- Etiquetas que se imprimen ---\n";
$GLOBALS['ctx']['thumb'] = 'https://ejemplo.test/foto.jpg';
ob_start(); coremushroom_metadatos(); $h = ob_get_clean();
foreach ([
    'name="description"', 'property="og:site_name"', 'property="og:locale"',
    'property="og:type"', 'property="og:title"', 'property="og:description"',
    'property="og:url"', 'property="og:image"', 'name="twitter:card"',
] as $etiqueta) {
    af(str_contains($h, $etiqueta), "imprime $etiqueta");
}
af(str_contains($h, 'content="summary_large_image"'), 'con imagen usa la tarjeta grande');
af(str_contains($h, 'content="article"'), 'una entrada se marca como article');

$GLOBALS['ctx']['thumb'] = '';
ob_start(); coremushroom_metadatos(); $h2 = ob_get_clean();
af(str_contains($h2, 'screenshot.png'), 'sin foto destacada usa la captura del tema');

$GLOBALS['ctx']['front'] = true;
ob_start(); coremushroom_metadatos(); $h3 = ob_get_clean();
af(str_contains($h3, 'content="website"'), 'la portada se marca como website');
$GLOBALS['ctx']['front'] = false;

echo "\n--- Escapado ---\n";
$GLOBALS['ctx']['excerpt'] = 'Comillas " y <script>alert(1)</script> y ampersand &';
ob_start(); coremushroom_metadatos(); $h4 = ob_get_clean();
af(!str_contains($h4, '<script'), 'no deja pasar una etiqueta script');
af(!preg_match('/content="[^"]*"[^">]*"/', $h4), 'una comilla en el texto no rompe el atributo');
af(str_contains($h4, '&amp;'), 'escapa el ampersand');

echo "\n--- Se respeta la casilla de no indexar ---\n";
$GLOBALS['ctx']['publico'] = 0;
ob_start(); coremushroom_metadatos(); $h5 = ob_get_clean();
af(str_contains($h5, 'noindex, nofollow'), 'con la casilla marcada agrega noindex y nofollow');
$GLOBALS['ctx']['publico'] = 1;
ob_start(); coremushroom_metadatos(); $h6 = ob_get_clean();
af(!str_contains($h6, 'noindex'), 'sin la casilla no agrega noindex');

echo "
--- Aviso cuando falta la identidad del sitio ---
";
$GLOBALS['bloginfo'] = ['name' => 'core', 'description' => ''];
ob_start(); coremushroom_avisar_identidad_sin_configurar(); $av = ob_get_clean();
af(str_contains($av, 'descripción corta'), 'avisa si falta la bajada del sitio');
af(str_contains($av, 'nombre del sitio'), 'avisa si el nombre sigue siendo el de la instalacion');

$GLOBALS['bloginfo'] = ['name' => 'CoreMushroom', 'description' => 'Derivados funcionales de hongo'];
ob_start(); coremushroom_avisar_identidad_sin_configurar(); $av2 = ob_get_clean();
af('' === $av2, 'con los dos configurados no avisa nada');

echo "\n--- Compatibilidad del enlace de envios ---\n";
af(
    coremushroom_destino_envios('/envios/', true) === 'https://ejemplo.test/politica-de-envios/',
    'el enlace antiguo redirige a la politica publicada'
);
af(
    coremushroom_destino_envios('/envios?origen=home', true) === 'https://ejemplo.test/politica-de-envios/',
    'acepta la ruta sin diagonal aunque la solicitud lleve una consulta'
);
af(coremushroom_destino_envios('/otra-pagina/', true) === '', 'no interfiere con otros errores 404');
af(coremushroom_destino_envios('/envios/', false) === '', 'no sustituye una pagina real si se crea despues');

echo "\n" . (0 === $fallos ? 'TODO OK' : "$fallos FALLOS") . "\n";
exit(0 === $fallos ? 0 : 1);
