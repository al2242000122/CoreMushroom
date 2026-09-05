<?php
/**
 * CoreMushroom - comprobante de transferencia.
 *
 * El cliente sube el comprobante de su transferencia SPEI y el dueno lo
 * verifica desde el panel antes de que el pedido pase a preparacion.
 *
 * Premisas, en orden de importancia:
 *
 *   1. Un comprobante bancario lleva nombre, banco y a veces numero de
 *      cuenta. Se guarda con nombre aleatorio, sin extension ejecutable, en
 *      un directorio cerrado, y solo se entrega a traves de PHP.
 *   2. Si el directorio no se puede blindar, NO se guarda nada. Vale mas
 *      perder una subida que dejar un documento bancario al aire.
 *   3. Los comprobantes no se sustituyen: se acumulan hasta un tope. La
 *      clave del pedido viaja en la URL de la pagina de gracias y puede
 *      filtrarse; con sustitucion, quien la tuviera podria borrar la prueba
 *      de pago del cliente subiendo un archivo cualquiera encima.
 *   4. El archivo lo sube un desconocido. El tipo no se deduce de la
 *      extension ni de lo que diga el navegador: se leen los bytes.
 *
 * @package CoreMushroom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Clave del metadato con la lista de comprobantes del pedido. */
const COREMUSHROOM_META_COMPROBANTES = '_coremushroom_comprobantes';

/** Tamano maximo por archivo, en bytes. */
const COREMUSHROOM_COMPROBANTE_MAX = 8388608; // 8 MB.

/** Cuantos comprobantes se conservan por pedido. */
const COREMUSHROOM_COMPROBANTES_TOPE = 5;

/** Segundos que hay que esperar entre una subida y la siguiente. */
const COREMUSHROOM_COMPROBANTE_ESPERA = 30;

/**
 * Tipos aceptados y su firma de bytes.
 *
 * La extension y el tipo que declara el navegador se ignoran a proposito:
 * los dos los controla quien sube el archivo. Lo unico que no puede falsear
 * sin romper el archivo son sus primeros bytes.
 *
 * @return array<string, array<string, string>>
 */
function coremushroom_tipos_comprobante() {
	return array(
		'jpg'  => array( 'firma' => "\xFF\xD8\xFF", 'mime' => 'image/jpeg' ),
		'png'  => array( 'firma' => "\x89PNG\r\n\x1A\n", 'mime' => 'image/png' ),
		'webp' => array( 'firma' => 'RIFF', 'mime' => 'image/webp' ),
		'pdf'  => array( 'firma' => '%PDF-', 'mime' => 'application/pdf' ),
	);
}

/**
 * Determina el tipo real de un archivo leyendo sus primeros bytes.
 *
 * @param string $ruta Ruta en disco.
 * @return string Clave del tipo, o cadena vacia si no es ninguno aceptado.
 */
function coremushroom_tipo_real( $ruta ) {
	if ( ! is_readable( $ruta ) ) {
		return '';
	}

	$manejador = fopen( $ruta, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions

	if ( ! $manejador ) {
		return '';
	}

	$cabecera = fread( $manejador, 16 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fclose( $manejador ); // phpcs:ignore WordPress.WP.AlternativeFunctions

	if ( ! is_string( $cabecera ) || '' === $cabecera ) {
		return '';
	}

	foreach ( coremushroom_tipos_comprobante() as $clave => $tipo ) {
		if ( 0 !== strpos( $cabecera, $tipo['firma'] ) ) {
			continue;
		}

		// RIFF tambien encabeza audio y video. El WebP lleva la marca WEBP
		// en el byte 8.
		if ( 'webp' === $clave && 'WEBP' !== substr( $cabecera, 8, 4 ) ) {
			continue;
		}

		return $clave;
	}

	return '';
}

/**
 * Contenido que debe tener el .htaccess del directorio.
 *
 * Se compara con el archivo existente en cada subida. Comprobar solo que el
 * archivo existe no basta: un restore o un gestor de archivos puede dejarlo
 * vacio, y entonces el directorio queda abierto sin que nadie se entere.
 *
 * @return string
 */
function coremushroom_reglas_comprobantes() {
	return "# Comprobantes bancarios. Nada aqui se sirve por HTTP.\n"
		. "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n"
		. "<IfModule !mod_authz_core.c>\n\tOrder allow,deny\n\tDeny from all\n</IfModule>\n"
		. "Options -Indexes -ExecCGI\n"
		. "RemoveHandler .php .phtml .phar .php3 .php4 .php5 .php7 .php8\n"
		. "php_flag engine off\n";
}

/**
 * Devuelve el directorio de comprobantes, blindado, o un error.
 *
 * Se prefiere una ruta fuera de la raiz web si el sitio la define. En
 * wp-config.php:
 *
 *     define( 'COREMUSHROOM_DIR_COMPROBANTES', '/home/usuario/comprobantes' );
 *
 * Sin esa constante se usa un directorio dentro de uploads, que es el unico
 * sitio donde WordPress garantiza permiso de escritura. Ahi el blindaje
 * depende del .htaccess, asi que se escribe, se verifica su contenido, y si
 * no se puede dejar en su sitio la funcion devuelve error y no se guarda
 * nada. Fallar cerrado, no abierto.
 *
 * @return string|WP_Error Ruta con barra final, o error.
 */
function coremushroom_dir_comprobantes() {
	if ( defined( 'COREMUSHROOM_DIR_COMPROBANTES' ) && COREMUSHROOM_DIR_COMPROBANTES ) {
		$dir      = trailingslashit( COREMUSHROOM_DIR_COMPROBANTES );
		$en_web   = false;
	} else {
		$uploads = wp_upload_dir();

		if ( ! empty( $uploads['error'] ) ) {
			// El mensaje de WordPress se registra pero no se le muestra al
			// visitante: confirmaria un fallo de disco o de cuota.
			coremushroom_registrar( 'wp_upload_dir: ' . $uploads['error'] );

			return new WP_Error(
				'directorio',
				__( 'No pudimos guardar tu comprobante en este momento. Escríbenos y lo resolvemos.', 'coremushroom' )
			);
		}

		$dir    = trailingslashit( $uploads['basedir'] ) . 'cm-comprobantes/';
		$en_web = true;
	}

	if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
		coremushroom_registrar( 'no se pudo crear ' . $dir );

		return new WP_Error(
			'directorio',
			__( 'No pudimos guardar tu comprobante en este momento. Escríbenos y lo resolvemos.', 'coremushroom' )
		);
	}

	if ( ! $en_web ) {
		return $dir;
	}

	// A partir de aqui, el directorio esta dentro de la raiz web y todo el
	// blindaje depende de estos dos archivos.
	$reglas   = coremushroom_reglas_comprobantes();
	$htaccess = $dir . '.htaccess';

	$actual = file_exists( $htaccess ) ? file_get_contents( $htaccess ) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions

	if ( $actual !== $reglas ) {
		$escrito = file_put_contents( $htaccess, $reglas ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		if ( false === $escrito || file_get_contents( $htaccess ) !== $reglas ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
			coremushroom_registrar( 'no se pudo blindar ' . $dir );

			return new WP_Error(
				'blindaje',
				__( 'No pudimos guardar tu comprobante de forma segura. Escríbenos y lo resolvemos.', 'coremushroom' )
			);
		}
	}

	$indice = $dir . 'index.php';

	if ( ! file_exists( $indice ) ) {
		file_put_contents( $indice, "<?php\n// Silencio.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}

	return $dir;
}

/**
 * Deja constancia de un fallo interno sin enseñarselo al visitante.
 *
 * @param string $mensaje Texto para el registro.
 */
function coremushroom_registrar( $mensaje ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'CoreMushroom comprobantes: ' . $mensaje ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
	}
}

/**
 * Comprueba si quien mira puede ver los comprobantes de un pedido.
 *
 * @param WC_Order $pedido Pedido.
 * @return bool
 */
function coremushroom_puede_ver_pedido( $pedido ) {
	if ( ! $pedido instanceof WC_Order ) {
		return false;
	}

	if ( current_user_can( 'edit_shop_orders' ) ) {
		return true;
	}

	$usuario = get_current_user_id();

	if ( $usuario > 0 && (int) $pedido->get_customer_id() === $usuario ) {
		return true;
	}

	// hash_equals para no filtrar informacion por el tiempo de comparacion.
	$clave = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	return '' !== $clave && hash_equals( (string) $pedido->get_order_key(), $clave );
}

/**
 * Indica si un pedido admite que se suba un comprobante.
 *
 * @param WC_Order $pedido Pedido.
 * @return bool
 */
function coremushroom_admite_comprobante( $pedido ) {
	if ( ! $pedido instanceof WC_Order ) {
		return false;
	}

	if ( 'coremushroom_spei' !== $pedido->get_payment_method() ) {
		return false;
	}

	// Una vez confirmado el pago ya no hace falta.
	return $pedido->has_status( 'cm-spei' );
}

/**
 * Devuelve la lista de comprobantes de un pedido.
 *
 * @param WC_Order $pedido Pedido.
 * @return array<int, array<string, mixed>>
 */
function coremushroom_comprobantes( $pedido ) {
	if ( ! $pedido instanceof WC_Order ) {
		return array();
	}

	$lista = $pedido->get_meta( COREMUSHROOM_META_COMPROBANTES );

	if ( ! is_array( $lista ) ) {
		return array();
	}

	// Se reindexa para que el indice que viaja en la URL de descarga sea
	// siempre 0, 1, 2 y no un hueco heredado de un borrado.
	return array_values( array_filter( $lista, 'is_array' ) );
}

/**
 * Resuelve la ruta en disco de un comprobante guardado.
 *
 * El nombre viene de los metadatos, no de la peticion, pero se valida igual.
 * Son 32 caracteres hexadecimales: cualquier otra cosa se rechaza.
 *
 * @param string $nombre Nombre del archivo.
 * @return string Ruta absoluta, o cadena vacia si el nombre no es aceptable.
 */
function coremushroom_ruta_comprobante( $nombre ) {
	if ( ! is_string( $nombre ) || ! preg_match( '/^[a-f0-9]{32}\.dat\z/', $nombre ) ) {
		return '';
	}

	$dir = coremushroom_dir_comprobantes();

	return is_wp_error( $dir ) ? '' : $dir . $nombre;
}

/**
 * URL de descarga de un comprobante.
 *
 * Lleva el ID del pedido y el indice dentro de su lista, nunca una ruta.
 *
 * @param WC_Order $pedido Pedido.
 * @param int      $indice Posicion en la lista.
 * @return string
 */
function coremushroom_url_comprobante( $pedido, $indice = 0 ) {
	return wp_nonce_url(
		add_query_arg(
			array(
				'action' => 'coremushroom_comprobante',
				'pedido' => $pedido->get_id(),
				'i'      => (int) $indice,
			),
			admin_url( 'admin-post.php' )
		),
		'coremushroom_comprobante_' . $pedido->get_id(),
		'cm_nonce'
	);
}

/**
 * Formulario de subida, para la pagina de gracias y la del pedido.
 *
 * @param int|WC_Order $pedido Pedido o su ID.
 */
function coremushroom_formulario_comprobante( $pedido ) {
	$pedido = is_numeric( $pedido ) ? wc_get_order( $pedido ) : $pedido;

	if ( ! coremushroom_admite_comprobante( $pedido ) || ! coremushroom_puede_ver_pedido( $pedido ) ) {
		return;
	}

	$lista = coremushroom_comprobantes( $pedido );

	echo '<section class="cm-comprobante">';
	echo '<h2 class="cm-comprobante__titulo">' . esc_html__( 'Tu comprobante', 'coremushroom' ) . '</h2>';

	if ( $lista ) {
		$ultimo = end( $lista );

		echo '<p class="cm-comprobante__estado">';
		printf(
			/* translators: %s: fecha y hora. */
			esc_html__( 'Recibimos tu comprobante el %s. Lo estamos revisando y te avisamos en cuanto confirmemos el depósito.', 'coremushroom' ),
			esc_html( wp_date( 'j \d\e F \d\e Y, H:i', (int) $ultimo['fecha'] ) )
		);
		echo '</p>';
	} else {
		echo '<p class="cm-comprobante__nota">' . esc_html__(
			'Cuando hayas hecho la transferencia, sube aquí la captura o el PDF que te da tu banco.',
			'coremushroom'
		) . '</p>';
	}

	if ( count( $lista ) >= COREMUSHROOM_COMPROBANTES_TOPE ) {
		echo '<p class="cm-comprobante__nota">' . esc_html__(
			'Ya recibimos varios archivos de este pedido. Si necesitas enviarnos otro, escríbenos.',
			'coremushroom'
		) . '</p></section>';
		return;
	}

	echo '<form method="post" enctype="multipart/form-data" class="cm-comprobante__form">';
	wp_nonce_field( 'coremushroom_subir_comprobante_' . $pedido->get_id(), 'cm_comprobante_nonce' );
	printf( '<input type="hidden" name="cm_pedido" value="%d">', (int) $pedido->get_id() );

	printf(
		'<input type="file" name="cm_comprobante" accept=".jpg,.jpeg,.png,.webp,.pdf" required class="cm-comprobante__archivo"> '
		. '<button type="submit" class="cm-btn cm-btn--primario">%s</button>',
		esc_html__( 'Enviar comprobante', 'coremushroom' )
	);

	echo '<p class="cm-comprobante__nota">' . esc_html__(
		'Aceptamos imagen o PDF, hasta 8 MB. Tu comprobante no queda visible para nadie más.',
		'coremushroom'
	) . '</p>';

	echo '</form></section>';
}
add_action( 'woocommerce_thankyou_coremushroom_spei', 'coremushroom_formulario_comprobante', 25 );
add_action( 'woocommerce_view_order', 'coremushroom_formulario_comprobante', 25 );

/**
 * Procesa la subida del comprobante.
 */
function coremushroom_procesar_comprobante() {
	if ( empty( $_POST['cm_pedido'] ) || empty( $_POST['cm_comprobante_nonce'] ) ) {
		return;
	}

	$pedido_id = absint( wp_unslash( $_POST['cm_pedido'] ) );
	$nonce     = sanitize_text_field( wp_unslash( $_POST['cm_comprobante_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'coremushroom_subir_comprobante_' . $pedido_id ) ) {
		wc_add_notice( __( 'La sesión expiró. Vuelve a cargar la página e inténtalo de nuevo.', 'coremushroom' ), 'error' );
		return;
	}

	$pedido = wc_get_order( $pedido_id );

	if ( ! coremushroom_admite_comprobante( $pedido ) || ! coremushroom_puede_ver_pedido( $pedido ) ) {
		wc_add_notice( __( 'No se pudo asociar el comprobante a un pedido válido.', 'coremushroom' ), 'error' );
		return;
	}

	$resultado = coremushroom_guardar_comprobante(
		$pedido,
		isset( $_FILES['cm_comprobante'] ) ? $_FILES['cm_comprobante'] : array() // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	);

	if ( is_wp_error( $resultado ) ) {
		wc_add_notice( $resultado->get_error_message(), 'error' );
		return;
	}

	wc_add_notice(
		__( 'Recibimos tu comprobante. Te avisamos en cuanto confirmemos el depósito.', 'coremushroom' ),
		'success'
	);

	// Patron POST y redireccion: sin esto, recargar la pagina reenvia el
	// archivo y cada reenvio deja una nota mas en el pedido.
	wp_safe_redirect( remove_query_arg( 'cm_enviado' ) );
	exit;
}
add_action( 'template_redirect', 'coremushroom_procesar_comprobante' );

/**
 * Valida y guarda el archivo subido.
 *
 * @param WC_Order             $pedido  Pedido.
 * @param array<string, mixed> $archivo Entrada de $_FILES.
 * @return true|WP_Error
 */
function coremushroom_guardar_comprobante( $pedido, $archivo ) {
	if ( empty( $archivo['tmp_name'] ) || ! isset( $archivo['error'] ) ) {
		return new WP_Error( 'sin_archivo', __( 'No llegó ningún archivo.', 'coremushroom' ) );
	}

	if ( UPLOAD_ERR_OK !== (int) $archivo['error'] ) {
		if ( in_array( (int) $archivo['error'], array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true ) ) {
			return new WP_Error( 'muy_grande', __( 'El archivo pesa demasiado. El máximo son 8 MB.', 'coremushroom' ) );
		}

		return new WP_Error( 'subida', __( 'La subida falló. Inténtalo otra vez.', 'coremushroom' ) );
	}

	// Sin esto, un atacante podria pasar una ruta del servidor en tmp_name.
	if ( ! is_uploaded_file( $archivo['tmp_name'] ) ) {
		return new WP_Error( 'no_subido', __( 'El archivo no llegó por una subida válida.', 'coremushroom' ) );
	}

	$lista = coremushroom_comprobantes( $pedido );

	if ( count( $lista ) >= COREMUSHROOM_COMPROBANTES_TOPE ) {
		return new WP_Error(
			'tope',
			__( 'Ya recibimos varios archivos de este pedido. Si necesitas enviarnos otro, escríbenos.', 'coremushroom' )
		);
	}

	// Espera entre subidas. Frena que alguien con la clave del pedido llene
	// el disco a base de peticiones seguidas.
	if ( $lista ) {
		$ultimo = end( $lista );

		if ( ( time() - (int) $ultimo['fecha'] ) < COREMUSHROOM_COMPROBANTE_ESPERA ) {
			return new WP_Error(
				'espera',
				__( 'Espera un momento antes de enviar otro archivo.', 'coremushroom' )
			);
		}
	}

	$tamano = (int) filesize( $archivo['tmp_name'] );

	if ( $tamano <= 0 ) {
		return new WP_Error( 'vacio', __( 'El archivo llegó vacío.', 'coremushroom' ) );
	}

	if ( $tamano > COREMUSHROOM_COMPROBANTE_MAX ) {
		return new WP_Error( 'muy_grande', __( 'El archivo pesa demasiado. El máximo son 8 MB.', 'coremushroom' ) );
	}

	$tipo = coremushroom_tipo_real( $archivo['tmp_name'] );

	if ( '' === $tipo ) {
		return new WP_Error(
			'tipo',
			__( 'Solo aceptamos imagen JPG, PNG o WEBP, o un PDF. Sube la captura o el archivo que te da tu banco.', 'coremushroom' )
		);
	}

	$dir = coremushroom_dir_comprobantes();

	if ( is_wp_error( $dir ) ) {
		return $dir;
	}

	// Nombre aleatorio con random_bytes, no con wp_generate_password: ese
	// pasa por un filtro publico que cualquier plugin puede cambiar, y un
	// nombre con caracteres raros dejaria el archivo imposible de leer y de
	// borrar por la validacion de mas abajo.
	$nombre  = bin2hex( random_bytes( 16 ) ) . '.dat';
	$destino = $dir . $nombre;

	// umask para que el archivo no exista ni un instante con permisos de
	// lectura para todo el sistema.
	$anterior = umask( 0077 );
	$movido   = move_uploaded_file( $archivo['tmp_name'], $destino );
	umask( $anterior );

	if ( ! $movido ) {
		coremushroom_registrar( 'no se pudo mover la subida a ' . $destino );

		return new WP_Error( 'mover', __( 'No se pudo guardar el archivo. Inténtalo otra vez.', 'coremushroom' ) );
	}

	@chmod( $destino, 0600 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

	// Los comprobantes se acumulan, no se sustituyen. La clave del pedido
	// viaja en la URL y puede filtrarse; si el ultimo archivo pisara al
	// anterior, quien tuviera esa clave podria borrar la prueba de pago real
	// del cliente subiendo cualquier cosa encima.
	$lista[] = array(
		'archivo' => $nombre,
		'tipo'    => $tipo,
		'fecha'   => time(),
		'bytes'   => $tamano,
	);

	$pedido->update_meta_data( COREMUSHROOM_META_COMPROBANTES, $lista );
	$pedido->add_order_note(
		sprintf(
			/* translators: %d: numero de archivo. */
			__( 'El cliente subió un comprobante de transferencia (archivo %d).', 'coremushroom' ),
			count( $lista )
		)
	);
	$pedido->save();

	return true;
}

/**
 * Borra del disco todos los comprobantes de un pedido.
 *
 * @param WC_Order $pedido Pedido.
 */
function coremushroom_borrar_comprobantes( $pedido ) {
	foreach ( coremushroom_comprobantes( $pedido ) as $entrada ) {
		$ruta = coremushroom_ruta_comprobante( isset( $entrada['archivo'] ) ? $entrada['archivo'] : '' );

		if ( '' !== $ruta && file_exists( $ruta ) ) {
			wp_delete_file( $ruta );
		}
	}
}

/**
 * Al borrar un pedido se borran sus comprobantes.
 *
 * Sin esto quedarian documentos bancarios en el disco para siempre, sin nada
 * que apunte a ellos, lo que ademas es un problema de conservacion de datos
 * personales.
 *
 * @param int $post_id ID del pedido o entrada.
 */
function coremushroom_borrar_al_eliminar( $post_id ) {
	$pedido = wc_get_order( $post_id );

	if ( $pedido ) {
		coremushroom_borrar_comprobantes( $pedido );
	}
}
add_action( 'before_delete_post', 'coremushroom_borrar_al_eliminar' );
add_action( 'woocommerce_before_delete_order', 'coremushroom_borrar_al_eliminar' );

/**
 * Entrega un comprobante al navegador.
 *
 * Nunca recibe una ruta: recibe el ID del pedido y un indice, comprueba el
 * permiso, y resuelve la ruta desde los metadatos.
 */
function coremushroom_descargar_comprobante() {
	$pedido_id = isset( $_GET['pedido'] ) ? absint( wp_unslash( $_GET['pedido'] ) ) : 0;
	$indice    = isset( $_GET['i'] ) ? absint( wp_unslash( $_GET['i'] ) ) : 0;
	$nonce     = isset( $_GET['cm_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['cm_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'coremushroom_comprobante_' . $pedido_id ) ) {
		wp_die( esc_html__( 'El enlace expiró.', 'coremushroom' ), 403 );
	}

	$pedido = wc_get_order( $pedido_id );

	if ( ! $pedido || ! coremushroom_puede_ver_pedido( $pedido ) ) {
		wp_die( esc_html__( 'No tienes permiso para ver este comprobante.', 'coremushroom' ), 403 );
	}

	$lista = coremushroom_comprobantes( $pedido );

	if ( ! isset( $lista[ $indice ] ) ) {
		wp_die( esc_html__( 'Ese comprobante no existe.', 'coremushroom' ), 404 );
	}

	$entrada = $lista[ $indice ];
	$ruta    = coremushroom_ruta_comprobante( isset( $entrada['archivo'] ) ? $entrada['archivo'] : '' );

	if ( '' === $ruta || ! is_readable( $ruta ) ) {
		wp_die( esc_html__( 'Ese comprobante no existe.', 'coremushroom' ), 404 );
	}

	$tipos = coremushroom_tipos_comprobante();
	$clave = isset( $entrada['tipo'] ) ? (string) $entrada['tipo'] : '';
	$mime  = isset( $tipos[ $clave ]['mime'] ) ? $tipos[ $clave ]['mime'] : 'application/octet-stream';

	// Se vacian los bufferes antes de mandar Content-Length. Con compresion
	// activa, la longitud declarada no coincidiria con lo enviado y el
	// archivo llegaria truncado.
	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}

	nocache_headers();
	header( 'Content-Type: ' . $mime );
	header( 'Content-Length: ' . filesize( $ruta ) );
	header( 'X-Content-Type-Options: nosniff' );
	header( "Content-Security-Policy: default-src 'none'; sandbox" );
	header( sprintf(
		'Content-Disposition: attachment; filename="comprobante-%s-%d.%s"',
		sanitize_file_name( $pedido->get_order_number() ),
		$indice + 1,
		'' !== $clave ? $clave : 'dat'
	) );

	readfile( $ruta ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}
// Solo para sesion iniciada. El enlace de descarga se emite unicamente en el
// panel, asi que registrar tambien la version anonima seria anunciar una
// entrada sin uso a un lector de documentos bancarios.
add_action( 'admin_post_coremushroom_comprobante', 'coremushroom_descargar_comprobante' );

/**
 * Muestra los comprobantes y el boton de confirmar en el pedido del panel.
 *
 * @param WC_Order $pedido Pedido.
 */
function coremushroom_panel_comprobante( $pedido ) {
	if ( 'coremushroom_spei' !== $pedido->get_payment_method() ) {
		return;
	}

	echo '<div class="order_data_column"><h3>' . esc_html__( 'Comprobante de transferencia', 'coremushroom' ) . '</h3>';

	$lista = coremushroom_comprobantes( $pedido );

	if ( ! $lista ) {
		echo '<p>' . esc_html__( 'El cliente todavía no ha subido su comprobante.', 'coremushroom' ) . '</p></div>';
		return;
	}

	if ( count( $lista ) > 1 ) {
		echo '<p>' . esc_html__(
			'Hay más de un archivo. Se conservan todos: el primero suele ser el bueno.',
			'coremushroom'
		) . '</p>';
	}

	echo '<ol>';

	foreach ( $lista as $indice => $entrada ) {
		printf(
			'<li><a href="%1$s" class="button">%2$s</a> <span>%3$s</span></li>',
			esc_url( coremushroom_url_comprobante( $pedido, $indice ) ),
			esc_html__( 'Descargar', 'coremushroom' ),
			esc_html( wp_date( 'j \d\e F \d\e Y, H:i', (int) $entrada['fecha'] ) )
		);
	}

	echo '</ol>';

	if ( $pedido->has_status( 'cm-spei' ) ) {
		printf(
			'<p><a href="%1$s" class="button button-primary">%2$s</a></p>',
			esc_url(
				wp_nonce_url(
					add_query_arg(
						array(
							'action' => 'coremushroom_confirmar_pago',
							'pedido' => $pedido->get_id(),
						),
						admin_url( 'admin-post.php' )
					),
					'coremushroom_confirmar_' . $pedido->get_id(),
					'cm_nonce'
				)
			),
			esc_html__( 'Confirmar pago y preparar pedido', 'coremushroom' )
		);
	}

	echo '</div>';
}
add_action( 'woocommerce_admin_order_data_after_billing_address', 'coremushroom_panel_comprobante', 20 );

/**
 * Confirma el pago y pasa el pedido a preparacion.
 */
function coremushroom_confirmar_pago() {
	$pedido_id = isset( $_GET['pedido'] ) ? absint( wp_unslash( $_GET['pedido'] ) ) : 0;
	$nonce     = isset( $_GET['cm_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['cm_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'coremushroom_confirmar_' . $pedido_id ) ) {
		wp_die( esc_html__( 'El enlace expiró.', 'coremushroom' ), 403 );
	}

	// Confirmar un pago mueve estado e inventario. Solo quien puede editar
	// pedidos, nunca el cliente aunque sea el dueno del pedido.
	if ( ! current_user_can( 'edit_shop_orders' ) ) {
		wp_die( esc_html__( 'No tienes permiso para confirmar pagos.', 'coremushroom' ), 403 );
	}

	$pedido = wc_get_order( $pedido_id );

	if ( ! $pedido || ! $pedido->has_status( 'cm-spei' ) ) {
		wp_die( esc_html__( 'Este pedido no está esperando comprobante.', 'coremushroom' ), 400 );
	}

	$pedido->payment_complete();
	$pedido->add_order_note(
		sprintf(
			/* translators: %s: nombre de quien confirma. */
			__( '%s verificó el comprobante y confirmó el pago.', 'coremushroom' ),
			wp_get_current_user()->display_name
		),
		false,
		true
	);

	wp_safe_redirect( $pedido->get_edit_order_url() );
	exit;
}
add_action( 'admin_post_coremushroom_confirmar_pago', 'coremushroom_confirmar_pago' );
