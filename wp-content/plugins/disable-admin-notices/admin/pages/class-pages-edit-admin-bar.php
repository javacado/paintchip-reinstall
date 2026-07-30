<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Страница общих настроек для этого плагина.
 *
 * Не поддерживает режим работы с мультисаймами.
 *
 * @author        Alexander Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @copyright (c) 2019 Webraftic Ltd
 * @version       1.0
 */
class WDAN_Edit_Admin_Bar extends WDN_Page {

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $id = "wdanp-edit-admin-bar";

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $type = "page";

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $page_menu_dashicon = 'dashicons-menu';

	/**
	 * {@inheritDoc}
	 *
	 * @since   2.0.5 - добавлен
	 * @var bool
	 */
	public $show_right_sidebar_in_options = false;


	/**
	 * @param WDN_Plugin $plugin
	 */
	public function __construct( $plugin ) {
		$this->menu_title                  = __( 'Hide admin bar items', 'disable-admin-notices' );
		$this->page_menu_short_description = __( 'Hide selected admin bar menu items', 'disable-admin-notices' );

		parent::__construct( $plugin );

		$this->plugin = $plugin;
	}

	/**
	 * Requests assets (js and css) for the page.
	 *
	 * @param Wbcr_Factory480_ScriptList $scripts
	 * @param Wbcr_Factory480_StyleList $styles
	 *
	 * @return void
	 * @see Wbcr_FactoryPages000_AdminPage
	 *
	 */
	public function assets( $scripts, $styles ) {
		parent::assets( $scripts, $styles );

		$this->styles->add( WDN_PLUGIN_URL . '/admin/assets/css/settings.css' );
		$this->scripts->add( WDN_PLUGIN_URL . '/admin/assets/js/settings.js', [
			'jquery'
		] );
	}

	public function disableAdminbarItemAction() {
		$item_ID = $this->request()->get( 'id', null, 'sanitize_key' );
		check_admin_referer( 'disable_adminbar_item_' . $item_ID );

		$items = $this->plugin->getPopulateOption( 'hidden_adminbar_items', [] );

		if ( ! isset( $items[ $item_ID ] ) ) {
			$items[ $item_ID ] = true;
			$this->plugin->updatePopulateOption( 'hidden_adminbar_items', $items );
		}

		$this->redirectToAction( 'index' );
	}

	public function enableAdminbarItemAction() {
		$item_ID = $this->request()->get( 'id', null, 'sanitize_key' );
		check_admin_referer( 'enable_adminbar_item_' . $item_ID );

		$items = $this->plugin->getPopulateOption( 'hidden_adminbar_items', [] );

		if ( isset( $items[ $item_ID ] ) ) {
			unset( $items[ $item_ID ] );
			$this->plugin->updatePopulateOption( 'hidden_adminbar_items', $items );
		}

		$this->redirectToAction( 'index' );
	}

	public function showPageContent() {
		$all_items    = $this->plugin->getPopulateOption( 'adminbar_items', [] );
		$hidden_items = $this->plugin->getPopulateOption( 'hidden_adminbar_items', [] );

		?>

		<div style="padding:15px;">
			<h4><?php esc_html_e( 'Disable admin bar items', 'disable-admin-notices' ); ?></h4>
			<table class="wp-list-table widefat fixed striped">
				<tr>
					<th><strong><?php esc_html_e( 'Menu title', 'disable-admin-notices' ); ?></strong></th>
					<th style="width:150px;"><strong><?php esc_html_e( 'Action', 'disable-admin-notices' ); ?></strong></th>
				</tr>
				<?php
				foreach ( (array) $all_items as $ID => $title ):
					$is_item_hidden = isset( $hidden_items[ $ID ] ) && true === $hidden_items[ $ID ];
					?>

					<tr>
						<td><?php echo esc_html( $title ); ?></td>
						<td>
							<div data-nonce="<?php echo esc_attr( wp_create_nonce( 'enable_adminbar_item_' . $ID ) ) ?>" data-menu-id="<?php echo esc_attr( $ID ); ?>" class="wdan-checkbox adminbar-items factory-checkbox factory-from-control-checkbox factory-buttons-way btn-group">
								<button type="button" class="btn btn-default btn-small btn-sm factory-on<?php echo esc_attr( ! $is_item_hidden ? ' active' : '' ); ?>">
									<?php esc_html_e( 'On ', 'disable-admin-notices' ); ?>
								</button>
								<button type="button" class="btn btn-default btn-small btn-sm factory-off<?php echo esc_attr( ! $is_item_hidden ? ' active' : '' ); ?>" data-value="0">
									<?php esc_html_e( 'Off', 'disable-admin-notices' ); ?>
								</button>
								<input type="checkbox" style="display: none" class="factory-result" value="<?php echo esc_attr( ! $is_item_hidden ? '1' : '0' ); ?>" checked="<?php echo esc_attr( ! $is_item_hidden ? 'checked' : '' ); ?>">
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>

		<?php
	}

}
