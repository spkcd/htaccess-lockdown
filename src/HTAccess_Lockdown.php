<?php
/**
 * Main plugin class
 *
 * @package HTAccess_Lockdown
 * @since 1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main HTAccess Lockdown Plugin Class
 *
 * @since 1.0.0
 */
class HTAccess_Lockdown
{
    /**
     * Plugin instance
     *
     * @var HTAccess_Lockdown|null
     */
    private static ?HTAccess_Lockdown $instance = null;

    /**
     * Plugin version
     *
     * @var string
     */
    public string $version;

    /**
     * Admin instance
     *
     * @var HTAccess_Lockdown_Admin|null
     */
    public ?HTAccess_Lockdown_Admin $admin = null;

    /**
     * Security instance
     *
     * @var HTAccess_Lockdown_Security|null
     */
    public ?HTAccess_Lockdown_Security $security = null;

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->version = HTACCESS_LOCKDOWN_VERSION;
        $this->init();
    }

    /**
     * Get plugin instance (Singleton pattern)
     *
     * @return HTAccess_Lockdown
     */
    public static function get_instance(): HTAccess_Lockdown
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initialize the plugin
     *
     * @return void
     */
    private function init(): void
    {
        // Initialize components
        $this->init_hooks();
        $this->init_components();
    }

    /**
     * Initialize WordPress hooks
     *
     * @return void
     */
    private function init_hooks(): void
    {
        add_action('init', [$this, 'load_textdomain']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'frontend_enqueue_scripts']);
        
        // Add hook for deferred setup after activation
        add_action('admin_init', [$this, 'maybe_complete_setup']);
        
        // Add cron hook for .htaccess monitoring
        add_action('htaccess_lockdown_daily_check', [$this, 'check_htaccess_changes']);
    }

    /**
     * Initialize plugin components
     *
     * @return void
     */
    private function init_components(): void
    {
        // Initialize admin interface
        if (is_admin()) {
            $this->admin = new HTAccess_Lockdown_Admin();
        }

        // Initialize security features only if protection is enabled
        if ($this->is_protection_enabled()) {
            $this->security = new HTAccess_Lockdown_Security();
        }
    }

    /**
     * Load plugin textdomain for internationalization
     *
     * @since 1.0.0
     * @return void
     */
    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            'htaccess-lockdown',
            false,
            dirname(plugin_basename(HTACCESS_LOCKDOWN_PLUGIN_FILE)) . '/languages'
        );
    }

    /**
     * Enqueue admin scripts and styles for plugin pages
     *
     * @since 1.0.0
     * @param string $hook Current admin page hook
     * @return void
     */
    public function admin_enqueue_scripts(string $hook): void
    {
        // Only load on plugin pages
        if (strpos($hook, 'htaccess-lockdown') === false) {
            return;
        }

        wp_enqueue_style(
            'htaccess-lockdown-admin',
            HTACCESS_LOCKDOWN_PLUGIN_URL . 'assets/css/admin.css',
            [],
            $this->version
        );

        wp_enqueue_script(
            'htaccess-lockdown-admin',
            HTACCESS_LOCKDOWN_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            $this->version,
            true
        );

        // Localize script
        wp_localize_script(
            'htaccess-lockdown-admin',
            'htaccessLockdown',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('htaccess_lockdown_nonce'),
                'i18n' => [
                    'confirm' => __('Are you sure?', 'htaccess-lockdown'),
                    'success' => __('Operation completed successfully.', 'htaccess-lockdown'),
                    'error' => __('An error occurred. Please try again.', 'htaccess-lockdown'),
                ]
            ]
        );
    }

    /**
     * Enqueue frontend scripts and styles (placeholder for future use)
     *
     * @since 1.0.0
     * @return void
     */
    public function frontend_enqueue_scripts(): void
    {
        // Frontend assets will be added here if needed
    }

    /**
     * Complete plugin setup after activation (deferred)
     *
     * @since 1.0.0
     * @return void
     */
    public function maybe_complete_setup(): void
    {
        // Only run if setup is needed
        if (!get_option('htaccess_lockdown_needs_setup', false)) {
            return;
        }

        // Only run in admin area and for administrators
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        // SAFETY: Emergency disable check
        if (defined('HTACCESS_LOCKDOWN_EMERGENCY_DISABLE') && HTACCESS_LOCKDOWN_EMERGENCY_DISABLE) {
            delete_option('htaccess_lockdown_needs_setup');
            error_log(__('[HTAccess Lockdown] Setup cancelled: Emergency disable mode is active', 'htaccess-lockdown'));
            return;
        }

        try {
            // Remove the setup flag first to prevent multiple runs
            delete_option('htaccess_lockdown_needs_setup');
            
            // Initialize plugin in SAFE MODE - no automatic file locking
            error_log(__('[HTAccess Lockdown] Beginning safe setup process...', 'htaccess-lockdown'));
            
            // Always create backup regardless of protection setting
            $backup_result = $this->create_htaccess_backup();
            
            if (!$backup_result) {
                error_log(__('[HTAccess Lockdown] Setup warning: .htaccess backup could not be created', 'htaccess-lockdown'));
            } else {
                error_log(__('[HTAccess Lockdown] Setup: .htaccess backup created successfully', 'htaccess-lockdown'));
            }
            
            // Store initial .htaccess file hash for monitoring
            $hash_result = $this->store_htaccess_hash();
            
            if (!$hash_result) {
                error_log(__('[HTAccess Lockdown] Setup warning: .htaccess file hash could not be stored', 'htaccess-lockdown'));
            } else {
                error_log(__('[HTAccess Lockdown] Setup: .htaccess file hash stored for monitoring', 'htaccess-lockdown'));
            }
            
            // Schedule daily monitoring only if protection is enabled
            if ($this->is_protection_enabled()) {
                $monitoring_result = $this->schedule_monitoring();
                
                if (!$monitoring_result) {
                    error_log(__('[HTAccess Lockdown] Setup warning: daily monitoring could not be scheduled', 'htaccess-lockdown'));
                } else {
                    error_log(__('[HTAccess Lockdown] Setup: daily monitoring scheduled successfully', 'htaccess-lockdown'));
                }
            }
            
            // SAFE MODE: Never automatically lock files during setup
            error_log(__('[HTAccess Lockdown] Safe setup completed successfully - manual file locking available in admin interface', 'htaccess-lockdown'));
            
            // Set flag to show setup completion notice
            update_option('htaccess_lockdown_setup_complete', true);
            update_option('htaccess_lockdown_safe_mode_setup', true);
            
            // Clean up any activation error flags
            delete_option('htaccess_lockdown_activation_error');
            
        } catch (Exception $e) {
            // Log the error with more details
            error_log(sprintf(__('[HTAccess Lockdown] Setup error: %s in %s on line %d', 'htaccess-lockdown'), 
                $e->getMessage(), 
                $e->getFile(), 
                $e->getLine()
            ));
            
            // Set flag to show setup error notice with safe recovery
            update_option('htaccess_lockdown_setup_error', $e->getMessage());
            update_option('htaccess_lockdown_safe_mode_setup', true);
            
            // Don't let setup errors break the site
            error_log(__('[HTAccess Lockdown] Setup failed but site remains safe - check admin notices for details', 'htaccess-lockdown'));
        }
    }

    /**
     * Get plugin version number
     *
     * @since 1.0.0
     * @return string Plugin version
     */
    public function get_version(): string
    {
        return $this->version;
    }

    /**
     * Get plugin directory path with optional subpath
     *
     * @since 1.0.0
     * @param string $path Optional path to append
     * @return string Full plugin path
     */
    public function get_plugin_path(string $path = ''): string
    {
        return HTACCESS_LOCKDOWN_PLUGIN_DIR . $path;
    }

    /**
     * Get plugin URL with optional subpath
     *
     * @since 1.0.0
     * @param string $path Optional path to append
     * @return string Full plugin URL
     */
    public function get_plugin_url(string $path = ''): string
    {
        return HTACCESS_LOCKDOWN_PLUGIN_URL . $path;
    }
    
    /**
     * Check if protection is enabled in plugin settings
     *
     * @since 1.0.0
     * @return bool True if protection is enabled
     */
    public function is_protection_enabled(): bool
    {
        $settings = get_option('htaccess_lockdown_settings', [
            'enable_protection' => true,
            'auto_relock' => true
        ]);
        
        return (bool) $settings['enable_protection'];
    }
    
    /**
     * Check if auto re-lock is enabled in plugin settings
     *
     * @since 1.0.0
     * @return bool True if auto re-lock is enabled
     */
    public function is_auto_relock_enabled(): bool
    {
        $settings = get_option('htaccess_lockdown_settings', [
            'enable_protection' => true,
            'auto_relock' => true,
            'enable_restore_on_change' => false
        ]);
        
        return (bool) $settings['auto_relock'];
    }
    
    /**
     * Check if restore on change is enabled in plugin settings
     *
     * @since 1.0.0
     * @return bool True if restore on change is enabled
     */
    public function is_restore_on_change_enabled(): bool
    {
        $settings = get_option('htaccess_lockdown_settings', [
            'enable_protection' => true,
            'auto_relock' => true,
            'enable_restore_on_change' => false
        ]);
        
        return (bool) $settings['enable_restore_on_change'];
    }
    
    /**
     * Get backup file path for .htaccess file
     *
     * @since 1.0.0
     * @return string Full path to backup file
     */
    public function get_backup_path(): string
    {
        return HTACCESS_LOCKDOWN_PLUGIN_DIR . 'backups/.htaccess.bak';
    }
    
    /**
     * Get backup directory path for plugin backups
     *
     * @since 1.0.0
     * @return string Full path to backup directory
     */
    public function get_backup_dir(): string
    {
        return HTACCESS_LOCKDOWN_PLUGIN_DIR . 'backups/';
    }
    
    /**
     * Create backup of .htaccess file using WP Filesystem API
     *
     * @since 1.0.0
     * @return bool Success status
     */
    public function create_htaccess_backup(): bool
    {
        // Initialize WP Filesystem
        if (!$this->init_wp_filesystem()) {
            error_log(__('[HTAccess Lockdown] Cannot initialize WP Filesystem API for backup', 'htaccess-lockdown'));
            return false;
        }
        
        global $wp_filesystem;
        $htaccess_file = ABSPATH . '.htaccess';
        
        // Check if source file exists
        if (!$wp_filesystem->exists($htaccess_file)) {
            error_log(__('[HTAccess Lockdown] Cannot create backup: .htaccess file does not exist', 'htaccess-lockdown'));
            return false;
        }
        
        // Create backup directory if it doesn't exist
        $backup_dir = $this->get_backup_dir();
        if (!$wp_filesystem->is_dir($backup_dir)) {
            if (!wp_mkdir_p($backup_dir)) {
                /* translators: %s: Directory path */
                error_log(sprintf(__('[HTAccess Lockdown] Failed to create backup directory: %s', 'htaccess-lockdown'), $backup_dir));
                return false;
            }
        }
        
        // Copy file to backup location using WP Filesystem
        $backup_path = $this->get_backup_path();
        if (!$wp_filesystem->copy($htaccess_file, $backup_path)) {
            error_log(__('[HTAccess Lockdown] Failed to create backup copy of .htaccess file', 'htaccess-lockdown'));
            return false;
        }
        
        // Set backup file permissions
        $wp_filesystem->chmod($backup_path, 0644);
        
        // Store backup creation timestamp
        update_option('htaccess_lockdown_backup_created', time());
        
        /* translators: %s: Backup file path */
        error_log(sprintf(__('[HTAccess Lockdown] Successfully created .htaccess backup at: %s', 'htaccess-lockdown'), $backup_path));
        return true;
    }
    
    /**
     * Restore .htaccess file from backup using WP Filesystem API
     *
     * @since 1.0.0
     * @return bool Success status
     */
    public function restore_htaccess_from_backup(): bool
    {
        // Initialize WP Filesystem
        if (!$this->init_wp_filesystem()) {
            error_log(__('[HTAccess Lockdown] Cannot initialize WP Filesystem API for restore', 'htaccess-lockdown'));
            return false;
        }
        
        global $wp_filesystem;
        $htaccess_file = ABSPATH . '.htaccess';
        $backup_path = $this->get_backup_path();
        
        // Check if backup exists
        if (!$wp_filesystem->exists($backup_path)) {
            /* translators: %s: Backup file path */
            error_log(sprintf(__('[HTAccess Lockdown] Cannot restore: backup file does not exist at %s', 'htaccess-lockdown'), $backup_path));
            return false;
        }
        
        // Check if target file exists and make it writable if needed
        if ($wp_filesystem->exists($htaccess_file) && !$wp_filesystem->is_writable($htaccess_file)) {
            // Try to make it writable temporarily
            if (!$wp_filesystem->chmod($htaccess_file, 0644)) {
                error_log(__('[HTAccess Lockdown] Cannot make .htaccess file writable for restoration', 'htaccess-lockdown'));
                return false;
            }
        }
        
        // Copy backup to original location
        if (!$wp_filesystem->copy($backup_path, $htaccess_file)) {
            error_log(__('[HTAccess Lockdown] Failed to restore .htaccess file from backup', 'htaccess-lockdown'));
            return false;
        }
        
        // Update file hash after restoration
        $this->store_htaccess_hash();
        
        // Store restoration timestamp
        update_option('htaccess_lockdown_last_restore', time());
        
        // Increment restoration counter
        $restore_count = get_option('htaccess_lockdown_restore_count', 0);
        update_option('htaccess_lockdown_restore_count', $restore_count + 1);
        
        error_log(__('[HTAccess Lockdown] Successfully restored .htaccess file from backup', 'htaccess-lockdown'));
        return true;
    }
    
    /**
     * Check if backup file exists using WP Filesystem API
     *
     * @since 1.0.0
     * @return bool True if backup exists, false otherwise
     */
    public function backup_exists(): bool
    {
        // Initialize WP Filesystem
        $this->init_wp_filesystem();
        global $wp_filesystem;
        
        $backup_path = $this->get_backup_path();
        
        return $wp_filesystem ? $wp_filesystem->exists($backup_path) : file_exists($backup_path);
    }
    
    /**
     * Get backup file information using WP Filesystem API
     *
     * @since 1.0.0
     * @return array Comprehensive backup information
     */
    public function get_backup_info(): array
    {
        // Initialize WP Filesystem
        $this->init_wp_filesystem();
        global $wp_filesystem;
        
        $backup_path = $this->get_backup_path();
        $info = [
            'exists' => $wp_filesystem ? $wp_filesystem->exists($backup_path) : file_exists($backup_path),
            'path' => $backup_path,
            'size' => 0,
            'created' => get_option('htaccess_lockdown_backup_created', 0),
            'last_restore' => get_option('htaccess_lockdown_last_restore', 0),
            'restore_count' => get_option('htaccess_lockdown_restore_count', 0)
        ];
        
        if ($info['exists']) {
            if ($wp_filesystem && method_exists($wp_filesystem, 'size')) {
                $info['size'] = $wp_filesystem->size($backup_path);
            } else {
                $info['size'] = file_exists($backup_path) ? filesize($backup_path) : 0;
            }
            
            if ($wp_filesystem && method_exists($wp_filesystem, 'mtime')) {
                $info['modified'] = $wp_filesystem->mtime($backup_path);
            } else {
                $info['modified'] = file_exists($backup_path) ? filemtime($backup_path) : 0;
            }
        }
        
        return $info;
    }

    /**
     * Prevent cloning
     */
    private function __clone()
    {
        // Prevent cloning
    }

    /**
     * Lock .htaccess file permissions to read-only using WP Filesystem API
     *
     * @since 1.0.0
     * @return bool Success status
     */
    public function lock_htaccess(): bool
    {
        // SAFETY CHECK: Only allow locking if user explicitly enabled protection
        if (!$this->is_protection_enabled()) {
            error_log(__('[HTAccess Lockdown] File locking denied: Protection is disabled in settings', 'htaccess-lockdown'));
            return false;
        }
        
        // SAFETY CHECK: Only allow in admin area by administrators
        if (!is_admin() || !current_user_can('manage_options')) {
            error_log(__('[HTAccess Lockdown] File locking denied: Insufficient permissions or not in admin area', 'htaccess-lockdown'));
            return false;
        }
        
        // SAFETY CHECK: Emergency disable check
        if (defined('HTACCESS_LOCKDOWN_EMERGENCY_DISABLE') && HTACCESS_LOCKDOWN_EMERGENCY_DISABLE) {
            error_log(__('[HTAccess Lockdown] File locking denied: Emergency disable mode is active', 'htaccess-lockdown'));
            return false;
        }
        
        // Initialize WP Filesystem
        if (!$this->init_wp_filesystem()) {
            error_log(__('[HTAccess Lockdown] Cannot initialize WP Filesystem API', 'htaccess-lockdown'));
            return false;
        }
        
        global $wp_filesystem;
        $htaccess_file = ABSPATH . '.htaccess';
        
        // Check if .htaccess file exists
        if (!$wp_filesystem->exists($htaccess_file)) {
            /* translators: %s: File path */
            error_log(sprintf(__('[HTAccess Lockdown] Cannot lock .htaccess: File does not exist at %s', 'htaccess-lockdown'), $htaccess_file));
            return false;
        }
        
        // SAFETY CHECK: Create backup first if it doesn't exist
        if (!$this->backup_exists()) {
            error_log(__('[HTAccess Lockdown] Creating backup before locking file...', 'htaccess-lockdown'));
            if (!$this->create_htaccess_backup()) {
                error_log(__('[HTAccess Lockdown] Cannot lock file: Backup creation failed', 'htaccess-lockdown'));
                return false;
            }
        }
        
        // Get current permissions using WordPress method
        $current_perms = $this->get_file_permissions($htaccess_file);
        if ($current_perms === false) {
            error_log(__('[HTAccess Lockdown] Cannot read .htaccess file permissions', 'htaccess-lockdown'));
            return false;
        }
        
        // SAFETY CHECK: Don't lock if already locked
        if (($current_perms & 0777) === 0444) {
            error_log(__('[HTAccess Lockdown] File is already locked (permissions 0444)', 'htaccess-lockdown'));
            update_option('htaccess_lockdown_file_locked', true);
            return true;
        }
        
        // Store current permissions for potential restoration
        update_option('htaccess_lockdown_original_perms', $current_perms);
        
        // Attempt to set read-only permissions (0444)
        if (!$wp_filesystem->chmod($htaccess_file, 0444)) {
            error_log(__('[HTAccess Lockdown] Failed to set .htaccess file permissions to read-only (0444)', 'htaccess-lockdown'));
            return false;
        }
        
        // VERIFY the lock worked
        $new_perms = $this->get_file_permissions($htaccess_file);
        if (($new_perms & 0777) !== 0444) {
            error_log(__('[HTAccess Lockdown] Warning: File permissions may not have been set correctly', 'htaccess-lockdown'));
        }
        
        // Log successful operation
        error_log(__('[HTAccess Lockdown] Successfully locked .htaccess file permissions to read-only', 'htaccess-lockdown'));
        
        // Update plugin option to track lock status
        update_option('htaccess_lockdown_file_locked', true);
        update_option('htaccess_lockdown_last_locked', time());
        
        return true;
    }
    
    /**
     * Unlock .htaccess file permissions to writable using WP Filesystem API
     *
     * @since 1.0.0
     * @return bool Success status
     */
    public function unlock_htaccess(): bool
    {
        // Initialize WP Filesystem
        if (!$this->init_wp_filesystem()) {
            error_log(__('[HTAccess Lockdown] Cannot initialize WP Filesystem API', 'htaccess-lockdown'));
            return false;
        }
        
        global $wp_filesystem;
        $htaccess_file = ABSPATH . '.htaccess';
        
        // Check if .htaccess file exists
        if (!$wp_filesystem->exists($htaccess_file)) {
            /* translators: %s: File path */
            error_log(sprintf(__('[HTAccess Lockdown] Cannot unlock .htaccess: File does not exist at %s', 'htaccess-lockdown'), $htaccess_file));
            return false;
        }
        
        // Get original permissions if stored, otherwise default to 0644
        $original_perms = get_option('htaccess_lockdown_original_perms', false);
        $restore_perms = $original_perms ? $original_perms : 0644;
        
        // Attempt to restore permissions
        if (!$wp_filesystem->chmod($htaccess_file, $restore_perms)) {
            /* translators: %s: Octal permission value */
            error_log(sprintf(__('[HTAccess Lockdown] Failed to restore .htaccess file permissions to %s', 'htaccess-lockdown'), decoct($restore_perms)));
            return false;
        }
        
        // Log successful operation
        /* translators: %s: Octal permission value */
        error_log(sprintf(__('[HTAccess Lockdown] Successfully unlocked .htaccess file permissions to %s', 'htaccess-lockdown'), decoct($restore_perms)));
        
        // Clean up plugin options
        delete_option('htaccess_lockdown_file_locked');
        delete_option('htaccess_lockdown_original_perms');
        
        return true;
    }
    
    /**
     * Get .htaccess file lock status using WP Filesystem API
     *
     * @since 1.0.0
     * @return array Status information
     */
    public function get_htaccess_lock_status(): array
    {
        // Initialize WP Filesystem
        $this->init_wp_filesystem();
        global $wp_filesystem;
        
        $htaccess_file = ABSPATH . '.htaccess';
        $status = [
            'file_exists' => $wp_filesystem ? $wp_filesystem->exists($htaccess_file) : file_exists($htaccess_file),
            'is_locked' => get_option('htaccess_lockdown_file_locked', false),
            'current_perms' => null,
            'is_writable' => false,
            'current_hash' => null,
            'stored_hash' => get_option('htaccess_lockdown_hash', ''),
            'monitoring_active' => wp_next_scheduled('htaccess_lockdown_daily_check') !== false
        ];
        
        if ($status['file_exists']) {
            $perms = $this->get_file_permissions($htaccess_file);
            $status['current_perms'] = $perms ? decoct($perms & 0777) : null;
            $status['is_writable'] = $wp_filesystem ? $wp_filesystem->is_writable($htaccess_file) : is_writable($htaccess_file);
            $status['current_hash'] = $this->get_file_hash($htaccess_file);
        }
        
        return $status;
    }
    
    /**
     * Initialize WordPress Filesystem API
     *
     * @since 1.0.0
     * @return bool True if filesystem is available, false otherwise
     */
    private function init_wp_filesystem(): bool
    {
        global $wp_filesystem;
        
        if ($wp_filesystem) {
            return true;
        }
        
        require_once ABSPATH . 'wp-admin/includes/file.php';
        
        // Try to initialize the filesystem
        $credentials = request_filesystem_credentials('', '', false, false, null);
        
        if (false === $credentials) {
            return false;
        }
        
        if (!WP_Filesystem($credentials)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get file permissions safely
     *
     * @since 1.0.0
     * @param string $file File path
     * @return int|false File permissions or false on failure
     */
    private function get_file_permissions(string $file)
    {
        global $wp_filesystem;
        
        if ($wp_filesystem && method_exists($wp_filesystem, 'getchmod')) {
            return $wp_filesystem->getchmod($file);
        }
        
        // Fallback to native PHP function
        return fileperms($file);
    }
    
    /**
     * Get file hash safely
     *
     * @since 1.0.0
     * @param string $file File path
     * @return string|false File hash or false on failure
     */
    private function get_file_hash(string $file)
    {
        global $wp_filesystem;
        
        if ($wp_filesystem && $wp_filesystem->exists($file)) {
            $content = $wp_filesystem->get_contents($file);
            return $content !== false ? md5($content) : false;
        }
        
        // Fallback to native PHP function
        return file_exists($file) ? md5_file($file) : false;
    }
    
    /**
     * Store current .htaccess file hash for monitoring
     *
     * @since 1.0.0
     * @return bool Success status
     */
    public function store_htaccess_hash(): bool
    {
        $htaccess_file = ABSPATH . '.htaccess';
        
        // Use our safe file hash method
        $hash = $this->get_file_hash($htaccess_file);
        
        if ($hash === false) {
            error_log(__('[HTAccess Lockdown] Cannot store hash: .htaccess file does not exist or cannot be read', 'htaccess-lockdown'));
            return false;
        }
        
        update_option('htaccess_lockdown_hash', $hash);
        update_option('htaccess_lockdown_hash_timestamp', time());
        
        /* translators: %s: File hash */
        error_log(sprintf(__('[HTAccess Lockdown] Stored .htaccess file hash: %s', 'htaccess-lockdown'), $hash));
        return true;
    }
    
    /**
     * Check for .htaccess file changes (WP-Cron callback)
     * 
     * This method is called by WordPress cron to monitor for changes
     * to the .htaccess file and handle them according to plugin settings.
     *
     * @since 1.0.0
     * @return void
     */
    public function check_htaccess_changes(): void
    {
        $htaccess_file = ABSPATH . '.htaccess';
        
        // Check if file exists using safe method
        $current_hash = $this->get_file_hash($htaccess_file);
        if ($current_hash === false) {
            error_log(__('[HTAccess Lockdown] .htaccess file no longer exists - monitoring cannot continue', 'htaccess-lockdown'));
            return;
        }
        
        // Get stored hash
        $stored_hash = get_option('htaccess_lockdown_hash', '');
        
        if (empty($stored_hash)) {
            // No stored hash, store current one
            $this->store_htaccess_hash();
            return;
        }
        
        // Compare hashes
        if ($current_hash !== $stored_hash) {
            // Hash has changed - log the event
            error_log(__('[HTAccess Lockdown] SECURITY ALERT: .htaccess file has been modified!', 'htaccess-lockdown'));
            /* translators: %s: File hash */
            error_log(sprintf(__('[HTAccess Lockdown] Original hash: %s', 'htaccess-lockdown'), $stored_hash));
            /* translators: %s: File hash */
            error_log(sprintf(__('[HTAccess Lockdown] Current hash: %s', 'htaccess-lockdown'), $current_hash));
            /* translators: %s: Date and time */
            error_log(sprintf(__('[HTAccess Lockdown] File modification detected at: %s', 'htaccess-lockdown'), current_time('mysql')));
            
            // Check if we should restore from backup
            if ($this->is_restore_on_change_enabled()) {
                $restore_result = $this->restore_htaccess_from_backup();
                if ($restore_result) {
                    error_log(__('[HTAccess Lockdown] File content restored from backup after modification detected', 'htaccess-lockdown'));
                    
                    // After restoration, re-lock if auto re-lock is enabled
                    if ($this->is_auto_relock_enabled()) {
                        $this->lock_htaccess();
                    }
                } else {
                    error_log(__('[HTAccess Lockdown] Failed to restore file content from backup after modification detected', 'htaccess-lockdown'));
                    
                    // If restore failed, still try to re-lock permissions
                    if ($this->is_auto_relock_enabled()) {
                        $reset_result = $this->lock_htaccess();
                        if ($reset_result) {
                            error_log(__('[HTAccess Lockdown] File permissions reset to read-only (444) after modification detected', 'htaccess-lockdown'));
                        } else {
                            error_log(__('[HTAccess Lockdown] Failed to reset file permissions after modification detected', 'htaccess-lockdown'));
                        }
                    }
                    
                    // Update stored hash to current one if restore failed
                    $this->store_htaccess_hash();
                }
            } else {
                // No restore enabled, just check if we should reset permissions
                if ($this->is_auto_relock_enabled()) {
                    $reset_result = $this->lock_htaccess();
                    if ($reset_result) {
                        error_log(__('[HTAccess Lockdown] File permissions reset to read-only (444) after modification detected', 'htaccess-lockdown'));
                    } else {
                        error_log(__('[HTAccess Lockdown] Failed to reset file permissions after modification detected', 'htaccess-lockdown'));
                    }
                }
                
                // Update stored hash to current one
                $this->store_htaccess_hash();
            }
            
            // Update last change detection timestamp
            update_option('htaccess_lockdown_last_change_detected', time());
            
            // Increment change counter
            $change_count = get_option('htaccess_lockdown_change_count', 0);
            update_option('htaccess_lockdown_change_count', $change_count + 1);
            
            // Set flag to show admin notice
            update_option('htaccess_lockdown_show_change_notice', true);
        }
        
        // Update last check timestamp
        update_option('htaccess_lockdown_last_check', time());
    }
    
    /**
     * Schedule daily .htaccess monitoring using WP-Cron
     *
     * @since 1.0.0
     * @return bool Success status
     */
    public function schedule_monitoring(): bool
    {
        // Clear any existing scheduled event first
        $this->unschedule_monitoring();
        
        // Schedule daily check
        $scheduled = wp_schedule_event(time(), 'daily', 'htaccess_lockdown_daily_check');
        
        if ($scheduled === false) {
            error_log(__('[HTAccess Lockdown] Failed to schedule daily .htaccess monitoring', 'htaccess-lockdown'));
            return false;
        }
        
        error_log(__('[HTAccess Lockdown] Scheduled daily .htaccess monitoring', 'htaccess-lockdown'));
        return true;
    }
    
    /**
     * Unschedule .htaccess monitoring from WP-Cron
     *
     * @since 1.0.0
     * @return bool Success status
     */
    public function unschedule_monitoring(): bool
    {
        $timestamp = wp_next_scheduled('htaccess_lockdown_daily_check');
        
        if ($timestamp) {
            $cleared = wp_unschedule_event($timestamp, 'htaccess_lockdown_daily_check');
            if ($cleared) {
                error_log(__('[HTAccess Lockdown] Unscheduled .htaccess monitoring', 'htaccess-lockdown'));
                return true;
            } else {
                error_log(__('[HTAccess Lockdown] Failed to unschedule .htaccess monitoring', 'htaccess-lockdown'));
                return false;
            }
        }
        
        // Also clear all scheduled hooks as a fallback
        wp_clear_scheduled_hook('htaccess_lockdown_daily_check');
        return true;
    }
    
    /**
     * Get monitoring statistics for display in admin interface
     *
     * @since 1.0.0
     * @return array Monitoring statistics including backup information
     */
    public function get_monitoring_stats(): array
    {
        return [
            'monitoring_active' => wp_next_scheduled('htaccess_lockdown_daily_check') !== false,
            'next_check' => wp_next_scheduled('htaccess_lockdown_daily_check'),
            'last_check' => get_option('htaccess_lockdown_last_check', 0),
            'last_change_detected' => get_option('htaccess_lockdown_last_change_detected', 0),
            'change_count' => get_option('htaccess_lockdown_change_count', 0),
            'hash_stored' => !empty(get_option('htaccess_lockdown_hash', '')),
            'hash_timestamp' => get_option('htaccess_lockdown_hash_timestamp', 0),
            'backup_info' => $this->get_backup_info()
        ];
    }

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new Exception('Cannot unserialize singleton');
    }
} 