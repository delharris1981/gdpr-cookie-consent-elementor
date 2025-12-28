/**
 * GDPR Cookie Detector
 *
 * Detects and logs cookies set via JavaScript for admin review.
 *
 * @package GDPR_Cookie_Consent_Elementor
 */

(function () {
	'use strict';

	/**
	 * Cookie detection buffer (for batching).
	 */
	let detectionBuffer = [];
	let detectionTimeout = null;

	/**
	 * Parse cookie string to extract name, domain, and path.
	 *
	 * @param {string} cookieString Cookie string.
	 * @return {Object|null} Parsed cookie data or null.
	 */
	function parseCookieString(cookieString) {
		const parts = cookieString.split(';');
		const nameValue = parts[0].trim();
		const nameValueParts = nameValue.split('=');
		const name = nameValueParts[0].trim();

		if (!name) {
			return null;
		}

		let domain = '';
		let path = '';

		for (let i = 1; i < parts.length; i++) {
			const part = parts[i].trim().toLowerCase();
			if (part.indexOf('domain=') === 0) {
				domain = part.substring(7).trim();
			} else if (part.indexOf('path=') === 0) {
				path = part.substring(5).trim();
			}
		}

		return {
			name: name,
			domain: domain,
			path: path,
			fullString: cookieString
		};
	}

	/**
	 * Log detected cookie to PHP.
	 *
	 * @param {string} cookieString Cookie string.
	 * @param {string} source Detection source.
	 */
	function logCookie(cookieString, source) {
		const cookieData = parseCookieString(cookieString);
		if (!cookieData) {
			return;
		}

		// Add to buffer.
		detectionBuffer.push({
			cookie: cookieData.fullString,
			source: source
		});

		// Debounce: send batch after 2 seconds of inactivity.
		if (detectionTimeout) {
			clearTimeout(detectionTimeout);
		}

		detectionTimeout = setTimeout(function () {
			sendDetectedCookies();
		}, 2000);
	}

	/**
	 * Send detected cookies to PHP via AJAX.
	 */
	function sendDetectedCookies() {
		if (detectionBuffer.length === 0) {
			return;
		}

		// Get nonce from detector localization (preferred) or widget container.
		let nonce = '';
		if (typeof gdprCookieDetector !== 'undefined' && gdprCookieDetector.nonce) {
			nonce = gdprCookieDetector.nonce;
		} else {
			// Fallback: try to get from widget container.
			if (typeof jQuery !== 'undefined') {
				const $container = jQuery('.gdpr-cookie-consent-container').first();
				nonce = $container.length ? $container.attr('data-nonce') : '';
			}
		}

		// Get AJAX URL.
		let ajaxUrl = '';
		if (typeof gdprCookieDetector !== 'undefined' && gdprCookieDetector.ajaxurl) {
			ajaxUrl = gdprCookieDetector.ajaxurl;
		} else if (typeof gdprCookieConsent !== 'undefined' && gdprCookieConsent.ajaxurl) {
			ajaxUrl = gdprCookieConsent.ajaxurl;
		} else if (typeof ajaxurl !== 'undefined') {
			ajaxUrl = ajaxurl;
		} else {
			const protocol = window.location.protocol === 'https:' ? 'https://' : 'http://';
			const host = window.location.host;
			ajaxUrl = protocol + host + '/wp-admin/admin-ajax.php';
		}

		// Send cookies.
		jQuery.ajax({
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'gdpr_log_detected_cookie',
				nonce: nonce,
				cookies: detectionBuffer.map(function (item) {
					return item.cookie;
				}),
				source: detectionBuffer[0].source || 'javascript'
			},
			success: function () {
				// Clear buffer on success.
				detectionBuffer = [];
			},
			error: function () {
				// Silently fail - detection is not critical.
			}
		});

		detectionTimeout = null;
	}

	/**
	 * Intercept document.cookie setter to log cookies.
	 */
	function initCookieInterception() {
		// Get original descriptor.
		const originalDescriptor = Object.getOwnPropertyDescriptor(Document.prototype, 'cookie') ||
			Object.getOwnPropertyDescriptor(HTMLDocument.prototype, 'cookie');

		if (!originalDescriptor) {
			// Try alternative approach if descriptor not available.
			try {
				// Store original setter reference.
				var originalSet = function (value) {
					// This will be overridden below.
				};

				// Try to capture original behavior.
				var testCookie = document.cookie;

				// Override with basic implementation.
				Object.defineProperty(document, 'cookie', {
					get: function () {
						return document.cookie;
					},
					set: function (value) {
						logCookie(value, 'javascript');
						// Use direct assignment instead of eval.
						// Note: This fallback is only used if Object.getOwnPropertyDescriptor failed.
						document.cookie = value;
					},
					configurable: true
				});
			} catch (e) {
				console.warn('GDPR Cookie Detector: Could not intercept document.cookie', e);
			}
			return;
		}

		// Store original setter.
		var originalSet = originalDescriptor.set;

		// Override setter to log cookies.
		Object.defineProperty(document, 'cookie', {
			get: originalDescriptor.get,
			set: function (value) {
				// Log cookie before setting.
				logCookie(value, 'javascript');
				// Call original setter.
				return originalSet.call(this, value);
			},
			configurable: true
		});
	}

	// Initialize IMMEDIATELY (don't wait for DOM).
	// This ensures we catch cookies set early in page load.
	try {
		initCookieInterception();
	} catch (e) {
		// If immediate init fails, try on DOM ready.
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', initCookieInterception);
		} else {
			initCookieInterception();
		}
	}

	// Also intercept wpCookies if available.
	if (typeof window.wpCookies !== 'undefined' && window.wpCookies.set) {
		const originalWpCookiesSet = window.wpCookies.set;
		window.wpCookies.set = function (name, value, expires, path, domain, secure) {
			// Build cookie string for logging.
			let cookieString = name + '=' + value;
			if (path) {
				cookieString += ';path=' + path;
			}
			if (domain) {
				cookieString += ';domain=' + domain;
			}
			logCookie(cookieString, 'wordpress_function');
			return originalWpCookiesSet.call(this, name, value, expires, path, domain, secure);
		};
	}

	// Send any remaining cookies on page unload.
	window.addEventListener('beforeunload', function () {
		if (detectionBuffer.length > 0) {
			// Use sendBeacon for reliability on page unload.
			let nonce = '';
			if (typeof gdprCookieDetector !== 'undefined' && gdprCookieDetector.nonce) {
				nonce = gdprCookieDetector.nonce;
			} else if (typeof jQuery !== 'undefined') {
				const $container = jQuery('.gdpr-cookie-consent-container').first();
				nonce = $container.length ? $container.attr('data-nonce') : '';
			}

			let ajaxUrl = '';
			if (typeof gdprCookieDetector !== 'undefined' && gdprCookieDetector.ajaxurl) {
				ajaxUrl = gdprCookieDetector.ajaxurl;
			} else if (typeof gdprCookieConsent !== 'undefined' && gdprCookieConsent.ajaxurl) {
				ajaxUrl = gdprCookieConsent.ajaxurl;
			} else {
				ajaxUrl = window.location.origin + '/wp-admin/admin-ajax.php';
			}

			const formData = new FormData();
			formData.append('action', 'gdpr_log_detected_cookie');
			formData.append('nonce', nonce);
			formData.append('cookies', JSON.stringify(detectionBuffer.map(function (item) {
				return item.cookie;
			})));
			formData.append('source', detectionBuffer[0].source || 'javascript');

			if (navigator.sendBeacon) {
				navigator.sendBeacon(ajaxUrl, formData);
			} else {
				// Fallback: synchronous AJAX (not ideal but works on unload).
				if (typeof jQuery !== 'undefined') {
					jQuery.ajax({
						url: ajaxUrl,
						type: 'POST',
						async: false,
						data: {
							action: 'gdpr_log_detected_cookie',
							nonce: nonce,
							cookies: detectionBuffer.map(function (item) {
								return item.cookie;
							}),
							source: detectionBuffer[0].source || 'javascript'
						}
					});
				}
			}
		}
	});

	// Also send cookies periodically (every 5 seconds) to ensure nothing is missed.
	setInterval(function () {
		if (detectionBuffer.length > 0) {
			sendDetectedCookies();
		}
	}, 5000);
})();

