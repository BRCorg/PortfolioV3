/**
 * Banner de consentement cookies - Conformité RGPD
 */

document.addEventListener('DOMContentLoaded', function() {
    const cookieBanner = document.getElementById('cookie-consent-banner');
    const acceptBtn = document.getElementById('accept-cookies');
    const declineBtn = document.getElementById('decline-cookies');

    // Vérifier si l'utilisateur a déjà donné son consentement
    const cookieConsent = localStorage.getItem('cookieConsent');

    if (!cookieConsent) {
        // Afficher le banner après un court délai
        setTimeout(() => {
            cookieBanner.classList.add('show');
        }, 500);
    }

    // Accepter les cookies
    acceptBtn.addEventListener('click', function() {
        localStorage.setItem('cookieConsent', 'accepted');
        localStorage.setItem('cookieConsentDate', new Date().toISOString());
        cookieBanner.classList.remove('show');

        // Activer les cookies analytics si nécessaire
        // gtag('consent', 'update', { 'analytics_storage': 'granted' });
    });

    // Refuser les cookies
    declineBtn.addEventListener('click', function() {
        localStorage.setItem('cookieConsent', 'declined');
        localStorage.setItem('cookieConsentDate', new Date().toISOString());
        cookieBanner.classList.remove('show');
    });
});
