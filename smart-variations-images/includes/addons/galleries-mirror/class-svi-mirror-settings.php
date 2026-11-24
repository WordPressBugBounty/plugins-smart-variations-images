<?php
/**
 * Mirror mode switch.
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SVI_Mirror_Settings')) {
    /**
     * Reads and writes the single option that owns the mirror.
     *
     * Deliberately not a settings field: the mirror must stay invisible to
     * merchants. `off` is the shipped default, so 5.3.10 behaves exactly like
     * 5.3.9 until the mode is raised.
     */
    final class SVI_Mirror_Settings
    {
        /** Option key. */
        const OPTION = 'svi_galleries_mirror';

        /** No mirror writes at all. */
        const OFF = 'off';

        /** Mirror on every legacy write. */
        const WRITE = 'write';

        /** Mirror on write plus a background pass over the catalogue. */
        const WRITE_BACKFILL = 'write+backfill';

        /**
         * Allowed modes.
         *
         * @return string[]
         */
        public static function modes(): array
        {
            return array(self::OFF, self::WRITE, self::WRITE_BACKFILL);
        }

        /**
         * Current mode after the `svi_galleries_mirror_enabled` filter.
         *
         * @return string
         */
        public static function mode(): string
        {
            $stored = get_option(self::OPTION, self::OFF);
            $stored = is_string($stored) ? strtolower(trim($stored)) : self::OFF;

            if (!in_array($stored, self::modes(), true)) {
                $stored = self::OFF;
            }

            $filtered = apply_filters('svi_galleries_mirror_enabled', $stored);
            $filtered = is_string($filtered) ? strtolower(trim($filtered)) : self::OFF;

            return in_array($filtered, self::modes(), true) ? $filtered : self::OFF;
        }

        /**
         * Whether any mirror write should happen.
         *
         * @return bool
         */
        public static function is_enabled(): bool
        {
            return self::OFF !== self::mode();
        }

        /**
         * Whether the background catalogue pass should run.
         *
         * @return bool
         */
        public static function backfill_enabled(): bool
        {
            return self::WRITE_BACKFILL === self::mode();
        }

        /**
         * Persist a mode.
         *
         * @param string $mode One of self::modes().
         * @return bool
         */
        public static function set_mode(string $mode): bool
        {
            $mode = strtolower(trim($mode));

            if (!in_array($mode, self::modes(), true)) {
                return false;
            }

            return (bool) update_option(self::OPTION, $mode, false);
        }
    }
}
