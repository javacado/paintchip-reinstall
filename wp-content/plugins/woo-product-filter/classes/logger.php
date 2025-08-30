<?php
define('WPF_LOG', true);

class LoggerWpf {

	public static function getInstance() {
		static $instance;
		if ( ! $instance ) {
			$instance = new LoggerWpf();
		}

		return $instance;
	}

	public static function _() {
		return self::getInstance();
	}

	public function log( $message, $data = '' ) {
		if ( defined( 'WPF_LOG' ) && WPF_LOG === true ) {
			if ( ! is_string( $data ) && ! is_numeric( $data ) ) {
				$data = var_export( $data, true );
			}
			if ( ! function_exists( 'wc_get_logger' ) ) {
				include_once( ABSPATH . PLUGINDIR . '/woocommerce/woocommerce.php' );
			}
			wc_get_logger()->debug( "{$message} \n\n {$data} \n", array( '_legacy' => true ) );
		}
	}
}
