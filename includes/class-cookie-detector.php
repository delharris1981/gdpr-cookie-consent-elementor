<?php
/**
 * Cookie Detector
 *
 * Automatically detects cookies set via WordPress functions and JavaScript.
 *
 * @package GDPR_Cookie_Consent_Elementor
 */

namespace GDPR_Cookie_Consent_Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Cookie Detector Class.
 *
 * @since 1.2.0
 */
class Cookie_Detector {

	/**
	 * Option name for detected cookies.
	 *
	 * @var string
	 */
	const OPTION_DETECTED = 'gdpr_detected_cookies';

	/**
	 * Option name for detection settings.
	 *
	 * @var string
	 */
	const OPTION_SETTINGS = 'gdpr_detection_settings';

	/**
	 * Detected cookies buffer (for batching).
	 *
	 * @var array
	 */
	private $detection_buffer = array();

	/**
	 * Category manager instance.
	 *
	 * @var Cookie_Category_Manager
	 */
	private $category_manager = null;

	/**
	 * Pattern learner instance.
	 *
	 * @var Cookie_Pattern_Learner
	 */
	private $pattern_learner = null;

	/**
	 * Initialize cookie detector.
	 *
	 * @return void
	 */
	public function __construct() {
		// Hook into WordPress cookie setting EARLY (before blocker removes them).
		// Use priority 998 so we detect before blocker removes at 999.
		add_filter( 'wp_headers', array( $this, 'detect_cookies_from_headers' ), 998, 2 );
		add_action( 'send_headers', array( $this, 'detect_cookies_from_send_headers' ), 998 );

		// Also hook into setcookie directly if possible.
		add_action( 'init', array( $this, 'hook_setcookie' ), 0 );

		// Register AJAX handlers.
		add_action( 'wp_ajax_gdpr_log_detected_cookie', array( $this, 'handle_log_cookie_ajax' ) );
		add_action( 'wp_ajax_nopriv_gdpr_log_detected_cookie', array( $this, 'handle_log_cookie_ajax' ) );

		// Enqueue detector script with nonce.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_detector_script' ), 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_detector_script' ), 1 );

		// Cleanup old detections periodically.
		add_action( 'gdpr_cleanup_detections', array( $this, 'cleanup_old_detections' ) );
		if ( ! wp_next_scheduled( 'gdpr_cleanup_detections' ) ) {
			wp_schedule_event( time(), 'daily', 'gdpr_cleanup_detections' );
		}
	}

	/**
	 * Detect cookies from wp_headers filter.
	 * This runs at priority 998, BEFORE the cookie blocker removes headers at 999.
	 *
	 * @param array  $headers Headers array.
	 * @param object $wp      WordPress environment instance.
	 * @return array Headers array (unchanged - we don't modify, just detect).
	 */
	public function detect_cookies_from_headers( $headers, $wp ) {
		// Detect cookies but don't modify headers - let blocker handle that.
		if ( isset( $headers['Set-Cookie'] ) ) {
			$cookies = is_array( $headers['Set-Cookie'] ) ? $headers['Set-Cookie'] : array( $headers['Set-Cookie'] );
			foreach ( $cookies as $cookie_string ) {
				if ( is_string( $cookie_string ) ) {
					$this->parse_and_detect_cookie( $cookie_string, 'wordpress_function' );
				}
			}
		}

		// Also check for array keys starting with Set-Cookie.
		foreach ( $headers as $key => $value ) {
			if ( stripos( $key, 'Set-Cookie' ) === 0 && $key !== 'Set-Cookie' ) {
				$cookies = is_array( $value ) ? $value : array( $value );
				foreach ( $cookies as $cookie_string ) {
					if ( is_string( $cookie_string ) ) {
						$this->parse_and_detect_cookie( $cookie_string, 'wordpress_function' );
					}
				}
			}
		}

		return $headers; // Return unchanged - we only detect, not modify.
	}

	/**
	 * Detect cookies from send_headers action.
	 *
	 * @return void
	 */
	public function detect_cookies_from_send_headers() {
		// Check if headers are already sent - if so, we can't use headers_list().
		if ( headers_sent() ) {
			return;
		}

		$headers = headers_list();
		foreach ( $headers as $header ) {
			if ( stripos( $header, 'Set-Cookie:' ) === 0 ) {
				$cookie_string = substr( $header, 11 ); // Remove "Set-Cookie: " prefix.
				$this->parse_and_detect_cookie( trim( $cookie_string ), 'wordpress_function' );
			}
		}
	}

	/**
	 * Hook into setcookie function.
	 * This is a workaround to detect cookies set via native PHP setcookie().
	 *
	 * @return void
	 */
	public function hook_setcookie() {
		// Note: We can't actually override setcookie() in PHP, but we can detect
		// cookies via the headers filter which runs when setcookie() is called.
		// This method is here for future extensibility if needed.
		
		// Also hook into output buffering to catch cookies in output.
		if ( ! headers_sent() ) {
			add_action( 'template_redirect', array( $this, 'start_detection_buffer' ), 0 );
		}
	}

	/**
	 * Start output buffering to catch cookies in headers.
	 *
	 * @return void
	 */
	public function start_detection_buffer() {
		// This is a backup method - primary detection happens via wp_headers filter.
	}

	/**
	 * Parse cookie string and detect cookie.
	 *
	 * @param string $cookie_string Cookie string.
	 * @param string $source        Detection source.
	 * @return void
	 */
	private function parse_and_detect_cookie( $cookie_string, $source ) {
		// Parse cookie string (format: name=value; path=/; domain=example.com; expires=...).
		$parts = explode( ';', $cookie_string );
		$name_value = trim( array_shift( $parts ) );
		$name_value_parts = explode( '=', $name_value, 2 );
		$name = isset( $name_value_parts[0] ) ? trim( $name_value_parts[0] ) : '';

		if ( empty( $name ) ) {
			return;
		}

		$domain = '';
		$path = '';

		// Parse attributes.
		foreach ( $parts as $part ) {
			$part = trim( $part );
			if ( stripos( $part, 'domain=' ) === 0 ) {
				$domain = trim( substr( $part, 7 ) );
			} elseif ( stripos( $part, 'path=' ) === 0 ) {
				$path = trim( substr( $part, 5 ) );
			}
		}

		$this->detect_cookie( $name, $domain, $path, $source );
	}

	/**
	 * Detect and log a cookie.
	 *
	 * @param string $name   Cookie name.
	 * @param string $domain Cookie domain.
	 * @param string $path   Cookie path.
	 * @param string $source Detection source.
	 * @return void
	 */
	public function detect_cookie( $name, $domain = '', $path = '', $source = 'unknown' ) {
		// Sanitize inputs.
		$name = sanitize_text_field( $name );
		$domain = sanitize_text_field( $domain );
		$path = sanitize_text_field( $path );
		$source = sanitize_text_field( $source );

		// Apply filter to allow disabling detection for specific cookies.
		if ( ! apply_filters( 'gdpr_cookie_detection_enabled', true, $name, $domain, $path ) ) {
			return;
		}

		// Get detected cookies.
		$detected = $this->get_detected_cookies();

		// Create unique key for this cookie.
		$cookie_key = md5( $name . '|' . $domain . '|' . $path );

		// Check if already detected.
		if ( isset( $detected[ $cookie_key ] ) ) {
			// Update detection count and timestamp.
			$detected[ $cookie_key ]['detection_count'] = isset( $detected[ $cookie_key ]['detection_count'] ) ? $detected[ $cookie_key ]['detection_count'] + 1 : 1;
			$detected[ $cookie_key ]['last_detected'] = current_time( 'mysql' );
		} else {
			// New detection.
			$category_manager = $this->get_category_manager();
			$suggested_category = $this->suggest_category( $name, $domain, $path );

			$detected[ $cookie_key ] = array(
				'name'            => $name,
				'domain'          => $domain,
				'path'            => $path,
				'source'          => $source,
				'suggested_category' => $suggested_category,
				'assigned_category'  => null,
				'detected_at'     => current_time( 'mysql' ),
				'last_detected'   => current_time( 'mysql' ),
				'detection_count' => 1,
				'plugin_context'  => $this->get_cookie_context( $name ),
			);

			// Apply filter before storing.
			$detected[ $cookie_key ] = apply_filters( 'gdpr_detected_cookie', $detected[ $cookie_key ], $name, $domain, $path, $source );
		}

		// Save detected cookies.
		$result = update_option( self::OPTION_DETECTED, $detected );

		// Trigger action.
		do_action( 'gdpr_cookie_detected', $detected[ $cookie_key ], $name, $domain, $path, $source );

		// Debug: Log detection (only in debug mode).
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( sprintf( 'GDPR Cookie Detected: %s (domain: %s, path: %s, source: %s)', $name, $domain, $path, $source ) );
		}
	}

	/**
	 * Get detected cookies.
	 *
	 * @param array $filters Optional filters (category, source, etc.).
	 * @return array Array of detected cookie objects.
	 */
	public function get_detected_cookies( $filters = array() ) {
		$detected = get_option( self::OPTION_DETECTED, array() );

		// Apply filters.
		if ( ! empty( $filters ) ) {
			$filtered = array();
			foreach ( $detected as $key => $cookie ) {
				$include = true;

				if ( isset( $filters['category'] ) && ! empty( $filters['category'] ) ) {
					$cookie_category = isset( $cookie['assigned_category'] ) ? $cookie['assigned_category'] : $cookie['suggested_category'];
					if ( $cookie_category !== $filters['category'] ) {
						$include = false;
					}
				}

				if ( isset( $filters['source'] ) && ! empty( $filters['source'] ) ) {
					if ( ! isset( $cookie['source'] ) || $cookie['source'] !== $filters['source'] ) {
						$include = false;
					}
				}

				if ( $include ) {
					$filtered[ $key ] = $cookie;
				}
			}
			return $filtered;
		}

		return $detected;
	}

	/**
	 * Suggest category for cookie.
	 *
	 * @param string $name   Cookie name.
	 * @param string $domain Cookie domain.
	 * @param string $path   Cookie path.
	 * @return string|null Suggested category ID or null.
	 */
	public function suggest_category( $name, $domain = '', $path = '' ) {
		$category_manager = $this->get_category_manager();

		// First, check existing mappings.
		$category = $category_manager->get_category_for_cookie( $name, $domain, $path );
		if ( $category ) {
			return $category;
		}

		// Check learned patterns.
		$pattern_learner = $this->get_pattern_learner();
		if ( $pattern_learner ) {
			$learned_category = $pattern_learner->suggest_based_on_learning( $name );
			if ( $learned_category ) {
				return $learned_category;
			}
		}

		// Apply filter for custom suggestions.
		$suggestion = apply_filters( 'gdpr_suggest_category', null, $name, $domain, $path );

		return $suggestion;
	}

	/**
	 * Auto-assign category to cookie if confidence is high.
	 *
	 * @param string $cookie_key Cookie key.
	 * @param string $category_id Category ID.
	 * @return bool True if auto-assigned.
	 */
	public function auto_assign_category( $cookie_key, $category_id ) {
		$settings = $this->get_detection_settings();
		if ( ! isset( $settings['auto_assign'] ) || ! $settings['auto_assign'] ) {
			return false;
		}

		$detected = $this->get_detected_cookies();
		if ( ! isset( $detected[ $cookie_key ] ) ) {
			return false;
		}

		$cookie = $detected[ $cookie_key ];
		$suggested = isset( $cookie['suggested_category'] ) ? $cookie['suggested_category'] : null;

		// Only auto-assign if suggestion matches and confidence is high.
		if ( $suggested === $category_id ) {
			$detected[ $cookie_key ]['assigned_category'] = $category_id;
			update_option( self::OPTION_DETECTED, $detected );

			do_action( 'gdpr_category_assigned', $cookie_key, $category_id, true );
			return true;
		}

		return false;
	}

	/**
	 * Get cookie context (try to identify plugin/theme source).
	 *
	 * @param string $cookie_name Cookie name.
	 * @return string|null Plugin/theme context or null.
	 */
	public function get_cookie_context( $cookie_name ) {
		// Check for common plugin patterns.
		$plugin_patterns = array(
			'woocommerce' => array( 'woocommerce_', 'wc_', 'wp_woocommerce_' ),
			'contact-form-7' => array( 'wpcf7_' ),
			'yoast' => array( '_yoast_' ),
		);

		foreach ( $plugin_patterns as $plugin => $patterns ) {
			foreach ( $patterns as $pattern ) {
				if ( stripos( $cookie_name, $pattern ) === 0 ) {
					return $plugin;
				}
			}
		}

		// Check WordPress core patterns.
		if ( stripos( $cookie_name, 'wordpress' ) === 0 || stripos( $cookie_name, 'wp_' ) === 0 ) {
			return 'wordpress-core';
		}

		return null;
	}

	/**
	 * Clear old detected cookies.
	 *
	 * @param int $older_than_days Days threshold.
	 * @return int Number of cookies cleared.
	 */
	public function clear_detected_cookies( $older_than_days = 90 ) {
		$detected = $this->get_detected_cookies();
		$cutoff = time() - ( $older_than_days * DAY_IN_SECONDS );
		$cleared = 0;

		foreach ( $detected as $key => $cookie ) {
			$detected_at = isset( $cookie['detected_at'] ) ? strtotime( $cookie['detected_at'] ) : 0;
			if ( $detected_at < $cutoff ) {
				unset( $detected[ $key ] );
				$cleared++;
			}
		}

		update_option( self::OPTION_DETECTED, $detected );
		return $cleared;
	}

	/**
	 * Cleanup old detections (scheduled task).
	 *
	 * @return void
	 */
	public function cleanup_old_detections() {
		$this->clear_detected_cookies( 90 );
	}

	/**
	 * Enqueue detector script with nonce.
	 *
	 * @return void
	 */
	public function enqueue_detector_script() {
		// Define assets URL if not already defined.
		if ( ! defined( 'GDPR_CCE_ASSETS_URL' ) ) {
			$assets_url = plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/';
		} else {
			$assets_url = GDPR_CCE_ASSETS_URL;
		}

		// Define version if not already defined.
		if ( ! defined( 'GDPR_CCE_VERSION' ) ) {
			$version = '1.2.0';
		} else {
			$version = GDPR_CCE_VERSION;
		}

		// Enqueue the detector script.
		wp_enqueue_script(
			'gdpr-cookie-detector',
			$assets_url . 'js/gdpr-cookie-detector.js',
			array( 'jquery' ),
			$version,
			false // Load in header for early interception.
		);

		// Create a nonce specifically for cookie detection.
		$detection_nonce = wp_create_nonce( 'gdpr_cookie_detection' );

		// Localize script data.
		wp_localize_script(
			'gdpr-cookie-detector',
			'gdprCookieDetector',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => $detection_nonce,
			)
		);
	}

	/**
	 * Handle AJAX request to log detected cookie from JavaScript.
	 *
	 * @return void
	 */
	public function handle_log_cookie_ajax() {
		// Verify nonce - use detection nonce instead of consent nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'gdpr_cookie_detection' ) ) {
			// Fallback: try consent nonce for backward compatibility.
			if ( ! check_ajax_referer( 'gdpr_consent_nonce', 'nonce', false ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'gdpr-cookie-consent-elementor' ) ) );
				return;
			}
		}

		if ( ! isset( $_POST['cookies'] ) || ! is_array( $_POST['cookies'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No cookies provided.', 'gdpr-cookie-consent-elementor' ) ) );
		}

		$cookies = array_map( 'sanitize_text_field', wp_unslash( $_POST['cookies'] ) );
		$source = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : 'javascript';

		foreach ( $cookies as $cookie_data ) {
			// Parse cookie data (format: name=value; domain=...; path=...).
			$parts = explode( ';', $cookie_data );
			$name_value = trim( array_shift( $parts ) );
			$name_value_parts = explode( '=', $name_value, 2 );
			$name = isset( $name_value_parts[0] ) ? trim( $name_value_parts[0] ) : '';

			if ( empty( $name ) ) {
				continue;
			}

			$domain = '';
			$path = '';

			foreach ( $parts as $part ) {
				$part = trim( $part );
				if ( stripos( $part, 'domain=' ) === 0 ) {
					$domain = trim( substr( $part, 7 ) );
				} elseif ( stripos( $part, 'path=' ) === 0 ) {
					$path = trim( substr( $part, 5 ) );
				}
			}

			$this->detect_cookie( $name, $domain, $path, $source );
		}

		wp_send_json_success( array( 'message' => __( 'Cookies logged.', 'gdpr-cookie-consent-elementor' ) ) );
	}

	/**
	 * Get detection settings.
	 *
	 * @return array Settings array.
	 */
	public function get_detection_settings() {
		$defaults = array(
			'auto_assign'        => false,
			'confidence_threshold' => 0.8,
		);
		$settings = get_option( self::OPTION_SETTINGS, $defaults );
		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Save detection settings.
	 *
	 * @param array $settings Settings array.
	 * @return bool True on success.
	 */
	public function save_detection_settings( $settings ) {
		$sanitized = array(
			'auto_assign'        => isset( $settings['auto_assign'] ) ? (bool) $settings['auto_assign'] : false,
			'confidence_threshold' => isset( $settings['confidence_threshold'] ) ? floatval( $settings['confidence_threshold'] ) : 0.8,
		);
		return update_option( self::OPTION_SETTINGS, $sanitized );
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
}

