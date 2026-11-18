<?php
/**
 * Single write path for every swatch import.
 *
 * All adapters go through this class so values are sanitised the same way and
 * every change can be undone. Writing is non-destructive: an existing SVI value
 * is only replaced when the caller asked for it.
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Swatch_Import_Writer')) {
	/**
	 * Writes import items and records the previous value of everything it touched.
	 */
	class SVI_Swatch_Import_Writer
	{
		/**
		 * Replace values that already exist.
		 *
		 * @var bool
		 */
		private $overwrite;

		/**
		 * Report only, do not write.
		 *
		 * @var bool
		 */
		private $dry_run;

		/**
		 * Constructor.
		 *
		 * @param bool $overwrite Replace existing SVI values.
		 * @param bool $dry_run   Compute the outcome without writing anything.
		 */
		public function __construct(bool $overwrite = false, bool $dry_run = false)
		{
			$this->overwrite = $overwrite;
			$this->dry_run = $dry_run;
		}

		/**
		 * Write one item.
		 *
		 * @param array $item Import item.
		 * @param array $log  Run log, appended by reference.
		 * @return array{status:string,message:string,changed:int}
		 */
		public function write(array $item, array &$log): array
		{
			if (!empty($item['unsupported'])) {
				return [
					'status' => 'skipped',
					'message' => isset($item['reason']) ? (string) $item['reason'] : __('No SVI equivalent.', 'smart-variations-images'),
					'changed' => 0,
				];
			}

			$payload = isset($item['payload']) && is_array($item['payload']) ? $item['payload'] : [];
			$kind = isset($item['kind']) ? (string) $item['kind'] : '';

			switch ($kind) {
				case 'attribute':
					return $this->write_attribute($payload, $log);
				case 'term':
					return $this->write_term($payload, $log);
				case 'override':
					return $this->write_override($payload, $log);
				case 'setting':
					return $this->write_setting($payload, $log);
			}

			return [
				'status' => 'skipped',
				'message' => __('Unknown import item.', 'smart-variations-images'),
				'changed' => 0,
			];
		}

		/**
		 * Store the swatch type of one global attribute.
		 *
		 * @param array $payload Item payload.
		 * @param array $log     Run log.
		 * @return array{status:string,message:string,changed:int}
		 */
		private function write_attribute(array $payload, array &$log): array
		{
			$attribute_id = absint($payload['attribute_id'] ?? 0);
			$type = sanitize_key((string) ($payload['type'] ?? ''));

			if (!$attribute_id || !in_array($type, ['color', 'image', 'text', 'radio'], true)) {
				return [
					'status' => 'skipped',
					'message' => __('No swatch type to import.', 'smart-variations-images'),
					'changed' => 0,
				];
			}

			$option = 'svi_attribute_swatch_type_' . $attribute_id;
			$previous = get_option($option, null);

			if (null !== $previous && (string) $previous === $type) {
				return [
					'status' => 'unchanged',
					'message' => __('Already set.', 'smart-variations-images'),
					'changed' => 0,
				];
			}

			if (null !== $previous && !$this->overwrite) {
				return [
					'status' => 'kept',
					'message' => __('SVI already has a swatch type; kept it.', 'smart-variations-images'),
					'changed' => 0,
				];
			}

			if (!$this->dry_run) {
				$log[] = [
					'store' => 'option',
					'option' => $option,
					'key' => null,
					'previous' => $previous,
				];

				update_option($option, $type);
			}

			return [
				'status' => null === $previous ? 'created' : 'updated',
				'message' => null === $previous
					/* translators: %s: swatch type name. */
					? sprintf(__('Set swatch type to %s.', 'smart-variations-images'), $type)
					/* translators: %s: swatch type name. */
					: sprintf(__('Replaced the SVI swatch type with %s.', 'smart-variations-images'), $type),
				'changed' => 1,
			];
		}

		/**
		 * Store colours, image and label for one attribute term.
		 *
		 * @param array $payload Item payload.
		 * @param array $log     Run log.
		 * @return array{status:string,message:string,changed:int}
		 */
		private function write_term(array $payload, array &$log): array
		{
			$term_id = absint($payload['term_id'] ?? 0);

			if (!$term_id || !get_term($term_id)) {
				return [
					'status' => 'skipped',
					'message' => __('Term not found.', 'smart-variations-images'),
					'changed' => 0,
				];
			}

			$colors = SVI_Swatch_Values::sanitize_colors((array) ($payload['colors'] ?? []));
			$image = absint($payload['image'] ?? 0);
			$label = isset($payload['label']) ? sanitize_text_field((string) $payload['label']) : '';

			$targets = [];

			foreach ($colors as $index => $color) {
				$targets[0 === $index ? 'svi_swatch_color' : 'svi_swatch_color_' . ($index + 1)] = $color;
			}

			if ($image > 0) {
				$targets['svi_swatch_image_id'] = $image;
			}

			if ('' !== $label) {
				$targets['svi_swatch_label'] = $label;
			}

			if (empty($targets)) {
				return [
					'status' => 'skipped',
					'message' => __('No swatch values to import.', 'smart-variations-images'),
					'changed' => 0,
				];
			}

			return $this->write_term_meta($term_id, $targets, $log);
		}

		/**
		 * Write a set of term meta keys, honouring the conflict policy.
		 *
		 * @param int   $term_id Term ID.
		 * @param array $targets Key => value pairs.
		 * @param array $log     Run log.
		 * @return array{status:string,message:string,changed:int}
		 */
		private function write_term_meta(int $term_id, array $targets, array &$log): array
		{
			$changed = 0;
			$kept = 0;
			$updated = false;

			foreach ($targets as $key => $value) {
				$exists = metadata_exists('term', $term_id, $key);
				$previous = $exists ? get_term_meta($term_id, $key, true) : null;

				if ($exists && (string) $previous === (string) $value) {
					continue;
				}

				if ($exists && !$this->overwrite) {
					$kept++;
					continue;
				}

				if (!$this->dry_run) {
					$log[] = [
						'store' => 'term_meta',
						'object_id' => $term_id,
						'key' => $key,
						'previous' => $previous,
					];

					update_term_meta($term_id, $key, $value);
				}

				$updated = $exists;
				$changed++;
			}

			if ($changed > 0) {
				return [
					'status' => $updated ? 'updated' : 'created',
					/* translators: %d: number of values written. */
					'message' => sprintf(_n('%d value written.', '%d values written.', $changed, 'smart-variations-images'), $changed),
					'changed' => $changed,
				];
			}

			if ($kept > 0) {
				return [
					'status' => 'kept',
					'message' => __('SVI already has values here; kept them.', 'smart-variations-images'),
					'changed' => 0,
				];
			}

			return [
				'status' => 'unchanged',
				'message' => __('Already identical.', 'smart-variations-images'),
				'changed' => 0,
			];
		}

		/**
		 * Merge one attribute's per-product override into the product meta.
		 *
		 * @param array $payload Item payload.
		 * @param array $log     Run log.
		 * @return array{status:string,message:string,changed:int}
		 */
		private function write_override(array $payload, array &$log): array
		{
			$product_id = absint($payload['product_id'] ?? 0);
			$attribute = isset($payload['attribute']) ? strtolower(sanitize_text_field((string) $payload['attribute'])) : '';

			if (!$product_id || !get_post($product_id) || '' === $attribute) {
				return [
					'status' => 'skipped',
					'message' => __('Product or attribute not found.', 'smart-variations-images'),
					'changed' => 0,
				];
			}

			$type = sanitize_key((string) ($payload['type'] ?? ''));

			if (!in_array($type, ['color', 'image', 'text', 'radio'], true)) {
				$type = '';
			}

			$terms = [];

			foreach ((array) ($payload['terms'] ?? []) as $slug => $values) {
				$slug = sanitize_title((string) $slug);

				if ('' === $slug || !is_array($values)) {
					continue;
				}

				$colors = SVI_Swatch_Values::sanitize_colors(array_filter([
					(string) ($values['color'] ?? ''),
					(string) ($values['color2'] ?? ''),
					(string) ($values['color3'] ?? ''),
					(string) ($values['color4'] ?? ''),
				]));

				$terms[$slug] = [
					'color' => $colors[0] ?? '',
					'color2' => $colors[1] ?? '',
					'color3' => $colors[2] ?? '',
					'color4' => $colors[3] ?? '',
					'image' => absint($values['image'] ?? 0),
					'label' => sanitize_text_field((string) ($values['label'] ?? '')),
				];
			}

			if ('' === $type && empty($terms)) {
				return [
					'status' => 'skipped',
					'message' => __('Empty override.', 'smart-variations-images'),
					'changed' => 0,
				];
			}

			$current = get_post_meta($product_id, '_svi_swatch_overrides', true);
			$current = is_array($current) ? $current : [];
			$existing = isset($current[$attribute]) && is_array($current[$attribute]) ? $current[$attribute] : null;

			$merged = [
				'type' => '' !== $type ? $type : (string) ($existing['type'] ?? ''),
				'terms' => array_merge((array) ($existing['terms'] ?? []), $terms),
			];

			if (null !== $existing && $existing == $merged) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- Array comparison by value.
				return [
					'status' => 'unchanged',
					'message' => __('Override already stored.', 'smart-variations-images'),
					'changed' => 0,
				];
			}

			if (null !== $existing && !$this->overwrite) {
				return [
					'status' => 'kept',
					'message' => __('Product already has an SVI override; kept it.', 'smart-variations-images'),
					'changed' => 0,
				];
			}

			$current[$attribute] = $merged;

			if (!$this->dry_run) {
				$log[] = [
					'store' => 'post_meta',
					'object_id' => $product_id,
					'key' => '_svi_swatch_overrides',
					'previous' => get_post_meta($product_id, '_svi_swatch_overrides', true),
				];

				update_post_meta($product_id, '_svi_swatch_overrides', $current);
			}

			return [
				'status' => null === $existing ? 'created' : 'updated',
				'message' => __('Attribute override stored.', 'smart-variations-images'),
				'changed' => 1,
			];
		}

		/**
		 * Store one global SVI setting.
		 *
		 * @param array $payload Item payload.
		 * @param array $log     Run log.
		 * @return array{status:string,message:string,changed:int}
		 */
		private function write_setting(array $payload, array &$log): array
		{
			$key = isset($payload['key']) ? (string) $payload['key'] : '';

			if ('' === $key) {
				return [
					'status' => 'skipped',
					'message' => __('Unknown setting.', 'smart-variations-images'),
					'changed' => 0,
				];
			}

			$value = $payload['value'] ?? '';

			$stored = get_option('woosvi_options_settings', []);
			$stored = is_object($stored) ? get_object_vars($stored) : (array) $stored;
			$exists = array_key_exists($key, $stored);
			$previous = $exists ? $stored[$key] : null;

			if ($exists && (string) $previous === (string) $value) {
				return [
					'status' => 'unchanged',
					'message' => __('Already set.', 'smart-variations-images'),
					'changed' => 0,
				];
			}

			if ($exists && !$this->overwrite) {
				return [
					'status' => 'kept',
					'message' => __('SVI already has this setting; kept it.', 'smart-variations-images'),
					'changed' => 0,
				];
			}

			if (!$this->dry_run) {
				$log[] = [
					'store' => 'option_array',
					'option' => 'woosvi_options_settings',
					'key' => $key,
					'previous' => $previous,
				];

				$stored[$key] = $value;
				update_option('woosvi_options_settings', $stored);
			}

			return [
				'status' => $exists ? 'updated' : 'created',
				'message' => __('Setting stored.', 'smart-variations-images'),
				'changed' => 1,
			];
		}

		/**
		 * Restore the value recorded before an import wrote it.
		 *
		 * @param array $entry Log entry.
		 * @return bool
		 */
		public static function restore(array $entry): bool
		{
			$store = isset($entry['store']) ? (string) $entry['store'] : '';
			$previous = $entry['previous'] ?? null;

			switch ($store) {
				case 'term_meta':
					$term_id = absint($entry['object_id'] ?? 0);
					$key = (string) ($entry['key'] ?? '');

					if (!$term_id || '' === $key) {
						return false;
					}

					if (null === $previous) {
						delete_term_meta($term_id, $key);
					} else {
						update_term_meta($term_id, $key, $previous);
					}

					return true;

				case 'post_meta':
					$post_id = absint($entry['object_id'] ?? 0);
					$key = (string) ($entry['key'] ?? '');

					if (!$post_id || '' === $key) {
						return false;
					}

					if (null === $previous) {
						delete_post_meta($post_id, $key);
					} else {
						update_post_meta($post_id, $key, $previous);
					}

					return true;

				case 'option':
					$option = (string) ($entry['option'] ?? '');

					if ('' === $option) {
						return false;
					}

					if (null === $previous) {
						delete_option($option);
					} else {
						update_option($option, $previous);
					}

					return true;

				case 'option_array':
					$option = (string) ($entry['option'] ?? '');
					$key = (string) ($entry['key'] ?? '');

					if ('' === $option || '' === $key) {
						return false;
					}

					$stored = get_option($option, []);
					$stored = is_object($stored) ? get_object_vars($stored) : (array) $stored;

					if (null === $previous) {
						unset($stored[$key]);
					} else {
						$stored[$key] = $previous;
					}

					update_option($option, $stored);

					return true;
			}

			return false;
		}
	}
}
