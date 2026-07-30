<?php
/**
 * Ajax handlers
 *
 * @author        Alex Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @copyright (c) 2017 Webraftic Ltd
 * @version       1.0
 */

// Exit if accessed directly
if( !defined('ABSPATH') ) {
	exit;
}

/**
 * Обработчик ajax запросов для виджета подписки на новости
 *
 * @param Wbcr_Factory480_Plugin $plugin_instance
 *
 * @since 2.3.0
 *
 */
function wbcr_factory_templates_134_subscribe($plugin_instance)
{
	$plugin_name = $plugin_instance->request->post('plugin_name', null, true);

	if( ($plugin_instance->getPluginName() !== $plugin_name) || !$plugin_instance->current_user_can() ) {
		wp_die(-1, 403);
	}

	$email = $plugin_instance->request->post('email', null, true);

	check_admin_referer("clearfy_subscribe_for_{$plugin_name}");

	if( empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) ) {
		wp_send_json_error(['error_message' => __('You did not send your email address or it is incorrect!', 'wbcr_factory_templates_134')]);
	}



	$response = wp_remote_post('https://api.themeisle.com/tracking/subscribe', array(
		'timeout' => 10,
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Cache-Control' => 'no-cache',
			'Accept'        => 'application/json, */*;q=0.1',
		),
		'body'    => wp_json_encode(
			array(
				'slug'  => $plugin_name,
				'site'  => home_url(),
				'email' => $email,
			)
		),
	));
	if( is_wp_error($response) ) {
		wp_send_json_error(['error_message' => $response->get_error_message()]);
	}

	$data = @json_decode(wp_remote_retrieve_body($response), ARRAY_A);


	if( isset($data['code']) ) {
		$plugin_instance->updatePopulateOption( 'factory_clearfy_user_subsribed', 1 );
		wp_send_json_success( array( 'code' => $data['code'] ) );
	}

	die();
}
