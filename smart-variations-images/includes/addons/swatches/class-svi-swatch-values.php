<?php
/**
 * Swatch value reader — reads both swatch term-meta key families.
 *
 * Two families exist in the wild:
 *  - "svi":    svi_swatch_color, svi_swatch_color_2..4, svi_swatch_image_id, svi_swatch_label
 *  - "legacy": color, color_2, image, label
 *
 * The "svi" family wins when it holds a value; the legacy family is used as a
 * fallback so stores configured by either implementation keep their colours and
 * images. Nothing is ever migrated or deleted here — this class only reads.
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Swatch_Values')) {
	/**
	 * Read swatch values for a term, supporting both meta key families.
	 */
	class SVI_Swatch_Values
	{

		/**
		 * Primary family meta keys, in colour order.
		 *
		 * @var string[]
		 */
		const PRIMARY_COLORS = ['svi_swatch_color', 'svi_swatch_color_2', 'svi_swatch_color_3', 'svi_swatch_color_4'];

		/**
		 * Legacy family meta keys, in colour order.
		 *
		 * @var string[]
		 */
		const LEGACY_COLORS = ['color', 'color_2'];

		/**
		 * Primary image meta key.
		 */
		const PRIMARY_IMAGE = 'svi_swatch_image_id';

		/**
		 * Legacy image meta key.
		 */
		const LEGACY_IMAGE = 'image';

		/**
		 * Primary label meta key.
		 */
		const PRIMARY_LABEL = 'svi_swatch_label';

		/**
		 * Legacy label meta key.
		 */
		const LEGACY_LABEL = 'label';

		/**
		 * Per-request cache of resolved values.
		 *
		 * @var array<int, array{colors:string[],image:int,label:string,source:string}>
		 */
		private static $cache = [];

		/**
		 * Prime the term meta cache for a set of terms.
		 *
		 * Keeps list rendering free of one query per term.
		 *
		 * @param array<int> $term_ids Term IDs.
		 * @return void
		 */
		public static function prime(array $term_ids): void
		{
			$term_ids = array_values(array_unique(array_filter(array_map('absint', $term_ids))));

			if (empty($term_ids)) {
				return;
			}

			update_meta_cache('term', $term_ids);
		}

		/**
		 * Resolve every swatch value for a term.
		 *
		 * @param int $term_id Term ID.
		 * @return array{colors:string[],image:int,label:string,source:string}
		 */
		public static function get(int $term_id): array
		{
			$term_id = absint($term_id);

			if (isset(self::$cache[$term_id])) {
				return self::$cache[$term_id];
			}

			$colors = self::get_colors($term_id);
			$image  = self::get_image_id($term_id);
			$label  = self::get_label($term_id);

			$source = 'none';
			if (!empty($colors)) {
				$source = 'color';
			} elseif ($image > 0) {
				$source = 'image';
			} elseif ('' !== $label) {
				$source = 'label';
			}

			self::$cache[$term_id] = [
				'colors' => $colors,
				'image'  => $image,
				'label'  => $label,
				'source' => $source,
			];

			return self::$cache[$term_id];
		}

		/**
		 * Resolve the colour list for a term.
		 *
		 * @param int $term_id Term ID.
		 * @return string[] Valid hex colours, in order.
		 */
		public static function get_colors(int $term_id): array
		{
			$term_id = absint($term_id);

			// Resolve slot by slot: a store may hold the first colour in the
			// legacy key and the second one in the newer key (or the other way
			// around), and both halves must still line up.
			$slots = [
				[self::PRIMARY_COLORS[0], self::LEGACY_COLORS[0]],
				[self::PRIMARY_COLORS[1], self::LEGACY_COLORS[1]],
				[self::PRIMARY_COLORS[2]],
				[self::PRIMARY_COLORS[3]],
			];

			$colors = [];

			foreach ($slots as $keys) {
				foreach ($keys as $key) {
					$value = get_term_meta($term_id, $key, true);

					if (is_string($value) && '' !== trim($value)) {
						$clean = self::sanitize_colors([$value]);

						if (!empty($clean)) {
							$colors[] = $clean[0];
						}

						break;
					}
				}
			}

			return array_values(array_unique($colors));
		}

		/**
		 * Resolve the swatch image attachment ID for a term.
		 *
		 * @param int $term_id Term ID.
		 * @return int Attachment ID, or 0.
		 */
		public static function get_image_id(int $term_id): int
		{
			$term_id = absint($term_id);

			$image = absint(get_term_meta($term_id, self::PRIMARY_IMAGE, true));

			if (!$image) {
				$image = absint(get_term_meta($term_id, self::LEGACY_IMAGE, true));
			}

			return $image > 0 ? $image : 0;
		}

		/**
		 * Resolve the swatch label for a term.
		 *
		 * @param int $term_id Term ID.
		 * @return string Label, or an empty string when none is stored.
		 */
		public static function get_label(int $term_id): string
		{
			$term_id = absint($term_id);

			$label = get_term_meta($term_id, self::PRIMARY_LABEL, true);

			if (!is_string($label) || '' === trim($label)) {
				$label = get_term_meta($term_id, self::LEGACY_LABEL, true);
			}

			return is_string($label) ? trim($label) : '';
		}

		/**
		 * Keep only valid hex colours, de-duplicated and ordered.
		 *
		 * Anything that is not a plain hex value is dropped so an invalid stored
		 * value can never break the inline CSS.
		 *
		 * @param array $colors Raw colour values.
		 * @return string[]
		 */
		public static function sanitize_colors(array $colors): array
		{
			$valid = [];

			foreach ($colors as $color) {
				if (!is_string($color)) {
					continue;
				}

				$color = trim($color);

				if ('' === $color) {
					continue;
				}

				// Accept 3/6 digit hex with or without the leading hash.
				if ('#' !== $color[0]) {
					$color = '#' . $color;
				}

				$sanitized = sanitize_hex_color($color);

				if (!$sanitized) {
					continue;
				}

				if (!in_array($sanitized, $valid, true)) {
					$valid[] = $sanitized;
				}
			}

			return $valid;
		}

		/**
		 * Build the background CSS declaration for a colour swatch.
		 *
		 * One colour is solid. Two colours split the swatch in half (the first
		 * colour first, left to right). Three or four colours become equal
		 * vertical stripes, preserving data stored by earlier versions.
		 *
		 * @param array $colors Sanitized colours.
		 * @return string CSS declaration, or an empty string.
		 */
		public static function build_color_css(array $colors): string
		{
			$colors = self::sanitize_colors($colors);
			$count  = count($colors);

			if (0 === $count) {
				return '';
			}

			if (1 === $count) {
				return 'background-color:' . $colors[0] . ';';
			}

			$stops = [];
			$step  = 100 / $count;

			for ($i = 0; $i < $count; $i++) {
				$start = $step * $i;
				$end   = $step * ($i + 1);
				$stops[] = sprintf('%1$s %2$s%%, %1$s %3$s%%', $colors[$i], self::format_percent($start), self::format_percent($end));
			}

			return 'background-image:linear-gradient(90deg, ' . implode(', ', $stops) . ');';
		}

		/**
		 * Format a percentage without trailing zeros.
		 *
		 * @param float $value Percentage value.
		 * @return string
		 */
		private static function format_percent(float $value): string
		{
			$formatted = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

			return '' === $formatted ? '0' : $formatted;
		}
	}
}
