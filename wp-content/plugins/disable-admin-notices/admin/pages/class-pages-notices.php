<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Страница со списком скрытых нотисов.
 *
 * @author        Artem Prihodko <webtemyk@yandex.ru>
 * @copyright (c) 2020 Webraftic Ltd
 * @version       1.0
 */
class WDAN_Notices extends WDN_Page {

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public $id = "wdan-notices";

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
	public $page_menu_dashicon = 'dashicons-hidden';

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
		$this->menu_title                  = __( 'Hidden notices', 'disable-admin-notices' );
		$this->page_menu_short_description = __( 'Manage hidden notices', 'disable-admin-notices' );

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
	 * @see Wbcr_FactoryPages480_AdminPage
	 *
	 */
	public function assets( $scripts, $styles ) {
		parent::assets( $scripts, $styles );

		$this->styles->add( WDN_PLUGIN_URL . '/admin/assets/css/settings.css' );
		$this->scripts->add( WDN_PLUGIN_URL . '/admin/assets/js/settings.js' );
	}

	public function showPageContent() {
		$notifications_user = get_user_meta( get_current_user_id(), WDN_Plugin::app()->getOptionName( 'hidden_notices' ), true );
		if ( ! is_array( $notifications_user ) ) {
			$notifications_user = [];
		}

		$notifications_all = WDN_Plugin::app()->getPopulateOption( 'hidden_notices', [] );

		if ( count( $notifications_user ) ) {
			?>
            <div class="wbcr-factory-page-group-header">
                <strong><?php echo esc_html__( 'Hidden for you', 'disable-admin-notices' ); ?></strong>
                <p>
					<?php echo esc_html__( 'Notices that are hidden only for you', 'disable-admin-notices' ); ?>
                </p>
            </div>
            <div class="wdan-hidden-list">
				<?php $this->notice_list_table( $notifications_user ); ?>
            </div>
			<?php
		}
		if ( count( $notifications_all ) ) {
			?>
            <div class="wbcr-factory-page-group-header">
                <strong><?php echo esc_html__( 'Hidden for all', 'disable-admin-notices' ); ?></strong>
                <p>
					<?php echo esc_html__( 'Notices that are hidden for all users of the site', 'disable-admin-notices' ); ?>
                </p>
            </div>
            <div class="wdan-hidden-list">
				<?php $this->notice_list_table( $notifications_all ); ?>
            </div>
			<?php
		}
	}

	/**
	 * @param $notifications
	 */
	public function notice_list_table( $notifications ) {
		?>
        <table class="wdan-hidden-list-table">
            <tbody>
			<?php
			foreach ( $notifications as $notice_id => $message ) {
				$button = '<div class="wdan-hidden-list-notice-action">
					<a href="#"
					data-nonce="' . wp_create_nonce( $this->plugin->getPluginName() . '_ajax_restore_notice_nonce' ) . '"
					data-notice-id="' . esc_attr( $notice_id ) . '" 
					class="button wdan-page-restore-notice-link">' .
				          __( 'Restore', 'disable-admin-notices' ) .
					'</a>
					<div class="wdan-page-restore-notice-link-loader" style="display: none;">&nbsp;</div>
					</div>';
				?>
                <tr>
                    <td>
                        <div class="wdan-hidden-list-notice">
                            <div class="wdan-notice-p"><?php echo $message; ?></div>
                        </div>
                    </td>
                    <td>
						<?= $button; ?>
                    </td>
                </tr>
				<?php
			}
			?>
            </tbody>
        </table>
		<?php
	}
}
