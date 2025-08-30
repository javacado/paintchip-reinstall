<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'paintchip' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1:3309' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'i]1HUimOkk)];fx_h55/d+%1T;(2}Nym&W lR5u,j8VXV;,NQ6fdEhU)C)>Ufw}t' );
define( 'SECURE_AUTH_KEY',  'Y~`{$;e#B?5bS 63~/b pk]JAt{dl=L`nk:DkxP]/[$@,b96)IGKC.(Um:H[f4 r' );
define( 'LOGGED_IN_KEY',    'UU280n&2MPO+h&rk!%9A|l}&pWm$#>{w:K?$oc)3]Fe<HH$VpAgB>)Da.p*.#o[!' );
define( 'NONCE_KEY',        'T3)8>I0>jGQtV[Wp31a/lF!mxwGlZP~Qg@5_ql$E0aXhNr$i6Ja^t[p]oFl_{Lnc' );
define( 'AUTH_SALT',        'MnL>dE_3Eb-MV09qD*`{w1ta:5V@!w dAi(.]onMW{ {f8+b_L|MVbal~!:e.y4J' );
define( 'SECURE_AUTH_SALT', '`!%=QJ*onG@oMyy77gNSnF*m,#+  x>b$@zMqsw1K<,S8[3|U0RM C+jeyHQW.*;' );
define( 'LOGGED_IN_SALT',   'r)[J%.eBN<NnP!q0x.e)axJ4Na+)I0Vn4.lM[.-lB:hmbL1V?;(QMLC^glGmAi U' );
define( 'NONCE_SALT',       'j 9<8se,` v/q&K]9gn]A}k&=zf/qZZ^7d1/@ED#TeexEuaIG%-uuOk65TQ{qM>>' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
