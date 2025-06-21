# HTAccess Lockdown

**Contributors:** SPARKWEB Studio  
**Plugin URI:** https://sparkwebstudio.com/plugins/htaccess-lockdown  
**Author URI:** https://sparkwebstudio.com  
**Tags:** security, htaccess, file protection, monitoring, backup  
**Requires at least:** 5.0  
**Tested up to:** 6.5  
**Requires PHP:** 7.4  
**Stable tag:** 1.0.0  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

A comprehensive WordPress security plugin for managing .htaccess file protection and monitoring.

## Description

HTAccess Lockdown is a professional security plugin developed by SPARKWEB Studio that protects your website's .htaccess file from unauthorized modifications. It provides real-time monitoring, automatic backup and restore functionality, and comprehensive logging to keep your WordPress site secure.

The plugin uses WordPress best practices and standards, including the WordPress Filesystem API for secure file operations, proper internationalization support, and comprehensive admin interface built with the WordPress Settings API.

### Key Features

- **🔒 File Protection**: Lock .htaccess file with read-only permissions (0444)
- **👁️ Real-time Monitoring**: Daily WP-Cron checks for file modifications with hash comparison
- **💾 Automatic Backup**: Creates secure backup on activation for easy restoration
- **🔄 Smart Restoration**: Automatically restore from backup when unauthorized changes detected
- **⚙️ Admin Interface**: Comprehensive settings and status dashboard with real-time information
- **📝 Security Logging**: Detailed error and security event logging with WordPress debug integration
- **🏆 WordPress Standards**: Built with WordPress coding standards and best practices
- **🌍 Internationalization**: Full i18n support with translation-ready strings and POT template

### Security Benefits

- Prevents unauthorized .htaccess modifications
- Detects and alerts on file tampering
- Automatic restoration of compromised files
- Comprehensive audit trail through logging
- No direct file access - uses WordPress Filesystem API
- Proper nonce verification and capability checks

## Requirements

- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher  
- **Permissions:** Write access to plugin directory (for backups)
- **Server:** Standard WordPress hosting environment

## Installation

### Method 1: WordPress Admin Dashboard (Recommended)

1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins > Add New**
3. Click **Upload Plugin**
4. Choose the `htaccess-lockdown.zip` file
5. Click **Install Now**
6. Click **Activate Plugin**
7. Go to **Settings > HTAccess Lockdown** to configure

### Method 2: Manual Installation via FTP

1. Download the plugin zip file
2. Extract the contents to your computer
3. Using an FTP client, upload the `htaccess-lockdown` folder to `/wp-content/plugins/`
4. Log in to your WordPress admin dashboard
5. Navigate to **Plugins > Installed Plugins**
6. Find "HTAccess Lockdown" and click **Activate**
7. Go to **Settings > HTAccess Lockdown** to configure

### Method 3: WordPress CLI (WP-CLI)

```bash
# Navigate to your WordPress root directory
cd /path/to/wordpress

# Install the plugin
wp plugin install htaccess-lockdown.zip

# Activate the plugin
wp plugin activate htaccess-lockdown
```

### Post-Installation Setup

1. **Navigate to Settings**: Go to **Settings > HTAccess Lockdown** in your WordPress admin
2. **Configure Protection**: Enable ".htaccess Protection" (enabled by default)
3. **Set Auto Re-lock**: Choose whether to automatically reset permissions on changes
4. **Enable Backup Restore**: Optionally enable automatic restoration from backup
5. **Review Status**: Check the file status and monitoring information
6. **Test Functionality**: Use the manual actions to verify everything is working

## Configuration

### Protection Settings

#### Enable .htaccess Protection
- **Default:** Enabled
- **Description:** Master switch for all protection features
- When enabled, the plugin will lock your .htaccess file and monitor for changes

#### Auto Re-lock on Changes  
- **Default:** Enabled
- **Description:** Automatically reset file permissions when unauthorized changes are detected
- Recommended to keep enabled for maximum security

#### Restore from Backup on Changes
- **Default:** Disabled  
- **Description:** Automatically restore original .htaccess content from backup when changes are detected
- **Warning:** Only enable if you're confident in your backup and don't frequently modify .htaccess

### File Status & Monitoring

The admin interface provides comprehensive information about:

- **File Information:** Path, permissions, last modified date, protection status
- **Monitoring Status:** Active monitoring, next/last check times, change statistics
- **Backup Information:** Backup availability, creation date, restoration history
- **Hash Verification:** Current vs stored file hash comparison

### Manual Actions

- **Check File Now:** Perform immediate file change detection
- **Lock File Now:** Manually apply read-only permissions to .htaccess file

## Troubleshooting

### Common Issues

**❌ Plugin won't activate**
- Verify PHP version meets requirements (7.4+)
- Check file permissions on plugin directory
- Ensure .htaccess file exists and is readable
- Review WordPress error logs for specific errors

**❌ Monitoring not working**  
- Verify WP-Cron is functioning on your server
- Check that protection is enabled in plugin settings
- Look for blocked cron jobs in server configuration

**❌ Backup creation fails**
- Verify write permissions on plugin directory (`wp-content/plugins/htaccess-lockdown/backups/`)
- Check available disk space
- Review WordPress debug logs for filesystem errors

**❌ File locking fails**
- Verify server supports file permission changes
- Check if .htaccess file is owned by the web server user
- Some shared hosting providers may restrict permission changes

### Debug Mode

Enable WordPress debug logging to see detailed plugin activity:

```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check `/wp-content/debug.log` for HTAccess Lockdown messages.

### Support

For technical support and assistance:

- **Website:** [sparkwebstudio.com](https://sparkwebstudio.com)
- **Plugin Support:** [sparkwebstudio.com/support](https://sparkwebstudio.com/support)
- **Documentation:** [sparkwebstudio.com/docs/htaccess-lockdown](https://sparkwebstudio.com/docs/htaccess-lockdown)

## Frequently Asked Questions

**Q: Will this plugin conflict with other security plugins?**  
A: HTAccess Lockdown focuses specifically on .htaccess file protection and should not conflict with most security plugins. However, test in a staging environment first.

**Q: Can I still modify my .htaccess file when protection is enabled?**  
A: You'll need to temporarily disable protection through the plugin settings before making manual changes.

**Q: What happens if I deactivate the plugin?**  
A: The plugin will automatically restore original .htaccess permissions and clean up monitoring when deactivated.

**Q: Does this work with caching plugins?**  
A: Yes, the plugin monitors the actual .htaccess file regardless of caching configurations.

## Developer Information

### Technical Details

- **Architecture:** Object-oriented with singleton pattern
- **Standards:** WordPress Coding Standards compliant
- **Security:** WordPress Filesystem API, nonce verification, capability checks
- **Internationalization:** Translation-ready with POT template included
- **Database:** Uses WordPress Options API for configuration storage

### Hooks & Filters

#### Actions
- `htaccess_lockdown_daily_check` - Daily monitoring cron hook
- `htaccess_lockdown_file_locked` - Fired when file is successfully locked
- `htaccess_lockdown_file_changed` - Fired when unauthorized change detected

#### Filters  
- `htaccess_lockdown_backup_directory` - Modify backup storage location
- `htaccess_lockdown_monitoring_frequency` - Change monitoring schedule

### Database Options

- `htaccess_lockdown_settings` - Main plugin configuration
- `htaccess_lockdown_hash` - Stored file hash for monitoring
- `htaccess_lockdown_file_locked` - Current lock status
- `htaccess_lockdown_backup_created` - Backup creation timestamp
- Additional monitoring and statistics options

## Changelog

### 1.0.0 - June 8, 2025

**🎉 Initial Release**

#### Added
- Complete .htaccess file protection system
- Real-time file monitoring with daily WP-Cron checks
- Automatic backup creation and restoration functionality  
- Comprehensive admin interface with WordPress Settings API
- Security logging with internationalized messages
- WordPress Filesystem API integration for secure file operations
- Full internationalization support with POT template
- Manual lock/unlock functionality through admin interface
- File change detection with MD5 hash comparison
- Admin notices for security events with dismissible notifications
- Plugin action links for quick access to settings
- Comprehensive error handling and graceful fallbacks

#### Security Features
- Read-only file permission enforcement (0444)
- Nonce verification for all admin actions
- Capability checks for administrative functions
- Input sanitization and output escaping throughout
- Protected backup directory with security files

#### Technical Features
- PSR-4 compatible autoloader
- Singleton pattern implementation
- Strict PHP typing enabled
- WordPress coding standards compliance
- Comprehensive PHPDoc documentation
- Responsive admin interface with AJAX functionality

---

**Developed with ❤️ by [SPARKWEB Studio](https://sparkwebstudio.com)**

## License

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA. 