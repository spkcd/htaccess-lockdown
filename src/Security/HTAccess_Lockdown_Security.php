<?php
/**
 * Security functionality
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
 * Security class for HTAccess Lockdown
 *
 * @since 1.0.0
 */
class HTAccess_Lockdown_Security
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Initialize security functionality
     *
     * @return void
     */
    private function init(): void
    {
        // Only initialize if protection is enabled
        $plugin = HTAccess_Lockdown::get_instance();
        
        if (!$plugin->is_protection_enabled()) {
            return;
        }

        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     *
     * @return void
     */
    private function init_hooks(): void
    {
        // Security hooks will be added here
        add_action('wp_loaded', [$this, 'check_security_status']);
        
        // Add basic security headers
        add_action('send_headers', [$this, 'add_security_headers']);
    }

    /**
     * Check security status
     *
     * @return void
     */
    public function check_security_status(): void
    {
        // Security status checks will be implemented here
        // This is where we'll check .htaccess rules, permissions, etc.
    }

    /**
     * Add basic security headers
     *
     * @return void
     */
    public function add_security_headers(): void
    {
        // Add basic security headers
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
    }

    /**
     * Log security events
     *
     * @param string $message Log message
     * @param string $level Log level (info, warning, error)
     * @return void
     */
    public function log_security_event(string $message, string $level = 'info'): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log(sprintf(
                '[HTAccess Lockdown] [%s] %s',
                strtoupper($level),
                $message
            ));
        }
    }

    /**
     * Get security status
     *
     * @return array Security status information
     */
    public function get_security_status(): array
    {
        return [
            'lockdown_enabled' => $this->is_lockdown_enabled(),
            'htaccess_writable' => $this->is_htaccess_writable(),
            'wp_version_hidden' => $this->is_wp_version_hidden(),
            'last_check' => get_option('htaccess_lockdown_last_check', 0),
        ];
    }

    /**
     * Check if lockdown is enabled
     *
     * @return bool
     */
    private function is_lockdown_enabled(): bool
    {
        $options = get_option('htaccess_lockdown_options', ['enable_lockdown' => false]);
        return (bool) $options['enable_lockdown'];
    }

    /**
     * Check if .htaccess file is writable
     *
     * @return bool
     */
    private function is_htaccess_writable(): bool
    {
        $htaccess_file = ABSPATH . '.htaccess';
        
        // If file doesn't exist, check if directory is writable
        if (!file_exists($htaccess_file)) {
            return is_writable(ABSPATH);
        }
        
        return is_writable($htaccess_file);
    }

    /**
     * Check if WordPress version is hidden
     *
     * @return bool
     */
    private function is_wp_version_hidden(): bool
    {
        // This will be implemented when we add .htaccess functionality
        // For now, return false as placeholder
        return false;
    }

    /**
     * Verify nonce for security operations
     *
     * @param string $nonce Nonce to verify
     * @param string $action Nonce action
     * @return bool
     */
    public function verify_nonce(string $nonce, string $action = 'htaccess_lockdown_nonce'): bool
    {
        return wp_verify_nonce($nonce, $action);
    }

    /**
     * Sanitize input for security operations
     *
     * @param mixed $input Input to sanitize
     * @param string $type Type of sanitization
     * @return mixed Sanitized input
     */
    public function sanitize_input($input, string $type = 'text'): mixed
    {
        switch ($type) {
            case 'email':
                return sanitize_email($input);
            case 'url':
                return esc_url_raw($input);
            case 'textarea':
                return sanitize_textarea_field($input);
            case 'key':
                return sanitize_key($input);
            case 'text':
            default:
                return sanitize_text_field($input);
        }
    }
} 