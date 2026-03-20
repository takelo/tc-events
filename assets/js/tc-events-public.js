/**
 * TC Events – Public JS (Vanilla)
 * Handles RSVP form submission via AJAX.
 */

(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var forms = document.querySelectorAll('.tc-rsvp-form');

		forms.forEach(function (form) {
			form.addEventListener('submit', handleRSVP);
		});
	});

	function handleRSVP(e) {
		e.preventDefault();

		var form    = e.currentTarget;
		var eventId = form.getAttribute('data-event-id');
		var button  = form.querySelector('.tc-rsvp-button');
		var msgEl   = form.parentNode.querySelector('.tc-rsvp-message');

		var fullName       = form.querySelector('[name="full_name"]').value.trim();
		var documentNumber = form.querySelector('[name="document_number"]').value.trim();

		if (!fullName || !documentNumber) {
			showMessage(msgEl, tcEvents.strings.fieldsRequired, 'error');
			return;
		}

		button.classList.add('tc-rsvp-loading');
		button.disabled = true;

		var formData = new FormData();
		formData.append('action', 'tc_events_rsvp');
		formData.append('nonce', tcEvents.nonce);
		formData.append('event_id', eventId);
		formData.append('full_name', fullName);
		formData.append('document_number', documentNumber);

		fetch(tcEvents.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (result) {
				button.classList.remove('tc-rsvp-loading');
				button.disabled = false;

				if (result.success) {
					showMessage(msgEl, result.data.message || tcEvents.strings.confirm, 'success');
					form.style.display = 'none';
					// Reload after short delay to update attendee count.
					setTimeout(function () { location.reload(); }, 2000);
				} else {
					var msg = result.data && result.data.message
						? result.data.message
						: tcEvents.strings.error;
					showMessage(msgEl, msg, 'error');
				}
			})
			.catch(function () {
				button.classList.remove('tc-rsvp-loading');
				button.disabled = false;
				showMessage(msgEl, tcEvents.strings.error, 'error');
			});
	}

	function showMessage(el, text, type) {
		if (!el) return;
		el.textContent = text;
		el.className = 'tc-rsvp-message tc-rsvp-message-' + type;
		el.style.display = 'block';
	}
})();
