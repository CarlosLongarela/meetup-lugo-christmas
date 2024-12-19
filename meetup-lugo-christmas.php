<?php
/**
 * Plugin Name: Christmas Greeting Generator
 * Description: Plugin to create personalized Christmas cards
 * Version: 1.2.1
 * Author: Meetup WordPress Lugo
 * Author URI: https://wplugo.eu/
 * Text Domain: meetup-lugo-christmas
 * Domain Path: /languages
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package meetup-lugo-christmas
 */

// Avoid direct access to file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MWLC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'MWLC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MWLC_VERSION', '1.2.1' );
define( 'MWLC_IMAGES_COUNT', 9 );

/**
 * Load plugin textdomain.
 */
function mwlc_load_textdomain() {
	load_plugin_textdomain( 'meetup-lugo-christmas', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
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
			'txt_error_enter'      => __( 'Please, enter a text and select a template', 'meetup-lugo-christmas' ),
			'txt_generating'       => __( 'Generating...', 'meetup-lugo-christmas' ),
			'txt_error'            => __( 'Error generating the greeting', 'meetup-lugo-christmas' ),
			'txt_error_connection' => __( 'Connection error', 'meetup-lugo-christmas' ),
			'txt_generate'         => __( 'Generate Greeting', 'meetup-lugo-christmas' ),
			'txt_all_fields'       => __( 'All fields are required', 'meetup-lugo-christmas' ),
			'txt_send_ok'          => __( 'Greeting sent successfully', 'meetup-lugo-christmas' ),
			'txt_send_error'       => __( 'Error sending the greeting', 'meetup-lugo-christmas' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'mwlc_enqueue_scripts' );

/**
 * Add menu to admin.
 */
function mwlc_menu() {
	add_menu_page(
		__( 'Christmas Greetings', 'meetup-lugo-christmas' ),
		__( 'Greetings', 'meetup-lugo-christmas' ),
		'manage_options',
		'christmas',
		'mwlc_greeting_form_page',
		'dashicons-images-alt2'
	);
}
add_action( 'admin_menu', 'mwlc_menu' );

/**
 * Form to generate Christmas greetings.
 */
function mwlc_greeting_form_page( $default_message = '' ) {
	if ( empty( $default_message ) ) {
		$default_message = __( 'Meetup WordPress Lugo wish you a Merry Christmas\nand a Happy New Year!', 'meetup-lugo-christmas' );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Christmas Greetings Generator', 'meetup-lugo-christmas' ); ?></h1>
		<div id="christmas-form">
			<div class="form-group">
				<label for="greeting-text"><h2><?php esc_html_e( 'Greeting text', 'meetup-lugo-christmas' ); ?>:</h2></label>
				<textarea id="greeting-text" name="text" rows="4"><?php echo esc_textarea( $default_message ); ?></textarea>
			</div>

			<div class="form-group">
				<label for="greeting-font"><h2><?php esc_html_e( 'Greeting font', 'meetup-lugo-christmas' ); ?>:</h2></label>
				<select name="font" id="greeting-font">
					<option value="OpenSans-Bold.ttf">Open Sans</option>
					<option value="Pacifico-Regular.ttf" selected>Pacifico</option>
					<option value="Molle-Italic.ttf">Molle</option>
					<option value="CraftyGirls-Regular.ttf">Crafty Girls</option>
					<option value="Bonbon-Regular.ttf">Bonbon</option>
				</select>
			</div>

			<div id="email-form" style="display: none;">
				<div class="form-group">
					<label for="email-recipient"><?php esc_html_e( 'Email Recipient', 'meetup-lugo-christmas' ); ?>:</label>
					<input type="email" id="email-recipient" name="email_recipient" required>
				</div>

				<div class="form-group">
					<label for="email-subject"><?php esc_html_e( 'Subject', 'meetup-lugo-christmas' ); ?>:</label>
					<input type="text" id="email-subject" name="email_subject" value="<?php esc_attr_e( 'Happy Christmas!', 'meetup-lugo-christmas' ); ?>" required>
				</div>

				<div class="form-group">
					<label for="email-message"><?php esc_html_e( 'Additional Message (optional)', 'meetup-lugo-christmas' ); ?>:</label>
					<textarea id="email-message" name="email_message" rows="4"></textarea>
				</div>

				<div class="form-group">
					<button id="send-email" class="button button-primary"><?php esc_html_e( 'Send Greeting', 'meetup-lugo-christmas' ); ?></button>
				</div>
			</div>

			<div class="button-group">
				<button id="generate-greeting" class="button button-primary"><?php esc_html_e( 'Generate Greeting', 'meetup-lugo-christmas' ); ?></button>
				<button id="show-email" class="button" style="display: none;"><?php esc_html_e( 'Send by Email', 'meetup-lugo-christmas' ); ?></button>
			</div>

			<div class="form-group">
				<details id="details-templates" open>
					<summary><h2 class="sumary-title"><?php esc_html_e( 'Select a template', 'meetup-lugo-christmas' ); ?>:</h2></summary>

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
				</details>
			</div>

		</div>

		<div id="preview-container" style="display: none;">
			<a id="download-btn" class="button button-primary" download="<?php esc_attr_e( 'christmas-greeting', 'meetup-lugo-christmas' ); // Christmas greeting file name. ?>.jpg"><?php esc_html_e( 'Download Greeting', 'meetup-lugo-christmas' ); ?></a>
			<h3><?php esc_html_e( 'Preview', 'meetup-lugo-christmas' ); ?>:</h3>
			<div id="preview-image"></div>
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

	if ( empty( $_POST['text'] ) ) {
		wp_send_json_error( __( 'Text field is required', 'meetup-lugo-christmas' ) );
	} else {
		$text = sanitize_textarea_field( wp_unslash( $_POST['text'] ) );
	}

	if ( empty( $_POST['template_id'] ) ) {
		$template_id = 1;
	} else {
		$template_id = intval( $_POST['template_id'] );
	}

	if ( empty( $_POST['font'] ) ) {
		$font = 'OpenSans-Bold.ttf';
	} else {
		$font = sanitize_text_field( wp_unslash( $_POST['font'] ) );
	}

	if ( $template_id > MWLC_IMAGES_COUNT ) {
		$template_id = 1;
	}

	// Load image base.
	$template_path = MWLC_PLUGIN_PATH . 'images/template-' . $template_id . '.jpg';
	$image         = imagecreatefromjpeg( $template_path );

	// Configure Font.
	$font_path = MWLC_PLUGIN_PATH . 'fonts/' . $font;
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
	$y = ( $height - $total_height ) / 2;

	// Draw every line.
	foreach ( $lines as $i => $line ) {
		// Center every line horizontally.
		$x = ( $width - $line_widths[ $i ] ) / 2;

		// Draw the line.
		imagettftext( $image, $font_size, 0, $x, $y + ( $i * $line_heights[ $i ] * 1.5 ), $color, $font_path, $line );
	}

	// Save image temporarily.
	$temp_file = wp_upload_dir()['path'] . '/temp_greeting_' . time() . '.jpg';
	imagejpeg( $image, $temp_file, 70 );

	// Clean memory.
	imagedestroy( $image );
	imagedestroy( $mask );

	// Return image URL and Path.
	$image_url  = wp_upload_dir()['url'] . '/temp_greeting_' . time() . '.jpg';
	$image_path = wp_upload_dir()['path'] . '/temp_greeting_' . time() . '.jpg';
	wp_send_json_success(
		array(
			'url'  => $image_url,
			'path' => $image_path,
		)
	);

	wp_die();
}
add_action( 'wp_ajax_generate_greeting', 'mwlc_generate_greeting' );
add_action( 'wp_ajax_nopriv_generate_greeting', 'mwlc_generate_greeting' );

/**
 * Send a greeting email.
 *
 * @return void
 */
function mwlc_email_greeting() {
	check_ajax_referer( 'christmas_nonce', 'nonce' );

	$email_to           = isset( $_POST['email_to'] ) ? sanitize_email( wp_unslash( $_POST['email_to'] ) ) : '';
	$subject            = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
	$additional_message = isset( $_POST['additional_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['additional_message'] ) ) : '';
	$image_path         = isset( $_POST['image_path'] ) ? sanitize_text_field( wp_unslash( $_POST['image_path'] ) ) : '';

	// Check if the email is valid.
	if ( ! is_email( $email_to ) ) {
		wp_send_json_error( 'Invalid email' );
		return;
	}

	// Obtain the image and convert it to an attachment.
	$image_content = file_get_contents( $image_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
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
			<p>' . __( 'You have been sent a Christmas greeting!', 'meetup-lugo-christmas' ) . '</p>
			<p style="color: #666; font-style: italic;">' . __( 'The greeting is attached to this email.', 'meetup-lugo-christmas' ) . '</p>
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
		wp_send_json_success( __( 'Email sent successfully', 'meetup-lugo-christmas' ) );
	} else {
		wp_send_json_error( __( 'Error sending email', 'meetup-lugo-christmas' ) );
	}

	wp_die();
}
add_action( 'wp_ajax_send_greeting_email', 'mwlc_email_greeting' );
add_action( 'wp_ajax_nopriv_send_greeting_email', 'mwlc_email_greeting' );

/**
 * Create shortcode for frontend use
 *
 * @param array $atts Shortcode attributes.
 */
function mwlc_shortcode( $atts ) {
	// Parse attributes.
	$defaults = array(
		'message' => '',
	);

	$atts = shortcode_atts( $defaults, $atts, 'christmas_greeting' );

	mwlc_enqueue_scripts();

	ob_start();
	mwlc_greeting_form_page( $atts['message'] );

	return ob_get_clean();
}
add_shortcode( 'christmas_greeting', 'mwlc_shortcode' );
