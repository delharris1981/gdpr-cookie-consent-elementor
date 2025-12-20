# GDPR Cookie Consent Elementor

![Version](https://img.shields.io/badge/version-1.2.1-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-6.8%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPLv2%2B-green.svg)

A comprehensive Elementor widget for GDPR cookie consent with granular cookie category management, customizable preferences center, and automatic cookie detection. Provides fine-grained control over cookie consent with category-based blocking.

## Description

GDPR Cookie Consent Elementor is a comprehensive cookie consent solution for Elementor that provides granular control over cookie categories and user preferences. The widget includes:

- ✅ **Cookie Category Management**: Organize cookies into categories (Essential, Analytics, Marketing, etc.)
- ✅ **Preferences Center Modal**: User-friendly modal interface for granular cookie control
- ✅ **Automatic Cookie Detection**: Intelligent detection and categorization of cookies
- ✅ **Customizable GDPR message** with Accept/Decline buttons
- ✅ **Granular category controls** with "Accept All" and "Reject All" quick actions
- ✅ **Separate styling controls** for message, buttons, container, and modal
- ✅ **Session-based preference storage** with PHP fallback
- ✅ **Category-aware cookie blocking** based on user preferences
- ✅ **PHP-level cookie blocking** for HTTP-only cookies
- ✅ **Elementor Pro popup integration**
- ✅ **Secure cookie handling** on HTTPS connections
- ✅ **Admin interface** for managing categories, mappings, and detected cookies

## Requirements

- **WordPress:** 6.8 or higher
- **PHP:** 8.2 or higher
- **Elementor:** Latest version required

## Installation

1. Upload the plugin files to the `/wp-content/plugins/gdpr-cookie-consent-elementor` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Ensure Elementor is installed and activated.
4. Add the widget to your page using Elementor editor.

## Features

### Cookie Category Management
- **Multiple Categories**: Organize cookies into Essential, Analytics, Marketing, Functional, and custom categories
- **Category Mapping**: Map specific cookies or cookie patterns to categories
- **Essential Categories**: Mark categories as required (cannot be disabled by users)
- **Admin Interface**: Comprehensive admin panel for managing categories and mappings
- **Default Presets**: Pre-configured category templates for quick setup

### Cookie Preferences Center
- **Modal Interface**: Full-screen preferences modal with category toggles
- **Quick Actions**: "Accept All" and "Reject All" buttons for convenience
- **Granular Control**: Users can enable/disable individual cookie categories
- **Visual Feedback**: Real-time confirmation messages
- **Accessibility**: ARIA attributes, keyboard navigation, and focus trapping

### Automatic Cookie Detection
- **Real-time Detection**: Automatically detects cookies on frontend and admin pages
- **Pattern Learning**: Intelligent pattern recognition for cookie categorization
- **Detection Interface**: View all detected cookies in the admin panel
- **Test Functionality**: Manual cookie detection testing

### Cookie Blocking
The plugin provides comprehensive cookie blocking at both JavaScript and PHP levels:

- **Category-aware blocking:** Blocks cookies based on user's category preferences
- **JavaScript blocking:** Intercepts `document.cookie` and common cookie libraries
- **PHP blocking:** Prevents server-side cookie headers from being sent
- **Hybrid mode:** Syncs preferences between JavaScript and PHP for maximum protection
- **Continuous monitoring:** Actively monitors and deletes cookies when consent is declined
- **Proactive blocking:** Intercepts specific libraries (Sourcebuster.js, sbjs) before initialization

### Elementor Pro Integration
- Automatic popup detection when widget is inside an Elementor Pro popup
- Configurable popup closing on Accept/Decline button clicks
- Multiple closing methods for maximum compatibility

### Security
- ✅ CWE-614 compliant: Secure attribute on cookies for HTTPS connections
- ✅ Nonce verification for all AJAX requests
- ✅ Input sanitization and output escaping
- ✅ Follows WordPress Coding Standards (WPCS)
- ✅ Capability checks for admin functions

## Usage

1. Open a page in Elementor editor
2. Search for "GDPR Cookie Consent" widget
3. Drag and drop the widget onto your page
4. Customize the message and button text in the Content tab
5. Style the widget using the Style tab controls
6. Configure popup settings if using inside an Elementor Pro popup

### Widget Controls

#### Content
- GDPR message text (textarea)
- Accept button text
- Decline button text
- Customize button text (for preferences modal)
- Category display mode (Inline, Modal, Both)

#### Popup Settings
- Enable/disable popup closing
- Select which button(s) close the popup (Both, Accept Only, Decline Only)

#### Style
- Message typography, color, alignment, spacing
- Accept button typography, colors, background, border, padding, shadow, hover states
- Decline button (same controls as Accept, independently styled)
- Customize button styling (typography, colors, background, border, padding, shadow, hover states)
- Container background, border, padding, shadow
- Modal styling (overlay, modal container, category items, buttons)

### Admin Settings

Access the admin settings via **WordPress Admin → Settings → GDPR Cookie Consent**:

#### Cookie Categories
- Add, edit, and delete cookie categories
- Set categories as essential (required)
- Configure category names and descriptions

#### Cookie Mappings
- Map cookies to categories using exact names or patterns
- Support for wildcard patterns (e.g., `sbjs_*`, `_ga*`)
- Bulk mapping operations

#### Detected Cookies
- View all cookies detected on your site
- Test cookie detection functionality
- See cookie details (name, domain, path, category)

## Frequently Asked Questions

### Does this plugin require Elementor?

Yes, this plugin requires Elementor to be installed and activated.

### How does cookie blocking work?

The plugin uses both JavaScript and PHP to block cookies when the user declines consent. The blocking is session-based and will reset when the browser session ends.

### Can I customize the widget appearance?

Yes, the widget includes comprehensive styling controls for the message, buttons, and container. You can customize colors, typography, spacing, borders, and more.

### Does this work with Elementor Pro popups?

Yes! The plugin automatically detects when the widget is inside an Elementor Pro popup and provides controls to close the popup after user interaction.

### Is this plugin secure?

Yes, the plugin follows WordPress Coding Standards and implements proper security measures including nonce verification, input sanitization, and CWE-614 compliance for secure cookie handling on HTTPS connections.

### How do cookie categories work?

Cookie categories allow you to organize cookies into groups (Essential, Analytics, Marketing, etc.). Users can then choose which categories to accept. Essential categories cannot be disabled and are always enabled. You can manage categories and map cookies to categories in the admin settings.

### Can users customize which cookies to accept?

Yes! The plugin includes a preferences center modal that users can access via the "Customize" button. This allows users to enable or disable individual cookie categories (except essential ones).

### How does automatic cookie detection work?

The plugin monitors cookie creation on your site and automatically detects new cookies. You can view all detected cookies in the admin panel under "Detected Cookies" and map them to appropriate categories.

## Changelog

### [1.2.1] - 2025-12-19

#### Security
- Improved cookie key hashing: Replaced MD5 with SHA-256 for cookie detection and pattern learning
- Enhanced test cookie functionality: Added Secure flag support for HTTPS connections in admin cookie detection test

#### Changed
- Cookie detector now uses SHA-256 hashing for cookie key generation
- Pattern learner now uses SHA-256 hashing for pattern key generation
- Admin cookie detection test now properly handles Secure flag based on connection protocol

### [1.2.0] - 2025-12-18

#### Added
- **Cookie Category Management System**: Complete implementation of granular cookie category control
  - Support for multiple cookie categories (Essential, Analytics, Marketing, etc.)
  - Category CRUD operations via admin interface
  - Cookie-to-category mapping system
  - User preference storage per category
  - Essential categories that cannot be disabled
- **Cookie Preferences Center Modal**: Enhanced user experience with modal-based preferences
  - "Customize" button in cookie notice to open preferences modal
  - Full-screen modal with category toggles
  - "Accept All" and "Reject All" quick action buttons in modal
  - Granular category control with visual feedback
  - Accessibility features (ARIA attributes, keyboard navigation, focus trapping)
- **Automatic Cookie Detection**: Intelligent cookie discovery system
  - Real-time cookie detection on frontend and admin pages
  - Cookie pattern learning for automatic categorization
  - Admin interface for viewing detected cookies
  - Cookie detection test functionality
- **Admin Settings Interface**: Comprehensive admin panel for cookie management
  - Cookie Categories management page
  - Cookie Mappings management page
  - Detected Cookies viewing page
  - Modal forms for adding categories and mappings
  - Default category presets (Essential, Analytics, Marketing, Functional)
- **Enhanced Cookie Blocking**: Category-aware blocking system
  - Block cookies based on user's category preferences
  - Automatic blocking of unmapped cookies when all non-essential categories declined
  - Proactive blocking of specific libraries (Sourcebuster.js, sbjs)
  - Improved blocking logic for category mode vs simple mode
- **Widget Display Modes**: Flexible category display options
  - Inline category display
  - Modal-only category display
  - Both inline and modal options
  - Customizable "Customize" button text and display

#### Fixed
- Cookie blocking now properly works in category mode
- Preference saving correctly handles category preferences
- Modal buttons (Accept All, Reject All) now properly save and apply preferences
- Reload loop issues resolved with reload flag mechanism
- Performance optimizations for cookie monitoring

### [1.1.2] - 2025-12-09

#### Fixed
- Fixed critical reload loop issue when clicking "Decline" or "Reject All" buttons
- Resolved browser freezing and performance degradation caused by aggressive cookie monitoring
- Improved cookie blocking initialization to prevent conflicts during page reloads
- Added reload flag mechanism to prevent blocking activation during page reload cycles
- Optimized cookie monitoring intervals
- Fixed "Reject All" button behavior in both inline and modal views

### [1.1.1] - 2025-12-03

#### Security
- Fixed CWE-614 vulnerability - Added Secure attribute to cookies on HTTPS connections
- Cookie operations now properly include the secure flag to prevent man-in-the-middle attacks
- All cookie set/delete operations now respect the connection security protocol
- Updated cookie deletion logic in `gdpr-cookie-blocker.js` and `gdpr-widget-frontend.js`

### [1.1.0] - 2025-12-02

#### Added
- Enhanced PHP-level cookie blocking for WordPress core and plugins
- Comprehensive server-side cookie interception
- Hybrid preference checking system
- WordPress cookie blocking (auth, comment, settings)
- AJAX preference synchronization
- Elementor Pro popup closing functionality
- Widget controls to enable/disable popup closing
- Option to select which button(s) close the popup

#### Technical Details
- Cookie_Blocker class fully integrated and active by default
- Early initialization for maximum effectiveness
- Preference caching to avoid repeated checks
- Graceful fallback when JavaScript unavailable

### [1.0.0] - 2025-12-01

#### Added
- Initial release of GDPR Cookie Consent Elementor plugin
- Custom Elementor widget for GDPR cookie consent
- JavaScript cookie blocking functionality
- Session-based preference storage
- Cookie deletion functionality
- Basic CSS styling for widget layout

## Upgrade Notice

### 1.2.0
**Major feature update:** Introduces cookie category management, preferences center modal, and automatic cookie detection. This is a significant update that adds granular cookie control capabilities. New admin settings are available under WordPress Admin → Settings → GDPR Cookie Consent.

### 1.1.2
**Performance and stability update:** Fixes critical reload loop issues and improves cookie monitoring performance. Recommended for all users experiencing browser freezing or performance issues.

### 1.1.1
**Security update:** Fixes CWE-614 vulnerability by adding Secure attribute to cookies on HTTPS connections. Recommended for all users.

### 1.1.0
Added Elementor Pro popup closing functionality. Configure in widget settings under "Popup Settings".

## License

This plugin is licensed under the GPLv2 or later.

```
Copyright (C) 2024 Your Name

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## Support

For bug reports, feature requests, or support questions, please open an issue on the plugin repository.

## Credits

- Contributors: yourname
- Tags: elementor, widget, gdpr, cookie, consent, privacy, security







