/**
 * GDPR Cookie Consent Widget Frontend
 *
 * Handles user interaction with the GDPR consent widget.
 *
 * @package GDPR_Cookie_Consent_Elementor
 */

(function($) {
	'use strict';

	/**
	 * GDPR Cookie Consent Widget Handler.
	 */
	const GDPRWidgetHandler = {
	/**
	 * Storage key for preference.
	 */
	storageKey: 'gdpr_cookie_consent_preference',

	/**
	 * Storage key for category preferences.
	 */
	categoryStorageKey: 'gdpr_cookie_category_preferences',

		/**
		 * Popup context cache.
		 */
		popupContext: null,

	/**
	 * Initialize the widget handler.
	 */
	init: function() {
		this.bindEvents();
		this.checkExistingPreference();
		this.initCategoryMode();
	},

	/**
	 * Bind event handlers.
	 */
	bindEvents: function() {
		// Use event delegation for dynamically added widgets.
		$(document).on('click', '.gdpr-cookie-consent-accept-button', this.handleAccept.bind(this));
		$(document).on('click', '.gdpr-cookie-consent-decline-button', this.handleDecline.bind(this));
		$(document).on('click', '.gdpr-cookie-consent-customize-button', this.handleCustomizeClick.bind(this));
		$(document).on('change', '.gdpr-category-checkbox', this.handleCategoryToggle.bind(this));
		$(document).on('change', '.gdpr-modal-category-checkbox', this.handleModalCategoryToggle.bind(this));
		$(document).on('click', '.gdpr-accept-all-button', this.handleAcceptAll.bind(this));
		$(document).on('click', '.gdpr-reject-all-button', this.handleRejectAll.bind(this));
		$(document).on('click', '.gdpr-accept-all-modal-button', this.handleModalAcceptAll.bind(this));
		$(document).on('click', '.gdpr-reject-all-modal-button', this.handleModalRejectAll.bind(this));
		$(document).on('click', '.gdpr-save-preferences-button', this.handleModalSavePreferences.bind(this));
		$(document).on('click', '.gdpr-modal-close', this.handleModalClose.bind(this));
		$(document).on('click', '.gdpr-preferences-modal-overlay', this.handleModalOverlayClick.bind(this));
		$(document).on('click', '.gdpr-preferences-modal', function(e) {
			// Prevent clicks inside modal from closing it.
			e.stopPropagation();
		});
		$(document).on('keydown', '.gdpr-preferences-modal-overlay', this.handleModalKeydown.bind(this));
	},

	/**
	 * Check for existing preference and apply blocking if needed.
	 */
	checkExistingPreference: function() {
		try {
			// Check for category preferences first.
			const categoryPrefs = this.getCategoryPreferences();
			if (categoryPrefs && Object.keys(categoryPrefs).length > 0) {
				// Category mode - preferences already set.
				this.hideWidget();
				return;
			}

			// Fallback to simple mode.
			const preference = sessionStorage.getItem(this.storageKey);
			if (preference === 'declined') {
				// Ensure cookie blocking is active.
				this.enableCookieBlocking();
				// Hide widget if preference already set.
				this.hideWidget();
			} else if (preference === 'accepted') {
				// Hide widget if already accepted.
				this.hideWidget();
			}
		} catch (e) {
			// sessionStorage not available, continue normally.
			console.warn('GDPR Cookie Consent: sessionStorage not available', e);
		}
	},

		/**
		 * Handle accept button click.
		 *
		 * @param {Event} e Click event.
		 */
		handleAccept: function(e) {
			e.preventDefault();
			e.stopPropagation();

			try {
				const $container = $(e.target).closest('.gdpr-cookie-consent-container');
				const categoryMode = $container.attr('data-category-mode') === 'yes';

				if (categoryMode) {
					// Category mode: save category preferences.
					const preferences = this.getCategoryPreferencesFromUI($container);
					this.saveCategoryPreferences(preferences);
					this.syncCategoryPreferencesToPHP(preferences);
				} else {
					// Simple mode: store preference in sessionStorage.
					sessionStorage.setItem(this.storageKey, 'accepted');
					this.syncPreferenceToPHP('accepted');
				}
				
				// Small delay to ensure sessionStorage is written.
				setTimeout(() => {
					// Check if popup closing is enabled and should close on accept.
					const closePopup = $container.attr('data-close-popup') === 'yes';
					const closeOnButton = $container.attr('data-close-on-button') || 'both';
					
					if (closePopup && (closeOnButton === 'both' || closeOnButton === 'accept')) {
						this.closePopup('accept', $container);
					}
					
					// Disable cookie blocking (or update based on categories).
					if (categoryMode) {
						// Re-initialize blocker with category preferences.
						if (typeof window.gdprCookieBlockerInit === 'function') {
							window.gdprCookieBlockerInit();
						}
					} else {
						this.disableCookieBlocking();
					}
					// Hide widget.
					this.hideWidget();
				}, 100);
			} catch (err) {
				console.error('GDPR Cookie Consent: Error storing preference', err);
			}
		},

		/**
		 * Handle decline button click.
		 *
		 * @param {Event} e Click event.
		 */
		handleDecline: function(e) {
			e.preventDefault();
			e.stopPropagation();

			try {
				const $container = $(e.target).closest('.gdpr-cookie-consent-container');
				const categoryMode = $container.attr('data-category-mode') === 'yes';

				// #region agent log
				fetch('http://127.0.0.1:7242/ingest/00d237e4-bec4-4982-96be-f6cd6aee5b45',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'gdpr-widget-frontend.js:150',message:'handleDecline called',data:{categoryMode:categoryMode},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch(()=>{});
				// #endregion

				if (categoryMode) {
					// Category mode: reject all non-essential categories.
					// First, update checkboxes in UI to reflect decline state.
					const categories = JSON.parse($container.attr('data-categories') || '[]');
					categories.forEach(function(cat) {
						if (!cat.required) {
							$container.find('.gdpr-category-checkbox[data-category-id="' + cat.id + '"]').prop('checked', false);
						}
					});
					
					// Now get preferences from updated UI.
					const preferences = this.getCategoryPreferencesFromUI($container);
					// Ensure all non-essential are false (in case some weren't in UI).
					categories.forEach(function(cat) {
						if (!cat.required) {
							preferences[cat.id] = false;
						} else {
							// Ensure required categories are always true.
							preferences[cat.id] = true;
						}
					});

					// #region agent log
					fetch('http://127.0.0.1:7242/ingest/00d237e4-bec4-4982-96be-f6cd6aee5b45',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'gdpr-widget-frontend.js:168',message:'Category mode preferences before save',data:{preferences:preferences,categoriesCount:categories.length},timestamp:Date.now(),sessionId:'debug-session',runId:'run2',hypothesisId:'B'})}).catch(()=>{});
					// #endregion

					this.saveCategoryPreferences(preferences);
					this.syncCategoryPreferencesToPHP(preferences);

					// #region agent log
					fetch('http://127.0.0.1:7242/ingest/00d237e4-bec4-4982-96be-f6cd6aee5b45',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'gdpr-widget-frontend.js:172',message:'Category preferences saved and synced',data:{preferences:preferences},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'B'})}).catch(()=>{});
					// #endregion
					
					// Immediately delete cookies for rejected categories.
					if (typeof window.gdprCookieBlockerInit === 'function') {
						window.gdprCookieBlockerInit();
					}
				} else {
					// Simple mode: store preference in sessionStorage.
					sessionStorage.setItem(this.storageKey, 'declined');
					this.syncPreferenceToPHP('declined');

					// #region agent log
					fetch('http://127.0.0.1:7242/ingest/00d237e4-bec4-4982-96be-f6cd6aee5b45',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'gdpr-widget-frontend.js:177',message:'Simple mode preference saved',data:{storageKey:this.storageKey},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'C'})}).catch(()=>{});
					// #endregion
					
					// Immediately enable cookie blocking.
					this.enableCookieBlocking();
				}
				
				// Small delay to ensure sessionStorage is written.
				setTimeout(() => {
					// Check if popup closing is enabled and should close on decline.
					const closePopup = $container.attr('data-close-popup') === 'yes';
					const closeOnButton = $container.attr('data-close-on-button') || 'both';
					
					if (closePopup && (closeOnButton === 'both' || closeOnButton === 'decline')) {
						this.closePopup('decline', $container);
					}
					
					// Hide widget.
					this.hideWidget();
					
					// Set reload flag to prevent blocking activation during reload.
					sessionStorage.setItem('gdpr_reloading', 'true');
					
					// Reload page to apply blocking and delete existing cookies.
					// Use a small delay to ensure sessionStorage is written.
					setTimeout(function() {
						window.location.reload();
					}, 50);
				}, 100);
			} catch (err) {
				console.error('GDPR Cookie Consent: Error storing preference', err);
			}
		},

		/**
		 * Enable cookie blocking.
		 */
		enableCookieBlocking: function() {
			// Trigger re-initialization of cookie blocking.
			if (typeof window.gdprCookieBlockerInit === 'function') {
				window.gdprCookieBlockerInit();
			}
			// Also delete all existing cookies immediately.
			this.deleteAllCookies();
		},

		/**
		 * Delete all cookies.
		 */
		deleteAllCookies: function() {
			try {
				const cookies = document.cookie.split(';');
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
				const domains = ['', hostname, '.' + hostname, window.location.host];
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
				
				// Specifically target sbjs_ cookies (Sourcebuster/WooCommerce).
				this.deleteCookiesByPrefix('sbjs_');
			} catch (e) {
				// Silently fail.
			}
		},

		/**
		 * Delete cookies by prefix.
		 *
		 * @param {string} prefix Cookie name prefix.
		 */
		deleteCookiesByPrefix: function(prefix) {
			try {
				const cookies = document.cookie.split(';');
				const hostname = window.location.hostname;
				const paths = ['/', window.location.pathname];
				const domains = ['', hostname, '.' + hostname, window.location.host];
				
				for (let i = 0; i < cookies.length; i++) {
					const cookie = cookies[i];
					const eqPos = cookie.indexOf('=');
					const name = eqPos > -1 ? cookie.substr(0, eqPos).trim() : cookie.trim();
					
				if (name && name.indexOf(prefix) === 0) {
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
		},

		/**
		 * Disable cookie blocking.
		 */
		disableCookieBlocking: function() {
			// Remove blocking by restoring original cookie behavior.
			// The blocker script checks sessionStorage, so updating it should be enough.
			// If needed, we can restore the original cookie descriptor here.
		},

		/**
		 * Get AJAX URL for WordPress admin-ajax.php.
		 *
		 * @return {string} AJAX URL.
		 */
		getAjaxUrl: function() {
			// Check for localized script variable.
			if (typeof gdprCookieConsent !== 'undefined' && gdprCookieConsent.ajaxurl) {
				return gdprCookieConsent.ajaxurl;
			}
			
			// Check for WordPress admin ajaxurl (available in admin).
			if (typeof ajaxurl !== 'undefined') {
				return ajaxurl;
			}
			
			// Construct URL from current page.
			const protocol = window.location.protocol === 'https:' ? 'https://' : 'http://';
			const host = window.location.host;
			return protocol + host + '/wp-admin/admin-ajax.php';
		},

		/**
		 * Sync preference to PHP via AJAX (hybrid mode).
		 *
		 * @param {string} preference Preference value ('accepted' or 'declined').
		 */
		syncPreferenceToPHP: function(preference) {
			// Get nonce from widget container or generate one.
			const $container = $('.gdpr-cookie-consent-container').first();
			const nonce = $container.attr('data-nonce') || '';
			
			// Get AJAX URL.
			const ajaxUrl = this.getAjaxUrl();
			
			// Sync preference to PHP via AJAX using the accept/decline endpoints.
			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				data: {
					action: 'gdpr_consent_' + preference,
					nonce: nonce
				},
				success: function(response) {
					if (response.success) {
						// Preference synced successfully.
					}
				},
				error: function() {
					// Silently fail - JavaScript blocking will still work.
				}
			});
			
			// Also call the get_preference endpoint to ensure sync.
			// This endpoint accepts the preference directly (no nonce required for sync).
			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				data: {
					action: 'gdpr_get_preference',
					preference: preference,
					nonce: nonce
				},
				success: function(response) {
					if (response.success) {
						// Preference synced successfully.
					}
				},
				error: function() {
					// Silently fail.
				}
			});
		},

		/**
		 * Hide the widget.
		 */
		hideWidget: function() {
			$('.gdpr-cookie-consent-container').fadeOut(300, function() {
				$(this).css('display', 'none');
			});
		},

		/**
		 * Check if widget is inside a popup.
		 *
		 * @param {jQuery} $element jQuery element to check.
		 * @return {Object} Popup context object.
		 */
		isInsidePopup: function($element) {
			const result = {
				isInside: false,
				popupId: null,
				popupInstance: null,
				popupType: null,
				$popupDocument: null
			};

			if (!$element || !$element.length) {
				return result;
			}

			// Check for Elementor Pro popup.
			const $popupModal = $element.closest('.elementor-popup-modal');
			if ($popupModal.length) {
				result.isInside = true;
				result.popupType = 'elementor-pro';
				
				// Try to find the popup document element (with data-elementor-type="popup").
				const $popupDocument = $element.closest('[data-elementor-type="popup"]');
				if ($popupDocument.length) {
					result.$popupDocument = $popupDocument;
					result.popupId = $popupDocument.data('elementorId');
				}
				
				// Fallback: Try to extract popup ID from modal ID.
				if (!result.popupId) {
					const modalId = $popupModal.attr('id');
					if (modalId && modalId.indexOf('elementor-popup-modal-') === 0) {
						result.popupId = modalId.replace('elementor-popup-modal-', '');
					}
				}
			}

			return result;
		},

		/**
		 * Check if Elementor Pro popup is available.
		 *
		 * @return {boolean} True if Elementor Pro popup module is available.
		 */
		isElementorProPopup: function() {
			return typeof elementorProFrontend !== 'undefined' &&
				elementorProFrontend.modules &&
				elementorProFrontend.modules.popup &&
				typeof elementorProFrontend.modules.popup.closePopup === 'function';
		},

		/**
		 * Close Elementor Pro popup.
		 *
		 * @param {string} buttonType Button type ('accept' or 'decline').
		 * @param {jQuery} $container Widget container element.
		 * @return {boolean} True if popup was closed successfully.
		 */
		closeElementorPopup: function(buttonType, $container) {
			// Check if Elementor Pro popup module is available.
			if (!this.isElementorProPopup()) {
				console.warn('GDPR Cookie Consent: Elementor Pro popup module not available');
				return false;
			}

			// Check if widget is inside a popup.
			const popupContext = this.isInsidePopup($container);
			if (!popupContext.isInside || popupContext.popupType !== 'elementor-pro') {
				console.warn('GDPR Cookie Consent: Widget is not inside a popup');
				return false;
			}

			try {
				// Method 1: Direct access via documentsManager (preferred).
				if (popupContext.popupId && typeof elementorFrontend !== 'undefined' && 
					elementorFrontend.documentsManager && 
					elementorFrontend.documentsManager.documents) {
					
					const popupDocument = elementorFrontend.documentsManager.documents[popupContext.popupId];
					if (popupDocument && popupDocument.getModal && typeof popupDocument.getModal === 'function') {
						const modal = popupDocument.getModal();
						if (modal && typeof modal.hide === 'function') {
							modal.hide();
							return true;
						}
					}
				}

				// Method 2: Use closePopup with fake event object.
				if (popupContext.$popupDocument && popupContext.$popupDocument.length) {
					const popupModule = elementorProFrontend.modules.popup;
					const settings = {
						do_not_show_again: false
					};
					
					// Create a fake event object with target pointing to popup document.
					const fakeEvent = {
						target: popupContext.$popupDocument[0]
					};
					
					popupModule.closePopup(settings, fakeEvent);
					return true;
				}

				// Method 3: Try to find popup document by traversing DOM.
				const $popupDocument = $container.closest('[data-elementor-type="popup"]');
				if ($popupDocument.length) {
					const popupId = $popupDocument.data('elementorId');
					if (popupId && elementorFrontend.documentsManager && elementorFrontend.documentsManager.documents) {
						const popupDocument = elementorFrontend.documentsManager.documents[popupId];
						if (popupDocument && popupDocument.getModal) {
							const modal = popupDocument.getModal();
							if (modal && modal.hide) {
								modal.hide();
								return true;
							}
						}
					}
				}

				console.warn('GDPR Cookie Consent: Could not close popup - no valid method found');
				return false;
			} catch (e) {
				console.error('GDPR Cookie Consent: Error closing popup', e);
				return false;
			}
		},

		/**
		 * Close popup (abstraction layer for future extensibility).
		 *
		 * @param {string} buttonType Button type ('accept' or 'decline').
		 * @param {jQuery} $container Widget container element.
		 * @return {boolean} True if popup was closed successfully.
		 */
		closePopup: function(buttonType, $container) {
			// Check which popup system.
			if (this.isElementorProPopup()) {
				return this.closeElementorPopup(buttonType, $container);
			}
			// Future: else if (this.isOtherPopupSystem()) { ... }
			return false;
		},

		/**
		 * Check popup context and cache result.
		 *
		 * @param {jQuery} $container Widget container element.
		 */
		checkPopupContext: function($container) {
			if (!$container || !$container.length) {
				return;
			}
			this.popupContext = this.isInsidePopup($container);
		},

		/**
		 * Initialize category mode.
		 */
		initCategoryMode: function() {
			$('.gdpr-cookie-consent-container[data-category-mode="yes"]').each(function() {
				const $container = $(this);
				const preferences = GDPRWidgetHandler.getCategoryPreferences();
				if (preferences && Object.keys(preferences).length > 0) {
					// Restore checkbox states from preferences.
					$container.find('.gdpr-category-checkbox').each(function() {
						const categoryId = $(this).data('category-id');
						$(this).prop('checked', preferences[categoryId] === true);
					});
				}
			});
		},

		/**
		 * Handle category toggle.
		 *
		 * @param {Event} e Change event.
		 */
		handleCategoryToggle: function(e) {
			const $checkbox = $(e.target);
			const categoryId = $checkbox.data('category-id');
			const checked = $checkbox.is(':checked');
			const $container = $checkbox.closest('.gdpr-cookie-consent-container');
			
			// Update preferences immediately.
			const preferences = this.getCategoryPreferencesFromUI($container);
			preferences[categoryId] = checked;
			this.saveCategoryPreferences(preferences);
		},

		/**
		 * Handle accept all button click.
		 *
		 * @param {Event} e Click event.
		 */
		handleAcceptAll: function(e) {
			e.preventDefault();
			const $container = $(e.target).closest('.gdpr-cookie-consent-container');
			$container.find('.gdpr-category-checkbox').not(':disabled').prop('checked', true);
			this.handleCategoryToggle({target: $container.find('.gdpr-category-checkbox').first()[0]});
		},

		/**
		 * Handle reject all button click.
		 *
		 * @param {Event} e Click event.
		 */
		handleRejectAll: function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			const $container = $(e.target).closest('.gdpr-cookie-consent-container');
			const categoryMode = $container.attr('data-category-mode') === 'yes';
			
			if (categoryMode) {
				// Category mode: reject all non-essential categories.
				const categories = JSON.parse($container.attr('data-categories') || '[]');
				categories.forEach(function(cat) {
					if (!cat.required) {
						$container.find('.gdpr-category-checkbox[data-category-id="' + cat.id + '"]').prop('checked', false);
					}
				});
				
				// Get preferences from updated UI.
				const preferences = this.getCategoryPreferencesFromUI($container);
				// Ensure all non-essential are false.
				categories.forEach(function(cat) {
					if (!cat.required) {
						preferences[cat.id] = false;
					} else {
						// Ensure required categories are always true.
						preferences[cat.id] = true;
					}
				});
				
				// Save preferences.
				this.saveCategoryPreferences(preferences);
				this.syncCategoryPreferencesToPHP(preferences);
				
				// Immediately re-initialize blocking.
				if (typeof window.gdprCookieBlockerInit === 'function') {
					window.gdprCookieBlockerInit();
				}
				
				// Set reload flag and reload page to apply blocking.
				sessionStorage.setItem('gdpr_reloading', 'true');
				setTimeout(() => {
					window.location.reload();
				}, 50);
			} else {
				// Simple mode: just uncheck all (shouldn't happen in simple mode, but handle it).
				$container.find('.gdpr-category-checkbox').not(':disabled').prop('checked', false);
				this.handleCategoryToggle({target: $container.find('.gdpr-category-checkbox').first()[0]});
			}
		},

		/**
		 * Get category preferences from UI.
		 *
		 * @param {jQuery} $container Container element.
		 * @return {Object} Preferences object.
		 */
		getCategoryPreferencesFromUI: function($container) {
			const preferences = {};
			$container.find('.gdpr-category-checkbox').each(function() {
				const categoryId = $(this).data('category-id');
				preferences[categoryId] = $(this).is(':checked');
			});
			return preferences;
		},

		/**
		 * Get category preferences from storage.
		 *
		 * @return {Object|null} Preferences object or null.
		 */
		getCategoryPreferences: function() {
			try {
				const stored = sessionStorage.getItem(this.categoryStorageKey);
				if (stored) {
					return JSON.parse(stored);
				}
			} catch (e) {
				console.warn('GDPR Cookie Consent: Error reading category preferences', e);
			}
			return null;
		},

		/**
		 * Save category preferences to storage.
		 *
		 * @param {Object} preferences Preferences object.
		 */
		saveCategoryPreferences: function(preferences) {
			try {
				sessionStorage.setItem(this.categoryStorageKey, JSON.stringify(preferences));
			} catch (e) {
				console.error('GDPR Cookie Consent: Error saving category preferences', e);
			}
		},

		/**
		 * Sync category preferences to PHP via AJAX.
		 *
		 * @param {Object} preferences Preferences object.
		 */
		syncCategoryPreferencesToPHP: function(preferences) {
			const $container = $('.gdpr-cookie-consent-container').first();
			const nonce = $container.attr('data-nonce') || '';
			const ajaxUrl = this.getAjaxUrl();

			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				data: {
					action: 'gdpr_save_category_preferences',
					nonce: nonce,
					preferences: preferences
				},
				success: function(response) {
					if (response.success) {
						// Preferences synced successfully.
					}
				},
				error: function() {
					// Silently fail - JavaScript blocking will still work.
				}
			});
		},

		/**
		 * Delete cookies for rejected categories.
		 *
		 * @param {Object} preferences Category preferences.
		 */
		deleteCookiesForRejectedCategories: function(preferences) {
			// This would need category-to-cookie mapping to work properly.
			// For now, just trigger blocker re-init.
			if (typeof window.gdprCookieBlockerInit === 'function') {
				window.gdprCookieBlockerInit();
			}
		},

		/**
		 * Handle customize button click.
		 *
		 * @param {Event} e Click event.
		 */
		handleCustomizeClick: function(e) {
			e.preventDefault();
			e.stopPropagation();

			const $container = $(e.target).closest('.gdpr-cookie-consent-container');
			this.openPreferencesModal($container);
		},

		/**
		 * Open preferences modal.
		 *
		 * @param {jQuery} $container Widget container element.
		 */
		openPreferencesModal: function($container) {
			const $modal = $container.find('.gdpr-preferences-modal-overlay');
			if (!$modal.length) {
				return;
			}

			// Store reference to container for later use.
			$modal.data('container', $container);

			// Show modal with animation.
			$modal.fadeIn(300);

			// Prevent body scroll.
			$('body').addClass('gdpr-modal-open');

			// Populate categories from data attributes.
			this.populateModalCategories($container);

			// Restore saved preferences if available.
			const savedPrefs = this.getCategoryPreferences();
			if (savedPrefs && Object.keys(savedPrefs).length > 0) {
				$modal.find('.gdpr-modal-category-checkbox').each(function() {
					const categoryId = $(this).data('category-id');
					const isRequired = $(this).prop('disabled');
					if (!isRequired && savedPrefs[categoryId] !== undefined) {
						$(this).prop('checked', savedPrefs[categoryId]);
					}
				});
			}

			// Set focus to first interactive element (close button or first checkbox).
			const $firstFocusable = $modal.find('.gdpr-modal-close, .gdpr-modal-category-checkbox:not(:disabled)').first();
			if ($firstFocusable.length) {
				setTimeout(function() {
					$firstFocusable.focus();
				}, 100);
			}

			// Store original active element for focus return.
			$modal.data('previousFocus', document.activeElement);

			// Trap focus within modal.
			this.trapFocusInModal($modal);
		},

		/**
		 * Handle modal close button click.
		 *
		 * @param {Event} e Click event.
		 */
		handleModalClose: function(e) {
			e.preventDefault();
			e.stopPropagation();

			const $modal = $(e.target).closest('.gdpr-preferences-modal-overlay');
			this.closePreferencesModal($modal);
		},

		/**
		 * Handle overlay click (close modal when clicking outside).
		 *
		 * @param {Event} e Click event.
		 */
		handleModalOverlayClick: function(e) {
			// Only close if clicking directly on overlay, not modal content.
			// Check if the click target is the overlay itself, not a child element.
			if ($(e.target).is('.gdpr-preferences-modal-overlay')) {
				e.preventDefault();
				e.stopPropagation();

				const $modal = $(e.target);
				this.closePreferencesModal($modal);
			}
		},

		/**
		 * Close preferences modal.
		 *
		 * @param {jQuery} $modal Modal overlay element.
		 */
		closePreferencesModal: function($modal) {
			if (!$modal || !$modal.length) {
				return;
			}

			// Hide modal with animation.
			$modal.fadeOut(300);

			// Restore body scroll.
			$('body').removeClass('gdpr-modal-open');

			// Return focus to previous element or customize button.
			const $previousFocus = $modal.data('previousFocus');
			if ($previousFocus && $previousFocus.length && $previousFocus.is(':visible')) {
				setTimeout(function() {
					$previousFocus.focus();
				}, 100);
			} else {
				const $container = $modal.data('container');
				if ($container && $container.length) {
					const $customizeButton = $container.find('.gdpr-cookie-consent-customize-button');
					if ($customizeButton.length) {
						setTimeout(function() {
							$customizeButton.focus();
						}, 100);
					}
				}
			}

			// Remove focus trap.
			this.removeFocusTrap();
		},

		/**
		 * Handle keyboard events in modal.
		 *
		 * @param {Event} e Keydown event.
		 */
		handleModalKeydown: function(e) {
			const $modal = $(e.target).closest('.gdpr-preferences-modal-overlay');
			if (!$modal.length || $modal.css('display') === 'none') {
				return;
			}

			// Close on Escape key.
			if (e.key === 'Escape' || e.keyCode === 27) {
				e.preventDefault();
				this.closePreferencesModal($modal);
				return;
			}

			// Trap Tab key within modal.
			if (e.key === 'Tab' || e.keyCode === 9) {
				this.handleModalTabKey($modal, e);
			}
		},

		/**
		 * Handle Tab key navigation in modal (focus trap).
		 *
		 * @param {jQuery} $modal Modal overlay element.
		 * @param {Event} e Keydown event.
		 */
		handleModalTabKey: function($modal, e) {
			const $focusableElements = $modal.find(
				'button, [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
			).filter(':visible');

			if ($focusableElements.length === 0) {
				return;
			}

			const $firstElement = $focusableElements.first();
			const $lastElement = $focusableElements.last();

			if (e.shiftKey) {
				// Shift + Tab
				if (document.activeElement === $firstElement[0]) {
					e.preventDefault();
					$lastElement.focus();
				}
			} else {
				// Tab
				if (document.activeElement === $lastElement[0]) {
					e.preventDefault();
					$firstElement.focus();
				}
			}
		},

		/**
		 * Trap focus within modal.
		 *
		 * @param {jQuery} $modal Modal overlay element.
		 */
		trapFocusInModal: function($modal) {
			// Store original tab handler if not already stored.
			if (!this.originalTabHandler) {
				this.originalTabHandler = $(document).off('keydown.gdprModal');
			}

			// Focus trap is handled in handleModalKeydown.
		},

		/**
		 * Remove focus trap.
		 */
		removeFocusTrap: function() {
			// Focus trap removal handled automatically when modal closes.
		},

		/**
		 * Populate modal categories from data attributes.
		 *
		 * @param {jQuery} $container Widget container element.
		 */
		populateModalCategories: function($container) {
			// Categories are already in the HTML, just ensure they're properly initialized.
			const categoriesData = $container.attr('data-categories');
			if (categoriesData) {
				try {
					const categories = JSON.parse(categoriesData);
					// Categories are already rendered in HTML, this is for future extensibility.
				} catch (e) {
					console.warn('GDPR Cookie Consent: Error parsing categories data', e);
				}
			}
		},

		/**
		 * Handle category toggle in modal.
		 *
		 * @param {Event} e Change event.
		 */
		handleModalCategoryToggle: function(e) {
			const $checkbox = $(e.target);
			const categoryId = $checkbox.data('category-id');
			const checked = $checkbox.is(':checked');
			const $modal = $checkbox.closest('.gdpr-preferences-modal-overlay');
			const $container = $modal.data('container');

			// Update preferences immediately (for visual feedback).
			// Actual save happens on "Save Preferences" click.
		},

		/**
		 * Handle Accept All in modal.
		 *
		 * @param {Event} e Click event.
		 */
		handleModalAcceptAll: function(e) {
			e.preventDefault();
			e.stopPropagation();

			const $modal = $(e.target).closest('.gdpr-preferences-modal-overlay');
			const $container = $modal.data('container');

			// Enable all non-disabled checkboxes.
			$modal.find('.gdpr-modal-category-checkbox:not(:disabled)').prop('checked', true);

			// Collect preferences from modal.
			const preferences = {};
			$modal.find('.gdpr-modal-category-checkbox').each(function() {
				const categoryId = $(this).data('category-id');
				const isRequired = $(this).prop('disabled');
				// Essential categories are always enabled.
				preferences[categoryId] = isRequired || $(this).is(':checked');
			});

			// Validate: ensure essential categories are enabled.
			const categoriesData = $container.attr('data-categories');
			if (categoriesData) {
				try {
					const categories = JSON.parse(categoriesData);
					categories.forEach(function(cat) {
						if (cat.required) {
							preferences[cat.id] = true;
						}
					});
				} catch (e) {
					console.warn('GDPR Cookie Consent: Error parsing categories', e);
				}
			}

			// Save preferences.
			this.saveCategoryPreferences(preferences);
			this.syncCategoryPreferencesToPHP(preferences);

			// Immediately re-initialize blocking.
			if (typeof window.gdprCookieBlockerInit === 'function') {
				window.gdprCookieBlockerInit();
			}

			// Show confirmation message.
			this.showConfirmationMessage($container);

			// Close modal after delay.
			setTimeout(() => {
				this.closePreferencesModal($modal);
				
				// Small delay before hiding notice to allow modal close animation.
				setTimeout(() => {
					// Hide notice.
					this.hideWidget();
					
					// Check if popup closing is enabled.
					const closePopup = $container.attr('data-close-popup') === 'yes';
					if (closePopup) {
						this.closePopup('accept-all', $container);
					}
					
					// Set reload flag and reload page to apply blocking.
					sessionStorage.setItem('gdpr_reloading', 'true');
					setTimeout(function() {
						window.location.reload();
					}, 50);
				}, 300);
			}, 2000);
		},

		/**
		 * Handle Reject All in modal.
		 *
		 * @param {Event} e Click event.
		 */
		handleModalRejectAll: function(e) {
			e.preventDefault();
			e.stopPropagation();

			const $modal = $(e.target).closest('.gdpr-preferences-modal-overlay');
			const $container = $modal.data('container');
			
			// Disable all non-essential categories (keep essential enabled).
			$modal.find('.gdpr-modal-category-checkbox:not(:disabled)').prop('checked', false);

			// Collect preferences from modal.
			const preferences = {};
			$modal.find('.gdpr-modal-category-checkbox').each(function() {
				const categoryId = $(this).data('category-id');
				const isRequired = $(this).prop('disabled');
				// Essential categories are always enabled.
				preferences[categoryId] = isRequired || $(this).is(':checked');
			});

			// Validate: ensure essential categories are enabled.
			const categoriesData = $container.attr('data-categories');
			if (categoriesData) {
				try {
					const categories = JSON.parse(categoriesData);
					categories.forEach(function(cat) {
						if (cat.required) {
							preferences[cat.id] = true;
						} else {
							// Ensure all non-essential are false.
							preferences[cat.id] = false;
						}
					});
				} catch (e) {
					console.warn('GDPR Cookie Consent: Error parsing categories', e);
				}
			}

			// Save preferences.
			this.saveCategoryPreferences(preferences);
			this.syncCategoryPreferencesToPHP(preferences);

			// Immediately re-initialize blocking.
			if (typeof window.gdprCookieBlockerInit === 'function') {
				window.gdprCookieBlockerInit();
			}

			// Show confirmation message.
			this.showConfirmationMessage($container);

			// Close modal after delay.
			setTimeout(() => {
				this.closePreferencesModal($modal);
				
				// Small delay before hiding notice to allow modal close animation.
				setTimeout(() => {
					// Hide notice.
					this.hideWidget();
					
					// Check if popup closing is enabled.
					const closePopup = $container.attr('data-close-popup') === 'yes';
					if (closePopup) {
						this.closePopup('reject-all', $container);
					}
					
					// Set reload flag and reload page to apply blocking.
					sessionStorage.setItem('gdpr_reloading', 'true');
					setTimeout(function() {
						window.location.reload();
					}, 50);
				}, 300);
			}, 2000);
		},

		/**
		 * Handle Save Preferences in modal.
		 *
		 * @param {Event} e Click event.
		 */
		handleModalSavePreferences: function(e) {
			e.preventDefault();
			e.stopPropagation();

			const $button = $(e.target);
			const $modal = $button.closest('.gdpr-preferences-modal-overlay');
			const $container = $modal.data('container');

			// Collect preferences from modal.
			const preferences = {};
			$modal.find('.gdpr-modal-category-checkbox').each(function() {
				const categoryId = $(this).data('category-id');
				const isRequired = $(this).prop('disabled');
				// Essential categories are always enabled.
				preferences[categoryId] = isRequired || $(this).is(':checked');
			});

			// Validate: ensure essential categories are enabled.
			const categoriesData = $container.attr('data-categories');
			if (categoriesData) {
				try {
					const categories = JSON.parse(categoriesData);
					categories.forEach(function(cat) {
						if (cat.required) {
							preferences[cat.id] = true;
						}
					});
				} catch (e) {
					console.warn('GDPR Cookie Consent: Error parsing categories', e);
				}
			}

			// Disable button during save.
			$button.prop('disabled', true).text('Saving...');

			// Save preferences.
			this.saveCategoryPreferences(preferences);
			this.syncCategoryPreferencesToPHP(preferences);

			// Immediately delete cookies for rejected categories and re-initialize blocking.
			if (typeof window.gdprCookieBlockerInit === 'function') {
				window.gdprCookieBlockerInit();
			}

			// Show confirmation message.
			this.showConfirmationMessage($container);

			// Close modal after delay.
			setTimeout(() => {
				this.closePreferencesModal($modal);
				
				// Small delay before hiding notice to allow modal close animation.
				setTimeout(() => {
					// Hide notice.
					this.hideWidget();
					
					// Check if popup closing is enabled.
					const closePopup = $container.attr('data-close-popup') === 'yes';
					if (closePopup) {
						this.closePopup('customize', $container);
					}
				}, 300);
			}, 2000);
		},

		/**
		 * Show confirmation message.
		 *
		 * @param {jQuery} $container Widget container element.
		 */
		showConfirmationMessage: function($container) {
			const $message = $container.find('.gdpr-confirmation-message');
			if ($message.length) {
				// Show message.
				$message.css('display', 'block');
				
				// Announce to screen readers.
				$message.attr('aria-live', 'polite');
				
				// Auto-hide after 2 seconds.
				setTimeout(() => {
					$message.css('display', 'none');
				}, 2000);
			}
		}
	};

	// Initialize when DOM is ready.
	$(document).ready(function() {
		GDPRWidgetHandler.init();
		
		// Check popup context for all widgets on page.
		$('.gdpr-cookie-consent-container').each(function() {
			GDPRWidgetHandler.checkPopupContext($(this));
		});
	});

	// Also initialize on Elementor frontend if available.
	if (typeof elementorFrontend !== 'undefined') {
		elementorFrontend.hooks.addAction('frontend/element_ready/gdpr-cookie-consent.default', function($scope) {
			GDPRWidgetHandler.init();
			
			// Check popup context for this specific widget.
			const $container = $scope.find('.gdpr-cookie-consent-container');
			if ($container.length) {
				GDPRWidgetHandler.checkPopupContext($container);
			}
		});
	}

})(jQuery);

