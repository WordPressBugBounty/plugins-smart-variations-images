/**
 * SVI setup wizard: step navigation, AJAX saving, live preview and promo copy.
 */
(function () {
	'use strict';

	var strings = window.sviWizard || {};
	var root = document.querySelector('[data-svi-wizard]');

	if (!root) {
		return;
	}

	var form = root.querySelector('[data-svi-wizard-form]');
	var stepField = root.querySelector('[data-svi-step-field]');
	var totalSteps = parseInt(strings.steps, 10) || 5;
	var steps = Array.prototype.slice.call(root.querySelectorAll('[data-svi-step]'));
	var items = Array.prototype.slice.call(root.querySelectorAll('.svi-wizard__step-item'));
	var busy = false;

	function currentStep() {
		return parseInt(root.getAttribute('data-step'), 10) || 1;
	}

	function panel(step) {
		return root.querySelector('[data-svi-step="' + step + '"]');
	}

	function setIndicator(step) {
		items.forEach(function (item, index) {
			var number = index + 1;
			var dot = item.querySelector('.svi-wizard__dot');

			item.classList.toggle('is-current', number === step);
			item.classList.toggle('is-done', number < step);

			if (number === step) {
				item.setAttribute('aria-current', 'step');
			} else {
				item.removeAttribute('aria-current');
			}

			if (dot) {
				dot.textContent = number < step ? '\u2713' : String(number);
			}
		});
	}

	function updateUrl(step) {
		var url = new URL(window.location.href);
		url.searchParams.set('step', String(step));
		window.history.replaceState({ sviStep: step }, '', url.toString());
	}

	function goTo(step, push) {
		step = Math.min(totalSteps, Math.max(1, step));

		steps.forEach(function (section) {
			var isTarget = parseInt(section.getAttribute('data-svi-step'), 10) === step;

			section.hidden = !isTarget;

			if (isTarget) {
				// Re-run the fade so the panel animates in again.
				section.style.animation = 'none';
				void section.offsetWidth;
				section.style.animation = '';
			}
		});

		root.setAttribute('data-step', String(step));

		if (stepField) {
			stepField.value = String(step);
		}

		setIndicator(step);

		if (push) {
			window.history.pushState({ sviStep: step }, '', window.location.href.split('#')[0]);
			updateUrl(step);
		}

		var target = panel(step);
		var title = target ? target.querySelector('.svi-step__title') : null;

		if (title) {
			title.focus({ preventScroll: true });
		}

		window.scrollTo({ top: 0, behavior: 'smooth' });
	}

	function setBusy(state) {
		busy = state;

		root.querySelectorAll('[data-svi-next], [data-svi-skip], [data-svi-back]').forEach(function (button) {
			button.disabled = state;
		});

		var next = root.querySelector('[data-svi-step="' + currentStep() + '"] [data-svi-next]');

		if (!next) {
			return;
		}

		if (state) {
			next.setAttribute('data-label', next.textContent);
			next.textContent = strings.saving || 'Saving\u2026';
			next.classList.add('is-busy');
		} else {
			if (next.getAttribute('data-label')) {
				next.textContent = next.getAttribute('data-label');
			}

			next.classList.remove('is-busy');
		}
	}

	function save(skip, done) {
		var step = currentStep();
		var body = new URLSearchParams();

		body.set('action', strings.action);
		body.set('nonce', strings.nonce);
		body.set('step', String(step));

		if (skip) {
			body.set('svi_wizard_skip', '1');
		} else if (form) {
			new FormData(form).forEach(function (value, key) {
				if (key === 'action' || key === '_wpnonce' || key === 'step' || key === 'svi_wizard_next') {
					return;
				}

				body.set(key, value);
			});
		}

		fetch(strings.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (payload) {
				if (!payload || !payload.success) {
					throw new Error('save failed');
				}

				done(payload.data || {});
			})
			.catch(function () {
				done(null);
			});
	}

	function notice(message) {
		var box = root.querySelector('.svi-wizard__error');

		if (!box) {
			box = document.createElement('p');
			box.className = 'svi-wizard__error';
			box.setAttribute('role', 'alert');
			box.style.color = '#b91c1c';
			form.appendChild(box);
		}

		box.textContent = message;
	}

	function clearNotice() {
		var box = root.querySelector('.svi-wizard__error');

		if (box) {
			box.textContent = '';
		}
	}

	root.addEventListener('click', function (event) {
		var next = event.target.closest('[data-svi-next]');
		var skip = event.target.closest('[data-svi-skip]');
		var back = event.target.closest('[data-svi-back]');

		if (busy) {
			if (next || skip || back) {
				event.preventDefault();
			}

			return;
		}

		if (next) {
			event.preventDefault();
			clearNotice();
			setBusy(true);

			save(false, function (data) {
				setBusy(false);

				if (!data) {
					notice(strings.errorText || 'Error');
					return;
				}

				var step = currentStep();

				if (step + 1 > totalSteps) {
					window.location.href = strings.settings;
					return;
				}

				goTo(step + 1, true);
			});

			return;
		}

		if (skip) {
			event.preventDefault();
			clearNotice();
			setBusy(true);

			save(true, function (data) {
				setBusy(false);

				var step = currentStep();

				if (!data) {
					notice(strings.errorText || 'Error');
					return;
				}

				if (step + 1 > totalSteps) {
					window.location.href = strings.settings;
					return;
				}

				goTo(step + 1, true);
			});

			return;
		}

		if (back) {
			event.preventDefault();
			goTo(currentStep() - 1, true);
		}
	});

	window.addEventListener('popstate', function (event) {
		var step = event.state && event.state.sviStep
			? event.state.sviStep
			: parseInt(new URL(window.location.href).searchParams.get('step'), 10);

		goTo(step || 1, false);
	});

	// Live preview: swatch style and out-of-stock treatment update instantly.
	var preview = root.querySelector('[data-svi-preview]');

	if (preview) {
		root.querySelectorAll('input[name="swatch_style"]').forEach(function (input) {
			input.addEventListener('change', function () {
				preview.setAttribute('data-style', input.value);
			});
		});

		root.querySelectorAll('input[name="oos"]').forEach(function (input) {
			input.addEventListener('change', function () {
				preview.setAttribute('data-oos', input.value);
			});
		});
	}

	// Coupon copy: two seconds of feedback, nothing stored.
	root.querySelectorAll('[data-svi-copy]').forEach(function (button) {
		var idle = button.textContent;

		button.addEventListener('click', function () {
			var value = button.getAttribute('data-svi-copy') || '';
			var done = function () {
				button.textContent = button.getAttribute('data-svi-copied') || strings.promoCopied || 'Copied';
				window.setTimeout(function () {
					button.textContent = idle;
				}, 2000);
			};

			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(value).then(done, done);
				return;
			}

			var field = document.createElement('textarea');
			field.value = value;
			document.body.appendChild(field);
			field.select();

			try {
				document.execCommand('copy');
			} catch (error) {
				// Nothing to report: the code is visible next to the button.
			}

			document.body.removeChild(field);
			done();
		});
	});

	setIndicator(currentStep());
})();
