<?php
/**
 * CoreMushroom - campos de lote por producto.
 *
 * Define los campos, dibuja la caja en el editor de producto y guarda.
 * Sin plugin de campos personalizados: la definicion vive aqui, en el
 * repositorio, no en la base de datos.
 *
 * La funcion coremushroom_campos_lote() es la fuente unica. El formulario,
 * el guardado y la tabla de la ficha leen de ella. Para agregar un campo,
 * se agrega ahi y los tres se enteran solos.
 *
 * @package CoreMushroom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prefijo de las claves de metadatos.
 *
 * Empieza con guion bajo a proposito: asi WordPress las trata como privadas
 * y no aparecen en la caja generica de campos personalizados, donde
 * cualquiera podria editarlas sin validacion.
 */
const COREMUSHROOM_META_PREFIJO = '_coremushroom_';

/**
 * Definicion de los campos de lote.
 *
 * Cada campo declara:
 *   etiqueta  Lo que ve el administrador y lo que sale en la tabla publica.
 *   tipo      text, textarea, select, date o url.
 *   opciones  Solo para select. El valor guardado tiene que ser una de estas
 *             claves; cualquier otra cosa se descarta al guardar.
 *   ayuda     Texto breve bajo el campo en el editor. Opcional.
 *
 * @return array<string, array<string, mixed>>
 */
function coremushroom_campos_lote() {
	return array(
		'lote'               => array(
			'etiqueta' => __( 'Código de lote', 'coremushroom' ),
			'tipo'     => 'text',
			'ayuda'    => __( 'Ejemplo: CM-2609-C4', 'coremushroom' ),
		),
		'especie'            => array(
			'etiqueta' => __( 'Especie', 'coremushroom' ),
			'tipo'     => 'select',
			'opciones' => array(
				'cordyceps' => __( 'Cordyceps', 'coremushroom' ),
				'hericium'  => __( 'Hericium', 'coremushroom' ),
				'trametes'  => __( 'Trametes', 'coremushroom' ),
			),
		),
		'formato'            => array(
			'etiqueta' => __( 'Formato', 'coremushroom' ),
			'tipo'     => 'select',
			'opciones' => array(
				'chocolate' => __( 'Chocolate', 'coremushroom' ),
				'tisana'    => __( 'Tisana', 'coremushroom' ),
				'capsula'   => __( 'Cápsula', 'coremushroom' ),
			),
		),
		'contenido_neto'     => array(
			'etiqueta' => __( 'Contenido neto', 'coremushroom' ),
			'tipo'     => 'text',
			'ayuda'    => __( 'Ejemplo: 60 g, 12 piezas', 'coremushroom' ),
		),
		'ingredientes'       => array(
			'etiqueta' => __( 'Ingredientes', 'coremushroom' ),
			'tipo'     => 'textarea',
			'ayuda'    => __( 'En orden decreciente de cantidad, como en la etiqueta física.', 'coremushroom' ),
		),
		'extracto_por_pieza' => array(
			'etiqueta' => __( 'Extracto por pieza', 'coremushroom' ),
			'tipo'     => 'text',
			'ayuda'    => __( 'Ejemplo: 500 mg. Es cuanto extracto lleva cada pieza.', 'coremushroom' ),
		),
		'alergenos'          => array(
			'etiqueta' => __( 'Alérgenos', 'coremushroom' ),
			'tipo'     => 'textarea',
		),
		'fecha_elaboracion'  => array(
			'etiqueta' => __( 'Fecha de elaboración', 'coremushroom' ),
			'tipo'     => 'date',
		),
		'consumo_preferente' => array(
			'etiqueta' => __( 'Consumir preferentemente antes de', 'coremushroom' ),
			'tipo'     => 'date',
		),
		'certificado_url'    => array(
			'etiqueta' => __( 'Certificado de análisis', 'coremushroom' ),
			'tipo'     => 'url',
			'ayuda'    => __( 'Enlace al PDF. Se deja vacío si todavía no existe.', 'coremushroom' ),
		),
	);
}

/**
 * Lee un campo de lote ya saneado para mostrarlo.
 *
 * Devuelve cadena vacia si no hay valor, para que quien renderiza solo tenga
 * que preguntar si esta vacio.
 *
 * @param int    $producto_id ID del producto.
 * @param string $clave       Clave del campo, sin prefijo.
 * @return string
 */
function coremushroom_obtener_lote( $producto_id, $clave ) {
	$campos = coremushroom_campos_lote();

	if ( ! isset( $campos[ $clave ] ) ) {
		return '';
	}

	$valor = get_post_meta( (int) $producto_id, COREMUSHROOM_META_PREFIJO . $clave, true );

	if ( ! is_string( $valor ) ) {
		return '';
	}

	return trim( $valor );
}

/**
 * Devuelve la etiqueta legible de un campo de tipo select.
 *
 * En la base de datos se guarda la clave, por ejemplo 'cordyceps'. Al
 * mostrarla hay que traducirla a 'Cordyceps'. Si el valor guardado ya no
 * corresponde a ninguna opcion, por ejemplo porque se retiro una especie del
 * catalogo, se devuelve cadena vacia en vez de imprimir la clave cruda.
 *
 * @param string $clave Clave del campo.
 * @param string $valor Valor guardado.
 * @return string
 */
function coremushroom_etiqueta_opcion( $clave, $valor ) {
	$campos = coremushroom_campos_lote();

	if ( empty( $campos[ $clave ]['opciones'][ $valor ] ) ) {
		return '';
	}

	return $campos[ $clave ]['opciones'][ $valor ];
}

/**
 * Registra la caja de metadatos en el editor de producto.
 */
function coremushroom_registrar_caja_lote() {
	add_meta_box(
		'coremushroom-lote',
		__( 'Ficha de lote', 'coremushroom' ),
		'coremushroom_dibujar_caja_lote',
		'product',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes_product', 'coremushroom_registrar_caja_lote' );

/**
 * Dibuja el formulario de la caja de lote.
 *
 * @param WP_Post $post Producto que se esta editando.
 */
function coremushroom_dibujar_caja_lote( $post ) {
	wp_nonce_field( 'coremushroom_guardar_lote_' . $post->ID, 'coremushroom_lote_nonce' );

	echo '<p class="description">';
	esc_html_e(
		'Estos datos describen lo que contiene el producto. No escribas aquí efectos, beneficios, indicaciones ni resultados.',
		'coremushroom'
	);
	echo '</p>';

	echo '<table class="form-table" role="presentation"><tbody>';

	foreach ( coremushroom_campos_lote() as $clave => $campo ) {
		$id     = COREMUSHROOM_META_PREFIJO . $clave;
		$valor  = coremushroom_obtener_lote( $post->ID, $clave );
		$titulo = $campo['etiqueta'];

		echo '<tr>';
		printf(
			'<th scope="row"><label for="%1$s">%2$s</label></th>',
			esc_attr( $id ),
			esc_html( $titulo )
		);
		echo '<td>';

		switch ( $campo['tipo'] ) {
			case 'textarea':
				printf(
					'<textarea id="%1$s" name="%1$s" rows="3" class="large-text">%2$s</textarea>',
					esc_attr( $id ),
					esc_textarea( $valor )
				);
				break;

			case 'select':
				printf( '<select id="%1$s" name="%1$s">', esc_attr( $id ) );
				printf(
					'<option value="">%s</option>',
					esc_html__( '— Sin especificar —', 'coremushroom' )
				);
				foreach ( $campo['opciones'] as $opcion => $texto ) {
					printf(
						'<option value="%1$s"%2$s>%3$s</option>',
						esc_attr( $opcion ),
						selected( $valor, $opcion, false ),
						esc_html( $texto )
					);
				}
				echo '</select>';
				break;

			case 'date':
				printf(
					'<input type="date" id="%1$s" name="%1$s" value="%2$s" class="regular-text">',
					esc_attr( $id ),
					esc_attr( $valor )
				);
				break;

			case 'url':
				printf(
					'<input type="url" id="%1$s" name="%1$s" value="%2$s" class="large-text" placeholder="https://">',
					esc_attr( $id ),
					esc_attr( $valor )
				);
				break;

			default:
				printf(
					'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text">',
					esc_attr( $id ),
					esc_attr( $valor )
				);
				break;
		}

		if ( ! empty( $campo['ayuda'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $campo['ayuda'] ) );
		}

		echo '</td></tr>';
	}

	echo '</tbody></table>';
}

/**
 * Guarda los campos de lote.
 *
 * El orden de las comprobaciones importa. Se sale antes de tocar nada si:
 *   1. Es un autoguardado. WordPress dispara save_post sin los campos del
 *      formulario y guardarlos borraria todo.
 *   2. No es un producto.
 *   3. Falta el nonce o no valida.
 *   4. El usuario no puede editar este producto en concreto.
 *
 * Un campo vacio borra el metadato en vez de guardar cadena vacia, para que
 * la tabla publica no tenga que distinguir entre ausente y vacio.
 *
 * @param int     $post_id ID del producto.
 * @param WP_Post $post    Objeto del producto.
 */
function coremushroom_guardar_lote( $post_id, $post ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! $post instanceof WP_Post || 'product' !== $post->post_type ) {
		return;
	}

	if ( ! isset( $_POST['coremushroom_lote_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['coremushroom_lote_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'coremushroom_guardar_lote_' . $post_id ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( coremushroom_campos_lote() as $clave => $campo ) {
		$id = COREMUSHROOM_META_PREFIJO . $clave;

		if ( ! isset( $_POST[ $id ] ) ) {
			continue;
		}

		$crudo  = wp_unslash( $_POST[ $id ] );
		$limpio = '';

		if ( is_string( $crudo ) ) {
			switch ( $campo['tipo'] ) {
				case 'textarea':
					$limpio = sanitize_textarea_field( $crudo );
					break;

				case 'select':
					// Solo se acepta una de las opciones declaradas. Cualquier
					// otro valor, venga de donde venga, se descarta.
					$limpio = isset( $campo['opciones'][ $crudo ] ) ? $crudo : '';
					break;

				case 'date':
					// Formato ISO estricto y fecha real de calendario: asi
					// '2026-02-31' no pasa aunque encaje con el patron.
					// Se ancla con \z, no con $. En PCRE el $ tambien casa justo
					// antes de un salto de linea final, asi que una fecha con un
					// salto pegado al final pasaria el patron y se guardaria con
					// el salto incluido.
					$limpio = '';
					if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})\z/', $crudo, $partes )
						&& checkdate( (int) $partes[2], (int) $partes[3], (int) $partes[1] ) ) {
						$limpio = $crudo;
					}
					break;

				case 'url':
					// esc_url_raw solo rechaza esquemas prohibidos. Una URL sin
					// esquema como //otrodominio.tld o /wp-admin/algo no lleva
					// ninguno, asi que pasa entera. Se exige http o https de
					// forma explicita antes de aceptarla.
					$limpio  = esc_url_raw( $crudo, array( 'http', 'https' ) );
					$esquema = strtolower( (string) wp_parse_url( $limpio, PHP_URL_SCHEME ) );

					if ( ! in_array( $esquema, array( 'http', 'https' ), true ) ) {
						$limpio = '';
					}
					break;

				default:
					$limpio = sanitize_text_field( $crudo );
					break;
			}
		}

		// Tope de longitud. meta_value es longtext, asi que sin esto un valor
		// de varios megabytes se guardaria y se imprimiria en cada tarjeta del
		// catalogo. Los limites son holgados para el uso real de estos campos.
		$tope   = ( 'textarea' === $campo['tipo'] ) ? 2000 : 300;
		$limpio = mb_substr( $limpio, 0, $tope );

		if ( '' === $limpio ) {
			delete_post_meta( $post_id, $id );
			continue;
		}

		update_post_meta( $post_id, $id, $limpio );
	}
}
add_action( 'save_post_product', 'coremushroom_guardar_lote', 10, 2 );
