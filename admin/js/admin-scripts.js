/**
 * Admin Scripts
 *
 * @package GDPR_Cookie_Consent_Elementor
 */

(function($) {
	'use strict';

	// Tab switching.
	$('.gdpr-admin-tabs .nav-tab').on('click', function(e) {
		e.preventDefault();
		var target = $(this).attr('href');
		
		$('.nav-tab').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');
		
		$('.tab-content').removeClass('active');
		$(target).addClass('active');
	});

	// Assign category to cookie.
	$('.assign-category').on('change', function() {
		var $select = $(this);
		var cookieKey = $select.data('cookie-key');
		var categoryId = $select.val();

		$.ajax({
			url: gdprAdmin.ajaxurl,
			type: 'POST',
			data: {
				action: 'gdpr_assign_category_to_cookie',
				nonce: gdprAdmin.nonce,
				cookie_key: cookieKey,
				category_id: categoryId
			},
			success: function(response) {
				if (response.success) {
					// Update UI or show success message.
					console.log('Category assigned');
				} else {
					alert('Error: ' + response.data.message);
				}
			},
			error: function() {
				alert('An error occurred.');
			}
		});
	});

	// Delete category.
	$('.delete-category').on('click', function() {
		if (!confirm('Are you sure you want to delete this category?')) {
			return;
		}

		var categoryId = $(this).data('id');

		$.ajax({
			url: gdprAdmin.ajaxurl,
			type: 'POST',
			data: {
				action: 'gdpr_delete_category',
				nonce: gdprAdmin.nonce,
				id: categoryId
			},
			success: function(response) {
				if (response.success) {
					location.reload();
				} else {
					alert('Error: ' + response.data.message);
				}
			},
			error: function() {
				alert('An error occurred.');
			}
		});
	});

	// Delete mapping.
	$('.delete-mapping').on('click', function() {
		if (!confirm('Are you sure you want to delete this mapping?')) {
			return;
		}

		var index = $(this).data('index');

		$.ajax({
			url: gdprAdmin.ajaxurl,
			type: 'POST',
			data: {
				action: 'gdpr_delete_mapping',
				nonce: gdprAdmin.nonce,
				index: index
			},
			success: function(response) {
				if (response.success) {
					location.reload();
				} else {
					alert('Error: ' + response.data.message);
				}
			},
			error: function() {
				alert('An error occurred.');
			}
		});
	});

	// Get category data from table row data attributes.
	function getCategoryData(categoryId) {
		var $row = $('tr[data-category-id="' + categoryId + '"]');
		if ($row.length) {
			return {
				id: $row.data('category-id'),
				name: $row.data('category-name'),
				description: $row.data('category-description'),
				required: $row.data('category-required') === 1 || $row.data('category-required') === '1',
				default_enabled: $row.data('category-default-enabled') === 1 || $row.data('category-default-enabled') === '1',
				order: parseInt($row.data('category-order')) || 0
			};
		}
		return null;
	}

	// Get mapping data from table row data attributes.
	function getMappingData(index) {
		var $row = $('tr[data-mapping-index="' + index + '"]');
		if ($row.length) {
			return {
				pattern: $row.data('mapping-pattern'),
				domain: $row.data('mapping-domain') || '',
				path: $row.data('mapping-path') || '',
				category: $row.data('mapping-category'),
				priority: parseInt($row.data('mapping-priority')) || 10
			};
		}
		return null;
	}

	// Open category modal for adding.
	$('#add-category').on('click', function(e) {
		e.preventDefault();
		openCategoryModal(null);
	});

	// Open category modal for editing.
	$(document).on('click', '.edit-category', function(e) {
		e.preventDefault();
		var categoryId = $(this).data('id');
		openCategoryModal(categoryId);
	});

	// Open mapping modal for adding.
	$('#add-mapping').on('click', function(e) {
		e.preventDefault();
		openMappingModal(null);
	});

	// Open mapping modal for editing.
	$(document).on('click', '.edit-mapping', function(e) {
		e.preventDefault();
		var index = $(this).data('index');
		openMappingModal(index);
	});

	// Save category.
	$('#gdpr-category-save').on('click', function() {
		var $button = $(this);
		var formData = {
			id: $('#category-id').val(),
			name: $('#category-name').val().trim(),
			description: $('#category-description').val().trim(),
			required: $('#category-required').is(':checked') ? 1 : 0,
			default_enabled: $('#category-default-enabled').is(':checked') ? 1 : 0,
			order: parseInt($('#category-order').val()) || 0
		};

		if (!formData.name) {
			alert('Category name is required.');
			$('#category-name').focus();
			return;
		}

		// Disable button during save.
		$button.prop('disabled', true).text('Saving...');

		$.ajax({
			url: gdprAdmin.ajaxurl,
			type: 'POST',
			data: {
				action: 'gdpr_save_category',
				nonce: gdprAdmin.nonce,
				id: formData.id,
				name: formData.name,
				description: formData.description,
				required: formData.required,
				default_enabled: formData.default_enabled,
				order: formData.order
			},
			success: function(response) {
				if (response.success) {
					location.reload();
				} else {
					alert('Error: ' + response.data.message);
				}
			},
			error: function() {
				alert('An error occurred.');
			}
		});
	});

	// Save mapping.
	$('#gdpr-mapping-save').on('click', function() {
		var $button = $(this);
		var formData = {
			index: $('#mapping-index').val(),
			pattern: $('#mapping-pattern').val().trim(),
			domain: $('#mapping-domain').val().trim(),
			path: $('#mapping-path').val().trim(),
			category: $('#mapping-category').val(),
			priority: parseInt($('#mapping-priority').val()) || 10
		};

		if (!formData.pattern) {
			alert('Cookie pattern is required.');
			$('#mapping-pattern').focus();
			return;
		}

		if (!formData.category) {
			alert('Category is required.');
			$('#mapping-category').focus();
			return;
		}

		// Disable button during save.
		$button.prop('disabled', true).text('Saving...');

		$.ajax({
			url: gdprAdmin.ajaxurl,
			type: 'POST',
			data: {
				action: 'gdpr_save_mapping',
				nonce: gdprAdmin.nonce,
				index: formData.index !== '' ? formData.index : -1,
				pattern: formData.pattern,
				domain: formData.domain,
				path: formData.path,
				category: formData.category,
				priority: formData.priority
			},
			success: function(response) {
				if (response.success) {
					location.reload();
				} else {
					alert('Error: ' + (response.data && response.data.message ? response.data.message : 'Failed to save mapping.'));
					$button.prop('disabled', false).text('Save Mapping');
				}
			},
			error: function() {
				alert('An error occurred while saving the mapping.');
				$button.prop('disabled', false).text('Save Mapping');
			}
		});
	});

	// Close modals.
	$('.gdpr-modal-close, #gdpr-category-cancel, #gdpr-mapping-cancel').on('click', function() {
		closeModals();
	});

	// Close modal when clicking outside.
	$(window).on('click', function(e) {
		if ($(e.target).hasClass('gdpr-modal')) {
			closeModals();
		}
	});

	// Open category modal.
	function openCategoryModal(categoryId) {
		$('#gdpr-category-modal').fadeIn(200);
		
		if (categoryId) {
			// Edit mode - get data from row.
			var category = getCategoryData(categoryId);
			if (category) {
				$('#gdpr-category-modal-title').text('Edit Category');
				$('#category-id').val(category.id);
				$('#category-name').val(category.name);
				$('#category-description').val(category.description);
				$('#category-required').prop('checked', category.required);
				$('#category-default-enabled').prop('checked', category.default_enabled);
				$('#category-order').val(category.order);
			}
		} else {
			// Add mode.
			$('#gdpr-category-modal-title').text('Add Category');
			$('#gdpr-category-form')[0].reset();
			$('#category-id').val('');
			$('#category-required').prop('checked', false);
			$('#category-default-enabled').prop('checked', false);
			$('#category-order').val('0');
		}
	}

	// Open mapping modal.
	function openMappingModal(index) {
		$('#gdpr-mapping-modal').fadeIn(200);
		
		if (index !== undefined && index !== null && index !== '') {
			// Edit mode - get data from row.
			var mapping = getMappingData(index);
			if (mapping) {
				$('#gdpr-mapping-modal-title').text('Edit Cookie Mapping');
				$('#mapping-index').val(index);
				$('#mapping-pattern').val(mapping.pattern);
				$('#mapping-domain').val(mapping.domain);
				$('#mapping-path').val(mapping.path);
				$('#mapping-category').val(mapping.category);
				$('#mapping-priority').val(mapping.priority);
			}
		} else {
			// Add mode.
			$('#gdpr-mapping-modal-title').text('Add Cookie Mapping');
			$('#gdpr-mapping-form')[0].reset();
			$('#mapping-index').val('');
			$('#mapping-priority').val('10');
		}
	}

	// Close all modals.
	function closeModals() {
		$('.gdpr-modal').fadeOut(200);
		// Reset forms.
		$('#gdpr-category-form')[0].reset();
		$('#gdpr-mapping-form')[0].reset();
		// Re-enable buttons.
		$('#gdpr-category-save, #gdpr-mapping-save').prop('disabled', false);
		$('#gdpr-category-save').text('Save Category');
		$('#gdpr-mapping-save').text('Save Mapping');
	}

	// Allow Enter key to submit forms.
	$('#gdpr-category-form, #gdpr-mapping-form').on('keypress', function(e) {
		if (e.which === 13) {
			e.preventDefault();
			if ($(this).attr('id') === 'gdpr-category-form') {
				$('#gdpr-category-save').click();
			} else {
				$('#gdpr-mapping-save').click();
			}
		}
	});

	// Refresh detected cookies list.
	$('#refresh-detected-cookies').on('click', function() {
		location.reload();
	});

	// Test cookie detection.
	$('#test-cookie-detection').on('click', function() {
		var $button = $(this);
		$button.prop('disabled', true).text('Testing...');

		// Set a test cookie via JavaScript.
		document.cookie = 'gdpr_test_cookie=test_value; path=/; max-age=60';

		// Also trigger detection via AJAX.
		$.ajax({
			url: gdprAdmin.ajaxurl,
			type: 'POST',
			data: {
				action: 'gdpr_test_detection',
				nonce: gdprAdmin.nonce
			},
			success: function(response) {
				if (response.success) {
					alert('Test cookie set. Refresh the list to see it.');
					setTimeout(function() {
						location.reload();
					}, 1000);
				} else {
					alert('Error: ' + (response.data && response.data.message ? response.data.message : 'Test failed'));
					$button.prop('disabled', false).text('Test Detection');
				}
			},
			error: function() {
				alert('An error occurred during testing.');
				$button.prop('disabled', false).text('Test Detection');
			}
		});
	});

})(jQuery);

