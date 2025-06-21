<?php
/**
 * EMERGENCY DISABLE FILE FOR HTACCESS LOCKDOWN
 * 
 * If the HTAccess Lockdown plugin is causing your site to go down:
 * 
 * 1. Upload this file to your WordPress root directory (same folder as wp-config.php)
 * 2. Add this line to your wp-config.php file (above the "That's all, stop editing!" line):
 *    define('HTACCESS_LOCKDOWN_EMERGENCY_DISABLE', true);
 * 3. Your site should come back online
 * 4. Go to WordPress admin and deactivate the HTAccess Lockdown plugin
 * 5. Remove this file and the line from wp-config.php
 * 
 * ALTERNATIVE QUICK FIX:
 * - Rename the plugin folder from 'htaccess-lockdown' to 'htaccess-lockdown-disabled'
 * - This will immediately deactivate the plugin
 */

// This file does nothing - it's just instructions
// The actual emergency disable is controlled by the constant in wp-config.php 