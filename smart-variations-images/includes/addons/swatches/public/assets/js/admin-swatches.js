jQuery(document).ready(function ($) {
    // Initialize Color Picker
    $('.svi-color-picker').wpColorPicker();

    // Swatch Type Toggle
    $(document).on('change', '#svi_swatch_type', function () {
        var type = $(this).val();

        // Find Options Wrappers (Handle Edit/Table Row vs Add/Form Field)
        var isEdit = $(this).closest('tr').length > 0;

        // Helper to find rows/fields by class
        // Note: Backend adds 'svi-opt-*' classes to the wrapper div/td/tr depending on context.
        function findGroup(cls) {
            // In Edit Attribute (tr.form-field), the class is on the TR itself.
            // In Add Attribute (div.form-field), the class is on the DIV itself.
            return isEdit ? $('.' + cls) : $('.' + cls);
        }

        var $visual = findGroup('svi-opt-visual');
        var $imageOnly = findGroup('svi-opt-image-only');
        var $common = findGroup('svi-opt-common');
        var $catalogMethod = findGroup('svi-opt-catalog-method');

        // Reset all
        $visual.hide();
        $imageOnly.hide();
        $common.hide();
        $catalogMethod.hide();

        if (type !== '') {
            // Show Common Options (Catalog, Overflow, Filters, Groups) for all valid types
            $common.show();

            // Show Visual Options (Shape, Size, Tooltips) for Color/Image
            if (type === 'color' || type === 'image') {
                $visual.show();
            }

            // Show Large Preview only for Image
            if (type === 'image') {
                $imageOnly.show();
            }

            // Trigger catalog change to show/hide catalog method logic
            $('#svi_show_in_catalog').trigger('change');
        }
    });

    // Catalog Show Toggle
    $(document).on('change', '#svi_show_in_catalog', function () {
        var show = $(this).val();
        var isEdit = $(this).closest('tr').length > 0;
        var $method = $('.svi-opt-catalog-method');

        if (show === 'yes') {
            $method.show();
        } else {
            $method.hide();
        }
    });

    // Trigger change on load
    if ($('#svi_swatch_type').length) {
        // Wait a tick for fields to exist if dynamically loaded, 
        // though usually document.ready is enough.
        $('#svi_swatch_type').trigger('change');
    }

    // Term Fields Toggle (kept for reference, but likely handled by PHP conditional rendering now)
    // ...

    // Image Uploader
    $(document).on('click', '.svi-upload-image-btn', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var $container = $btn.closest('.svi-image-uploader');
        var $input = $container.find('input[type="hidden"]');
        var $preview = $container.find('.image-preview');
        var $removeBtn = $container.find('.svi-remove-image-btn');

        var frame = wp.media({
            title: 'Select Swatch Image',
            button: { text: 'Use this image' },
            multiple: false
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            $input.val(attachment.id);
            $preview.attr('src', attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url);
            $removeBtn.show();
        });

        frame.open();
    });

    $(document).on('click', '.svi-remove-image-btn', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var $container = $btn.closest('.svi-image-uploader');
        var $input = $container.find('input[type="hidden"]');
        var $preview = $container.find('.image-preview');

        $input.val('');
        $preview.attr('src', ''); // You might want a placeholder here
        $btn.hide();
    });

    // --- Product data panel: per-value colour/image/label overrides ---

    if ($.fn.wpColorPicker) {
        $('#svi_swatches_product_data .svi-swatch-color-field').wpColorPicker({
            change: function (event, ui) {
                $(event.target).val(ui.color.toString()).trigger('change');
            },
            clear: function () {
                $(this).val('').trigger('change');
            }
        });
    }

    $(document).on('click', '#svi_swatches_product_data .svi-swatch-image-pick', function (e) {
        e.preventDefault();

        var $cell = $(this).closest('.svi-swatch-image-cell');
        var frame = wp.media({
            title: 'Select Swatch Image',
            button: { text: 'Use this image' },
            multiple: false
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            var url = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;

            $cell.find('.svi-swatch-image-id').val(attachment.id);
            $cell.find('.svi-swatch-image-preview').attr('src', url).removeClass('is-empty');
            $cell.find('.svi-swatch-image-clear').show();
        });

        frame.open();
    });

    $(document).on('click', '#svi_swatches_product_data .svi-swatch-image-clear', function (e) {
        e.preventDefault();

        var $cell = $(this).closest('.svi-swatch-image-cell');
        $cell.find('.svi-swatch-image-id').val('');
        $cell.find('.svi-swatch-image-preview').attr('src', '').addClass('is-empty');
        $(this).hide();
    });

    // --- Settings screen: swatch definitions import ---

    $(document).on('click', '.svi-definitions-import', function (e) {
        e.preventDefault();

        var $button = $(this);
        var $input = $('.svi-definitions-file').get(0);
        var file = $input && $input.files ? $input.files[0] : null;

        if (!file) {
            window.alert('Please choose a JSON file exported by this plugin.');
            return;
        }

        var reader = new FileReader();

        reader.onload = function () {
            var form = document.createElement('form');

            form.method = 'post';
            form.action = (window.sviSwatchesImport && window.sviSwatchesImport.action) || 'admin-post.php';
            form.style.display = 'none';

            function add(name, value) {
                var field = document.createElement('input');
                field.type = 'hidden';
                field.name = name;
                field.value = value;
                form.appendChild(field);
            }

            add('action', 'svi_swatches_import');
            add('_wpnonce', (window.sviSwatchesImport && window.sviSwatchesImport.nonce) || '');
            add('svi_swatches_json', reader.result);

            document.body.appendChild(form);
            $button.prop('disabled', true);
            form.submit();
        };

        reader.readAsText(file);
    });

    // --- Settings screen: the Global switch owns the Swatches tab ---

    var sviMaster = document.querySelector('input[type="checkbox"][name="woosvi_options_settings[main_section_global_default_swatches]"]');

    if (sviMaster) {
        var sviTabLink = document.querySelector('.wpsfsvi-nav__item-link[href="#tab-swatches"]');
        var sviTabItem = sviTabLink ? sviTabLink.closest('.wpsfsvi-nav__item') : null;
        var sviTabPanel = document.getElementById('tab-swatches');

        var sviApplyTabState = function () {
            var on = !!sviMaster.checked;

            document.body.classList.toggle('svi-swatches-off', !on);

            // Fallback for browsers without :has(), and for the panel the
            // framework would keep visible while it is the active tab.
            if (sviTabItem) {
                sviTabItem.style.display = on ? '' : 'none';
            }

            if (sviTabPanel) {
                sviTabPanel.style.display = on ? '' : 'none';
            }
        };

        sviApplyTabState();
        sviMaster.addEventListener('change', sviApplyTabState);
    }
});
