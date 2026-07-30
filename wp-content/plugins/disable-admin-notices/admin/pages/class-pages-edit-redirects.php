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
class WDAN_Block_Ad_Redirects extends WDN_Page {

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $id = "wdanp-edit-redirects";

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
	public $page_menu_dashicon = 'dashicons dashicons-undo';

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
		$this->menu_title                  = __( 'Block ad redirects', 'disable-admin-notices' );
		$this->page_menu_short_description = __( 'Break advertising redirects', 'disable-admin-notices' );

		parent::__construct( $plugin );

		$this->plugin = $plugin;

		add_filter( 'wp_redirect', function ( $location ) {
			$redirects = $this->getPopulateOption( 'blocked_redirects', [] );
			if ( in_array( $location, $redirects ) ) {
				return null;
			}

			return $location;
		} );
	}

	public function unblockRedirectAction() {
		$redirect_ID = $this->request()->get( 'redirect_id', null, 'sanitize_key' );
		check_admin_referer( 'unblock_redirect_' . $redirect_ID );

		$redirects = $this->getPopulateOption( 'blocked_redirects', [] );

		if ( isset( $redirects[ $redirect_ID ] ) ) {
			unset( $redirects[ $redirect_ID ] );
			$this->updatePopulateOption( 'blocked_redirects', $redirects );
		}

		$this->redirectToAction( 'index' );
	}

	/**
	 * Requests assets (js and css) for the page.
	 *
	 * @param Wbcr_Factory480_ScriptList $scripts
	 * @param Wbcr_Factory480_StyleList $styles
	 *
	 * @return void
	 * @see Wbcr_FactoryPages480_AdminPage
	 *
	 */
	public function assets( $scripts, $styles ) {
		parent::assets( $scripts, $styles );

		$this->styles->add( WDN_PLUGIN_URL . '/admin/assets/css/settings.css' );
	}

	public function showPageContent() {
		$redirects = $this->getPopulateOption( 'blocked_redirects', [] );

		if ( isset( $_POST['wdan_add_block'] ) ) {
			check_admin_referer( 'wdan_add_block_redirect' );
			$url = $this->request()->post( 'wdan_redirect_url', null, 'sanitize_url' );

			if ( ! empty( $url ) ) {
				if ( ! in_array( $url, $redirects ) ) {
					$redirects[ md5( $url ) ] = $url;
					$this->updatePopulateOption( 'blocked_redirects', $redirects );
				}
	
				$this->redirectToAction( 'index' );
			}
		}
		?>
		<div style="padding:15px;">
			<h4><?php esc_html_e( 'Block ad redirects', 'disable-admin-notices' ); ?></h4>
			<form method="post">
				<?php wp_nonce_field( 'wdan_add_block_redirect' ); ?>
				<label for="wdan-redirect-url"><?php esc_html_e( 'Enter url for block', 'disable-admin-notices'); ?></label><br>
				<input id="wdan-redirect-url" style="width:400px;" type="text" name="wdan_redirect_url">
				<input type="submit" name="wdan_add_block" class="button" value="<?php esc_attr_e( 'Add block', 'disable-admin-notices' ); ?>">
			</form>
			<p class="wdan-redirect-url-description"><?php esc_html_e( 'Some plugins open a page automatically after installation or update to show announcements, promotions, or other information. Enter the URLs of those pages here to prevent them from opening automatically.', 'disable-admin-notices' ); ?></p>
			<br>
			<table class="wp-list-table widefat fixed striped">
				<tr>
					<th><?php esc_html( 'Url', 'disable-admin-notices' ); ?></th>
					<th style="width:200px;"><?php esc_html_e( 'Action', 'disable-admin-notices' ); ?></th>
				</tr>
				<?php foreach ( $redirects as $ID => $redirect ): ?>
					<tr>
						<td>
							<?php echo esc_html( $redirect ); ?>
						</td>
						<td>
							<a style="color:#428bca;" href="<?php echo wp_nonce_url( $this->getActionUrl( 'unblock-redirect', [ 'redirect_id' => $ID ] ), 'unblock_redirect_' . $ID ); ?>"><?php esc_html_e( 'Unblock', 'disable-admin-notices' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>
		<?php
	}

}
