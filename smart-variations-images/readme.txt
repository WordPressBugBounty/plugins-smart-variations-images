=== Smart Variations Images & Swatches for WooCommerce ===
Contributors: drosendo
Tags: woocommerce, variation swatches, variation images, product gallery, color swatches
Requires at least: 6.0
Tested up to: 7.2
WC requires at least: 8.2
WC tested up to: 11.3.0
Stable tag: 5.3.15
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Every swatch option free, plus a full gallery per color that every size reuses. Switch from your current plugin in one click, with undo.

== Description ==

**Every swatch option is free. No lite version, no locked swatch settings.**

**Smart Variations Images & Swatches (SVI)** turns WooCommerce variation dropdowns into **color, image, text or radio swatches**. When a shopper picks one, **the whole product gallery changes**: the main image and every thumbnail, not only the first photo.

**One gallery per color, reused by every size.** A hoodie in 6 colors and 7 sizes has 42 variations. With SVI it needs **6 galleries, not 42**.

**Already using another plugin?** SVI imports your **swatches and variation galleries in one click**, shows a **preview before anything changes**, and lets you **undo** the import.

[youtube https://youtu.be/QMV8XBeub_o]

[Live demo](https://svi.rosendo.pt/free) | [Support forum](https://wordpress.org/support/plugin/smart-variations-images/) | [How native variation galleries compare with galleries per attribute](https://www.smart-variations.com/e-commerce-news-updates/woocommerce-native-vs-attribute-variation-galleries/?utm_source=wporg&utm_medium=listing&utm_campaign=evergreen&utm_content=readme-article)

= Why stores use SVI =

* **Full swatches in the free version.** Color, image, text and radio, styling, tooltips, out-of-stock states, shop cards and filters. Nothing about swatches is locked.
* **Galleries per attribute value.** Set the photos once per color (or material, or finish) and every combination reuses them.
* **Switch in one click, with undo.** Import from the most popular variation gallery and swatches plugins, and from WooCommerce's own variation galleries.
* **Works alongside WooCommerce core.** Import the WooCommerce 11.1+ variation galleries, or write SVI galleries back to them so core blocks, REST and feeds see the same pictures.
* **Fast and private.** Scripts and styles load **from your own server, with no third-party CDN**, and the **first gallery image is rendered by the server** with high fetch priority.
* **Variations as products.** Show chosen variations as **their own cards in the shop**, free.
* **Ready in minutes.** A short **setup wizard** on first activation, and swatches are **on by default** for new installs.

= Swatches (free, every option) =

SVI PRO does not unlock extra swatch types or swatch settings. **Everything below is in the free version.**

* **Color, image, text and radio swatches**, including custom (non-taxonomy) attributes
* **Up to four colors in one swatch**, for two-tone and multi-color values
* Shape, size, colors, hover and selected states
* **Tooltips**, with an optional large image preview
* The selected value next to the attribute name, for example "Color: Red"
* **Out-of-stock values** can be blurred, dimmed, struck through or hidden
* **Keyboard access**: swatches work as a radio group, with arrow keys, Home, End, Space and Enter
* **Per-product overrides**, so one product can use different types or colors
* **Swatches on shop and catalog cards**, with optional image swap on hover or click. An optional **View product** button appears once a swatch is chosen and opens the product page with those values already selected
* Color and image swatches in the classic **"Filter by attribute"** widget
* WooCommerce **"Color / image" attributes** (the core visual attribute type) render as SVI swatches on classic themes too
* Attributes that have no type can be shown as text, color, image or radio swatches with one setting. Nothing is saved to your attributes, and switching back to Dropdown undoes it
* Import and export of swatch definitions

= Variation galleries (free) =

* **One gallery per attribute value**, reused by every combination that shares it
* **Unlimited images** in each gallery, in the order you drag and drop
* The gallery changes **as soon as one attribute is picked**, without waiting for the full combination
* **Filter Attribute Image Swapping**: when a shopper filters the shop by color, each product card shows that color's image
* **Showcase Variations** on shop and catalog cards, and one showcase image under the variation selection
* **Slider** with navigation arrows, or a **stacked layout**
* **Magnifier lens** (Lens, Window or Inner) and **lightbox**
* Hide thumbnails until a variation is chosen
* Shortcode `[svi_wcsc]` for builders and custom layouts
* **WPML-aware**
* Import and export of global plugin settings

= Variations as products (free) =

Show the variations you choose as **their own product cards** on the shop page, category and tag pages and in search results. Tick **"Show in shop as its own product"** on each variation, and turn the module on in the SVI settings.

= Switch in one click: Migration (free) =

Moving from another plugin should not mean rebuilding your catalog. The **Migration** tab in the SVI settings reads what is already in your database and copies it into SVI.

* **Swatches** from **8 popular swatches plugins**: swatch types, term colors and images, labels, per-product overrides and matching global settings
* **Variation galleries** from **5 popular variation gallery plugins**, and from the **WooCommerce 11.1+ variation galleries**
* The Migration tab lists **every plugin SVI can read**, and marks the ones not installed on your site
* **Preview first**: a dry run lists every item and says what would happen to it
* **Nothing is deleted.** The other plugin keeps all of its data, and existing SVI values are kept unless you tick "Overwrite"
* **Backup per product and undo per run**, from the Recent imports table
* Batches with a progress bar, resumable if the tab closes, and **WP-CLI** commands for large stores

Back up your database before any import.

= WooCommerce core and search =

* **Import WooCommerce variation galleries**: variations that share one image set per color become one SVI gallery for that color
* **"Also save SVI galleries to WooCommerce variations"** (off by default): writes each variation image and gallery back from SVI, so the core Product Gallery block, REST API, feeds and structured data see the same pictures
* **ProductGroup structured data**: each variation is described with the images of its SVI gallery and a link that preselects it. It switches itself off when your SEO plugin already prints variants

= Fast by design =

* **Local assets**: slider, lightbox, video player and lens scripts load from your own server, **no third-party CDN**, which also helps with privacy rules such as the GDPR
* **First image rendered by the server** with `fetchpriority="high"`, so shoppers see the product photo before the scripts finish loading
* Native `srcset` and `sizes` on main images, so browsers pick the right size

= SVI PRO: advanced galleries =

[Live demo](https://svi.rosendo.pt/pro) | [See SVI PRO plans](https://www.smart-variations.com/?utm_source=wporg&utm_medium=listing&utm_campaign=evergreen&utm_content=readme-pro#pricing)

**The free version is complete for swatches and galleries.** SVI PRO is for stores that want **the chosen variation to follow the order**, richer galleries and more control. It runs on the galleries you already built, so **nothing has to be set up again**.

**After the click: the right image follows the order**

* **The chosen variation image in the cart, mini-cart and checkout**, including the Cart and Checkout blocks
* **The same image in order emails**, the admin order screen and the customer's order details

**On the product page**

* **Video in the variation gallery** (YouTube, Vimeo or MP4)
* **Showcase Images under Variations with the whole gallery**, in columns, instead of one image
* **SVI Global**: images added to every variation gallery, at the start or the end
* **Exact-match triggering**, so a gallery is used only when the full combination matches
* **Sticky product summary** next to a stacked gallery, stacked columns, and stacked layout forced on mobile
* **Quick View append**: SVI puts its gallery inside the Quick View your theme or another plugin already provides. It does not add Quick View itself
* **Advanced slider, lightbox and magnifier options** (pagination, lazy load, lightbox thumbnails, round or square lens and more), plus image captions

**On shop and catalog pages**

* **Showcase Variations with every gallery**, a limit per product and a choice of position
* **"Hide from Product Loop"** per gallery, also respected when filtering swaps the product image
* Filter Attribute Animation for split images

**Variations as products, PRO**

* **One card per color** instead of one per variation
* **Bulk setup**, **hide the parent product**, **variation filters** and **add to cart on the card**

**For large catalogs and marketplaces**

* **CSV import of product galleries** through the WooCommerce product importer
* **REST API** (GET and POST) for product galleries
* **Variation feeds** at a private URL, one row per variation with its SVI gallery images: **XML for Google Merchant Center** and **JSON/CSV in the OpenAI product feed format**

SVI PRO includes **priority ticket support**. There is a **7-day free trial** (payment method required) and a **14-day money-back guarantee**, no questions asked.

== Installation ==

= Automatic install from the WordPress dashboard =

1. Log in to your admin panel
2. Go to Plugins → Add New
3. Search for **Smart Variations Images & Swatches**
4. Click Install, then Activate
5. The **setup wizard** opens once: pick your swatches, gallery layout and first products, or skip any step

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

= Switching from another plugin? =

After activation, open **WooCommerce → SVI → Migration**, choose Swatches or Galleries, run the preview, then import. Keep the other plugin active until you have checked the result.

== Frequently Asked Questions ==

= Are some swatch options only available in SVI PRO? =

No. From 5.3.0, every swatch option is in the free version. There is no lite or PRO split for swatches. SVI PRO adds gallery features: video, the variation image in the cart, emails and orders, the full showcase, sticky product summary, Quick View append, variation feeds, and finer slider, lightbox and lens controls.

= Is the free version enough for my store? =

Often, yes. Swatches, galleries per attribute, the gallery swap, the filter image swap, slider, lens, lightbox, migration and variations as separate products are free. Consider SVI PRO when you want the variation image in the cart and in emails, video in the gallery, the whole gallery shown under the variations and on shop cards, or one card per color in the shop.

= WooCommerce 11.1 has variation galleries. Do I still need SVI? =

Not always. WooCommerce 11.1 lets you set a gallery on each variation row. If each variation has its own photos, that is simple and free. SVI stores the gallery once per attribute value, so a color gallery is reused by every size. That saves the most time when the same photos repeat across many variations.

SVI also works with the core galleries: you can **import** them from the Migration tab, and you can turn on **"Also save SVI galleries to WooCommerce variations"** so WooCommerce and other plugins see the same pictures.

= Can I move from my current swatches or gallery plugin? =

Yes. Open **WooCommerce → SVI → Migration**. SVI lists the plugins it can read, previews the import, keeps a backup and lets you undo each run. Nothing is deleted in the other plugin. Back up your database before you start.

= How do I set up the galleries? =

1. Create a product and build its attributes and variations
2. Open the **SVI Variations Gallery** tab and add a gallery for each attribute value you want to show, for example one per color
3. Save the product
4. Go to WooCommerce → SVI → Global and enable SVI so it runs on the storefront
5. If you want help with the layout, [watch the walkthrough](https://youtu.be/QMV8XBeub_o)

= How do I set up swatches? =

1. On new installs swatches are already on. On older installs, turn on **Enable Swatches** under WooCommerce → SVI → Global
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

= What happens on my first activation? =

A fresh install opens the SVI setup wizard once, after a single activation: swatches, gallery layout, your first variable products and a few links. Every step can be skipped, and Exit returns to the Plugins screen. You can run it again under WooCommerce → SVI → Support → Run the setup wizard. Updates, bulk and network activations, and WP-CLI never redirect.

= Why did I get a "What's new" notice? =

After a minor or major update (5.3 to 5.4, for example), each user sees one dismissible notice that links to WooCommerce → SVI → What's new. The tab lists the release highlights with images bundled in the plugin. Patch releases, such as 5.3.8 to 5.3.9, do not show it, and the notice does not come back once you dismiss it or open the tab.

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

= 5.3.15 =
* Fix: catalog swatches now appear on block themes too. The shop loop hook they were printed from never fires there, so Twenty Twenty-Four, Twenty Twenty-Five and any theme using the Product Collection block showed plain cards. The same markup is now appended to every collection item, with the same hover, click, picked-image and "View product" behaviour as classic themes.
* Fix: the module data cleanup moved from a bundled uninstall.php to Freemius' after_uninstall hook, which is the hook Freemius tracks on. Deleting the plugin with "Delete this module's data on uninstall" on still removes only the module meta and the bulk run state, and nothing when the option is off.
* Fix: a swatch picked on a shop card kept its picture only while the pointer was over the card. Hovering another value and leaving the card used to fall back to the default image; the chosen value stays on the card now, and hover still previews the other values. Click mode and the Clear link behave as before.
* Tweak: the settings tabs sit in a vertical sidebar now, ordered by how often they are opened (Global, Swatches, the gallery addons, then the shop features). Save stays at the foot of the sidebar, the Migration sub-tabs are a strip above the pane, and screens narrower than 960px keep the horizontal strip. Groups are separated by a thin rule.

= 5.3.13 =
* New: Variable products are described with a ProductGroup node (variesBy + one Product per variation), each variant listing the images of its SVI gallery and linking to the URL that preselects it. The parent Product printed by WooCommerce is untouched, and the node switches itself off when Yoast WooCommerce SEO or Rank Math already prints variants (filter svi_variant_schema_enabled to force it).
* New PRO: variation feeds at a secret URL, one row per variation with the SVI gallery images: XML for Google Merchant (id, item_group_id, image_link, additional_image_link, link, price, availability) and JSON/CSV for the OpenAI product feed (image_url, additional_image_urls). Rows are cached and refreshed by Action Scheduler after a product changes; svi_variation_image_urls and svi_vs_feed_row let other feed plugins reuse the images.

= 5.3.12 =
* New: Import the WooCommerce 11.1+ variation galleries from the Migration tab. Variations that share one image set per attribute value (all the Red sizes, for example) become one SVI gallery for that value; when the sets differ, one gallery per variation is written. Backup per product, undo per run and WP-CLI, like the other sources.
* New: "Also save SVI galleries to WooCommerce variations" (off by default, Migration tab) writes each variation image and gallery back from its SVI gallery, so the core Product Gallery block, REST v3, feeds and structured data see the same pictures. The variation images are backed up before the first write and nothing is deleted when the setting is off.
* New: WooCommerce "Color / image" (`wc-visual`) attributes render as SVI swatches on classic themes, with the colour or the image stored on each value.

= 5.3.11 =
* New: Show variations as separate products in the shop (free).
* New PRO: One card per color, bulk setup, hide parent, variation filters and add to cart on the card.

= 5.3.10 =
* New: Migration tab (WooCommerce → SVI settings) with vertical panes for
  swatches and variation galleries. Copies variation galleries from Additional
  Variation Images Gallery for WooCommerce, WooCommerce Additional Variation
  Images, RadiusTheme Variation Images Gallery, Ajaxy Multiple Variation Image
  and WooThumbs for WooCommerce. The import is additive, keeps a backup per
  product and can be undone. Import / Export is now the last tab.
* New: both migration panes list every plugin SVI can read. Plugins that are
  not installed are greyed out with the reason next to them, so the table also
  answers "what can SVI import from?" before anything is imported. Recent
  imports are a table with one undo action per run, refreshed after each
  import.

= 5.3.9 =
* New: First-run setup wizard for swatches and galleries. Five full-screen steps ("Welcome, Swatches, Gallery, First gallery, Done") with a live swatch preview, gallery layout previews, up to five variable products with a link to their SVI Swatches tab, and links to the guides, the demo and the support forum. Every step can be skipped, Exit returns to Plugins, and each step only changes the options it shows.
* New: A fresh install opens the setup wizard once, after a single activation. Bulk, network and WP-CLI activations, updates and later activations never redirect.
* New: When Freemius still has to ask for a licence or an opt-in, the wizard waits: the licence screen or the opt-in comes first, then the wizard opens. Skipping the redirect never loses the invitation.
* New: Existing installs updating to 5.3.9 see one dismissible notice on the Plugins screen, the Dashboard and the SVI settings only. For the whole site it disappears once dismissed or once the wizard is opened.
* New: The wizard reopens from WooCommerce -> SVI -> Support -> Run the setup wizard, pre-filled with the settings the store already has. Nothing changes unless a step is saved.
* New: When another swatches plugin left data behind, the wizard offers the existing Import Swatches tool.
* New: In the free version the last wizard step offers SVI PRO: a dated campaign card while a promotion runs, with coupon and prices in US dollars, and a plain upgrade card the rest of the year. Paying stores never see it.
* New: A "What's new" tab in the SVI settings lists the release highlights on a full-width panel, with brand illustrations bundled in the plugin, a version badge, a link to the setup wizard and a link to the full changelog.
* New: In the free version the What's new tab shows what SVI PRO adds, with an upgrade link.
* New installs: swatches enabled by default. A site that has never saved an SVI setting starts with swatches on, attributes without a type rendered as text buttons instead of a dropdown, and a one-time notice linking to the Swatches settings. Existing settings, including swatches switched off, are left exactly as they are.
* Shop swatch button now reads View product. On shop and catalog cards the button only opens the product with the chosen values, so the label no longer promises an add to cart. It also keeps the theme's own button styling (size, colours, border, hover icon) instead of the plugin's compact pill.

= 5.3.8 =
* Fix: A gallery could not be created for an attribute value whose slug contains a special character, such as the ″ in "18″ x 24″". The gallery key lost its percent-encoded octets (18%e2%80%b3 became 18), so it never matched the value and was reported as "(Missing)", and its images never appeared on the product page. Those values now keep their gallery, and galleries already saved that way are re-linked automatically when the product is opened. Values with plain slugs are untouched.
* Fix: The settings tab bar scrolls sideways with arrow buttons when the tabs do not fit, instead of pushing the whole page wider. The Save button stays at the right end, and the bar now lines up with the settings panel below it.
* Fix: In the free version "Showcase Images under Variations" showed the second image of a gallery instead of the first.
* Fix: Deleting and recreating an attribute no longer detaches its galleries through a fuzzy slug match. Galleries are only re-linked when the match is unambiguous.

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
