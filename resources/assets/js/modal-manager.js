/**
 * Modal Manager - Enhanced modal lifecycle management for OphimCore
 * Provides robust modal handling with backdrop cleanup and error recovery
 */

window.ModalManager = (function() {
    'use strict';

    let debugMode = false;

    function log(message, data = null) {
        if (debugMode) {
            console.log('[ModalManager]', message, data || '');
        }
    }

    function error(message, data = null) {
        console.error('[ModalManager]', message, data || '');
    }

    return {
        // Enable/disable debug logging
        setDebugMode: function(enabled) {
            debugMode = !!enabled;
        },

        // Clean up any existing modal backdrops and reset body state
        cleanupBackdrops: function() {
            try {
                log('Cleaning up modal backdrops');
                
                // Remove all backdrop elements
                $('.modal-backdrop').remove();
                
                // Reset body classes and styles
                $('body').removeClass('modal-open');
                $('body').css('padding-right', '');
                
                // Reset any stuck modal states
                $('.modal').each(function() {
                    const $modal = $(this);
                    if (!$modal.hasClass('show')) {
                        $modal.attr('aria-hidden', 'true');
                        $modal.removeAttr('aria-modal');
                        $modal.css('display', '');
                    }
                });

                log('Backdrop cleanup completed');
            } catch (err) {
                error('Error during backdrop cleanup:', err);
            }
        },

        // Show modal with proper error handling and configuration
        showModal: function(modalId, options = {}) {
            try {
                log('Showing modal:', modalId);
                
                const $modal = $(modalId);
                if ($modal.length === 0) {
                    error('Modal not found:', modalId);
                    return false;
                }

                // Clean up any existing backdrops first
                this.cleanupBackdrops();

                // Configure modal options with defaults
                const modalOptions = {
                    backdrop: options.backdrop !== false,
                    keyboard: options.keyboard !== false,
                    focus: options.focus !== false,
                    show: true
                };

                log('Modal options:', modalOptions);

                // Show the modal
                $modal.modal(modalOptions);
                
                // Verify modal is shown after a brief delay
                setTimeout(() => {
                    if (!$modal.hasClass('show')) {
                        error('Modal failed to show properly:', modalId);
                        this.cleanupBackdrops();
                    } else {
                        log('Modal shown successfully:', modalId);
                    }
                }, 100);

                return true;
            } catch (err) {
                error('Error showing modal:', err);
                this.cleanupBackdrops();
                return false;
            }
        },

        // Hide modal with proper cleanup
        hideModal: function(modalId) {
            try {
                log('Hiding modal:', modalId);
                
                const $modal = $(modalId);
                if ($modal.length > 0) {
                    $modal.modal('hide');
                }
                
                // Force cleanup after Bootstrap's transition time
                setTimeout(() => {
                    this.cleanupBackdrops();
                    log('Modal hidden and cleaned up:', modalId);
                }, 300);
            } catch (err) {
                error('Error hiding modal:', err);
                this.cleanupBackdrops();
            }
        },

        // Reset modal state completely
        resetModal: function(modalId) {
            try {
                log('Resetting modal:', modalId);
                
                const $modal = $(modalId);
                if ($modal.length > 0) {
                    $modal.removeClass('show');
                    $modal.attr('aria-hidden', 'true');
                    $modal.removeAttr('aria-modal');
                    $modal.css('display', '');
                }
                
                this.cleanupBackdrops();
                log('Modal reset completed:', modalId);
            } catch (err) {
                error('Error resetting modal:', err);
                this.cleanupBackdrops();
            }
        },

        // Check for stuck backdrops and provide recovery
        checkForStuckBackdrops: function() {
            const hasBackdrop = $('.modal-backdrop').length > 0;
            const hasVisibleModal = $('.modal.show').length > 0;
            
            if (hasBackdrop && !hasVisibleModal) {
                log('Detected stuck backdrop, cleaning up');
                this.cleanupBackdrops();
                return true;
            }
            
            return false;
        },

        // Emergency recovery function
        emergencyRecovery: function() {
            try {
                log('Performing emergency modal recovery');
                
                // Hide all modals
                $('.modal').modal('hide');
                
                // Force cleanup
                this.cleanupBackdrops();
                
                // Reset all modal states
                $('.modal').each((index, modal) => {
                    this.resetModal('#' + modal.id);
                });

                // Show recovery notification
                if (window.Noty) {
                    new Noty({
                        type: 'info',
                        text: 'Interface recovered. You can now interact with the page normally.',
                        timeout: 3000
                    }).show();
                }

                log('Emergency recovery completed');
                return true;
            } catch (err) {
                error('Error during emergency recovery:', err);
                return false;
            }
        },

        // Initialize modal manager with global handlers
        initialize: function(options = {}) {
            log('Initializing ModalManager');
            
            debugMode = options.debug || false;

            // Set up global modal event handlers
            $(document).on('show.bs.modal', '.modal', (e) => {
                log('Modal showing:', e.target.id);
                this.cleanupBackdrops();
            });

            $(document).on('hidden.bs.modal', '.modal', (e) => {
                log('Modal hidden:', e.target.id);
                setTimeout(() => this.cleanupBackdrops(), 100);
            });

            // Periodic check for stuck backdrops
            if (options.autoCleanup !== false) {
                setInterval(() => {
                    this.checkForStuckBackdrops();
                }, 5000);
            }

            // Emergency recovery on page unload
            $(window).on('beforeunload', () => {
                this.cleanupBackdrops();
            });

            // Add emergency recovery button if enabled
            if (options.emergencyButton !== false) {
                this.addEmergencyButton();
            }

            log('ModalManager initialized');
        },

        // Add emergency recovery button to page
        addEmergencyButton: function() {
            if ($('#modal-emergency-recovery').length === 0) {
                $('body').append(`
                    <button id="modal-emergency-recovery" 
                            style="position: fixed; top: 10px; right: 10px; z-index: 9999; display: none;" 
                            class="btn btn-warning btn-sm"
                            title="Click if the interface becomes unresponsive">
                        <i class="fa fa-refresh"></i> Fix Interface
                    </button>
                `);

                $('#modal-emergency-recovery').click(() => {
                    this.emergencyRecovery();
                    $('#modal-emergency-recovery').hide();
                });

                // Show button if backdrop is stuck
                setInterval(() => {
                    if (this.checkForStuckBackdrops()) {
                        $('#modal-emergency-recovery').show();
                    } else {
                        $('#modal-emergency-recovery').hide();
                    }
                }, 2000);
            }
        }
    };
})();

// Auto-initialize on document ready
$(document).ready(function() {
    if (window.ModalManager) {
        window.ModalManager.initialize({
            debug: false,
            autoCleanup: true,
            emergencyButton: true
        });
    }
});