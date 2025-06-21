<?php
/**
 * Admin functionality
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
 * Admin class for HTAccess Lockdown
 *
 * @since 1.0.0
 */
class HTAccess_Lockdown_Admin
{
    /**
     * Constructor - Initialize the admin functionality
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Initialize admin functionality and register hooks
     *
     * @since 1.0.0
     * @return void
     */
    private function init(): void
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'admin_init']);
        add_filter('plugin_action_links_' . HTACCESS_LOCKDOWN_PLUGIN_BASENAME, [$this, 'add_plugin_action_links']);
        
        // Add admin notices
        add_action('admin_notices', [$this, 'show_change_notice']);
        
        // Add AJAX handlers
        add_action('wp_ajax_htaccess_manual_check', [$this, 'handle_manual_check']);
        add_action('wp_ajax_htaccess_manual_lock', [$this, 'handle_manual_lock']);
        add_action('wp_ajax_htaccess_restore_from_notice', [$this, 'handle_restore_from_notice']);
        add_action('wp_ajax_htaccess_dismiss_notice', [$this, 'handle_dismiss_notice']);
    }

    /**
     * Add admin menu page to WordPress admin
     *
     * @since 1.0.0
     * @return void
     */
    public function add_admin_menu(): void
    {
        add_options_page(
            __('HTAccess Lockdown', 'htaccess-lockdown'),
            __('HTAccess Lockdown', 'htaccess-lockdown'),
            'manage_options',
            'htaccess-lockdown',
            [$this, 'admin_page']
        );
    }

    /**
     * Initialize admin settings using WordPress Settings API
     *
     * @since 1.0.0
     * @return void
     */
    public function admin_init(): void
    {
        // Register settings
        register_setting(
            'htaccess_lockdown_settings_group',
            'htaccess_lockdown_settings',
            [
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => $this->get_default_settings(),
            ]
        );

        // Add settings sections and fields
        add_settings_section(
            'htaccess_lockdown_protection',
            __('Protection Settings', 'htaccess-lockdown'),
            [$this, 'protection_section_callback'],
            'htaccess-lockdown'
        );

        add_settings_field(
            'enable_protection',
            __('Enable .htaccess Protection', 'htaccess-lockdown'),
            [$this, 'enable_protection_callback'],
            'htaccess-lockdown',
            'htaccess_lockdown_protection'
        );

        add_settings_field(
            'auto_relock',
            __('Auto Re-lock on Changes', 'htaccess-lockdown'),
            [$this, 'auto_relock_callback'],
            'htaccess-lockdown',
            'htaccess_lockdown_protection'
        );

        add_settings_field(
            'enable_restore_on_change',
            __('Restore from Backup on Changes', 'htaccess-lockdown'),
            [$this, 'enable_restore_callback'],
            'htaccess-lockdown',
            'htaccess_lockdown_protection'
        );

        // Add file status section
        add_settings_section(
            'htaccess_lockdown_status',
            __('File Status & Actions', 'htaccess-lockdown'),
            [$this, 'status_section_callback'],
            'htaccess-lockdown'
        );
    }

    /**
     * Display the main admin page with settings form
     *
     * @since 1.0.0
     * @return void
     */
    public function admin_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'htaccess-lockdown'));
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php settings_errors(); ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('htaccess_lockdown_settings_group');
                do_settings_sections('htaccess-lockdown');
                submit_button(__('Save Settings', 'htaccess-lockdown'));
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Protection settings section callback for WordPress Settings API
     *
     * @since 1.0.0
     * @return void
     */
    public function protection_section_callback(): void
    {
        echo '<p>' . esc_html__('Configure .htaccess file protection and monitoring settings.', 'htaccess-lockdown') . '</p>';
    }

    /**
     * Enable protection field callback for WordPress Settings API
     *
     * @since 1.0.0
     * @return void
     */
    public function enable_protection_callback(): void
    {
        $settings = get_option('htaccess_lockdown_settings', $this->get_default_settings());
        $checked = checked($settings['enable_protection'], true, false);
        
        echo '<input type="checkbox" id="enable_protection" name="htaccess_lockdown_settings[enable_protection]" value="1" ' . $checked . ' />';
        echo '<label for="enable_protection">' . esc_html__('Enable .htaccess file protection', 'htaccess-lockdown') . '</label>';
        echo '<p class="description">' . esc_html__('When enabled, the .htaccess file will be locked with read-only permissions and monitored for changes.', 'htaccess-lockdown') . '</p>';
    }

    /**
     * Auto re-lock field callback for WordPress Settings API
     *
     * @since 1.0.0
     * @return void
     */
    public function auto_relock_callback(): void
    {
        $settings = get_option('htaccess_lockdown_settings', $this->get_default_settings());
        $checked = checked($settings['auto_relock'], true, false);
        
        echo '<input type="checkbox" id="auto_relock" name="htaccess_lockdown_settings[auto_relock]" value="1" ' . $checked . ' />';
        echo '<label for="auto_relock">' . esc_html__('Automatically re-lock file when changes are detected', 'htaccess-lockdown') . '</label>';
        echo '<p class="description">' . esc_html__('When enabled, the file permissions will be automatically reset to read-only if unauthorized changes are detected.', 'htaccess-lockdown') . '</p>';
    }

    /**
     * Enable restore field callback for WordPress Settings API
     *
     * @since 1.0.0
     * @return void
     */
    public function enable_restore_callback(): void
    {
        $settings = get_option('htaccess_lockdown_settings', $this->get_default_settings());
        $checked = checked($settings['enable_restore_on_change'], true, false);
        
        echo '<input type="checkbox" id="enable_restore_on_change" name="htaccess_lockdown_settings[enable_restore_on_change]" value="1" ' . $checked . ' />';
        echo '<label for="enable_restore_on_change">' . esc_html__('Restore original .htaccess content from backup when changes are detected', 'htaccess-lockdown') . '</label>';
        echo '<p class="description">' . esc_html__('When enabled, the .htaccess file content will be restored from the backup created during plugin activation if unauthorized changes are detected.', 'htaccess-lockdown') . '</p>';
        
        // Show backup status
        $plugin = HTAccess_Lockdown::get_instance();
        $backup_exists = $plugin->backup_exists();
        
        echo '<p class="description">';
        echo '<strong>' . esc_html__('Backup Status:', 'htaccess-lockdown') . '</strong> ';
        if ($backup_exists) {
            echo '<span class="htaccess-status enabled">' . esc_html__('Available', 'htaccess-lockdown') . '</span>';
        } else {
            echo '<span class="htaccess-status warning">' . esc_html__('Not Found', 'htaccess-lockdown') . '</span>';
        }
        echo '</p>';
    }

    /**
     * Status section callback for WordPress Settings API
     *
     * @since 1.0.0
     * @return void
     */
    public function status_section_callback(): void
    {
        echo '<p>' . esc_html__('Current .htaccess file status and available actions.', 'htaccess-lockdown') . '</p>';
        $this->display_file_status();
        $this->display_manual_actions();
    }

    /**
     * Get default plugin settings
     *
     * @since 1.0.0
     * @return array Default settings array
     */
    private function get_default_settings(): array
    {
        return [
            'enable_protection' => true,
            'auto_relock' => true,
            'enable_restore_on_change' => false,
        ];
    }

    /**
     * Sanitize settings input from WordPress Settings API
     *
     * @since 1.0.0
     * @param array $input Raw input from settings form
     * @return array Sanitized settings
     */
    public function sanitize_settings(array $input): array
    {
        $sanitized = [];
        
        $sanitized['enable_protection'] = !empty($input['enable_protection']);
        $sanitized['auto_relock'] = !empty($input['auto_relock']);
        $sanitized['enable_restore_on_change'] = !empty($input['enable_restore_on_change']);
        
        // If protection is being enabled, ensure monitoring is set up
        if ($sanitized['enable_protection']) {
            $plugin = HTAccess_Lockdown::get_instance();
            $plugin->store_htaccess_hash();
            $plugin->schedule_monitoring();
        }
        
        return $sanitized;
    }

    /**
     * Display .htaccess file status information in admin interface
     *
     * @since 1.0.0
     * @return void
     */
    private function display_file_status(): void
    {
        $plugin = HTAccess_Lockdown::get_instance();
        $status = $plugin->get_htaccess_lock_status();
        $htaccess_file = ABSPATH . '.htaccess';
        
        echo '<div class="htaccess-lockdown-info">';
        echo '<h4>' . esc_html__('File Information', 'htaccess-lockdown') . '</h4>';
        
        if (!$status['file_exists']) {
            echo '<p><span class="htaccess-status warning">' . esc_html__('Warning', 'htaccess-lockdown') . '</span> ';
            esc_html_e('.htaccess file does not exist', 'htaccess-lockdown');
            echo '</p>';
            echo '</div>';
            return;
        }
        
        // File basic info
        echo '<p><strong>' . esc_html__('File Path:', 'htaccess-lockdown') . '</strong> ' . esc_html($htaccess_file) . '</p>';
        
        // Permissions
        echo '<p><strong>' . esc_html__('Current Permissions:', 'htaccess-lockdown') . '</strong> ';
        echo '<code>' . esc_html($status['current_perms'] ?? 'Unknown') . '</code>';
        if ($status['is_writable']) {
            echo ' <span class="htaccess-status warning">' . esc_html__('Writable', 'htaccess-lockdown') . '</span>';
        } else {
            echo ' <span class="htaccess-status enabled">' . esc_html__('Read-only', 'htaccess-lockdown') . '</span>';
        }
        echo '</p>';
        
        // Last modified
        $last_modified = filemtime($htaccess_file);
        if ($last_modified) {
            echo '<p><strong>' . esc_html__('Last Modified:', 'htaccess-lockdown') . '</strong> ';
            echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_modified));
            echo '</p>';
        }
        
        // Lock status
        echo '<p><strong>' . esc_html__('Protection Status:', 'htaccess-lockdown') . '</strong> ';
        if ($status['is_locked']) {
            echo '<span class="htaccess-status enabled">' . esc_html__('Protected', 'htaccess-lockdown') . '</span>';
        } else {
            echo '<span class="htaccess-status disabled">' . esc_html__('Unprotected', 'htaccess-lockdown') . '</span>';
        }
        echo '</p>';
        
        echo '</div>';
        
        // Display monitoring information
        $this->display_monitoring_status();
    }
    
    /**
     * Display file monitoring status information in admin interface
     *
     * @since 1.0.0
     * @return void
     */
    private function display_monitoring_status(): void
    {
        $plugin = HTAccess_Lockdown::get_instance();
        $monitoring_stats = $plugin->get_monitoring_stats();
        $status = $plugin->get_htaccess_lock_status();
        
        echo '<h4>' . esc_html__('File Monitoring', 'htaccess-lockdown') . '</h4>';
        
        echo '<p><strong>' . esc_html__('Monitoring Active:', 'htaccess-lockdown') . '</strong> ';
        if ($monitoring_stats['monitoring_active']) {
            echo '<span class="htaccess-status enabled">' . esc_html__('Yes', 'htaccess-lockdown') . '</span>';
        } else {
            echo '<span class="htaccess-status disabled">' . esc_html__('No', 'htaccess-lockdown') . '</span>';
        }
        echo '</p>';
        
        if ($monitoring_stats['monitoring_active'] && $monitoring_stats['next_check']) {
            echo '<p><strong>' . esc_html__('Next Check:', 'htaccess-lockdown') . '</strong> ';
            echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $monitoring_stats['next_check']));
            echo '</p>';
        }
        
        if ($monitoring_stats['last_check']) {
            echo '<p><strong>' . esc_html__('Last Check:', 'htaccess-lockdown') . '</strong> ';
            echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $monitoring_stats['last_check']));
            echo '</p>';
        }
        
        echo '<p><strong>' . esc_html__('Changes Detected:', 'htaccess-lockdown') . '</strong> ';
        echo esc_html($monitoring_stats['change_count']);
        echo '</p>';
        
        if ($monitoring_stats['last_change_detected']) {
            echo '<p><strong>' . esc_html__('Last Change:', 'htaccess-lockdown') . '</strong> ';
            echo '<span class="htaccess-status warning">';
            echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $monitoring_stats['last_change_detected']));
            echo '</span></p>';
        }
        
        // Display backup information
        $backup_info = $monitoring_stats['backup_info'];
        if ($backup_info['exists']) {
            echo '<p><strong>' . esc_html__('Backup Available:', 'htaccess-lockdown') . '</strong> ';
            echo '<span class="htaccess-status enabled">' . esc_html__('Yes', 'htaccess-lockdown') . '</span>';
            
            if ($backup_info['created']) {
                echo ' <small>(' . esc_html(date_i18n(get_option('date_format'), $backup_info['created'])) . ')</small>';
            }
            echo '</p>';
            
            if ($backup_info['restore_count'] > 0) {
                echo '<p><strong>' . esc_html__('Restorations:', 'htaccess-lockdown') . '</strong> ';
                echo esc_html($backup_info['restore_count']);
                
                if ($backup_info['last_restore']) {
                    echo ' <small>(' . esc_html__('Last:', 'htaccess-lockdown') . ' ';
                    echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $backup_info['last_restore']));
                    echo ')</small>';
                }
                echo '</p>';
            }
        } else {
            echo '<p><strong>' . esc_html__('Backup Available:', 'htaccess-lockdown') . '</strong> ';
            echo '<span class="htaccess-status warning">' . esc_html__('No', 'htaccess-lockdown') . '</span></p>';
        }
        
        // Display hash information
        if ($monitoring_stats['hash_stored']) {
            echo '<p><strong>' . esc_html__('File Hash Match:', 'htaccess-lockdown') . '</strong> ';
            $hash_match = !empty($status['current_hash']) && !empty($status['stored_hash']) && 
                         $status['current_hash'] === $status['stored_hash'];
            
            if ($hash_match) {
                echo '<span class="htaccess-status enabled">' . esc_html__('Yes', 'htaccess-lockdown') . '</span>';
            } else {
                echo '<span class="htaccess-status warning">' . esc_html__('No', 'htaccess-lockdown') . '</span>';
            }
            echo '</p>';
            
            if (!empty($status['current_hash'])) {
                echo '<p><strong>' . esc_html__('Current Hash:', 'htaccess-lockdown') . '</strong> ';
                echo '<code>' . esc_html(substr($status['current_hash'], 0, 8)) . '...</code></p>';
            }
            
            if (!empty($status['stored_hash'])) {
                echo '<p><strong>' . esc_html__('Stored Hash:', 'htaccess-lockdown') . '</strong> ';
                echo '<code>' . esc_html(substr($status['stored_hash'], 0, 8)) . '...</code></p>';
            }
        } else {
            echo '<p><strong>' . esc_html__('Hash Status:', 'htaccess-lockdown') . '</strong> ';
                         echo '<span class="htaccess-status warning">' . esc_html__('Not Stored', 'htaccess-lockdown') . '</span>';
             echo '</p>';
         }
         
    }
    
    /**
     * Display manual action buttons
     *
     * @return void
     */
    private function display_manual_actions(): void
    {
        $plugin = HTAccess_Lockdown::get_instance();
        $status = $plugin->get_htaccess_lock_status();
        $monitoring_stats = $plugin->get_monitoring_stats();
        $settings = get_option('htaccess_lockdown_settings', $this->get_default_settings());
        
        if (!$status['file_exists']) {
            return;
        }
        
        echo '<div class="htaccess-lockdown-actions">';
        echo '<h4>' . esc_html__('Manual Actions', 'htaccess-lockdown') . '</h4>';
        
        // Manual lock button
        if ($settings['enable_protection']) {
            echo '<p>';
            echo '<button type="button" class="htaccess-lockdown-button" id="manual-lock-btn" data-action="htaccess_manual_lock">';
            if ($status['is_locked']) {
                esc_html_e('Re-lock .htaccess File', 'htaccess-lockdown');
            } else {
                esc_html_e('Lock .htaccess File', 'htaccess-lockdown');
            }
            echo '</button>';
            echo '<span class="htaccess-spinner"></span>';
            echo '</p>';
            echo '<p class="description">' . esc_html__('Manually set the .htaccess file permissions to read-only (444).', 'htaccess-lockdown') . '</p>';
        }
        
        // Manual check button
        if ($monitoring_stats['monitoring_active']) {
            echo '<p>';
            echo '<button type="button" class="htaccess-lockdown-button secondary" id="manual-check-btn" data-action="htaccess_manual_check">';
            esc_html_e('Run Manual Check', 'htaccess-lockdown');
            echo '</button>';
            echo '<span class="htaccess-spinner"></span>';
            echo '</p>';
            echo '<p class="description">' . esc_html__('Manually trigger a file integrity check.', 'htaccess-lockdown') . '</p>';
        }
        
        echo '</div>';
    }
    
    /**
     * Handle manual .htaccess check via AJAX
     *
     * @return void
     */
    public function handle_manual_check(): void
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'htaccess_lockdown_nonce')) {
            wp_die(__('Security check failed.', 'htaccess-lockdown'));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'htaccess-lockdown'));
        }
        
        // Run the check
        $plugin = HTAccess_Lockdown::get_instance();
        $plugin->check_htaccess_changes();
        
        // Get updated status
        $monitoring_stats = $plugin->get_monitoring_stats();
        
        wp_send_json_success([
            'message' => __('Manual check completed successfully.', 'htaccess-lockdown'),
            'last_check' => $monitoring_stats['last_check'] ? 
                date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $monitoring_stats['last_check']) : 
                __('Never', 'htaccess-lockdown'),
            'reload' => true
        ]);
    }

    /**
     * Handle manual .htaccess lock via AJAX
     *
     * @return void
     */
    public function handle_manual_lock(): void
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'htaccess_lockdown_nonce')) {
            wp_die(__('Security check failed.', 'htaccess-lockdown'));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'htaccess-lockdown'));
        }
        
        // Check if protection is enabled
        $settings = get_option('htaccess_lockdown_settings', $this->get_default_settings());
        if (!$settings['enable_protection']) {
            wp_send_json_error(__('Protection is disabled in settings.', 'htaccess-lockdown'));
        }
        
        // Run the lock
        $plugin = HTAccess_Lockdown::get_instance();
        $lock_result = $plugin->lock_htaccess();
        
        if ($lock_result) {
            wp_send_json_success([
                'message' => __('.htaccess file locked successfully.', 'htaccess-lockdown'),
                'reload' => true
            ]);
        } else {
            wp_send_json_error(__('Failed to lock .htaccess file. Check error logs for details.', 'htaccess-lockdown'));
        }
    }

    /**
     * Handle restore from notice via AJAX
     *
     * @return void
     */
    public function handle_restore_from_notice(): void
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'htaccess_lockdown_notice')) {
            wp_die(__('Security check failed.', 'htaccess-lockdown'));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'htaccess-lockdown'));
        }
        
        // Check if protection is enabled
        $settings = get_option('htaccess_lockdown_settings', $this->get_default_settings());
        if (!$settings['enable_protection']) {
            wp_send_json_error(__('Protection is disabled in settings.', 'htaccess-lockdown'));
        }
        
        // Run the restore
        $plugin = HTAccess_Lockdown::get_instance();
        $restore_result = $plugin->restore_htaccess_from_backup();
        
        if ($restore_result) {
            // Clear the notice flag
            delete_option('htaccess_lockdown_show_change_notice');
            
            // Record the dismissal timestamp
            $change_time = intval($_POST['change_time'] ?? 0);
            if ($change_time) {
                update_option('htaccess_lockdown_dismissed_change', $change_time);
            }
            
            // Re-lock if auto re-lock is enabled
            if ($settings['auto_relock']) {
                $plugin->lock_htaccess();
            }
            
            wp_send_json_success([
                'message' => __('.htaccess file has been restored from backup successfully.', 'htaccess-lockdown')
            ]);
        } else {
            wp_send_json_error(__('Failed to restore .htaccess file from backup. Check error logs for details.', 'htaccess-lockdown'));
        }
    }

    /**
     * Handle dismiss notice via AJAX
     *
     * @return void
     */
    public function handle_dismiss_notice(): void
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'htaccess_lockdown_notice')) {
            wp_die(__('Security check failed.', 'htaccess-lockdown'));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'htaccess-lockdown'));
        }
        
        // Clear the notice flag
        delete_option('htaccess_lockdown_show_change_notice');
        
        // Record the dismissal timestamp
        $change_time = intval($_POST['change_time'] ?? 0);
        if ($change_time) {
            update_option('htaccess_lockdown_dismissed_change', $change_time);
        }
        
        wp_send_json_success([
            'message' => __('Notice dismissed. You can view file status in the plugin settings.', 'htaccess-lockdown')
        ]);
    }

    /**
     * Show change detection notice to administrators
     *
     * @return void
     */
    public function show_change_notice(): void
    {
        // Only show to administrators
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check if notice should be shown
        if (!get_option('htaccess_lockdown_show_change_notice', false)) {
            return;
        }
        
        // Check if user has dismissed this specific change
        $last_change = get_option('htaccess_lockdown_last_change_detected', 0);
        $dismissed_change = get_option('htaccess_lockdown_dismissed_change', 0);
        
        if ($last_change && $dismissed_change >= $last_change) {
            return;
        }
        
        $plugin = HTAccess_Lockdown::get_instance();
        $backup_info = $plugin->get_backup_info();
        
        ?>
        <div class="notice notice-error is-dismissible htaccess-change-notice" data-change-time="<?php echo esc_attr($last_change); ?>">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-warning" style="color: #d63638; font-size: 20px;"></span>
                <div style="flex: 1;">
                    <h3 style="margin: 0 0 10px 0;"><?php esc_html_e('HTAccess Lockdown: Unauthorized File Modification Detected', 'htaccess-lockdown'); ?></h3>
                    
                    <p style="margin: 5px 0;">
                        <strong><?php esc_html_e('Last Change Detected:', 'htaccess-lockdown'); ?></strong>
                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_change)); ?>
                    </p>
                    
                    <p style="margin: 5px 0;">
                        <?php esc_html_e('Your .htaccess file has been modified unexpectedly. This could indicate unauthorized access or changes.', 'htaccess-lockdown'); ?>
                    </p>
                    
                    <?php if ($backup_info['exists']): ?>
                        <p style="margin: 10px 0 5px 0;">
                            <button type="button" class="button button-primary htaccess-notice-action" data-action="restore">
                                <span class="dashicons dashicons-backup" style="vertical-align: text-top;"></span>
                                <?php esc_html_e('Restore from Backup', 'htaccess-lockdown'); ?>
                            </button>
                            
                            <button type="button" class="button htaccess-notice-action" data-action="ignore" style="margin-left: 10px;">
                                <span class="dashicons dashicons-dismiss" style="vertical-align: text-top;"></span>
                                <?php esc_html_e('Ignore This Change', 'htaccess-lockdown'); ?>
                            </button>
                            
                            <span class="htaccess-spinner" style="margin-left: 10px;"></span>
                        </p>
                        
                        <p style="margin: 5px 0; font-size: 12px; color: #666;">
                            <?php esc_html_e('Backup created:', 'htaccess-lockdown'); ?>
                            <?php echo esc_html(date_i18n(get_option('date_format'), $backup_info['created'])); ?>
                        </p>
                    <?php else: ?>
                        <p style="margin: 10px 0 5px 0;">
                            <button type="button" class="button htaccess-notice-action" data-action="ignore">
                                <span class="dashicons dashicons-dismiss" style="vertical-align: text-top;"></span>
                                <?php esc_html_e('Acknowledge', 'htaccess-lockdown'); ?>
                            </button>
                            
                            <span class="htaccess-spinner" style="margin-left: 10px;"></span>
                        </p>
                        
                        <p style="margin: 5px 0; font-size: 12px; color: #d63638;">
                            <?php esc_html_e('Note: No backup available for restoration.', 'htaccess-lockdown'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Handle notice action buttons
            $('.htaccess-notice-action').on('click', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var $notice = $btn.closest('.htaccess-change-notice');
                var $spinner = $notice.find('.htaccess-spinner');
                var action = $btn.data('action');
                var changeTime = $notice.data('change-time');
                
                // Disable buttons and show spinner
                $notice.find('.htaccess-notice-action').prop('disabled', true);
                $spinner.show();
                
                var ajaxAction = action === 'restore' ? 'htaccess_restore_from_notice' : 'htaccess_dismiss_notice';
                
                $.post(ajaxurl, {
                    action: ajaxAction,
                    nonce: '<?php echo wp_create_nonce('htaccess_lockdown_notice'); ?>',
                    change_time: changeTime
                })
                .done(function(response) {
                    if (response.success) {
                        $notice.fadeOut();
                        if (response.data.message) {
                            $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>')
                                .insertAfter($notice);
                        }
                    } else {
                        alert(response.data || '<?php esc_html_e('An error occurred. Please try again.', 'htaccess-lockdown'); ?>');
                        // Re-enable buttons
                        $notice.find('.htaccess-notice-action').prop('disabled', false);
                        $spinner.hide();
                    }
                })
                .fail(function() {
                    alert('<?php esc_html_e('An error occurred. Please try again.', 'htaccess-lockdown'); ?>');
                    // Re-enable buttons
                    $notice.find('.htaccess-notice-action').prop('disabled', false);
                    $spinner.hide();
                });
            });
            
            // Handle notice dismissal via X button
            $('.htaccess-change-notice .notice-dismiss').on('click', function() {
                var $notice = $(this).closest('.htaccess-change-notice');
                var changeTime = $notice.data('change-time');
                
                $.post(ajaxurl, {
                    action: 'htaccess_dismiss_notice',
                    nonce: '<?php echo wp_create_nonce('htaccess_lockdown_notice'); ?>',
                    change_time: changeTime
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Add plugin action links
     *
     * @param array $links Existing plugin action links
     * @return array Modified plugin action links
     */
    public function add_plugin_action_links(array $links): array
    {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('options-general.php?page=htaccess-lockdown'),
            __('Settings', 'htaccess-lockdown')
        );
        
        array_unshift($links, $settings_link);
        
        return $links;
    }
} 