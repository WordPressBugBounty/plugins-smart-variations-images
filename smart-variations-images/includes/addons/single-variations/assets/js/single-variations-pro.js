/**
 * Variations as products — PRO card behaviour.
 *
 *  - the second image of the variation gallery on hover;
 *  - choosing the remaining attribute (for example the size) on a group card;
 *  - adding the chosen variation to the cart without leaving the shop.
 */
(function () {
	'use strict';

	var strings = window.sviSV || {};

	function ready(fn) {
		if ('loading' !== document.readyState) {
			fn();
			return;
		}

		document.addEventListener('DOMContentLoaded', fn);
	}

	function cardImage(card) {
		return card.querySelector('img');
	}

	function bindHover(card) {
		var holder = card.querySelector('[data-svi-hover]');
		var image = cardImage(card);
		var src = holder ? holder.getAttribute('data-svi-hover') : '';

		if (!image || !src) {
			return;
		}

		card.addEventListener('mouseenter', function () {
			if (!image.getAttribute('data-svi-original')) {
				image.setAttribute('data-svi-original', image.getAttribute('src') || '');
				image.setAttribute('data-svi-original-srcset', image.getAttribute('srcset') || '');
			}

			image.setAttribute('src', src);
			image.removeAttribute('srcset');
		});

		card.addEventListener('mouseleave', function () {
			var original = image.getAttribute('data-svi-original');

			if (!original) {
				return;
			}

			image.setAttribute('src', original);

			var srcset = image.getAttribute('data-svi-original-srcset');

			if (srcset) {
				image.setAttribute('srcset', srcset);
			}
		});
	}

	function replaceFragments(fragments) {
		Object.keys(fragments).forEach(function (selector) {
			var target = document.querySelector(selector);

			if (target) {
				target.outerHTML = fragments[selector];
			}
		});
	}

	function flash(button, text) {
		var original = button.getAttribute('data-svi-label') || button.textContent;

		button.setAttribute('data-svi-label', original);
		button.textContent = text;

		window.setTimeout(function () {
			button.textContent = original;
		}, 2500);
	}

	function addToCart(button, variationId) {
		if (!strings.ajaxUrl || !variationId) {
			return false;
		}

		var body = new URLSearchParams();
		body.append('product_id', String(variationId));
		body.append('quantity', '1');

		button.classList.add('svi-sv-loading');

		window.fetch(strings.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (data) {
				if (!data || !data.fragments) {
					throw new Error('no fragments');
				}

				replaceFragments(data.fragments);

				if (window.jQuery) {
					window.jQuery(document.body).trigger('added_to_cart', [data.fragments, data.cart_hash, window.jQuery(button)]);
				}

				flash(button, strings.added || 'Added to cart');
			})
			.catch(function () {
				flash(button, strings.error || 'Error');
			})
			.finally(function () {
				button.classList.remove('svi-sv-loading');
			});

		return true;
	}

	function bindOptions(card) {
		var options = card.querySelector('[data-svi-card-options]');

		if (!options) {
			return;
		}

		var button = card.querySelector('.add_to_cart_button, .svi-sv-card-button, a.button');
		var price = card.querySelector('.price');
		var image = cardImage(card);

		options.addEventListener('click', function (event) {
			var swatch = event.target.closest('.svi-sv-option');

			if (!swatch) {
				return;
			}

			event.preventDefault();

			if ('0' === swatch.getAttribute('data-in-stock')) {
				window.location.href = swatch.getAttribute('data-url');
				return;
			}

			options.querySelectorAll('.svi-sv-option').forEach(function (item) {
				item.classList.remove('is-selected');
				item.setAttribute('aria-checked', 'false');
			});

			swatch.classList.add('is-selected');
			swatch.setAttribute('aria-checked', 'true');

			if (price && swatch.getAttribute('data-price')) {
				price.innerHTML = swatch.getAttribute('data-price');
			}

			var hover = card.querySelector('[data-svi-hover]');
			var nextImage = swatch.getAttribute('data-image');

			if (nextImage) {
				if (hover) {
					hover.setAttribute('data-svi-hover', nextImage);
				}

				if (image) {
					image.setAttribute('src', nextImage);
					image.removeAttribute('srcset');
				}
			}

			if (!button) {
				window.location.href = swatch.getAttribute('data-url');
				return;
			}

			button.setAttribute('data-product_id', swatch.getAttribute('data-variation'));

			if (swatch.getAttribute('data-url')) {
				button.setAttribute('href', swatch.getAttribute('data-url'));
			}

			button.textContent = strings.add || 'Add to cart';
		});

		if (!button) {
			return;
		}

		button.addEventListener('click', function (event) {
			var variationId = parseInt(button.getAttribute('data-product_id') || '0', 10);

			if (!variationId || !strings.ajaxUrl) {
				return;
			}

			if (addToCart(button, variationId)) {
				event.preventDefault();
			}
		});
	}

	function cardOf(element) {
		return element.closest('.svi-sv-card, li.product, .product-small, .type-product, .product') || element.parentElement;
	}

	ready(function () {
		// Themes name the card differently (Divi, Flatsome, block themes), so the
		// markers we printed decide which element is the card.
		document.querySelectorAll('[data-svi-hover], [data-svi-card-options]').forEach(function (marker) {
			var card = cardOf(marker);

			if (!card || card.getAttribute('data-svi-bound')) {
				return;
			}

			card.setAttribute('data-svi-bound', '1');
			bindHover(card);
			bindOptions(card);
		});
	});
})();
