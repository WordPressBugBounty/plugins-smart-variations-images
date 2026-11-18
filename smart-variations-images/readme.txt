=== Smart Variations Images & Swatches for WooCommerce ===
Contributors: drosendo
Tags: woocommerce, variation swatches, variation images, product gallery, color swatches
Requires at least: 6.0
Tested up to: 7.1
WC requires at least: 8.2
WC tested up to: 11.3.0
Stable tag: 5.3.7
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Every swatch option free: color, image, text and radio. Plus a whole WooCommerce gallery per color, set once and reused by every size.

== Description ==

**Every swatch option is free. No lite version, no locked swatch settings.**

Smart Variations Images & Swatches (SVI) turns WooCommerce variation dropdowns into color, image, text or radio swatches. When a shopper picks one, the whole product gallery changes: the main image and every thumbnail, not only the first photo.

The gallery is the part I have built SVI around since 2015. You create one gallery per attribute value, for example one per color, and every size reuses it. A hoodie in 6 colors and 7 sizes has 42 variations. With SVI it needs 6 galleries, not 42.

WooCommerce 11.1 added a gallery to each variation row. If every variation has its own photos, that may be all you need. SVI is for the catalogs where the same photos repeat across sizes, materials or fits.

[youtube https://youtu.be/QMV8XBeub_o]

[How native variation galleries compare with galleries per attribute](https://www.smart-variations.com/e-commerce-news-updates/woocommerce-native-vs-attribute-variation-galleries/?utm_source=wporg&utm_medium=listing&utm_campaign=evergreen&utm_content=readme-article)

= Swatches (free, every option) =

[Live demo](https://svi.rosendo.pt/free) | [Support](https://wordpress.org/support/plugin/smart-variations-images/)

SVI PRO does not unlock extra swatch types or swatch settings. Everything below is in the free version.

* Color, image, text and radio swatches, including custom (non-taxonomy) attributes
* Up to four colors in one swatch, for two-tone and multi-color values
* Shape, size, colors, hover and selected states
* Tooltips, with an optional large image preview
* The selected value next to the attribute name, for example "Color: Red"
* Out-of-stock values can be blurred, dimmed, struck through or hidden
* Keyboard access: swatches work as a radio group, with arrow keys, Home, End, Space and Enter
* Per-product overrides, so one product can use different types or colors
* Swatches on shop and catalog cards, with optional image swap on hover or click. An optional button appears once a swatch is chosen and opens the product page with those values already selected
* Color and image swatches in the classic "Filter by attribute" widget (block themes can use the native Color/image attribute type)
* Attributes that have no type can be shown as text, color, image or radio swatches with one setting (Dropdown by default). Nothing is saved to your attributes, and switching back to Dropdown undoes it. Color and image values without a color or image show as text, and you can use the variation image instead
* Import and export of swatch definitions, with a safe conversion of existing color, image and label data

= Switching from another swatches plugin? Import in one click =

Already configured your swatches somewhere else? SVI reads them from your database and copies them into its own swatch settings, from **Swatches > Import Swatches** in the SVI settings screen.

* Preview first: a dry run lists every attribute, term value and product override, and says what would happen to it.
* Nothing is deleted. The other plugin keeps all of its data and SVI values that already exist are kept unless you tick "Overwrite". Back up your database before you start, and use **Undo** to return SVI to exactly what it was before the import.
* The import runs in batches with a progress bar and per-item results, and can be resumed if the tab closes.
* WP-CLI is available too: `wp svi import-swatches --list`, `--from=<plugin> --dry-run`, `--from=<plugin> --overwrite`, `--undo=<run-id>`.

Supported sources (free versions from the WordPress.org directory): Woo Variation Swatches, Variation Swatches for WooCommerce, WCBoost Variation Swatches, Variation Swatches for WooCommerce (WooSuite), Product Variations Swatches for WooCommerce (VillaTheme), Woo Product Variation Swatches (RadiusTheme), Product Variation Swatches for WooCommerce (ThemeHunk) and WPC Variation Swatches.

What is imported: attribute swatch types, term colors (including dual color where the source stores it), swatch images and labels, per-product overrides where the source has them, and the matching global swatch settings. What has no SVI equivalent is listed in the preview, item by item, for example tooltips and groups, per-attribute shape or size, and the source's own theme or profile settings.

= Variation galleries (free) =

* One gallery per attribute value, reused by every combination that shares it
* Unlimited images in each gallery, in the order you drag and drop
* The gallery changes as soon as one attribute is picked, without waiting for the full combination
* Filter Attribute Image Swapping: when a shopper filters the shop by color, each product card shows that color's image. Several filtered values split the image into one part per value
* Showcase Variations on shop and catalog cards: the first image of the first 2 galleries of each product
* One showcase image under the variation selection
* Slider with navigation arrows, or a stacked layout
* Magnifier lens (Lens, Window or Inner)
* Lightbox
* Hide thumbnails until a variation is chosen
* Shortcode `[svi_wcsc]` for builders and custom layouts
* WPML-aware
* Import and export of global plugin settings

= SVI PRO: more from the same galleries =

[Live demo](https://svi.rosendo.pt/pro) | [See SVI PRO plans](https://www.smart-variations.com/?utm_source=wporg&utm_medium=listing&utm_campaign=evergreen&utm_content=readme-pro#pricing)

The free version is complete for swatches and galleries. SVI PRO is for stores that want the chosen variation to follow the order, and more control over how galleries look. It runs on the same galleries you already built, so nothing has to be set up again.

After the click: the right image follows the order

* The chosen variation image in the cart, mini-cart and checkout, including the Cart and Checkout blocks
* The same image in order emails, the admin order screen and the customer's order details

On the product page

* Video in the variation gallery (YouTube, Vimeo or MP4)
* Showcase Images under Variations with the whole gallery, in columns, instead of one image
* SVI Global: images added to every variation gallery, at the start or the end
* Exact-match triggering, so a gallery is used only when the full combination matches
* Sticky product summary next to a stacked gallery, stacked columns, and stacked layout forced on mobile
* Quick View append: SVI tries to put its gallery inside the Quick View your theme or another plugin already provides. It does not add Quick View itself
* Advanced slider, lightbox and magnifier options (pagination, lazy load, lightbox thumbnails, round or square lens, and similar controls), plus image captions

On shop and catalog pages

* Showcase Variations with every gallery, a limit per product and a choice of position
* "Hide from Product Loop" per gallery, also respected when filtering swaps the product image
* Filter Attribute Animation for split images

For large catalogs

* CSV import of product galleries through the WooCommerce product importer
* REST API (GET and POST) for product galleries

SVI PRO includes priority ticket support. There is a 7-day free trial (payment method required) and a 14-day money-back guarantee, no questions asked.

== Installation ==

= Automatic install from the WordPress dashboard =

1. Log in to your admin panel
2. Go to Plugins → Add New
3. Search for **Smart Variations Images & Swatches**
4. Click Install, then Activate

= Manual install from the WordPress dashboard =

1. Download the plugin
2. Log in to your admin panel and go to Plugins → Add New → Upload
3. Choose the plugin file and click Install

= Install using FTP =

1. Download the plugin
2. Unzip the file
3. Upload the folder to `wp-content/plugins/`
4. Log in to your WordPress dashboard
5. Go to Plugins → Installed Plugins
6. Activate the plugin

== Frequently Asked Questions ==

= Are some swatch options only available in SVI PRO? =

No. From 5.3.0, every swatch option is in the free version. There is no lite or PRO split for swatches. SVI PRO adds gallery features: video, the variation image in the cart, emails and orders, the full showcase, sticky product summary, Quick View append, and finer slider, lightbox and lens controls.

= Is the free version enough for my store? =

Often, yes. Swatches, galleries per attribute, the gallery swap, the filter image swap, slider, lens and lightbox are free. Consider SVI PRO when you want the variation image in the cart and in emails, video in the gallery, or the whole gallery shown under the variations and on shop cards.

= WooCommerce 11.1 has variation galleries. Do I still need SVI? =

Not always. WooCommerce 11.1 lets you set a gallery on each variation row. If each variation has its own photos, that is simple and free. SVI stores the gallery once per attribute value, so a color gallery is reused by every size. That saves the most time when the same photos repeat across many variations.

= How do I set up the galleries? =

1. Create a product and build its attributes and variations
2. Open the **SVI Variations Gallery** tab and add a gallery for each attribute value you want to show, for example one per color
3. Save the product
4. Go to WooCommerce → SVI → Global and enable SVI so it runs on the storefront
5. If you want help with the layout, [watch the walkthrough](https://youtu.be/QMV8XBeub_o)

= How do I set up swatches? =

1. Turn on **Enable Swatches** under WooCommerce → SVI → Global
2. Edit each attribute under Products → Attributes and choose a type: color, image, text or radio
3. Open Configure terms and set the colors, image or label for each value
4. Optionally, adjust appearance and catalog or filter behavior under WooCommerce → SVI → Swatches
5. To override types or values on a single product, use the **SVI Swatches** tab on the product edit screen

Existing color, image and label swatches from earlier versions are kept. You can convert them from the Swatch Definitions section if you want them stored in the new fields.

= What happens to my PRO settings if my license expires? =

They stay saved. Without an active license, PRO settings stop taking effect, and they work again when the license is reactivated. Swatches and the free gallery features keep working.

= Is it compatible with any theme? =

Themes that follow the default WooCommerce product layout usually work. Some themes replace the product gallery or lightbox with their own code, which can get in the way of the hooks this plugin needs. I do not claim it works with every theme. If you run into a conflict, try a default theme such as Storefront on a staging site first.

= Does it support page builders? =

Often yes, and there is a `[svi_wcsc]` shortcode when you need to place the gallery yourself. Some builders take over the product image area and its hooks. If the gallery does not appear, I can usually find a way to hook it back in.

= What happens to my theme's default gallery? =

SVI replaces the theme's image and thumbnail area on the product page, so theme options for that area no longer apply. Each theme builds this section differently, and it is not practical to support every layout, so SVI uses its own.

= What browsers does SVI support? =

SVI does not support Internet Explorer, which Microsoft ended support for some time ago and which does not handle the JavaScript this plugin needs. [Read more about the end of IE support](https://www.microsoft.com/en-us/windowsforbusiness/end-of-ie-support).

SVI is tested in Microsoft Edge, Safari, Chrome and Firefox.

== Screenshots ==

1. Pick a color and the whole gallery changes: main image and every thumbnail.
2. The lightbox on a variation gallery.
3. The SVI Variations Gallery tab, where you build the galleries. The SVI Global gallery and "Hide from Product Loop" shown here are SVI PRO.
4. Showcase under the variation selection. The free version shows one image; SVI PRO shows the whole gallery.
5. Color, image, text and radio swatches on the product page. Every swatch option is free.
6. Swatches on shop and catalog cards. The card image can change on hover or click.
7. Attribute setup: swatch type, shape, size and tooltips for each attribute.
8. The Swatches settings tab. Shape, size, border and colors come first; tooltips, out-of-stock behavior, catalog and filter options follow on the same tab.

== Changelog ==

= 5.3.7 =
* Fix: In the free version the locked SVI PRO options showed the PRO badge on its own centred line and hid their description, which left the control column empty. The badge now sits with the option text and every locked option explains what it does.
* New: A Support menu in every edition, pointing to the right place: the WordPress.org forum for the free version, the smart-variations.com support area for SVI PRO and the contact form for self-managed installs. It used to open the smart-variations.com homepage.

= 5.3.6 =
* Fix: Radio-style swatches kept their text when selected. They were inheriting the filled pill used for colours and images, so the label turned white on a transparent background and disappeared; the radio circle marks the choice and the label stays readable (and bold) now.

= 5.3.5 =
* New: Showcase variations on hover now change the product card image, like catalog swatches do, instead of covering the card and hiding its title, price and swatches. Works with themes whose product cards do not use the default WooCommerce markup (tested with Storefront, OceanWP, Flatsome and Twenty Twenty-Five).
* Fix: The attribute filter widget no longer nests a second list item inside WooCommerce's own one, so the swatches line up with the widget title instead of being indented by the theme's bullet padding. Active filters keep their ring and bold label.

= 5.3.4 =
* Fix: Swatches on shop and catalog cards use the full card width again, and text, label and radio values size to their content instead of being clipped by the fixed swatch square.
* Fix: Image swatches reset the theme's loop-image margin, padding and border, so the photo fills the swatch in themes such as Storefront.

= 5.3.3 =
* New: Import swatches from another swatches plugin, with a dry run first, batched import with progress, per-run undo and WP-CLI. Supported: Woo Variation Swatches, Variation Swatches for WooCommerce, WCBoost Variation Swatches, Variation Swatches for WooCommerce (WooSuite), Product Variations Swatches for WooCommerce (VillaTheme), Woo Product Variation Swatches (RadiusTheme), Product Variation Swatches for WooCommerce (ThemeHunk) and WPC Variation Swatches. Nothing is deleted in the other plugin, and the screen asks for a backup before you start.
* New: The Import Swatches tab follows the "Enable Swatches" switch, is only shown when a supported plugin's data is found, and the Swatches tab points to it.
* Fix: Activating SVI without WooCommerce no longer throws fatal errors. SVI now declares WooCommerce as a plugin dependency (WordPress 6.5+ blocks the invalid activation and blocks deactivating WooCommerce while SVI is active), stays dormant with a clear notice elsewhere, and the settings URL goes to the Plugins screen instead of "Sorry, you are not allowed to access this page."

= 5.3.2 =
* Fix: Product CSV imports now keep the SVI galleries and normalise the imported image data, so imported products render their galleries again.
* Fix: Existing galleries saved with the exported image data are repaired automatically when the product is opened in the admin.

= 5.3.1 =
* Fix: Swatch labels and the selected value caption no longer overlap the swatches in themes that stack the variation rows.
* Fix: Swatches admin screens now load their minified CSS and JavaScript.

= 5.3.0 =
* New: Every swatch option is now included in the free version. There is no lite or PRO split for swatches. SVI PRO continues to add advanced galleries, not extra swatch settings.
* New: Color, image, text and radio swatches, including custom (non-taxonomy) attributes and up to four colors in one swatch.
* New: Swatch styling (shape, size, colors, hover and selected states), tooltips with an optional large image preview, and out-of-stock states (blur, dim, strikethrough or hide).
* New: Keyboard access for swatches (radiogroup, arrow keys, Home, End, Space and Enter).
* New: Per-product swatch overrides.
* New: Swatches on shop and catalog cards, with optional image swap, overflow options, and an optional button that opens the product page with the chosen values selected.
* New: Swatches in the classic "Filter by attribute" widget.
* New: Option to show attributes that have no type as text, color, image or radio swatches, without changing stored data, with an optional fallback to the variation image. Import/export of swatch definitions.
* Fix: The attribute label sat below its swatch row when the theme aligns table cells vertically (WooCommerce block themes do). The label is now measured against the swatch row and lined up with it, at every swatch size and screen width.
* Fix: The Swatches tab follows the "Enable Swatches" switch in the Global tab immediately, without saving, and its settings are never lost while the tab is hidden.
* Fix: The selected value now shows next to the attribute name (for example "Color: Red") and updates on click, keyboard and Clear. It previously appeared at the end of the row and disappeared after Clear.
* Improved: Arrow keys, Home and End now select swatches the way a radio group does, and skip out-of-stock values. On shop and catalog cards they only move focus.
* Improved: Per-product swatch rules now accept up to four colors, the same as terms. Existing two-color rules keep working.
* Fix: Per-product rules for custom attributes with uppercase letters in their name now appear in the product panel.
* Fix: The Quick View AJAX request now responds only when a PRO license is active.
* Changed: Without an active PRO license, saved PRO settings no longer take effect, for example after moving from PRO to Free. Your values stay saved and work again when the license is reactivated.
* Fix: Galleries marked "Hide from Product Loop" are now also skipped when filtering swaps the product image on shop and archive pages.

= 5.2.40 =
* Note: 5.2.40 was not released on its own. These changes ship in 5.3.0.
* Fix: Preserve WooCommerce variation galleries when SVI is disabled for a product or configured to skip products without SVI data.
* Remove the Version 6 announcement from admin notices and move its legacy callbacks to a dedicated include.
* Update: WooCommerce tested up to 11.3.0.
* Fix: Settings stored under the legacy bare field ids (for example `lens`, `slider_effect`, `columns`) were ignored by the settings screen and the runtime, which only read `<tab>_<section>_<field>` keys, so a save replaced them with defaults. The stored values are now migrated once to the current keys; the legacy keys are kept so the change can be rolled back.
* Fix: The settings framework cached resolved settings per option group only, so `get_settings()` and `get_settings(true)` could return each other's key shapes within one request.
* Fix: Export now writes the keys the settings screen reads, and import normalizes payloads (including older bare-id exports) instead of storing them verbatim.
* Fix: "Show Image in Email" now enables order item images. WooCommerce always passes `show_image` to `woocommerce_email_order_items_args`, so the previous "only when missing" check never applied and the option had no effect. Verified in both classic and block (email editor) emails.
* Fix: The Import/Export tab reused the same DOM id for the import file field and the import button, producing invalid duplicate ids.
* Fix: Every gallery option was silently inactive when the JS bundle read `wcsvi.options` as JSON booleans (`true`/`false`), because the guards use `parseInt()`/`parseInt(x) || 0` and `parseInt(true)` is `NaN`. Swatches, lightbox, lens, slider, video, captions, thumbnail and loop options were all disabled by it. Boolean values are now sent as `"1"`/`""`.
* Fix: "Trigger on Thumbnail click" (attribute swap) never matched the clicked image because the gallery image ids are serialised as numbers while the lookup compared them with strings.
* Fix: "Show SRCSET" only produced a `data-srcset` attribute, which browsers ignore. Main images now also receive the native `srcset`/`sizes` attributes (thumbnails keep `data-srcset` so lazy load plugins still find it).
* Fix: Options that were rendered in the settings screen but never read by the runtime: Slider "Centered thumbnails", Magnifier "Disable Lens Zoom Contain", lens/window fade-in duration, and Video "Disable Context Menu".
* Fix: Products that SVI does not render (SVI disabled, or "Disable SVI display" enabled on a product without SVI data) still had their attribute dropdown replaced by swatches. The scripts that bind the swatch clicks are only loaded when SVI renders the product, so those swatches could not be clicked and the variation was impossible to select. Swatches are now only output when SVI renders that product.
* Fix: Clicking an image in "Showcase Images under Variations" did nothing unless the showcase lightbox option was enabled. The image is now shown in the main gallery, and the lightbox option still takes priority when enabled.

= 5.2.39 =
* Fix: Cart, Mini-Cart and Checkout blocks displayed the parent product image even though the Store API payload carried the variation image. The override replaced `src`, `thumbnail`, `srcset` and `sizes` but left `thumbnail_srcset`/`thumbnail_sizes` pointing at the original image, and the blocks resolve `currentSrc` from the thumbnail srcset, so the stale srcset won.

= 5.2.38 =
* Fix: Duplicate product gallery on block themes (Twenty Twenty-Three, Twenty Twenty-Four, Twenty Twenty-Five, Storefront block templates, etc.). WooCommerce injects the classic `woocommerce_before_single_product_summary` hook into block templates for backwards compatibility, so SVI rendered one gallery on that hook and the `woocommerce/product-image-gallery` block rendered a second one. The hook render is now skipped whenever the active block template already contains the gallery block.
* Fix: Block themes discarded the inlined gallery data. Block templates are built before `wp_head()`, so `wp_add_inline_script()` ran before the SVI script handle existed and the product payload was dropped, forcing every view to fetch it through `admin-ajax.php`. Asset handles are now registered on `wp_loaded`, before the template is rendered.
* Fix: When the gallery payload could not be loaded (blocked `admin-ajax.php`, security plugin or a custom integration using a different script handle) the Vue app was still mounted with empty data, threw `Cannot use 'in' operator to search for 'slugs' in false` and left an empty skeleton where the product image should be. The app now leaves the server-rendered image in place, and the payload falls back to an inline JSON block when it cannot be attached to the script handle.
* Update: The initial gallery placeholder now contains the real main image and thumbnails (with `srcset`, `sizes` and `fetchpriority`) behind the skeleton, so the product image is visible even if JavaScript does not run.

= 5.2.37 =
* Fix: The "Remove gallery" bulk action control in the product edit gallery now renders as a clickable link wrapping the trash icon. A chained HTML generator call returned the inner icon element instead of the anchor, so the icon was output without its link wrapper.
* Update: The product edit admin script is now written as ES modules under `src/admin/` and bundled with Vite, so the minified file is generated by `npm run build:admin` (part of `npm run prod`) instead of being maintained by hand. DOM ids, data attributes, input names, AJAX actions and the `WOOSVIADM` API are unchanged. Fixes carried in the rewrite: a repeated attribute-picker reload could leave the tab overlay stuck, gallery creation could skip its completion on one path, delegated handlers accumulated for every gallery on the screen, and several implicit globals would have thrown once the bundle runs in module scope.
* Fix: The variation video dialog now uses the WordPress admin dialog styling instead of WooCommerce's bundled jQuery UI theme - dashicons close cross, admin buttons (Save Video primary, Remove destructive) and a "Video URL" field label - and the modal overlay is stacked at 100101/100102, matching WordPress's own dialog stylesheet, so the whole screen dims instead of only the content area.
* Update: The admin stylesheet is minified by `npm run build:css` (esbuild), which is part of `npm run prod`.

= 5.2.36 =
* Fix: Slider gallery crash ("Cannot read properties of null (reading 'el')") when more than one SVI slider gallery existed in the page DOM at the same time (e.g. Quick View, product loop showcase, or hidden builder templates). Swiper was initialised against a page-wide CSS selector instead of the specific gallery instance; each slider now initialises against its own DOM node.
* Update: Freemius SDK updated to 2.13.5; WooCommerce tested up to 11.2.1.

= 5.2.35 =
* Update: Minor adjustments.

= 5.2.33 =
* Update: Bumped minimum requirements to WordPress 6.0 and WooCommerce 8.2 to ensure stability with modern environments.

= 5.2.32 =
* Fix: WooCommerce 11.1.0 compatibility. Prevent native WooCommerce JS from detaching the SVI gallery by replacing the DOM node when variation data is loaded inline (<= 30 variations).

= 5.2.31 =
* Fix: Prevent raw HTML text rendering in the product edit gallery. This resolves an issue where strict XSS escaping incorrectly encoded fully-formed HTML tags (like gallery titles and image wrappers).

= 5.2.30 =
* Security Fix: Added visibility and read_post permission checks to `sviloadProduct` and `loadProduct` AJAX endpoints to prevent unauthorized access to private product gallery data.
* Compatibility: WordPress tested up to 7.1.
* Compatibility: WooCommerce tested up to 11.1.0.

= 5.2.29 =
* Compatibility: WordPress tested up to 7.0.
* Update: Freemius SDK updated to latest stable version.

= 5.2.28 =
* Compatibility: WooCommerce tested up to 10.7.0.
* Update: Freemius SDK updated to 2.13.1.
* Fix: PHP 8+ compatibility audit - added `isset()` guard on `$_POST['product-type']` in `save_woosvibulk_meta()` to prevent E_WARNING when hook fires without POST body (e.g. background order processing).
* Fix: PHP 8+ compatibility audit - added `isset()` + `intval()` guard on `$_POST['data']` in `reloadSelect_json()` AJAX handler.
* Fix: PHP 8+ compatibility audit - `woosvi_slugify()` now guards `$_POST['data']` with `isset()` and handles array input before calling `strtolower()`, preventing Fatal TypeError.
* Fix: PHP 8+ compatibility audit - added `isset()` guard on `$_POST['id']` in `render_quick_view_frontend()`.
* Fix: PHP 8+ - replaced `array_pop(explode(...))` with an IIFE pattern in gallery export helpers to avoid "Only variables should be passed by reference" warning.

= 5.2.27 =
* Fix: PHP 8+ compatibility - `woosvi_esc_html()` now checks if `$_POST['data']` is an array before calling `implode()`, preventing fatal TypeError when Printify/Gelato integrations pass a string value.
* Fix: PHP 8+ compatibility - Removed strict `string` type hint from `$size` parameter in `filter_main_product_image()` to accept array sizes (e.g. `[32, 32]`) passed by WooCommerce email thumbnail generation.

= 5.2.26 =
* Fix: Breakdance compatibility - added final AJAX fallback in gallery bootstrap when `data-wcsvi` is not available, using `sviloadProduct` to load product gallery payload safely.
* Fix: Registered missing AJAX handlers for both guests and logged-in users (`wp_ajax_sviloadProduct` and `wp_ajax_nopriv_sviloadProduct`).
* Improvement: Routed debug `console.log` output through `Vue.prototype.consoleLog`, gated by `SCRIPT_DEBUG` (`wcsvi.prod`).

= 5.2.25 =
* Added: GDPR-compliant local asset loading option - Load PhotoSwipe, Plyr, Swiper, and imagesLoaded from plugin directories instead of CDN (Cloudflare, jsDelivr, UNPKG) to protect visitor privacy and comply with GDPR regulations
* Feature: New "Use Local Assets" toggle in Global settings (enabled by default) to switch between local and CDN loading modes

= 5.2.24 =
* Improved: Enhanced admin review notice with detailed v6 preview showcasing upcoming features
* Info: Version 6 development update - preparing major architecture improvements, enhanced gallery builder, better mobile experience, and performance optimizations
* Added: Divi Builder compatibility - SVI gallery now replaces Divi's WooCommerce Images module
* Fix: CSS class conflicts when using Divi theme - conditionally removes 'images' class to prevent styling conflicts
* Compatibility: 'Showcase Images under Variations' option now fully supported with Divi Builder

= 5.2.22 =
* Fix: PHP 8.2 compatibility - Added proper property declaration for $runSitePress to prevent dynamic property deprecation warning in Smart_Variations_Images_Admin class
* Fix: PHP 8+ TypeError in filter_wc_get_template - Made $template_path parameter nullable to prevent crashes when other plugins (e.g., WooCommerce Mercado Pago) pass null values
* Fix: Slider TypeError when thumbnails disabled - Added null check for galleryThumbs.slideTo() to prevent JavaScript error that broke lens functionality after slide changes
* Update WC,WP compatibility

= 5.2.21 =
* Fix: Prevent PHP fatal when a product has no featured image by normalizing attachment IDs and falling back to WooCommerce placeholders in gallery rendering.
* Fix: Default to minified assets when not in SCRIPT_DEBUG to reduce JS parse issues with external minifiers or rewrites.
* Fix: Avoid PHP 8 TypeError on non-product templates (e.g. Elementor/YITH account or quote pages) by allowing nullable template args in the `wc_get_template` filter hook.
* Update WC,WP,Freemius SDK version compatibility

= 5.2.20 =
* Fixed Elementor builder templates not displaying the SVI gallery by routing `wc_get_template()` calls through a plugin-aware stub.
* Added safer fallback when the gallery output is empty to prevent blank product images in custom templates.
* Reduced front-end DOM payload by localizing gallery data to scripts instead of embedding large JSON blobs in markup.
* Added lightweight skeleton placeholders so the gallery retains its layout while the Vue app boots.
* Introduced product-data caching and Store API image overrides so Gutenberg Cart & Checkout blocks show the matching variation thumbnail.
* Refined admin order item filters to avoid duplicate thumbnails when viewing/editing orders.

= 5.2.19 =
* Added compatibility fix for Gutenberg Product Template — ensures gallery renders inside WooCommerce block `<div data-block-name="woocommerce/product-image-gallery">`.
* Improved compatibility with block-based themes.
* Added relocation fallback script for older versions of SVI to maintain gallery positioning.

= 5.2.18 =
* Added feature: Filter Attribute on Shop/archive Pages to show fitlered image
* Added premium feature: Filter Attribute Animation (Sliding effect on hover for Diagonal, Vertical, and Horizontal layouts in Filter Attribute Image Swapping)
* Update WC,WP,Freemius SDK version compatibility

= 5.2.17 =
* Update WC,WP,Freemius SDK version compatibility
* Minor bug fixed

= 5.2.16 =
* Update WC,WP,Freemius SDK version compatibility
* Update SwiperJS to v11
* Minor bug fixed

= 5.2.15 =
* Resolved JavaScript character encoding issue by decoding HTML entities in the title attribute before assigning it to the image element.
* Update WC,WP,Freemius SDK version compatibility

= 5.2.14 =
* Update WC,WP version compatibility
* Fix Slider Lens Issue

= 5.2.13 =
* Update WC,WP version compatibility
* Updated Vue.js to version 2.7.15 for improved compatibility.
* Updated vue-loader to version 15.11.1 to match Vue.js version.
* Updated laravel-mix to version 6.0.49 for enhanced build capabilities.
* Upgraded laravel-mix-polyfill to version 3.0.1 for better cross-browser support.
* Updated node-sass to version 9.0.0 for compatibility with the latest Node.js.
* Upgraded postcss to version 8.4.32 for improved CSS processing.
* Updated sass-loader to version 13.3.2 for enhanced SASS support.
* Fix PHP warnings
* Fix video player Aspect Ratio 

= 5.2.12 =
* Resolved an issue where only the SVI meta data was being exported. All relevant metadata will now be correctly included in exports.
* Video support 1:1
* Update WC,WP & Freemius SDK 2.6

= 5.2.11 =
* Fix possible Fatal error: Uncaught TypeError on Backwards compatibility
* Update WC,WP & Freemius SDK 2.5.12 version compatibilty 

= 5.2.10 =
* Fix readme file

= 5.2.9 =
* Update WC,WP & Freemius SDK version compatibilty 
* Added ALT attribute option

= 5.2.8 =
* Update WC,WP & Freemius SDK version compatibilty 
* Fix possible Video/slider delay

= 5.2.7 =
* Update WC,WP & Freemius SDK 2.5.8 version compatibilty 
* Fix .hidden class could conflict
* Added alert on product SVI Gallery when attribute changed or missing 
* Fix error notices

= 5.2.6 =
* Update WC version compatibilty 
* Update WP version compatibilty 
* Update Freemius SDK 2.5.6
* Added Product Image shortcode to be used in UX Builders or other ocassions

= 5.2.5 =
* Fix SVI Default image swapping
* Fix Swiper Cube effect

= 5.2.4 =
* Update Swiper v8
* Fix Swiper arrows color white/black/blues
* Fix Swiper Bullets clickable

= 5.2.3 =
* Fix Swatches Display
* Update Freemius SDK v2.5.3
* Update WC version compatibilty 
* Update WP version compatibilty 

= 5.2.2 =
* Update WC version compatibilty 
* Update WP version compatibilty 
* Updated slider version to SwiperJS v8.4.4
* Updated Video player to Plyr v3.7.2
* Fix Video AutoPlay/Loop/Ratio

= 5.2.1 =
* Fix fatal error due to most not using PHP 8 str_ends_with()

= 5.2.0 =
* Removed ReduxFramework for admin Management
* Fix slider image jump on navigation slider
* Fix outline on swiper lazyLoad
* Unlocked some PRO features now FREE

= 5.1.12 =
* Update WC compatibility 6.5
* Fix compatibility with builders

= 5.1.11 =
* Update WC compatibility 6.4
* Updated slider version to SwiperJS v8
* Updated Video player to Plyr 3.7.0
* Unlocked Stacked Option
* Unlocked Multiple variation gallery creation

== Upgrade Notice ==

= 5.3.0 =
Every swatch option is now free. After updating, review WooCommerce → SVI → Swatches. Existing color, image and label data is kept. If your PRO license is not active, saved PRO gallery settings pause until you reactivate it. Your settings are kept.
