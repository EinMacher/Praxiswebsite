// Cookie Banner Functionality
class CookieManager {
    constructor() {
        this.cookieBanner = document.getElementById('cookie-banner');
        this.cookieModal = document.getElementById('cookie-settings-modal');
        this.analyticsCheckbox = document.getElementById('analytics-cookies');
        this.marketingCheckbox = document.getElementById('marketing-cookies');
        
        this.init();
    }

    init() {
        // Check if user has already made a choice
        const cookieConsent = this.getCookieConsent();
        
        if (!cookieConsent) {
            // Show banner after a short delay
            setTimeout(() => {
                this.showBanner();
            }, 1000);
        } else {
            // Apply saved preferences
            this.applyCookiePreferences(cookieConsent);
        }

        // Event listeners
        document.getElementById('cookie-accept-all').addEventListener('click', () => {
            this.acceptAllCookies();
        });

        document.getElementById('cookie-accept-essential').addEventListener('click', () => {
            this.acceptEssentialCookies();
        });

        document.getElementById('cookie-settings').addEventListener('click', () => {
            this.openSettingsModal();
        });

        document.getElementById('close-cookie-settings').addEventListener('click', () => {
            this.closeSettingsModal();
        });

        document.getElementById('save-cookie-settings').addEventListener('click', () => {
            this.saveSettings();
        });

        document.getElementById('cancel-cookie-settings').addEventListener('click', () => {
            this.closeSettingsModal();
        });

        // Footer cookie settings button
        const footerCookieSettings = document.getElementById('footer-cookie-settings');
        if (footerCookieSettings) {
            footerCookieSettings.addEventListener('click', () => {
                this.openSettingsModal();
            });
        }

        // Close modal when clicking outside
        this.cookieModal.addEventListener('click', (e) => {
            if (e.target === this.cookieModal) {
                this.closeSettingsModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !this.cookieModal.classList.contains('hidden')) {
                this.closeSettingsModal();
            }
        });
    }

    showBanner() {
        this.cookieBanner.classList.remove('translate-y-full');
    }

    hideBanner() {
        this.cookieBanner.classList.add('translate-y-full');
    }

    openSettingsModal() {
        this.cookieModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    closeSettingsModal() {
        this.cookieModal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    acceptAllCookies() {
        const consent = {
            essential: true,
            analytics: true,
            marketing: true,
            timestamp: new Date().toISOString()
        };
        
        this.setCookieConsent(consent);
        this.applyCookiePreferences(consent);
        this.hideBanner();
    }

    acceptEssentialCookies() {
        const consent = {
            essential: true,
            analytics: false,
            marketing: false,
            timestamp: new Date().toISOString()
        };
        
        this.setCookieConsent(consent);
        this.applyCookiePreferences(consent);
        this.hideBanner();
    }

    saveSettings() {
        const consent = {
            essential: true, // Always true
            analytics: this.analyticsCheckbox.checked,
            marketing: this.marketingCheckbox.checked,
            timestamp: new Date().toISOString()
        };
        
        this.setCookieConsent(consent);
        this.applyCookiePreferences(consent);
        this.closeSettingsModal();
        this.hideBanner();
    }

    getCookieConsent() {
        const consent = localStorage.getItem('cookieConsent');
        return consent ? JSON.parse(consent) : null;
    }

    setCookieConsent(consent) {
        localStorage.setItem('cookieConsent', JSON.stringify(consent));
    }

    applyCookiePreferences(consent) {
        // Essential cookies are always enabled
        if (consent.analytics) {
            this.enableAnalyticsCookies();
        } else {
            this.disableAnalyticsCookies();
        }

        if (consent.marketing) {
            this.enableMarketingCookies();
        } else {
            this.disableMarketingCookies();
        }

        // Update checkboxes in settings modal
        if (this.analyticsCheckbox) {
            this.analyticsCheckbox.checked = consent.analytics;
        }
        if (this.marketingCheckbox) {
            this.marketingCheckbox.checked = consent.marketing;
        }
    }

    enableAnalyticsCookies() {
        // Enable Google Fonts and Font Awesome
        // These are loaded via CDN, so we just track the consent
        console.log('Analytics cookies enabled');
    }

    disableAnalyticsCookies() {
        // Disable Google Fonts and Font Awesome
        // Note: This is a limitation of CDN services
        // In a production environment, you might want to load fonts locally
        console.log('Analytics cookies disabled');
    }

    enableMarketingCookies() {
        // Enable OpenStreetMap and Unsplash
        console.log('Marketing cookies enabled');
    }

    disableMarketingCookies() {
        // Disable OpenStreetMap and Unsplash
        // Note: This is a limitation of external services
        console.log('Marketing cookies disabled');
    }
}

// Initialize cookie manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new CookieManager();
});
