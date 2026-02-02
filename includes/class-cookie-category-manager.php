<?php
/**
 * Cookie Category Manager
 *
 * Manages cookie categories, mappings, and user preferences.
 *
 * @package GDPR_Cookie_Consent_Elementor
 */

namespace GDPR_Cookie_Consent_Elementor;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Cookie Category Manager Class.
 *
 * @since 1.2.0
 */
class Cookie_Category_Manager
{

	/**
	 * Option name for categories.
	 *
	 * @var string
	 */
	const OPTION_CATEGORIES = 'gdpr_cookie_categories';

	/**
	 * Option name for mappings.
	 *
	 * @var string
	 */
	const OPTION_MAPPINGS = 'gdpr_cookie_mappings';

	/**
	 * Option name for settings.
	 *
	 * @var string
	 */
	const OPTION_SETTINGS = 'gdpr_category_settings';

	/**
	 * Transient key prefix for categories cache.
	 *
	 * @var string
	 */
	const CACHE_KEY_CATEGORIES = 'gdpr_categories_cache_';

	/**
	 * Transient key prefix for mappings cache.
	 *
	 * @var string
	 */
	const CACHE_KEY_MAPPINGS = 'gdpr_mappings_cache_';

	/**
	 * Cache duration in seconds (1 hour).
	 *
	 * @var int
	 */
	const CACHE_DURATION = HOUR_IN_SECONDS;

	/**
	 * Get all categories.
	 *
	 * @param bool $use_cache Whether to use cache.
	 * @return array Array of category objects.
	 */
	public function get_categories($use_cache = true)
	{
		$cache_key = self::CACHE_KEY_CATEGORIES . get_current_blog_id();

		if ($use_cache) {
			$cached = get_transient($cache_key);
			if (false !== $cached) {
				$categories = apply_filters('gdpr_cookie_categories', $cached);
				return $categories;
			}
		}

		$categories = get_option(self::OPTION_CATEGORIES, array());

		// If no categories exist, initialize defaults.
		if (empty($categories)) {
			$categories = $this->initialize_default_categories();
		}

		// Apply filter for extensibility.
		$categories = apply_filters('gdpr_cookie_categories', $categories);

		// Cache the result.
		if ($use_cache) {
			set_transient($cache_key, $categories, self::CACHE_DURATION);
		}

		return $categories;
	}

	/**
	 * Get category by ID.
	 *
	 * @param string $category_id Category ID.
	 * @return array|null Category object or null if not found.
	 */
	public function get_category_by_id($category_id)
	{
		$categories = $this->get_categories();
		foreach ($categories as $category) {
			if (isset($category['id']) && $category['id'] === $category_id) {
				return $category;
			}
		}
		return null;
	}

	/**
	 * Save categories.
	 *
	 * @param array $categories Array of category objects.
	 * @return bool True on success, false on failure.
	 */
	public function save_categories($categories)
	{
		// Validate and sanitize categories.
		$sanitized = $this->sanitize_categories($categories);

		// Clear cache.
		$this->clear_categories_cache();

		// Apply filter before saving.
		$sanitized = apply_filters('gdpr_cookie_categories', $sanitized);

		$result = update_option(self::OPTION_CATEGORIES, $sanitized);

		return $result;
	}

	/**
	 * Get cookie mappings.
	 *
	 * @param bool $use_cache Whether to use cache.
	 * @return array Array of mapping objects.
	 */
	public function get_cookie_mappings($use_cache = true)
	{
		$cache_key = self::CACHE_KEY_MAPPINGS . get_current_blog_id();

		if ($use_cache) {
			$cached = get_transient($cache_key);
			if (false !== $cached) {
				$mappings = apply_filters('gdpr_cookie_mappings', $cached);
				return $mappings;
			}
		}

		$mappings = get_option(self::OPTION_MAPPINGS, array());

		// Apply filter for extensibility.
		$mappings = apply_filters('gdpr_cookie_mappings', $mappings);

		// Cache the result.
		if ($use_cache) {
			set_transient($cache_key, $mappings, self::CACHE_DURATION);
		}

		return $mappings;
	}

	/**
	 * Get category for a cookie based on mappings.
	 *
	 * @param string $name   Cookie name.
	 * @param string $domain Cookie domain.
	 * @param string $path   Cookie path.
	 * @return string|null Category ID or null if not found.
	 */
	public function get_category_for_cookie($name, $domain = '', $path = '')
	{
		$mappings = $this->get_cookie_mappings();

		// Sort by priority (higher priority first).
		usort(
			$mappings,
			function ($a, $b) {
				$priority_a = isset($a['priority']) ? (int) $a['priority'] : 10;
				$priority_b = isset($b['priority']) ? (int) $b['priority'] : 10;
				return $priority_b - $priority_a;
			}
		);

		foreach ($mappings as $mapping) {
			if ($this->match_cookie_pattern($name, $domain, $path, $mapping)) {
				return isset($mapping['category']) ? $mapping['category'] : null;
			}
		}

		return null;
	}

	/**
	 * Check if cookie matches pattern.
	 *
	 * @param string $name   Cookie name.
	 * @param string $domain Cookie domain.
	 * @param string $path   Cookie path.
	 * @param array  $mapping Mapping object.
	 * @return bool True if matches.
	 */
	private function match_cookie_pattern($name, $domain, $path, $mapping)
	{
		// Check name pattern.
		if (!empty($mapping['pattern'])) {
			$pattern = $mapping['pattern'];
			// Convert wildcard to regex.
			$pattern_regex = str_replace('*', '.*', preg_quote($pattern, '/'));
			if (!preg_match('/^' . $pattern_regex . '$/i', $name)) {
				return false;
			}
		}

		// Check domain pattern.
		if (!empty($mapping['domain'])) {
			$domain_pattern = str_replace('*', '.*', preg_quote($mapping['domain'], '/'));
			if (!preg_match('/^' . $domain_pattern . '$/i', $domain)) {
				return false;
			}
		}

		// Check path pattern.
		if (!empty($mapping['path'])) {
			$path_pattern = str_replace('*', '.*', preg_quote($mapping['path'], '/'));
			if (!preg_match('/^' . $path_pattern . '$/i', $path)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get user preferences.
	 *
	 * @return array Array of category preferences.
	 */
	public function get_user_preferences()
	{
		// Try to get from PHP transient first.
		$session_id = $this->get_session_id();
		$transient_key = 'gdpr_category_preferences_' . $session_id;
		$preferences = get_transient($transient_key);

		if (false === $preferences) {
			// Fallback: return default preferences (only essential enabled).
			$categories = $this->get_categories();
			$preferences = array();
			foreach ($categories as $category) {
				$category_id = isset($category['id']) ? $category['id'] : '';
				$required = isset($category['required']) && $category['required'];
				$default_enabled = isset($category['default_enabled']) && $category['default_enabled'];
				$preferences[$category_id] = $required || $default_enabled;
			}
		}

		// Apply filter.
		$preferences = apply_filters('gdpr_category_preferences', $preferences);

		return $preferences;
	}

	/**
	 * Save user preferences.
	 *
	 * @param array $preferences Array of category preferences.
	 * @return bool True on success, false on failure.
	 */
	public function save_user_preferences($preferences)
	{
		// Validate preferences.
		$sanitized = $this->sanitize_preferences($preferences);

		// Apply filter before saving.
		$sanitized = apply_filters('gdpr_category_preferences', $sanitized);

		// Store in transient (24 hours).
		$session_id = $this->get_session_id();
		$transient_key = 'gdpr_category_preferences_' . $session_id;
		$result = set_transient($transient_key, $sanitized, DAY_IN_SECONDS);

		// Trigger action.
		do_action('gdpr_category_preferences_saved', $sanitized);

		return $result;
	}

	/**
	 * Check if category is allowed for current user.
	 *
	 * @param string $category_id Category ID.
	 * @return bool True if allowed.
	 */
	public function is_category_allowed($category_id)
	{
		$preferences = $this->get_user_preferences();

		// #region agent log
		$log_data = array(
			'category_id' => $category_id,
			'preferences' => $preferences,
			'is_allowed' => isset($preferences[$category_id]) && $preferences[$category_id],
			'location' => 'class-cookie-category-manager.php:296',
		);
		error_log(json_encode(array(
			'id' => 'log_' . time() . '_php',
			'timestamp' => time() * 1000,
			'location' => 'class-cookie-category-manager.php:296',
			'message' => 'is_category_allowed called',
			'data' => $log_data,
			'sessionId' => 'debug-session',
			'runId' => 'run1',
			'hypothesisId' => 'E',
		)) . "\n", 3, '/Users/derek/Local Sites/test/app/public/.cursor/debug.log');
		// #endregion

		return isset($preferences[$category_id]) && $preferences[$category_id];
	}

	/**
	 * Determine if cookie should be blocked.
	 *
	 * @param string $name   Cookie name.
	 * @param string $domain Cookie domain.
	 * @param string $path   Cookie path.
	 * @return bool True if should be blocked.
	 */
	public function should_block_cookie($name, $domain = '', $path = '')
	{
		// Get category for this cookie.
		$category_id = $this->get_category_for_cookie($name, $domain, $path);

		// #region agent log
		$log_data = array(
			'cookie_name' => $name,
			'category_id' => $category_id,
			'location' => 'class-cookie-category-manager.php:310',
		);
		error_log(json_encode(array(
			'id' => 'log_' . time() . '_php',
			'timestamp' => time() * 1000,
			'location' => 'class-cookie-category-manager.php:310',
			'message' => 'should_block_cookie called',
			'data' => $log_data,
			'sessionId' => 'debug-session',
			'runId' => 'run2',
			'hypothesisId' => 'D',
		)) . "\n", 3, '/Users/derek/Local Sites/test/app/public/.cursor/debug.log');
		// #endregion

		// If no category found, check mode.
		if (null === $category_id) {
			// Check if we're in simple mode (no categories).
			$settings = $this->get_settings();

			// #region agent log
			$log_data = array(
				'cookie_name' => $name,
				'no_category' => true,
				'settings_mode' => isset($settings['mode']) ? $settings['mode'] : 'not_set',
				'location' => 'class-cookie-category-manager.php:323',
			);
			error_log(json_encode(array(
				'id' => 'log_' . time() . '_php',
				'timestamp' => time() * 1000,
				'location' => 'class-cookie-category-manager.php:323',
				'message' => 'Cookie has no category mapping',
				'data' => $log_data,
				'sessionId' => 'debug-session',
				'runId' => 'run2',
				'hypothesisId' => 'D',
			)) . "\n", 3, '/Users/derek/Local Sites/test/app/public/.cursor/debug.log');
			// #endregion

			if (isset($settings['mode']) && 'simple' === $settings['mode']) {
				// In simple mode, check old preference.
				$session_id = $this->get_session_id();
				$preference = get_transient('gdpr_consent_preference_' . $session_id);
				return $preference === 'declined';
			}

			// In category mode with no mapping: check if user has declined all categories.
			// If all non-essential categories are declined, block unmapped cookies.
			$preferences = $this->get_user_preferences();
			$categories = $this->get_categories();
			$all_non_essential_declined = true;
			foreach ($categories as $category) {
				$cat_id = isset($category['id']) ? $category['id'] : '';
				$required = isset($category['required']) && $category['required'];
				if (!$required && isset($preferences[$cat_id]) && $preferences[$cat_id]) {
					$all_non_essential_declined = false;
					break;
				}
			}
			// If all non-essential are declined, block unmapped cookies (safer approach).
			return $all_non_essential_declined;
		}

		// Check if user allowed this category.
		$is_allowed = $this->is_category_allowed($category_id);
		$should_block = !$is_allowed;

		// #region agent log
		$log_data = array(
			'cookie_name' => $name,
			'category_id' => $category_id,
			'is_allowed' => $is_allowed,
			'should_block' => $should_block,
			'location' => 'class-cookie-category-manager.php:327',
		);
		error_log(json_encode(array(
			'id' => 'log_' . time() . '_php',
			'timestamp' => time() * 1000,
			'location' => 'class-cookie-category-manager.php:327',
			'message' => 'should_block_cookie result',
			'data' => $log_data,
			'sessionId' => 'debug-session',
			'runId' => 'run2',
			'hypothesisId' => 'E',
		)) . "\n", 3, '/Users/derek/Local Sites/test/app/public/.cursor/debug.log');
		// #endregion

		return $should_block;
	}

	/**
	 * Get settings.
	 *
	 * @return array Settings array.
	 */
	public function get_settings()
	{
		$defaults = array(
			'mode' => 'simple', // 'simple' or 'categories'.
		);
		$settings = get_option(self::OPTION_SETTINGS, $defaults);
		return wp_parse_args($settings, $defaults);
	}

	/**
	 * Save settings.
	 *
	 * @param array $settings Settings array.
	 * @return bool True on success, false on failure.
	 */
	public function save_settings($settings)
	{
		$sanitized = $this->sanitize_settings($settings);
		return update_option(self::OPTION_SETTINGS, $sanitized);
	}

	/**
	 * Get session ID.
	 *
	 * @return string Session identifier.
	 */
	private function get_session_id()
	{
		$ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
		$ua = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
		$salt = defined('AUTH_SALT') ? AUTH_SALT : 'gdpr-consent-salt';
		return wp_hash($ip . $ua . $salt, 'gdpr_consent');
	}

	/**
	 * Sanitize categories.
	 *
	 * @param array $categories Categories array.
	 * @return array Sanitized categories.
	 */
	private function sanitize_categories($categories)
	{
		$sanitized = array();
		foreach ($categories as $category) {
			if (!is_array($category)) {
				continue;
			}
			$sanitized[] = array(
				'id' => isset($category['id']) ? sanitize_key($category['id']) : '',
				'name' => isset($category['name']) ? sanitize_text_field($category['name']) : '',
				'description' => isset($category['description']) ? sanitize_textarea_field($category['description']) : '',
				'required' => isset($category['required']) ? (bool) $category['required'] : false,
				'default_enabled' => isset($category['default_enabled']) ? (bool) $category['default_enabled'] : false,
				'order' => isset($category['order']) ? absint($category['order']) : 0,
			);
		}
		return $sanitized;
	}

	/**
	 * Sanitize preferences.
	 *
	 * @param array $preferences Preferences array.
	 * @return array Sanitized preferences.
	 */
	private function sanitize_preferences($preferences)
	{
		$sanitized = array();
		$categories = $this->get_categories();
		$category_ids = array();
		foreach ($categories as $category) {
			if (isset($category['id'])) {
				$category_ids[] = $category['id'];
			}
		}

		foreach ($preferences as $category_id => $allowed) {
			if (in_array($category_id, $category_ids, true)) {
				$sanitized[sanitize_key($category_id)] = (bool) $allowed;
			}
		}

		// Ensure required categories are always enabled.
		foreach ($categories as $category) {
			if (isset($category['required']) && $category['required'] && isset($category['id'])) {
				$sanitized[$category['id']] = true;
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $settings Settings array.
	 * @return array Sanitized settings.
	 */
	private function sanitize_settings($settings)
	{
		$sanitized = array();
		if (isset($settings['mode'])) {
			$mode = sanitize_text_field($settings['mode']);
			$sanitized['mode'] = in_array($mode, array('simple', 'categories'), true) ? $mode : 'simple';
		}
		return $sanitized;
	}

	/**
	 * Initialize default categories.
	 *
	 * @return array Default categories.
	 */
	private function initialize_default_categories()
	{
		// Load defaults class if available.
		if (class_exists(__NAMESPACE__ . '\Cookie_Category_Defaults')) {
			$defaults = new Cookie_Category_Defaults();
			return $defaults->get_default_categories();
		}

		// Fallback defaults.
		return array(
			array(
				'id' => 'essential',
				'name' => __('Essential Cookies', 'gdpr-cookie-consent-elementor'),
				'description' => __('Required for the website to function properly', 'gdpr-cookie-consent-elementor'),
				'required' => true,
				'default_enabled' => true,
				'order' => 1,
			),
		);
	}

	/**
	 * Clear categories cache.
	 *
	 * @return void
	 */
	public function clear_categories_cache()
	{
		$cache_key = self::CACHE_KEY_CATEGORIES . get_current_blog_id();
		delete_transient($cache_key);
	}

	/**
	 * Clear mappings cache.
	 *
	 * @return void
	 */
	public function clear_mappings_cache()
	{
		$cache_key = self::CACHE_KEY_MAPPINGS . get_current_blog_id();
		delete_transient($cache_key);
	}
}

