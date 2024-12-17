// js/script.js

document.addEventListener( 'DOMContentLoaded', () => {
	// References to DOM elements
	const templates         = document.querySelectorAll( '.item-template' );
	const generate_btn      = document.getElementById( 'generate-greeting' );
	const text_input        = document.getElementById( 'greeting-text' );
	const preview_container = document.getElementById( 'preview-container' );
	const preview_image     = document.getElementById( 'preview-image' );
	const download_btn      = document.getElementById( 'download-btn' );
	const email_form        = document.getElementById( 'email-form' );
	const show_email_btn    = document.getElementById( 'show-email' );
	const send_email_btn    = document.getElementById( 'send-email' );
	const email_input       = document.getElementById( 'email-recipient' );
	const details_templates = document.getElementById( 'details-templates' );
	let current_image_url   = null;
	let current_image_path  = null;

	// Functions to display messages
	function show_message( message, message_type ) {
		const message_el       = document.createElement( 'div' );
		message_el.className   = `${message_type}-message`;
		message_el.textContent = message;

		const container = document.querySelector( '.wrap' );
		container.insertBefore( message_el, container.firstChild );

		setTimeout( () => {
			message_el.remove();
		}, 10000 );
	}

	// Template selection.
	templates.forEach( template => {
		template.addEventListener( 'click', () => {
			// Remove previous selection
			templates.forEach( p => p.classList.remove( 'selected' ) );
			// Add new selection
			template.classList.add( 'selected');
		} );
	} );

	// Generate Christmas greeting.
	generate_btn.addEventListener( 'click', async () => {
		const text              = text_input.value;
		const selected_template = document.querySelector( '.item-template.selected img' );
		const template_id       = selected_template ? selected_template.dataset.id : null;
		const font              = document.getElementById( 'greeting-font' ).value;

		if ( ! text || ! template_id ) {
			alert( christmas_ajax_object.txt_error_enter );

			return;
		}

		try {
			// Disable button and show status
			generate_btn.disabled    = true;
			generate_btn.textContent = christmas_ajax_object.txt_generating;

			// Prepare data for the request
			const form_data = new FormData();

			form_data.append( 'action', 'generate_greeting' );
			form_data.append( 'nonce', christmas_ajax_object.nonce );
			form_data.append( 'text', text );
			form_data.append( 'font', font );
			form_data.append( 'template_id', template_id );

			// Make request
			const response = await fetch( christmas_ajax_object.ajaxurl, {
				method:      'POST',
				credentials: 'same-origin',
				body:        form_data
			} );

			const data = await response.json();

			if ( data.success ) {
				// Create and show the image
				const img = document.createElement( 'img' );

				img.src                 = data.data.url;
				preview_image.innerHTML = '';

				preview_image.appendChild( img );

				// Show preview container and update download button
				preview_container.style.display = 'block';
				download_btn.href               = data.data.url;
				show_email_btn.style.display    = 'inline-block';
				current_image_url               = data.data.url;
				current_image_path              = data.data.path;
				details_templates.open          = false;

			} else {
				throw new Error( christmas_ajax_object.txt_error );
			}
		} catch ( error ) {
			console.error( 'Error:', error );
			alert( error.message || christmas_ajax_object.txt_error_connection );
		} finally {
			// Restore button state
			generate_btn.disabled    = false;
			generate_btn.textContent = christmas_ajax_object.txt_generate;
		}
	} );

	// Optional: Add functionality of dragging and dropping to select template
	templates.forEach( template => {
		template.addEventListener( 'dragstart', (e) => {
			e.preventDefault(); // Prevenir arrastre de imágenes
		} );

		template.addEventListener( 'mouseenter', () => {
			if ( ! template.classList.contains( 'selected' ) ) {
				template.style.transform = 'scale(1.05)';
			}
		} );

		template.addEventListener('mouseleave', () => {
			template.style.transform = 'scale(1)';
		} );
	} );

	// Optional: Real-time validation of text
	text_input.addEventListener( 'input', () => {
		const maxLength = 200; // Adjust as needed

		if ( text_input.value.length > maxLength ) {
			text_input.value = text_input.value.substring( 0, maxLength );

			alert(`El texto no puede exceder los ${maxLength} caracteres`);
		}
	} );

	// Show/hide email form
	show_email_btn.addEventListener( 'click', () => {
		email_form.style.display     = email_form.style.display === 'none' ? 'block' : 'none';
	} );

	// Send email
	send_email_btn.addEventListener( 'click', async () => {
		const email_receipt = document.getElementById( 'email-recipient' ).value;
		const email_subject = document.getElementById( 'email-subject' ).value;
		const email_message = document.getElementById( 'email-message' ).value;

		if ( ! email_receipt || ! email_subject || ! current_image_url ) {
			alert( christmas_ajax_object.txt_all_fields );

			return;
		}

		try {
			send_email_btn.classList.add( 'loading' );
			send_email_btn.disabled = true;

			const formData = new FormData();
			formData.append( 'action', 'send_greeting_email' );
			formData.append( 'nonce', christmas_ajax_object.nonce );
			formData.append( 'email_to', email_receipt );
			formData.append( 'subject', email_subject );
			formData.append( 'additional_message', email_message );
			formData.append( 'imagen_url', current_image_url );
			formData.append( 'image_path', current_image_path );

			const response = await fetch( christmas_ajax_object.ajaxurl, {
				method:      'POST',
				credentials: 'same-origin',
				body:         formData
			} );

			const data = await response.json();

			if ( data.success ) {
				show_message( christmas_ajax_object.txt_send_ok, 'success' );
				email_form.style.display     = 'none';
			} else {
				throw new Error( data.data || christmas_ajax_object.txt_send_error );
			}
		} catch ( error ) {
			console.error( 'Error:', error );
			show_message( error.message, 'error' );
		} finally {
			send_email_btn.classList.remove( 'loading' );
			send_email_btn.disabled = false;
		}
	} );

	email_input.addEventListener( 'input', () => {
		const is_valid = email_input.checkValidity();

		email_input.style.borderColor = is_valid ? '' : '#dc3545';
	} );
} );
