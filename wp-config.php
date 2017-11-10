<?php
/** 
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information by
 * visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'gdd_scanface');

/** MySQL database username */
define('DB_USER', 'gdd_scanface');

/** MySQL database password */
define('DB_PASSWORD', 'du873q6j');

/** MySQL hostname */
define('DB_HOST', 'gdd.mysql.ukraine.com.ua');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link http://api.wordpress.org/secret-key/1.1/ WordPress.org secret-key service}
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'sO7ao4YvT&$)IG7DqMsP7VFM@kKlfd&7@DznjsYeveIPEWoEC@Qd9Ys73KDFB)DA');
define('SECURE_AUTH_KEY',  'UdE8yBgOB%0cM4u3uiW@jC8lksxXg74f^4#ceuK2PIz(rFDfI@UycmV@zUYLUDJ&');
define('LOGGED_IN_KEY',    'M3Xc(FD4VCFVY)*RI@9e22LukCtdtyaGmSxt(SY#%2gUCujRI0RRd^IN9JXsewf#');
define('NONCE_KEY',        'bhOUmGqrY&ux&*JGBWiTPb2GQNUF4HRB%^z)x0YQ*tGHJub1fqA@82X)kr(%s0$!');
define('AUTH_SALT',        ')enq4Uzudq3aVP8%h*yg&lghDQi5CkM0ih0XTjSLL&iatV19cvIgaOKly!Fx4iaI');
define('SECURE_AUTH_SALT', 'L5yv8cL1p5c!^Wf#*jw@AZ52Dn@9qq1v@)IZWpS%*9G7p8X8k7KaYWS8Rnvivsss');
define('LOGGED_IN_SALT',   '5kASWh7#X5wuI%vb1ZXRnjISWG$3SQk@u7msDOC0@1!*XNz@VK0eprdrxjjN81Yh');
define('NONCE_SALT',       '4p&1AGbwm1RO(1yQ(*1eL%k^6@fZ77EU*j(UjFYK0nX1ozU#nMyTR9#ruKVA3pcv');
/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress.  A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de.mo to wp-content/languages and set WPLANG to 'de' to enable German
 * language support.
 */
define ('WPLANG', 'ru_RU');

define ('FS_METHOD', 'direct');

define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** WordPress absolute path to the Wordpress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');
/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

?>
