<?php
/**
 * Prueba de renderizado de los block patterns.
 *
 * Los patterns llevan PHP dentro. WordPress los incluye capturando la salida,
 * asi que ese PHP se ejecuta en tiempo de registro. Esta prueba reproduce esa
 * inclusion y comprueba que la salida es marcado limpio: sin PHP sin ejecutar,
 * sin avisos, con las URL ya resueltas y con los bloques balanceados.
 *
 * El validador en Python no puede cubrir esto porque no ejecuta PHP.
 *
 * Uso: php tools/prueba-patterns.php <ruta-del-tema>
 */
define('ABSPATH', __DIR__);
$TEMA = $argv[1] ?? '.';
$RAIZ = 'https://ejemplo.test';

// --- Nucleo simulado, lo justo para que los patterns se incluyan ---
function home_url($ruta = '/') { return 'https://ejemplo.test' . $ruta; }
function esc_url($u) { return htmlspecialchars((string) $u, ENT_QUOTES, 'UTF-8'); }
function esc_html($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
function esc_attr($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
function __($t, $d = '') { return $t; }

// WooCommerce presente, con slugs en ingles como los que trae por defecto.
function wc_get_page_permalink($pagina) {
    $mapa = [
        'shop'      => 'https://ejemplo.test/shop/',
        'cart'      => 'https://ejemplo.test/cart/',
        'checkout'  => 'https://ejemplo.test/checkout/',
        'myaccount' => 'https://ejemplo.test/my-account/',
    ];
    return $mapa[$pagina] ?? '';
}

$fallos = 0;
function af($cond, $msg) {
    global $fallos;
    if ($cond) { echo "OK    $msg\n"; } else { echo "FALLA $msg\n"; $fallos++; }
}

$archivos = glob(rtrim($TEMA, '/\\') . '/patterns/*.php');
sort($archivos);

if (!$archivos) {
    echo "No se encontraron patterns en $TEMA/patterns/\n";
    exit(1);
}

foreach ($archivos as $ruta) {
    $nombre = basename($ruta);

    // Cualquier aviso o error se convierte en fallo visible, no en ruido.
    $avisos = [];
    set_error_handler(static function ($n, $m) use (&$avisos) { $avisos[] = $m; return true; });

    ob_start();
    include $ruta;
    $salida = ob_get_clean();

    restore_error_handler();

    af(empty($avisos), "$nombre se incluye sin avisos" . ($avisos ? ': ' . $avisos[0] : ''));
    af('' !== trim($salida), "$nombre produce salida");
    af(!str_contains($salida, '<?php'), "$nombre no deja PHP sin ejecutar");
    af(!str_contains($salida, '$cm_url'), "$nombre no deja variables sin resolver");

    // Los bloques abren y cierran balanceados tras ejecutarse el PHP.
    preg_match_all('/<!--\s+wp:([a-z0-9\/-]+)(\s+\{.*?\})?\s+(\/)?-->/s', $salida, $abre, PREG_SET_ORDER);
    preg_match_all('/<!--\s+\/wp:([a-z0-9\/-]+)\s+-->/', $salida, $cierra, PREG_SET_ORDER);
    $sin_cerrar = 0;
    foreach ($abre as $m) { if (empty($m[3])) { $sin_cerrar++; } }
    af($sin_cerrar === count($cierra),
       "$nombre balancea sus bloques: $sin_cerrar aperturas, " . count($cierra) . ' cierres');

    // Ninguna URL puede haber quedado apuntando a un slug inventado.
    preg_match_all('/href="([^"]*)"/', $salida, $enlaces);
    foreach ($enlaces[1] as $href) {
        if (str_starts_with($href, 'https://ejemplo.test/')) { continue; }
        // Las paginas legales todavia no existen: son de la Fase 6.
        $pendientes = ['/aviso-de-privacidad', '/terminos-de-uso',
                       '/politica-de-envios', '/declaracion-de-uso-previsto',
                       '/envios'];
        af(in_array($href, $pendientes, true),
           "$nombre: enlace $href es una pagina pendiente conocida o una URL resuelta");
    }
}

echo "\n" . ($fallos === 0 ? 'TODO OK' : "$fallos FALLOS") . "\n";
exit($fallos === 0 ? 0 : 1);
