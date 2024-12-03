<?php
/**
 * Plugin Name: Christmas Greeting Generator
 * Description: Plugin to create personalized Christmas cards
 * Version: 1.0
 * Author: Meetup WordPress Lugo
 * Author URI: https://wplugo.eu/
 * Text Domain: mwl_christmas
 * Domain Path: /languages
 *
 * @package mwl_christmas
 */

// Avoid direct access to file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MWLC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'MWLC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MWLC_VERSION', '1.0.0' );
define( 'MWLC_IMAGES_COUNT', 9 );

/**
 * Load plugin textdomain.
 */
function mwlc_load_textdomain() {
	load_plugin_textdomain( 'mwl_christmas', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'mwlc_load_textdomain' );

/**
 * Enqueue scripts and styles.
 */
function mwlc_enqueue_scripts() {
	wp_enqueue_style( 'christmas-style', MWLC_PLUGIN_URL . 'css/meetup-lugo-christmas-styles.css', array(), MWLC_VERSION );
	wp_enqueue_script( 'christmas-script', MWLC_PLUGIN_URL . 'js/meetup-lugo-christmas-script.js', array(), MWLC_VERSION, true );
	wp_localize_script(
		'christmas-script',
		'christmas_ajax_object',
		array(
			'ajaxurl'              => admin_url( 'admin-ajax.php' ),
			'nonce'                => wp_create_nonce( 'christmas_nonce' ),
			'txt_error_enter'      => __( 'Please, enter a text and select a template', 'mwl_christmas' ),
			'txt_generating'       => __( 'Generating...', 'mwl_christmas' ),
			'txt_error'            => __( 'Error generating the greeting', 'mwl_christmas' ),
			'txt_error_connection' => __( 'Connection error', 'mwl_christmas' ),
			'txt_generate'         => __( 'Generate Greeting', 'mwl_christmas' ),

		)
	);
}
add_action( 'wp_enqueue_scripts', 'mwlc_enqueue_scripts' );
add_action( 'admin_enqueue_scripts', 'mwlc_enqueue_scripts' );

/**
 * Add menu to admin.
 */
function mwlc_menu() {
	add_menu_page(
		__( 'Christmas Greetings', 'mwl_christmas' ),
		__( 'Greetings', 'mwl_christmas' ),
		'manage_options',
		'christmas',
		'mwlc_admin_page',
		'dashicons-images-alt2'
	);
}
add_action( 'admin_menu', 'mwlc_menu' );

/**
 * Form to generate Christmas greetings.
 */
function mwlc_admin_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Christmas Greetings Generator', 'mwl_christmas' ); ?></h1>
		<div id="christmas-form">
			<div class="form-group">
				<label for="greeting-text"><h2><?php esc_html_e( 'Greeting text', 'mwl_christmas' ); ?>:</h2></label>
				<textarea id="greeting-text" name="text" rows="4"></textarea>
			</div>

			<div class="form-group">
				<p><strong><?php esc_html_e( 'Select a template', 'mwl_christmas' ); ?>:</strong></p>
				<div class="grid-template">
					<?php
					for ( $i = 1; $i <= MWLC_IMAGES_COUNT; $i++ ) {
						// Check if image exists.
						$image_path = MWLC_PLUGIN_PATH . 'images/template-' . $i . '.jpg';
						$image_url  = MWLC_PLUGIN_URL . 'images/template-' . $i . '.jpg';

						if ( file_exists( $image_path ) ) {
							echo '<div class="item-template">';
							// phpcs:ignore
							echo '<img src="' . $image_url . '" alt="Template number ' . $i . ' " data-id="' . $i . '" />';
							echo '</div>';
						}
					}
					?>
				</div>
			</div>

			<div id="email-form" style="display: none;">
				<div class="form-group">
					<label for="email-recipient"><?php esc_html_e( 'Email Recipient', 'mwl_christmas' ); ?>:</label>
					<input type="email" id="email-recipient" name="email_recipient" required>
				</div>

				<div class="form-group">
					<label for="email-subject"><?php esc_html_e( 'Subject', 'mwl_christmas' ); ?>:</label>
					<input type="text" id="email-subject" name="email_subject" value="<?php esc_attr_e( 'Happy Christmas!', 'mwl_christmas' ); ?>" required>
				</div>

				<div class="form-group">
					<label for="email-message"><?php esc_html_e( 'Additional Message (optional)', 'mwl_christmas' ); ?>:</label>
					<textarea id="email-message" name="email_message" rows="3"></textarea>
				</div>
			</div>

			<div class="button-group">
				<button id="generate-greeting" class="button button-primary"><?php esc_html_e( 'Generate Greeting', 'mwl_christmas' ); ?></button>
				<button id="show-email" class="button" style="display: none;"><?php esc_html_e( 'Send by Email', 'mwl_christmas' ); ?></button>
				<button id="send-email" class="button button-primary" style="display: none;"><?php esc_html_e( 'Send Greeting', 'mwl_christmas' ); ?></button>
			</div>
		</div>

		<div id="preview-container" style="display: none;">
			<h3><?php esc_html_e( 'Preview', 'mwl_christmas' ); ?>:</h3>
			<div id="preview-image"></div>
			<a id="download-btn" class="button button-primary" download="<?php esc_attr_e( 'christmas-greeting', 'mwl_christmas' ); // Christmas greeting file name. ?>.jpg"><?php esc_html_e( 'Download Greeting', 'mwl_christmas' ); ?></a>
		</div>
	</div>
	<?php
}

/**
 * Proccess the image generation
 *
 * @return void
 */
function mwlc_generate_greeting() {
	check_ajax_referer( 'christmas_nonce', 'nonce' );

	$text        = sanitize_textarea_field( $_POST['text'] );
	$template_id = intval( $_POST['template_id'] );

	if ( $template_id > MWLC_IMAGES_COUNT ) {
		$template_id = 1;
	}

	// Load image base.
	$template_path = MWLC_PLUGIN_PATH . 'images/template-' . $template_id . '.jpg';
	$image         = imagecreatefromjpeg( $template_path );

	// Configure Font.
	$font_path = MWLC_PLUGIN_PATH . 'fonts/OpenSans-Bold.ttf';
	$font_size = 60;
	$color     = imagecolorallocate( $image, 255, 255, 255 ); // White text.

	// Create semitransparent mask for text.
	$width  = imagesx( $image );
	$height = imagesy( $image );
	$mask   = imagecreatetruecolor( $width, $height );
	$black  = imagecolorallocatealpha( $mask, 0, 0, 0, 80 );
	imagefill( $mask, 0, 0, $black );

	// Merge mask with original image.
	imagecopy( $image, $mask, 0, 0, 0, 0, $width, $height );

	// Obtain dimensions of text.
	//$bbox        = imagettfbbox( $font_size, 0, $font_path, $text );
	//$text_width  = $bbox[2] - $bbox[0];
	//$text_height = $bbox[1] - $bbox[7];

	// Split text into lines.
	$lines = explode( "\n", $text );

	// Calculate total height of all lines.
	$total_height = 0;
	$line_heights = array();
	$line_widths  = array();

	foreach ( $lines as $line ) {
		$bbox           = imagettfbbox( $font_size, 0, $font_path, $line );
		$line_heights[] = $bbox[1] - $bbox[7];
		$line_widths[]  = $bbox[2] - $bbox[0];
		$total_height  += ( $bbox[1] - $bbox[7] ) * 1.5; // 1.5 for line spacing
	}

	// Y position (from where to start drawing).
	$y = ( $height - $total_height) / 2;

	// Draw every line.
	foreach ( $lines as $i => $line ) {
		// Center every line horizontally.
		$x = ( $width - $line_widths[ $i ] ) / 2;

		// Draw the line.
		imagettftext( $image, $font_size, 0, $x, $y + ( $i * $line_heights[ $i ] * 1.5 ), $color, $font_path, $line );
	}

	// Save image temporarily.
	$temp_file = wp_upload_dir()['path'] . '/temp_greeting_' . time() . '.jpg';
	imagejpeg( $image, $temp_file, 90 );

	// Clean memory.
	imagedestroy( $image );
	imagedestroy( $mask );

	// Return image URL.
	$image_url = wp_upload_dir()['url'] . '/temp_greeting_' . time() . '.jpg';
	wp_send_json_success( array( 'url' => $image_url ) );

	wp_die();
}
add_action( 'wp_ajax_generate_greeting', 'mwlc_generate_greeting' );

/**
 * Send a greeting email.
 *
 * @return void
 */
function mwlc_email_greeting() {
	check_ajax_referer( 'christmas_nonce', 'nonce' );

	$email_to           = sanitize_email( $_POST['email_to'] );
	$subject            = sanitize_text_field( $_POST['subject'] );
	$additional_message = sanitize_textarea_field( $_POST['additional_message'] );
	$image_url          = esc_url_raw( $_POST['image_url'] );

	// Check if the email is valid.
	if ( ! is_email( $email_to ) ) {
		wp_send_json_error( 'Invalid email' );
		return;
	}

	// Obtain the image and convert it to an attachment.
	$image_content = file_get_contents( $image_url ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$image_name    = 'felicitacion-navidad.jpg';
	$temp_file     = wp_upload_dir()['path'] . '/' . $image_name;

	file_put_contents( $temp_file, $image_content ); // phpcs:ignore WordPress.WP.AlternativeFunctions

	// Prepare the email message.
	$headers      = array( 'Content-Type: text/html; charset=UTF-8' );
	$html_message = sprintf(
		'<!DOCTYPE html>
		<html>
		<body>
			<p>%s</p>
			<p>' . __( 'You have been sent a Christmas greeting!', 'mwl_christmas' ) . '</p>
			<p style="color: #666; font-style: italic;">' . __( 'The greeting is attached to this email.', 'mwl_christmas' ) . '</p>
		</body>
		</html>',
		nl2br( $additional_message )
	);

	// Attach the image to the email.
	$attachments = array( $temp_file );

	// Send the email.
	$mail_sent = wp_mail( $email_to, $subject, $html_message, $headers, $attachments );

	// Clean up the temporary file.
	unlink( $temp_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions

	if ( $mail_sent ) {
		wp_send_json_success( __( 'Email sent successfully', 'mwl_christmas' ) );
	} else {
		wp_send_json_error( __( 'Error sending email', 'mwl_christmas' ) );
	}

	wp_die();
}
add_action( 'wp_ajax_send_greeting_email', 'mwlc_email_greeting' );
