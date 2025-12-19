/**
 * GDPR Cookie Blocker
 *
 * Blocks all cookies when user has declined consent.
 * This script must load early before other scripts execute.
 *
 * @package GDPR_Cookie_Consent_Elementor
 */

(function() {
	'use strict';

	/**
	 * Check if preferences exist in sessionStorage.
	 * This runs immediately when script loads to activate blocking early.
	 *
	 * @return {boolean} True if preferences exist.
	 */
	function hasExistingPreferences() {
		try {
			const categoryPrefs = sessionStorage.getItem('gdpr_cookie_category_preferences');
			const simplePref = sessionStorage.getItem('gdpr_cookie_consent_preference');
			return (categoryPrefs && JSON.parse(categoryPrefs)) || (simplePref === 'declined');
		} catch (e) {
			return false;
		}
	}

	/**
	 * Check if cookies should be blocked.
	 *
	 * @return {boolean} True if cookies should be blocked.
	 */
	function shouldBlockCookies() {
		try {
			// Check for category preferences first.
			const categoryPrefs = getCategoryPreferences();

			// #region agent log
			fetch('http://127.0.0.1:7242/ingest/00d237e4-bec4-4982-96be-f6cd6aee5b45',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'gdpr-cookie-blocker.js:18',message:'shouldBlockCookies called',data:{categoryPrefs:categoryPrefs,hasCategoryPrefs:categoryPrefs && Object.keys(categoryPrefs).length > 0},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'F'})}).catch(()=>{});
			// #endregion

			if (categoryPrefs && Object.keys(categoryPrefs).length > 0) {
				// In category mode, we don't block all cookies here.
				// Individual cookies are checked in shouldBlockCookieByCategory.
				return false;
			}

			// Fallback to simple mode.
			const preference = sessionStorage.getItem('gdpr_cookie_consent_preference');

			// #region agent log
			fetch('http://127.0.0.1:7242/ingest/00d237e4-bec4-4982-96be-f6cd6aee5b45',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'gdpr-cookie-blocker.js:31',message:'Simple mode preference check',data:{preference:preference,shouldBlock:preference === 'declined'},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'C'})}).catch(()=>{});
			// #endregion

			return preference === 'declined';
		} catch (e) {
			// If sessionStorage is not available, allow cookies.
			return false;
		}
	}

	/**
	 * Get category preferences from storage.
	 *
	 * @return {Object|null} Preferences object or null.
	 */
	function getCategoryPreferences() {
		try {
			const stored = sessionStorage.getItem('gdpr_cookie_category_preferences');
			if (stored) {
				return JSON.parse(stored);
			}
		} catch (e) {
			// Silently fail.
		}
		return null;
	}

	/**
	 * Get category for cookie (would need mapping data from server).
	 * This is a simplified version - in production, you'd get mappings from PHP.
	 *
	 * @param {string} cookieName Cookie name.
	 * @param {string} domain Cookie domain.
	 * @param {string} path Cookie path.
	 * @return {string|null} Category ID or null.
	 */
	function getCookieCategory(cookieName, domain, path) {
		// Common patterns (would be better to get from server).
		if (cookieName.indexOf('wordpress') === 0 || cookieName.indexOf('wp_') === 0) {
			return 'essential';
		}
		if (cookieName.indexOf('_ga') === 0 || cookieName.indexOf('_gid') === 0 || cookieName.indexOf('_gat') === 0) {
			return 'analytics';
		}
		if (cookieName.indexOf('_fbp') === 0 || cookieName.indexOf('_fbc') === 0) {
			return 'marketing';
		}
		if (cookieName.indexOf('sbjs_') === 0) {
			return 'analytics';
		}
		return null;
	}

	/**
	 * Check if all non-essential categories are declined.
	 *
	 * @param {Object} categoryPrefs Category preferences object.
	 * @return {boolean} True if all non-essential categories are declined.
	 */
	function checkAllNonEssentialDeclined(categoryPrefs) {
		// Essential categories are: 'essential'
		// We need to check if all non-essential categories are false
		// For now, we'll check common categories: analytics, marketing, functional
		const nonEssentialCategories = ['analytics', 'marketing', 'functional'];
		let allDeclined = true;
		
		for (let i = 0; i < nonEssentialCategories.length; i++) {
			const catId = nonEssentialCategories[i];
			if (categoryPrefs[catId] === true) {
				allDeclined = false;
				break;
			}
		}
		
		return allDeclined;
	}

	/**
	 * Check if cookie should be blocked based on category.
	 *
	 * @param {string} cookieName Cookie name.
	 * @param {string} domain Cookie domain.
	 * @param {string} path Cookie path.
	 * @return {boolean} True if should be blocked.
	 */
	function shouldBlockCookieByCategory(cookieName, domain, path) {
		const categoryPrefs = getCategoryPreferences();
		if (!categoryPrefs || Object.keys(categoryPrefs).length === 0) {
			// Not in category mode, use simple mode.
			return shouldBlockCookies();
		}

		const category = getCookieCategory(cookieName, domain, path);

		// #region agent log
		fetch('http://127.0.0.1:7242/ingest/00d237e4-bec4-4982-96be-f6cd6aee5b45',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'gdpr-cookie-blocker.js:88',message:'shouldBlockCookieByCategory called',data:{cookieName:cookieName,category:category,categoryPrefs:categoryPrefs,isAllowed:category ? categoryPrefs[category] : null,shouldBlock:category ? !categoryPrefs[category] : false},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'D'})}).catch(()=>{});
		// #endregion

		if (!category) {
			// No category found - check if all non-essential categories are declined.
			// If all non-essential are declined, block unmapped cookies (safer approach).
			const allNonEssentialDeclined = checkAllNonEssentialDeclined(categoryPrefs);
			if (allNonEssentialDeclined) {
				// Block unmapped cookies if all non-essential declined.
				return true;
			}
			// Allow only if some non-essential categories are enabled.
			return false;
		}

		// Check if user allowed this category.
		const shouldBlock = !categoryPrefs[category];
		
		// Note: Cookie deletion happens in the setter, not here, to avoid performance issues.
		// The setter will prevent the cookie from being set, which is more efficient.
		
		return shouldBlock;
	}

	/**
	 * Delete all cookies aggressively.
	 */
	function deleteAllCookies() {
		try {
			// Get current cookies.
			const cookieString = originalCookieDescriptor ? originalCookieDescriptor.get.call(document) : document.cookie;
			if (!cookieString) {
				return;
			}

			const cookies = cookieString.split(';');
			const hostname = window.location.hostname;
			const paths = ['/', window.location.pathname];
			
			for (let i = 0; i < cookies.length; i++) {
				const cookie = cookies[i];
				const eqPos = cookie.indexOf('=');
				const name = eqPos > -1 ? cookie.substr(0, eqPos).trim() : cookie.trim();
				
				if (!name) {
					continue;
				}

				// Delete cookie for various paths and domains.
				for (let p = 0; p < paths.length; p++) {
					const path = paths[p];
					// Try multiple domain variations.
					const domains = [
						'',
						hostname,
						'.' + hostname,
						window.location.host
					];

					for (let d = 0; d < domains.length; d++) {
						const domain = domains[d];
						const domainPart = domain ? ';domain=' + domain : '';
						const isSecure = window.location.protocol === 'https:';
						const securePart = isSecure ? ';secure' : '';
						
						// Try with secure flag if on HTTPS.
						document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=' + path + domainPart + securePart;
						// Also try with explicit secure flag to catch cookies set with secure=true.
						if (!isSecure) {
							document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=' + path + domainPart + ';secure';
						}
					}
				}
			}
		} catch (e) {
			// Silently fail if cookie deletion fails.
		}
	}

	/**
	 * Delete specific cookies by prefix.
	 *
	 * @param {string} prefix Cookie name prefix to delete.
	 */
	function deleteCookiesByPrefix(prefix) {
		try {
			const cookieString = originalCookieDescriptor ? originalCookieDescriptor.get.call(document) : document.cookie;
			if (!cookieString) {
				return;
			}

			const cookies = cookieString.split(';');
			const hostname = window.location.hostname;
			
			for (let i = 0; i < cookies.length; i++) {
				const cookie = cookies[i];
				const eqPos = cookie.indexOf('=');
				const name = eqPos > -1 ? cookie.substr(0, eqPos).trim() : cookie.trim();
				
				if (name && name.indexOf(prefix) === 0) {
					// Delete this specific cookie.
					const domains = ['', hostname, '.' + hostname, window.location.host];
					const paths = ['/', window.location.pathname];
					const isSecure = window.location.protocol === 'https:';
					
					for (let d = 0; d < domains.length; d++) {
						for (let p = 0; p < paths.length; p++) {
							const domain = domains[d];
							const domainPart = domain ? ';domain=' + domain : '';
							const securePart = isSecure ? ';secure' : '';
							document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=' + paths[p] + domainPart + securePart;
							// Also try with explicit secure flag to catch cookies set with secure=true.
							if (!isSecure) {
								document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=' + paths[p] + domainPart + ';secure';
							}
						}
					}
				}
			}
		} catch (e) {
			// Silently fail.
		}
	}

	/**
	 * Delete a specific cookie by name, domain, and path.
	 *
	 * @param {string} cookieName Cookie name to delete.
	 * @param {string} domain Cookie domain.
	 * @param {string} path Cookie path.
	 */
	function deleteCookieByName(cookieName, domain, path) {
		try {
			const hostname = window.location.hostname;
			const domains = domain ? [domain] : ['', hostname, '.' + hostname, window.location.host];
			const paths = path ? [path] : ['/', window.location.pathname];
			const isSecure = window.location.protocol === 'https:';
			
			for (let d = 0; d < domains.length; d++) {
				for (let p = 0; p < paths.length; p++) {
					const dom = domains[d];
					const domainPart = dom ? ';domain=' + dom : '';
					const securePart = isSecure ? ';secure' : '';
					document.cookie = cookieName + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=' + paths[p] + domainPart + securePart;
					// Also try with explicit secure flag to catch cookies set with secure=true.
					if (!isSecure) {
						document.cookie = cookieName + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=' + paths[p] + domainPart + ';secure';
					}
				}
			}
		} catch (e) {
			// Silently fail.
		}
	}

	/**
	 * Delete cookies for rejected categories.
	 */
	function deleteCookiesForRejectedCategories() {
		const categoryPrefs = getCategoryPreferences();
		if (!categoryPrefs || Object.keys(categoryPrefs).length === 0) {
			return;
		}
		
		// Delete cookies for declined categories.
		if (categoryPrefs['analytics'] === false) {
			deleteCookiesByPrefix('sbjs_');
			deleteCookiesByPrefix('_ga');
			deleteCookiesByPrefix('_gid');
			deleteCookiesByPrefix('_gat');
		}
		if (categoryPrefs['marketing'] === false) {
			deleteCookiesByPrefix('_fbp');
			deleteCookiesByPrefix('_fbc');
		}
	}

	/**
	 * Store original cookie descriptor.
	 */
	let originalCookieDescriptor = null;
	let cookieBlockingActive = false;
	
	// Check for existing preferences immediately and activate blocking if needed.
	// This happens before DOM is ready to catch early cookie setting.
	// Only activate if we're not in a reload situation (check for reload flag).
	const isCurrentlyReloading = sessionStorage.getItem('gdpr_reloading') === 'true';
	if (hasExistingPreferences() && !isCurrentlyReloading) {
		// Get original cookie descriptor immediately.
		if (!originalCookieDescriptor) {
			originalCookieDescriptor = Object.getOwnPropertyDescriptor(Document.prototype, 'cookie') ||
				Object.getOwnPropertyDescriptor(HTMLDocument.prototype, 'cookie') ||
				Object.getOwnPropertyDescriptor(window, 'cookie');
			
			if (!originalCookieDescriptor) {
				try {
					const testCookie = document.cookie;
					originalCookieDescriptor = {
						get: function() {
							return document.cookie;
						},
						set: function(value) {
							// Will be overridden below.
						},
						configurable: true
					};
				} catch (e) {
					// Cannot get descriptor.
				}
			}
		}
		
		// Activate blocking immediately if we have the descriptor.
		if (originalCookieDescriptor && !cookieBlockingActive) {
			try {
				Object.defineProperty(document, 'cookie', {
					get: function() {
						return originalCookieDescriptor.get.call(this);
					},
					set: function(value) {
						// Parse cookie name from value.
						const cookieParts = value.split(';');
						const nameValue = cookieParts[0].split('=');
						const cookieName = nameValue[0].trim();
						
						// Extract domain and path if available.
						let domain = '';
						let path = '';
						for (let i = 1; i < cookieParts.length; i++) {
							const part = cookieParts[i].trim();
							if (part.toLowerCase().indexOf('domain=') === 0) {
								domain = part.substring(7).trim();
							} else if (part.toLowerCase().indexOf('path=') === 0) {
								path = part.substring(5).trim();
							}
						}
						
						// Check if this cookie should be blocked.
						if (shouldBlockCookieByCategory(cookieName, domain, path)) {
							return false;
						}
						return originalCookieDescriptor.set.call(this, value);
					},
					configurable: true
				});
				cookieBlockingActive = true;
			} catch (e) {
				// Silently fail.
			}
		}
	}
	
	// Clear reload flag after a short delay (page has loaded).
	if (isCurrentlyReloading) {
		setTimeout(function() {
			sessionStorage.removeItem('gdpr_reloading');
		}, 1000);
	}

	/**
	 * Initialize cookie blocking.
	 */
	function initCookieBlocking() {
		// Get original cookie descriptor if not already stored.
		if (!originalCookieDescriptor) {
			originalCookieDescriptor = Object.getOwnPropertyDescriptor(Document.prototype, 'cookie') ||
				Object.getOwnPropertyDescriptor(HTMLDocument.prototype, 'cookie') ||
				Object.getOwnPropertyDescriptor(window, 'cookie');

			if (!originalCookieDescriptor) {
				// Try to get it from document directly.
				try {
					const testCookie = document.cookie;
					originalCookieDescriptor = {
						get: function() {
							return document.cookie;
						},
						set: function(value) {
							// This will be overridden below.
						},
						configurable: true
					};
				} catch (e) {
					return;
				}
			}
		}

		const shouldBlock = shouldBlockCookies();
		const categoryPrefs = getCategoryPreferences();
		const hasCategoryPrefs = categoryPrefs && Object.keys(categoryPrefs).length > 0;
		
		// Activate blocking if:
		// 1. Simple mode: shouldBlock is true
		// 2. Category mode: category preferences exist (we need to check each cookie individually)
		const shouldActivateBlocking = shouldBlock || hasCategoryPrefs;

		// If we should activate blocking and it isn't active, activate it.
		if (shouldActivateBlocking && !cookieBlockingActive) {
			// Delete existing cookies first.
			if (shouldBlock) {
				deleteAllCookies();
			} else if (hasCategoryPrefs) {
				// In category mode, delete cookies for declined categories.
				deleteCookiesForRejectedCategories();
			}

			// Override document.cookie setter.
			try {
				Object.defineProperty(document, 'cookie', {
					get: function() {
						// In category mode, we can't filter the getter easily.
						// Return all cookies - blocking happens in setter.
						return originalCookieDescriptor.get.call(this);
					},
					set: function(value) {
						// Parse cookie name from value.
						const cookieParts = value.split(';');
						const nameValue = cookieParts[0].split('=');
						const cookieName = nameValue[0].trim();
						
						// Extract domain and path if available.
						let domain = '';
						let path = '';
						for (let i = 1; i < cookieParts.length; i++) {
							const part = cookieParts[i].trim();
							if (part.toLowerCase().indexOf('domain=') === 0) {
								domain = part.substring(7).trim();
							} else if (part.toLowerCase().indexOf('path=') === 0) {
								path = part.substring(5).trim();
							}
						}

						// Check if this cookie should be blocked.
						if (shouldBlockCookieByCategory(cookieName, domain, path)) {
							// Silently block the cookie - don't call setter.
							return false;
						}
						// Allow cookie if preference allows it.
						return originalCookieDescriptor.set.call(this, value);
					},
					configurable: true
				});
				cookieBlockingActive = true;
			} catch (e) {
				// If defineProperty fails, try alternative approach.
				console.warn('GDPR Cookie Blocker: Could not override document.cookie', e);
			}
		} else if (!shouldActivateBlocking && cookieBlockingActive) {
			// Restore original behavior if preference changed to accept all.
			try {
				Object.defineProperty(document, 'cookie', originalCookieDescriptor);
				cookieBlockingActive = false;
			} catch (e) {
				// Silently fail.
			}
		}

		// Block WordPress wpCookies if available.
		if (typeof window.wpCookies !== 'undefined' && window.wpCookies.set) {
			const originalWpCookiesSet = window.wpCookies.set;
			window.wpCookies.set = function(name, value, expires, path, domain, secure) {
				if (shouldBlockCookieByCategory(name, domain || '', path || '')) {
					return false;
				}
				return originalWpCookiesSet.call(this, name, value, expires, path, domain, secure);
			};
		}

		// Block cookies set via jQuery if available.
		if (typeof jQuery !== 'undefined') {
			if (jQuery.cookie) {
				const originalJQueryCookie = jQuery.cookie;
				jQuery.cookie = function(name, value, options) {
					if (value !== undefined) {
						const domain = options && options.domain ? options.domain : '';
						const path = options && options.path ? options.path : '';
						if (shouldBlockCookieByCategory(name, domain, path)) {
							// Block setting cookies.
							return undefined;
						}
					}
					return originalJQueryCookie.apply(this, arguments);
				};
			}
			// Also block $.cookie if it exists.
			if (jQuery.fn && jQuery.fn.cookie) {
				const originalJQueryFnCookie = jQuery.fn.cookie;
				jQuery.fn.cookie = function(name, value, options) {
					if (value !== undefined) {
						const domain = options && options.domain ? options.domain : '';
						const path = options && options.path ? options.path : '';
						if (shouldBlockCookieByCategory(name, domain, path)) {
							return this;
						}
					}
					return originalJQueryFnCookie.apply(this, arguments);
				};
			}
		}

		// Block common cookie libraries.
		if (typeof Cookies !== 'undefined' && Cookies.set) {
			const originalCookiesSet = Cookies.set;
			Cookies.set = function(name, value, options) {
				const domain = options && options.domain ? options.domain : '';
				const path = options && options.path ? options.path : '';
				if (shouldBlockCookieByCategory(name, domain, path)) {
					return undefined;
				}
				return originalCookiesSet.call(this, name, value, options);
			};
		}

		// Block js-cookie library.
		if (typeof window.Cookies !== 'undefined' && window.Cookies.set) {
			const originalCookiesSet = window.Cookies.set;
			window.Cookies.set = function(name, value, options) {
				const domain = options && options.domain ? options.domain : '';
				const path = options && options.path ? options.path : '';
				if (shouldBlockCookieByCategory(name, domain, path)) {
					return undefined;
				}
				return originalCookiesSet.call(this, name, value, options);
			};
		}

		// Block Sourcebuster.js (WooCommerce order attribution).
		// Re-run interception to catch late-loading libraries.
		if (typeof window.sbjs !== 'undefined') {
			blockSbjsMethods(window.sbjs);
		}

		// Block Sourcebuster cookie setting directly.
		if (typeof window.Sourcebuster !== 'undefined') {
			if (window.Sourcebuster.set) {
				const originalSourcebusterSet = window.Sourcebuster.set;
				window.Sourcebuster.set = function() {
					// Check if analytics category is allowed.
					const categoryPrefs = getCategoryPreferences();
					if (categoryPrefs && categoryPrefs['analytics'] === false) {
						return false;
					}
					if (shouldBlockCookies()) {
						return false;
					}
					return originalSourcebusterSet.apply(this, arguments);
				};
			}
		}
	}

	/**
	 * Proactively block sbjs library before it initializes.
	 */
	function setupProactiveSbjsBlocking() {
		// Try to intercept sbjs before it's created.
		try {
			// Use Object.defineProperty to intercept window.sbjs
			let sbjsValue = null;
			Object.defineProperty(window, 'sbjs', {
				configurable: true,
				get: function() {
					return sbjsValue;
				},
				set: function(value) {
					sbjsValue = value;
					// Immediately block sbjs methods if value is set.
					if (value && typeof value === 'object') {
						blockSbjsMethods(value);
					}
				}
			});
		} catch (e) {
			// If defineProperty fails, try to block when it appears.
		}
		
		// Also try to block Sourcebuster.
		try {
			let sourcebusterValue = null;
			Object.defineProperty(window, 'Sourcebuster', {
				configurable: true,
				get: function() {
					return sourcebusterValue;
				},
				set: function(value) {
					sourcebusterValue = value;
					if (value && typeof value === 'object' && value.set) {
						const originalSet = value.set;
						value.set = function() {
							const categoryPrefs = getCategoryPreferences();
							if (categoryPrefs && categoryPrefs['analytics'] === false) {
								return false;
							}
							if (shouldBlockCookies()) {
								return false;
							}
							return originalSet.apply(this, arguments);
						};
					}
				}
			});
		} catch (e) {
			// Silently fail.
		}
	}
	
	/**
	 * Block sbjs methods.
	 *
	 * @param {Object} sbjsObj The sbjs object to block.
	 */
	function blockSbjsMethods(sbjsObj) {
		if (!sbjsObj) {
			return;
		}
		
		// Block sbjs.get method.
		if (sbjsObj.get && typeof sbjsObj.get === 'function') {
			const originalGet = sbjsObj.get;
			sbjsObj.get = function() {
				const categoryPrefs = getCategoryPreferences();
				if (categoryPrefs && categoryPrefs['analytics'] === false) {
					return null;
				}
				if (shouldBlockCookies()) {
					return null;
				}
				return originalGet.apply(this, arguments);
			};
		}
		
		// Block sbjs.set method.
		if (sbjsObj.set && typeof sbjsObj.set === 'function') {
			const originalSet = sbjsObj.set;
			sbjsObj.set = function() {
				const categoryPrefs = getCategoryPreferences();
				if (categoryPrefs && categoryPrefs['analytics'] === false) {
					return false;
				}
				if (shouldBlockCookies()) {
					return false;
				}
				return originalSet.apply(this, arguments);
			};
		}
	}
	
	// Set up proactive blocking before initialization.
	setupProactiveSbjsBlocking();
	
	// Initialize immediately.
	initCookieBlocking();

	// Aggressive cookie monitoring and deletion when blocking is active.
	let cookieMonitorInterval = null;
	
	function startCookieMonitoring() {
		if (cookieMonitorInterval) {
			return; // Already monitoring.
		}
		
		// Don't start monitoring if we're in a reload situation.
		const isReloading = sessionStorage.getItem('gdpr_reloading') === 'true';
		if (isReloading) {
			return;
		}
		
		cookieMonitorInterval = setInterval(function() {
			// Check again if we're reloading and stop if so.
			const stillReloading = sessionStorage.getItem('gdpr_reloading') === 'true';
			if (stillReloading) {
				stopCookieMonitoring();
				return;
			}
			
			const shouldBlock = shouldBlockCookies();
			const categoryPrefs = getCategoryPreferences();
			const hasCategoryPrefs = categoryPrefs && Object.keys(categoryPrefs).length > 0;
			
			if (shouldBlock) {
				// Simple mode: delete all cookies.
				deleteAllCookies();
				// Specifically target common analytics cookies.
				deleteCookiesByPrefix('sbjs_');
				deleteCookiesByPrefix('_ga');
				deleteCookiesByPrefix('_gid');
				deleteCookiesByPrefix('_gat');
				deleteCookiesByPrefix('_fbp');
				deleteCookiesByPrefix('_fbc');
			} else if (hasCategoryPrefs) {
				// Category mode: delete cookies for declined categories.
				deleteCookiesForRejectedCategories();
			}
		}, 250); // Check every 250ms when blocking is active (balanced performance and effectiveness).
	}

	function stopCookieMonitoring() {
		if (cookieMonitorInterval) {
			clearInterval(cookieMonitorInterval);
			cookieMonitorInterval = null;
		}
	}

	// Start monitoring if cookies should be blocked OR if category preferences exist.
	// But not if we're in a reload situation.
	const shouldStartMonitoring = sessionStorage.getItem('gdpr_reloading') !== 'true';
	if (shouldStartMonitoring) {
		const initialShouldBlock = shouldBlockCookies();
		const initialCategoryPrefs = getCategoryPreferences();
		const initialHasCategoryPrefs = initialCategoryPrefs && Object.keys(initialCategoryPrefs).length > 0;
		
		if (initialShouldBlock || initialHasCategoryPrefs) {
			startCookieMonitoring();
		}
	}

	// Re-initialize periodically to catch new cookie-setting methods.
	// Use a longer interval to avoid performance issues.
	const checkInterval = setInterval(function() {
		// Don't re-initialize if we're reloading.
		const isReloading = sessionStorage.getItem('gdpr_reloading') === 'true';
		if (isReloading) {
			return;
		}
		
		const shouldBlock = shouldBlockCookies();
		const categoryPrefs = getCategoryPreferences();
		const hasCategoryPrefs = categoryPrefs && Object.keys(categoryPrefs).length > 0;
		
		if (shouldBlock || hasCategoryPrefs) {
			initCookieBlocking();
			if (shouldBlock || hasCategoryPrefs) {
				startCookieMonitoring();
			}
		} else {
			stopCookieMonitoring();
		}
	}, 500); // Check every 500ms (reduced frequency for better performance).

	// Re-check on preference change (for dynamic updates).
	if (typeof window !== 'undefined') {
		// Listen for storage events (from other tabs).
		window.addEventListener('storage', function(e) {
			if (e.key === 'gdpr_cookie_consent_preference') {
				initCookieBlocking();
				// Delete cookies if declined.
				if (e.newValue === 'declined') {
					deleteAllCookies();
				}
			}
		});

		// Expose function to re-initialize blocking (for use by widget handler).
		window.gdprCookieBlockerInit = function() {
			initCookieBlocking();
			const shouldBlock = shouldBlockCookies();
			const categoryPrefs = getCategoryPreferences();
			const hasCategoryPrefs = categoryPrefs && Object.keys(categoryPrefs).length > 0;
			
			if (shouldBlock) {
				deleteAllCookies();
				deleteCookiesByPrefix('sbjs_');
				startCookieMonitoring();
			} else if (hasCategoryPrefs) {
				deleteCookiesForRejectedCategories();
				startCookieMonitoring();
			} else {
				stopCookieMonitoring();
			}
		};
	}

	// Clean up intervals when page unloads.
	if (typeof window !== 'undefined') {
		window.addEventListener('beforeunload', function() {
			clearInterval(checkInterval);
			stopCookieMonitoring();
		});
	}
})();

