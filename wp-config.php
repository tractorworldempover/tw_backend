<?php
define('WP_CACHE', true); // WP-Optimize Cache
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */
// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'M@hindra2025' );
/** Database username */
define( 'DB_USER', 'rootuser' );
/** Database password */
define( 'DB_PASSWORD', 'eMpover@2754' );
/** Database hostname */
define( 'DB_HOST', 'mazutwmmysql01.mysql.database.azure.com' );
/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );
/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );
/** Headless Code. */
///jwt
define( 'GRAPHQL_JWT_AUTH_SECRET_KEY', '7mj4_WntLba(-1na|Q%okh[v{g1s|9-b[WL=n/ p}SW}I.w+(FmE|5s<iP{Ud{/w' );
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
define( 'AUTH_KEY',         '$_fXQ:@b{M>KuUDRS}3N~i7$lR+b2[//,B{MwzeLKSaOn-B.N9]hZ0n+{%H-j=e/' );
define( 'SECURE_AUTH_KEY',  '&X RAYO6&;Zbf2~=Owy-yKJ:%j=w#~J%*y1R=##_lW>0Em#:ynI[Q(1_Q!7k*].{' );
define( 'LOGGED_IN_KEY',    'dzOMKnJD[,xAzh*)mN&g,e48W|dS.3r{WY#Om,~>Z>`gMWO=u=|GxUluHI=.IhTb' );
define( 'NONCE_KEY',        '<?,oq=Mk.j0<7(-#l-M+K3LBG]&]/q{:hH,Z3K>y3M*dr^2X)TB7wP;aa-aw-hWh' );
define( 'AUTH_SALT',        'jI7w|/K}Y.kXh^@!jJTce9Hs{vM|Tm>5B/0TWzFV,L#><7!m@-Fm@JHM&LL PJvS' );
define( 'SECURE_AUTH_SALT', '`;)WuvOtIzp0{$G.++^n.!_n7-&$S,hs/0nmZY8<70A6fzZ7hEU/AY/KocQiehH%' );
define( 'LOGGED_IN_SALT',   'wi;,!|2QG#,0K}wPv)8{[P.O8b)8O%72}Wz2Y041t$fZC&saVD4CyYxC4|lZ,*(m' );
define( 'NONCE_SALT',       'BF&jr4l-T;&o7R|=u$~0b&l,u`;v+y-I#qfuE7=uv|a(Z0Qsa_&gL_<}.mb)5;v3' );
/**#@-*/
/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'tw_';
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
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false); 
/* Add any custom values between this line and the "stop editing" line. */
/* That's all, stop editing! Happy publishing. */
/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';