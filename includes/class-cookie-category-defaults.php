<?php
/**
 * Cookie Category Defaults
 *
 * Provides default categories and cookie mappings.
 *
 * @package GDPR_Cookie_Consent_Elementor
 */

namespace GDPR_Cookie_Consent_Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Cookie Category Defaults Class.
 *
 * @since 1.2.0
 */
class Cookie_Category_Defaults {

	/**
	 * Get default categories.
	 *
	 * @return array Array of default category objects.
	 */
	public function get_default_categories() {
		return array(
			array(
				'id'             => 'essential',
				'name'           => __( 'Essential Cookies', 'gdpr-cookie-consent-elementor' ),
				'description'    => __( 'Required for the website to function properly', 'gdpr-cookie-consent-elementor' ),
				'required'       => true,
				'default_enabled' => true,
				'order'          => 1,
			),
			array(
				'id'             => 'analytics',
				'name'           => __( 'Analytics Cookies', 'gdpr-cookie-consent-elementor' ),
				'description'    => __( 'Help us understand how visitors interact with our website', 'gdpr-cookie-consent-elementor' ),
				'required'       => false,
				'default_enabled' => false,
				'order'          => 2,
			),
			array(
				'id'             => 'marketing',
				'name'           => __( 'Marketing Cookies', 'gdpr-cookie-consent-elementor' ),
				'description'    => __( 'Used to track visitors for marketing purposes', 'gdpr-cookie-consent-elementor' ),
				'required'       => false,
				'default_enabled' => false,
				'order'          => 3,
			),
			array(
				'id'             => 'functional',
				'name'           => __( 'Functional Cookies', 'gdpr-cookie-consent-elementor' ),
				'description'    => __( 'Enable enhanced functionality and personalization', 'gdpr-cookie-consent-elementor' ),
				'required'       => false,
				'default_enabled' => false,
				'order'          => 4,
			),
		);
	}

	/**
	 * Get default cookie mappings.
	 *
	 * @return array Array of default mapping objects.
	 */
	public function get_default_mappings() {
		return array(
			// WordPress core cookies → Essential.
			array(
				'pattern'  => 'wordpress_*',
				'domain'   => '',
				'path'     => '',
				'category' => 'essential',
				'priority' => 100,
			),
			array(
				'pattern'  => 'wordpressuser_*',
				'domain'   => '',
				'path'     => '',
				'category' => 'essential',
				'priority' => 100,
			),
			array(
				'pattern'  => 'wordpresspass_*',
				'domain'   => '',
				'path'     => '',
				'category' => 'essential',
				'priority' => 100,
			),
			array(
				'pattern'  => 'wordpress_logged_in_*',
				'domain'   => '',
				'path'     => '',
				'category' => 'essential',
				'priority' => 100,
			),
			array(
				'pattern'  => 'wp-settings-*',
				'domain'   => '',
				'path'     => '',
				'category' => 'essential',
				'priority' => 90,
			),
			array(
				'pattern'  => 'comment_author_*',
				'domain'   => '',
				'path'     => '',
				'category' => 'essential',
				'priority' => 80,
			),
			// Google Analytics → Analytics.
			array(
				'pattern'  => '_ga',
				'domain'   => '',
				'path'     => '',
				'category' => 'analytics',
				'priority' => 50,
			),
			array(
				'pattern'  => '_ga_*',
				'domain'   => '',
				'path'     => '',
				'category' => 'analytics',
				'priority' => 50,
			),
			array(
				'pattern'  => '_gid',
				'domain'   => '',
				'path'     => '',
				'category' => 'analytics',
				'priority' => 50,
			),
			array(
				'pattern'  => '_gat',
				'domain'   => '',
				'path'     => '',
				'category' => 'analytics',
				'priority' => 50,
			),
			array(
				'pattern'  => '_gat_*',
				'domain'   => '',
				'path'     => '',
				'category' => 'analytics',
				'priority' => 50,
			),
			// Facebook Pixel → Marketing.
			array(
				'pattern'  => '_fbp',
				'domain'   => '',
				'path'     => '',
				'category' => 'marketing',
				'priority' => 50,
			),
			array(
				'pattern'  => '_fbc',
				'domain'   => '',
				'path'     => '',
				'category' => 'marketing',
				'priority' => 50,
			),
			// WooCommerce Sourcebuster → Analytics.
			array(
				'pattern'  => 'sbjs_*',
				'domain'   => '',
				'path'     => '',
				'category' => 'analytics',
				'priority' => 40,
			),
		);
	}
}

