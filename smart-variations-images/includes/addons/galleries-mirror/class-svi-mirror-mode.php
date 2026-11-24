<?php
/**
 * Resolves whether the mirror should act in this install.
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SVI_Mirror_Mode')) {
    /**
     * One place that answers "should we mirror?", for this release and for a
     * host that already reads schema 3 natively.
     */
    final class SVI_Mirror_Mode
    {
        /**
         * Whether the host plugin reads schema 3 natively (5.4+).
         *
         * In that case the mirror is always current: the read path depends on
         * it, so the conversion is not optional.
         *
         * @return bool
         */
        public static function host_is_v3(): bool
        {
            return class_exists('SVI_Gallery_Service', false)
                && method_exists('SVI_Gallery_Service', 'is_v3')
                && SVI_Gallery_Service::is_v3();
        }

        /**
         * Whether legacy writes must be mirrored.
         *
         * @return bool
         */
        public static function should_mirror(): bool
        {
            return self::host_is_v3() || SVI_Mirror_Settings::is_enabled();
        }

        /**
         * Whether the background catalogue pass may run.
         *
         * @return bool
         */
        public static function should_backfill(): bool
        {
            return self::host_is_v3() || SVI_Mirror_Settings::backfill_enabled();
        }
    }
}
