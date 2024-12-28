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
define( 'DB_NAME', 'cont1' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

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
define( 'AUTH_KEY',         'Odq`Jz#08lN?Ojk7b@; jrMgH8efZhk5F?^)CCI97cX0,38#+}j(WThHf8smJ}Hs' );
define( 'SECURE_AUTH_KEY',  'J`5B+ea0&RR- ByKjMl8Vwk~/kWyRmS*ZTR&,J,}=4`etX3favmX >:/~yG(7aRh' );
define( 'LOGGED_IN_KEY',    '+YYwr!31j6c^vk0J|]T8B1~SQKy8O+q.2k]jew3EppUl,&6Qi5?pRM}bUjhFnK1A' );
define( 'NONCE_KEY',        'XI9EQ}f|=L80&oni;qL=<`WahuVE)9U(FnQ>eu3!X<Ms2,5*;.{P]eMgF)bP+T!z' );
define( 'AUTH_SALT',        '}VuI0g: f>1wB3$8P5;ZPeNFx7|v+f8<Oq:hrsQ4D($6Ck-NT<ML8t14>3#}3Be>' );
define( 'SECURE_AUTH_SALT', ':ph.1{SU6FEARG`<f)&e{Xh{kO.EtRt9}WQBLa[x6b^O3`s2_ .u|s#m(f:kuxyn' );
define( 'LOGGED_IN_SALT',   '?j1s=*W?P{cO@$u$r`K}DhT#S:oWts *`3z/8_JA=N,.(*(hI#/{I(3$Dhpe%C=$' );
define( 'NONCE_SALT',       '5$);q!!bU;ldQi={1 Ch RH4E$xUnpP_>lD]%{&Tc]8z)OeJbPs0DhUUE2OVqe-I' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
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
