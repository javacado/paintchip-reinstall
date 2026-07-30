<?php
/**
 * Регистрируем поля Html формы в Clearfy на странице "Подолнительно". Если этот плагин загружен, как отдельный плагин
 * то поля будет зарегистрированы для страницы общих настроек этого плагина.
 *
 * Github: https://github.com/alexkovalevv
 *
 * @author        Alexander Kovalev <alex.kovalevv@gmail.com>
 * @copyright (c) 2018 Webraftic Ltd
 * @version       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Регистрируем поля Html формы с настройками плагина.
 *
 * Эта функция используется для общей страницы настроек текущего плагина,
 * а также для раширения настроек в плагине Clearfy.
 *
 * @return array Возвращает группу зарегистрируемых опций
 * @since  1.0
 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
 */
function wbcr_dan_get_plugin_options() {
	$options = [];

	$options[] = [
		'type' => 'html',
		'html' => '<div class="wbcr-factory-page-group-header">' . '<strong>' . __( 'Admin Notifications and Update Notices', 'disable-admin-notices' ) . '</strong>' . '<p>' . __( 'Manage how plugin, theme, and WordPress update notices are displayed in the admin area to reduce distractions and keep your workspace organized.', 'disable-admin-notices' ) . '</p>' . '</div>'
	];

	$hide_admin_notices_data = [
		[
			'not_hide',
			__( "Show all", 'disable-admin-notices' ),
			__( 'Displays all admin notices and does not show the “Hide notice permanently” link.', 'disable-admin-notices' )
		],
		[
			'all',
			__( 'Hide all', 'disable-admin-notices' ),
			__( 'Hides all admin notices across the admin area.', 'disable-admin-notices' )
		],
		[
			'only_selected',
			__( 'Hide selected', 'disable-admin-notices' ),
			__( 'Displays a “Hide notice permanently” link on each notice, allowing you to hide individual notices.', 'disable-admin-notices' )
		],
		[
			'compact_panel',
			__( 'Show compact panel', 'disable-admin-notices' ),
			__( 'Groups all admin notices into a single compact panel with counters. Click the panel to view individual notices.', 'disable-admin-notices' )
		],
	];

	$options[] = [
		'type'     => 'dropdown',
		'name'     => 'hide_admin_notices',
		'way'      => 'buttons',
		'title'    => __( 'Manage admin notices', 'disable-admin-notices' ),
		'data'     => $hide_admin_notices_data,
		'layout'   => [ 'hint-type' => 'icon', 'hint-icon-color' => 'green' ],
		'hint'     => __( 'Controls how plugin notices are displayed.', 'disable-admin-notices' ),
		'default'  => 'only_selected',
		'cssClass' => [],
		'events'   => [
			'all'           => [
				'show' => '.factory-control-hide_admin_notices_user_roles',
				'hide' => '.factory-control-reset_notices_button'
			],
			'only_selected' => [
				'hide' => '.factory-control-hide_admin_notices_user_roles',
				'show' => '.factory-control-reset_notices_button'
			],
			'not_hide'      => [
				'hide' => '.factory-control-hide_admin_notices_user_roles, .factory-control-reset_notices_button'
			]
		]
	];

	if ( ! wbcr_dan_is_active_clearfy_component() ) {
		$options[] = [
			'type'     => 'checkbox',
			'way'      => 'buttons',
			'name'     => 'disable_updates_nags_for_plugins',
			'title'    => __( 'Disable plugin update notifications', 'disable-admin-notices' ),
			'layout'   => [ 'hint-type' => 'icon', 'hint-icon-color' => 'green' ],
			'hint'     => __( 'Disable plugin update notifications (you will not see available plugin updates)', 'disable-admin-notices' ),
			'cssClass' => array(),
			'default'  => false
		];

		$options[] = [
			'type'     => 'checkbox',
			'way'      => 'buttons',
			'name'     => 'disable_updates_nags_for_core',
			'title'    => __( 'Disable WordPress core update notifications', 'disable-admin-notices' ),
			'layout'   => [ 'hint-type' => 'icon', 'hint-icon-color' => 'green' ],
			'hint'     => __( 'Disable WordPress core update notifications (you will not see available WordPress updates)', 'disable-admin-notices' ),
			'cssClass' => array(),
			'default'  => false
		];
	}

	/*$options[] = array(
		'type' => 'dropdown',
		'name' => 'hide_admin_notices_for',
		'way' => 'buttons',
		'title' => __('Hide admin notices only for', 'disable-admin-notices'),
		'data' => array(
			array(
				'user',
				__('Current user', 'disable-admin-notices')
			),
			array(
				'all_users',
				__('All users', 'disable-admin-notices')
			)
		),
		'layout' => array('hint-type' => 'icon', 'hint-icon-color' => 'green'),
		'hint' => __('Choose who to hide notifications for?', 'disable-admin-notices'),
		'default' => 'user',
		'events' => array(
			'all' => array(
				'show' => '.factory-control-hide_admin_notices_user_roles',
				'hide' => '.factory-control-reset_notices_button'
			),
			'only_selected' => array(
				'hide' => '.factory-control-hide_admin_notices_user_roles',
				'show' => '.factory-control-reset_notices_button'
			),
			'not_hide' => array(
				'hide' => '.factory-control-hide_admin_notices_user_roles, .factory-control-reset_notices_button'
			)
		)
	);*/

	$options[] = [
		'type'    => 'checkbox',
		'way'     => 'buttons',
		'name'    => 'show_notices_in_adminbar',
		'title'   => __( 'Show hidden notices in the admin toolbar', 'disable-admin-notices' ),
		'layout'  => [ 'hint-type' => 'icon', 'hint-icon-color' => 'green' ],
		'hint'    => __( 'Displays hidden admin notices in the admin bar for quick access.', 'disable-admin-notices' ),
		'default' => false
	];

	$options[] = [
		'type' => 'html',
		'html' => 'wbcr_dan_reset_notices_button'
	];

	return $options;
}

function wbcr_dan_is_active_clearfy_component() {
	if ( defined( 'WCL_PLUGIN_ACTIVE' ) && class_exists( 'WCL_Plugin' ) ) {
		$deactivate_components = WCL_Plugin::app()->getPopulateOption( 'deactive_preinstall_components', [] );
		if ( ! in_array( 'disable_notices', $deactivate_components ) ) {
            return true;
		}
	}
	return false;
}

/**
 * Расширяем опции html формы страницы "Дополнительно" в плагине Clearfy
 *
 * Это необходимо для того, чтобы не создавать отдельную страницу в плагине Clearfy, \
 * с настройками этого плагина, потому что это ухудшает юзабилити.
 *
 * @param array $form Массив с группой настроек, страницы "Дополнительно" в плагине Clearfy
 * @param Wbcr_FactoryPages480_ImpressiveThemplate $page Экземпляр страницы
 *
 * @return mixed Отсортированный массив с группой опций
 */
function wbcr_dan_additionally_form_options( $form, $page ) {
	if ( empty( $form ) ) {
		return $form;
	}

	$options = wbcr_dan_get_plugin_options();

	foreach ( array_reverse( $options ) as $option ) {
		array_unshift( $form[0]['items'], $option );
	}

	return $form;
}

add_filter( 'wbcr_clr_additionally_form_options', 'wbcr_dan_additionally_form_options', 10, 2 );

/**
 * Реализует кнопку сброса скрытых уведомлений.
 *
 * Вы можете выбрать для какой группы пользователей сбросить уведомления.
 * Эта модикация является не стандартной, поэтому мы не можете реалировать ее
 * через фреймворк.
 *
 * @param  @param $html_builder Wbcr_FactoryForms480_Html
 *
 * @since  1.0
 *
 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
 */
function wbcr_dan_reset_notices_button( $html_builder ) {
	global $wpdb;

	$form_name = $html_builder->getFormName();
	$reseted   = false;

	if ( isset( $_POST['wbcr_dan_reset_action'] ) ) {
		check_admin_referer( $form_name, 'wbcr_dan_reset_nonce' );
		$reset_for_users = WDN_Plugin::app()->request->post( 'wbcr_dan_reset_for_users', 'current_user', true );

		if ( $reset_for_users == 'current_user' ) {
			delete_user_meta( get_current_user_id(), WDN_Plugin::app()->getOptionName( 'hidden_notices' ) );
		} else {
			$meta_key = WDN_Plugin::app()->getOptionName( 'hidden_notices' );
			$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => $meta_key ), array( '%s' ) );
		}

		$reseted = true;
	}

	?>
    <div class="form-group form-group-checkbox factory-control-reset_notices_button">
        <label for="wbcr_clearfy_reset_notices_button" class="col-sm-4 control-label">
			<?php echo esc_html__( 'Reset hidden notices for:', 'disable-admin-notices' ); ?>
            <span class="factory-hint-icon factory-hint-icon-green" data-toggle="factory-tooltip" data-placement="right"
                  title=""
                  data-original-title="<?php echo esc_attr__( 'Restores all previously hidden admin notices.', 'disable-admin-notices' ); ?>">
					<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAkAAAAJCAQAAABKmM6bAAAAUUlEQVQIHU3BsQ1AQABA0X/komIrnQHYwyhqQ1hBo9KZRKL9CBfeAwy2ri42JA4mPQ9rJ6OVt0BisFM3Po7qbEliru7m/FkY+TN64ZVxEzh4ndrMN7+Z+jXCAAAAAElFTkSuQmCC"
                         alt="">
				</span>
        </label>
        <div class="control-group col-sm-8">
            <div class="factory-checkbox factory-from-control-checkbox factory-buttons-way btn-group">
                <form method="post">
					<?php wp_nonce_field( $form_name, 'wbcr_dan_reset_nonce' ); ?>
                    <p>
                        <input type="radio" name="wbcr_dan_reset_for_users" value="current_user"
                               checked/> <?php echo esc_html__( 'current user', 'disable-admin-notices' ); ?>
                    </p>
                    <p>
                        <input type="radio" name="wbcr_dan_reset_for_users"
                               value="all"/> <?php echo esc_html__( 'all users', 'disable-admin-notices' ); ?>
                    </p>
                    <p>
                        <input type="submit" name="wbcr_dan_reset_action"
                               value="<?php echo esc_attr__( 'Reset Hidden Notices', 'disable-admin-notices' ); ?>"
                               class="button button-default"/>
                    </p>
					<?php if ( $reseted ): ?>
                        <div style="color:green;margin-top:5px;"><?php echo esc_html__( 'Hidden notices are successfully reset, now you can see them again!', 'disable-admin-notices' ); ?></div>
					<?php endif; ?>
                </form>
            </div>
        </div>
    </div>
	<?php
}

