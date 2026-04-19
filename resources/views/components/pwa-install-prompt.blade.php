{{-- PWA Install Prompt Banner --}}
{{-- Shows on mobile devices when app is not installed and not recently dismissed --}}
<div id="pwa-install-banner" style="display:none;">
    <style>
        #pwa-install-banner {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            pointer-events: none;
        }
        #pwa-install-banner .pwa-banner-inner {
            max-width: 480px;
            margin: 0 auto 1rem;
            padding: 1.25rem 1.5rem;
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 -4px 30px rgba(0, 0, 0, 0.12), 0 4px 20px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 1rem;
            pointer-events: all;
            animation: pwa-slide-up 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid rgba(231, 26, 57, 0.12);
        }
        @keyframes pwa-slide-up {
            from {
                transform: translateY(100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        #pwa-install-banner .pwa-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            flex-shrink: 0;
            object-fit: cover;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        #pwa-install-banner .pwa-text {
            flex: 1;
            min-width: 0;
        }
        #pwa-install-banner .pwa-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #111827;
            margin: 0 0 0.15rem;
            line-height: 1.3;
        }
        #pwa-install-banner .pwa-desc {
            font-size: 0.78rem;
            color: #6b7280;
            margin: 0;
            line-height: 1.4;
        }
        #pwa-install-banner .pwa-actions {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            flex-shrink: 0;
        }
        #pwa-install-banner .pwa-btn-install {
            background: #e71a39;
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            white-space: nowrap;
        }
        #pwa-install-banner .pwa-btn-install:hover {
            background: #c91432;
        }
        #pwa-install-banner .pwa-btn-dismiss {
            background: transparent;
            color: #9ca3af;
            border: none;
            padding: 0.25rem 0.5rem;
            font-size: 0.72rem;
            cursor: pointer;
            text-align: center;
            transition: color 0.2s;
        }
        #pwa-install-banner .pwa-btn-dismiss:hover {
            color: #6b7280;
        }
        /* iOS-specific instruction styling */
        #pwa-install-banner .pwa-ios-steps {
            font-size: 0.78rem;
            color: #6b7280;
            margin: 0.3rem 0 0;
            line-height: 1.5;
        }
        #pwa-install-banner .pwa-ios-steps strong {
            color: #374151;
        }

        @media (max-width: 520px) {
            #pwa-install-banner .pwa-banner-inner {
                margin: 0 0.75rem 0.75rem;
                padding: 1rem 1.25rem;
            }
        }
    </style>

    <div class="pwa-banner-inner">
        <img src="/images/icons/icon-192x192.png" alt="Booklix" class="pwa-icon">
        <div class="pwa-text">
            <p class="pwa-title">Install Booklix</p>
            <p class="pwa-desc" id="pwa-description">Add Booklix to your home screen for quick access.</p>
            <p class="pwa-ios-steps" id="pwa-ios-steps" style="display:none;">
                Tap <strong>Share</strong> <span style="font-size:1.1em">⬆️</span> then <strong>"Add to Home Screen"</strong>
            </p>
        </div>
        <div class="pwa-actions">
            <button class="pwa-btn-install" id="pwa-btn-install" type="button">Install</button>
            <button class="pwa-btn-dismiss" id="pwa-btn-dismiss" type="button">Not now</button>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    const DISMISS_KEY = 'pwa-dismissed';
    const DISMISS_DAYS = 7;
    const banner = document.getElementById('pwa-install-banner');
    const installBtn = document.getElementById('pwa-btn-install');
    const dismissBtn = document.getElementById('pwa-btn-dismiss');
    const iosSteps = document.getElementById('pwa-ios-steps');
    const description = document.getElementById('pwa-description');

    let deferredPrompt = null;

    // --- Guards: don't show if already installed or recently dismissed ---

    // 1. Already running as standalone PWA
    if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
        return;
    }

    // 2. Not a mobile device
    if (!isMobileDevice()) {
        return;
    }

    // 3. Recently dismissed
    if (isRecentlyDismissed()) {
        return;
    }

    // --- Platform detection ---

    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    const isAndroid = /Android/i.test(navigator.userAgent);

    if (isIOS) {
        // iOS: Show manual instructions (no beforeinstallprompt support)
        // Only show in Safari (not in-app browsers like Chrome on iOS)
        const isSafari = /Safari/i.test(navigator.userAgent) && !/CriOS|FxiOS|OPiOS|EdgiOS/i.test(navigator.userAgent);
        if (!isSafari) {
            description.textContent = 'Open this page in Safari to install Booklix on your device.';
            iosSteps.style.display = 'none';
            installBtn.style.display = 'none';
            showBanner();
            return;
        }
        iosSteps.style.display = 'block';
        description.style.display = 'none';
        installBtn.textContent = 'Got it';
        installBtn.addEventListener('click', function() {
            dismissBanner();
        });
        showBanner();
    } else if (isAndroid) {
        // Android: Listen for the native beforeinstallprompt event
        window.addEventListener('beforeinstallprompt', function(e) {
            e.preventDefault();
            deferredPrompt = e;
            showBanner();
        });

        installBtn.addEventListener('click', function() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then(function(choiceResult) {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('[PWA] User accepted install');
                    }
                    deferredPrompt = null;
                    hideBanner();
                });
            }
        });
    }

    // Dismiss button
    dismissBtn.addEventListener('click', function() {
        dismissBanner();
    });

    // Listen for successful install
    window.addEventListener('appinstalled', function() {
        console.log('[PWA] App installed');
        hideBanner();
        deferredPrompt = null;
    });

    // --- Helper functions ---

    function showBanner() {
        banner.style.display = 'block';
    }

    function hideBanner() {
        banner.style.display = 'none';
    }

    function dismissBanner() {
        localStorage.setItem(DISMISS_KEY, Date.now().toString());
        hideBanner();
    }

    function isRecentlyDismissed() {
        const dismissed = localStorage.getItem(DISMISS_KEY);
        if (!dismissed) return false;
        const dismissedTime = parseInt(dismissed, 10);
        const daysSince = (Date.now() - dismissedTime) / (1000 * 60 * 60 * 24);
        return daysSince < DISMISS_DAYS;
    }

    function isMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
            || (navigator.maxTouchPoints && navigator.maxTouchPoints > 2 && /Macintosh/i.test(navigator.userAgent));
    }
})();
</script>
