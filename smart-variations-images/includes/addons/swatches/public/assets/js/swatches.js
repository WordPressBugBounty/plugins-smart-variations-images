/**
 * SVI Swatches Frontend Logic
 * 
 * Handles interaction between visual swatches and hidden WooCommerce select fields.
 */
(function ($) {
    'use strict';

    const SVI_Swatches = {
        init: function () {
            // Bind Click Events
            $(document).on('click', '.svi-swatch:not(.svi-disabled)', this.handleSwatchClick);

            // Keyboard support (roving tabindex inside each radiogroup).
            $(document).on('keydown', '.svi-swatch', this.handleKeydown);

            // Availability: WooCommerce events cover the classic path, and the
            // extra hooks cover products above the AJAX variation threshold,
            // where WooCommerce stops refreshing the attribute selects.
            $(document).on('woocommerce_update_variation_values', '.variations_form', this.handleVariationUpdates);
            $(document).on('found_variation reset_data check_variations', '.variations_form', this.scheduleAvailabilityUpdate);
            $(document).on('change', '.variations_form select', this.scheduleAvailabilityUpdate);

            // Listen for Reset
            $(document).on('click', '.reset_variations', this.handleReset);

            // Catalog Add to Cart
            $(document).on('click', '.svi-catalog-add-to-cart', this.handleCatalogAddToCart);

            // Catalog cards: swap the product image with the value's gallery image.
            this.initCatalogImages();

            // Filter widget: mark the list rows so the theme's bullet padding can
            // be dropped even where :has() is not supported.
            this.markFilterRows();

            // Product page: preselect the variation coming from a link.
            this.preselectFromQuery();

            // Slider Navigation
            this.initSlider();
            $(window).on('resize', this.initSlider);
            $(document).on('click', '.svi-nav-prev', this.handleSliderPrev);
            $(document).on('click', '.svi-nav-next', this.handleSliderNext);
            $(document).on('scroll', '.svi-overflow-slider', this.handleSliderScroll); // Capture scroll on wrapper
        },

        initSlider: function () {
            $('.svi-slider-container').each(function () {
                SVI_Swatches.updateSliderArrows($(this));
            });
        },

        handleSliderPrev: function (e) {
            e.preventDefault();
            const $btn = $(this);
            const $container = $btn.closest('.svi-slider-container');
            const $wrapper = $container.find('.svi-overflow-slider');
            const scrollAmount = $wrapper.width() / 1.5; // Scroll 2/3 of visibility

            $wrapper.animate({ scrollLeft: '-=' + scrollAmount }, 300, function () {
                SVI_Swatches.updateSliderArrows($container);
            });
        },

        handleSliderNext: function (e) {
            e.preventDefault();
            const $btn = $(this);
            const $container = $btn.closest('.svi-slider-container');
            const $wrapper = $container.find('.svi-overflow-slider');
            const scrollAmount = $wrapper.width() / 1.5;

            $wrapper.animate({ scrollLeft: '+=' + scrollAmount }, 300, function () {
                SVI_Swatches.updateSliderArrows($container);
            });
        },

        handleSliderScroll: function (e) {
            // Debounce or just simple check? 
            // Simple check is fine for now, wrapper scroll event
            const $wrapper = $(this);
            const $container = $wrapper.closest('.svi-slider-container');
            SVI_Swatches.updateSliderArrows($container);
        },

        updateSliderArrows: function ($container) {
            const $wrapper = $container.find('.svi-overflow-slider');
            if ($wrapper.length === 0) return;

            const el = $wrapper[0];
            const $prev = $container.find('.svi-nav-prev');
            const $next = $container.find('.svi-nav-next');

            // Check Overflow
            if (el.scrollWidth > el.clientWidth) {
                $prev.addClass('visible');
                $next.addClass('visible');

                // Check Positions
                if (el.scrollLeft <= 5) { // Tolerance
                    $prev.addClass('disabled');
                } else {
                    $prev.removeClass('disabled');
                }

                if (el.scrollLeft + el.clientWidth >= el.scrollWidth - 5) {
                    $next.addClass('disabled');
                } else {
                    $next.removeClass('disabled');
                }
            } else {
                $prev.removeClass('visible');
                $next.removeClass('visible');
            }
        },

        handleSwatchClick: function (e) {
            const $swatch = $(this);
            const $wrapper = $swatch.closest('.svi-swatches-wrapper');
            const attribute = $wrapper.data('attribute');
            const value = $swatch.data('value');
            const clearOnReselect = $wrapper.data('clear') === true; // bool check

            // Handle Catalog Clicks
            if ($wrapper.hasClass('svi-catalog-wrapper')) {
                // Some themes wrap the loop title in the product link; keep the
                // anchor from firing a second navigation.
                e.preventDefault();
                e.stopPropagation();

                const permalink = $wrapper.data('permalink');
                const showCart = $wrapper.data('show-cart') === true || $wrapper.data('show-cart') === 'true';

                if (showCart) {
                    // Update Visuals
                    $wrapper.find('.svi-swatch').removeClass('selected');
                    $swatch.addClass('selected');
                    SVI_Swatches.updateSelectedLabel($wrapper, $swatch.data('label'));
                    SVI_Swatches.followCatalogSelection($wrapper, $swatch);

                    // Update Add to Cart Button
                    const $container = $wrapper.closest('.svi-catalog-swatches');
                    const $btn = $container.find('.svi-catalog-add-to-cart');

                    if ($btn.length && permalink) {
                        let params = [];
                        $container.find('.svi-swatches-wrapper').each(function () {
                            const $w = $(this);
                            const $sel = $w.find('.svi-swatch.selected');
                            if ($sel.length) {
                                const attr = $w.data('attribute');
                                const val = $sel.data('value');
                                params.push('attribute_' + attr + '=' + val);
                            }
                        });

                        const sep = permalink.includes('?') ? '&' : '?';
                        const url = permalink + sep + params.join('&');
                        $btn.attr('href', url).addClass('visible');
                    }
                    return;
                }

                // "Link to the preselected variation" off: stay on the card and
                // only mark the choice, so the image-on-click option can work.
                const linkVariation = $wrapper.data('link') === true || $wrapper.data('link') === 'true';

                if (!linkVariation) {
                    $wrapper.find('.svi-swatch').removeClass('selected');
                    $swatch.addClass('selected');
                    SVI_Swatches.followCatalogSelection($wrapper, $swatch);
                    return;
                }

                // The server builds the preselected URL so it also works for
                // custom (non taxonomy) attributes.
                const cardUrl = $swatch.attr('data-url');

                if (cardUrl) {
                    window.location = cardUrl;
                    return;
                }

                if (permalink) {
                    const sep = permalink.includes('?') ? '&' : '?';
                    window.location = permalink + sep + 'attribute_' + attribute + '=' + value;
                    return;
                }
            }

            const $form = $wrapper.closest('form.variations_form');
            if ($form.length === 0) return; // Not in a form?

            e.preventDefault();

            // Find hidden select. Custom attribute names keep their label case
            // ("Print") while the select is lower case ("attribute_print"), so
            // the last lookup compares names case-insensitively.
            let $select = $form.find('select[name="' + attribute + '"]');
            if ($select.length === 0) {
                $select = $form.find('select[name="attribute_' + attribute + '"]');
            }
            if ($select.length === 0) {
                const wanted = ('attribute_' + String(attribute).replace(/^attribute_/, '')).toLowerCase();
                $select = $form.find('select[name^="attribute_"]').filter(function () {
                    return String(this.name).toLowerCase() === wanted;
                });
            }

            if ($select.length === 0) return;

            // Deselect if already selected AND clear enabled
            if ($swatch.hasClass('selected') && clearOnReselect) {
                $select.val('').trigger('change');
                $swatch.removeClass('selected').attr('aria-checked', 'false');
                SVI_Swatches.updateSelectedLabel($wrapper, '');
                SVI_Swatches.setRovingFocus($wrapper);
                return;
            }

            // Select new value
            $select.val(value).trigger('change');

            // Update UI State immediately
            $wrapper.find('.svi-swatch').removeClass('selected').attr('aria-checked', 'false');
            $swatch.addClass('selected').attr('aria-checked', 'true');

            // Update Label
            const label = $swatch.data('label');
            SVI_Swatches.updateSelectedLabel($wrapper, label);

            // Keep keyboard focus and availability in sync.
            SVI_Swatches.setRovingFocus($wrapper);
            SVI_Swatches.updateAvailability($form);
        },

        /**
         * Move the selected-value span next to the attribute name when the
         * standard variations table provides a label cell.
         *
         * Idempotent: the span is stored on the wrapper, so it is only moved and
         * cached once.
         */
        placeSelectedLabels: function () {
            $('.svi-swatches-wrapper').not('.svi-catalog-wrapper').each(function () {
                const $wrapper = $(this);

                if ($wrapper.data('sviSelectedLabel')) {
                    return;
                }

                const $span = $wrapper.find('.svi-attribute-label-selected').first();

                if (!$span.length) {
                    return;
                }

                const $row = $wrapper.closest('tr');

                if ($row.length) {
                    const $label = $row.children('.label').first().find('label').first();

                    if ($label.length) {
                        $label.append($span);
                        $span.addClass('svi-in-label');
                    }
                }

                $wrapper.data('sviSelectedLabel', $span);
            });
        },

        /**
         * Selected-value span of a wrapper, wherever it currently lives.
         *
         * @param {jQuery} $wrapper Swatch wrapper.
         * @return {jQuery} The span, possibly empty.
         */
        getSelectedLabelEl: function ($wrapper) {
            const $stored = $wrapper.data('sviSelectedLabel');

            if ($stored && $stored.jquery && $stored.length) {
                return $stored;
            }

            return $wrapper.find('.svi-attribute-label-selected').first();
        },

        updateSelectedLabel: function ($wrapper, label) {
            const $labelSpan = SVI_Swatches.getSelectedLabelEl($wrapper);

            if (!$labelSpan.length) {
                return;
            }

            if (label) {
                const inLabel = $labelSpan.hasClass('svi-in-label');

                $labelSpan
                    .text(inLabel ? ': ' + label : label)
                    .attr('data-value', label)
                    .removeAttr('hidden')
                    .css('display', '');

                // The label text changed height, so the optical centring is stale.
                if (inLabel) {
                    SVI_Swatches.alignLabels();
                }
            } else {
                $labelSpan.text('').removeAttr('data-value').attr('hidden', 'hidden');
            }
        },

        /**
         * Read the availability map published by PHP for a product.
         *
         * @param {number} productId Product ID.
         * @return {Object|null} Map with `attributes` and `variations`, or null.
         */
        getAvailabilityMap: function (productId, $form) {
            let availability = null;

            if (window.wcsvi && window.wcsvi.options && productId) {
                const productOptions = window.wcsvi.options[productId];

                if (productOptions && productOptions.addons && productOptions.addons.swatches) {
                    availability = productOptions.addons.swatches.availability;
                }
            }

            // Builds without the SVI runtime carry the map on the wrapper.
            if (!availability && $form && $form.length) {
                const raw = $form.find('.svi-swatches-wrapper[data-availability]').first().attr('data-availability');

                if (raw) {
                    try {
                        availability = JSON.parse(raw);
                    } catch (error) {
                        availability = null;
                    }
                }
            }

            if (!availability || !Array.isArray(availability.variations) || availability.variations.length === 0) {
                return null;
            }

            return availability;
        },

        /**
         * Current value of every attribute select in the form.
         *
         * @param {jQuery} $form Variations form.
         * @return {Object} Map of select name to value.
         */
        getSelections: function ($form) {
            const selections = {};

            $form.find('select[name^="attribute_"]').each(function () {
                selections[$(this).attr('name')] = $(this).val() || '';
            });

            return selections;
        },

        /**
         * Real select name for a wrapper attribute.
         *
         * Custom attributes carry the label case in data-attribute ("Print")
         * but the select is lower case ("attribute_print"), so the lookup is
         * case-insensitive and falls back to the constructed name.
         */
        resolveSelectName: function ($form, attribute) {
            const raw = String(attribute).replace(/^attribute_/, '');
            const direct = $form.find('select[name="attribute_' + raw + '"]');

            if (direct.length) {
                return direct.attr('name');
            }

            const wanted = ('attribute_' + raw).toLowerCase();
            let found = '';

            $form.find('select[name^="attribute_"]').each(function () {
                if (String(this.name).toLowerCase() === wanted) {
                    found = this.name;
                }
            });

            return found || ('attribute_' + raw);
        },

        /**
         * Debounced availability refresh.
         */
        scheduleAvailabilityUpdate: function () {
            const $form = $(this);

            window.clearTimeout(SVI_Swatches._availabilityTimer);
            SVI_Swatches._availabilityTimer = window.setTimeout(function () {
                SVI_Swatches.updateAvailability($form);
            }, 30);
        },

        /**
         * Does a variation match the given selections?
         *
         * Empty selections act as wildcards, which is what makes it possible to
         * evaluate a candidate value for one attribute against the current
         * choice of the others.
         *
         * @param {Object} variation Variation entry.
         * @param {Object} selections Selections map.
         * @return {boolean}
         */
        matchesSelections: function (variation, selections) {
            const attributes = variation.a || {};

            for (const name in selections) {
                if (!Object.prototype.hasOwnProperty.call(selections, name)) {
                    continue;
                }

                const wanted = selections[name];

                if (!wanted) {
                    continue;
                }

                const value = attributes[name];

                // An empty stored value means the variation accepts any value
                // for that attribute (WooCommerce "any" variations).
                if (value && value !== wanted) {
                    return false;
                }
            }

            return true;
        },

        /**
         * Recompute every swatch state from the availability map.
         *
         * Distinguishes three states:
         *  - available: at least one matching variation can be bought;
         *  - out of stock: the combination exists but none of the matching
         *    variations is in stock or purchasable;
         *  - missing: no variation exists for that combination.
         *
         * @param {jQuery} $form Variations form.
         */
        updateAvailability: function ($form) {
            if (!$form || !$form.length) {
                return;
            }

            const productId = parseInt($form.data('product_id'), 10);
            const map = this.getAvailabilityMap(productId, $form);
            const selections = this.getSelections($form);

            $form.find('.svi-swatches-wrapper').each(function () {
                const $wrapper = $(this);
                const attribute = $wrapper.data('attribute');
                const selectName = SVI_Swatches.resolveSelectName($form, attribute);
                const oosBehavior = $wrapper.data('oos') || 'blur';

                $wrapper.find('.svi-swatch').each(function () {
                    const $swatch = $(this);
                    const value = $swatch.data('value');
                    let state = 'available';

                    if (map) {
                        const candidate = $.extend({}, selections);
                        candidate[selectName] = value;

                        const matching = map.variations.filter(function (variation) {
                            return SVI_Swatches.matchesSelections(variation, candidate);
                        });

                        if (matching.length === 0) {
                            state = 'missing';
                        } else {
                            const buyable = matching.some(function (variation) {
                                return variation.stock && variation.buy;
                            });

                            if (!buyable) {
                                state = 'oos';
                            }
                        }
                    } else {
                        // Fallback for stores without the map: mirror WooCommerce
                        // by reading the (possibly refreshed) select options.
                        const $select = $form.find('select[name="' + selectName + '"]');
                        const exists = $select.find('option').filter(function () {
                            return $(this).attr('value') === String(value);
                        }).length > 0;

                        state = exists ? 'available' : 'missing';
                    }

                    SVI_Swatches.applySwatchState($swatch, state, oosBehavior);
                });
            });

            this.syncAria($form);
            this.refreshRovingFocus($form);
        },

        /**
         * Apply the visual + ARIA state to a single swatch.
         *
         * @param {jQuery} $swatch Swatch element.
         * @param {string} state   available|oos|missing.
         * @param {string} oosBehavior Configured out-of-stock decoration.
         */
        applySwatchState: function ($swatch, state, oosBehavior) {
            $swatch.removeClass('svi-disabled svi-oos-blur svi-oos-dim svi-oos-cross svi-oos-hide');

            if (state === 'available') {
                $swatch.removeAttr('aria-disabled');
                $swatch.attr('tabindex', '-1');
                return;
            }

            $swatch.addClass('svi-disabled').attr('aria-disabled', 'true');

            if (state === 'oos') {
                $swatch.addClass('svi-oos-' + (oosBehavior || 'blur'));
            }
        },

        /**
         * Keep aria-checked in sync with the hidden selects.
         *
         * @param {jQuery} $form Variations form.
         */
        syncAria: function ($form) {
            $form.find('.svi-swatches-wrapper').each(function () {
                const $wrapper = $(this);
                const attribute = String($wrapper.data('attribute')).replace(/^attribute_/, '');
                const $select = $form.find('select[name="' + SVI_Swatches.resolveSelectName($form, $wrapper.data('attribute')) + '"]');
                const current = $select.val() || '';

                $wrapper.find('.svi-swatch').each(function () {
                    const $swatch = $(this);
                    const isSelected = current !== '' && String($swatch.data('value')) === String(current);

                    $swatch.attr('aria-checked', isSelected ? 'true' : 'false');
                    $swatch.toggleClass('selected', isSelected);
                });

                const $selected = $wrapper.find('.svi-swatch.selected').first();

                SVI_Swatches.updateSelectedLabel($wrapper, $selected.length ? $selected.data('label') : '');
            });
        },

        /**
         * Give each radiogroup exactly one tab stop.
         *
         * @param {jQuery} $form Variations form.
         */
        refreshRovingFocus: function ($form) {
            $form.find('.svi-swatches-wrapper').each(function () {
                SVI_Swatches.setRovingFocus($(this));
            });
        },

        /**
         * Roving tabindex inside one wrapper.
         *
         * @param {jQuery} $wrapper Swatch wrapper.
         */
        setRovingFocus: function ($wrapper) {
            const $enabled = $wrapper.find('.svi-swatch:not(.svi-disabled)').filter(function () {
                return $(this).css('display') !== 'none';
            });

            const $all = $wrapper.find('.svi-swatch');

            if ($enabled.length === 0) {
                $all.attr('tabindex', '-1');
                return;
            }

            let $target = $enabled.filter('.selected').first();

            if ($target.length === 0) {
                $target = $enabled.first();
            }

            $all.attr('tabindex', '-1');
            $target.attr('tabindex', '0');
        },

        /**
         * Keyboard interaction for the radiogroup.
         *
         * Arrows, Home and End move focus and select the focused value, skipping
         * disabled and out-of-stock swatches. Space and Enter select the focused
         * swatch.
         */
        handleKeydown: function (e) {
            const $swatch = $(this);
            const $wrapper = $swatch.closest('.svi-swatches-wrapper');
            const key = e.key;

            if (['ArrowRight', 'ArrowDown', 'ArrowLeft', 'ArrowUp', 'Home', 'End', ' ', 'Enter'].indexOf(key) === -1) {
                return;
            }

            if (key === ' ' || key === 'Enter') {
                e.preventDefault();
                if (!$swatch.hasClass('svi-disabled')) {
                    $swatch.trigger('click');
                }
                return;
            }

            const $enabled = $wrapper.find('.svi-swatch:not(.svi-disabled)').filter(function () {
                return $(this).css('display') !== 'none';
            });

            if ($enabled.length === 0) {
                return;
            }

            e.preventDefault();

            const currentIndex = $enabled.index($swatch);
            let nextIndex = 0;

            if (key === 'Home') {
                nextIndex = 0;
            } else if (key === 'End') {
                nextIndex = $enabled.length - 1;
            } else if (key === 'ArrowRight' || key === 'ArrowDown') {
                nextIndex = currentIndex < 0 ? 0 : (currentIndex + 1) % $enabled.length;
            } else {
                nextIndex = currentIndex <= 0 ? $enabled.length - 1 : currentIndex - 1;
            }

            const $next = $enabled.eq(nextIndex);

            $wrapper.find('.svi-swatch').attr('tabindex', '-1');
            $next.attr('tabindex', '0').trigger('focus');

            // Radiogroup pattern: moving with the arrows also selects. Catalog cards
            // keep focus-only movement, because selecting there can navigate away.
            if (!$wrapper.hasClass('svi-catalog-wrapper') && !$next.hasClass('selected')) {
                $next.trigger('click');
                $next.trigger('focus');
            }
        },

        /**
         * Kept for backwards compatibility with the previous handler name.
         */
        handleVariationUpdates: function (e) {
            SVI_Swatches.updateAvailability($(this));
        },

        /**
         * Tag the layered nav rows that hold a swatch so the theme's bullet
         * padding can be dropped even where :has() is not supported.
         */
        markFilterRows: function () {
            $('.woocommerce-widget-layered-nav-list > li').each(function () {
                const $row = $(this);
                if ($row.children('.svi-filter-term').length) {
                    $row.addClass('svi-filter-row');
                }
            });
        },

        /**
         * Image swapping on shop cards.
         *
         * The original src/srcset are remembered on the element so the swap is
         * reversible without a second request.
         */
        initCatalogImages: function () {
            const self = this;

            $(document).on('mouseenter focusin', '.svi-catalog-wrapper[data-image-action="hover"] .svi-swatch[data-image]', function () {
                self.swapCardImage($(this));
            });

            $(document).on('mouseleave focusout', '.svi-catalog-wrapper[data-image-action="hover"]', function () {
                self.restoreCardImage($(this));
            });

            $(document).on('click', '.svi-catalog-wrapper[data-image-action="click"] .svi-swatch[data-image]', function () {
                const $swatch = $(this);
                const $wrapper = $swatch.closest('.svi-catalog-wrapper');
                const $img = self.getCardImage($wrapper);

                // Clicking the same value again brings the original picture back.
                if ($img.length && $img.attr('src') === $swatch.attr('data-image')) {
                    self.restoreCardImage($wrapper, true);
                    return;
                }

                self.swapCardImage($swatch);
            });
        },

        /**
         * Find the product image of the card that holds a swatch wrapper.
         *
         * Themes wrap loop items differently (li.product, wc-block-grid__product,
         * Flatsome's .product-small, custom builders), so walk up the tree until
         * an ancestor contains an image that is not a swatch or a tooltip.
         */
        getCardImage: function ($wrapper) {
            let $node = $wrapper.parent();

            for (let i = 0; i < 6 && $node.length; i++) {
                const $img = $node.find('img').not('.svi-swatch-image').not('.svi-tooltip img').first();

                if ($img.length) {
                    return $img;
                }

                $node = $node.parent();
            }

            return $();
        },

        /**
         * Show the picture of a value the shopper just picked.
         *
         * Hover mode previews on the way in; picking with the keyboard or a tap
         * has no hover first, so the chosen swatch updates the card too. Click
         * mode handles its own toggling and is left alone.
         */
        followCatalogSelection: function ($wrapper, $swatch) {
            if ('hover' !== $wrapper.attr('data-image-action') || !$swatch.attr('data-image')) {
                return;
            }

            this.swapCardImage($swatch);
        },

        swapCardImage: function ($swatch) {            const $img = this.getCardImage($swatch.closest('.svi-catalog-wrapper'));
            const src = $swatch.attr('data-image');

            if (!$img.length || !src) {
                return;
            }

            if (typeof $img.data('sviOriginalSrc') === 'undefined') {
                $img.data('sviOriginalSrc', $img.attr('src'));
                $img.data('sviOriginalSrcset', $img.attr('srcset') || '');
                $img.data('sviOriginalSizes', $img.attr('sizes') || '');
            }

            $img.attr('src', src).removeAttr('srcset').removeAttr('sizes');
            $img.addClass('svi-card-image-swapped');
        },

        /**
         * Put the card picture back.
         *
         * A value the shopper picked outranks the hover preview: leaving the
         * card keeps the picture of that value instead of falling back to the
         * default one. `forceOriginal` is for the click mode, where clicking
         * the swapped swatch again is meant to clear the swap.
         */
        restoreCardImage: function ($wrapper, forceOriginal) {
            const $img = this.getCardImage($wrapper);

            if (!$img.length || typeof $img.data('sviOriginalSrc') === 'undefined') {
                return;
            }

            const original = $img.data('sviOriginalSrc');
            const $picked = forceOriginal ? $() : $wrapper.find('.svi-swatch.selected[data-image]').first();
            const picked = $picked.length ? $picked.attr('data-image') : '';

            if (picked && picked !== original) {
                $img.attr('src', picked).removeAttr('srcset').removeAttr('sizes');
                $img.addClass('svi-card-image-swapped');
                return;
            }

            $img.attr('src', original);

            const srcset = $img.data('sviOriginalSrcset');
            const sizes = $img.data('sviOriginalSizes');

            if (srcset) {
                $img.attr('srcset', srcset);
            }

            if (sizes) {
                $img.attr('sizes', sizes);
            }

            $img.removeClass('svi-card-image-swapped');
        },

        /**
         * Read attribute_* query args and apply them to the variation selects.
         */
        preselectFromQuery: function () {
            const $form = $('.variations_form');

            if (!$form.length || !window.URLSearchParams) {
                return;
            }

            const params = new URLSearchParams(window.location.search);
            let applied = false;

            $form.find('select[name^="attribute_"]').each(function () {
                const $select = $(this);
                const value = params.get($select.attr('name'));

                if (!value) {
                    return;
                }

                if ($select.find('option').filter(function () {
                    return this.value === value;
                }).length) {
                    $select.val(value);
                    applied = true;
                }
            });

            if (applied) {
                $form.trigger('check_variations');
                $form.trigger('change');

                $form.find('select[name^="attribute_"]').each(function () {
                    const $select = $(this);
                    const attrName = $select.attr('name').replace('attribute_', '');
                    const $wrapper = $form.find('.svi-swatches-wrapper').filter(function () {
                        return String($(this).data('attribute')).toLowerCase() === String(attrName).toLowerCase();
                    }).first();

                    if (!$wrapper.length || !$select.val()) {
                        return;
                    }

                    const $swatch = $wrapper.find('.svi-swatch[data-value="' + $select.val() + '"]');

                    if ($swatch.length) {
                        $wrapper.find('.svi-swatch').removeClass('selected').attr('aria-checked', 'false');
                        $swatch.addClass('selected').attr('aria-checked', 'true');
                        SVI_Swatches.updateSelectedLabel($wrapper, $swatch.data('label'));
                    }
                });
            }
        },

        handleReset: function (e) {
            $('.svi-swatch').removeClass('selected').attr('aria-checked', 'false');

            // Nothing is picked any more, so the cards go back to their own picture.
            $('.svi-catalog-wrapper').each(function () {
                SVI_Swatches.restoreCardImage($(this));
            });

            $('.svi-swatches-wrapper').each(function () {
                SVI_Swatches.updateSelectedLabel($(this), '');
            });

            $('.variations_form').each(function () {
                SVI_Swatches.updateAvailability($(this));
            });
        },

        /**
         * Line the attribute label up with the swatch row.
         *
         * WooCommerce prints the "Clear" link inside the same table cell, so
         * the cell is taller than the swatches and vertical-align: middle
         * pushed the label below them. Align to the top and offset the label by
         * half the height difference, so it stays optically centred whatever
         * swatch size the settings use.
         *
         * Only the side-by-side table layout needs that offset. Block themes
         * stack the label above the value cell, so the label is already on its
         * own line: shifting it down would drag the caption ("size: XL") over
         * the swatches, where it peeks through the gaps between them.
         */
        alignLabels: function () {
            // Quick view and other late forms render after init.
            SVI_Swatches.placeSelectedLabels();

            $('.svi-swatches-wrapper').each(function () {
                var $wrap = $(this);
                var $row = $wrap.closest('tr');

                if (!$row.length) {
                    return;
                }

                $row.addClass('svi-row-swatches');

                var $label = $row.children('.label').first();

                if (!$label.length) {
                    return;
                }

                // Themes (woocommerce-blocktheme.css) set vertical-align on the
                // cell with a heavier selector, so this has to win inline.
                $label.css('vertical-align', 'top');

                var $text = $label.find('label').first();

                if (!$text.length) {
                    return;
                }

                // `position: relative` shifts inline text in both directions,
                // which margins and padding cannot do.
                $text.css({position: 'relative', top: 0});

                var wrapBox = this.getBoundingClientRect();
                var textBox = $text.get(0).getBoundingClientRect();

                // Horizontal overlap means the theme stacked the cells (label
                // above swatches), not the classic two-column layout. Centring
                // would only paint the label on top of the swatch row.
                var stacked = textBox.right > wrapBox.left + 1 && textBox.left < wrapBox.right - 1;
                var delta = stacked ? 0 : Math.round((wrapBox.top + wrapBox.height / 2) - (textBox.top + textBox.height / 2));

                $text.css('top', delta + 'px');
            });
        },

        handleCatalogAddToCart: function (e) {
            // Placeholder for future AJAX add to cart logic if needed. 
            // Currently allows default link behavior or does nothing.
        }
    };

    $(document).ready(function () {
        // Preselected values (defaults, query string) must land in the moved span.
        SVI_Swatches.placeSelectedLabels();
        SVI_Swatches.init();

        $('.variations_form').each(function () {
            SVI_Swatches.updateAvailability($(this));
        });

        // Swatch size changes with the settings, so measure after layout.
        SVI_Swatches.alignLabels();
        $(window).on('resize', function () {
            SVI_Swatches.alignLabels();
        });
    });

})(jQuery);

