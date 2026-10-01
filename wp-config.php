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
define( 'DB_NAME', 'driveoexclusive' );

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
define( 'AUTH_KEY',         '$Wr6k5`.k(G=9/;N?nQODf:t&}P:3FII4{L0n&EH+8JDP+2tMinoE+xJ]5Co4/hk' );
define( 'SECURE_AUTH_KEY',  'aVr%Er[(G<h[/gdJPSn&PTTIY{Bo>@K{DEylH9i^+.).gpK^)krBgyGcy4{.f/fL' );
define( 'LOGGED_IN_KEY',    '9nR`IuR<>BP~QxBYQh~-81G^ b.(r}FvAWk;dE$%-l! Vx-z<TCh*#j{h =f;I#c' );
define( 'NONCE_KEY',        'll^/R7* IfQR{ge*x7q_;dm$3y94{$e@Ib5%H%_bc} R~/2E]-bo}*^f@+6_,6&5' );
define( 'AUTH_SALT',        '8cJ-wRMF<?~[~OH^QNTonAF9&iZY*[Nko}Rv9d* jNSAKYIu=)JRZma;XL,vg)EE' );
define( 'SECURE_AUTH_SALT', '=Ut10RP-tujuDQU:NCRyrF5 ,`go@Znd_Q5+,Qeto4NGgz&F<p}q{Gp9Rupknxg]' );
define( 'LOGGED_IN_SALT',   'CR?Eh(DP>*m0To,gT*~M<Rp+C.B%m<0ppB9,C$6,B/!j89EN@[}C1De*>ThK_mU&' );
define( 'NONCE_SALT',       'S{iL|1Vfk 2%H[eR;|/Kx2iN_i&y-R&cOyqYOm1Qm#TPoS%t#)@O{#-E`0|Fs6S|' );

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
$table_prefix = 'jax17_';

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
