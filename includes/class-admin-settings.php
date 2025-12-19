<?php
/**
 * Admin Settings
 *
 * Handles admin settings page for cookie categories and mappings.
 *
 * @package GDPR_Cookie_Consent_Elementor
 */

namespace GDPR_Cookie_Consent_Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Admin Settings Class.
 *
 * @since 1.2.0
 */
class Admin_Settings {

	/**
	 * Category manager instance.
	 *
	 * @var Cookie_Category_Manager
	 */
	private $category_manager = null;

	/**
	 * Cookie detector instance.
	 *
	 * @var Cookie_Detector
	 */
	private $cookie_detector = null;

	/**
	 * Pattern learner instance.
	 *
	 * @var Cookie_Pattern_Learner
	 */
	private $pattern_learner = null;

	/**
	 * Initialize admin settings.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_gdpr_save_category', array( $this, 'handle_save_category_ajax' ) );
		add_action( 'wp_ajax_gdpr_delete_category', array( $this, 'handle_delete_category_ajax' ) );
		add_action( 'wp_ajax_gdpr_save_mapping', array( $this, 'handle_save_mapping_ajax' ) );
		add_action( 'wp_ajax_gdpr_delete_mapping', array( $this, 'handle_delete_mapping_ajax' ) );
		add_action( 'wp_ajax_gdpr_assign_category_to_cookie', array( $this, 'handle_assign_category_ajax' ) );
		add_action( 'wp_ajax_gdpr_test_detection', array( $this, 'handle_test_detection_ajax' ) );
	}

	/**
	 * Add admin menu.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'Cookie Categories', 'gdpr-cookie-consent-elementor' ),
			__( 'Cookie Categories', 'gdpr-cookie-consent-elementor' ),
			'manage_options',
			'gdpr-cookie-categories',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'gdpr_cookie_categories',
			'gdpr_category_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'settings_page_gdpr-cookie-categories' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'gdpr-admin-styles',
			GDPR_CCE_ASSETS_URL . '../admin/css/admin-styles.css',
			array(),
			GDPR_CCE_VERSION
		);

		wp_enqueue_script(
			'gdpr-admin-scripts',
			GDPR_CCE_ASSETS_URL . '../admin/js/admin-scripts.js',
			array( 'jquery' ),
			GDPR_CCE_VERSION,
			true
		);

		$category_manager = $this->get_category_manager();
		$categories = $category_manager->get_categories();

		// Prepare categories for JavaScript (for mapping dropdown).
		$categories_js = array();
		foreach ( $categories as $category ) {
			$categories_js[] = array(
				'id'   => $category['id'],
				'name' => $category['name'],
			);
		}

		wp_localize_script(
			'gdpr-admin-scripts',
			'gdprAdmin',
			array(
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'gdpr_admin_nonce' ),
				'categories' => $categories_js,
			)
		);
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$category_manager = $this->get_category_manager();
		$cookie_detector = $this->get_cookie_detector();
		$categories = $category_manager->get_categories();
		$mappings = $category_manager->get_cookie_mappings();
		$detected_cookies = $cookie_detector->get_detected_cookies();
		$settings = $category_manager->get_settings();

		// Load view.
		$view_path = GDPR_CCE_PATH . 'admin/views/settings-page.php';
		if ( file_exists( $view_path ) ) {
			include $view_path;
		} else {
			// Fallback inline view.
			$this->render_inline_settings_page( $categories, $mappings, $detected_cookies, $settings );
		}
	}

	/**
	 * Render inline settings page (fallback).
	 *
	 * @param array $categories      Categories array.
	 * @param array $mappings        Mappings array.
	 * @param array $detected_cookies Detected cookies array.
	 * @param array $settings        Settings array.
	 * @return void
	 */
	private function render_inline_settings_page( $categories, $mappings, $detected_cookies, $settings ) {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p><?php esc_html_e( 'Manage cookie categories and mappings for GDPR compliance.', 'gdpr-cookie-consent-elementor' ); ?></p>

			<div class="gdpr-admin-tabs">
				<nav class="nav-tab-wrapper">
					<a href="#categories" class="nav-tab nav-tab-active"><?php esc_html_e( 'Categories', 'gdpr-cookie-consent-elementor' ); ?></a>
					<a href="#mappings" class="nav-tab"><?php esc_html_e( 'Cookie Mappings', 'gdpr-cookie-consent-elementor' ); ?></a>
					<a href="#detected" class="nav-tab"><?php esc_html_e( 'Detected Cookies', 'gdpr-cookie-consent-elementor' ); ?></a>
					<a href="#settings" class="nav-tab"><?php esc_html_e( 'Settings', 'gdpr-cookie-consent-elementor' ); ?></a>
				</nav>

				<div id="categories" class="tab-content active">
					<h2><?php esc_html_e( 'Cookie Categories', 'gdpr-cookie-consent-elementor' ); ?></h2>
					<p><?php esc_html_e( 'Define cookie categories that users can consent to.', 'gdpr-cookie-consent-elementor' ); ?></p>
					<?php $this->render_categories_section( $categories ); ?>
				</div>

				<div id="mappings" class="tab-content">
					<h2><?php esc_html_e( 'Cookie Mappings', 'gdpr-cookie-consent-elementor' ); ?></h2>
					<p><?php esc_html_e( 'Map cookie patterns to categories.', 'gdpr-cookie-consent-elementor' ); ?></p>
					<?php $this->render_mappings_section( $mappings, $categories ); ?>
				</div>

				<div id="detected" class="tab-content">
					<h2><?php esc_html_e( 'Detected Cookies', 'gdpr-cookie-consent-elementor' ); ?></h2>
					<p><?php esc_html_e( 'Review automatically detected cookies and assign categories.', 'gdpr-cookie-consent-elementor' ); ?></p>
					<?php $this->render_detected_cookies_section( $detected_cookies, $categories ); ?>
				</div>

				<div id="settings" class="tab-content">
					<h2><?php esc_html_e( 'Settings', 'gdpr-cookie-consent-elementor' ); ?></h2>
					<?php $this->render_settings_section( $settings ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render categories section.
	 *
	 * @param array $categories Categories array.
	 * @return void
	 */
	private function render_categories_section( $categories ) {
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'gdpr-cookie-consent-elementor' ); ?></th>
					<th><?php esc_html_e( 'Description', 'gdpr-cookie-consent-elementor' ); ?></th>
					<th><?php esc_html_e( 'Required', 'gdpr-cookie-consent-elementor' ); ?></th>
					<th><?php esc_html_e( 'Default Enabled', 'gdpr-cookie-consent-elementor' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'gdpr-cookie-consent-elementor' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $categories as $category ) : ?>
					<tr>
						<td><?php echo esc_html( $category['name'] ); ?></td>
						<td><?php echo esc_html( $category['description'] ); ?></td>
						<td><?php echo $category['required'] ? esc_html__( 'Yes', 'gdpr-cookie-consent-elementor' ) : esc_html__( 'No', 'gdpr-cookie-consent-elementor' ); ?></td>
						<td><?php echo $category['default_enabled'] ? esc_html__( 'Yes', 'gdpr-cookie-consent-elementor' ) : esc_html__( 'No', 'gdpr-cookie-consent-elementor' ); ?></td>
						<td>
							<button class="button button-small edit-category" data-id="<?php echo esc_attr( $category['id'] ); ?>"><?php esc_html_e( 'Edit', 'gdpr-cookie-consent-elementor' ); ?></button>
							<?php if ( ! $category['required'] ) : ?>
								<button class="button button-small delete-category" data-id="<?php echo esc_attr( $category['id'] ); ?>"><?php esc_html_e( 'Delete', 'gdpr-cookie-consent-elementor' ); ?></button>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<button class="button button-primary" id="add-category"><?php esc_html_e( 'Add Category', 'gdpr-cookie-consent-elementor' ); ?></button>
		<?php
	}

	/**
	 * Render mappings section.
	 *
	 * @param array $mappings  Mappings array.
	 * @param array $categories Categories array.
	 * @return void
	 */
	private function render_mappings_section( $mappings, $categories ) {
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Pattern', 'gdpr-cookie-consent-elementor' ); ?></th>
					<th><?php esc_html_e( 'Domain', 'gdpr-cookie-consent-elementor' ); ?></th>
					<th><?php esc_html_e( 'Path', 'gdpr-cookie-consent-elementor' ); ?></th>
					<th><?php esc_html_e( 'Category', 'gdpr-cookie-consent-elementor' ); ?></th>
					<th><?php esc_html_e( 'Priority', 'gdpr-cookie-consent-elementor' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'gdpr-cookie-consent-elementor' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $mappings as $index => $mapping ) : ?>
					<tr>
						<td><?php echo esc_html( $mapping['pattern'] ); ?></td>
						<td><?php echo esc_html( $mapping['domain'] ?: '-' ); ?></td>
						<td><?php echo esc_html( $mapping['path'] ?: '-' ); ?></td>
						<td><?php echo esc_html( $mapping['category'] ); ?></td>
						<td><?php echo esc_html( $mapping['priority'] ?? 10 ); ?></td>
						<td>
							<button class="button button-small edit-mapping" data-index="<?php echo esc_attr( $index ); ?>"><?php esc_html_e( 'Edit', 'gdpr-cookie-consent-elementor' ); ?></button>
							<button class="button button-small delete-mapping" data-index="<?php echo esc_attr( $index ); ?>"><?php esc_html_e( 'Delete', 'gdpr-cookie-consent-elementor' ); ?></button>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<button class="button button-primary" id="add-mapping"><?php esc_html_e( 'Add Mapping', 'gdpr-cookie-consent-elementor' ); ?></button>
		<?php
	}

	/**
	 * Render detected cookies section.
	 *
	 * @param array $detected_cookies Detected cookies array.
	 * @param array $categories       Categories array.
	 * @return void
	 */
	private function render_detected_cookies_section( $detected_cookies, $categories ) {
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Cookie Name', 'gdpr-cookie-consent-elementor' ); ?></th>
					<th><?php esc_html_e( 'Domain', 'gdpr-cookie-consent-elementor' ); ?></th>
					<th><?php esc_html_e( 'Source', 'gdpr-cookie-consent-elementor' ); ?></th>
					<th><?php esc_html_e( 'Suggested Category', 'gdpr-cookie-consent-elementor' ); ?></th>
					<th><?php esc_html_e( 'Assigned Category', 'gdpr-cookie-consent-elementor' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'gdpr-cookie-consent-elementor' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $detected_cookies as $key => $cookie ) : ?>
					<tr>
						<td><?php echo esc_html( $cookie['name'] ); ?></td>
						<td><?php echo esc_html( $cookie['domain'] ?: '-' ); ?></td>
						<td><?php echo esc_html( $cookie['source'] ); ?></td>
						<td><?php echo esc_html( $cookie['suggested_category'] ?: '-' ); ?></td>
						<td><?php echo esc_html( $cookie['assigned_category'] ?: '-' ); ?></td>
						<td>
							<select class="assign-category" data-cookie-key="<?php echo esc_attr( $key ); ?>">
								<option value=""><?php esc_html_e( 'Select Category', 'gdpr-cookie-consent-elementor' ); ?></option>
								<?php foreach ( $categories as $category ) : ?>
									<option value="<?php echo esc_attr( $category['id'] ); ?>" <?php selected( $cookie['assigned_category'], $category['id'] ); ?>>
										<?php echo esc_html( $category['name'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render settings section.
	 *
	 * @param array $settings Settings array.
	 * @return void
	 */
	private function render_settings_section( $settings ) {
		?>
		<form method="post" action="options.php">
			<?php settings_fields( 'gdpr_cookie_categories' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="gdpr_mode"><?php esc_html_e( 'Default Mode', 'gdpr-cookie-consent-elementor' ); ?></label>
					</th>
					<td>
						<select name="gdpr_category_settings[mode]" id="gdpr_mode">
							<option value="simple" <?php selected( $settings['mode'], 'simple' ); ?>><?php esc_html_e( 'Simple (Accept/Decline)', 'gdpr-cookie-consent-elementor' ); ?></option>
							<option value="categories" <?php selected( $settings['mode'], 'categories' ); ?>><?php esc_html_e( 'Categories', 'gdpr-cookie-consent-elementor' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Default mode for new widgets.', 'gdpr-cookie-consent-elementor' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<?php
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $settings Settings array.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $settings ) {
		$sanitized = array();
		if ( isset( $settings['mode'] ) ) {
			$mode = sanitize_text_field( $settings['mode'] );
			$sanitized['mode'] = in_array( $mode, array( 'simple', 'categories' ), true ) ? $mode : 'simple';
		}
		return $sanitized;
	}

	/**
	 * Handle save category AJAX.
	 *
	 * @return void
	 */
	public function handle_save_category_ajax() {
		check_ajax_referer( 'gdpr_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'gdpr-cookie-consent-elementor' ) ) );
		}

		$category = array(
			'id'             => isset( $_POST['id'] ) ? sanitize_key( $_POST['id'] ) : '',
			'name'           => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'description'    => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
			'required'       => isset( $_POST['required'] ) ? (bool) $_POST['required'] : false,
			'default_enabled' => isset( $_POST['default_enabled'] ) ? (bool) $_POST['default_enabled'] : false,
			'order'          => isset( $_POST['order'] ) ? absint( $_POST['order'] ) : 0,
		);

		$category_manager = $this->get_category_manager();
		$categories = $category_manager->get_categories();

		if ( empty( $category['id'] ) ) {
			// New category - generate ID from name.
			$category['id'] = sanitize_key( $category['name'] );
			// Ensure ID is unique.
			$original_id = $category['id'];
			$counter = 1;
			foreach ( $categories as $existing ) {
				if ( isset( $existing['id'] ) && $existing['id'] === $category['id'] ) {
					$category['id'] = $original_id . '-' . $counter;
					$counter++;
				}
			}
		}

		// Update or add category.
		$found = false;
		foreach ( $categories as $index => $cat ) {
			if ( $cat['id'] === $category['id'] ) {
				$categories[ $index ] = $category;
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			$categories[] = $category;
		}

		$result = $category_manager->save_categories( $categories );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Category saved.', 'gdpr-cookie-consent-elementor' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to save category.', 'gdpr-cookie-consent-elementor' ) ) );
		}
	}

	/**
	 * Handle delete category AJAX.
	 *
	 * @return void
	 */
	public function handle_delete_category_ajax() {
		check_ajax_referer( 'gdpr_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'gdpr-cookie-consent-elementor' ) ) );
		}

		$category_id = isset( $_POST['id'] ) ? sanitize_key( $_POST['id'] ) : '';

		$category_manager = $this->get_category_manager();
		$categories = $category_manager->get_categories();

		// Remove category.
		$categories = array_filter(
			$categories,
			function( $cat ) use ( $category_id ) {
				return $cat['id'] !== $category_id;
			}
		);

		$result = $category_manager->save_categories( array_values( $categories ) );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Category deleted.', 'gdpr-cookie-consent-elementor' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to delete category.', 'gdpr-cookie-consent-elementor' ) ) );
		}
	}

	/**
	 * Handle save mapping AJAX.
	 *
	 * @return void
	 */
	public function handle_save_mapping_ajax() {
		check_ajax_referer( 'gdpr_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'gdpr-cookie-consent-elementor' ) ) );
		}

		$mapping = array(
			'pattern'  => isset( $_POST['pattern'] ) ? sanitize_text_field( wp_unslash( $_POST['pattern'] ) ) : '',
			'domain'   => isset( $_POST['domain'] ) ? sanitize_text_field( wp_unslash( $_POST['domain'] ) ) : '',
			'path'     => isset( $_POST['path'] ) ? sanitize_text_field( wp_unslash( $_POST['path'] ) ) : '',
			'category' => isset( $_POST['category'] ) ? sanitize_key( $_POST['category'] ) : '',
			'priority' => isset( $_POST['priority'] ) ? absint( $_POST['priority'] ) : 10,
		);

		$category_manager = $this->get_category_manager();
		$mappings = $category_manager->get_cookie_mappings();

		$index = isset( $_POST['index'] ) ? absint( $_POST['index'] ) : -1;
		if ( $index >= 0 && isset( $mappings[ $index ] ) ) {
			$mappings[ $index ] = $mapping;
		} else {
			$mappings[] = $mapping;
		}

		update_option( Cookie_Category_Manager::OPTION_MAPPINGS, $mappings );
		$category_manager->clear_mappings_cache();

		wp_send_json_success( array( 'message' => __( 'Mapping saved.', 'gdpr-cookie-consent-elementor' ) ) );
	}

	/**
	 * Handle delete mapping AJAX.
	 *
	 * @return void
	 */
	public function handle_delete_mapping_ajax() {
		check_ajax_referer( 'gdpr_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'gdpr-cookie-consent-elementor' ) ) );
		}

		$index = isset( $_POST['index'] ) ? absint( $_POST['index'] ) : -1;

		$category_manager = $this->get_category_manager();
		$mappings = $category_manager->get_cookie_mappings();

		if ( isset( $mappings[ $index ] ) ) {
			unset( $mappings[ $index ] );
			update_option( Cookie_Category_Manager::OPTION_MAPPINGS, array_values( $mappings ) );
			$category_manager->clear_mappings_cache();
			wp_send_json_success( array( 'message' => __( 'Mapping deleted.', 'gdpr-cookie-consent-elementor' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Mapping not found.', 'gdpr-cookie-consent-elementor' ) ) );
		}
	}

	/**
	 * Handle assign category to cookie AJAX.
	 *
	 * @return void
	 */
	public function handle_assign_category_ajax() {
		check_ajax_referer( 'gdpr_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'gdpr-cookie-consent-elementor' ) ) );
		}

		$cookie_key = isset( $_POST['cookie_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cookie_key'] ) ) : '';
		$category_id = isset( $_POST['category_id'] ) ? sanitize_key( $_POST['category_id'] ) : '';

		$cookie_detector = $this->get_cookie_detector();
		$detected = $cookie_detector->get_detected_cookies();

		if ( isset( $detected[ $cookie_key ] ) ) {
			$detected[ $cookie_key ]['assigned_category'] = $category_id ?: null;
			update_option( Cookie_Detector::OPTION_DETECTED, $detected );

			// Learn from assignment.
			if ( $category_id ) {
				$pattern_learner = $this->get_pattern_learner();
				if ( $pattern_learner ) {
					$pattern_learner->learn_from_assignment( $detected[ $cookie_key ]['name'], $category_id );
				}
			}

			do_action( 'gdpr_category_assigned', $cookie_key, $category_id, false );

			wp_send_json_success( array( 'message' => __( 'Category assigned.', 'gdpr-cookie-consent-elementor' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Cookie not found.', 'gdpr-cookie-consent-elementor' ) ) );
		}
	}

	/**
	 * Get category manager instance.
	 *
	 * @return Cookie_Category_Manager
	 */
	private function get_category_manager() {
		if ( null === $this->category_manager ) {
			$this->category_manager = new Cookie_Category_Manager();
		}
		return $this->category_manager;
	}

	/**
	 * Get cookie detector instance.
	 *
	 * @return Cookie_Detector
	 */
	private function get_cookie_detector() {
		if ( null === $this->cookie_detector ) {
			$this->cookie_detector = new Cookie_Detector();
		}
		return $this->cookie_detector;
	}

	/**
	 * Get pattern learner instance.
	 *
	 * @return Cookie_Pattern_Learner|null
	 */
	private function get_pattern_learner() {
		if ( null === $this->pattern_learner && class_exists( __NAMESPACE__ . '\Cookie_Pattern_Learner' ) ) {
			$this->pattern_learner = new Cookie_Pattern_Learner();
		}
		return $this->pattern_learner;
	}

	/**
	 * Handle test detection AJAX request.
	 *
	 * @return void
	 */
	public function handle_test_detection_ajax() {
		check_ajax_referer( 'gdpr_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'gdpr-cookie-consent-elementor' ) ) );
		}

		$cookie_detector = $this->get_cookie_detector();

		// Manually detect a test cookie.
		$cookie_detector->detect_cookie( 'gdpr_test_cookie', '', '/', 'test' );

		wp_send_json_success( array( 'message' => __( 'Test cookie detected and logged.', 'gdpr-cookie-consent-elementor' ) ) );
	}
}

