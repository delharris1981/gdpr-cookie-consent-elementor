# Changelog

All notable changes to the GDPR Cookie Consent Elementor plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.3.2] - 2026-02-02

### Fixed
- Fixed critical login loop issue by whitelisting authentication cookies in `class-cookie-blocker.php` to prevent administrative lockout when cookies are declined in Simple Mode.

## [1.3.1] - 2026-02-02

### Fixed
- Fixed "No such file or directory" warnings by removing hardcoded debug logging in `class-cookie-blocker.php`.

## [1.3.0] - 2025-12-28

### Security
- Fixed CWE-319: Removed debug logging and telemetry code transmitting user data over unencrypted HTTP (Cleartext Transmission of Sensitive Information)
- Fixed critical JavaScript injection vulnerability: Replaced dangerous `eval()` usage in cookie detection with direct assignment
- Enhanced stability: Refactored property descriptor fallbacks to prevent potential recursion and browser crashes

### Changed
- Removed all development-related "agent log" blocks from production scripts
- Cleaned up assets/js files for production readiness

### Technical Details
- Removed `fetch()` calls to localhost ingest server in `gdpr-cookie-blocker.js` and `gdpr-widget-frontend.js`
- Replaced `eval()` with `document.cookie = value` in `gdpr-cookie-detector.js` fallback logic

---

## [1.2.1] - 2025-12-19

### Security
- Improved cookie key hashing: Replaced MD5 with SHA-256 for cookie detection and pattern learning
- Enhanced test cookie functionality: Added Secure flag support for HTTPS connections in admin cookie detection test

### Changed
- Cookie detector now uses SHA-256 hashing for cookie key generation (more secure than MD5)
- Pattern learner now uses SHA-256 hashing for pattern key generation (more secure than MD5)
- Admin cookie detection test now properly handles Secure flag based on connection protocol

### Technical Details
- Updated `Cookie_Detector::detect_cookie()` to use `hash('sha256')` instead of `md5()`
- Updated `Cookie_Pattern_Learner::learn_from_assignment()` to use `hash('sha256')` instead of `md5()`
- Admin JavaScript now conditionally adds Secure flag to test cookies when on HTTPS

---

## [1.2.0] - 2025-12-18

### Added
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

### Changed
- Cookie blocking now respects category preferences instead of simple accept/decline
- Widget rendering includes category data attributes for JavaScript access
- Preference storage enhanced to support category-based preferences
- Cookie blocker initialization improved for category mode
- Admin interface restructured with dedicated pages for categories, mappings, and detection

### Fixed
- Cookie blocking now properly works in category mode
- Preference saving correctly handles category preferences
- Modal buttons (Accept All, Reject All) now properly save and apply preferences
- Reload loop issues resolved with reload flag mechanism
- Performance optimizations for cookie monitoring

### Technical Details
- New PHP classes: `Cookie_Category_Manager`, `Cookie_Category_Defaults`, `Cookie_Detector`, `Cookie_Pattern_Learner`
- Enhanced `Cookie_Blocker` class with category-aware blocking logic
- Updated `GDPR_Widget` class with category display controls
- JavaScript enhancements for modal interactions and category preferences
- WordPress Settings API integration for admin interface
- Session-based preference storage with PHP transient fallback
- AJAX handlers for category and mapping management

---

## [1.1.2] - 2025-12-09

### Fixed
- Fixed critical reload loop issue when clicking "Decline" or "Reject All" buttons
- Resolved browser freezing and performance degradation caused by aggressive cookie monitoring
- Improved cookie blocking initialization to prevent conflicts during page reloads
- Added reload flag mechanism to prevent blocking activation during page reload cycles
- Optimized cookie monitoring intervals (reduced from 10ms to 250ms for monitoring, 100ms to 500ms for re-initialization)
- Fixed "Reject All" button behavior in both inline and modal views to properly save preferences and reload page
- Enhanced "Accept All" and "Reject All" buttons in preferences modal to immediately save preferences and apply blocking
- Removed redundant cookie deletion calls that were causing performance issues

### Changed
- Cookie monitoring now respects reload state to prevent blocking during page transitions
- All preference-saving actions (Decline, Reject All, Accept All, Save Preferences) now use consistent reload flag mechanism
- Improved cookie blocking performance by reducing unnecessary polling intervals

### Technical Details
- Introduced `gdpr_reloading` sessionStorage flag to prevent blocking activation during reloads
- Cookie blocker now checks reload state before initializing blocking mechanisms
- Monitoring intervals optimized for better performance without sacrificing effectiveness
- Reload flag is automatically cleared after page load completes

---

## [1.1.1] - 2025-12-03

### Security
- Fixed CWE-614: Added `Secure` attribute to cookies when running on HTTPS
- Cookie operations now properly include the `secure` flag on HTTPS sites to prevent man-in-the-middle attacks
- Updated cookie deletion logic in both `gdpr-cookie-blocker.js` and `gdpr-widget-frontend.js`
- Maintained backward compatibility for HTTP sites while ensuring HTTPS security

### Fixed
- All cookie set/delete operations now respect the connection security protocol
- Vulnerability scanner (Snyk) findings resolved for sensitive cookie security

---

## [1.1.0] - 2025-12-02

### Added
- Enhanced PHP-level cookie blocking for WordPress core and plugins
- Comprehensive server-side cookie interception:
  - Output buffering to intercept Set-Cookie headers before they're sent
  - Header removal via `send_headers` action hook
  - WordPress filter integration (`wp_headers` filter)
- Hybrid preference checking system:
  - Primary: PHP transient storage (session-based)
  - Fallback: JavaScript sessionStorage sync via AJAX
  - Extensible architecture for future PHP-only mode migration
- WordPress cookie blocking:
  - Authentication cookies (via `send_auth_cookies` filter)
  - Comment author cookies (via `comment_cookie_lifetime` filter)
  - WordPress settings cookies (`wp-settings-*`)
  - Plugin cookies using WordPress cookie paths and domains
- AJAX preference synchronization:
  - Automatic sync from JavaScript to PHP when user makes choice
  - Preference sync endpoint for hybrid mode
  - Nonce security for all AJAX requests
- Cookie blocking rules system:
  - Pattern matching for WordPress cookie names
  - Path and domain-based blocking
  - Extensible architecture for custom blocking rules

### Technical Details
- Cookie_Blocker class fully integrated and active by default
- Early initialization (before headers are sent) for maximum effectiveness
- Header interception via `send_headers` action (priority 999)
- Output buffering started at `init` hook (priority 0)
- Preference caching to avoid repeated checks
- Session ID generation using IP + User Agent hash
- WordPress filter integration for comprehensive coverage
- Graceful fallback when JavaScript unavailable

### Changed
- PHP cookie blocking is now active by default (previously optional)
- Cookie_Blocker class enhanced with comprehensive blocking capabilities
- JavaScript widget handler now syncs preferences to PHP automatically
- Widget container includes nonce attribute for secure AJAX requests

### Fixed
- HTTP-only cookies (set server-side) are now blocked at PHP level
- Server-side cookie setting by WordPress core is now intercepted
- Plugin cookies using WordPress functions are now blocked

---

## [1.0.1] - 2025-12-01

### Added
- Elementor Pro popup closing functionality
- Widget control to enable/disable popup closing on button click
- Widget control to select which button(s) close the popup (Both, Accept Only, Decline Only)
- Automatic popup detection when widget is inside an Elementor Pro popup
- Popup closes after preference is saved to sessionStorage
- Uses Elementor's default popup closing animation
- Extensible architecture for future popup system support

### Technical Details
- Popup detection via DOM traversal to find `.elementor-popup-modal` container
- Popup ID extraction from `data-elementor-id` attribute
- Direct access to Elementor Pro popup documents via `elementorFrontend.documentsManager`
- Multiple fallback methods for popup closing to ensure compatibility
- Graceful error handling when Elementor Pro is not available

---

## [1.0.0] - 2024-12-XX

### Added
- Initial release of GDPR Cookie Consent Elementor plugin
- Custom Elementor widget for GDPR cookie consent
- Content controls:
  - Customizable GDPR message text (textarea control)
  - Customizable Accept button text
  - Customizable Decline button text
- Style controls:
  - Separate styling for GDPR message (typography, color, alignment, spacing)
  - Separate styling for Accept button (typography, colors, background, border, padding, shadow, hover states)
  - Separate styling for Decline button (same controls as Accept, independently styled)
  - Container styling (background, border, padding, shadow)
- JavaScript cookie blocking functionality:
  - Global cookie blocker script that runs early in page load
  - Blocks `document.cookie` setter when consent is declined
  - Blocks WordPress `wpCookies.set()` method
  - Blocks jQuery cookie plugins
  - Blocks common cookie libraries (js-cookie, Cookies.js)
  - Blocks Sourcebuster.js (WooCommerce order attribution) cookies
  - Continuous cookie monitoring and deletion (every 50ms when blocking active)
  - Automatic deletion of cookies with common analytics prefixes (`sbjs_`, `_ga`, `_gid`, `_gat`, `_fbp`, `_fbc`)
- Session-based preference storage using `sessionStorage`
- Widget frontend JavaScript for handling user interactions
- Cookie deletion functionality that handles multiple paths and domains
- Inline script output in `<head>` for earliest possible execution
- PHP cookie blocking classes (optional):
  - `class-cookie-blocker.php` for server-side cookie blocking
  - `class-ajax-handler.php` for AJAX preference storage
- Basic CSS styling for widget layout and responsiveness
- Plugin readme.txt with installation instructions
- Elementor dependency check with admin notice

### Technical Details
- PHP 8.2+ compatible
- WordPress 6.8+ compatible
- Follows WordPress Coding Standards (WPCS)
- Properly namespaced code
- Security: Nonce verification, input sanitization, output escaping
- Early script execution (priority 0 in `wp_head`)
- Aggressive cookie blocking with continuous monitoring
- Support for multiple cookie deletion methods (various paths and domains)

### Known Limitations
- HTTP-only cookies (set server-side) cannot be deleted by JavaScript and require PHP-level blocking
- Some third-party scripts may use cookie-setting methods that are difficult to intercept
- Cookie blocking effectiveness depends on script execution order

---

## [Unreleased]

### Planned
- Integration with popular cookie consent plugins
- Additional cookie library interceptors as needed
- Cookie consent audit logging
- Export/import cookie category configurations

