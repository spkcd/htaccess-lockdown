/**
 * HTAccess Lockdown - Admin JavaScript
 *
 * @package HTAccess_Lockdown
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Main admin object
     */
    const HTAccessLockdownAdmin = {
        
        /**
         * Initialize the admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initializeComponents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Confirm actions
            $(document).on('click', '.htaccess-confirm-action', this.confirmAction);
            
            // AJAX form submissions
            $(document).on('submit', '.htaccess-ajax-form', this.handleAjaxForm);
            
            // Toggle advanced options
            $(document).on('click', '.htaccess-toggle-advanced', this.toggleAdvanced);
            
            // Manual check button
            $(document).on('click', '#manual-check-btn', this.handleManualCheck);
            
            // Manual lock button
            $(document).on('click', '#manual-lock-btn', this.handleManualLock);
        },

        /**
         * Initialize components
         */
        initializeComponents: function() {
            // Initialize tooltips if available
            if (typeof $.fn.tooltip !== 'undefined') {
                $('[data-toggle="tooltip"]').tooltip();
            }
            
            // Check for any initial notices
            this.checkInitialNotices();
        },

        /**
         * Handle confirmation dialogs
         */
        confirmAction: function(e) {
            const message = $(this).data('confirm') || htaccessLockdown.i18n.confirm;
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        },

        /**
         * Handle AJAX form submissions
         */
        handleAjaxForm: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('[type="submit"]');
            const $spinner = $form.find('.htaccess-spinner');
            
            // Show loading state
            $submitBtn.prop('disabled', true);
            $spinner.show();
            
            // Prepare form data
            const formData = $form.serialize();
            formData.append('action', $form.data('action') || 'htaccess_lockdown_ajax');
            formData.append('nonce', htaccessLockdown.nonce);
            
            // Send AJAX request
            $.post(htaccessLockdown.ajaxUrl, formData)
                .done(function(response) {
                    if (response.success) {
                        HTAccessLockdownAdmin.showNotice(response.data.message || htaccessLockdown.i18n.success, 'success');
                        
                        // Reload page if requested
                        if (response.data.reload) {
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        }
                    } else {
                        HTAccessLockdownAdmin.showNotice(response.data || htaccessLockdown.i18n.error, 'error');
                    }
                })
                .fail(function() {
                    HTAccessLockdownAdmin.showNotice(htaccessLockdown.i18n.error, 'error');
                })
                .always(function() {
                    // Hide loading state
                    $submitBtn.prop('disabled', false);
                    $spinner.hide();
                });
        },

        /**
         * Toggle advanced options
         */
        toggleAdvanced: function(e) {
            e.preventDefault();
            
            const $toggle = $(this);
            const $target = $($toggle.data('target'));
            
            $target.slideToggle();
            
            // Update toggle text
            const showText = $toggle.data('show-text') || 'Show Advanced';
            const hideText = $toggle.data('hide-text') || 'Hide Advanced';
            
            $toggle.text($target.is(':visible') ? hideText : showText);
        },

        /**
         * Show notice message
         */
        showNotice: function(message, type) {
            type = type || 'info';
            
            const $notice = $('<div class="htaccess-notice ' + type + '">' + message + '</div>');
            
            // Insert notice
            $('.wrap h1').after($notice);
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(function() {
                    $notice.fadeOut();
                }, 5000);
            }
            
            // Scroll to notice
            $('html, body').animate({
                scrollTop: $notice.offset().top - 50
            }, 500);
        },

        /**
         * Check for initial notices from server
         */
        checkInitialNotices: function() {
            // This can be used to display notices passed from PHP
            if (typeof htaccessLockdownNotices !== 'undefined' && htaccessLockdownNotices.length > 0) {
                $.each(htaccessLockdownNotices, function(index, notice) {
                    HTAccessLockdownAdmin.showNotice(notice.message, notice.type);
                });
            }
        },

        /**
         * Utility function to get URL parameter
         */
        getUrlParameter: function(name) {
            name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
            const regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
            const results = regex.exec(location.search);
            return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
        },

        /**
         * Handle manual .htaccess check
         */
        handleManualCheck: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const $spinner = $btn.siblings('.htaccess-spinner');
            
            // Show loading state
            $btn.prop('disabled', true);
            $spinner.show();
            
            // Send AJAX request
            $.post(htaccessLockdown.ajaxUrl, {
                action: 'htaccess_manual_check',
                nonce: htaccessLockdown.nonce
            })
            .done(function(response) {
                if (response.success) {
                    HTAccessLockdownAdmin.showNotice(response.data.message, 'success');
                    
                    // Reload page if requested
                    if (response.data.reload) {
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                } else {
                    HTAccessLockdownAdmin.showNotice(response.data || htaccessLockdown.i18n.error, 'error');
                }
            })
            .fail(function() {
                HTAccessLockdownAdmin.showNotice(htaccessLockdown.i18n.error, 'error');
            })
            .always(function() {
                // Hide loading state
                $btn.prop('disabled', false);
                $spinner.hide();
            });
        },

        /**
         * Handle manual .htaccess lock
         */
        handleManualLock: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const $spinner = $btn.siblings('.htaccess-spinner');
            
            // Show loading state
            $btn.prop('disabled', true);
            $spinner.show();
            
            // Send AJAX request
            $.post(htaccessLockdown.ajaxUrl, {
                action: 'htaccess_manual_lock',
                nonce: htaccessLockdown.nonce
            })
            .done(function(response) {
                if (response.success) {
                    HTAccessLockdownAdmin.showNotice(response.data.message, 'success');
                    
                    // Reload page if requested
                    if (response.data.reload) {
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                } else {
                    HTAccessLockdownAdmin.showNotice(response.data || htaccessLockdown.i18n.error, 'error');
                }
            })
            .fail(function() {
                HTAccessLockdownAdmin.showNotice(htaccessLockdown.i18n.error, 'error');
            })
            .always(function() {
                // Hide loading state
                $btn.prop('disabled', false);
                $spinner.hide();
            });
        },

        /**
         * Utility function to validate form fields
         */
        validateForm: function($form) {
            let isValid = true;
            
            // Check required fields
            $form.find('[required]').each(function() {
                const $field = $(this);
                if (!$field.val().trim()) {
                    $field.addClass('error');
                    isValid = false;
                } else {
                    $field.removeClass('error');
                }
            });
            
            return isValid;
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        HTAccessLockdownAdmin.init();
    });

    // Make admin object globally available
    window.HTAccessLockdownAdmin = HTAccessLockdownAdmin;

})(jQuery); 