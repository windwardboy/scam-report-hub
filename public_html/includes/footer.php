<footer class="bg-light py-4 mt-auto"> 
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5 class="h6">Pages</h5>
                <ul class="list-unstyled">
                    <li><a href="/official-resources.php" class="text-decoration-none">Official Scam Reporting Resources</a></li>
                    <li><a href="/privacy-policy.php" class="text-decoration-none">Privacy Policy</a></li>
                    <li><a href="/terms-of-service.php" class="text-decoration-none">Terms of Service</a></li>
                </ul>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Scam Report Hub. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

<script defer type="text/javascript" src="https://cdn.jsdelivr.net/npm/klaro@0.7.22/dist/klaro.min.js" crossorigin="anonymous"></script> 

<script type="text/javascript">
    // Define the Klaro configuration object
    var klaroConfig = {
        version: 1,
        storageMethod: 'cookie',
        cookieName: 'scamhub_consent', 
        cookieExpiresAfterDays: 365,
        privacyPolicy: '/privacy-policy.php', // <-- URL for automatic link
        default: false, 
        mustConsent: false, 
        acceptAll: true, 
        hideDeclineAll: false, 
        noticeAsModal: false, 

        // Define services/apps at the top level
        services: [ 
            { name: 'googleRecaptcha', title: 'Google reCAPTCHA', purposes: ['security'], cookies: [/^_GRECAPTCHA/], required: false, optOut: false, }
        ],

        // Define purposes at the top level
        purposes: {
            security: { title: 'Security & Spam Protection', description: 'These services help protect the site against malicious activity like bot attacks and spam form submissions.', }
        },

        // Translations for all texts
        translations: {
            en: {
                consentModal: {
                    title: 'Cookies & Site Functionality', 
                    // ** MODIFIED: Removed manual link text from end **
                    description: 'This website uses essential cookies for basic functions and security features like reCAPTCHA to protect against spam. Accepting helps us ensure site security. You can manage your preferences here.', 
                    privacyPolicy: { 
                        text: 'Privacy Policy', // <-- Text for automatic link in modal
                    }, 
                },
                consentNotice: {
                     // ** MODIFIED: Removed manual link text from end **
                     description: 'We use cookies for functionality and security (reCAPTCHA).', 
                     learnMore: 'Preferences', 
                     acceptAll: 'Accept All',
                     decline: 'Decline',
                },
                // Text overrides for services/purposes (optional)
                googleRecaptcha: { description: 'Helps protect our forms from automated spam bots.' },
                security: { title: 'Security' } 
            }
        }
    };

    // Initialize Klaro
    if (typeof Klaro !== 'undefined') {
        Klaro.initialize(klaroConfig);
    } else {
        console.error("Klaro! library not loaded. Consent banner cannot be initialized.");
    }

</script>