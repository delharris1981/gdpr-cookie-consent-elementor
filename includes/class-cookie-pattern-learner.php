<?php
/**
 * Cookie Pattern Learner
 *
 * Learns patterns from manual category assignments to improve detection.
 *
 * @package GDPR_Cookie_Consent_Elementor
 */

namespace GDPR_Cookie_Consent_Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Cookie Pattern Learner Class.
 *
 * @since 1.2.0
 */
class Cookie_Pattern_Learner {

	/**
	 * Option name for learned patterns.
	 *
	 * @var string
	 */
	const OPTION_LEARNED = 'gdpr_learned_patterns';

	/**
	 * Learn from manual category assignment.
	 *
	 * @param string $cookie_name Cookie name.
	 * @param string $category_id Category ID.
	 * @return bool True on success.
	 */
	public function learn_from_assignment( $cookie_name, $category_id ) {
		$learned = $this->get_learned_patterns();

		// Extract patterns from cookie name.
		$patterns = $this->extract_patterns( $cookie_name );

		foreach ( $patterns as $pattern ) {
			$pattern_key = hash( 'sha256', $pattern . '|' . $category_id );

			if ( isset( $learned[ $pattern_key ] ) ) {
				// Update existing pattern.
				$learned[ $pattern_key ]['total_count']++;
				$learned[ $pattern_key ]['accuracy_count']++;
			} else {
				// New pattern.
				$learned[ $pattern_key ] = array(
					'pattern'         => $pattern,
					'category'        => $category_id,
					'confidence'      => 0.5, // Start with medium confidence.
					'accuracy_count' => 1,
					'total_count'     => 1,
				);
			}

			// Recalculate confidence.
			$learned[ $pattern_key ]['confidence'] = $this->calculate_confidence( $learned[ $pattern_key ] );
		}

		// Save learned patterns.
		update_option( self::OPTION_LEARNED, $learned );

		// Trigger action.
		do_action( 'gdpr_pattern_learned', $patterns, $category_id );

		return true;
	}

	/**
	 * Get learned patterns.
	 *
	 * @return array Array of learned pattern objects.
	 */
	public function get_learned_patterns() {
		return get_option( self::OPTION_LEARNED, array() );
	}

	/**
	 * Calculate confidence score for pattern.
	 *
	 * @param array $pattern_data Pattern data.
	 * @return float Confidence score (0-1).
	 */
	public function calculate_confidence( $pattern_data ) {
		if ( ! isset( $pattern_data['total_count'] ) || $pattern_data['total_count'] === 0 ) {
			return 0;
		}

		$accuracy = $pattern_data['accuracy_count'] / $pattern_data['total_count'];

		// Boost confidence based on total count (more samples = more reliable).
		$count_boost = min( 1.0, $pattern_data['total_count'] / 10 );

		return min( 1.0, $accuracy * 0.7 + $count_boost * 0.3 );
	}

	/**
	 * Suggest category based on learned patterns.
	 *
	 * @param string $cookie_name Cookie name.
	 * @return string|null Suggested category ID or null.
	 */
	public function suggest_based_on_learning( $cookie_name ) {
		$learned = $this->get_learned_patterns();
		$best_match = null;
		$best_confidence = 0;

		foreach ( $learned as $pattern_data ) {
			$pattern = isset( $pattern_data['pattern'] ) ? $pattern_data['pattern'] : '';
			$confidence = isset( $pattern_data['confidence'] ) ? $pattern_data['confidence'] : 0;

			// Convert pattern to regex.
			$pattern_regex = str_replace( '*', '.*', preg_quote( $pattern, '/' ) );
			if ( preg_match( '/^' . $pattern_regex . '$/i', $cookie_name ) ) {
				if ( $confidence > $best_confidence ) {
					$best_confidence = $confidence;
					$best_match = isset( $pattern_data['category'] ) ? $pattern_data['category'] : null;
				}
			}
		}

		// Only return suggestion if confidence is above threshold.
		if ( $best_confidence >= 0.6 ) {
			return $best_match;
		}

		return null;
	}

	/**
	 * Extract patterns from cookie name.
	 *
	 * @param string $cookie_name Cookie name.
	 * @return array Array of pattern strings.
	 */
	private function extract_patterns( $cookie_name ) {
		$patterns = array();

		// Full name pattern.
		$patterns[] = $cookie_name;

		// Prefix pattern (e.g., "wp_" from "wp_settings_1").
		$parts = explode( '_', $cookie_name );
		if ( count( $parts ) > 1 ) {
			$prefix = $parts[0] . '_*';
			$patterns[] = $prefix;
		}

		// Suffix pattern (if applicable).
		if ( preg_match( '/^(.+?)_(\d+)$/', $cookie_name, $matches ) ) {
			$patterns[] = $matches[1] . '_*';
		}

		// Common patterns.
		if ( preg_match( '/^([a-z]+)_/', $cookie_name, $matches ) ) {
			$patterns[] = $matches[1] . '_*';
		}

		return array_unique( $patterns );
	}
}

