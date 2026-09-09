<?php
/**
 * CoreMushroom - metadatos para buscadores y para compartir.
 *
 * El sitio no traia descripcion meta ni etiquetas Open Graph. Sin ellas, un
 * buscador inventa el resumen a partir del primer texto que encuentra, y al
 * compartir un enlace en WhatsApp o en redes no sale ni titulo ni imagen.
 *
 * Sin plugin de SEO: son unas cuantas etiquetas y las genera el tema.
 *
 * REGLA DE CUMPLIMIENTO: estas descripciones salen del contenido que escribe
 * el cliente. Lo que no se puede afirmar en una ficha de producto tampoco se
 * puede afirmar aqui. La descripcion se recorta pero no se reescribe, asi
 * que la responsabilidad sigue estando en el texto original.
 *
 * @package CoreMushroom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Longitud maxima de la descripcion, en caracteres. */
const COREMUSHROOM_DESCRIPCION_MAX = 155;

/**
 * Recorta un texto sin partir palabras.
 *
 * @param string $texto  Texto de origen.
 * @param int    $limite Maximo de caracteres.
 * @return string
 */
function coremushroom_recortar( $texto, $limite = COREMUSHROOM_DESCRIPCION_MAX ) {
	$texto = wp_strip_all_tags( (string) $texto, true );
	$texto = preg_replace( '/\s+/u', ' ', $texto );
	$texto = trim( (string) $texto );

	if ( '' === $texto ) {
		return '';
	}

	if ( mb_strlen( $texto ) <= $limite ) {
		return $texto;
	}

	$corte = mb_substr( $texto, 0, $limite );
	$hueco = mb_strrpos( $corte, ' ' );

	if ( false !== $hueco && $hueco > (int) ( $limite * 0.6 ) ) {
		$corte = mb_substr( $corte, 0, $hueco );
	}

	return rtrim( $corte, " ,.;:-" ) . '…';
}

/**
 * Devuelve la descripcion que corresponde a la pantalla actual.
 *
 * @return string
 */
function coremushroom_descripcion() {
	$texto = '';

	if ( is_front_page() ) {
		$texto = get_bloginfo( 'description' );
	} elseif ( function_exists( 'is_product' ) && is_product() ) {
		$producto = wc_get_product( get_queried_object_id() );

		if ( $producto ) {
			$texto = $producto->get_short_description();

			if ( '' === trim( (string) $texto ) ) {
				$texto = $producto->get_description();
			}
		}
	} elseif ( is_singular() ) {
		$texto = get_the_excerpt( get_queried_object_id() );

		if ( '' === trim( (string) $texto ) ) {
			$entrada = get_post( get_queried_object_id() );
			$texto   = $entrada ? $entrada->post_content : '';
		}
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$termino = get_queried_object();
		$texto   = $termino instanceof WP_Term ? $termino->description : '';
	}

	if ( '' === trim( (string) $texto ) ) {
		$texto = get_bloginfo( 'description' );
	}

	/**
	 * Permite cambiar la descripcion desde otro sitio.
	 *
	 * @param string $texto Descripcion antes de recortarse.
	 */
	$texto = apply_filters( 'coremushroom_descripcion', $texto );

	return coremushroom_recortar( $texto );
}

/**
 * Devuelve el titulo que corresponde a la pantalla actual.
 *
 * @return string
 */
function coremushroom_titulo() {
	if ( is_front_page() ) {
		return get_bloginfo( 'name' );
	}

	$titulo = wp_get_document_title();

	// wp_get_document_title ya incluye el nombre del sitio. Para compartir se
	// prefiere el titulo a secas, que es mas corto en una tarjeta.
	$sufijo = ' ' . trim( (string) apply_filters( 'document_title_separator', '-' ) ) . ' ' . get_bloginfo( 'name' );

	if ( str_ends_with( $titulo, $sufijo ) ) {
		$titulo = substr( $titulo, 0, - strlen( $sufijo ) );
	}

	return trim( $titulo );
}

/**
 * Devuelve la imagen para compartir.
 *
 * Se prefiere la destacada de la entrada. Si no hay, la captura del tema,
 * que existe siempre y lleva la marca.
 *
 * @return string URL, o cadena vacia.
 */
function coremushroom_imagen_compartir() {
	if ( is_singular() && has_post_thumbnail() ) {
		$url = get_the_post_thumbnail_url( get_queried_object_id(), 'large' );

		if ( $url ) {
			return $url;
		}
	}

	$captura = get_stylesheet_directory() . '/screenshot.png';

	if ( is_readable( $captura ) ) {
		return get_stylesheet_directory_uri() . '/screenshot.png';
	}

	return '';
}

/**
 * Imprime las etiquetas en la cabecera.
 *
 * Se respeta la casilla de disuadir a los buscadores: cuando esta marcada,
 * WordPress ya imprime noindex y aqui se agrega nofollow, para que ademas no
 * se sigan los enlaces del sitio de desarrollo.
 */
function coremushroom_metadatos() {
	$descripcion = coremushroom_descripcion();
	$titulo      = coremushroom_titulo();
	$imagen      = coremushroom_imagen_compartir();
	$url         = coremushroom_url_actual();

	if ( '' !== $descripcion ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $descripcion ) );
	}

	printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
	printf( '<meta property="og:locale" content="%s">' . "\n", esc_attr( get_locale() ) );
	printf(
		'<meta property="og:type" content="%s">' . "\n",
		esc_attr( is_singular() && ! is_front_page() ? 'article' : 'website' )
	);

	if ( '' !== $titulo ) {
		printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $titulo ) );
	}

	if ( '' !== $descripcion ) {
		printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $descripcion ) );
	}

	if ( '' !== $url ) {
		printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
	}

	if ( '' !== $imagen ) {
		printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $imagen ) );
		printf( '<meta name="twitter:card" content="%s">' . "\n", 'summary_large_image' );
	} else {
		printf( '<meta name="twitter:card" content="%s">' . "\n", 'summary' );
	}

	if ( ! get_option( 'blog_public' ) ) {
		// El sitio de desarrollo no debe indexarse ni repartir autoridad de
		// enlace. WordPress ya pone noindex; esto agrega nofollow.
		echo '<meta name="robots" content="noindex, nofollow">' . "\n";
	}
}
add_action( 'wp_head', 'coremushroom_metadatos', 5 );

/**
 * URL canonica de la pantalla actual.
 *
 * @return string
 */
function coremushroom_url_actual() {
	if ( is_front_page() ) {
		return home_url( '/' );
	}

	if ( is_singular() ) {
		return (string) get_permalink( get_queried_object_id() );
	}

	if ( is_tax() || is_category() || is_tag() ) {
		$enlace = get_term_link( get_queried_object() );

		return is_wp_error( $enlace ) ? '' : (string) $enlace;
	}

	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return (string) get_permalink( wc_get_page_id( 'shop' ) );
	}

	return '';
}

/**
 * Avisa en el panel si falta el nombre o la bajada del sitio.
 *
 * Sin bajada no hay descripcion meta que generar, porque no hay de donde
 * sacarla y este tema no inventa texto comercial. Sin nombre propio, lo que
 * sale al compartir un enlace es lo que WordPress puso al instalar.
 *
 * El aviso no se puede descartar a proposito: los dos datos afectan a lo que
 * ve cualquiera que comparta un enlace de la tienda.
 */
function coremushroom_avisar_identidad_sin_configurar() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$pantalla = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	// Solo en las pantallas donde el aviso es accionable.
	if ( $pantalla && ! in_array( $pantalla->id, array( 'dashboard', 'options-general', 'edit-page', 'edit-post' ), true ) ) {
		return;
	}

	$faltan = array();

	if ( '' === trim( (string) get_bloginfo( 'description' ) ) ) {
		$faltan[] = __( 'la descripción corta del sitio, de la que sale la descripción para buscadores', 'coremushroom' );
	}

	$nombre = trim( (string) get_bloginfo( 'name' ) );

	if ( '' === $nombre || 'core' === strtolower( $nombre ) ) {
		$faltan[] = __( 'el nombre del sitio, que hoy es el que puso WordPress al instalar', 'coremushroom' );
	}

	if ( ! $faltan ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p><strong>CoreMushroom:</strong> falta %1$s. Se configura en <a href="%2$s">Ajustes generales</a>.</p></div>',
		esc_html( implode( __( ', y también ', 'coremushroom' ), $faltan ) ),
		esc_url( admin_url( 'options-general.php' ) )
	);
}
add_action( 'admin_notices', 'coremushroom_avisar_identidad_sin_configurar' );

/**
 * Resuelve el destino del enlace antiguo de envios.
 *
 * La portada publicada conserva `/envios/` dentro de su contenido. Mantener
 * esta compatibilidad evita un 404 sin depender de volver a editar el bloque.
 *
 * @param string $solicitud URI solicitada por el navegador.
 * @param bool   $es_404    Si WordPress resolvio la solicitud como 404.
 * @return string URL de destino, o cadena vacia si no corresponde redirigir.
 */
function coremushroom_destino_envios( $solicitud, $es_404 ) {
	if ( ! $es_404 ) {
		return '';
	}

	$ruta = wp_parse_url( (string) $solicitud, PHP_URL_PATH );

	if ( ! is_string( $ruta ) || '/envios/' !== trailingslashit( $ruta ) ) {
		return '';
	}

	return home_url( '/politica-de-envios/' );
}

/**
 * Redirige `/envios/` a la pagina legal que ya existe.
 */
function coremushroom_redirigir_envios() {
	$destino = coremushroom_destino_envios(
		isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '',
		is_404()
	);

	if ( '' === $destino ) {
		return;
	}

	wp_safe_redirect( $destino, 301, 'CoreMushroom' );
	exit;
}
add_action( 'template_redirect', 'coremushroom_redirigir_envios' );
