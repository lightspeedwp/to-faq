<?php
/**
 * Plugin Name:       Tour Operator FAQ
 * Plugin URI:        https://github.com/lightspeedwp/to-faq
 * Description:       Turns the Yoast FAQ block into an accessible, collapsible accordion for Tour Operator sites.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            LightSpeed
 * Author URI:        https://lightspeedwp.agency/
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       to-faq
 * Domain Path:       /languages
 *
 * @package TO_FAQ
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LSX_TO_FAQ_VER', '0.1.0' );
define( 'LSX_TO_FAQ_CORE', __FILE__ );
define( 'LSX_TO_FAQ_PATH', plugin_dir_path( __FILE__ ) );
define( 'LSX_TO_FAQ_URL', plugin_dir_url( __FILE__ ) );

require_once LSX_TO_FAQ_PATH . 'includes/classes/class-faq.php';

new lsx\to_faq\Faq();
