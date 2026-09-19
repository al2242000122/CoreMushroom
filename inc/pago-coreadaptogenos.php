<?php
/**
 * CoreMushroom - puente de tarjeta hacia CoreAdaptogenos.
 *
 * CoreMushroom conserva el pedido y el inventario. CoreAdaptogenos crea un
 * pedido espejo transparente y muestra el formulario oficial de Stripe. El
 * navegador nunca confirma el pago: solo un callback firmado entre servidores
 * puede marcar el pedido de origen como pagado.
 *
 * @package CoreMushroom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const COREMUSHROOM_PUENTE_VERSION = '1';
const COREMUSHROOM_PUENTE_RUTA_SESION = '/wp-json/coreadaptogenos/v1/payment-sessions';

/**
 * Convierte una cantidad decimal a centavos sin depender de flotantes.
 *
 * @param string|int|float $cantidad Cantidad monetaria.
 * @return int
 */
function coremushroom_importe_menor( $cantidad ) {
	$normalizada = function_exists( 'wc_format_decimal' )
		? wc_format_decimal( $cantidad, 2 )
		: number_format( (float) $cantidad, 2, '.', '' );
	$partes      = explode( '.', (string) $normalizada, 2 );
	$entero      = preg_replace( '/\D/', '', $partes[0] );
	$decimales   = isset( $partes[1] ) ? preg_replace( '/\D/', '', $partes[1] ) : '';

	$decimales = substr( str_pad( $decimales, 2, '0' ), 0, 2 );

	return ( (int) $entero * 100 ) + (int) $decimales;
}

/**
 * Firma el cuerpo exacto de una peticion del puente.
 *
 * @param string $timestamp Marca Unix.
 * @param string $nonce     Valor aleatorio hexadecimal.
 * @param string $cuerpo    JSON exacto enviado.
 * @param string $secreto   Secreto compartido.
 * @return string
 */
function coremushroom_firma_puente( $timestamp, $nonce, $cuerpo, $secreto ) {
	$canon = (string) $timestamp . "\n" . (string) $nonce . "\n" . (string) $cuerpo;
	return hash_hmac( 'sha256', $canon, (string) $secreto );
}

/**
 * Devuelve los ajustes privados de la pasarela.
 *
 * @return array<string, string>
 */
function coremushroom_ajustes_puente() {
	$ajustes = get_option( 'woocommerce_coremushroom_tarjeta_settings', array() );
	return is_array( $ajustes ) ? $ajustes : array();
}

/**
 * Valida un destino HTTPS exacto de CoreAdaptogenos.
 *
 * @param string $url URL candidata.
 * @param string $base URL configurada.
 * @return bool
 */
function coremushroom_url_receptor_valida( $url, $base, $pedido_receptor = 0 ) {
	$url_partes  = wp_parse_url( $url );
	$base_partes = wp_parse_url( $base );

	if ( ! is_array( $url_partes ) || ! is_array( $base_partes ) ) {
		return false;
	}

	$puerto_url  = isset( $url_partes['port'] ) ? (int) $url_partes['port'] : 443;
	$puerto_base = isset( $base_partes['port'] ) ? (int) $base_partes['port'] : 443;
	$ruta        = (string) ( $url_partes['path'] ?? '' );
	$ruta_valida = $pedido_receptor > 0
		? (bool) preg_match( '#/order-pay/' . preg_quote( (string) $pedido_receptor, '#' ) . '/?$#', $ruta )
		: (bool) preg_match( '#/order-pay/[0-9]+/?$#', $ruta );

	return 'https' === strtolower( (string) ( $url_partes['scheme'] ?? '' ) )
		&& strtolower( (string) ( $url_partes['host'] ?? '' ) ) === strtolower( (string) ( $base_partes['host'] ?? '' ) )
		&& $puerto_url === $puerto_base
		&& empty( $url_partes['user'] ) && empty( $url_partes['pass'] )
		&& $ruta_valida;
}

/**
 * Comprueba si la configuracion minima existe.
 *
 * @param array<string, string>|null $ajustes Ajustes opcionales para prueba.
 * @return bool
 */
function coremushroom_puente_configurado( $ajustes = null ) {
	$ajustes = is_array( $ajustes ) ? $ajustes : coremushroom_ajustes_puente();
	$base    = isset( $ajustes['receiver_url'] ) ? untrailingslashit( trim( (string) $ajustes['receiver_url'] ) ) : '';
	$secreto = isset( $ajustes['shared_secret'] ) ? trim( (string) $ajustes['shared_secret'] ) : '';
	$partes  = wp_parse_url( $base );

	$base_limpia = is_array( $partes ) && empty( $partes['user'] ) && empty( $partes['pass'] ) && empty( $partes['query'] ) && empty( $partes['fragment'] )
		&& ( empty( $partes['path'] ) || '/' === $partes['path'] )
		&& ( ! isset( $partes['port'] ) || 443 === (int) $partes['port'] );

	return 'yes' === ( $ajustes['enabled'] ?? 'no' )
		&& strlen( $secreto ) >= 32
		&& in_array( (string) ( $ajustes['environment'] ?? '' ), array( 'test', 'live' ), true )
		&& is_array( $partes )
		&& 'https' === strtolower( (string) ( $partes['scheme'] ?? '' ) )
		&& 'coreadaptogenos.app' === strtolower( (string) ( $partes['host'] ?? '' ) )
		&& $base_limpia;
}

/**
 * Arma los articulos del pedido sin ocultar el catalogo real.
 *
 * @param WC_Order $pedido Pedido de origen.
 * @return array<int, array<string, int|string>>
 */
function coremushroom_articulos_puente( $pedido ) {
	$articulos = array();

	foreach ( $pedido->get_items() as $item ) {
		$producto    = $item->get_product();
		$articulos[] = array(
			'name'           => wp_strip_all_tags( $item->get_name() ),
			'sku'            => $producto ? (string) $producto->get_sku() : '',
			'quantity'       => max( 1, (int) $item->get_quantity() ),
			'subtotal_minor' => coremushroom_importe_menor( $item->get_subtotal() ),
			'total_minor'    => coremushroom_importe_menor( $item->get_total() ),
			'tax_minor'      => coremushroom_importe_menor( $item->get_total_tax() ),
		);
	}

	return $articulos;
}

/**
 * Registra la pasarela de tarjeta cuando WooCommerce ya cargo su clase base.
 *
 * @param string[] $pasarelas Pasarelas existentes.
 * @return string[]
 */
function coremushroom_registrar_pasarela_coreadaptogenos( $pasarelas ) {
	coremushroom_definir_pasarela_coreadaptogenos();
	if ( class_exists( 'CoreMushroom_Gateway_Tarjeta' ) ) {
		$pasarelas[] = 'CoreMushroom_Gateway_Tarjeta';
	}
	return $pasarelas;
}
add_filter( 'woocommerce_payment_gateways', 'coremushroom_registrar_pasarela_coreadaptogenos', 20 );

/**
 * Declara la pasarela en el momento correcto del arranque de WooCommerce.
 */
function coremushroom_definir_pasarela_coreadaptogenos() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) || class_exists( 'CoreMushroom_Gateway_Tarjeta' ) ) {
		return;
	}

	/** Pasarela remota procesada por Stripe en CoreAdaptogenos. */
	class CoreMushroom_Gateway_Tarjeta extends WC_Payment_Gateway {
		/** Constructor. */
		public function __construct() {
			$this->id                 = 'coremushroom_tarjeta';
			$this->has_fields         = false;
			$this->method_title       = __( 'Tarjeta mediante CoreAdaptógenos', 'coremushroom' );
			$this->method_description = __( 'Envía al cliente al checkout seguro de CoreAdaptógenos. Stripe procesa la tarjeta y confirma el pago mediante un callback firmado.', 'coremushroom' );
			$this->init_form_fields();
			$this->init_settings();
			$this->title       = $this->get_option( 'title' );
			$this->description = $this->get_option( 'description' );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}

		/** Campos privados de configuracion. */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title'   => __( 'Activar', 'coremushroom' ),
					'type'    => 'checkbox',
					'label'   => __( 'Mostrar tarjeta solo cuando el puente esté completo', 'coremushroom' ),
					'default' => 'no',
				),
				'title' => array(
					'title'   => __( 'Título', 'coremushroom' ),
					'type'    => 'text',
					'default' => __( 'Tarjeta — cobro por CoreAdaptógenos', 'coremushroom' ),
				),
				'description' => array(
					'title'   => __( 'Descripción', 'coremushroom' ),
					'type'    => 'textarea',
					'default' => __( 'Finalizarás el pago con tarjeta en el sitio seguro de CoreAdaptógenos. Tu pedido seguirá disponible aquí.', 'coremushroom' ),
				),
				'receiver_url' => array(
					'title'   => __( 'Sitio receptor', 'coremushroom' ),
					'type'    => 'url',
					'default' => 'https://coreadaptogenos.app',
				),
				'environment' => array(
					'title'   => __( 'Entorno de Stripe', 'coremushroom' ),
					'type'    => 'select',
					'default' => 'test',
					'options' => array(
						'test' => __( 'Pruebas', 'coremushroom' ),
						'live' => __( 'Producción', 'coremushroom' ),
					),
				),
				'shared_secret' => array(
					'title'       => __( 'Secreto compartido', 'coremushroom' ),
					'type'        => 'cm_secret',
					'description' => __( 'Mínimo 32 caracteres. Déjalo vacío para conservar el actual. Debe coincidir con el plugin receptor y nunca se guarda en Git.', 'coremushroom' ),
				),
			);
		}

		/**
		 * Renderiza el secreto sin devolver el valor actual al navegador.
		 *
		 * @param string               $key  Clave del ajuste.
		 * @param array<string, mixed> $data Definicion del campo.
		 * @return string
		 */
		public function generate_cm_secret_html( $key, $data ) {
			$field_key = $this->get_field_key( $key );
			return '<tr valign="top"><th scope="row" class="titledesc"><label for="' . esc_attr( $field_key ) . '">' . esc_html( $data['title'] ) . '</label></th><td class="forminp"><fieldset><input class="input-text regular-input" type="password" autocomplete="new-password" name="' . esc_attr( $field_key ) . '" id="' . esc_attr( $field_key ) . '" value="" /><p class="description">' . esc_html( $data['description'] ) . '</p></fieldset></td></tr>';
		}

		/**
		 * Conserva el secreto al guardar un campo vacio y rechaza valores cortos.
		 *
		 * @param string $key   Clave del ajuste.
		 * @param string $value Valor enviado.
		 * @return string
		 */
		public function validate_cm_secret_field( $key, $value ) {
			$value = trim( (string) $value );
			if ( '' === $value ) {
				return (string) $this->get_option( $key );
			}
			if ( strlen( $value ) < 32 ) {
				WC_Admin_Settings::add_error( __( 'El secreto compartido debe tener por lo menos 32 caracteres.', 'coremushroom' ) );
				return (string) $this->get_option( $key );
			}
			return $value;
		}

		/** En pruebas solo la ve quien administra WooCommerce; en vivo exige MXN y puente completo. */
		public function is_available() {
			return parent::is_available()
				&& 'MXN' === get_woocommerce_currency()
				&& ( 'test' !== $this->get_option( 'environment' ) || current_user_can( 'manage_woocommerce' ) )
				&& coremushroom_puente_configurado( $this->settings );
		}

		/**
		 * Crea la sesion remota y devuelve la redireccion firmada.
		 *
		 * @param int $pedido_id ID del pedido.
		 * @return array<string, string>
		 */
		public function process_payment( $pedido_id ) {
			$pedido = wc_get_order( $pedido_id );
			if ( ! $pedido || ! coremushroom_puente_configurado( $this->settings ) ) {
				wc_add_notice( __( 'El pago con tarjeta no está disponible en este momento.', 'coremushroom' ), 'error' );
				return array( 'result' => 'failure' );
			}

			$secreto     = (string) $this->get_option( 'shared_secret' );
			$entorno     = (string) $this->get_option( 'environment' );
			$base        = untrailingslashit( (string) $this->get_option( 'receiver_url' ) );
			$sesion      = hash_hmac( 'sha256', 'coremushroom-order|' . $pedido->get_id() . '|' . $pedido->get_order_key(), $secreto );
			$sesion_hash = hash( 'sha256', $sesion );
			$importe     = coremushroom_importe_menor( $pedido->get_total() );
			$expira      = (int) $pedido->get_meta( '_cm_bridge_expires_at' );
			if ( $expira <= 0 ) {
				$expira = time() + HOUR_IN_SECONDS;
			}

			$receptor_guardado = (int) $pedido->get_meta( '_cm_bridge_receiver_order' );
			$url_guardada      = (string) $pedido->get_meta( '_cm_bridge_checkout_url' );
			if (
				$receptor_guardado > 0 && time() < $expira
				&& hash_equals( (string) $pedido->get_meta( '_cm_bridge_session_hash' ), $sesion_hash )
				&& $importe === (int) $pedido->get_meta( '_cm_bridge_amount_minor' )
				&& $entorno === (string) $pedido->get_meta( '_cm_bridge_environment' )
				&& coremushroom_url_receptor_valida( $url_guardada, $base, $receptor_guardado )
			) {
				return array( 'result' => 'success', 'redirect' => esc_url_raw( $url_guardada ) );
			}

			if ( time() >= $expira ) {
				wc_add_notice( __( 'La sesión de tarjeta venció. Crea un pedido nuevo o elige SPEI.', 'coremushroom' ), 'error' );
				return array( 'result' => 'failure' );
			}

			$articulos = coremushroom_articulos_puente( $pedido );
			$subtotal_articulos = 0;
			foreach ( $articulos as $articulo ) {
				$subtotal_articulos += (int) $articulo['total_minor'] + (int) $articulo['tax_minor'];
			}
			$envio = method_exists( $pedido, 'get_shipping_total' )
				? coremushroom_importe_menor( $pedido->get_shipping_total() ) + coremushroom_importe_menor( $pedido->get_shipping_tax() )
				: 0;
			$ajustes = $importe - $subtotal_articulos - $envio;

			$pedido->update_meta_data( '_cm_bridge_session_hash', $sesion_hash );
			$pedido->update_meta_data( '_cm_bridge_amount_minor', $importe );
			$pedido->update_meta_data( '_cm_bridge_currency', $pedido->get_currency() );
			$pedido->update_meta_data( '_cm_bridge_environment', $entorno );
			$pedido->update_meta_data( '_cm_bridge_expires_at', $expira );
			$pedido->save();

			$cuerpo = array(
				'protocol'          => COREMUSHROOM_PUENTE_VERSION,
				'session'           => $sesion,
				'source_order_id'   => (string) $pedido->get_id(),
				'amount_minor'      => $importe,
				'currency'          => $pedido->get_currency(),
				'method'            => 'card',
				'environment'       => $entorno,
				'expires_at'        => $expira,
				'items'             => $articulos,
				'shipping_minor'    => $envio,
				'adjustments_minor' => $ajustes,
				'billing'           => array(
					'first_name' => $pedido->get_billing_first_name(),
					'last_name'  => $pedido->get_billing_last_name(),
					'email'      => $pedido->get_billing_email(),
					'phone'      => $pedido->get_billing_phone(),
					'country'    => $pedido->get_billing_country(),
					'state'      => $pedido->get_billing_state(),
					'city'       => $pedido->get_billing_city(),
					'postcode'   => $pedido->get_billing_postcode(),
					'address_1'  => $pedido->get_billing_address_1(),
					'address_2'  => $pedido->get_billing_address_2(),
				),
				'callback_url' => rest_url( 'coremushroom/v1/payment-events' ),
				'return_url'   => $this->get_return_url( $pedido ),
			);
			$json = wp_json_encode( $cuerpo );
			if ( ! is_string( $json ) ) {
				return array( 'result' => 'failure' );
			}

			$timestamp = (string) time();
			$nonce     = bin2hex( random_bytes( 16 ) );
			$respuesta = wp_safe_remote_post(
				$base . COREMUSHROOM_PUENTE_RUTA_SESION,
				array(
					'timeout'     => 20,
					'redirection' => 0,
					'headers'     => array(
						'Content-Type'   => 'application/json',
						'X-CM-Timestamp' => $timestamp,
						'X-CM-Nonce'     => $nonce,
						'X-CM-Signature' => coremushroom_firma_puente( $timestamp, $nonce, $json, $secreto ),
					),
					'body' => $json,
				)
			);

			if ( is_wp_error( $respuesta ) || 201 !== wp_remote_retrieve_response_code( $respuesta ) ) {
				wc_add_notice( __( 'No pudimos abrir el pago seguro. Inténtalo de nuevo o elige SPEI.', 'coremushroom' ), 'error' );
				return array( 'result' => 'failure' );
			}

			$datos = json_decode( wp_remote_retrieve_body( $respuesta ), true );
			if ( ! is_array( $datos ) || ! coremushroom_respuesta_puente_valida( $datos, $sesion, $importe, $base, $secreto, $entorno ) ) {
				wc_add_notice( __( 'El sitio de pago devolvió una respuesta inválida.', 'coremushroom' ), 'error' );
				return array( 'result' => 'failure' );
			}

			$pedido->update_meta_data( '_cm_bridge_receiver_order', absint( $datos['receiver_order_id'] ) );
			$pedido->update_meta_data( '_cm_bridge_checkout_url', esc_url_raw( $datos['checkout_url'] ) );
			$pedido->save();
			$pedido->update_status( 'pending', __( 'Esperando confirmación de Stripe en CoreAdaptógenos.', 'coremushroom' ) );
			if ( function_exists( 'wc_maybe_reduce_stock_levels' ) ) {
				wc_maybe_reduce_stock_levels( $pedido_id );
			} else {
				wc_reduce_stock_levels( $pedido_id );
			}
			if ( WC()->cart ) {
				WC()->cart->empty_cart();
			}

			return array( 'result' => 'success', 'redirect' => esc_url_raw( $datos['checkout_url'] ) );
		}
	}
}

/**
 * Verifica la respuesta firmada del receptor.
 *
 * @param array<string, mixed> $datos   Respuesta.
 * @param string               $sesion Sesion esperada.
 * @param int                  $importe Centavos esperados.
 * @param string               $base    Sitio receptor.
 * @param string               $secreto Secreto compartido.
 * @param string               $entorno Entorno Stripe esperado.
 * @return bool
 */
function coremushroom_respuesta_puente_valida( $datos, $sesion, $importe, $base, $secreto, $entorno = 'test' ) {
	$campos = array( 'session', 'receiver_order_id', 'amount_minor', 'currency', 'environment', 'checkout_url', 'signature' );
	foreach ( $campos as $campo ) {
		if ( ! isset( $datos[ $campo ] ) ) {
			return false;
		}
	}
	$canon = implode( '|', array( $datos['session'], $datos['receiver_order_id'], $datos['amount_minor'], $datos['currency'], $datos['environment'], $datos['checkout_url'] ) );
	return hash_equals( hash_hmac( 'sha256', $canon, $secreto ), (string) $datos['signature'] )
		&& hash_equals( $sesion, (string) $datos['session'] )
		&& $importe === (int) $datos['amount_minor']
		&& 'MXN' === (string) $datos['currency']
		&& $entorno === (string) $datos['environment']
		&& coremushroom_url_receptor_valida( (string) $datos['checkout_url'], $base, absint( $datos['receiver_order_id'] ) );
}

/** Registra el callback firmado del receptor. */
function coremushroom_registrar_ruta_eventos_pago() {
	register_rest_route(
		'coremushroom/v1',
		'/payment-events',
		array(
			'methods'             => 'POST',
			'callback'            => 'coremushroom_recibir_evento_pago',
			'permission_callback' => 'coremushroom_autorizar_evento_pago',
		)
	);
}
add_action( 'rest_api_init', 'coremushroom_registrar_ruta_eventos_pago' );

/**
 * Autoriza HMAC, vigencia y nonce unico del callback.
 *
 * @param WP_REST_Request $request Peticion.
 * @return true|WP_Error
 */
function coremushroom_autorizar_evento_pago( $request ) {
	$ajustes   = coremushroom_ajustes_puente();
	$secreto   = (string) ( $ajustes['shared_secret'] ?? '' );
	$timestamp = (string) $request->get_header( 'x-ca-timestamp' );
	$nonce     = (string) $request->get_header( 'x-ca-nonce' );
	$firma     = (string) $request->get_header( 'x-ca-signature' );

	if ( strlen( $secreto ) < 32 || ! ctype_digit( $timestamp ) || abs( time() - (int) $timestamp ) > 300 || ! preg_match( '/^[a-f0-9]{32,64}$/', $nonce ) ) {
		return new WP_Error( 'cm_bridge_auth', __( 'Firma inválida.', 'coremushroom' ), array( 'status' => 401 ) );
	}
	$esperada = coremushroom_firma_puente( $timestamp, $nonce, $request->get_body(), $secreto );
	$llave    = 'cm_bridge_nonce_' . hash( 'sha256', $nonce );
	if ( ! hash_equals( $esperada, $firma ) || ! coremushroom_reservar_nonce_puente( $llave ) ) {
		return new WP_Error( 'cm_bridge_auth', __( 'Firma inválida.', 'coremushroom' ), array( 'status' => 401 ) );
	}
	return true;
}

/**
 * Reserva un nonce de forma atomica para impedir dos callbacks simultaneos.
 *
 * add_option usa una clave unica en la base de datos; a diferencia de leer y
 * luego escribir un transient, dos procesos no pueden ganar al mismo tiempo.
 *
 * @param string $llave Nombre derivado del hash del nonce.
 * @return bool
 */
function coremushroom_reservar_nonce_puente( $llave ) {
	if ( function_exists( 'add_option' ) ) {
		$reservado = add_option( $llave, time() + ( 10 * MINUTE_IN_SECONDS ), '', false );
		if ( $reservado && function_exists( 'wp_schedule_single_event' ) ) {
			wp_schedule_single_event( time() + ( 11 * MINUTE_IN_SECONDS ), 'coremushroom_borrar_nonce_puente', array( $llave ) );
		}
		return $reservado;
	}
	if ( get_transient( $llave ) ) {
		return false;
	}
	set_transient( $llave, 1, 10 * MINUTE_IN_SECONDS );
	return true;
}

/** Borra la reserva cuando ya quedo fuera de la ventana de firma. */
function coremushroom_borrar_nonce_puente( $llave ) {
	if ( is_string( $llave ) && 0 === strpos( $llave, 'cm_bridge_nonce_' ) ) {
		delete_option( $llave );
	}
}
add_action( 'coremushroom_borrar_nonce_puente', 'coremushroom_borrar_nonce_puente' );

/**
 * Aplica una confirmacion idempotente al pedido de origen.
 *
 * @param WP_REST_Request $request Peticion autenticada.
 * @return WP_REST_Response|WP_Error
 */
function coremushroom_recibir_evento_pago( $request ) {
	$datos  = $request->get_json_params();
	$pedido = is_array( $datos ) && isset( $datos['source_order_id'] ) ? wc_get_order( absint( $datos['source_order_id'] ) ) : false;
	$evento = is_array( $datos ) ? (string) ( $datos['event'] ?? '' ) : '';
	$event_id = is_array( $datos ) ? (string) ( $datos['event_id'] ?? '' ) : '';
	if ( ! $pedido || ! in_array( $evento, array( 'paid', 'refunded', 'payment_reversed' ), true ) || ! preg_match( '/^[a-f0-9]{64}$/', $event_id ) ) {
		return new WP_Error( 'cm_bridge_event', __( 'Evento inválido.', 'coremushroom' ), array( 'status' => 400 ) );
	}
	$coincide = hash_equals( (string) $pedido->get_meta( '_cm_bridge_session_hash' ), hash( 'sha256', (string) ( $datos['session'] ?? '' ) ) )
		&& (int) $pedido->get_meta( '_cm_bridge_receiver_order' ) === absint( $datos['receiver_order_id'] ?? 0 )
		&& (int) $pedido->get_meta( '_cm_bridge_amount_minor' ) === (int) ( $datos['amount_minor'] ?? -1 )
		&& (string) $pedido->get_meta( '_cm_bridge_currency' ) === (string) ( $datos['currency'] ?? '' )
		&& (string) $pedido->get_meta( '_cm_bridge_environment' ) === (string) ( $datos['environment'] ?? '' );
	if ( ! $coincide ) {
		return new WP_Error( 'cm_bridge_mismatch', __( 'El pago no coincide con el pedido.', 'coremushroom' ), array( 'status' => 409 ) );
	}

	$llave_evento = 'cm_bridge_event_' . $event_id;
	$estado_evento = get_option( $llave_evento, '' );
	if ( 'complete' === $estado_evento ) {
		return coremushroom_respuesta_evento_pago( $datos, $event_id, 'duplicate' );
	}
	if ( ! add_option( $llave_evento, 'processing', '', false ) ) {
		return new WP_Error( 'cm_bridge_event_busy', __( 'El evento ya se está procesando.', 'coremushroom' ), array( 'status' => 409 ) );
	}

	$estado = 'applied';
	if ( 'paid' === $evento ) {
		$permitido = 'coremushroom_tarjeta' === (string) $pedido->get_payment_method()
			&& in_array( $pedido->get_status(), array( 'pending', 'on-hold' ), true )
			&& time() <= (int) $pedido->get_meta( '_cm_bridge_expires_at' );
		if ( ! $permitido ) {
			$estado = 'manual_review';
			$pedido->add_order_note( __( 'Stripe confirmó un pago fuera de la ventana o estado permitido. Requiere conciliación manual; no se cambió el estado automáticamente.', 'coremushroom' ) );
			$pedido->save();
		} elseif ( ! $pedido->is_paid() ) {
			$pedido->payment_complete( sanitize_text_field( (string) ( $datos['transaction_id'] ?? '' ) ) );
			$pedido->add_order_note( __( 'Pago con tarjeta confirmado por CoreAdaptógenos y Stripe.', 'coremushroom' ) );
		}
	} elseif ( 'refunded' === $evento ) {
		$reembolso = max( 0, (int) ( $datos['refund_amount_minor'] ?? 0 ) );
		if ( $reembolso <= 0 || $reembolso > (int) $pedido->get_meta( '_cm_bridge_amount_minor' ) || ! function_exists( 'wc_create_refund' ) ) {
			delete_option( $llave_evento );
			return new WP_Error( 'cm_bridge_refund', __( 'El reembolso no es válido.', 'coremushroom' ), array( 'status' => 409 ) );
		}
		$resultado = wc_create_refund(
			array(
				'order_id'       => $pedido->get_id(),
				'amount'         => $reembolso / 100,
				'reason'         => __( 'Reembolso confirmado por CoreAdaptógenos y Stripe.', 'coremushroom' ),
				'refund_payment' => false,
				'restock_items'  => false,
			)
		);
		if ( is_wp_error( $resultado ) ) {
			delete_option( $llave_evento );
			return $resultado;
		}
	} else {
		$pedido->update_status( 'on-hold', __( 'CoreAdaptógenos informó una reversión de pago. Requiere conciliación antes de enviar.', 'coremushroom' ) );
	}
	update_option( $llave_evento, 'complete', false );
	return coremushroom_respuesta_evento_pago( $datos, $event_id, $estado );
}

/** Devuelve un acuse verificable al emisor del callback. */
function coremushroom_respuesta_evento_pago( $datos, $event_id, $estado ) {
	return new WP_REST_Response(
		array(
			'ok'                => true,
			'event_id'          => $event_id,
			'source_order_id'   => (string) ( $datos['source_order_id'] ?? '' ),
			'receiver_order_id' => absint( $datos['receiver_order_id'] ?? 0 ),
			'status'            => $estado,
		),
		200
	);
}
