<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPSF_SVI_Subtabs' ) ) {
	/**
	 * Nested tabs (sub-tabs) for the settings screen.
	 *
	 * A tab can hold several panes without inventing a second UI: the renderer
	 * outputs a nav + panes block, and a self-contained initialiser switches
	 * them. Works vertically (default) or horizontally, and collapses to a
	 * scrollable strip on narrow screens through main.css.
	 *
	 * Usage:
	 *
	 *     WPSF_SVI_Subtabs::render(
	 *         array(
	 *             'general' => array(
	 *                 'label'  => __( 'General' ),
	 *                 'render' => array( __CLASS__, 'render_general' ),
	 *             ),
	 *         ),
	 *         array( 'layout' => 'vertical', 'id' => 'my-subtabs' )
	 *     );
	 */
	class WPSF_SVI_Subtabs {

		/**
		 * Render a sub-tab group.
		 *
		 * @param array $tabs {
		 *     @type string   $label  Tab label.
		 *     @type callable $render Callback that prints the pane.
		 * }
		 * @param array $args {
		 *     @type string $layout vertical|horizontal.
		 *     @type string $id     DOM id of the group.
		 * }
		 * @return void
		 */
		public static function render( array $tabs, array $args = array() ) {
			$layout = isset( $args['layout'] ) && 'horizontal' === $args['layout'] ? 'horizontal' : 'vertical';
			$group  = isset( $args['id'] ) && '' !== (string) $args['id'] ? sanitize_html_class( (string) $args['id'] ) : 'wpsfsvi-subtabs';

			$valid = array();
			$index = 0;

			foreach ( $tabs as $key => $tab ) {
				if ( ! is_array( $tab ) || empty( $tab['label'] ) || empty( $tab['render'] ) || ! is_callable( $tab['render'] ) ) {
					continue;
				}

				$key = '' !== (string) $key ? (string) $key : 'tab-' . $index;
				$key = sanitize_html_class( $key );

				$valid[ $key ] = $tab;
				++$index;
			}

			if ( empty( $valid ) ) {
				echo '<p>' . esc_html__( 'Nothing to configure yet.', 'smart-variations-images' ) . '</p>';

				return;
			}

			$first = (string) array_key_first( $valid );
			?>
			<div id="<?php echo esc_attr( $group ); ?>"
				class="wpsfsvi-subtabs wpsfsvi-subtabs--<?php echo esc_attr( $layout ); ?>"
				data-wpsfsvi-subtabs>
				<div class="wpsfsvi-subtabs__nav" role="tablist" aria-orientation="<?php echo esc_attr( $layout ); ?>">
					<?php foreach ( $valid as $key => $tab ) : ?>
						<button type="button"
							role="tab"
							id="<?php echo esc_attr( $group . '-' . $key . '-tab' ); ?>"
							class="wpsfsvi-subtabs__tab<?php echo $key === $first ? ' is-active' : ''; ?>"
							aria-controls="<?php echo esc_attr( $group . '-' . $key . '-pane' ); ?>"
							aria-selected="<?php echo $key === $first ? 'true' : 'false'; ?>"
							data-wpsfsvi-subtab="<?php echo esc_attr( $key ); ?>">
							<?php echo esc_html( $tab['label'] ); ?>
						</button>
					<?php endforeach; ?>
				</div>
				<div class="wpsfsvi-subtabs__panes">
					<?php foreach ( $valid as $key => $tab ) : ?>
						<div class="wpsfsvi-subtabs__pane"
							id="<?php echo esc_attr( $group . '-' . $key . '-pane' ); ?>"
							role="tabpanel"
							aria-labelledby="<?php echo esc_attr( $group . '-' . $key . '-tab' ); ?>"
							data-wpsfsvi-subtab="<?php echo esc_attr( $key ); ?>"
							<?php echo $key === $first ? '' : 'hidden'; ?>>
							<?php call_user_func( $tab['render'] ); ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<script>
			( function () {
				function init() {
					var groups = document.querySelectorAll( '[data-wpsfsvi-subtabs]:not([data-ready])' );

					Array.prototype.forEach.call( groups, function ( root ) {
						root.setAttribute( 'data-ready', '1' );

						var nav = root.querySelector( '.wpsfsvi-subtabs__nav' );

						if ( ! nav ) {
							return;
						}

						nav.addEventListener( 'click', function ( event ) {
							var button = event.target.closest( '.wpsfsvi-subtabs__tab' );

							if ( ! button ) {
								return;
							}

							var id = button.getAttribute( 'data-wpsfsvi-subtab' );

							Array.prototype.forEach.call( root.querySelectorAll( '.wpsfsvi-subtabs__tab' ), function ( tab ) {
								var active = tab === button;
								tab.classList.toggle( 'is-active', active );
								tab.setAttribute( 'aria-selected', active ? 'true' : 'false' );
							} );

							Array.prototype.forEach.call( root.querySelectorAll( '.wpsfsvi-subtabs__pane' ), function ( pane ) {
								pane.hidden = pane.getAttribute( 'data-wpsfsvi-subtab' ) !== id;
							} );
						} );
					} );
				}

				if ( 'loading' === document.readyState ) {
					document.addEventListener( 'DOMContentLoaded', init );
				} else {
					init();
				}

				window.wpsfsviInitSubtabs = init;
			}() );
			</script>
			<?php
		}
	}
}
