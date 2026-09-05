<?php
/**
 * Prueba del comprobante de transferencia.
 *
 * Cubre lo que importa de este modulo: que el tipo de archivo se decida por
 * los bytes y no por la extension, que la ruta en disco no se pueda torcer,
 * y que solo vea el comprobante quien tiene derecho a verlo.
 *
 * Uso: php tools/prueba-comprobante.php <ruta-del-tema>
 */
define('ABSPATH', __DIR__);
$TEMA = rtrim($argv[1] ?? '.', "/\\");

$GLOBALS['usuario']  = 0;
$GLOBALS['permisos'] = [];
$GLOBALS['get']      = [];

function add_action($h, $f, $p = 10, $a = 1) {}
function add_filter($h, $f, $p = 10, $a = 1) {}
function __($t, $d = '') { return $t; }
function esc_html__($t, $d = '') { return $t; }
function esc_html($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
function esc_url($u) { return $u; }
function esc_attr($t) { return $t; }
function trailingslashit($s) { return rtrim($s, "/\\") . '/'; }
function wp_mkdir_p($d) { return is_dir($d) || mkdir($d, 0755, true); }
function wp_upload_dir() { return ['basedir' => $GLOBALS['dir_uploads'], 'error' => false]; }
function get_current_user_id() { return $GLOBALS['usuario']; }
function current_user_can($cap) { return in_array($cap, $GLOBALS['permisos'], true); }
function sanitize_text_field($t) { return trim(strip_tags((string) $t)); }
function wp_unslash($v) { return $v; }
function wp_generate_password($n = 12, $s = true, $e = true) {
    return substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes($n * 2))), 0, $n);
}

class WP_Error {
    private $codigo, $mensaje;
    public function __construct($c = '', $m = '') { $this->codigo = $c; $this->mensaje = $m; }
    public function get_error_message() { return $this->mensaje; }
    public function get_error_code() { return $this->codigo; }
}
function is_wp_error($t) { return $t instanceof WP_Error; }

class WC_Order {
    private $id, $cliente, $clave, $metodo, $estado, $meta = [];
    public function __construct($id, $cliente = 0, $clave = '', $metodo = 'coremushroom_spei', $estado = 'cm-spei') {
        $this->id = $id; $this->cliente = $cliente; $this->clave = $clave;
        $this->metodo = $metodo; $this->estado = $estado;
    }
    public function get_id() { return $this->id; }
    public function get_customer_id() { return $this->cliente; }
    public function get_order_key() { return $this->clave; }
    public function get_payment_method() { return $this->metodo; }
    public function has_status($e) { return $this->estado === $e; }
    public function get_meta($k) { return $this->meta[$k] ?? ''; }
    public function update_meta_data($k, $v) { $this->meta[$k] = $v; }
    public function delete_meta_data($k) { unset($this->meta[$k]); }
    public function add_order_note($n, $a = false, $b = false) {}
    public function save() {}
}

$GLOBALS['dir_uploads'] = sys_get_temp_dir() . '/cm-prueba-' . getmypid();
mkdir($GLOBALS['dir_uploads'], 0755, true);

require $TEMA . '/inc/comprobante.php';

$fallos = 0;
function af($cond, $msg) {
    global $fallos;
    if ($cond) { echo "OK    $msg\n"; } else { echo "FALLA $msg\n"; $fallos++; }
}

// ---------------------------------------------------------------------------
echo "--- El tipo se decide por los bytes, no por la extension ---\n";
$banco = $GLOBALS['dir_uploads'] . '/muestras';
mkdir($banco, 0755, true);

// La carga "peligrosa" es una apertura de PHP inofensiva. Lo que se prueba es
// que un archivo que empieza por <?php no pase por imagen, no lo que haga
// ese PHP. Una carga real de webshell hace que el antivirus bloquee el
// archivo y la prueba falle por un motivo que no tiene que ver.
$muestras = [
    'real.jpg'          => ["\xFF\xD8\xFF\xE0" . str_repeat("\x00", 20), 'jpg'],
    'real.png'          => ["\x89PNG\r\n\x1A\n" . str_repeat("\x00", 20), 'png'],
    'real.webp'         => ["RIFF\x24\x00\x00\x00WEBPVP8 " . str_repeat("\x00", 10), 'webp'],
    'real.pdf'          => ["%PDF-1.7\n" . str_repeat('x', 20), 'pdf'],
    'audio_riff.webp'   => ["RIFF\x24\x00\x00\x00WAVEfmt " . str_repeat("\x00", 10), ''],
    'php.jpg'           => ['<' . '?php echo 1; ?' . '>' . str_repeat("\x00", 10), ''],
    'svg.png'           => ['<svg xmlns="http://www.w3.org/2000/svg"></svg>', ''],
    'html.pdf'          => ['<html><body>hola</body></html>', ''],
    'vacio.pdf'         => ['', ''],
    'texto.jpg'         => ['solo texto plano sin firma', ''],
    'jpg_con_php_detras' => ["\xFF\xD8\xFF" . '<' . '?php echo 1; ?' . '>', 'jpg'],
];

foreach ($muestras as $nombre => [$bytes, $esperado]) {
    file_put_contents($banco . '/' . $nombre, $bytes);
    $r = coremushroom_tipo_real($banco . '/' . $nombre);
    af($r === $esperado, sprintf('%-20s -> %s', $nombre, '' === $r ? 'rechazado' : $r));
}

af(coremushroom_tipo_real($banco . '/no-existe.jpg') === '', 'un archivo inexistente se rechaza');

echo "\n  Nota: jpg_con_php_detras se acepta como JPG porque su firma lo es.\n";
echo "  Eso es correcto y no es un agujero: el archivo se guarda con nombre\n";
echo "  aleatorio y extension .dat en un directorio cerrado, y se entrega con\n";
echo "  Content-Disposition attachment y nosniff. Nunca lo ejecuta el servidor.\n";

// ---------------------------------------------------------------------------
echo "\n--- La ruta en disco no se puede torcer ---\n";
$rutas = [
    '0123456789abcdef0123456789abcdef.dat' => true,
    '../../../wp-config.php'                => false,
    '0123456789abcdef0123456789abcdef.php'  => false,
    'A123456789ABCDEF0123456789ABCDEF.dat'  => false,
    'corto.dat'                             => false,
    '0123456789abcdef0123456789abcdef.dat/../x' => false,
    '/etc/passwd'                           => false,
    ''                                      => false,
    '0123456789abcdef0123456789abcde.dat'   => false,
];
foreach ($rutas as $n => $valida) {
    $r = coremushroom_ruta_comprobante($n);
    $ok = ('' !== $r) === $valida;
    af($ok, sprintf('%-44s -> %s', '' === $n ? '(vacio)' : $n, '' === $r ? 'rechazada' : 'aceptada'));
}

// ---------------------------------------------------------------------------
echo "\n--- Quien puede ver el comprobante ---\n";
$pedido = new WC_Order(50, 7, 'wc_order_ABC123');

$GLOBALS['usuario'] = 0; $GLOBALS['permisos'] = []; $_GET = [];
af(coremushroom_puede_ver_pedido($pedido) === false, 'un desconocido sin clave no puede');

$GLOBALS['usuario'] = 9;
af(coremushroom_puede_ver_pedido($pedido) === false, 'un usuario que no es el dueno no puede');

$GLOBALS['usuario'] = 7;
af(coremushroom_puede_ver_pedido($pedido) === true, 'el dueno del pedido si puede');

$GLOBALS['usuario'] = 0; $GLOBALS['permisos'] = ['edit_shop_orders'];
af(coremushroom_puede_ver_pedido($pedido) === true, 'quien gestiona pedidos si puede');

$GLOBALS['permisos'] = [];
$_GET = ['key' => 'wc_order_ABC123'];
af(coremushroom_puede_ver_pedido($pedido) === true, 'un invitado con la clave correcta si puede');

$_GET = ['key' => 'wc_order_ABC124'];
af(coremushroom_puede_ver_pedido($pedido) === false, 'con la clave cambiada en un caracter no puede');

// sanitize_text_field recorta espacios, asi que una clave con espacio al
// final si coincide. No es una debilidad: la clave sigue teniendo que ser
// exacta, y recortar espacios de un parametro de URL no le regala nada a
// nadie. Se deja escrito para que no se lea como un descuido.
$_GET = ['key' => 'wc_order_ABC123 '];
af(coremushroom_puede_ver_pedido($pedido) === true, 'un espacio al final de la clave se recorta y sigue valiendo');

$_GET = ['key' => 'wc_order_ABC12'];
af(coremushroom_puede_ver_pedido($pedido) === false, 'una clave truncada no puede');

$_GET = ['key' => ''];
af(coremushroom_puede_ver_pedido($pedido) === false, 'una clave vacia no puede');

$_GET = [];
af(coremushroom_puede_ver_pedido(null) === false, 'sin pedido devuelve false');

// ---------------------------------------------------------------------------
echo "\n--- Que pedidos admiten comprobante ---\n";
af(coremushroom_admite_comprobante(new WC_Order(1, 0, '', 'coremushroom_spei', 'cm-spei')) === true,
   'un pedido SPEI esperando comprobante si');
af(coremushroom_admite_comprobante(new WC_Order(2, 0, '', 'coremushroom_spei', 'processing')) === false,
   'un pedido SPEI ya confirmado no, para que no se sustituya el comprobante');
af(coremushroom_admite_comprobante(new WC_Order(3, 0, '', 'otra_pasarela', 'cm-spei')) === false,
   'un pedido de otra pasarela no');
af(coremushroom_admite_comprobante(null) === false, 'sin pedido no');

// ---------------------------------------------------------------------------
echo "\n--- El directorio se blinda al crearse ---\n";
$dir = coremushroom_dir_comprobantes();
af(!is_wp_error($dir), 'se crea el directorio');
af(is_file($dir . '.htaccess'), 'deja un .htaccess');
$reglas = file_get_contents($dir . '.htaccess');
af(str_contains($reglas, 'Require all denied'), 'el .htaccess niega el acceso');
af(str_contains($reglas, 'RemoveHandler'), 'el .htaccess quita el manejador de PHP');
af(str_contains($reglas, 'php_flag engine off'), 'el .htaccess apaga el motor de PHP');
af(str_contains($reglas, 'Options -Indexes'), 'el .htaccess prohibe listar el directorio');
af(is_file($dir . 'index.php'), 'deja un index.php de silencio');

// Comprobar solo que el archivo existe no basta: un restore puede dejarlo
// vacio. Se verifica el contenido y se repara.
file_put_contents($dir . '.htaccess', "vaciado por accidente
");
$dir2 = coremushroom_dir_comprobantes();
af(!is_wp_error($dir2), 'se sigue pudiendo usar el directorio');
af(file_get_contents($dir . '.htaccess') === coremushroom_reglas_comprobantes(),
   'un .htaccess alterado se repara solo');

echo "
--- Los comprobantes se acumulan, no se sustituyen ---
";
$ped = new WC_Order(70, 0, 'k', 'coremushroom_spei', 'cm-spei');
af(coremushroom_comprobantes($ped) === [], 'un pedido nuevo no tiene comprobantes');
$ped->update_meta_data(COREMUSHROOM_META_COMPROBANTES, [
    ['archivo' => str_repeat('a', 32) . '.dat', 'tipo' => 'jpg', 'fecha' => 1000],
    ['archivo' => str_repeat('b', 32) . '.dat', 'tipo' => 'pdf', 'fecha' => 2000],
]);
af(count(coremushroom_comprobantes($ped)) === 2, 'devuelve los dos guardados');
$ped->update_meta_data(COREMUSHROOM_META_COMPROBANTES, 'no es una lista');
af(coremushroom_comprobantes($ped) === [], 'un metadato corrupto no rompe nada');
$ped->update_meta_data(COREMUSHROOM_META_COMPROBANTES, [
    5, ['archivo' => str_repeat('c', 32) . '.dat', 'tipo' => 'png', 'fecha' => 3000],
]);
$r = coremushroom_comprobantes($ped);
af(count($r) === 1 && isset($r[0]['archivo']), 'descarta entradas que no son listas y reindexa');

echo "\n" . (0 === $fallos ? 'TODO OK' : "$fallos FALLOS") . "\n";

// Limpieza.
foreach (glob($banco . '/*') as $f) { @unlink($f); }
foreach (glob($dir . '{,.}*', GLOB_BRACE) as $f) { if (is_file($f)) { @unlink($f); } }
@rmdir($banco); @rmdir($dir); @rmdir($GLOBALS['dir_uploads']);

exit(0 === $fallos ? 0 : 1);
