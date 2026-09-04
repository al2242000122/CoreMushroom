<?php
/**
 * CoreMushroom - funciones del tema hijo.
 *
 * Tema padre: Blocksy. Nunca se edita el padre.
 * Todo lo que se pueda declarar en codigo se declara aqui o en theme.json.
 *
 * @package CoreMushroom
 */

// Corta la ejecucion si el archivo se abre directamente por URL.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Version del tema hijo.
 * Solo se usa como respaldo cuando un archivo de assets no existe en disco.
 */
if ( ! defined( 'COREMUSHROOM_VERSION' ) ) {
	define( 'COREMUSHROOM_VERSION', '0.1.0' );
}

/**
 * Devuelve la version de un asset a partir de su fecha de modificacion.
 *
 * Con esto el navegador vuelve a descargar el CSS cada vez que cambia
 * el archivo, sin tener que subir el numero de version a mano en cada
 * despliegue por Git. Si el archivo no existe se devuelve la version
 * del tema para no romper la URL encolada.
 *
 * @param string $ruta_relativa Ruta dentro del tema hijo, por ejemplo 'assets/css/base.css'.
 * @return string Cadena de version para wp_enqueue_style().
 */
function coremushroom_version_asset( $ruta_relativa ) {
	// Guarda de tipo. realpath() lanza ValueError con un byte nulo y ltrim()
	// lanza TypeError con un array, y ambos serian errores fatales dentro de
	// wp_enqueue_scripts, es decir pantalla en blanco. Se falla en silencio.
	if ( ! is_string( $ruta_relativa ) || '' === $ruta_relativa || false !== strpos( $ruta_relativa, chr( 0 ) ) ) {
		return COREMUSHROOM_VERSION;
	}

	$directorio = get_stylesheet_directory();
	$candidata  = $directorio . '/' . ltrim( $ruta_relativa, '/' );

	// Contencion de ruta. ltrim solo quita barras iniciales, no impide un
	// '../' en medio. realpath resuelve los saltos y comprobamos que el
	// resultado siga dentro del tema hijo. Hoy todas las llamadas usan
	// literales, pero la funcion es publica y esto la deja segura si algun
	// dia alguien le pasa un valor que venga de fuera.
	$real = realpath( $candidata );
	$raiz = realpath( $directorio );

	if ( false === $real || false === $raiz || 0 !== strpos( $real, $raiz . DIRECTORY_SEPARATOR ) ) {
		return COREMUSHROOM_VERSION;
	}

	// Una sola llamada al sistema de archivos en vez de is_readable seguido
	// de filemtime. Entre las dos llamadas cabe un git pull del webhook de
	// despliegue, y ese hueco produce un warning de PHP en mitad de wp_head.
	$marca = @filemtime( $real );

	return false === $marca ? COREMUSHROOM_VERSION : (string) $marca;
}

/**
 * Declara lo que soporta el tema hijo.
 *
 * Blocksy ya declara casi todo esto, pero lo repetimos de forma explicita
 * para que el tema hijo siga funcionando aunque el padre cambie de criterio.
 * add_theme_support() es idempotente, repetirlo no causa efectos secundarios.
 */
function coremushroom_soporte_tema() {
	// Traducciones propias del hijo, si algun dia se agregan en /languages.
	load_child_theme_textdomain( 'coremushroom', get_stylesheet_directory() . '/languages' );

	// El editor de bloques debe cargar nuestras hojas de estilo.
	add_theme_support( 'editor-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );

	// WooCommerce y su galeria de producto.
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	// Mismas hojas en el editor que en el frente, para que lo que se ve
	// al editar sea lo que se publica. Las rutas son relativas al tema hijo.
	add_editor_style(
		array(
			'assets/css/fonts.css',
			'assets/css/base.css',
		)
	);
}
add_action( 'after_setup_theme', 'coremushroom_soporte_tema' );

/**
 * Encola las hojas de estilo del frente.
 *
 * Orden de carga:
 *   1. fonts.css  - declaraciones @font-face de las fuentes autoalojadas
 *   2. base.css   - sistema de diseno, depende de las fuentes y de Blocksy
 *   3. style.css  - cabecera del tema y parches de ultimo recurso
 *
 * Blocksy registra su hoja principal con el handle 'ct-main-styles'. Si
 * esta encolada, base.css declara depender de ella para cargarse despues y
 * poder sobrescribirla. La prioridad 100 existe para que Blocksy ya haya
 * corrido cuando preguntamos: si preguntaramos antes, la respuesta seria no
 * y perderiamos la dependencia sin que nada avisara. Si Blocksy cambia ese handle en el futuro, la
 * condicion simplemente no aplica y base.css se encola igual.
 */
function coremushroom_encolar_estilos() {
	$dependencias_base = array( 'coremushroom-fuentes' );

	// Se comprueba 'enqueued', no 'registered'. Si Blocksy solo registro la
	// hoja pero decidio no encolarla en esta peticion, declararla como
	// dependencia obligaria a WordPress a imprimirla de todos modos y
	// estariamos cargando CSS que el padre omitio a proposito.
	if ( wp_style_is( 'ct-main-styles', 'enqueued' ) ) {
		$dependencias_base[] = 'ct-main-styles';
	}

	wp_enqueue_style(
		'coremushroom-fuentes',
		get_stylesheet_directory_uri() . '/assets/css/fonts.css',
		array(),
		coremushroom_version_asset( 'assets/css/fonts.css' )
	);

	wp_enqueue_style(
		'coremushroom-base',
		get_stylesheet_directory_uri() . '/assets/css/base.css',
		$dependencias_base,
		coremushroom_version_asset( 'assets/css/base.css' )
	);

	wp_enqueue_style(
		'coremushroom-style',
		get_stylesheet_uri(),
		array( 'coremushroom-base' ),
		coremushroom_version_asset( 'style.css' )
	);
}
add_action( 'wp_enqueue_scripts', 'coremushroom_encolar_estilos', 100 );

/**
 * Precarga los dos archivos de fuente que se usan en el primer pintado.
 *
 * Solo se precargan los subconjuntos latinos. El latin-ext se descarga
 * bajo demanda gracias al unicode-range declarado en fonts.css, asi que
 * precargarlo seria desperdiciar ancho de banda en la mayoria de visitas.
 *
 * El atributo crossorigin es obligatorio aunque la fuente sea del mismo
 * dominio: sin el, el navegador descarga el archivo dos veces.
 */
function coremushroom_precargar_fuentes() {
	$fuentes = array(
		'fraunces-latin.woff2',
		'figtree-latin.woff2',
	);

	foreach ( $fuentes as $fuente ) {
		if ( ! is_readable( get_stylesheet_directory() . '/assets/fonts/' . $fuente ) ) {
			continue;
		}

		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( get_stylesheet_directory_uri() . '/assets/fonts/' . $fuente )
		);
	}
}
add_action( 'wp_head', 'coremushroom_precargar_fuentes', 1 );
