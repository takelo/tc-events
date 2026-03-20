/**
 * TC Events – Admin JS
 * Date validation for event meta boxes.
 */

(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var startDate = document.getElementById('tc_event_date');
		var endDate   = document.getElementById('tc_event_end_date');

		if (!startDate || !endDate) {
			return;
		}

		// Create error message element.
		var errorEl = document.createElement('span');
		errorEl.className = 'tc-date-error';
		errorEl.textContent = 'End date must be after start date.';
		endDate.parentNode.appendChild(errorEl);

		function validateDates() {
			if (!startDate.value || !endDate.value) {
				errorEl.classList.remove('visible');
				return;
			}

			var start = new Date(startDate.value);
			var end   = new Date(endDate.value);

			if (end <= start) {
				errorEl.classList.add('visible');
				endDate.setCustomValidity('End date must be after start date.');
			} else {
				errorEl.classList.remove('visible');
				endDate.setCustomValidity('');
			}
		}

		startDate.addEventListener('change', validateDates);
		endDate.addEventListener('change', validateDates);

		// Set min on end date when start changes.
		startDate.addEventListener('change', function () {
			if (startDate.value) {
				endDate.setAttribute('min', startDate.value);
			}
		});
	});
})();
