<?php
/**
 * Plugin Name: GDPR Cookie Consent Elementor
 * Plugin URI: https://github.com/delharris1981/gdpr-cookie-consent-elementor/tree/main
 * Description: A custom Elementor widget for GDPR cookie consent with customizable text, buttons, and styling options. Blocks all cookies when declined.
 * Version: 1.2.1
 * Author: Panda ADV
 * Author URI: 
 * Requires PHP: 8.2
 * Requires at least: 6.8
 * Text Domain: gdpr-cookie-consent-elementor
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package GDPR_Cookie_Consent_Elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants.
define( 'GDPR_CCE_VERSION', '1.2.1' );
define( 'GDPR_CCE__FILE__', __FILE__ );
define( 'GDPR_CCE_PLUGIN_BASE', plugin_basename( GDPR_CCE__FILE__ ) );
define( 'GDPR_CCE_PATH', plugin_dir_path( GDPR_CCE__FILE__ ) );
define( 'GDPR_CCE_URL', plugins_url( '/', GDPR_CCE__FILE__ ) );
define( 'GDPR_CCE_ASSETS_PATH', GDPR_CCE_PATH . 'assets/' );
define( 'GDPR_CCE_ASSETS_URL', GDPR_CCE_URL . 'assets/' );

/**
 * Main plugin class.
 *
 * @since 1.0.0
 */
final class GDPR_Cookie_Consent_Elementor {

	/**
	 * Plugin instance.
	 *
	 * @var GDPR_Cookie_Consent_Elementor
	 */
	private static $instance = null;

	/**
	 * Cookie Blocker instance.
	 *
	 * @var Cookie_Blocker
	 */
	private $cookie_blocker = null;

	/**
	 * Get plugin instance.
	 *
	 * @return GDPR_Cookie_Consent_Elementor
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize plugin.
	 *
	 * @return void
	 */
	private function init() {
		// Load and initialize PHP cookie blocker early (before headers are sent).
		$this->init_cookie_blocker();

		// Check if Elementor is installed and activated.
		add_action( 'plugins_loaded', array( $this, 'check_elementor_dependency' ) );

		// Initialize admin settings.
		if ( is_admin() ) {
			require_once GDPR_CCE_PATH . 'includes/class-admin-settings.php';
			new \GDPR_Cookie_Consent_Elementor\Admin_Settings();
		}

		// Output cookie blocker as early as possible (before wp_head).
		add_action( 'wp_head', array( $this, 'output_cookie_blocker_inline' ), 0 );

		// Enqueue global cookie blocker script early.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_cookie_blocker' ), 1 );

		// Note: Cookie detector script is enqueued by Cookie_Detector class.

		// Register scripts early so they're available for widget dependencies.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ), 0 );

		// Initialize defaults on activation.
		register_activation_hook( GDPR_CCE__FILE__, array( $this, 'activate' ) );
	}

	/**
	 * Initialize PHP cookie blocker.
	 * Must be called early, before headers are sent.
	 *
	 * @return void
	 */
	private function init_cookie_blocker() {
		// Require cookie blocker class.
		require_once GDPR_CCE_PATH . 'includes/class-cookie-blocker.php';

		// Require category manager.
		require_once GDPR_CCE_PATH . 'includes/class-cookie-category-manager.php';

		// Require category defaults.
		require_once GDPR_CCE_PATH . 'includes/class-cookie-category-defaults.php';

		// Require cookie detector.
		require_once GDPR_CCE_PATH . 'includes/class-cookie-detector.php';

		// Require pattern learner.
		require_once GDPR_CCE_PATH . 'includes/class-cookie-pattern-learner.php';

		// Instantiate cookie blocker (initializes hooks immediately).
		$this->cookie_blocker = new \GDPR_Cookie_Consent_Elementor\Cookie_Blocker();

		// Initialize cookie detector.
		new \GDPR_Cookie_Consent_Elementor\Cookie_Detector();
	}

	/**
	 * Register scripts.
	 *
	 * @return void
	 */
	public function register_scripts() {
		// Register widget frontend script.
		wp_register_script(
			'gdpr-widget-frontend',
			GDPR_CCE_ASSETS_URL . 'js/gdpr-widget-frontend.js',
			array( 'jquery' ),
			GDPR_CCE_VERSION,
			true
		);
	}

	/**
	 * Check if Elementor is active.
	 *
	 * @return void
	 */
	public function check_elementor_dependency() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', array( $this, 'elementor_missing_notice' ) );
			return;
		}

		// Register widget.
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
	}

	/**
	 * Admin notice for missing Elementor.
	 *
	 * @return void
	 */
	public function elementor_missing_notice() {
		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor */
			esc_html__( '%1$s requires %2$s to be installed and activated.', 'gdpr-cookie-consent-elementor' ),
			'<strong>' . esc_html__( 'GDPR Cookie Consent Elementor', 'gdpr-cookie-consent-elementor' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'gdpr-cookie-consent-elementor' ) . '</strong>'
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%s</p></div>', wp_kses_post( $message ) );
	}

	/**
	 * Register Elementor widgets.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {
		require_once GDPR_CCE_PATH . 'includes/class-gdpr-widget.php';
		$widgets_manager->register( new \GDPR_Cookie_Consent_Elementor\Widgets\GDPR_Widget() );
	}

	/**
	 * Enqueue global cookie blocker script.
	 *
	 * This script must load early to block cookies before other scripts execute.
	 *
	 * @return void
	 */
	public function enqueue_cookie_blocker() {
		// Also enqueue as external file as backup.
		wp_enqueue_script(
			'gdpr-cookie-blocker',
			GDPR_CCE_ASSETS_URL . 'js/gdpr-cookie-blocker.js',
			array(),
			GDPR_CCE_VERSION,
			false // Load in header, not footer.
		);

		// Enqueue widget frontend script.
		wp_enqueue_script(
			'gdpr-widget-frontend',
			GDPR_CCE_ASSETS_URL . 'js/gdpr-widget-frontend.js',
			array( 'jquery' ),
			GDPR_CCE_VERSION,
			true // Load in footer.
		);

		// Localize script with AJAX URL.
		wp_localize_script(
			'gdpr-widget-frontend',
			'gdprCookieConsent',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			)
		);

		// Enqueue widget CSS.
		wp_enqueue_style(
			'gdpr-widget',
			GDPR_CCE_ASSETS_URL . 'css/gdpr-widget.css',
			array(),
			GDPR_CCE_VERSION
		);
	}

	/**
	 * Output cookie blocker as inline script in head.
	 *
	 * @return void
	 */
	public function output_cookie_blocker_inline() {
		$blocker_file      = GDPR_CCE_ASSETS_PATH . 'js/gdpr-cookie-blocker.js';
		$real_blocker_path = realpath( $blocker_file );
		$real_assets_path  = realpath( GDPR_CCE_ASSETS_PATH );

		if ( ! $real_blocker_path || ! $real_assets_path ) {
			return;
		}

		// Ensure the blocker file resides within the expected assets directory.
		if ( 0 !== strpos( $real_blocker_path, $real_assets_path ) || ! is_readable( $real_blocker_path ) ) {
			return;
		}

		$blocker_code = file_get_contents( $real_blocker_path );
		if ( false !== $blocker_code && '' !== $blocker_code ) {
			// Safely print inline script using core helper.
			wp_print_inline_script_tag( $blocker_code );
		}
	}


	/**
	 * Plugin activation hook.
	 * Initialize default categories and mappings.
	 *
	 * @return void
	 */
	public function activate() {
		require_once GDPR_CCE_PATH . 'includes/class-cookie-category-manager.php';
		require_once GDPR_CCE_PATH . 'includes/class-cookie-category-defaults.php';

		$category_manager = new \GDPR_Cookie_Consent_Elementor\Cookie_Category_Manager();
		$defaults = new \GDPR_Cookie_Consent_Elementor\Cookie_Category_Defaults();

		// Initialize default categories if none exist.
		$categories = $category_manager->get_categories( false );
		if ( empty( $categories ) ) {
			$category_manager->save_categories( $defaults->get_default_categories() );
		}

		// Initialize default mappings if none exist.
		$mappings = $category_manager->get_cookie_mappings( false );
		if ( empty( $mappings ) ) {
			update_option( \GDPR_Cookie_Consent_Elementor\Cookie_Category_Manager::OPTION_MAPPINGS, $defaults->get_default_mappings() );
		}
	}
}

/**
 * Initialize plugin.
 *
 * @return GDPR_Cookie_Consent_Elementor
 */
function gdpr_cookie_consent_elementor() {
	return GDPR_Cookie_Consent_Elementor::instance();
}

// Start the plugin.
gdpr_cookie_consent_elementor();

