<?php
/**
 * PHP Cookie Blocker
 *
 * Blocks server-side cookie setting when user has declined consent.
 * Comprehensive blocking for WordPress core and plugin cookies.
 *
 * @package GDPR_Cookie_Consent_Elementor
 */

namespace GDPR_Cookie_Consent_Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Cookie Blocker Class.
 *
 * @since 1.0.0
 */
class Cookie_Blocker {

	/**
	 * Transient key for storing preference.
	 *
	 * @var string
	 */
	const TRANSIENT_KEY = 'gdpr_consent_preference_';

	/**
	 * Preference checking mode: 'hybrid' or 'php_only'.
	 *
	 * @var string
	 */
	const PREFERENCE_MODE = 'hybrid';

	/**
	 * Output buffer active flag.
	 *
	 * @var bool
	 */
	private $output_buffer_active = false;

	/**
	 * Preference cache to avoid repeated checks.
	 *
	 * @var string|null
	 */
	private $preference_cache = null;

	/**
	 * Category manager instance.
	 *
	 * @var Cookie_Category_Manager|null
	 */
	private $category_manager = null;

	/**
	 * Initialize cookie blocker.
	 *
	 * @return void
	 */
	public function __construct() {
		// Start output buffering early to intercept headers.
		add_action( 'init', array( $this, 'start_output_buffering' ), 0 );

		// Hook into WordPress cookie filters and actions.
		add_filter( 'send_auth_cookies', array( $this, 'maybe_block_auth_cookies' ), 10, 5 );
		add_action( 'set_auth_cookie', array( $this, 'maybe_prevent_auth_cookie' ), 0, 6 );
		add_action( 'clear_auth_cookie', array( $this, 'maybe_prevent_clear_auth_cookie' ), 0 );

		// Intercept WordPress cookie setting via setcookie().
		add_filter( 'wp_headers', array( $this, 'filter_http_headers' ), 999, 2 );

		// Block comment cookies.
		add_filter( 'comment_cookie_lifetime', array( $this, 'maybe_block_comment_cookies' ), 10, 2 );

		// Block settings cookies.
		add_filter( 'wp_set_current_user', array( $this, 'maybe_prevent_user_setting' ), 10, 2 );

		// Register AJAX handlers.
		add_action( 'wp_ajax_gdpr_consent_accept', array( $this, 'handle_accept_ajax' ) );
		add_action( 'wp_ajax_gdpr_consent_decline', array( $this, 'handle_decline_ajax' ) );
		add_action( 'wp_ajax_nopriv_gdpr_consent_accept', array( $this, 'handle_accept_ajax' ) );
		add_action( 'wp_ajax_nopriv_gdpr_consent_decline', array( $this, 'handle_decline_ajax' ) );

		// AJAX endpoint to read preference from JavaScript sessionStorage.
		add_action( 'wp_ajax_gdpr_get_preference', array( $this, 'handle_get_preference_ajax' ) );
		add_action( 'wp_ajax_nopriv_gdpr_get_preference', array( $this, 'handle_get_preference_ajax' ) );

		// AJAX endpoint for category preferences.
		add_action( 'wp_ajax_gdpr_save_category_preferences', array( $this, 'handle_save_category_preferences_ajax' ) );
		add_action( 'wp_ajax_nopriv_gdpr_save_category_preferences', array( $this, 'handle_save_category_preferences_ajax' ) );

		// Remove Set-Cookie headers before output is sent.
		add_action( 'send_headers', array( $this, 'remove_set_cookie_headers' ), 999 );
		
		// Clean up output buffer on shutdown.
		add_action( 'shutdown', array( $this, 'cleanup_output_buffer' ), 999 );
	}

	/**
	 * Start output buffering to intercept Set-Cookie headers.
	 *
	 * @return void
	 */
	public function start_output_buffering() {
		if ( ! headers_sent() && ! $this->output_buffer_active ) {
			ob_start();
			$this->output_buffer_active = true;
		}
	}

	/**
	 * Filter output buffer to remove Set-Cookie headers.
	 *
	 * @param string $buffer Output buffer content.
	 * @return string Filtered buffer.
	 */
	public function filter_output_buffer( $buffer ) {
		// Headers are already sent at this point, so we can't modify them.
		// This callback is mainly for processing output if needed.
		return $buffer;
	}

	/**
	 * Remove Set-Cookie headers before they're sent.
	 * Called on send_headers action.
	 *
	 * @return void
	 */
	public function remove_set_cookie_headers() {
		if ( ! headers_sent() ) {
			$headers = headers_list();
			$category_manager = $this->get_category_manager();
			$settings = $category_manager->get_settings();

			// Check if we're in category mode.
			if ( isset( $settings['mode'] ) && 'categories' === $settings['mode'] ) {
				// Category mode: check each cookie individually.
				foreach ( $headers as $header ) {
					if ( stripos( $header, 'Set-Cookie:' ) === 0 ) {
						$cookie_string = substr( $header, 11 );
						$cookie_parts = $this->parse_cookie_string( $cookie_string );
						if ( $cookie_parts && $category_manager->should_block_cookie( $cookie_parts['name'], $cookie_parts['domain'], $cookie_parts['path'] ) ) {
							// This cookie should be blocked - remove it.
							// Note: We can't selectively remove headers, so we'll handle this in filter_http_headers.
						}
					}
				}
			} else {
				// Simple mode: block all if declined.
				if ( $this->should_block_cookies() ) {
					header_remove( 'Set-Cookie' );
				}
			}
		}
	}

	/**
	 * Clean up output buffer.
	 *
	 * @return void
	 */
	public function cleanup_output_buffer() {
		if ( $this->output_buffer_active && ob_get_level() > 0 ) {
			ob_end_flush();
			$this->output_buffer_active = false;
		}
	}

	/**
	 * Filter HTTP headers to remove Set-Cookie headers.
	 * Note: This filter may not exist in all WordPress versions.
	 * IMPORTANT: Cookie detection happens at priority 998, so cookies are detected before being removed here.
	 *
	 * @param array  $headers Headers array.
	 * @param object $wp      WordPress environment instance.
	 * @return array Filtered headers.
	 */
	public function filter_http_headers( $headers, $wp ) {
		// Allow cookie detector to run first (it hooks at 998, we're at 999).
		// This ensures cookies are detected before being blocked.
		
		$category_manager = $this->get_category_manager();
		$settings = $category_manager->get_settings();

		// Check if we're in category mode.
		if ( isset( $settings['mode'] ) && 'categories' === $settings['mode'] ) {
			// Category mode: check each cookie individually.
			if ( isset( $headers['Set-Cookie'] ) ) {
				$cookies = is_array( $headers['Set-Cookie'] ) ? $headers['Set-Cookie'] : array( $headers['Set-Cookie'] );
				$filtered_cookies = array();

				foreach ( $cookies as $cookie_string ) {
					$cookie_parts = $this->parse_cookie_string( $cookie_string );
					if ( $cookie_parts && ! $category_manager->should_block_cookie( $cookie_parts['name'], $cookie_parts['domain'], $cookie_parts['path'] ) ) {
						$filtered_cookies[] = $cookie_string;
					}
				}

				if ( empty( $filtered_cookies ) ) {
					unset( $headers['Set-Cookie'] );
				} else {
					$headers['Set-Cookie'] = count( $filtered_cookies ) === 1 ? $filtered_cookies[0] : $filtered_cookies;
				}
			}

			// Also check for array keys starting with Set-Cookie.
			foreach ( $headers as $key => $value ) {
				if ( stripos( $key, 'Set-Cookie' ) === 0 && $key !== 'Set-Cookie' ) {
					$cookies = is_array( $value ) ? $value : array( $value );
					$filtered_cookies = array();

					foreach ( $cookies as $cookie_string ) {
						$cookie_parts = $this->parse_cookie_string( $cookie_string );
						if ( $cookie_parts && ! $category_manager->should_block_cookie( $cookie_parts['name'], $cookie_parts['domain'], $cookie_parts['path'] ) ) {
							$filtered_cookies[] = $cookie_string;
						}
					}

					if ( empty( $filtered_cookies ) ) {
						unset( $headers[ $key ] );
					} else {
						$headers[ $key ] = count( $filtered_cookies ) === 1 ? $filtered_cookies[0] : $filtered_cookies;
					}
				}
			}
		} else {
			// Simple mode: block all if declined.
			if ( $this->should_block_cookies() ) {
				// Remove Set-Cookie headers from array.
				if ( isset( $headers['Set-Cookie'] ) ) {
					unset( $headers['Set-Cookie'] );
				}
				// Also check for array of Set-Cookie headers.
				foreach ( $headers as $key => $value ) {
					if ( stripos( $key, 'Set-Cookie' ) === 0 ) {
						unset( $headers[ $key ] );
					}
				}
			}
		}

		return $headers;
	}

	/**
	 * Check if cookies should be blocked for current session.
	 * Uses hybrid mode: checks PHP transient first, then falls back to AJAX check of sessionStorage.
	 * In category mode, this checks if all non-essential categories are declined.
	 *
	 * @return bool True if cookies should be blocked.
	 */
	private function should_block_cookies() {
		$category_manager = $this->get_category_manager();
		$settings = $category_manager->get_settings();

		// #region agent log
		$log_data = array(
			'settings_mode' => isset( $settings['mode'] ) ? $settings['mode'] : 'not_set',
			'location' => 'class-cookie-blocker.php:256',
		);
		error_log( json_encode( array(
			'id' => 'log_' . time() . '_php',
			'timestamp' => time() * 1000,
			'location' => 'class-cookie-blocker.php:256',
			'message' => 'should_block_cookies called',
			'data' => $log_data,
			'sessionId' => 'debug-session',
			'runId' => 'run1',
			'hypothesisId' => 'A',
		) ) . "\n", 3, '/Users/derek/Local Sites/test/app/public/.cursor/debug.log' );
		// #endregion

		// Check if we're in category mode.
		if ( isset( $settings['mode'] ) && 'categories' === $settings['mode'] ) {
			// In category mode, we don't block all cookies - each cookie is checked individually.
			// This method is only used for simple mode backward compatibility.
			return false;
		}

		// Simple mode: check old preference.
		$preference = $this->get_preference_from_storage();

		// #region agent log
		$log_data = array(
			'preference' => $preference,
			'should_block' => $preference === 'declined',
			'location' => 'class-cookie-blocker.php:270',
		);
		error_log( json_encode( array(
			'id' => 'log_' . time() . '_php',
			'timestamp' => time() * 1000,
			'location' => 'class-cookie-blocker.php:270',
			'message' => 'should_block_cookies result',
			'data' => $log_data,
			'sessionId' => 'debug-session',
			'runId' => 'run1',
			'hypothesisId' => 'C',
		) ) . "\n", 3, '/Users/derek/Local Sites/test/app/public/.cursor/debug.log' );
		// #endregion

		return $preference === 'declined';
	}

	/**
	 * Get preference from storage (hybrid mode).
	 * Can be overridden for PHP-only mode.
	 *
	 * @return string|null Preference value ('accepted', 'declined', or null).
	 */
	protected function get_preference_from_storage() {
		// Use cache if available.
		if ( $this->preference_cache !== null ) {
			return $this->preference_cache;
		}

		// Primary method: Check PHP transient.
		$session_id = $this->get_session_id();
		$preference = get_transient( self::TRANSIENT_KEY . $session_id );

		// If no preference in PHP and in hybrid mode, check if we can get from JavaScript.
		if ( empty( $preference ) && self::PREFERENCE_MODE === 'hybrid' ) {
			// In hybrid mode, we'll check sessionStorage via AJAX when needed.
			// For now, return null (no preference set).
			// The AJAX endpoint can be called separately if needed.
		}

		// Cache the result.
		$this->preference_cache = $preference;

		return $preference;
	}

	/**
	 * Get session identifier.
	 * Can be overridden for custom session ID generation.
	 *
	 * @return string Session identifier.
	 */
	protected function get_session_id() {
		// Use a combination of IP and User Agent with a salt for session identification.
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		$salt = defined( 'AUTH_SALT' ) ? AUTH_SALT : 'gdpr-consent-salt';

		// Use wp_hash for better security and to avoid predictable identifiers.
		return wp_hash( $ip . $ua . $salt, 'gdpr_consent' );
	}

	/**
	 * Maybe block authentication cookies.
	 *
	 * @param bool   $send       Whether to send auth cookies.
	 * @param int    $expire     Cookie expiration time.
	 * @param int    $expiration Cookie expiration timestamp.
	 * @param int    $user_id    User ID.
	 * @param string $scheme     Authentication scheme.
	 * @return bool Whether to send auth cookies.
	 */
	public function maybe_block_auth_cookies( $send, $expire, $expiration, $user_id, $scheme ) {
		if ( $this->should_block_cookies() ) {
			return false;
		}

		return $send;
	}

	/**
	 * Prevent authentication cookie from being set.
	 *
	 * @param string $auth_cookie Authentication cookie value.
	 * @param int    $expire     The time the login grace period expires as a UNIX timestamp.
	 * @param int    $expiration The time when the authentication cookie expires as a UNIX timestamp.
	 * @param int    $user_id    User ID.
	 * @param string $scheme     Authentication scheme.
	 * @param string $token      User's session token.
	 * @return void
	 */
	public function maybe_prevent_auth_cookie( $auth_cookie, $expire, $expiration, $user_id, $scheme, $token ) {
		if ( $this->should_block_cookies() ) {
			// Prevent cookie from being set by removing the action.
			remove_action( 'set_auth_cookie', 'wp_set_auth_cookie', 10 );
		}
	}

	/**
	 * Prevent auth cookie clearing when blocking is active.
	 *
	 * @return void
	 */
	public function maybe_prevent_clear_auth_cookie() {
		if ( $this->should_block_cookies() ) {
			// Prevent cookie clearing when blocking is active.
			remove_action( 'clear_auth_cookie', 'wp_clear_auth_cookie', 10 );
		}
	}

	/**
	 * Maybe block comment cookies.
	 *
	 * @param int $expiration Cookie expiration time.
	 * @param int $comment_id Comment ID.
	 * @return int Cookie expiration time (0 to block).
	 */
	public function maybe_block_comment_cookies( $expiration, $comment_id ) {
		if ( $this->should_block_cookies() ) {
			return 0; // Return 0 to prevent cookie from being set.
		}

		return $expiration;
	}

	/**
	 * Maybe prevent user from being set when cookies are blocked.
	 *
	 * @param int    $user_id User ID.
	 * @param string $name    User name.
	 * @return int|false User ID or false to prevent setting.
	 */
	public function maybe_prevent_user_setting( $user_id, $name ) {
		// Don't block user setting, just cookies.
		// This is here for extensibility if needed.
		return $user_id;
	}

	/**
	 * Check if a cookie should be blocked based on name and context.
	 * Extensible method for custom cookie blocking rules.
	 * Now uses category manager for category-based blocking.
	 *
	 * @param string $name   Cookie name.
	 * @param string $value  Cookie value.
	 * @param int    $expire Cookie expiration.
	 * @param string $path   Cookie path.
	 * @param string $domain Cookie domain.
	 * @return bool True if cookie should be blocked.
	 */
	protected function should_block_cookie( $name, $value = '', $expire = 0, $path = '', $domain = '' ) {
		$category_manager = $this->get_category_manager();
		$settings = $category_manager->get_settings();

		// Check if we're in category mode.
		if ( isset( $settings['mode'] ) && 'categories' === $settings['mode'] ) {
			// Use category manager to check if this cookie should be blocked.
			return $category_manager->should_block_cookie( $name, $domain, $path );
		}

		// Simple mode: use old logic.
		if ( ! $this->should_block_cookies() ) {
			return false;
		}

		// Block cookies matching WordPress patterns.
		$wordpress_cookie_patterns = array(
			'comment_author_',
			'comment_author_email_',
			'comment_author_url_',
			'wp-settings-',
			'wp-settings-time-',
		);

		foreach ( $wordpress_cookie_patterns as $pattern ) {
			if ( strpos( $name, $pattern ) === 0 ) {
				return true;
			}
		}

		// Block cookies using WordPress cookie paths/domains.
		$wp_paths = array( COOKIEPATH, SITECOOKIEPATH, ADMIN_COOKIE_PATH, PLUGINS_COOKIE_PATH );
		$wp_domains = array( COOKIE_DOMAIN );

		if ( in_array( $path, $wp_paths, true ) || in_array( $domain, $wp_domains, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Handle accept AJAX request.
	 *
	 * @return void
	 */
	public function handle_accept_ajax() {
		// Verify nonce.
		check_ajax_referer( 'gdpr_consent_nonce', 'nonce' );

		$session_id = $this->get_session_id();
		// Store preference for 24 hours (session-like duration).
		set_transient( self::TRANSIENT_KEY . $session_id, 'accepted', DAY_IN_SECONDS );

		// Clear cache.
		$this->preference_cache = 'accepted';

		wp_send_json_success( array( 'message' => esc_html__( 'Preference saved.', 'gdpr-cookie-consent-elementor' ) ) );
	}

	/**
	 * Handle decline AJAX request.
	 *
	 * @return void
	 */
	public function handle_decline_ajax() {
		// Verify nonce.
		check_ajax_referer( 'gdpr_consent_nonce', 'nonce' );

		$session_id = $this->get_session_id();
		// Store preference for 24 hours (session-like duration).
		set_transient( self::TRANSIENT_KEY . $session_id, 'declined', DAY_IN_SECONDS );

		// Clear cache.
		$this->preference_cache = 'declined';

		wp_send_json_success( array( 'message' => esc_html__( 'Preference saved.', 'gdpr-cookie-consent-elementor' ) ) );
	}

	/**
	 * Handle get preference AJAX request (for hybrid mode).
	 * This allows PHP to check JavaScript sessionStorage preference.
	 *
	 * @return void
	 */
	public function handle_get_preference_ajax() {
		// Verify nonce.
		check_ajax_referer( 'gdpr_consent_nonce', 'nonce' );

		// This endpoint is called by JavaScript to sync preference.
		// The actual preference is read from sessionStorage in JavaScript,
		// then sent to this endpoint to store in PHP transient.
		if ( ! isset( $_POST['preference'] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'No preference provided.', 'gdpr-cookie-consent-elementor' ) ) );
		}

		$preference = sanitize_text_field( wp_unslash( $_POST['preference'] ) );
		if ( ! in_array( $preference, array( 'accepted', 'declined' ), true ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid preference.', 'gdpr-cookie-consent-elementor' ) ) );
		}

		$session_id = $this->get_session_id();
		set_transient( self::TRANSIENT_KEY . $session_id, $preference, DAY_IN_SECONDS );

		// Clear cache.
		$this->preference_cache = $preference;

		wp_send_json_success(
			array(
				'message'    => esc_html__( 'Preference synced.', 'gdpr-cookie-consent-elementor' ),
				'preference' => $preference,
			)
		);
	}

	/**
	 * Handle save category preferences AJAX request.
	 *
	 * @return void
	 */
	public function handle_save_category_preferences_ajax() {
		// Verify nonce.
		check_ajax_referer( 'gdpr_consent_nonce', 'nonce' );

		if ( ! isset( $_POST['preferences'] ) || ! is_array( $_POST['preferences'] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'No preferences provided.', 'gdpr-cookie-consent-elementor' ) ) );
		}

		$preferences = array_map( 'boolval', wp_unslash( $_POST['preferences'] ) );

		// #region agent log
		$log_data = array(
			'preferences' => $preferences,
			'preferences_count' => count( $preferences ),
			'location' => 'class-cookie-blocker.php:570',
		);
		error_log( json_encode( array(
			'id' => 'log_' . time() . '_php',
			'timestamp' => time() * 1000,
			'location' => 'class-cookie-blocker.php:570',
			'message' => 'handle_save_category_preferences_ajax called',
			'data' => $log_data,
			'sessionId' => 'debug-session',
			'runId' => 'run2',
			'hypothesisId' => 'B',
		) ) . "\n", 3, '/Users/derek/Local Sites/test/app/public/.cursor/debug.log' );
		// #endregion

		$category_manager = $this->get_category_manager();
		
		// Ensure mode is set to 'categories' if we're saving category preferences.
		$settings = $category_manager->get_settings();
		if ( ! isset( $settings['mode'] ) || 'categories' !== $settings['mode'] ) {
			$settings['mode'] = 'categories';
			$category_manager->save_settings( $settings );
		}
		
		$result = $category_manager->save_user_preferences( $preferences );

		// #region agent log
		$log_data = array(
			'result' => $result,
			'saved_preferences' => $category_manager->get_user_preferences(),
			'location' => 'class-cookie-blocker.php:590',
		);
		error_log( json_encode( array(
			'id' => 'log_' . time() . '_php',
			'timestamp' => time() * 1000,
			'location' => 'class-cookie-blocker.php:590',
			'message' => 'Preferences save result',
			'data' => $log_data,
			'sessionId' => 'debug-session',
			'runId' => 'run2',
			'hypothesisId' => 'B',
		) ) . "\n", 3, '/Users/derek/Local Sites/test/app/public/.cursor/debug.log' );
		// #endregion

		if ( $result ) {
			wp_send_json_success( array( 'message' => esc_html__( 'Preferences saved.', 'gdpr-cookie-consent-elementor' ) ) );
		} else {
			wp_send_json_error( array( 'message' => esc_html__( 'Failed to save preferences.', 'gdpr-cookie-consent-elementor' ) ) );
		}
	}

	/**
	 * Parse cookie string to extract name, domain, and path.
	 *
	 * @param string $cookie_string Cookie string.
	 * @return array|null Parsed cookie data or null on failure.
	 */
	private function parse_cookie_string( $cookie_string ) {
		$parts = explode( ';', trim( $cookie_string ) );
		$name_value = trim( array_shift( $parts ) );
		$name_value_parts = explode( '=', $name_value, 2 );
		$name = isset( $name_value_parts[0] ) ? trim( $name_value_parts[0] ) : '';

		if ( empty( $name ) ) {
			return null;
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

		return array(
			'name'   => $name,
			'domain' => $domain,
			'path'   => $path,
		);
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
	 * Clear preference cache.
	 * Useful when preference changes.
	 *
	 * @return void
	 */
	public function clear_preference_cache() {
		$this->preference_cache = null;
	}
}
