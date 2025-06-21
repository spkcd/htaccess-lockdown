<?php
/**
 * Plugin Name: HTAccess Lockdown
 * Plugin URI: https://sparkwebstudio.com/plugins/htaccess-lockdown
 * Description: A comprehensive security plugin for managing .htaccess file protection and monitoring with real-time change detection and automatic backup functionality.
 * Version: 1.0.0
 * Author: SPARKWEB Studio
 * Author URI: https://sparkwebstudio.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: htaccess-lockdown
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * Network: false
 * 
 * @package HTAccess_Lockdown
 * @author SPARKWEB Studio
 * @version 1.0.0
 * @since 1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('HTACCESS_LOCKDOWN_VERSION', '1.0.0');
define('HTACCESS_LOCKDOWN_PLUGIN_FILE', __FILE__);
define('HTACCESS_LOCKDOWN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HTACCESS_LOCKDOWN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HTACCESS_LOCKDOWN_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Autoloader for the plugin classes
 */
spl_autoload_register(function ($class) {
    // Check if the class belongs to our plugin
    if (strpos($class, 'HTAccess_Lockdown') !== 0) {
        return;
    }

    // Handle the main plugin class
    if ($class === 'HTAccess_Lockdown') {
        $file_path = HTACCESS_LOCKDOWN_PLUGIN_DIR . 'src/HTAccess_Lockdown.php';
    } else {
        // Handle other classes with proper directory structure
        $class_parts = explode('_', $class);
        
        if (count($class_parts) >= 3) {
            // Remove 'HTAccess' and 'Lockdown' from the beginning
            array_shift($class_parts); // Remove 'HTAccess'
            array_shift($class_parts); // Remove 'Lockdown'
            
            // First part is the directory, rest is the class name
            $directory = array_shift($class_parts);
            $class_name = implode('_', array_merge(['HTAccess', 'Lockdown'], $class_parts));
            
            $file_path = HTACCESS_LOCKDOWN_PLUGIN_DIR . 'src/' . $directory . '/' . $class . '.php';
        } else {
            return;
        }
    }

    // Load the class file if it exists
    if (file_exists($file_path)) {
        require_once $file_path;
    }
});

/**
 * Initialize the plugin
 */
function htaccess_lockdown_init(): void {
    try {
        // Emergency check - if site is broken, don't initialize
        if (defined('HTACCESS_LOCKDOWN_EMERGENCY_DISABLE') && HTACCESS_LOCKDOWN_EMERGENCY_DISABLE) {
            return;
        }
        
        // Load plugin textdomain
        load_plugin_textdomain(
            'htaccess-lockdown',
            false,
            dirname(HTACCESS_LOCKDOWN_PLUGIN_BASENAME) . '/languages'
        );

        // Initialize the main plugin class
        HTAccess_Lockdown::get_instance();
    } catch (Exception $e) {
        // Log initialization error
        error_log('[HTAccess Lockdown] Initialization error: ' . $e->getMessage());
        
        // Show admin notice if in admin area
        if (is_admin()) {
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error"><p>';
                echo '<strong>HTAccess Lockdown:</strong> Failed to initialize. Error: ' . esc_html($e->getMessage());
                echo '</p></div>';
            });
        }
    }
}

/**
 * Plugin activation hook
 */
function htaccess_lockdown_activate(): void {
    try {
        // Set activation flag
        update_option('htaccess_lockdown_activated', true);
        
        // Set flag to initialize plugin setup on next admin page load
        update_option('htaccess_lockdown_needs_setup', true);
        
        // Log successful activation
        error_log('[HTAccess Lockdown] Plugin activated successfully - setup will complete on next admin page load');
        
    } catch (Exception $e) {
        // Log the error but don't prevent activation
        error_log('[HTAccess Lockdown] Activation error: ' . $e->getMessage());
        
        // Set basic activation flag even if setup fails
        update_option('htaccess_lockdown_activated', true);
        update_option('htaccess_lockdown_needs_setup', true);
        
        // Set a flag to show admin notice about partial activation
        update_option('htaccess_lockdown_activation_error', $e->getMessage());
    }
}

/**
 * Plugin deactivation hook
 */
function htaccess_lockdown_deactivate(): void {
    // Initialize the plugin class to access methods
    $plugin = HTAccess_Lockdown::get_instance();
    
    // Unschedule monitoring
    $unschedule_result = $plugin->unschedule_monitoring();
    
    if (!$unschedule_result) {
        error_log('[HTAccess Lockdown] Plugin deactivated but monitoring could not be unscheduled');
    }
    
    // Unlock .htaccess file permissions
    $unlock_result = $plugin->unlock_htaccess();
    
    if (!$unlock_result) {
        // If unlocking fails, log the error
        error_log('[HTAccess Lockdown] Plugin deactivated but .htaccess file permissions could not be restored');
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Clean up activation flag
    delete_option('htaccess_lockdown_activated');
}

/**
 * Plugin uninstall hook
 */
function htaccess_lockdown_uninstall(): void {
    // Initialize the plugin class to access methods
    $plugin = HTAccess_Lockdown::get_instance();
    
    // Ensure .htaccess file permissions are restored
    $plugin->unlock_htaccess();
    
    // Clean up all plugin options
    delete_option('htaccess_lockdown_settings');
    delete_option('htaccess_lockdown_options'); // Legacy option
    delete_option('htaccess_lockdown_activated');
    delete_option('htaccess_lockdown_file_locked');
    delete_option('htaccess_lockdown_original_perms');
    delete_option('htaccess_lockdown_last_check');
    delete_option('htaccess_lockdown_hash');
    delete_option('htaccess_lockdown_hash_timestamp');
    delete_option('htaccess_lockdown_last_change_detected');
    delete_option('htaccess_lockdown_change_count');
    delete_option('htaccess_lockdown_backup_created');
    delete_option('htaccess_lockdown_last_restore');
    delete_option('htaccess_lockdown_restore_count');
    delete_option('htaccess_lockdown_show_change_notice');
    delete_option('htaccess_lockdown_dismissed_change');
    
    // Clean up any transients
    delete_transient('htaccess_lockdown_status');
}

// Hook into WordPress
add_action('plugins_loaded', 'htaccess_lockdown_init');
register_activation_hook(__FILE__, 'htaccess_lockdown_activate');
register_deactivation_hook(__FILE__, 'htaccess_lockdown_deactivate');
register_uninstall_hook(__FILE__, 'htaccess_lockdown_uninstall'); 