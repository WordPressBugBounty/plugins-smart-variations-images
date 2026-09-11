<?php

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Legacy settings callback used by existing configuration.
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
add_filter( 'wpsfsvi_register_settings_woosvi_options', 'wpsfsvi_svi_tabbed_settings' );
add_filter( 'wpsfsvi_register_settings_clean_woosvi_options', 'wpsfsvi_svi_tabbed_settings_clean' );
/**
 * Tabbed example.
 *
 * @param array $wpsfsvi_settings settings.
 */
function wpsfsvi_svi_tabbed_settings(  $wpsfsvi_settings  ) {
    $wpsfsvi_settings['tabs'] = ( isset( $wpsfsvi_settings['tabs'] ) ? $wpsfsvi_settings['tabs'] : array() );
    $wpsfsvi_settings['sections'] = ( isset( $wpsfsvi_settings['sections'] ) ? $wpsfsvi_settings['sections'] : array() );
    $wpsfsvi_settings['tabs'][] = array(
        'id'       => 'main',
        'title'    => esc_html__( 'Global', 'smart-variations-images' ),
        'sections' => array(array(
            'id'    => 'section_variations',
            'title' => esc_html__( 'Variation Select Options', 'smart-variations-images' ),
        )),
    );
    $wpsfsvi_settings['tabs'][] = array(
        'id'    => 'stacked',
        'title' => esc_html__( 'Stacked Layout', 'smart-variations-images' ),
    );
    $wpsfsvi_settings['tabs'][] = array(
        'id'    => 'lightbox',
        'title' => esc_html__( 'Lightbox', 'smart-variations-images' ),
    );
    $wpsfsvi_settings['tabs'][] = array(
        'id'    => 'slider',
        'title' => esc_html__( 'Slider', 'smart-variations-images' ),
    );
    $wpsfsvi_settings['tabs'][] = array(
        'id'    => 'lens',
        'title' => esc_html__( 'Magnifier Lens', 'smart-variations-images' ),
    );
    $wpsfsvi_settings['tabs'][] = array(
        'id'    => 'video',
        'title' => esc_html__( 'Video', 'smart-variations-images' ),
    );
    $wpsfsvi_settings['tabs'][] = array(
        'id'    => 'thumbnails',
        'title' => esc_html__( 'Thumbails', 'smart-variations-images' ),
    );
    $wpsfsvi_settings['tabs'][] = array(
        'id'    => 'fixes',
        'title' => esc_html__( 'Layout Fixes', 'smart-variations-images' ),
    );
    $wpsfsvi_settings['sections'][] = wpsfsvi_svi_options_tab_main();
    $wpsfsvi_settings['sections'][] = wpsfsvi_svi_options_tab_main_variations();
    $wpsfsvi_settings['sections'][] = wpsfsvi_svi_options_tab_main_loopvariations();
    $wpsfsvi_settings['sections'][] = wpsfsvi_svi_options_tab_main_displaylocations();
    $wpsfsvi_settings['sections'][] = wpsfsvi_svi_options_tab_main_imagesettings();
    $wpsfsvi_settings['sections'][] = wpsfsvi_svi_options_tab_stack();
    $wpsfsvi_settings['sections'][] = wpsfsvi_svi_options_tab_lightbox();
    $wpsfsvi_settings['sections'][] = wpsfsvi_svi_options_tab_slider();
    $wpsfsvi_settings['sections'][] = wpsfsvi_svi_options_tab_lens();
    $wpsfsvi_settings['sections'][] = wpsfsvi_svi_options_tab_video();
    $wpsfsvi_settings['sections'][] = wpsfsvi_svi_options_tab_thumbnails();
    $wpsfsvi_settings['sections'][] = wpsfsvi_svi_options_tab_fixes();
    $wpsfsvi_settings['tabs'][] = array(
        'id'    => 'import_export',
        'title' => esc_html__( 'Import / Export', 'smart-variations-images' ),
    );
    $wpsfsvi_settings['sections'][] = wpsfsvi_svi_options_tab_importexport();
    return $wpsfsvi_settings;
}

/**
 * MAIN TAB
 *
 * @return void
 */
function wpsfsvi_svi_options_tab_main() {
    $variation_thumbdesc = '<strong>' . esc_html__( 'Free Version limited to display 1 image, upgrade to PRO to display all.', 'smart-variations-images' ) . '</strong> ';
    $variation_thumbdesc .= esc_html__( 'Unlock all these features', 'smart-variations-images' ) . ' <a href="/wp-admin/admin.php?page=woocommerce_svi-pricing" target="_blank">' . esc_html__( 'here', 'smart-variations-images' ) . '</a>.<br><br>';
    $variation_thumbdesc .= esc_html__( 'This option will display the product variations images under the dropdowns/swatches of the product page.', 'smart-variations-images' ) . '<br>';
    $variation_thumbdesc .= esc_html__( 'All images or Images with no variations assigned will be displayed as default gallery under the main image.', 'smart-variations-images' ) . '<br>';
    $variation_thumbdesc .= esc_html__( 'Adds lightbox option to be activated or not on this images.', 'smart-variations-images' ) . ' <strong>' . esc_html__( 'Thumbnails Keep visible', 'smart-variations-images' ) . '</strong> ' . esc_html__( 'option should be set disabled because activating this options alraedy keeps the images visible.', 'smart-variations-images' );
    return array(
        'section_title' => __( 'Global', 'smart-variations-images' ),
        'tab_id'        => 'main',
        'section_id'    => 'section_global',
        'section_order' => 10,
        'fields'        => array(
            array(
                'id'      => 'default',
                'type'    => 'toggle',
                'title'   => __( 'Enable SVI', 'smart-variations-images' ),
                'desc'    => __( 'This setting allows you to activate or deactivate SVI from running on your site. If you deactivate SVI, it will not be displayed on any product pages.', 'smart-variations-images' ),
                'default' => true,
            ),
            array(
                'id'      => 'default_swatches',
                'type'    => 'toggle',
                'title'   => __( 'Enable Swatches', 'smart-variations-images' ),
                'desc'    => __( 'This setting allows you to activate or deactivate SVI swatches from running on your site. If you deactivate SVI swatches, they will not be displayed on any product pages.', 'smart-variations-images' ),
                'default' => false,
            ),
            array(
                'id'      => 'variation_thumbnails',
                'type'    => 'toggle',
                'title'   => __( 'Showcase Images under Variations', 'smart-variations-images' ),
                'desc'    => $variation_thumbdesc,
                'show_if' => array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                )),
                'default' => false,
            ),
            array(
                'id'      => 'columns_showcase',
                'type'    => wpsfsvi_svi_pass( 'n' ),
                'title'   => __( 'Showcase Thumbnail Items', 'smart-variations-images' ),
                'desc'    => __( 'This setting allows you to set the number of thumbnails to be displayed by row. You can choose a value between 1 and 10. This setting is only applicable if you have enabled the Showcase Images under Variations setting.', 'smart-variations-images' ),
                'show_if' => array(array(
                    'field' => 'main_section_global_variation_thumbnails',
                    'value' => array('1'),
                )),
                'default' => '4',
                'class'   => 'svisubfield',
            ),
            array(
                'id'      => 'svi_disabled_woosvislug',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Disable SVI display', 'smart-variations-images' ),
                'desc'    => 'This setting allows you to disable SVI from running on products that have no SVI data configured and fallback to the default theme display. This means that if a product does not have any SVI data, it will be displayed using the default theme display instead of SVI.',
                'show_if' => array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                )),
                'default' => false,
            ),
            array(
                'id'      => 'skip_equivalent',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Skip Gallery equivalence check', 'smart-variations-images' ),
                'desc'    => 'This setting allows you to skip the gallery equivalence check. Each time an attribute is selected, the plugin will jump to the first image of the gallery by default. If there are no changes made to the current gallery in display, there is no need to jump to the first image. This setting can be useful for products that have many variations or attributes.',
                'show_if' => array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                )),
                'default' => false,
            )
        ),
    );
}

function wpsfsvi_svi_options_tab_main_variations() {
    return array(
        'section_title'       => __( 'Variation Select Options', 'smart-variations-images' ),
        'tab_id'              => 'main',
        'section_id'          => 'section_variations',
        'section_description' => 'On the frontend, when viewing a variable product, the user is presented with dropdown boxes or swatches to select variation options.<br>The following options will interact with the selected information.',
        'section_order'       => 10,
        'fields'              => array(array(
            'id'      => 'swselect',
            'type'    => 'toggle',
            'title'   => __( 'Trigger on attribute change', 'smart-variations-images' ),
            'desc'    => __( 'This setting allows you to trigger the image changing when an attribute is selected. All attributes/swatches will trigger the image changing, so customers don\'t have to wait for all attributes/swatches to be selected before the image changes.', 'smart-variations-images' ),
            'show_if' => array(array(array(
                'field' => 'main_section_global_default',
                'value' => array('1'),
            ))),
            'default' => false,
        ), array(
            'id'       => 'triger_match',
            'type'     => wpsfsvi_svi_pass( 't' ),
            'title'    => __( 'Trigger Exact SVI Gallery Match', 'smart-variations-images' ),
            'subtitle' => __( 'Only activate if you understand the effect', 'smart-variations-images' ),
            'desc'     => __( 'This setting allows you to trigger/display the SVI gallery only when there is an exact match between the attributes selected and the SVI galleries created. This means that variation images will only be displayed when there is a gallery that exactly matches the attributes selected. Note that this setting should only be activated if you understand its effect.', 'smart-variations-images' ),
            'show_if'  => array(array(
                'field' => 'main_section_global_default',
                'value' => array('1'),
            )),
            'default'  => false,
        )),
    );
}

function wpsfsvi_svi_options_tab_main_loopvariations() {
    $fields = array(
        array(
            'id'       => 'filter_attribute',
            'type'     => 'toggle',
            'title'    => __( 'Enable Filter Attribute Image Swapping', 'smart-variations-images' ),
            'subtitle' => __( 'Showcase variation images based on applied filters', 'smart-variations-images' ),
            'desc'     => __( 'When enabled, this option will display the <b>first image</b> of each matching <u>SVI Variations Gallery</u> on the product loop pages (e.g., Shop or Archive pages) based on the applied filters (such as color or size). You can enable/disable specific galleries from being displayed by checking the appropriate <u>SVI Variations Gallery</u> settings on the product edit page.', 'smart-variations-images' ),
            'show_if'  => array(array(
                'field' => 'main_section_global_default',
                'value' => array('1'),
            )),
            'default'  => false,
        ),
        array(
            'id'       => 'filter_attribute_cut',
            'type'     => 'select',
            'title'    => __( 'Filter Attribute Split Layout Type', 'smart-variations-images' ),
            'subtitle' => __( 'Choose the layout style for displaying multiple variation images', 'smart-variations-images' ),
            'desc'     => __( 'Select how multiple variation images should be displayed when a filter matches more than one variation on the Shop/Archive page. <b>Diagonal</b>: Images are split diagonally from bottom-left to top-right. <b>Vertical</b>: Images are stacked vertically. <b>Horizontal</b>: Images are arranged side by side horizontally. Default: Diagonal.', 'smart-variations-images' ),
            'show_if'  => array(array(
                'field' => 'main_section_loopvariations_filter_attribute',
                'value' => array('1'),
            )),
            'choices'  => array(
                'diagonal'   => 'Diagonal',
                'vertical'   => 'Vertical',
                'horizontal' => 'Horizontal',
            ),
            'default'  => 'diagonal',
            'class'    => 'svisubfield',
        ),
        array(
            'id'       => 'filter_attribute_animation',
            'type'     => ( svi_fs()->can_use_premium_code__premium_only() ? 'toggle' : 'custom' ),
            'title'    => __( 'Enable Filter Attribute Animation', 'smart-variations-images' ) . (( svi_fs()->can_use_premium_code__premium_only() ? '' : SMART_SVI_PROVS )),
            'subtitle' => __( 'Add sliding animation on hover', 'smart-variations-images' ),
            'desc'     => ( svi_fs()->can_use_premium_code__premium_only() ? __( 'When enabled, this option adds a sliding animation effect on hover for the Filter Attribute Split Layout (Diagonal, Vertical, Horizontal). The hovered image will expand, and the other images will slide to the remaining space.', 'smart-variations-images' ) : __( 'This is a premium feature. When enabled, it adds a sliding animation effect on hover for the Filter Attribute Split Layout (Diagonal, Vertical, Horizontal). Upgrade to PRO to unlock this feature. <a href="/wp-admin/admin.php?page=woocommerce_svi-pricing" target="_blank">Upgrade here</a>.', 'smart-variations-images' ) ),
            'output'   => ( svi_fs()->can_use_premium_code__premium_only() ? null : 'wpsfsvisvi_info' ),
            'style'    => ( svi_fs()->can_use_premium_code__premium_only() ? null : 'warning' ),
            'show_if'  => array(array(
                'field' => 'main_section_loopvariations_filter_attribute',
                'value' => array('1'),
            )),
            'default'  => false,
            'class'    => 'svisubfield',
        ),
        array(
            'id'       => 'loop_showcase',
            'type'     => 'toggle',
            'title'    => __( 'Showcase Variations', 'smart-variations-images' ),
            'subtitle' => __( 'Showcase your variations on the product loop page', 'smart-variations-images' ),
            'desc'     => __( 'Activating this option will showcase the <b>first image</b> of each of your <u>SVI Variations Gallery</u> under each product on the Product loop pages.<br>You may enable/disable specific galleries from being displayed by checking the proper <u>SVI Variations Gallery</u> on the product.', 'smart-variations-images' ),
            'show_if'  => array(array(
                'field' => 'main_section_global_default',
                'value' => array('1'),
            )),
            'default'  => false,
        ),
        array(
            'id'      => 'loop_showcase_limit',
            'type'    => wpsfsvi_svi_pass( 'n' ),
            'title'   => __( 'Visible galleries ', 'smart-variations-images' ),
            'desc'    => __( 'Define a limit of galleries to be displayed p/product, 0 = all', 'smart-variations-images' ),
            'show_if' => array(array(array(
                'field' => 'main_section_loopvariations_loop_showcase',
                'value' => array('1'),
            ), array(
                'field' => 'main_section_global_default',
                'value' => array('1'),
            ))),
            'default' => '0',
        ),
        array(
            'id'       => 'loop_showcase_position',
            'title'    => __( 'Showcase Position', 'smart-variations-images' ),
            'subtitle' => __( 'Adjust the position of the showcase in the product loop.', 'smart-variations-images' ),
            'desc'     => __( 'WooCommerce has hooks set in place to allow users to customize the positions of certain elements, if your theme has this hooks in place you may adjust the position.', 'smart-variations-images' ),
            'type'     => wpsfsvi_svi_pass( 's' ),
            'choices'  => array(
                'woocommerce_before_shop_loop_item'       => 'Display before product loop item',
                'woocommerce_before_shop_loop_item_title' => 'Display before product title',
                'woocommerce_shop_loop_item_title'        => 'Display next to product title',
                'woocommerce_after_shop_loop_item_title'  => 'Display after product title',
                'woocommerce_after_shop_loop_item'        => 'Display after product loop item',
            ),
            'show_if'  => array(array(array(
                'field' => 'main_section_loopvariations_loop_showcase',
                'value' => array('1'),
            ), array(
                'field' => 'main_section_global_default',
                'value' => array('1'),
            ))),
            'default'  => 'woocommerce_before_shop_loop_item_title',
        ),
        array(
            'id'      => 'loop_showcase_position_priority',
            'type'    => wpsfsvi_svi_pass( 'n' ),
            'title'   => __( 'Showcase Position Priority', 'smart-variations-images' ),
            'desc'    => __( 'Used to specify the order in which the Showcase Position action will be executed. Lower numbers correspond with earlier execution, and functions with the same priority are executed in the order in which they were added to the action. Default value: 10', 'smart-variations-images' ),
            'show_if' => array(array(array(
                'field' => 'main_section_loopvariations_loop_showcase',
                'value' => array('1'),
            ), array(
                'field' => 'main_section_global_default',
                'value' => array('1'),
            ))),
            'default' => '10',
        ),
        array(
            'id'      => 'loop_showcase_wrapper_el',
            'type'    => wpsfsvi_svi_pass( 'tx' ),
            'title'   => __( 'Specify Product Wrapper', 'smart-variations-images' ),
            'desc'    => __( 'Used to specify the element that is wrapping the product on the loop page. By default, it is set to find the closest ".product", but just in case your theme doesn\'t have the class present, this option will allow you to define the target. You can specify, for example, any element (div, li), classes (.product), or both (div.product).', 'smart-variations-images' ),
            'show_if' => array(array(array(
                'field' => 'main_section_loopvariations_loop_showcase',
                'value' => array('1'),
            ), array(
                'field' => 'main_section_global_default',
                'value' => array('1'),
            ))),
            'default' => '.product',
        ),
        array(
            'id'      => 'loop_showcase_wrapper_el_img',
            'type'    => wpsfsvi_svi_pass( 'tx' ),
            'title'   => __( 'Specify Product Wrapper Image', 'smart-variations-images' ),
            'desc'    => __( 'Used to specify the element that is wrapping the product image on the loop page. By default, it is set to find the first image, but just in case the first image is not the product image, this option will allow you to define the target. You can specify, for example, any element (img, div), classes (.attachment-woocommerce_thumbnail), or both (img.attachment-woocommerce_thumbnail).', 'smart-variations-images' ),
            'show_if' => array(array(array(
                'field' => 'main_section_loopvariations_loop_showcase',
                'value' => array('1'),
            ), array(
                'field' => 'main_section_global_default',
                'value' => array('1'),
            ))),
            'default' => 'img',
        ),
        array(
            'title'   => __( 'Showcase Image Size Loaded', 'smart-variations-images' ),
            'desc'    => __( 'Select the image size you want loaded from the available registered image sizes on your site.', 'smart-variations-images' ),
            'id'      => 'showcase_imagesize',
            'type'    => 'select',
            'choices' => svi_get_image_sizes(),
            'show_if' => array(array(array(
                'field' => 'main_section_loopvariations_loop_showcase',
                'value' => array('1'),
            ), array(
                'field' => 'main_section_global_default',
                'value' => array('1'),
            ))),
            'default' => 'shop_thumbnail',
        )
    );
    return array(
        'section_title'       => __( 'WooCommerce Shop Page / Archive', 'smart-variations-images' ),
        'tab_id'              => 'main',
        'section_id'          => 'section_loopvariations',
        'section_description' => 'A Product Archive/Shop page is a WooCommerce page that displays the list of products.<br>The following options will interact with this page.',
        'section_order'       => 10,
        'fields'              => $fields,
    );
}

function wpsfsvi_svi_options_tab_main_displaylocations() {
    $lists_qv = array(
        'Default WooCommerce Target'           => '.woocommerce-product-gallery',
        'WPC Smart Quick View for WooCommerce' => '.thumbnails-ori',
    );
    $li_qv = '<ul>';
    foreach ( $lists_qv as $plugin => $value ) {
        $li_qv .= '<li>' . $plugin . ': <b>' . $value . '</b></li>';
    }
    $li_qv .= "</ul>";
    return array(
        'section_title'       => __( 'Extra display locations', 'smart-variations-images' ),
        'tab_id'              => 'main',
        'section_id'          => 'section_displaylocations',
        'section_description' => 'This section allows you to activate extra locations where you want SVI galleries to be loaded.',
        'section_order'       => 10,
        'fields'              => array(
            array(
                'id'      => 'svicart',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Cart Image', 'smart-variations-images' ),
                'desc'    => __( 'Display choosen variation image in cart/checkout instead of default Product image.', 'smart-variations-images' ),
                'show_if' => array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                )),
                'default' => false,
            ),
            array(
                'id'      => 'sviemail',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Show Image in Email', 'smart-variations-images' ),
                'desc'    => __( 'Display choosen variation image in order email.', 'smart-variations-images' ),
                'show_if' => array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                )),
                'default' => false,
            ),
            array(
                'id'      => 'sviemailadmin',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Show Image in Admin Edit Order', 'smart-variations-images' ),
                'desc'    => __( 'Display choosen variation image in the admin edit order page.', 'smart-variations-images' ),
                'show_if' => array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                )),
                'default' => false,
            ),
            array(
                'id'      => 'order_details',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Show Image in order details', 'smart-variations-images' ),
                'desc'    => __( 'Display choosen variation image in the order details page after the checkout.', 'smart-variations-images' ),
                'show_if' => array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                )),
                'default' => false,
            ),
            array(
                'id'      => 'quick_view',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Enable Quick View append', 'smart-variations-images' ),
                'desc'    => __( 'If theme has Quick View, SVI <b><u>will try</u></b> to append to it, SVI does not guarantee 100% compatibility.<br>This is a "HACK" so if not compatible or not working get in touch.<br><b>NOTE</b>: Activating this option does not enable Quick View on your site.', 'smart-variations-images' ),
                'show_if' => array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                )),
                'default' => false,
            ),
            array(
                'id'      => 'quick_view_target',
                'type'    => wpsfsvi_svi_pass( 'tx' ),
                'title'   => __( 'Target Quick View Gallery', 'smart-variations-images' ),
                'desc'    => '<p>' . esc_html__( 'There are several Quick View solutions from themes/plugins if activating Quick View isnt enough you may target the Quick View Class/ID (The element that contains the WooCommerce gallery). SVI will try to capture and replace it with SVI gallery.', 'smart-variations-images' ) . '</p><br><p>' . esc_html__( 'Known List:', 'smart-variations-images' ) . '<br>' . wp_kses_post( $li_qv ) . '</p>',
                'show_if' => array(array(
                    'field' => 'main_section_displaylocations_quick_view',
                    'value' => array('1'),
                )),
                'default' => '.woocommerce-product-gallery',
            )
        ),
    );
}

function wpsfsvi_svi_options_tab_main_imagesettings() {
    return array(
        'section_title'       => __( 'Image Settings', 'smart-variations-images' ),
        'tab_id'              => 'main',
        'section_id'          => 'section_imagesettings',
        'section_description' => 'This section allows you to refine images sizes and attributes',
        'section_order'       => 10,
        'fields'              => array(
            array(
                'id'      => 'preload_fimg',
                'type'    => 'toggle',
                'title'   => __( 'Use Feat. Image as Preload', 'smart-variations-images' ),
                'desc'    => __( 'Featured image will be used as preloader until SVI get fully loaded.', 'smart-variations-images' ),
                'show_if' => array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                )),
                'default' => false,
            ),
            array(
                'id'      => 'placeholder',
                'type'    => 'toggle',
                'title'   => __( 'Use Placeholder', 'smart-variations-images' ),
                'desc'    => __( 'If activated a placeholder will be displayed for variations that dont have a SVI gallery created otherwise it will fallback to show Default SVI gallery or all images. Want to set a custom placeholder image read <a href="https://docs.woocommerce.com/document/change-the-placeholder-image/" target="_blank">this</a>.', 'smart-variations-images' ),
                'show_if' => array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                )),
                'default' => false,
            ),
            array(
                'id'      => 'imagecaption',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Image Caption', 'smart-variations-images' ),
                'desc'    => __( 'Show Image Title or Caption under main image.<br>Activating this option may require some styling adjustments specific for your theme not supported by this plugin.', 'smart-variations-images' ),
                'show_if' => array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                )),
                'default' => false,
            ),
            array(
                'id'      => 'imagecaption_option',
                'type'    => wpsfsvi_svi_pass( 's', true ),
                'title'   => __( 'Show Title or Caption', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'main_section_imagesettings_imagecaption',
                    'value' => array('1'),
                ))),
                'choices' => array(
                    'title'   => 'Title',
                    'caption' => 'Caption',
                ),
                'default' => 'caption',
            ),
            array(
                'id'      => 'thumb_imagecaption',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Thumbnails Image Caption', 'smart-variations-images' ),
                'desc'    => __( 'Show Image Title or Caption under thumbnail image.<br>Activating this option may require some styling adjustments specific for your theme not supported by this plugin.', 'smart-variations-images' ),
                'show_if' => array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                )),
                'default' => false,
            ),
            array(
                'id'      => 'thumb_imagecaption_option',
                'type'    => wpsfsvi_svi_pass( 's', true ),
                'title'   => __( 'Thumbnails Show Title or Caption', 'smart-variations-images' ),
                'choices' => array(
                    'title'   => 'Title',
                    'caption' => 'Caption',
                ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'main_section_imagesettings_thumb_imagecaption',
                    'value' => array('1'),
                ))),
                'default' => 'caption',
            ),
            array(
                'id'      => 'main_imagesize',
                'type'    => 'select',
                'title'   => __( 'Main Image Size', 'smart-variations-images' ),
                'desc'    => __( 'Select your main image size from the registred sizes', 'smart-variations-images' ),
                'choices' => svi_get_image_sizes(),
                'show_if' => array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                )),
                'default' => 'shop_single',
            ),
            array(
                'id'      => 'thumb_imagesize',
                'type'    => 'select',
                'title'   => __( 'Thumbnail Image Size', 'smart-variations-images' ),
                'desc'    => __( 'Select your Thumbnail size from the registred sizes', 'smart-variations-images' ),
                'choices' => svi_get_image_sizes(),
                'show_if' => array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                )),
                'default' => 'shop_thumbnail',
            ),
            array(
                'id'      => 'sviesrcset',
                'type'    => 'toggle',
                'title'   => __( 'Show SRCSET', 'smart-variations-images' ),
                'desc'    => __( 'Add scrset attribute to images', 'smart-variations-images' ),
                'show_if' => array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                )),
                'default' => false,
            ),
            array(
                'id'      => 'svititleattr',
                'type'    => 'toggle',
                'title'   => __( 'Show Title attribute', 'smart-variations-images' ),
                'desc'    => __( 'Add title attribute to images', 'smart-variations-images' ),
                'show_if' => array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                )),
                'default' => false,
            ),
            array(
                'id'      => 'svialtattr',
                'type'    => 'toggle',
                'title'   => __( 'Show ALT attribute', 'smart-variations-images' ),
                'desc'    => __( 'Add ALT attribute to images', 'smart-variations-images' ),
                'show_if' => array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                )),
                'default' => false,
            ),
            array(
                'id'      => 'sviproglobal',
                'type'    => wpsfsvi_svi_pass( 's' ),
                'title'   => __( 'SVI GLOBAL position display', 'smart-variations-images' ),
                'desc'    => __( 'Whether to display SVI GLOBAL images at Beginning or End of other variation images. Default: End', 'smart-variations-images' ),
                'show_if' => array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                )),
                'choices' => array(
                    'end'       => 'End',
                    'beginning' => 'Beginning',
                ),
                'default' => 'end',
            )
        ),
    );
}

/**
 * STACK TAB
 * 
 */
function wpsfsvi_svi_options_tab_stack() {
    return array(
        'section_title' => __( 'Stacked layout', 'smart-variations-images' ),
        'tab_id'        => 'stacked',
        'section_id'    => 'section_stack',
        'section_order' => 10,
        'fields'        => array(
            array(
                'id'      => 'stacked',
                'type'    => 'toggle',
                'title'   => __( 'Activate stacked images', 'smart-variations-images' ),
                'desc'    => __( 'All images will be showed in a single column, stacked. Only in desktop mode, mobile will fallback to default settings.', 'smart-variations-images' ),
                'show_if' => array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                )),
                'default' => false,
            ),
            array(
                'id'      => 'stacked_columns',
                'type'    => wpsfsvi_svi_pass( 'n' ),
                'title'   => __( 'Items per row', 'smart-variations-images' ),
                'desc'    => __( 'Number of thumbnails to be displayed by row, min:1 | max: 10.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'stacked_section_stack_stacked',
                    'value' => array('1'),
                ))),
                'default' => '1',
            ),
            array(
                'id'      => 'force_stacked',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Force Stacked on Mobile', 'smart-variations-images' ),
                'desc'    => __( 'If activated Stacked layout will also be displayed on mobile otherwise it will fallback to default SVI settings.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'stacked_section_stack_stacked',
                    'value' => array('1'),
                ))),
                'default' => false,
            ),
            array(
                'id'      => 'sticky',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Sticky Product Summary', 'smart-variations-images' ),
                'desc'    => __( 'Product Summary will slide side by side with the images until it reaches last image', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'stacked_section_stack_stacked',
                    'value' => array('1'),
                ))),
                'default' => false,
            ),
            array(
                'id'      => 'sa_element',
                'type'    => wpsfsvi_svi_pass( 'tx' ),
                'title'   => __( 'Sticky Element', 'smart-variations-images' ),
                'desc'    => __( 'The element that needs to be sticky once you scroll. This can be your menu, or any other element like a sidebar, ad banner, etc. Make sure this is a unique identifier.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'stacked_section_stack_stacked',
                    'value' => array('1'),
                ), array(
                    'field' => 'stacked_section_stack_sticky',
                    'value' => array('1'),
                ))),
                'default' => '.summary',
            ),
            array(
                'id'      => 'sticky_margin',
                'type'    => wpsfsvi_svi_pass( 'n' ),
                'title'   => __( 'Margin top', 'smart-variations-images' ),
                'desc'    => __( 'Space between top of page and sticky element: (optional)', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'stacked_section_stack_stacked',
                    'value' => array('1'),
                ), array(
                    'field' => 'stacked_section_stack_sticky',
                    'value' => array('1'),
                ))),
                'default' => '10',
            ),
            array(
                'id'      => 'sa_pushup',
                'type'    => wpsfsvi_svi_pass( 'tx' ),
                'title'   => __( 'Push-up element (optional):', 'smart-variations-images' ),
                'desc'    => __( 'If you want your sticky element to be \'pushed up\' again by another element lower on the page, enter it here. Make sure this is a unique identifier.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'stacked_section_stack_stacked',
                    'value' => array('1'),
                ), array(
                    'field' => 'stacked_section_stack_sticky',
                    'value' => array('1'),
                ))),
                'default' => '',
            )
        ),
    );
}

/**
 * LIGTHBOX TAB
 * 
 */
function wpsfsvi_svi_options_tab_lightbox() {
    return array(
        'section_title' => __( 'Lightbox', 'smart-variations-images' ),
        'tab_id'        => 'lightbox',
        'section_id'    => 'section_lightboxsvi',
        'section_order' => 10,
        'fields'        => array(
            array(
                'id'      => 'lightbox',
                'type'    => 'toggle',
                'title'   => __( 'Activate Lightbox', 'smart-variations-images' ),
                'desc'    => __( 'A Lightbox pops up on click so customers can see a highlighted closeup of the image against a dark background and, if there is one, view the Gallery as a slideshow.', 'smart-variations-images' ),
                'show_if' => array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                )),
                'default' => false,
            ),
            array(
                'id'      => 'variation_thumbnails_lb',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Activate Lightbox on Images under Variations', 'smart-variations-images' ),
                'desc'    => __( 'This option will display the product variations images under the dropdowns/swatches of the product page.<br>Images with no variations assigned will be displayed as default gallery under the main image.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lightbox_section_lightboxsvi_lightbox',
                    'value' => array('1'),
                ), array(
                    'field' => 'main_section_global_variation_thumbnails',
                    'value' => array('1'),
                ))),
                'default' => false,
            ),
            array(
                'id'      => 'lightbox_icon',
                'type'    => 'toggle',
                'title'   => __( 'Show Icon', 'smart-variations-images' ),
                'desc'    => __( 'Enable click icon on image for ligthbox.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lightbox_section_lightboxsvi_lightbox',
                    'value' => array('1'),
                ))),
                'default' => false,
            ),
            array(
                'id'      => 'lightbox_iconclick',
                'type'    => 'toggle',
                'title'   => __( 'Enable Icon Click', 'smart-variations-images' ),
                'desc'    => __( 'Ligthbox only available on icon click, disables ligthbox on image click.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lightbox_section_lightboxsvi_lightbox',
                    'value' => array('1'),
                ), array(
                    'field' => 'lightbox_section_lightboxsvi_lightbox_icon',
                    'value' => array('1'),
                ))),
                'default' => false,
            ),
            array(
                'id'      => 'lightbox_iconcolor',
                'type'    => 'color',
                'title'   => __( 'Icon Color', 'smart-variations-images' ),
                'desc'    => __( 'Pick a color for the icon.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lightbox_section_lightboxsvi_lightbox',
                    'value' => array('1'),
                ), array(
                    'field' => 'lightbox_section_lightboxsvi_lightbox_icon',
                    'value' => array('1'),
                ))),
                'default' => '#888',
            ),
            array(
                'id'      => 'lightbox_thumbnails',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Show Thumbnails', 'smart-variations-images' ),
                'desc'    => __( 'Display a slideshow gallery inside the lightbox.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lightbox_section_lightboxsvi_lightbox',
                    'value' => array('1'),
                ))),
                'default' => false,
            ),
            array(
                'id'      => 'lightbox_close',
                'type'    => 'toggle',
                'title'   => __( 'Show Close Button', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lightbox_section_lightboxsvi_lightbox',
                    'value' => array('1'),
                ))),
                'default' => true,
            ),
            array(
                'id'      => 'lightbox_title',
                'type'    => 'toggle',
                'title'   => __( 'Show Image Titles', 'smart-variations-images' ),
                'desc'    => __( 'Display image titles inside the ligthbox', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lightbox_section_lightboxsvi_lightbox',
                    'value' => array('1'),
                ))),
                'default' => false,
            ),
            array(
                'id'      => 'lightbox_fullScreen',
                'type'    => 'toggle',
                'title'   => __( 'Show FullScreen Option', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lightbox_section_lightboxsvi_lightbox',
                    'value' => array('1'),
                ))),
                'default' => false,
            ),
            array(
                'id'      => 'lightbox_zoom',
                'type'    => 'toggle',
                'title'   => __( 'Show Zoom Option', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lightbox_section_lightboxsvi_lightbox',
                    'value' => array('1'),
                ))),
                'default' => false,
            ),
            array(
                'id'      => 'lightbox_share',
                'type'    => 'toggle',
                'title'   => __( 'Show Share Option', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lightbox_section_lightboxsvi_lightbox',
                    'value' => array('1'),
                ))),
                'default' => false,
            ),
            array(
                'id'      => 'lightbox_counter',
                'type'    => 'toggle',
                'title'   => __( 'Show Counter Option', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lightbox_section_lightboxsvi_lightbox',
                    'value' => array('1'),
                ))),
                'default' => false,
            ),
            array(
                'id'      => 'lightbox_controls',
                'type'    => 'toggle',
                'title'   => __( 'Show Arrows Option', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lightbox_section_lightboxsvi_lightbox',
                    'value' => array('1'),
                ))),
                'default' => true,
            )
        ),
    );
}

/**
 * LIGTHBOX TAB
 * 
 */
function wpsfsvi_svi_options_tab_slider() {
    return array(
        'section_title' => __( 'Slider', 'smart-variations-images' ),
        'tab_id'        => 'slider',
        'section_id'    => 'section_slidersvi',
        'section_order' => 10,
        'fields'        => array(
            array(
                'id'      => 'slider',
                'type'    => 'toggle',
                'title'   => __( 'Activate Slider', 'smart-variations-images' ),
                'desc'    => __( 'Swiper is the most modern free mobile touch slider with hardware accelerated transitions and amazing native behavior.', 'smart-variations-images' ),
                'show_if' => array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                )),
                'default' => false,
            ),
            array(
                'id'      => 'slider_center',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Centered thumbnails', 'smart-variations-images' ),
                'desc'    => __( 'Start in center position or aligned to main image.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'slider_section_slidersvi_slider',
                    'value' => array('1'),
                ))),
                'default' => true,
            ),
            array(
                'id'      => 'slider_effect',
                'title'   => __( 'Tranisition effect', 'smart-variations-images' ),
                'desc'    => __( 'Could be "slide", "fade", "cube", "coverflow" or "flip". <b>Note:</b> Cube transition requires that all images are of the same size, which means its width and height should be equal.', 'smart-variations-images' ),
                'type'    => 'select',
                'choices' => array(
                    'slide'     => 'Slide',
                    'cube'      => 'Cube',
                    'fade'      => 'Fade',
                    'coverflow' => 'Coverflow',
                    'flip'      => 'Flip',
                    'cards'     => 'Cards',
                ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'slider_section_slidersvi_slider',
                    'value' => array('1'),
                ))),
                'default' => 'slide',
            ),
            array(
                'id'      => 'slider_lazyload',
                'title'   => __( 'LazyLoad', 'smart-variations-images' ),
                'desc'    => __( 'Activates LazyLoad of images.', 'smart-variations-images' ),
                'type'    => wpsfsvi_svi_pass( 't' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'slider_section_slidersvi_slider',
                    'value' => array('1'),
                ))),
                'default' => false,
            ),
            array(
                'id'      => 'slider_lazyload_color',
                'type'    => wpsfsvi_svi_pass( 'c' ),
                'title'   => __( 'LazyLoad Color', 'smart-variations-images' ),
                'desc'    => __( 'Pick a color for the lazyload.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'slider_section_slidersvi_slider',
                    'value' => array('1'),
                ), array(
                    'field' => 'slider_section_slidersvi_slider_lazyload',
                    'value' => array('1'),
                ))),
                'default' => '#888',
            ),
            array(
                'id'      => 'slider_pagination',
                'title'   => __( 'Pagination', 'smart-variations-images' ),
                'desc'    => __( 'Activates pagination options.', 'smart-variations-images' ),
                'type'    => wpsfsvi_svi_pass( 't' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'slider_section_slidersvi_slider',
                    'value' => array('1'),
                ))),
                'default' => false,
            ),
            array(
                'id'      => 'slider_paginationType',
                'title'   => __( 'Pagination Type', 'smart-variations-images' ),
                'desc'    => __( 'String with type of pagination. Can be "bullets", "fraction", "progressbar".', 'smart-variations-images' ),
                'type'    => wpsfsvi_svi_pass( 's' ),
                'choices' => array(
                    'bullets'     => 'bullets',
                    'fraction'    => 'Fraction',
                    'progressbar' => 'Progressbar',
                ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'slider_section_slidersvi_slider',
                    'value' => array('1'),
                ), array(
                    'field' => 'slider_section_slidersvi_slider_pagination',
                    'value' => array('1'),
                ))),
                'default' => 'bullets',
            ),
            array(
                'id'      => 'slider_paginationclickable',
                'title'   => __( 'Clickable Bullets', 'smart-variations-images' ),
                'desc'    => __( 'If true then clicking on pagination button will cause transition to appropriate slide. Only for bullets pagination type.', 'smart-variations-images' ),
                'type'    => wpsfsvi_svi_pass( 't' ),
                'show_if' => array(array(
                    array(
                        'field' => 'main_section_global_default',
                        'value' => array('1'),
                    ),
                    array(
                        'field' => 'slider_section_slidersvi_slider',
                        'value' => array('1'),
                    ),
                    array(
                        'field' => 'slider_section_slidersvi_slider_pagination',
                        'value' => array('1'),
                    ),
                    array(
                        'field' => 'slider_section_slidersvi_slider_paginationType',
                        'value' => array('bullets'),
                    )
                )),
                'default' => false,
            ),
            array(
                'id'      => 'slider_paginationDynamicBullets',
                'title'   => __( 'Dynamic Bullets', 'smart-variations-images' ),
                'desc'    => __( 'Good to enable if you use bullets pagination with a lot of slides. So it will keep only few bullets visible at the same time.', 'smart-variations-images' ),
                'type'    => wpsfsvi_svi_pass( 't' ),
                'show_if' => array(array(
                    array(
                        'field' => 'main_section_global_default',
                        'value' => array('1'),
                    ),
                    array(
                        'field' => 'slider_section_slidersvi_slider',
                        'value' => array('1'),
                    ),
                    array(
                        'field' => 'slider_section_slidersvi_slider_pagination',
                        'value' => array('1'),
                    ),
                    array(
                        'field' => 'slider_section_slidersvi_slider_paginationType',
                        'value' => array('bullets'),
                    )
                )),
                'default' => false,
            ),
            array(
                'id'      => 'slider_pagination_color',
                'type'    => wpsfsvi_svi_pass( 'c' ),
                'title'   => __( 'Pagination Color', 'smart-variations-images' ),
                'desc'    => __( 'Pick a color for the pagination.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'slider_section_slidersvi_slider',
                    'value' => array('1'),
                ), array(
                    'field' => 'slider_section_slidersvi_slider_pagination',
                    'value' => array('1'),
                ))),
                'default' => '#888',
            ),
            array(
                'id'       => 'slider_navigation',
                'type'     => 'toggle',
                'title'    => __( 'Main Navigation', 'smart-variations-images' ),
                'subtitle' => __( 'Add arrow navigation to main image.', 'smart-variations-images' ),
                'show_if'  => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'slider_section_slidersvi_slider',
                    'value' => array('1'),
                ))),
                'default'  => false,
            ),
            array(
                'id'       => 'slider_navigation_thumb',
                'type'     => 'toggle',
                'title'    => __( 'Thumb Navigation', 'smart-variations-images' ),
                'subtitle' => __( 'Add arrow navigation to thumbnails.', 'smart-variations-images' ),
                'show_if'  => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'slider_section_slidersvi_slider',
                    'value' => array('1'),
                ))),
                'default'  => false,
            ),
            array(
                'title'   => __( 'Nav Color', 'smart-variations-images' ),
                'desc'    => __( 'Select your navigation color. Requires Main Navigation or Thumb navigation On.', 'smart-variations-images' ),
                'id'      => 'slider_navcolor',
                'type'    => wpsfsvi_svi_pass( 's' ),
                'choices' => array(
                    'custom' => 'Custom',
                    '-blue'  => 'Blue',
                    '-white' => 'White',
                    '-black' => 'Black',
                ),
                'show_if' => array(array(
                    'field' => 'slider_section_slidersvi_slider_navigation',
                    'value' => array('1'),
                ), array(
                    'field' => 'slider_section_slidersvi_slider_navigation_thumb',
                    'value' => array('1'),
                )),
                'default' => '-blue',
            ),
            array(
                'id'      => 'slider_navigation_color',
                'type'    => wpsfsvi_svi_pass( 'c' ),
                'title'   => __( 'Arrows Color', 'smart-variations-images' ),
                'desc'    => __( 'Pick a color for the pagination.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'slider_section_slidersvi_slider',
                    'value' => array('1'),
                ), array(
                    'field' => 'slider_section_slidersvi_slider_navcolor',
                    'value' => array('custom'),
                ))),
                'default' => '',
            ),
            array(
                'id'       => 'slider_autoslide',
                'type'     => 'toggle',
                'title'    => __( 'Auto Slide', 'smart-variations-images' ),
                'subtitle' => __( 'Add auto sliding.', 'smart-variations-images' ),
                'show_if'  => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'slider_section_slidersvi_slider',
                    'value' => array('1'),
                ))),
                'default'  => false,
            ),
            array(
                'id'       => 'slider_autoslide_ms',
                'type'     => wpsfsvi_svi_pass( 'n' ),
                'required' => array('slider_autoslide', '=', '1'),
                'title'    => __( 'Auto Slide time (ms)', 'smart-variations-images' ),
                'desc'     => __( 'Delay between transitions (in ms). If this parameter is not specified or is 0(zero), auto play will be 2500 (2, 5s)', 'smart-variations-images' ),
                'show_if'  => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'slider_section_slidersvi_slider',
                    'value' => array('1'),
                ), array(
                    'field' => 'slider_section_slidersvi_slider_autoslide',
                    'value' => array('1'),
                ))),
                'default'  => '2500',
                'class'    => 'svisubfield',
            ),
            array(
                'id'      => 'slider_autoslide_disableOnInteraction',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Disable on Interaction', 'smart-variations-images' ),
                'desc'    => __( 'Set to Enabled and autoplay will be disabled after user interacts.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'slider_section_slidersvi_slider',
                    'value' => array('1'),
                ), array(
                    'field' => 'slider_section_slidersvi_slider_autoslide',
                    'value' => array('1'),
                ))),
                'default' => false,
                'class'   => 'svisubfield',
            )
        ),
    );
}

/**
 * LENS TAB
 * 
 */
function wpsfsvi_svi_options_tab_lens() {
    $options = '<ul style="display: flex;">
    <li style="padding: 0px 10px;text-align: center;">
    <img src="' . SMART_SVI_DIR_URL . 'admin/images/sviRound.png" title="" alt="" class="" >
    <p>
    Round
    </p>
    </li>
    <li style="padding: 0px 10px;text-align: center;">
    <img src="' . SMART_SVI_DIR_URL . 'admin/images/sviSquare.png" title="" alt="" class="" >
    <p>
    Square
    </p>
    </li>
    </ul>';
    return array(
        'section_title' => __( 'Magnifier Lens', 'smart-variations-images' ),
        'tab_id'        => 'lens',
        'section_id'    => 'section_lenssvi',
        'section_order' => 10,
        'fields'        => array(
            array(
                'id'      => 'lens',
                'type'    => 'toggle',
                'title'   => __( 'Activate Magnifier Lens', 'smart-variations-images' ),
                'desc'    => __( 'Allows zooming images within a container or also in a "lens" that floats overtop of web page.', 'smart-variations-images' ),
                'show_if' => array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                )),
                'default' => false,
            ),
            array(
                'title'   => __( 'Mobile Enabled', 'smart-variations-images' ),
                'id'      => 'lens_mobiledisabled',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'desc'    => '<strong>' . esc_html__( 'NOTE', 'smart-variations-images' ) . '</strong>: ' . esc_html__( 'I recommend this option be off, doesnt make sense in mobile since the finger will be over the lens execpt for inner. Lens is Unvailable for "Window" Zoom type. Lightbox will not work with this option enabled in Mobile View due to the trigger.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lens_section_lenssvi_lens',
                    'value' => array('1'),
                ))),
                'default' => false,
            ),
            array(
                'title'   => __( 'Zoom Type', 'smart-variations-images' ),
                'id'      => 'lens_zoomtype',
                'type'    => 'select',
                'choices' => array(
                    'lens'   => __( 'Lens', 'smart-variations-images' ),
                    'window' => __( 'Window', 'smart-variations-images' ),
                    'inner'  => __( 'Inner', 'smart-variations-images' ),
                ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lens_section_lenssvi_lens',
                    'value' => array('1'),
                ))),
                'default' => 'lens',
            ),
            array(
                'id'      => 'containlenszoom',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Disable Lens Zoom Contain', 'smart-variations-images' ),
                'desc'    => __( 'NOTE: If active in some themes this option may not work properly.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lens_section_lenssvi_lens',
                    'value' => array('1'),
                ), array(
                    'field' => 'lens_section_lenssvi_lens_zoomtype',
                    'value' => array('lens'),
                ))),
                'default' => false,
                'class'   => 'svisubfield',
            ),
            array(
                'id'      => 'lens_type',
                'title'   => __( 'Lens Format', 'smart-variations-images' ),
                'desc'    => $options,
                'type'    => wpsfsvi_svi_pass( 's' ),
                'choices' => array(
                    'round'  => 'Round',
                    'square' => 'Square',
                ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lens_section_lenssvi_lens',
                    'value' => array('1'),
                ), array(
                    'field' => 'lens_section_lenssvi_lens_zoomtype',
                    'value' => array('window', 'lens'),
                ))),
                'default' => 'round',
                'class'   => 'svisubfield',
            ),
            array(
                'id'      => 'lens_lensFadeIn',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Fade In Effect', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lens_section_lenssvi_lens',
                    'value' => array('1'),
                ), array(
                    'field' => 'lens_section_lenssvi_lens_zoomtype',
                    'value' => array('lens'),
                ))),
                'default' => false,
                'class'   => 'svisubfield',
            ),
            array(
                'id'      => 'lens_lensFadeInms',
                'type'    => wpsfsvi_svi_pass( 'n' ),
                'title'   => __( 'Lens FadeIn ms', 'smart-variations-images' ),
                'desc'    => __( 'Set as a number e.g 200 for speed of Lens fadeIn', 'smart-variations-images' ),
                'show_if' => array(array(
                    array(
                        'field' => 'main_section_global_default',
                        'value' => array('1'),
                    ),
                    array(
                        'field' => 'lens_section_lenssvi_lens',
                        'value' => array('1'),
                    ),
                    array(
                        'field' => 'lens_section_lenssvi_lens_zoomtype',
                        'value' => array('lens'),
                    ),
                    array(
                        'field' => 'lens_section_lenssvi_lens_lensFadeIn',
                        'value' => array('1'),
                    )
                )),
                'default' => 200,
                'class'   => 'svisubfield',
            ),
            array(
                'id'      => 'lens_zoomWindowFadeIn',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Window Fade In Effect', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lens_section_lenssvi_lens',
                    'value' => array('1'),
                ), array(
                    'field' => 'lens_section_lenssvi_lens_zoomtype',
                    'value' => array('window'),
                ))),
                'default' => false,
                'class'   => 'svisubfield',
            ),
            array(
                'id'      => 'lens_zoomWindowFadeInms',
                'type'    => wpsfsvi_svi_pass( 'n' ),
                'title'   => __( 'Window FadeIn ms', 'smart-variations-images' ),
                'desc'    => __( 'Set as a number e.g 200 for speed of Window fadeIn', 'smart-variations-images' ),
                'show_if' => array(array(
                    array(
                        'field' => 'main_section_global_default',
                        'value' => array('1'),
                    ),
                    array(
                        'field' => 'lens_section_lenssvi_lens',
                        'value' => array('1'),
                    ),
                    array(
                        'field' => 'lens_section_lenssvi_lens_zoomtype',
                        'value' => array('window'),
                    ),
                    array(
                        'field' => 'lens_section_lenssvi_lens_zoomWindowFadeIn',
                        'value' => array('1'),
                    )
                )),
                'default' => '200',
                'class'   => 'svisubfield',
            ),
            array(
                'id'      => 'lens_size',
                'type'    => wpsfsvi_svi_pass( 'n' ),
                'title'   => __( 'Lens Size', 'smart-variations-images' ),
                'desc'    => __( 'Lens size to be displayed, min:100 | max: 300.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lens_section_lenssvi_lens',
                    'value' => array('1'),
                ), array(
                    'field' => 'lens_section_lenssvi_lens_zoomtype',
                    'value' => array('lens'),
                ))),
                'default' => '150',
                'class'   => 'svisubfield',
            ),
            array(
                'id'      => 'lens_easing',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Lens Easing', 'smart-variations-images' ),
                'desc'    => __( 'Allows smooth scrool of image to Zoom Type Window & Inner', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lens_section_lenssvi_lens',
                    'value' => array('1'),
                ), array(
                    'field' => 'lens_section_lenssvi_lens_zoomtype',
                    'value' => array('window', 'inner'),
                ))),
                'default' => false,
                'class'   => 'svisubfield',
            ),
            array(
                'id'      => 'lens_border',
                'type'    => wpsfsvi_svi_pass( 'c' ),
                'title'   => __( 'Magnifier Border Color', 'smart-variations-images' ),
                'desc'    => __( 'Pick a border color for the lens.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lens_section_lenssvi_lens',
                    'value' => array('1'),
                ))),
                'default' => '#888',
                'class'   => 'svisubfield',
            ),
            array(
                'id'       => 'lens_lensBorder',
                'type'     => wpsfsvi_svi_pass( 'n' ),
                'required' => array(array('lens', '=', '1'), array('lens_border', '!=', 'transparent')),
                'title'    => __( 'Lens Border Width', 'smart-variations-images' ),
                'desc'     => __( 'Width in pixels of the lens border. min: 1 | max: 15.', 'smart-variations-images' ),
                'show_if'  => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lens_section_lenssvi_lens',
                    'value' => array('1'),
                ))),
                'default'  => 1,
                'class'    => 'svisubfield',
            ),
            array(
                'id'      => 'lens_zoomWindowWidth',
                'type'    => wpsfsvi_svi_pass( 'n' ),
                'title'   => __( 'Width of the Window', 'smart-variations-images' ),
                'desc'    => __( 'Set Width for window, default 400.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lens_section_lenssvi_lens',
                    'value' => array('1'),
                ), array(
                    'field' => 'lens_section_lenssvi_lens_zoomtype',
                    'value' => array('window'),
                ))),
                'default' => 400,
                'class'   => 'svisubfield',
            ),
            array(
                'id'      => 'lens_zoomWindowHeight',
                'type'    => wpsfsvi_svi_pass( 'n' ),
                'title'   => __( 'Height of the Window', 'smart-variations-images' ),
                'desc'    => __( 'Set Height for window, default 400.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lens_section_lenssvi_lens',
                    'value' => array('1'),
                ), array(
                    'field' => 'lens_section_lenssvi_lens_zoomtype',
                    'value' => array('window'),
                ))),
                'default' => 400,
                'class'   => 'svisubfield',
            ),
            array(
                'id'      => 'lens_scrollzoom',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Zoom Effect', 'smart-variations-images' ),
                'desc'    => __( 'Allows Zoom with mouse scroll.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lens_section_lenssvi_lens',
                    'value' => array('1'),
                ))),
                'default' => false,
            ),
            array(
                'id'      => 'lens_zIndex',
                'type'    => 'text',
                'title'   => __( 'Custom zIndex for Magnifier', 'smart-variations-images' ),
                'desc'    => __( 'Specifies the stack order of an element. An element with greater stack order is always in front of an element with a lower stack order.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'lens_section_lenssvi_lens',
                    'value' => array('1'),
                ))),
                'default' => 1000,
            )
        ),
    );
}

/**
 * VIDEO TAB
 * 
 */
function wpsfsvi_svi_options_tab_video() {
    return array(
        'section_title' => __( 'Video', 'smart-variations-images' ),
        'tab_id'        => 'video',
        'section_id'    => 'section_videosvi',
        'section_order' => 10,
        'fields'        => array(
            array(
                'id'      => 'video',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Activate Video Support', 'smart-variations-images' ),
                'desc'    => __( 'A simple, accessible and customisable media player for Video, Audio, YouTube and Vimeo.', 'smart-variations-images' ),
                'show_if' => array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                )),
                'default' => false,
            ),
            array(
                'id'      => 'video_maincolor',
                'type'    => wpsfsvi_svi_pass( 'c' ),
                'title'   => __( 'UI color', 'smart-variations-images' ),
                'desc'    => __( 'Change the primary UI color', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'video_section_videosvi_video',
                    'value' => array('1'),
                ))),
                'default' => '#00b3ff',
            ),
            array(
                'id'      => 'video_autoplay',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Autoplay', 'smart-variations-images' ),
                'desc'    => __( 'Autoplay the media on load.<br> Autoplay is generally not recommended as it is seen as a negative user experience. It is also disabled in many browsers. Before raising issues, do your homework. More info can be found here:
                    <ul>
                    <li>Muted option should be ON and activate Video Control Toggle mute if video has sound so that user can enable it manually if needed.</li>
                    <li><a target="_blank" href="https://webkit.org/blog/6784/new-video-policies-for-ios/">New </video> Policies for iOS</a></li>
                    <li><a target="_blank" href="https://developers.google.com/web/updates/2017/09/autoplay-policy-changes">Autoplay Policy Changes</a></li>
                    <li><a target="_blank" href="https://hacks.mozilla.org/2019/02/firefox-66-to-block-automatically-playing-audible-video-and-audio/">Firefox 66 to block automatically playing audible video and audio</a></li></ul>', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'video_section_videosvi_video',
                    'value' => array('1'),
                ))),
                'default' => false,
            ),
            array(
                'id'      => 'video_muted',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Muted', 'smart-variations-images' ),
                'desc'    => __( 'Whether to start playback muted.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'video_section_videosvi_video',
                    'value' => array('1'),
                ))),
                'default' => false,
            ),
            array(
                'id'      => 'video_clickToPlay',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Click To Play', 'smart-variations-images' ),
                'desc'    => __( 'Click (or tap) of the video container will toggle play/pause.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'video_section_videosvi_video',
                    'value' => array('1'),
                ))),
                'default' => true,
            ),
            array(
                'id'      => 'video_disableContextMenu',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Disable Context Menu', 'smart-variations-images' ),
                'desc'    => __( 'Disable right click menu on video to help as very primitive obfuscation to prevent downloads of content.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'video_section_videosvi_video',
                    'value' => array('1'),
                ))),
                'default' => true,
            ),
            array(
                'id'      => 'video_hideControls',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Auto Hide Controls', 'smart-variations-images' ),
                'desc'    => __( 'Hide video controls automatically after 2s of no mouse or focus movement, on control element blur (tab out), on playback start or entering fullscreen. As soon as the mouse is moved, a control element is focused or playback is paused, the controls reappear instantly.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'video_section_videosvi_video',
                    'value' => array('1'),
                ))),
                'default' => true,
            ),
            array(
                'id'      => 'video_fullscreen',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Fullscreen', 'smart-variations-images' ),
                'desc'    => __( 'Toggles whether fullscreen should be enabled on double touch/click.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'video_section_videosvi_video',
                    'value' => array('1'),
                ))),
                'default' => true,
            ),
            array(
                'id'      => 'video_loop',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Loop', 'smart-variations-images' ),
                'desc'    => __( 'Whether to loop the current video.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'video_section_videosvi_video',
                    'value' => array('1'),
                ))),
                'default' => false,
            ),
            array(
                'id'      => 'video_poster',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Poster', 'smart-variations-images' ),
                'desc'    => __( 'Sets SVI image as the current poster image for the player.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'video_section_videosvi_video',
                    'value' => array('1'),
                ))),
                'default' => false,
            ),
            array(
                'id'      => 'video_ratio',
                'type'    => wpsfsvi_svi_pass( 's' ),
                'title'   => __( 'Ratio', 'smart-variations-images' ),
                'desc'    => __( 'Force an aspect ratio for all videos. If this is set to auto then the default for HTML5 and Vimeo is to use the native resolution of the video. Dimensions are not available from YouTube via SDK, 16:9 is forced as a sensible default.', 'smart-variations-images' ),
                'choices' => array(
                    'auto' => 'auto',
                    '1:1'  => '1:1',
                    '16:9' => '16:9',
                    '4:3'  => '4:3',
                    '9:16' => '9:16',
                ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'video_section_videosvi_video',
                    'value' => array('1'),
                ))),
                'default' => 'auto',
            ),
            array(
                'id'      => 'video_controls',
                'type'    => wpsfsvi_svi_pass( 'cx' ),
                'title'   => __( 'Video Controls', 'smart-variations-images' ),
                'desc'    => __( 'Manage the controls of the player (play,pause,progress, duration, mute, volume, fullscreen)', 'smart-variations-images' ),
                'choices' => array(
                    'play-large'   => __( 'The large play button in the center', 'smart-variations-images' ),
                    'play'         => __( 'Play/pause playback', 'smart-variations-images' ),
                    'progress'     => __( 'The progress bar and scrubber for playback and buffering', 'smart-variations-images' ),
                    'current-time' => __( 'The current time of playback', 'smart-variations-images' ),
                    'duration'     => __( 'The full duration of the media', 'smart-variations-images' ),
                    'mute'         => __( 'Toggle mute', 'smart-variations-images' ),
                    'volume'       => __( 'Volume control', 'smart-variations-images' ),
                    'fullscreen'   => __( 'Toggle fullscreen', 'smart-variations-images' ),
                ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'video_section_videosvi_video',
                    'value' => array('1'),
                ))),
                'default' => array('play-large', 'play'),
            )
        ),
    );
}

/**
 * THUMBNAILS TAB
 * 
 */
function wpsfsvi_svi_options_tab_thumbnails() {
    $positions = '<ul style="display: flex;">
    <li style="padding: 0px 10px;text-align: center;">
    <img src="' . SMART_SVI_DIR_URL . 'admin/images/sviBottom.png" title="" alt="" class="" >
    <p>
    Bottom
    </p>
    </li>
    <li style="padding: 0px 10px;text-align: center;">
    <img src="' . SMART_SVI_DIR_URL . 'admin/images/sviLeft.png" title="" alt="" class="" >
    <p>
    Left
    </p>
    </li>
    <li style="padding: 0px 10px;text-align: center;">
    <img src="' . SMART_SVI_DIR_URL . 'admin/images/sviRight.png" title="" alt="" class="" >
    <p>
    Right
    </p>
    </li>
    </ul>';
    return array(
        'section_title' => __( 'Thumbails', 'smart-variations-images' ),
        'tab_id'        => 'thumbnails',
        'section_id'    => 'section_thumbs',
        'section_order' => 10,
        'fields'        => array(
            array(
                'id'      => 'disable_thumb',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Disabled', 'smart-variations-images' ),
                'desc'    => __( 'Disable thumbnails on all product pages', 'smart-variations-images' ),
                'show_if' => array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                )),
                'default' => false,
            ),
            array(
                'id'       => 'slider_position',
                'type'     => wpsfsvi_svi_pass( 's' ),
                'title'    => __( 'Position', 'smart-variations-images' ),
                'subtitle' => __( 'Select thumnails position. Bottom, Left or right.', 'smart-variations-images' ),
                'desc'     => sprintf( 
                    /* translators: %s: thumbnail position preview markup. */
                    __( 'Bottom, Left and Right positions, for thumbnails.%s', 'smart-variations-images' ),
                    $positions
                 ),
                'choices'  => array(
                    '0' => 'Bottom',
                    '1' => 'Left',
                    '2' => 'Right',
                ),
                'show_if'  => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'thumbnails_section_thumbs_disable_thumb',
                    'value' => array('0'),
                ))),
                'default'  => '0',
            ),
            array(
                'id'      => 'columns',
                'type'    => 'number',
                'title'   => __( 'Items per row', 'smart-variations-images' ),
                'desc'    => __( 'Number of thumbnails to be displayed by row, min:1 | max: 10. <br><b>Note:</b> If using slider you may add decimal to display part of next slide, ex: 4.5', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'thumbnails_section_thumbs_disable_thumb',
                    'value' => array('0'),
                ), array(
                    'field' => 'thumbnails_section_thumbs_slider_position',
                    'value' => array('0'),
                ))),
                'svif'    => ( svi_fs()->can_use_premium_code__premium_only() ? 'false' : 'true' ),
                'default' => '4',
            ),
            array(
                'id'      => 'notice8',
                'type'    => 'custom',
                'output'  => 'wpsfsvisvi_info',
                'title'   => '',
                'desc'    => __( 'Number of thumbnails to be displayed by row only available in <b>bottom</b> position, <b>Vertical</b> positions are auto calculated.', 'smart-variations-images' ),
                'style'   => 'warning',
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'thumbnails_section_thumbs_disable_thumb',
                    'value' => array('0'),
                ), array(
                    'field' => 'thumbnails_section_thumbs_slider_position',
                    'value' => array('1', '2'),
                ))),
            ),
            array(
                'id'      => 'hide_thumbs',
                'type'    => 'toggle',
                'title'   => __( 'Hidden', 'smart-variations-images' ),
                'desc'    => __( 'Thumbnails will be hidden until a variation as been selected.<br><b>Note</b>: Will not work with products that have "<u>SVI Default Gallery</u>" present.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'thumbnails_section_thumbs_disable_thumb',
                    'value' => array('0'),
                ))),
                'svif'    => ( svi_fs()->can_use_premium_code__premium_only() ? 'false' : 'true' ),
                'default' => false,
            ),
            array(
                'id'       => 'variation_swap',
                'type'     => wpsfsvi_svi_pass( 't' ),
                'title'    => __( 'Trigger on Thumbnail click', 'smart-variations-images' ),
                'subtitle' => __( 'Change value of the attributes/Swatches', 'smart-variations-images' ),
                'desc'     => '<ul><li>' . esc_html__( 'When user clicks the thumbnail the values of Attributes/Swatches will changed to reflect the image values.', 'smart-variations-images' ) . '</li><li>' . esc_html__( 'If image is present in multiple SVI Galleries the Variations/Swatches will be changed to the first match.', 'smart-variations-images' ) . '</li></ul>',
                'show_if'  => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'thumbnails_section_thumbs_disable_thumb',
                    'value' => array('0'),
                ))),
                'default'  => false,
            ),
            array(
                'id'      => 'notice4',
                'type'    => 'custom',
                'output'  => 'wpsfsvisvi_info',
                'title'   => '',
                'desc'    => __( 'Thumbnail Click Swap disabled. To activate switch Hidden Thumbnails options <b>off</b>.', 'smart-variations-images' ),
                'style'   => 'warning',
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'thumbnails_section_thumbs_hide_thumbs',
                    'value' => array('1'),
                ))),
            ),
            array(
                'id'      => 'keep_thumbnails',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Keep visible', 'smart-variations-images' ),
                'desc'    => __( 'This option will keep thumbnails visible all the time. <b>No changes</b> will be made to the product gallery.<br> Option should be disabled if "Showcase Images under Variations" is active or may cause unexpected behaviour.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'thumbnails_section_thumbs_disable_thumb',
                    'value' => array('0'),
                ), array(
                    'field' => 'main_section_global_variation_thumbnails',
                    'value' => array('0'),
                ))),
                'default' => false,
            ),
            array(
                'id'      => 'keep_thumbnails_option',
                'type'    => wpsfsvi_svi_pass( 's' ),
                'title'   => __( 'Default Image Display', 'smart-variations-images' ),
                'desc'    => __( 'Select the default display to be showed.<br>If <b>SVI Default Gallery</b> not present it will fallback to display the images in the WooCommerce Product Gallery.', 'smart-variations-images' ),
                'choices' => array(
                    'svidefault' => __( 'SVI Default Gallery', 'smart-variations-images' ),
                    'product'    => __( 'WooCommerce Product Gallery', 'smart-variations-images' ),
                ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'thumbnails_section_thumbs_disable_thumb',
                    'value' => array('0'),
                ), array(
                    'field' => 'thumbnails_section_thumbs_keep_thumbnails',
                    'value' => array('1'),
                ))),
                'default' => 'svidefault',
            ),
            array(
                'id'      => 'thumbnails_showactive',
                'type'    => wpsfsvi_svi_pass( 't' ),
                'title'   => __( 'Thumbnail Opacity', 'smart-variations-images' ),
                'desc'    => __( 'If active, current tumbnail will be faded.', 'smart-variations-images' ),
                'show_if' => array(array(array(
                    'field' => 'main_section_global_default',
                    'value' => array('1'),
                ), array(
                    'field' => 'thumbnails_section_thumbs_disable_thumb',
                    'value' => array('0'),
                ))),
                'default' => false,
            )
        ),
    );
}

function wpsfsvi_svi_options_tab_fixes() {
    return array(
        'section_title' => __( 'Layout Fixes', 'smart-variations-images' ),
        'tab_id'        => 'fixes',
        'section_id'    => 'section_fixessvi',
        'section_order' => 10,
        'fields'        => array(array(
            'id'      => 'custom_class',
            'type'    => 'text',
            'title'   => __( 'Custom Class', 'smart-variations-images' ),
            'desc'    => __( 'Insert custom css class(es) to fit your theme needs.', 'smart-variations-images' ),
            'show_if' => array(array(
                'field' => 'main_section_global_default',
                'value' => array('1'),
            )),
        ), array(
            'id'      => 'sviforce_image',
            'type'    => 'toggle',
            'title'   => __( 'Remove Image class', 'smart-variations-images' ),
            'desc'    => __( 'Some theme force styling on image class that may break the layout.', 'smart-variations-images' ),
            'show_if' => array(array(
                'field' => 'main_section_global_default',
                'value' => array('1'),
            )),
            'default' => false,
        )),
    );
}

/**
 * Get size information for all currently-registered image sizes.
 *
 * @global $_wp_additional_image_sizes
 * @uses   get_intermediate_image_sizes()
 * @return array $sizes Data for all currently-registered image sizes.
 */
function svi_get_image_sizes() {
    global $_wp_additional_image_sizes;
    $sizes = array(
        'woocommerce_thumbnail',
        'woocommerce_single',
        'woocommerce_gallery_thumbnail',
        'shop_catalog',
        'shop_single',
        'shop_thumbnail',
        'full'
    );
    foreach ( get_intermediate_image_sizes() as $_size ) {
        if ( in_array( $_size, array(
            'thumbnail',
            'medium',
            'medium_large',
            'large'
        ) ) ) {
            array_push( $sizes, $_size );
        } elseif ( isset( $_wp_additional_image_sizes[$_size] ) ) {
            array_push( $sizes, $_size );
        }
    }
    $available_sizes = array();
    foreach ( $sizes as $size ) {
        $available_sizes[$size] = $size;
    }
    return $available_sizes;
}

function wpsfsvisvi_info(  $args  ) {
    $style = ( isset( $args['style'] ) ? sanitize_html_class( $args['style'] ) : 'info' );
    $class = ( isset( $args['class'] ) ? sanitize_html_class( $args['class'] ) : '' );
    $desc = ( isset( $args['desc'] ) ? wp_kses_post( $args['desc'] ) : '' );
    echo '<div class="notice wpsfsvi-info notice-' . esc_attr( $style ) . ' inline ' . esc_attr( $class ) . '"><p>' . wp_kses_post( $desc ) . '</p></div>';
}

function wpsfsvi_svi_tabbed_settings_clean(  $args  ) {
    foreach ( $args['sections'] as $k => $fields ) {
        foreach ( $fields['fields'] as $k2 => $field ) {
            if ( $field['type'] == 'svi' ) {
                unset($args['sections'][$k]['fields'][$k2]['show_if']);
                $title = $args['sections'][$k]['fields'][$k2]['title'];
                $args['sections'][$k]['fields'][$k2]['title'] = $title . SMART_SVI_PROVS;
                $args['sections'][$k]['fields'][$k2]['type'] = 'custom';
                $args['sections'][$k]['fields'][$k2]['style'] = 'warning';
                $args['sections'][$k]['fields'][$k2]['output'] = 'wpsfsvisvi_info';
            }
            if ( isset( $field['svif'] ) ) {
                unset($args['sections'][$k]['fields'][$k2]['show_if']);
            }
        }
    }
    return $args;
}

function wpsfsvi_svi_pass(  $arg, $h = false  ) {
    if ( $h ) {
        return 'hidden';
    } else {
        return 'svi';
    }
}

function wpsfsvi_svi_options_tab_importexport() {
    return array(
        'section_title' => __( 'Import / Export', 'smart-variations-images' ),
        'tab_id'        => 'import_export',
        'section_id'    => 'section_import_export',
        'section_order' => 10,
        'fields'        => array(array(
            'id'       => 'export',
            'title'    => 'Export settings',
            'subtitle' => 'Export settings.',
            'type'     => 'export',
        ), array(
            'id'       => 'import',
            'title'    => 'Import',
            'subtitle' => 'Import settings.',
            'type'     => 'import',
        )),
    );
}
