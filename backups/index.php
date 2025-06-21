<?php
/**
 * HTAccess Lockdown - Backup Directory Protection
 * 
 * This file prevents directory browsing and unauthorized access
 * to backup files.
 * 
 * @package HTAccess_Lockdown
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Redirect to admin dashboard
wp_redirect(admin_url());
exit; 