/**
 * DentoSys Common JavaScript Functions
 * ================================================================
 * Shared utilities and helper functions used across the application
 */

// Common modal functionality
window.DentoSys = window.DentoSys || {};

DentoSys.Modal = {
    /**
     * Close modal by ID
     */
    close: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
        }
    },

    /**
     * Show modal by ID
     */
    show: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'block';
        }
    },

    /**
     * Initialize modal close handlers
     */
    init: function() {
        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal, [id$="Modal"]');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        };

        // Close modals with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modals = document.querySelectorAll('.modal, [id$="Modal"]');
                modals.forEach(modal => {
                    if (modal.style.display === 'block') {
                        modal.style.display = 'none';
                    }
                });
            }
        });
    }
};

// Common form utilities
DentoSys.Form = {
    /**
     * Validate required fields
     */
    validateRequired: function(formId, requiredIds) {
        let valid = true;
        requiredIds.forEach(id => {
            const element = document.getElementById(id);
            if (element && !element.value.trim()) {
                element.classList.add('error');
                valid = false;
            } else if (element) {
                element.classList.remove('error');
            }
        });
        return valid;
    },

    /**
     * Reset form validation errors
     */
    clearErrors: function(formId) {
        const form = document.getElementById(formId);
        if (form) {
            form.querySelectorAll('.error').forEach(el => {
                el.classList.remove('error');
            });
        }
    },

    /**
     * Show loading state on submit button
     */
    setLoading: function(buttonId, loading = true) {
        const button = document.getElementById(buttonId);
        if (button) {
            if (loading) {
                button.disabled = true;
                button.dataset.originalText = button.textContent;
                button.textContent = 'Processing...';
            } else {
                button.disabled = false;
                button.textContent = button.dataset.originalText || 'Submit';
            }
        }
    }
};

// Common utilities
DentoSys.Utils = {
    /**
     * Generate random password
     */
    generatePassword: function(length = 12) {
        const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        let password = '';
        for (let i = 0; i < length; i++) {
            password += charset.charAt(Math.floor(Math.random() * charset.length));
        }
        return password;
    },

    /**
     * Copy text to clipboard
     */
    copyToClipboard: function(text) {
        navigator.clipboard.writeText(text).then(() => {
            this.showToast('Copied to clipboard');
        }).catch(() => {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            this.showToast('Copied to clipboard');
        });
    },

    /**
     * Show temporary toast notification
     */
    showToast: function(message, duration = 3000) {
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #059669;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            font-weight: 500;
            animation: slideIn 0.3s ease-out;
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease-in forwards';
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, duration);
    },

    /**
     * Format currency
     */
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('en-AU', {
            style: 'currency',
            currency: 'AUD'
        }).format(amount);
    },

    /**
     * Format date
     */
    formatDate: function(date, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        };
        return new Date(date).toLocaleDateString('en-AU', { ...defaultOptions, ...options });
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    DentoSys.Modal.init();
});

// Add CSS for toast animations
if (!document.querySelector('#dentosys-common-styles')) {
    const style = document.createElement('style');
    style.id = 'dentosys-common-styles';
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        .error {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
        }
    `;
    document.head.appendChild(style);
}
