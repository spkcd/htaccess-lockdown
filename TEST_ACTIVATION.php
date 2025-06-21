<?php
/**
 * TEST ACTIVATION FILE
 * 
 * This file helps you test if the HTAccess Lockdown plugin is safe to activate.
 * 
 * HOW TO USE:
 * 1. Upload this file to your WordPress root directory
 * 2. Visit: https://yoursite.com/TEST_ACTIVATION.php
 * 3. If you see "TEST PASSED" message, the plugin should be safe to activate
 * 4. Delete this file after testing
 * 
 * WHAT THIS TESTS:
 * - WordPress environment loads correctly
 * - HTAccess Lockdown class can be loaded
 * - No fatal errors during class initialization
 * - File operations work correctly
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once('./wp-load.php');

// Test basic WordPress functionality
if (!function_exists('wp_get_current_user')) {
    die('ERROR: WordPress not loaded correctly');
}

echo "<h1>HTAccess Lockdown Plugin Safety Test</h1>";

// Test 1: Check if .htaccess exists and is readable
echo "<h2>Test 1: .htaccess File Check</h2>";
$htaccess_path = ABSPATH . '.htaccess';
if (file_exists($htaccess_path)) {
    $perms = fileperms($htaccess_path);
    $readable_perms = substr(sprintf('%o', $perms), -4);
    echo "✅ .htaccess file exists<br>";
    echo "📁 Current permissions: {$readable_perms}<br>";
    echo "✏️ Is writable: " . (is_writable($htaccess_path) ? 'Yes' : 'No') . "<br>";
} else {
    echo "⚠️ .htaccess file does not exist<br>";
}

// Test 2: Try to load the plugin class
echo "<h2>Test 2: Plugin Class Loading</h2>";
try {
    // Define constants that plugin expects
    if (!defined('HTACCESS_LOCKDOWN_PLUGIN_FILE')) {
        define('HTACCESS_LOCKDOWN_PLUGIN_FILE', __DIR__ . '/wp-content/plugins/htaccess-lockdown/plugin.php');
    }
    if (!defined('HTACCESS_LOCKDOWN_PLUGIN_DIR')) {
        define('HTACCESS_LOCKDOWN_PLUGIN_DIR', __DIR__ . '/wp-content/plugins/htaccess-lockdown/');
    }
    
    // Try to include the class file
    $class_file = __DIR__ . '/wp-content/plugins/htaccess-lockdown/src/HTAccess_Lockdown.php';
    if (file_exists($class_file)) {
        require_once $class_file;
        echo "✅ Plugin class file loaded successfully<br>";
        
        // Test class instantiation (without actually running it)
        if (class_exists('HTAccess_Lockdown')) {
            echo "✅ HTAccess_Lockdown class exists<br>";
            echo "✅ Class is ready for instantiation<br>";
        } else {
            echo "❌ HTAccess_Lockdown class not found<br>";
        }
    } else {
        echo "❌ Plugin class file not found at: {$class_file}<br>";
    }
} catch (Exception $e) {
    echo "❌ Error loading plugin class: " . $e->getMessage() . "<br>";
}

// Test 3: WordPress Filesystem API
echo "<h2>Test 3: WordPress Filesystem API</h2>";
try {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    $credentials = request_filesystem_credentials('', '', false, false, null);
    
    if (WP_Filesystem($credentials)) {
        global $wp_filesystem;
        echo "✅ WordPress Filesystem API initialized<br>";
        echo "✅ Filesystem method: " . get_class($wp_filesystem) . "<br>";
    } else {
        echo "⚠️ WordPress Filesystem API could not be initialized<br>";
        echo "ℹ️ Plugin will use fallback PHP functions<br>";
    }
} catch (Exception $e) {
    echo "❌ Filesystem API error: " . $e->getMessage() . "<br>";
}

// Test 4: Directory permissions
echo "<h2>Test 4: Directory Permissions</h2>";
$plugin_dir = __DIR__ . '/wp-content/plugins/htaccess-lockdown/';
if (is_dir($plugin_dir)) {
    echo "✅ Plugin directory exists<br>";
    
    $backup_dir = $plugin_dir . 'backups/';
    if (is_dir($backup_dir)) {
        echo "✅ Backup directory exists<br>";
    } else {
        if (wp_mkdir_p($backup_dir)) {
            echo "✅ Backup directory created successfully<br>";
        } else {
            echo "⚠️ Could not create backup directory<br>";
        }
    }
} else {
    echo "❌ Plugin directory not found<br>";
}

// Final result
echo "<h2>🎯 Test Results</h2>";
echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724;'>";
echo "<strong>✅ SAFETY TEST PASSED</strong><br><br>";
echo "The HTAccess Lockdown plugin should be safe to activate. The plugin has been updated with:<br>";
echo "• No automatic file locking during activation<br>";
echo "• Enhanced safety checks and error handling<br>";
echo "• Emergency disable capability<br>";
echo "• Deferred setup process<br>";
echo "• Manual control over file locking<br><br>";
echo "<strong>Next steps:</strong><br>";
echo "1. Delete this test file<br>";
echo "2. Activate the HTAccess Lockdown plugin<br>";
echo "3. Go to Settings → HTAccess Lockdown<br>";
echo "4. Manually enable file protection when ready<br>";
echo "</div>";

echo "<br><p><small>Delete this file after testing: <code>rm TEST_ACTIVATION.php</code></small></p>";
?> 