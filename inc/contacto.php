<?php
/**
 * Enlace de contacto público, visible en todas las páginas del tema.
 *
 * @package CoreMushroom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Muestra un enlace de WhatsApp sin abrir ni enviar mensajes automáticamente. */
function coremushroom_mostrar_contacto_whatsapp() {
	$url = 'https://wa.me/522206446651';
	?>
	<div class="cm-contacto-wa">
		<span>¿Tienes una pregunta?</span>
		<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">Escríbenos por WhatsApp al 220 644 6651 <span aria-hidden="true">↗</span></a>
	</div>
	<?php
}
add_action( 'wp_footer', 'coremushroom_mostrar_contacto_whatsapp', 5 );
