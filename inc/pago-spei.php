<?php
/**
 * CoreMushroom - cobro por transferencia SPEI.
 *
 * Pasarela propia, no la de transferencia bancaria que trae WooCommerce. Se
 * escribe a mano porque hace falta que el pedido quede en un estado propio,
 * "esperando comprobante", que la de WooCommerce no distingue de un pedido
 * en espera cualquiera.
 *
 * El flujo es: el cliente confirma el pedido, ve la CLABE y la referencia,
 * transfiere desde su banco, sube el comprobante, y el dueno lo verifica
 * desde el panel antes de que el pedido pase a preparacion.
 *
 * Comision cero. No depende de la aprobacion de ningun procesador.
 *
 * @package CoreMushroom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Estado de pedido propio: esperando que llegue y se verifique el dinero.
 *
 * WooCommerce trae "en espera", pero ese estado lo usan tambien otras cosas.
 * Uno propio deja filtrar en el panel por los pedidos que hay que revisar.
 */
const COREMUSHROOM_ESTADO_SPEI = 'wc-cm-spei';

/**
 * Registra el estado en WordPress.
 */
function coremushroom_registrar_estado_spei() {
	register_post_status(
		COREMUSHROOM_ESTADO_SPEI,
		array(
			'label'                     => _x( 'Esperando comprobante', 'Estado de pedido', 'coremushroom' ),
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			/* translators: %s: numero de pedidos. */
			'label_count'               => _n_noop(
				'Esperando comprobante <span class="count">(%s)</span>',
				'Esperando comprobante <span class="count">(%s)</span>',
				'coremushroom'
			),
		)
	);
}
add_action( 'init', 'coremushroom_registrar_estado_spei' );

/**
 * Agrega el estado a la lista que WooCommerce muestra en el panel.
 *
 * Se inserta justo despues de "pendiente de pago", que es donde tiene
 * sentido leerlo.
 *
 * @param array<string, string> $estados Estados registrados.
 * @return array<string, string>
 */
function coremushroom_agregar_estado_spei( $estados ) {
	$salida = array();

	foreach ( $estados as $clave => $etiqueta ) {
		$salida[ $clave ] = $etiqueta;

		if ( 'wc-pending' === $clave ) {
			$salida[ COREMUSHROOM_ESTADO_SPEI ] = _x( 'Esperando comprobante', 'Estado de pedido', 'coremushroom' );
		}
	}

	// Si WooCommerce cambiara el nombre de wc-pending, el estado se agrega
	// igual al final en vez de perderse en silencio.
	if ( ! isset( $salida[ COREMUSHROOM_ESTADO_SPEI ] ) ) {
		$salida[ COREMUSHROOM_ESTADO_SPEI ] = _x( 'Esperando comprobante', 'Estado de pedido', 'coremushroom' );
	}

	return $salida;
}
add_filter( 'wc_order_statuses', 'coremushroom_agregar_estado_spei' );

/**
 * Los pedidos en este estado cuentan como pendientes de pago.
 *
 * @param string[] $estados Estados que WooCommerce considera no pagados.
 * @return string[]
 */
function coremushroom_estado_spei_es_pendiente( $estados ) {
	$estados[] = 'cm-spei';
	return $estados;
}
add_filter( 'woocommerce_valid_order_statuses_for_payment', 'coremushroom_estado_spei_es_pendiente' );

/**
 * Registra la pasarela en WooCommerce.
 *
 * @param string[] $pasarelas Clases de pasarela.
 * @return string[]
 */
function coremushroom_registrar_pasarelas( $pasarelas ) {
	$pasarelas[] = 'CoreMushroom_Gateway_SPEI';
	$pasarelas[] = 'CoreMushroom_Gateway_Tarjeta';
	return $pasarelas;
}
add_filter( 'woocommerce_payment_gateways', 'coremushroom_registrar_pasarelas' );

/**
 * Carga las clases cuando WooCommerce ya definio la clase padre.
 *
 * WC_Payment_Gateway no existe hasta que WooCommerce arranca, asi que las
 * clases no pueden declararse al incluir el archivo.
 */
function coremushroom_definir_pasarelas() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) || class_exists( 'CoreMushroom_Gateway_SPEI' ) ) {
		return;
	}

	/**
	 * Transferencia SPEI con comprobante.
	 */
	class CoreMushroom_Gateway_SPEI extends WC_Payment_Gateway {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id                 = 'coremushroom_spei';
			$this->has_fields         = false;
			$this->method_title       = __( 'Transferencia SPEI', 'coremushroom' );
			$this->method_description = __(
				'El cliente transfiere desde su banco y sube el comprobante. Tú lo verificas antes de preparar el pedido. Sin comisión.',
				'coremushroom'
			);

			$this->init_form_fields();
			$this->init_settings();

			$this->title       = $this->get_option( 'title' );
			$this->description = $this->get_option( 'description' );

			add_action(
				'woocommerce_update_options_payment_gateways_' . $this->id,
				array( $this, 'process_admin_options' )
			);
		}

		/**
		 * Campos de configuracion de la pasarela.
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'      => array(
					'title'   => __( 'Activar', 'coremushroom' ),
					'type'    => 'checkbox',
					'label'   => __( 'Aceptar pagos por transferencia SPEI', 'coremushroom' ),
					'default' => 'yes',
				),
				'title'        => array(
					'title'       => __( 'Título en el checkout', 'coremushroom' ),
					'type'        => 'text',
					'default'     => __( 'Transferencia SPEI', 'coremushroom' ),
					'desc_tip'    => true,
					'description' => __( 'Lo que ve el cliente al elegir cómo pagar.', 'coremushroom' ),
				),
				'description'  => array(
					'title'       => __( 'Descripción en el checkout', 'coremushroom' ),
					'type'        => 'textarea',
					'default'     => __(
						'Transfiere desde tu banco y sube tu comprobante. Preparamos tu pedido en cuanto confirmemos el depósito.',
						'coremushroom'
					),
				),
				'beneficiario' => array(
					'title'       => __( 'Beneficiario', 'coremushroom' ),
					'type'        => 'text',
					'description' => __( 'A nombre de quién está la cuenta. Tiene que coincidir con lo que ve el cliente en su banca.', 'coremushroom' ),
				),
				'banco'        => array(
					'title' => __( 'Banco', 'coremushroom' ),
					'type'  => 'text',
				),
				'clabe'        => array(
					'title'       => __( 'CLABE interbancaria', 'coremushroom' ),
					'type'        => 'text',
					'description' => __( 'Dieciocho dígitos. Se valida al guardar.', 'coremushroom' ),
				),
				'plazo_horas'  => array(
					'title'       => __( 'Horas para pagar', 'coremushroom' ),
					'type'        => 'number',
					'default'     => '48',
					'description' => __( 'Pasado este plazo sin comprobante, el pedido se puede cancelar a mano.', 'coremushroom' ),
				),
				'instrucciones' => array(
					'title'       => __( 'Instrucciones adicionales', 'coremushroom' ),
					'type'        => 'textarea',
					'description' => __( 'Se muestran en la página de gracias y en el correo del pedido.', 'coremushroom' ),
				),
			);
		}

		/**
		 * Valida la CLABE antes de guardarla.
		 *
		 * Una CLABE mal capturada manda el dinero de los clientes a otra
		 * cuenta o a ninguna. Se comprueba longitud, que sean digitos y el
		 * digito verificador del estandar mexicano.
		 *
		 * @param string $clave Nombre del campo.
		 * @param string $valor Valor enviado.
		 * @return string
		 */
		public function validate_clabe_field( $clave, $valor ) {
			$valor = preg_replace( '/\D/', '', (string) $valor );

			if ( '' === $valor ) {
				return '';
			}

			if ( 18 !== strlen( $valor ) || ! coremushroom_clabe_valida( $valor ) ) {
				WC_Admin_Settings::add_error(
					__( 'La CLABE no es válida. Revisa que sean 18 dígitos y que estén bien copiados.', 'coremushroom' )
				);

				// Se conserva lo que ya estaba guardado en vez de pisarlo con
				// un valor malo.
				return (string) $this->get_option( 'clabe' );
			}

			return $valor;
		}

		/**
		 * Solo se ofrece si la cuenta esta configurada.
		 *
		 * Sin CLABE el cliente confirmaria un pedido sin saber a donde
		 * transferir.
		 *
		 * @return bool
		 */
		public function is_available() {
			if ( ! parent::is_available() ) {
				return false;
			}

			return '' !== trim( (string) $this->get_option( 'clabe' ) );
		}

		/**
		 * Procesa el pedido.
		 *
		 * No cobra nada: deja el pedido esperando comprobante, reduce el
		 * inventario para que no se venda dos veces lo mismo, y vacia el
		 * carrito.
		 *
		 * @param int $pedido_id ID del pedido.
		 * @return array<string, string>
		 */
		public function process_payment( $pedido_id ) {
			$pedido = wc_get_order( $pedido_id );

			if ( ! $pedido ) {
				return array( 'result' => 'failure' );
			}

			$pedido->update_status(
				'cm-spei',
				__( 'Esperando la transferencia y el comprobante del cliente.', 'coremushroom' )
			);

			wc_reduce_stock_levels( $pedido_id );

			if ( WC()->cart ) {
				WC()->cart->empty_cart();
			}

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $pedido ),
			);
		}

		/**
		 * Instrucciones en la pagina de gracias.
		 *
		 * @param int $pedido_id ID del pedido.
		 */
		public function thankyou_page( $pedido_id ) {
			echo coremushroom_instrucciones_spei( $pedido_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya viene escapado.
		}

		/**
		 * Instrucciones en el correo del pedido.
		 *
		 * @param WC_Order $pedido        Pedido.
		 * @param bool     $al_admin      Si el correo va al administrador.
		 * @param bool     $texto_plano   Si el correo es de texto plano.
		 */
		public function email_instructions( $pedido, $al_admin, $texto_plano = false ) {
			if ( $al_admin || $this->id !== $pedido->get_payment_method() ) {
				return;
			}

			if ( ! $pedido->has_status( 'cm-spei' ) ) {
				return;
			}

			echo coremushroom_instrucciones_spei( $pedido->get_id(), $texto_plano ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya viene escapado.
		}
	}

	/**
	 * Tarjeta, registrada pero sin integrar.
	 *
	 * Existe para que el dia que haya credenciales de un procesador se
	 * encienda con un interruptor, sin tocar el checkout. Nace desactivada y
	 * ademas is_available() devuelve false pase lo que pase, para que no
	 * pueda aparecerle a un cliente por accidente un metodo que no cobra.
	 */
	class CoreMushroom_Gateway_Tarjeta extends WC_Payment_Gateway {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id                 = 'coremushroom_tarjeta';
			$this->has_fields         = false;
			$this->method_title       = __( 'Tarjeta (sin integrar)', 'coremushroom' );
			$this->method_description = __(
				'Hueco reservado. No cobra nada y no se muestra en el checkout. Cuando haya cuenta con Conekta, Stripe u otro procesador, se sustituye por su plugin oficial y esta entrada se retira.',
				'coremushroom'
			);

			$this->init_form_fields();
			$this->init_settings();

			add_action(
				'woocommerce_update_options_payment_gateways_' . $this->id,
				array( $this, 'process_admin_options' )
			);
		}

		/**
		 * Campos de configuracion.
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'aviso' => array(
					'title'       => __( 'Estado', 'coremushroom' ),
					'type'        => 'title',
					'description' => __(
						'Sin integrar. El cliente no ve esta opción en el checkout. Mostrar un método de pago que no cobra hace que la gente abandone el carrito, así que permanece oculta hasta que exista una integración real.',
						'coremushroom'
					),
				),
			);
		}

		/**
		 * Nunca disponible mientras no exista integracion.
		 *
		 * @return bool
		 */
		public function is_available() {
			return false;
		}
	}
}
add_action( 'plugins_loaded', 'coremushroom_definir_pasarelas', 11 );

/**
 * Valida el digito verificador de una CLABE mexicana.
 *
 * Los primeros 17 digitos se multiplican por 3, 7 y 1 de forma ciclica, se
 * toma el ultimo digito de cada producto, se suman, y el verificador es lo
 * que le falta a esa suma para llegar a la siguiente decena.
 *
 * @param string $clabe Dieciocho digitos.
 * @return bool
 */
function coremushroom_clabe_valida( $clabe ) {
	$clabe = (string) $clabe;

	if ( ! preg_match( '/^\d{18}\z/', $clabe ) ) {
		return false;
	}

	$pesos = array( 3, 7, 1 );
	$suma  = 0;

	for ( $i = 0; $i < 17; $i++ ) {
		$suma += ( (int) $clabe[ $i ] * $pesos[ $i % 3 ] ) % 10;
	}

	$verificador = ( 10 - ( $suma % 10 ) ) % 10;

	return $verificador === (int) $clabe[17];
}

/**
 * Construye las instrucciones de pago de un pedido.
 *
 * La referencia es el numero de pedido. Se pide al cliente que la escriba en
 * el concepto de la transferencia, porque es lo unico que permite casar un
 * deposito con un pedido sin adivinar.
 *
 * @param int  $pedido_id   ID del pedido.
 * @param bool $texto_plano Si se genera para un correo de texto plano.
 * @return string HTML o texto ya escapado.
 */
function coremushroom_instrucciones_spei( $pedido_id, $texto_plano = false ) {
	$pedido = wc_get_order( $pedido_id );

	if ( ! $pedido || 'coremushroom_spei' !== $pedido->get_payment_method() ) {
		return '';
	}

	$ajustes = get_option( 'woocommerce_coremushroom_spei_settings', array() );
	$clabe   = isset( $ajustes['clabe'] ) ? (string) $ajustes['clabe'] : '';

	if ( '' === $clabe ) {
		return '';
	}

	$referencia = $pedido->get_order_number();
	$total      = wp_strip_all_tags( wc_price( $pedido->get_total(), array( 'currency' => $pedido->get_currency() ) ) );

	$filas = array(
		__( 'Banco', 'coremushroom' )        => isset( $ajustes['banco'] ) ? $ajustes['banco'] : '',
		__( 'Beneficiario', 'coremushroom' ) => isset( $ajustes['beneficiario'] ) ? $ajustes['beneficiario'] : '',
		__( 'CLABE', 'coremushroom' )        => $clabe,
		__( 'Monto exacto', 'coremushroom' ) => $total,
		__( 'Concepto o referencia', 'coremushroom' ) => $referencia,
	);

	if ( $texto_plano ) {
		$salida = "\n" . __( 'DATOS PARA TU TRANSFERENCIA', 'coremushroom' ) . "\n\n";

		foreach ( $filas as $etiqueta => $valor ) {
			if ( '' === $valor ) {
				continue;
			}
			$salida .= $etiqueta . ': ' . $valor . "\n";
		}

		$salida .= "\n" . __(
			'Escribe la referencia en el concepto de la transferencia. Después sube tu comprobante desde el pedido en tu cuenta.',
			'coremushroom'
		) . "\n";

		return $salida;
	}

	$html = '<section class="cm-spei">';
	$html .= '<h2 class="cm-spei__titulo">' . esc_html__( 'Datos para tu transferencia', 'coremushroom' ) . '</h2>';
	$html .= '<div class="cm-datos-envoltorio"><table class="cm-datos"><tbody>';

	foreach ( $filas as $etiqueta => $valor ) {
		if ( '' === $valor ) {
			continue;
		}

		$html .= sprintf(
			'<tr><th scope="row">%1$s</th><td>%2$s</td></tr>',
			esc_html( $etiqueta ),
			esc_html( $valor )
		);
	}

	$html .= '</tbody></table></div>';

	$html .= '<p class="cm-spei__nota">' . esc_html__(
		'Escribe la referencia en el concepto de la transferencia. Sin ella no podemos saber qué depósito corresponde a tu pedido.',
		'coremushroom'
	) . '</p>';

	$plazo = isset( $ajustes['plazo_horas'] ) ? (int) $ajustes['plazo_horas'] : 0;

	if ( $plazo > 0 ) {
		$html .= '<p class="cm-spei__nota">' . sprintf(
			/* translators: %d: horas. */
			esc_html( _n(
				'Tienes %d hora para completar la transferencia. Después de ese plazo el pedido se puede cancelar.',
				'Tienes %d horas para completar la transferencia. Después de ese plazo el pedido se puede cancelar.',
				$plazo,
				'coremushroom'
			) ),
			$plazo
		) . '</p>';
	}

	if ( ! empty( $ajustes['instrucciones'] ) ) {
		$html .= wpautop( wptexturize( wp_kses_post( $ajustes['instrucciones'] ) ) );
	}

	$html .= '</section>';

	return $html;
}

/**
 * Engancha las instrucciones a la pagina de gracias y a los correos.
 *
 * Se hace aqui y no en la clase para que funcione tambien con el checkout
 * de bloques, donde WooCommerce no llama a thankyou_page de la pasarela.
 */
function coremushroom_enganchar_instrucciones_spei() {
	add_action( 'woocommerce_thankyou_coremushroom_spei', 'coremushroom_instrucciones_spei_eco', 20 );
	add_action( 'woocommerce_email_before_order_table', 'coremushroom_instrucciones_spei_correo', 20, 3 );
}
add_action( 'init', 'coremushroom_enganchar_instrucciones_spei' );

/**
 * Imprime las instrucciones en la pagina de gracias.
 *
 * @param int $pedido_id ID del pedido.
 */
function coremushroom_instrucciones_spei_eco( $pedido_id ) {
	echo coremushroom_instrucciones_spei( $pedido_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya viene escapado.
}

/**
 * Imprime las instrucciones en el correo del cliente.
 *
 * @param WC_Order $pedido      Pedido.
 * @param bool     $al_admin    Si el correo va al administrador.
 * @param bool     $texto_plano Si el correo es de texto plano.
 */
function coremushroom_instrucciones_spei_correo( $pedido, $al_admin = false, $texto_plano = false ) {
	if ( $al_admin || ! $pedido instanceof WC_Order ) {
		return;
	}

	if ( ! $pedido->has_status( 'cm-spei' ) ) {
		return;
	}

	echo coremushroom_instrucciones_spei( $pedido->get_id(), $texto_plano ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya viene escapado.
}
