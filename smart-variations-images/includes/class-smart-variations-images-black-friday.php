<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Freemius helper is a public compatibility contract.
/**
 * Black Friday campaign notice for unlicensed installs.
 *
 * @package    Smart_Variations_Images
 * @subpackage Smart_Variations_Images/includes
 */

defined('WPINC') || die;

/**
 * Renders, schedules and dismisses the Black Friday admin notice.
 *
 * @package    Smart_Variations_Images
 * @subpackage Smart_Variations_Images/includes
 */
class Smart_Variations_Images_Black_Friday
{
    /**
     * Coupon appended to the checkout URL.
     */
    const COUPON = 'BF50';

    /**
     * Campaign end, in the Europe/Lisbon timezone.
     */
    const DEADLINE = '2026-12-01 23:59:59';

    /**
     * Timezone the deadline is measured in.
     */
    const TIMEZONE = 'Europe/Lisbon';

    /**
     * User meta flag set once the notice is dismissed.
     */
    const DISMISS_META = 'svi_bf_dismissed';

    /**
     * Query argument used by the dismiss link.
     */
    const DISMISS_ARG = 'svi_bf_dismiss';

    /**
     * Nonce action and field name of the dismiss link.
     */
    const NONCE = 'svi_bf_dismiss';

    /**
     * Product page linked from the secondary call to action.
     */
    const PRODUCT_URL = 'https://www.smart-variations.com/black-friday/';

    /**
     * Screens the notice is allowed on.
     */
    const SCREENS = array('dashboard', 'plugins', 'update-core');

    /**
     * Register the admin hooks.
     */
    public function run()
    {
        add_action('admin_init', array($this, 'dismiss'));
        add_action('admin_notices', array($this, 'render'));
    }

    /**
     * Persist the dismissal for the current user and reload the same screen.
     */
    public function dismiss()
    {
        if (!isset($_GET[self::DISMISS_ARG]) || !current_user_can('manage_options')) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

        if (!wp_verify_nonce($nonce, self::NONCE)) {
            return;
        }

        update_user_meta(get_current_user_id(), self::DISMISS_META, 'yes');

        $redirect = remove_query_arg(array(self::DISMISS_ARG, '_wpnonce'));
        wp_safe_redirect($redirect ? $redirect : admin_url());
        exit;
    }

    /**
     * Print the campaign card.
     */
    public function render()
    {
        if (!$this->should_render()) {
            return;
        }

        $checkout_url = add_query_arg('coupon', self::COUPON, svi_fs()->get_upgrade_url());
        $dismiss_url  = add_query_arg(
            array(
                self::DISMISS_ARG => '1',
                '_wpnonce'        => wp_create_nonce(self::NONCE),
            )
        );
        ?>
        <style>
            div.svi-bf-card,
            div.svi-bf-card * {
                box-sizing: border-box;
            }

            div.svi-bf-card {
                position: relative;
                display: flex;
                gap: 24px;
                max-width: 900px;
                margin: 16px 0 24px;
                padding: 24px;
                border-radius: 12px;
                background: #0B1220;
                font-family: inherit;
            }

            .svi-bf-card p,
            .svi-bf-card h2,
            .svi-bf-card ul {
                margin: 0;
                padding: 0;
                font-family: inherit;
            }

            .svi-bf-card a {
                text-decoration: none;
                box-shadow: none;
            }

            .svi-bf-copy {
                display: flex;
                flex: 1 1 auto;
                flex-direction: column;
                min-width: 0;
            }

            .svi-bf-kicker {
                font-size: 12px;
                line-height: 1.4;
                text-transform: uppercase;
                letter-spacing: 2px;
                color: #10B981;
            }

            .svi-bf-headline {
                margin: 8px 0 6px;
                font-size: 30px;
                font-weight: 700;
                line-height: 1.2;
                color: #FFFFFF;
            }

            .svi-bf-subhead {
                font-size: 15px;
                line-height: 1.5;
                color: #94A3B8;
            }

            .svi-bf-benefits {
                margin-top: 18px;
                list-style: none;
            }

            .svi-bf-benefits li {
                position: relative;
                margin: 0 0 14px;
                padding-left: 16px;
                font-size: 14px;
                line-height: 1.5;
                color: #E2E8F0;
            }

            .svi-bf-benefits li:last-child {
                margin-bottom: 0;
            }

            .svi-bf-benefits li::before {
                content: "";
                position: absolute;
                top: 8px;
                left: 0;
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: #10B981;
            }

            .svi-bf-buttons {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-top: 18px;
            }

            .svi-bf-primary,
            .svi-bf-secondary {
                display: inline-block;
                padding: 12px 20px;
                border-radius: 8px;
                font-size: 14px;
                line-height: 1.4;
                text-align: center;
            }

            .svi-bf-primary {
                border: 1px solid #10B981;
                background: #10B981;
                color: #FFFFFF;
                font-weight: 600;
            }

            .svi-bf-primary:hover,
            .svi-bf-primary:focus {
                border-color: #0EA371;
                background: #0EA371;
                color: #FFFFFF;
            }

            .svi-bf-secondary {
                border: 1px solid #334155;
                background: transparent;
                color: #E2E8F0;
            }

            .svi-bf-secondary:hover,
            .svi-bf-secondary:focus {
                border-color: #475569;
                color: #FFFFFF;
            }

            .svi-bf-price {
                display: flex;
                flex: 0 0 180px;
                flex-direction: column;
                width: 180px;
            }

            .svi-bf-price-old {
                font-size: 18px;
                color: #64748B;
                text-decoration: line-through;
            }

            .svi-bf-price-new {
                margin: 2px 0;
                font-size: 34px;
                font-weight: 800;
                line-height: 1.1;
                color: #FFFFFF;
            }

            .svi-bf-price-meta {
                font-size: 12px;
                color: #94A3B8;
            }

            .svi-bf-dismiss {
                position: absolute;
                top: 12px;
                right: 14px;
                font-size: 20px;
                line-height: 1;
                color: #64748B;
            }

            .svi-bf-dismiss:hover,
            .svi-bf-dismiss:focus {
                color: #E2E8F0;
            }

            @media (max-width: 900px) {
                div.svi-bf-card {
                    flex-direction: column;
                    gap: 0;
                }

                .svi-bf-copy {
                    display: contents;
                }

                .svi-bf-kicker {
                    order: 1;
                }

                .svi-bf-headline {
                    order: 2;
                }

                .svi-bf-price {
                    order: 3;
                    flex: 0 0 auto;
                    width: 100%;
                    margin: 6px 0 14px;
                }

                .svi-bf-subhead {
                    order: 4;
                }

                .svi-bf-benefits {
                    order: 5;
                }

                .svi-bf-buttons {
                    order: 6;
                    flex-direction: column;
                }

                .svi-bf-primary,
                .svi-bf-secondary {
                    width: 100%;
                }
            }
        </style>
        <div class="svi-bf-card" role="region" aria-label="<?php esc_attr_e('Black Friday offer', 'smart-variations-images'); ?>">
            <a class="svi-bf-dismiss" href="<?php echo esc_url($dismiss_url); ?>" aria-label="<?php esc_attr_e('Dismiss this offer', 'smart-variations-images'); ?>">&times;</a>
            <div class="svi-bf-copy">
                <p class="svi-bf-kicker"><?php esc_html_e('Black Friday', 'smart-variations-images'); ?></p>
                <h2 class="svi-bf-headline"><?php esc_html_e('50% off your first year', 'smart-variations-images'); ?></h2>
                <p class="svi-bf-subhead"><?php esc_html_e('First 100 licences. Then 30% until 1 December.', 'smart-variations-images'); ?></p>
                <ul class="svi-bf-benefits">
                    <li><?php esc_html_e('One gallery per colour — not per variation', 'smart-variations-images'); ?></li>
                    <li><?php esc_html_e('Video in the gallery: YouTube, Vimeo, self-hosted', 'smart-variations-images'); ?></li>
                    <li><?php esc_html_e('Swatches, magnifier and lightbox built in', 'smart-variations-images'); ?></li>
                </ul>
                <div class="svi-bf-buttons">
                    <a class="svi-bf-primary" href="<?php echo esc_url($checkout_url); ?>"><?php esc_html_e('Claim 50% off', 'smart-variations-images'); ?></a>
                    <a class="svi-bf-secondary" href="<?php echo esc_url(self::PRODUCT_URL); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('See what\'s included', 'smart-variations-images'); ?></a>
                </div>
            </div>
            <div class="svi-bf-price">
                <span class="svi-bf-price-old">$69.99</span>
                <span class="svi-bf-price-new">$34.99</span>
                <span class="svi-bf-price-meta"><?php esc_html_e('first year · PRO', 'smart-variations-images'); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * Whether the current screen, user and campaign state allow the notice.
     *
     * @return bool
     */
    private function should_render()
    {
        if (!$this->is_campaign_live() || !current_user_can('manage_options')) {
            return false;
        }

        if ('' !== (string) get_user_meta(get_current_user_id(), self::DISMISS_META, true)) {
            return false;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        if (!$screen instanceof WP_Screen) {
            return false;
        }

        if (false !== strpos($screen->id, 'smart-variations')) {
            return false;
        }

        return in_array($screen->id, self::SCREENS, true);
    }

    /**
     * Whether the campaign is still running and the install is unlicensed.
     *
     * @return bool
     */
    private function is_campaign_live()
    {
        if (!function_exists('svi_fs')) {
            return false;
        }

        $freemius = svi_fs();

        if (!is_object($freemius) || !method_exists($freemius, 'is_paying')) {
            return false;
        }

        if ($freemius->is_paying()) {
            return false;
        }

        $timezone = new DateTimeZone(self::TIMEZONE);
        $now      = new DateTimeImmutable('now', $timezone);
        $deadline = new DateTimeImmutable(self::DEADLINE, $timezone);

        return $now <= $deadline;
    }
}
