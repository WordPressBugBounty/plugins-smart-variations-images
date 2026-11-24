/**
 * Copy link to this variation.
 *
 * Reads the current variation selects, builds the product URL with the
 * attribute_* query args and copies it. SVI already preselects the variation
 * and loads the matching gallery when that URL is opened.
 */
(function () {
	'use strict';

	var strings = window.sviCopyLink || {};

	function findForm() {
		var form = document.querySelector('form.variations_form');

		if (form) {
			return form;
		}

		var select = document.querySelector('select[name^="attribute_"]');

		return select ? select.closest('form') : null;
	}

	function buttonMarkup() {
		var wrap = document.createElement('div');

		wrap.className = 'svi-copy-link';
		wrap.setAttribute('data-svi-copy-link', '');
		wrap.innerHTML = '<button type="button" class="svi-copy-link__button">'
			+ '<span class="svi-copy-link__icon" aria-hidden="true">'
			+ '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="16" height="16" focusable="false" aria-hidden="true">'
			+ '<path d="M12.5 3.5a3 3 0 0 1 4.2 4.2l-2.8 2.8-1.4-1.4 2.8-2.8a1 1 0 0 0-1.4-1.4l-2.8 2.8-1.4-1.4z" fill="currentColor"/>'
			+ '<path d="M7.5 16.5a3 3 0 0 1-4.2-4.2l2.8-2.8 1.4 1.4-2.8 2.8a1 1 0 0 0 1.4 1.4l2.8-2.8 1.4 1.4z" fill="currentColor"/>'
			+ '<path d="M7.1 11.5l4.4-4.4 1.4 1.4-4.4 4.4z" fill="currentColor"/>'
			+ '</svg></span>'
			+ '<span class="svi-copy-link__label"></span>'
			+ '</button>'
			+ '<span class="svi-copy-link__status" role="status" aria-live="polite"></span>';

		wrap.querySelector('.svi-copy-link__label').textContent = strings.label || 'Copy link to this variation';

		return wrap;
	}

	/**
	 * Themes that do not fire woocommerce_after_variations_form still get the
	 * button: it is added after the form when the server did not print it.
	 */
	function ensureButton() {
		var existing = document.querySelector('[data-svi-copy-link]');

		if (existing) {
			return existing;
		}

		var form = findForm();

		if (!form) {
			return null;
		}

		var wrap = buttonMarkup();
		form.insertAdjacentElement('afterend', wrap);

		return wrap;
	}

	function buildUrl(form) {
		var root = form || document;
		var params = new URLSearchParams();
		var selects = root.querySelectorAll('select[name^="attribute_"]');

		for (var i = 0; i < selects.length; i++) {
			if (selects[i].value) {
				params.append(selects[i].name, selects[i].value);
			}
		}

		var base = window.location.origin + window.location.pathname;
		var query = params.toString();

		return query ? base + '?' + query : base;
	}

	function copyText(text) {
		if (window.navigator.clipboard && window.isSecureContext) {
			return window.navigator.clipboard.writeText(text);
		}

		return new Promise(function (resolve, reject) {
			var area = document.createElement('textarea');

			area.value = text;
			area.setAttribute('readonly', '');
			area.style.position = 'fixed';
			area.style.top = '-1000px';
			document.body.appendChild(area);
			area.select();

			var copied = false;

			try {
				copied = document.execCommand('copy');
			} catch (error) {
				copied = false;
			}

			document.body.removeChild(area);
			copied ? resolve() : reject(new Error('copy-failed'));
		});
	}

	function feedback(wrap, ok) {
		var label = wrap.querySelector('.svi-copy-link__label');
		var status = wrap.querySelector('.svi-copy-link__status');
		var original = strings.label || 'Copy link to this variation';

		if (!ok) {
			if (status) {
				status.textContent = strings.failed || '';
			}

			return;
		}

		if (label) {
			label.textContent = strings.copied || 'Link copied';
		}

		if (status) {
			status.textContent = strings.copied || '';
		}

		window.setTimeout(function () {
			if (label) {
				label.textContent = original;
			}

			if (status) {
				status.textContent = '';
			}
		}, 2500);
	}

	document.addEventListener('click', function (event) {
		var button = event.target.closest ? event.target.closest('.svi-copy-link__button') : null;

		if (!button) {
			return;
		}

		event.preventDefault();

		var wrap = button.closest('[data-svi-copy-link]');
		var form = findForm();

		copyText(buildUrl(form)).then(
			function () {
				if (wrap) {
					feedback(wrap, true);
				}
			},
			function () {
				if (wrap) {
					feedback(wrap, false);
				}
			}
		);
	});

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', ensureButton);
	} else {
		ensureButton();
	}

	// Block themes hydrate the add-to-cart area after this file runs.
	window.addEventListener('load', function () {
		window.setTimeout(ensureButton, 300);
	});
}());
