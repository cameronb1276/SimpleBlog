# Changelog - SimpleBlog

## [1.1.0] - 2024-07-13

### Added
- **Dark Theme**: Complete dark mode theme with modern design and gradient effects
- **Theme Management**: Upload, activate, and delete themes via admin panel
- **Enhanced Security**: Environment variable support for sensitive data

### Fixed
- Database connection issues with environment variables
- JavaScript errors in post sharing functionality (copyToClipboard event parameter)
- Theme activation form resubmission problems with Post-Redirect-Get pattern
- Missing CSS classes for admin interface (status badges, danger buttons)
- File upload path handling with relative server paths
- Duplicate HTML meta tags in theme headers

### Security
- Removed hardcoded credentials for GitHub safety
- Enhanced input sanitization and validation
- Improved session security configuration
- Better CSRF protection across all forms

## [1.0.0] - 2024-07-12
- Initial release with core blogging functionality
