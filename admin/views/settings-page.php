<?php
/**
 * Admin Settings Page View
 *
 * @package GDPR_Cookie_Consent_Elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
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
					<?php if ( empty( $categories ) ) : ?>
						<tr>
							<td colspan="5"><?php esc_html_e( 'No categories found.', 'gdpr-cookie-consent-elementor' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $categories as $category ) : ?>
							<tr data-category-id="<?php echo esc_attr( $category['id'] ); ?>"
								data-category-name="<?php echo esc_attr( $category['name'] ); ?>"
								data-category-description="<?php echo esc_attr( $category['description'] ); ?>"
								data-category-required="<?php echo $category['required'] ? '1' : '0'; ?>"
								data-category-default-enabled="<?php echo $category['default_enabled'] ? '1' : '0'; ?>"
								data-category-order="<?php echo esc_attr( $category['order'] ?? 0 ); ?>">
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
					<?php endif; ?>
				</tbody>
			</table>
			<button class="button button-primary" id="add-category"><?php esc_html_e( 'Add Category', 'gdpr-cookie-consent-elementor' ); ?></button>
		</div>

		<div id="mappings" class="tab-content">
			<h2><?php esc_html_e( 'Cookie Mappings', 'gdpr-cookie-consent-elementor' ); ?></h2>
			<p><?php esc_html_e( 'Map cookie patterns to categories. Use * as wildcard.', 'gdpr-cookie-consent-elementor' ); ?></p>
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
					<?php if ( empty( $mappings ) ) : ?>
						<tr>
							<td colspan="6"><?php esc_html_e( 'No mappings found.', 'gdpr-cookie-consent-elementor' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $mappings as $index => $mapping ) : ?>
							<tr data-mapping-index="<?php echo esc_attr( $index ); ?>"
								data-mapping-pattern="<?php echo esc_attr( $mapping['pattern'] ); ?>"
								data-mapping-domain="<?php echo esc_attr( $mapping['domain'] ?? '' ); ?>"
								data-mapping-path="<?php echo esc_attr( $mapping['path'] ?? '' ); ?>"
								data-mapping-category="<?php echo esc_attr( $mapping['category'] ); ?>"
								data-mapping-priority="<?php echo esc_attr( $mapping['priority'] ?? 10 ); ?>">
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
					<?php endif; ?>
				</tbody>
			</table>
			<button class="button button-primary" id="add-mapping"><?php esc_html_e( 'Add Mapping', 'gdpr-cookie-consent-elementor' ); ?></button>
		</div>

		<div id="detected" class="tab-content">
			<h2><?php esc_html_e( 'Detected Cookies', 'gdpr-cookie-consent-elementor' ); ?></h2>
			<p><?php esc_html_e( 'Review automatically detected cookies and assign categories.', 'gdpr-cookie-consent-elementor' ); ?></p>
			<p>
				<button type="button" class="button" id="refresh-detected-cookies"><?php esc_html_e( 'Refresh List', 'gdpr-cookie-consent-elementor' ); ?></button>
				<button type="button" class="button" id="test-cookie-detection"><?php esc_html_e( 'Test Detection', 'gdpr-cookie-consent-elementor' ); ?></button>
				<span class="description" style="margin-left: 10px;"><?php esc_html_e( 'Cookies are detected automatically as they are set. Visit your site pages to trigger detection.', 'gdpr-cookie-consent-elementor' ); ?></span>
			</p>
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
					<?php if ( empty( $detected_cookies ) ) : ?>
						<tr>
							<td colspan="6"><?php esc_html_e( 'No cookies detected yet. Cookies will appear here as they are set on your site.', 'gdpr-cookie-consent-elementor' ); ?></td>
						</tr>
					<?php else : ?>
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
											<option value="<?php echo esc_attr( $category['id'] ); ?>" <?php selected( $cookie['assigned_category'] ?? '', $category['id'] ); ?>>
												<?php echo esc_html( $category['name'] ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<div id="settings" class="tab-content">
			<h2><?php esc_html_e( 'Settings', 'gdpr-cookie-consent-elementor' ); ?></h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'gdpr_cookie_categories' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="gdpr_mode"><?php esc_html_e( 'Default Mode', 'gdpr-cookie-consent-elementor' ); ?></label>
						</th>
						<td>
							<select name="gdpr_category_settings[mode]" id="gdpr_mode">
								<option value="simple" <?php selected( $settings['mode'] ?? 'simple', 'simple' ); ?>><?php esc_html_e( 'Simple (Accept/Decline)', 'gdpr-cookie-consent-elementor' ); ?></option>
								<option value="categories" <?php selected( $settings['mode'] ?? 'simple', 'categories' ); ?>><?php esc_html_e( 'Categories', 'gdpr-cookie-consent-elementor' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Default mode for new widgets. Individual widgets can override this setting.', 'gdpr-cookie-consent-elementor' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
	</div>
</div>

<!-- Category Modal -->
<div id="gdpr-category-modal" class="gdpr-modal" style="display: none;">
	<div class="gdpr-modal-content">
		<div class="gdpr-modal-header">
			<h2 id="gdpr-category-modal-title"><?php esc_html_e( 'Add Category', 'gdpr-cookie-consent-elementor' ); ?></h2>
			<span class="gdpr-modal-close">&times;</span>
		</div>
		<div class="gdpr-modal-body">
			<form id="gdpr-category-form">
				<input type="hidden" id="category-id" name="id" value="" />
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="category-name"><?php esc_html_e( 'Category Name', 'gdpr-cookie-consent-elementor' ); ?> <span class="required">*</span></label>
						</th>
						<td>
							<input type="text" id="category-name" name="name" class="regular-text" required />
							<p class="description"><?php esc_html_e( 'Display name for this category (e.g., Analytics Cookies)', 'gdpr-cookie-consent-elementor' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="category-description"><?php esc_html_e( 'Description', 'gdpr-cookie-consent-elementor' ); ?></label>
						</th>
						<td>
							<textarea id="category-description" name="description" class="large-text" rows="3"></textarea>
							<p class="description"><?php esc_html_e( 'Description shown to users explaining what this category is for', 'gdpr-cookie-consent-elementor' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="category-required"><?php esc_html_e( 'Required', 'gdpr-cookie-consent-elementor' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" id="category-required" name="required" value="1" />
								<?php esc_html_e( 'This category is required and cannot be disabled by users', 'gdpr-cookie-consent-elementor' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="category-default-enabled"><?php esc_html_e( 'Default Enabled', 'gdpr-cookie-consent-elementor' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" id="category-default-enabled" name="default_enabled" value="1" />
								<?php esc_html_e( 'Enable this category by default for new users', 'gdpr-cookie-consent-elementor' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="category-order"><?php esc_html_e( 'Display Order', 'gdpr-cookie-consent-elementor' ); ?></label>
						</th>
						<td>
							<input type="number" id="category-order" name="order" class="small-text" value="0" min="0" />
							<p class="description"><?php esc_html_e( 'Lower numbers appear first', 'gdpr-cookie-consent-elementor' ); ?></p>
						</td>
					</tr>
				</table>
			</form>
		</div>
		<div class="gdpr-modal-footer">
			<button type="button" class="button" id="gdpr-category-cancel"><?php esc_html_e( 'Cancel', 'gdpr-cookie-consent-elementor' ); ?></button>
			<button type="button" class="button button-primary" id="gdpr-category-save"><?php esc_html_e( 'Save Category', 'gdpr-cookie-consent-elementor' ); ?></button>
		</div>
	</div>
</div>

<!-- Mapping Modal -->
<div id="gdpr-mapping-modal" class="gdpr-modal" style="display: none;">
	<div class="gdpr-modal-content">
		<div class="gdpr-modal-header">
			<h2 id="gdpr-mapping-modal-title"><?php esc_html_e( 'Add Cookie Mapping', 'gdpr-cookie-consent-elementor' ); ?></h2>
			<span class="gdpr-modal-close">&times;</span>
		</div>
		<div class="gdpr-modal-body">
			<form id="gdpr-mapping-form">
				<input type="hidden" id="mapping-index" name="index" value="" />
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="mapping-pattern"><?php esc_html_e( 'Cookie Pattern', 'gdpr-cookie-consent-elementor' ); ?> <span class="required">*</span></label>
						</th>
						<td>
							<input type="text" id="mapping-pattern" name="pattern" class="regular-text" required />
							<p class="description"><?php esc_html_e( 'Cookie name pattern (use * as wildcard, e.g., _ga* or wordpress_*)', 'gdpr-cookie-consent-elementor' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="mapping-domain"><?php esc_html_e( 'Domain', 'gdpr-cookie-consent-elementor' ); ?></label>
						</th>
						<td>
							<input type="text" id="mapping-domain" name="domain" class="regular-text" placeholder="<?php esc_attr_e( 'Leave empty for any domain', 'gdpr-cookie-consent-elementor' ); ?>" />
							<p class="description"><?php esc_html_e( 'Optional: Match specific domain (use * as wildcard)', 'gdpr-cookie-consent-elementor' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="mapping-path"><?php esc_html_e( 'Path', 'gdpr-cookie-consent-elementor' ); ?></label>
						</th>
						<td>
							<input type="text" id="mapping-path" name="path" class="regular-text" placeholder="<?php esc_attr_e( 'Leave empty for any path', 'gdpr-cookie-consent-elementor' ); ?>" />
							<p class="description"><?php esc_html_e( 'Optional: Match specific path (use * as wildcard)', 'gdpr-cookie-consent-elementor' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="mapping-category"><?php esc_html_e( 'Category', 'gdpr-cookie-consent-elementor' ); ?> <span class="required">*</span></label>
						</th>
						<td>
							<select id="mapping-category" name="category" required>
								<option value=""><?php esc_html_e( 'Select Category', 'gdpr-cookie-consent-elementor' ); ?></option>
								<?php foreach ( $categories as $category ) : ?>
									<option value="<?php echo esc_attr( $category['id'] ); ?>"><?php echo esc_html( $category['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Category to assign cookies matching this pattern', 'gdpr-cookie-consent-elementor' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="mapping-priority"><?php esc_html_e( 'Priority', 'gdpr-cookie-consent-elementor' ); ?></label>
						</th>
						<td>
							<input type="number" id="mapping-priority" name="priority" class="small-text" value="10" min="0" max="100" />
							<p class="description"><?php esc_html_e( 'Higher priority patterns are checked first (0-100)', 'gdpr-cookie-consent-elementor' ); ?></p>
						</td>
					</tr>
				</table>
			</form>
		</div>
		<div class="gdpr-modal-footer">
			<button type="button" class="button" id="gdpr-mapping-cancel"><?php esc_html_e( 'Cancel', 'gdpr-cookie-consent-elementor' ); ?></button>
			<button type="button" class="button button-primary" id="gdpr-mapping-save"><?php esc_html_e( 'Save Mapping', 'gdpr-cookie-consent-elementor' ); ?></button>
		</div>
	</div>
</div>

