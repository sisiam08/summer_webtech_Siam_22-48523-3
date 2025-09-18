/**
 * Modern Notification System for Web Application
 * Provides toast notifications, modal dialogs, and confirmation popups
 */

class NotificationSystem {
    constructor() {
        this.container = null;
        this.notifications = new Map();
        this.loadingNotification = null; // Store current loading notification
        this.init();
    }

    init() {
        // Ensure DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.createContainer());
        } else {
            this.createContainer();
        }
    }

    createContainer() {
        // Create notification container if it doesn't exist
        this.container = document.querySelector('.notification-container') || 
                        document.querySelector('#notification-container');
        
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'notification-container';
            this.container.id = 'notification-container';
            document.body.appendChild(this.container);
        } else {
            // Ensure it has the right class
            this.container.classList.add('notification-container');
        }
    }

    /**
     * Show a toast notification
     * @param {string} message - The message to display
     * @param {string} type - success, error, warning, info
     * @param {object} options - Additional options
     */
    showToast(message, type = 'success', options = {}) {
        const config = {
            duration: options.duration || 5000,
            title: options.title || this.getDefaultTitle(type),
            showProgress: options.showProgress !== false,
            persistent: options.persistent || false,
            action: options.action || null,
            ...options
        };

        const notification = this.createNotification(message, type, config);
        this.container.appendChild(notification);

        // Trigger show animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);

        // Auto remove if not persistent
        if (!config.persistent) {
            this.scheduleRemoval(notification, config.duration);
        }

        return notification;
    }

    /**
     * Show a modal dialog
     * @param {string} message - The message to display
     * @param {string} type - success, error, warning, info
     * @param {object} options - Additional options
     */
    showModal(message, type = 'info', options = {}) {
        const config = {
            title: options.title || this.getDefaultTitle(type),
            confirmText: options.confirmText || 'OK',
            cancelText: options.cancelText || null,
            onConfirm: options.onConfirm || null,
            onCancel: options.onCancel || null,
            ...options
        };

        const modal = this.createModal(message, type, config);
        document.body.appendChild(modal);

        // Trigger show animation
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);

        return modal;
    }

    /**
     * Show a confirmation dialog
     * @param {string} message - The message to display
     * @param {object} options - Additional options
     */
    showConfirm(message, options = {}) {
        return new Promise((resolve) => {
            const config = {
                title: options.title || 'Confirm Action',
                confirmText: options.confirmText || 'Confirm',
                cancelText: options.cancelText || 'Cancel',
                type: options.type || 'warning',
                onConfirm: () => resolve(true),
                onCancel: () => resolve(false),
                ...options
            };

            this.showModal(message, config.type, config);
        });
    }

    /**
     * Show a loading notification
     * @param {string} message - The loading message
     */
    showLoading(message = 'Processing...') {
        // Hide existing loading notification if any
        this.hideLoading();
        
        const loadingIcon = '<div class="notification-loading"></div>';
        
        this.loadingNotification = this.showToast(message, 'info', {
            title: 'Loading',
            persistent: true,
            showProgress: false,
            customIcon: loadingIcon
        });
        
        return this.loadingNotification;
    }

    /**
     * Hide the current loading notification
     */
    hideLoading() {
        if (this.loadingNotification) {
            this.hideNotification(this.loadingNotification);
            this.loadingNotification = null;
        }
    }

    /**
     * Hide a specific notification
     * @param {HTMLElement} notification - The notification element to hide
     */
    hideNotification(notification) {
        if (notification && notification.parentElement) {
            notification.classList.remove('show');
            notification.classList.add('hide');
            
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }
    }

    /**
     * Hide all notifications
     */
    hideAll() {
        const notifications = this.container.querySelectorAll('.notification');
        notifications.forEach(notification => {
            this.hideNotification(notification);
        });
    }

    /**
     * Create a notification element
     */
    createNotification(message, type, config) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        
        const iconMap = {
            success: 'fa-check',
            error: 'fa-times',
            warning: 'fa-exclamation',
            info: 'fa-info'
        };

        const icon = config.customIcon || `<i class="fas ${iconMap[type]}"></i>`;
        
        notification.innerHTML = `
            <div class="notification-icon">
                ${icon}
            </div>
            <div class="notification-content">
                ${config.title ? `<div class="notification-title">${config.title}</div>` : ''}
                <div class="notification-message">${message}</div>
            </div>
            <button class="notification-close" onclick="notificationSystem.hideNotification(this.parentElement)">
                <i class="fas fa-times"></i>
            </button>
            ${config.showProgress ? '<div class="notification-progress"></div>' : ''}
        `;

        // Add click action if provided
        if (config.action) {
            notification.style.cursor = 'pointer';
            notification.addEventListener('click', (e) => {
                if (!e.target.classList.contains('notification-close')) {
                    config.action();
                }
            });
        }

        return notification;
    }

    /**
     * Create a modal element
     */
    createModal(message, type, config) {
        const overlay = document.createElement('div');
        overlay.className = 'notification-modal-overlay';
        
        const iconMap = {
            success: 'fa-check',
            error: 'fa-times',
            warning: 'fa-exclamation',
            info: 'fa-info'
        };

        const cancelButton = config.cancelText ? 
            `<button class="notification-modal-btn secondary" onclick="notificationSystem.closeModal(this, false)">${config.cancelText}</button>` : '';

        overlay.innerHTML = `
            <div class="notification-modal ${type}">
                <div class="notification-modal-header">
                    <div class="notification-modal-icon">
                        <i class="fas ${iconMap[type]}"></i>
                    </div>
                    <div class="notification-modal-title">${config.title}</div>
                </div>
                <div class="notification-modal-message">${message}</div>
                <div class="notification-modal-actions">
                    ${cancelButton}
                    <button class="notification-modal-btn primary" onclick="notificationSystem.closeModal(this, true)">${config.confirmText}</button>
                </div>
            </div>
        `;

        // Store callbacks
        overlay._onConfirm = config.onConfirm;
        overlay._onCancel = config.onCancel;

        // Close on overlay click
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                this.closeModal(overlay, false);
            }
        });

        return overlay;
    }

    /**
     * Close a modal
     */
    closeModal(element, confirmed) {
        const overlay = element.closest('.notification-modal-overlay');
        if (!overlay) return;

        overlay.classList.remove('show');
        
        setTimeout(() => {
            if (confirmed && overlay._onConfirm) {
                overlay._onConfirm();
            } else if (!confirmed && overlay._onCancel) {
                overlay._onCancel();
            }
            
            if (overlay.parentElement) {
                overlay.remove();
            }
        }, 300);
    }

    /**
     * Schedule notification removal
     */
    scheduleRemoval(notification, duration) {
        if (duration <= 0) return;

        const progressBar = notification.querySelector('.notification-progress');
        if (progressBar) {
            progressBar.style.width = '100%';
            progressBar.style.transition = `width ${duration}ms linear`;
            
            // Start progress animation
            setTimeout(() => {
                progressBar.style.width = '0%';
            }, 10);
        }

        setTimeout(() => {
            this.hideNotification(notification);
        }, duration);
    }

    /**
     * Get default title for notification type
     */
    getDefaultTitle(type) {
        const titles = {
            success: 'Success',
            error: 'Error',
            warning: 'Warning',
            info: 'Information'
        };
        return titles[type] || 'Notification';
    }
}

// Initialize global notification system
let notificationSystem;

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        notificationSystem = new NotificationSystem();
        setupGlobalFunctions();
    });
} else {
    notificationSystem = new NotificationSystem();
    setupGlobalFunctions();
}

// Setup global helper functions
function setupGlobalFunctions() {
    // Global helper functions for easy access
    window.showNotification = (message, type = 'success', options = {}) => {
        if (!notificationSystem || !notificationSystem.container) {
            // Fallback to simple alert if notification system fails
            console.warn('Notification system not available, using fallback');
            if (type === 'error') {
                alert('Error: ' + message);
            } else {
                alert(message);
            }
            return;
        }
        return notificationSystem.showToast(message, type, options);
    };

    window.showModal = (message, type = 'info', options = {}) => {
        if (!notificationSystem) {
            console.error('Notification system not initialized');
            return;
        }
        return notificationSystem.showModal(message, type, options);
    };

    window.showConfirm = (message, options = {}) => {
        if (!notificationSystem) {
            console.error('Notification system not initialized');
            return;
        }
        return notificationSystem.showConfirm(message, options);
    };

    window.showLoading = (message = 'Processing...') => {
        if (!notificationSystem) {
            console.error('Notification system not initialized');
            return;
        }
        return notificationSystem.showLoading(message);
    };

    window.hideLoading = () => {
        if (!notificationSystem) {
            console.error('Notification system not initialized');
            return;
        }
        return notificationSystem.hideLoading();
    };

    window.hideAllNotifications = () => {
        if (!notificationSystem) {
            console.error('Notification system not initialized');
            return;
        }
        return notificationSystem.hideAll();
    };
}

// Replace native alert, confirm functions
window.originalAlert = window.alert;
window.originalConfirm = window.confirm;

window.alert = (message) => {
    showModal(message, 'info', {
        title: 'Alert',
        confirmText: 'OK'
    });
};

window.confirm = (message) => {
    return showConfirm(message, {
        title: 'Confirm',
        confirmText: 'OK',
        cancelText: 'Cancel'
    });
};

// Export for modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationSystem;
}