<?php
/**
 * Plugin Name:       Tour Operator FAQ
 * Plugin URI:        https://github.com/lightspeedwp/to-faq
 * Description:       FAQs for Tour Operator: a collapsible Yoast FAQ block, repeatable FAQ fields with a "Post FAQs" block variation, and an FAQ template part.
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
require_once LSX_TO_FAQ_PATH . 'includes/classes/class-template-parts.php';
require_once LSX_TO_FAQ_PATH . 'includes/classes/class-faq-fields.php';

new lsx\to_faq\Faq();

/**
 * Load the features that depend on other plugins, once they have loaded.
 */
function lsx_to_faq_init() {
	// FAQ pattern and template part (requires Yoast SEO). The faq area is registered by Tour Operator core.
	new lsx\to_faq\Template_Parts();

	// Repeatable FAQ fields and the "Post FAQs" Yoast FAQ block variation (requires Yoast SEO; the meta box uses Tour Operator's bundled CMB2).
	if ( lsx\to_faq\Faq_Fields::is_supported() ) {
		new lsx\to_faq\Faq_Fields();
	}
}
add_action( 'plugins_loaded', 'lsx_to_faq_init' );
