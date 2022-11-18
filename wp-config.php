<?php

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'shrikrishna_wp_e4fmn' );

/** MySQL database username */
define( 'DB_USER', 'shrikrishna_wp_zijns' );

/** MySQL database password */
define( 'DB_PASSWORD', 'F?E3Vs3c0O#$ZMeW' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost:3306' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', 'Yy#z3GVf)-Oo5[3u3#OVk@]N6]8N/FyQ7Eca~/N2|dVpKetZ*06/n9T0wMTX-(57');
define('SECURE_AUTH_KEY', '/nz*zClv_5RIp3-p4L43Y(k|U8ELK+G2U-[@w4/0Le:!~4OrFQ0A;Mn-S1&98GC6');
define('LOGGED_IN_KEY', 'n873[Xxx;u]Dq2)XzZ)1_0-/nC/9e2|xiiy]R(dt]4I#*D4)4o):4&Q+61/@9;wR');
define('NONCE_KEY', '*ccXzrK8i8661K[1cTqV++17!)/;5t(3]h7-j|1xVDPP|XptnP[JEs8wZn44YB4(');
define('AUTH_SALT', ':OY)-59YK)IGJKR[Ef2HnN+2q41j#+7]N41KFYB~~O8o:kn]b%Q8eQIyHKg)ui-v');
define('SECURE_AUTH_SALT', 'UL/;cEcIBaWX;3oelq;E|;89K5/4c/656C-XjS2_LG_;-g17TE&SH5]5sH]NUl*m');
define('LOGGED_IN_SALT', 'YlTX(Qo2xRdokn)[(w0G#:)R_4t9|&c#Eo9;IH:~Ro68hd1Bg0;&(tR5208NM;J(');
define('NONCE_SALT', '0X8Zl0OW|5e122)7Wi2oa2VU4Umd#_&bHaW6POPEqcwT@lCu9b2+Q#jce~#m8P;7');

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'SIjttOFp_';


define('WP_ALLOW_MULTISITE', true);
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
