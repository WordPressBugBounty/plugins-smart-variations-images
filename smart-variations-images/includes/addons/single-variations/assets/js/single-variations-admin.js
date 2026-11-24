/**
 * Variations as products — PRO bulk panel.
 */
(function () {
	'use strict';

	var config = window.sviSVAdmin || {};
	var panel = document.querySelector('[data-svi-bulk]');

	if (!panel || !config.ajaxUrl) {
		return;
	}

	var status = panel.querySelector('[data-svi-bulk-status]');
	var bar = panel.querySelector('[data-svi-bulk-bar]');
	var barFill = bar ? bar.querySelector('span') : null;
	var resume = panel.querySelector('[data-svi-bulk-resume]');
	var poll = 0;

	function post(action, extra) {
		var body = new URLSearchParams();

		body.append('action', action);
		body.append('nonce', config.nonce);

		Object.keys(extra || {}).forEach(function (key) {
			var value = extra[key];

			if (Array.isArray(value)) {
				value.forEach(function (item) {
					body.append(key + '[]', item);
				});
				return;
			}

			body.append(key, value);
		});

		return window.fetch(config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		}).then(function (response) {
			return response.json();
		});
	}

	function options() {
		var contexts = [];

		panel.querySelectorAll('[data-svi-bulk-context]').forEach(function (input) {
			if (input.checked) {
				contexts.push(input.value);
			}
		});

		var categories = [];
		var categoryField = panel.querySelector('[data-svi-bulk-category]');

		if (categoryField) {
			Array.prototype.forEach.call(categoryField.selectedOptions || [], function (option) {
				categories.push(option.value);
			});
		}

		var attributeField = panel.querySelector('[data-svi-bulk-attribute-select]');

		return {
			scope: panel.querySelector('[data-svi-bulk-scope]').value,
			categories: categories,
			attribute: attributeField ? attributeField.value : '',
			contexts: contexts,
			hide_parent: panel.querySelector('[data-svi-bulk-hide-parent]').checked ? '1' : '0',
			overwrite: panel.querySelector('[data-svi-bulk-overwrite]').checked ? '1' : '0'
		};
	}

	function showLabel(text) {
		if (!status) {
			return;
		}

		status.textContent = text;
	}

	function paint(data) {
		if (!status) {
			return;
		}

		status.textContent = data.label || '';
		status.setAttribute('data-state', data.status || 'idle');

		var total = parseInt(data.total || 0, 10);
		var processed = parseInt(data.processed || 0, 10);
		var percent = total > 0 ? Math.min(100, Math.round((processed / total) * 100)) : 0;

		if (bar && barFill) {
			bar.hidden = !total;
			barFill.style.width = percent + '%';
		}

		if (resume) {
			resume.hidden = 'running' !== data.status;
		}
	}

	function pollStatus() {
		window.clearTimeout(poll);

		post('svi_sv_bulk_status', {}).then(function (response) {
			if (!response || !response.success) {
				return;
			}

			paint(response.data);

			if ('running' === response.data.status) {
				poll = window.setTimeout(pollStatus, 3000);
			}
		});
	}

	function refreshRows() {
		var scope = panel.querySelector('[data-svi-bulk-scope]').value;

		panel.querySelector('[data-svi-bulk-categories]').hidden = 'category' !== scope;
		panel.querySelector('[data-svi-bulk-attribute]').hidden = 'attribute' !== scope;
	}

	panel.querySelector('[data-svi-bulk-scope]').addEventListener('change', refreshRows);
	refreshRows();

	panel.querySelector('[data-svi-bulk-dry]').addEventListener('click', function (event) {
		event.preventDefault();
		showLabel(config.strings.dryRun);

		post('svi_sv_bulk_dry_run', options()).then(function (response) {
			showLabel(response && response.success ? response.data.message : (response && response.data && response.data.message) || config.strings.error);
		}).catch(function () {
			showLabel(config.strings.error);
		});
	});

	panel.querySelector('[data-svi-bulk-start]').addEventListener('click', function (event) {
		event.preventDefault();
		showLabel(config.strings.starting);

		post('svi_sv_bulk_start', options()).then(function (response) {
			if (!response || !response.success) {
				showLabel((response && response.data && response.data.message) || config.strings.error);
				return;
			}

			paint(response.data);
			pollStatus();
		}).catch(function () {
			showLabel(config.strings.error);
		});
	});

	var resumeButton = panel.querySelector('[data-svi-bulk-resume]');

	if (resumeButton) {
		resumeButton.addEventListener('click', function (event) {
			event.preventDefault();

			post('svi_sv_bulk_resume', {}).then(function (response) {
				if (response && response.success) {
					paint(response.data);
					pollStatus();
				}
			});
		});
	}

	if (status && 'running' === status.getAttribute('data-state')) {
		pollStatus();
	}
})();
