<?php
defined( 'ABSPATH' ) || exit;

/**
 * A small session-aware HTTP client for supplier portals.
 *
 * WordPress's HTTP API is stateless, and classic-ASP portals authenticate with
 * an ASPSESSIONID cookie that has to survive across requests. This keeps a
 * cookie jar in an option, replays it, and re-authenticates when the session
 * expires.
 *
 * Credentials are encrypted at rest with a key derived from the site's own
 * AUTH_KEY/AUTH_SALT, so a database dump alone does not expose them. That is
 * meaningfully better than plaintext but is not a secret manager: anyone who
 * can read wp-config.php and the database can still decrypt.
 */
class PCI_Http {

	private $vend;
	private $cookies = array();

	public function __construct( $vend ) {
		$this->vend = strtoupper( $vend );
		$stored     = get_option( $this->cookie_option(), array() );
		if ( is_array( $stored ) ) {
			$this->cookies = $stored;
		}
	}

	private function cookie_option() {
		return 'pci_cookies_' . strtolower( $this->vend );
	}

	private static function cred_option( $vend ) {
		return 'pci_creds_' . strtolower( $vend );
	}

	// ------------------------------------------------------------ credentials

	private static function crypt_key() {
		$material = ( defined( 'AUTH_KEY' ) ? AUTH_KEY : '' ) . ( defined( 'AUTH_SALT' ) ? AUTH_SALT : '' );
		if ( '' === $material ) {
			$material = get_option( 'pci_fallback_key' );
			if ( ! $material ) {
				$material = wp_generate_password( 64, true, true );
				update_option( 'pci_fallback_key', $material, false );
			}
		}
		return hash( 'sha256', 'pci|' . $material, true );
	}

	public static function save_credentials( $vend, $user, $pass ) {
		$user = trim( (string) $user );

		// An empty password means "leave the stored one alone", so the settings
		// form can render without ever echoing the password back to the browser.
		if ( '' === (string) $pass ) {
			$existing = self::get_credentials( $vend );
			$pass     = isset( $existing['pass'] ) ? $existing['pass'] : '';
		}

		if ( '' === $user && '' === (string) $pass ) {
			delete_option( self::cred_option( $vend ) );
			return;
		}

		$payload = wp_json_encode( array( 'user' => $user, 'pass' => (string) $pass ) );

		if ( function_exists( 'openssl_encrypt' ) ) {
			$iv     = openssl_random_pseudo_bytes( 16 );
			$cipher = openssl_encrypt( $payload, 'aes-256-cbc', self::crypt_key(), OPENSSL_RAW_DATA, $iv );
			$value  = 'v1:' . base64_encode( $iv . $cipher );
		} else {
			$value = 'v0:' . base64_encode( $payload );
		}

		update_option( self::cred_option( $vend ), $value, false );
	}

	/** @return array{user:string,pass:string} */
	public static function get_credentials( $vend ) {
		$raw = get_option( self::cred_option( $vend ), '' );
		if ( ! is_string( $raw ) || '' === $raw ) {
			return array( 'user' => '', 'pass' => '' );
		}

		if ( 0 === strpos( $raw, 'v1:' ) && function_exists( 'openssl_decrypt' ) ) {
			$bin = base64_decode( substr( $raw, 3 ) );
			if ( strlen( $bin ) > 16 ) {
				$json = openssl_decrypt( substr( $bin, 16 ), 'aes-256-cbc', self::crypt_key(), OPENSSL_RAW_DATA, substr( $bin, 0, 16 ) );
				$data = json_decode( (string) $json, true );
				if ( is_array( $data ) ) {
					return array(
						'user' => isset( $data['user'] ) ? $data['user'] : '',
						'pass' => isset( $data['pass'] ) ? $data['pass'] : '',
					);
				}
			}
		} elseif ( 0 === strpos( $raw, 'v0:' ) ) {
			$data = json_decode( (string) base64_decode( substr( $raw, 3 ) ), true );
			if ( is_array( $data ) ) {
				return array(
					'user' => isset( $data['user'] ) ? $data['user'] : '',
					'pass' => isset( $data['pass'] ) ? $data['pass'] : '',
				);
			}
		}

		return array( 'user' => '', 'pass' => '' );
	}

	public static function has_credentials( $vend ) {
		$c = self::get_credentials( $vend );
		return '' !== $c['user'];
	}

	// ---------------------------------------------------------------- cookies

	public function cookie_header() {
		$bits = array();
		foreach ( $this->cookies as $name => $value ) {
			$bits[] = $name . '=' . $value;
		}
		return implode( '; ', $bits );
	}

	private function absorb_cookies( $response ) {
		$jar = wp_remote_retrieve_cookies( $response );
		if ( empty( $jar ) ) {
			return;
		}
		foreach ( $jar as $cookie ) {
			$this->cookies[ $cookie->name ] = $cookie->value;
		}
		update_option( $this->cookie_option(), $this->cookies, false );
	}

	public function clear_session() {
		$this->cookies = array();
		delete_option( $this->cookie_option() );
	}

	public function has_session() {
		return ! empty( $this->cookies );
	}

	// --------------------------------------------------------------- requests

	private function base_args( array $args = array() ) {
		$defaults = array(
			'timeout'     => 30,
			'redirection' => 5,
			'user-agent'  => 'ThePaintChip-InventorySync/1.1 (+https://thepaint-chip.com)',
			'headers'     => array(
				// Must be permissive: the category tree is served as .js files
				// and the server answers 406 to an html-only Accept header.
				'Accept'          => '*/*',
				'Accept-Language' => 'en-US,en;q=0.9',
			),
		);

		$args = array_replace_recursive( $defaults, $args );

		$cookie = $this->cookie_header();
		if ( '' !== $cookie ) {
			$args['headers']['Cookie'] = $cookie;
		}

		return $args;
	}

	/** @return string|WP_Error Body. */
	public function get( $url, array $args = array() ) {
		$res = wp_remote_get( $url, $this->base_args( $args ) );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$this->absorb_cookies( $res );

		$code = (int) wp_remote_retrieve_response_code( $res );
		if ( $code >= 400 ) {
			return new WP_Error( 'pci_http', sprintf( __( 'HTTP %d from %s', 'pci' ), $code, $url ), array( 'url' => $url ) );
		}

		return wp_remote_retrieve_body( $res );
	}

	/** @return string|WP_Error Body. */
	public function post( $url, array $fields, array $args = array() ) {
		$args = $this->base_args( array_merge( $args, array( 'body' => $fields ) ) );
		$res  = wp_remote_post( $url, $args );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$this->absorb_cookies( $res );

		$code = (int) wp_remote_retrieve_response_code( $res );
		if ( $code >= 400 ) {
			return new WP_Error( 'pci_http', sprintf( __( 'HTTP %d from %s', 'pci' ), $code, $url ), array( 'url' => $url ) );
		}

		return wp_remote_retrieve_body( $res );
	}

	// --------------------------------------------------------------- form aid

	/**
	 * Read a form out of an HTML page.
	 *
	 * Portals of this vintage often have hidden fields and non-obvious input
	 * names, and ASP.NET adds __VIEWSTATE. Rather than guess, the setup screen
	 * shows what is actually on the page and pre-fills the field mapping from
	 * it.
	 *
	 * @return array{action:string,method:string,fields:array,password_field:string,text_fields:array}|WP_Error
	 */
	public static function inspect_form( $html, $page_url ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return new WP_Error( 'pci_noform', __( 'The page returned nothing to inspect.', 'pci' ) );
		}

		if ( ! preg_match( '#<form\b([^>]*)>(.*?)</form>#is', $html, $m ) ) {
			return new WP_Error( 'pci_noform', __( 'No <form> was found on that page.', 'pci' ) );
		}

		$attrs = $m[1];
		$inner = $m[2];

		$action = '';
		if ( preg_match( '/action\s*=\s*["\']([^"\']*)["\']/i', $attrs, $a ) ) {
			$action = $a[1];
		}
		$method = 'post';
		if ( preg_match( '/method\s*=\s*["\']([^"\']*)["\']/i', $attrs, $a ) ) {
			$method = strtolower( $a[1] );
		}

		$fields   = array();
		$pass     = '';
		$texts    = array();

		if ( preg_match_all( '/<input\b([^>]*)>/i', $inner, $inputs ) ) {
			foreach ( $inputs[1] as $tag ) {
				$name = '';
				$type = 'text';
				$val  = '';
				if ( preg_match( '/name\s*=\s*["\']([^"\']*)["\']/i', $tag, $x ) ) {
					$name = $x[1];
				}
				if ( preg_match( '/type\s*=\s*["\']([^"\']*)["\']/i', $tag, $x ) ) {
					$type = strtolower( $x[1] );
				}
				if ( preg_match( '/value\s*=\s*["\']([^"\']*)["\']/i', $tag, $x ) ) {
					$val = $x[1];
				}
				if ( '' === $name ) {
					continue;
				}
				$fields[ $name ] = array( 'type' => $type, 'value' => $val );
				if ( 'password' === $type ) {
					$pass = $name;
				} elseif ( in_array( $type, array( 'text', 'email', '' ), true ) ) {
					$texts[] = $name;
				}
			}
		}

		// Resolve a relative action against the page it came from.
		if ( '' === $action ) {
			$action = $page_url;
		} elseif ( ! preg_match( '#^https?://#i', $action ) ) {
			$parts = wp_parse_url( $page_url );
			$base  = $parts['scheme'] . '://' . $parts['host'];
			$action = ( 0 === strpos( $action, '/' ) )
				? $base . $action
				: $base . rtrim( dirname( $parts['path'] ), '/' ) . '/' . $action;
		}

		return array(
			'action'         => $action,
			'method'         => $method,
			'fields'         => $fields,
			'password_field' => $pass,
			'text_fields'    => $texts,
		);
	}
}
