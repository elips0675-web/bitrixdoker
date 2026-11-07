/*!
 * PWA: регистрация service worker и баннер «Установить приложение».
 *
 * Установка доступна не везде:
 *  - Chrome / Edge / Samsung Internet на Android и десктопе присылают
 *    beforeinstallprompt — показываем кнопку, она вызывает сохранённое событие;
 *  - Safari на iOS такого события не даёт: показываем инструкцию
 *    «Поделиться → На экран “Домой”»;
 *  - остальные браузеры (Firefox, Safari на macOS) баннер не получают.
 *
 * Service worker и установка требуют HTTPS (или localhost).
 * Пути к sw.js и его scope передаёт шаблон в window.NLB_PWA.
 */

(function () {
    'use strict';

    const doc = document;
    const config = window.NLB_PWA || {};
    const syncNotices = function () {
        if (typeof window.nlbSyncNotices === 'function') {
            window.nlbSyncNotices();
        }
    };

    /* ------------------------------------------------------------------
     * 1. Регистрация service worker
     * ------------------------------------------------------------------ */
    if ('serviceWorker' in navigator && config.sw) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register(config.sw, { scope: config.scope || '/' })
                .catch(function () {
                    // Без service worker сайт работает как обычный: это не ошибка страницы.
                });
        });
    }

    const banner = doc.getElementById('nlbInstallBanner');
    if (!banner) return;

    const DISMISS_KEY = 'nlb_pwa_dismissed_at';
    const INSTALLED_KEY = 'nlb_pwa_installed';
    const DISMISS_DAYS = 30;
    const SHOW_DELAY = 2500;

    let deferredPrompt = null;
    let shown = false;
    let pendingVariant = null;

    const store = {
        get: function (key) {
            try {
                return window.localStorage.getItem(key);
            } catch (error) {
                return null;
            }
        },
        set: function (key, value) {
            try {
                window.localStorage.setItem(key, value);
            } catch (error) {
                // Приватный режим Safari: память недоступна — баннер просто покажется снова.
            }
        }
    };

    const isStandalone = function () {
        return window.matchMedia('(display-mode: standalone)').matches
            || window.matchMedia('(display-mode: minimal-ui)').matches
            || window.navigator.standalone === true;
    };

    const isIos = function () {
        const ua = window.navigator.userAgent || '';
        const iOS = /iPad|iPhone|iPod/.test(ua);
        // iPadOS 13+ представляется как Macintosh с сенсорным экраном.
        const iPadOS = /Macintosh/.test(ua) && window.navigator.maxTouchPoints > 1;
        return iOS || iPadOS;
    };

    const isDismissed = function () {
        if (store.get(INSTALLED_KEY) === '1') return true;

        const raw = store.get(DISMISS_KEY);
        if (!raw) return false;

        const at = parseInt(raw, 10);
        if (!at) return false;

        return (Date.now() - at) < DISMISS_DAYS * 24 * 60 * 60 * 1000;
    };

    const hide = function (remember) {
        if (remember) {
            store.set(DISMISS_KEY, String(Date.now()));
        }
        banner.hidden = true;
        shown = false;
        syncNotices();
    };

    const cookieNotice = doc.getElementById('nlbCookieNotice');

    const cookiePending = function () {
        return !!(cookieNotice && !cookieNotice.hidden);
    };

    const show = function (variant) {
        if (shown || isStandalone() || isDismissed()) return;

        // Не показываем два баннера одновременно: ждём выбора по cookie.
        if (cookiePending()) {
            pendingVariant = variant;
            return;
        }

        banner.dataset.variant = variant;

        banner.querySelectorAll('[data-pwa-text]').forEach(function (node) {
            node.hidden = node.dataset.pwaText !== variant;
        });

        const installButton = banner.querySelector('[data-pwa-install]');
        if (installButton) {
            installButton.hidden = variant !== 'install';
        }

        banner.hidden = false;
        shown = true;
        syncNotices();
    };

    doc.addEventListener('nlb:cookie-choice', function () {
        if (!pendingVariant) return;

        const variant = pendingVariant;
        pendingVariant = null;
        window.setTimeout(function () {
            show(variant);
        }, 400);
    });

    /* ------------------------------------------------------------------
     * 2. Chrome / Edge: сохранённое событие установки
     * ------------------------------------------------------------------ */
    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        deferredPrompt = event;
        window.setTimeout(function () {
            show('install');
        }, SHOW_DELAY);
    });

    window.addEventListener('appinstalled', function () {
        deferredPrompt = null;
        store.set(INSTALLED_KEY, '1');
        hide(false);
    });

    /* ------------------------------------------------------------------
     * 3. iOS: инструкция вместо кнопки
     * ------------------------------------------------------------------ */
    if (isIos() && !isStandalone()) {
        window.setTimeout(function () {
            if (!deferredPrompt) {
                show('ios');
            }
        }, SHOW_DELAY + 1200);
    }

    /* ------------------------------------------------------------------
     * 4. Кнопки баннера
     * ------------------------------------------------------------------ */
    banner.addEventListener('click', function (event) {
        const installButton = event.target.closest('[data-pwa-install]');
        const closeButton = event.target.closest('[data-notice-close]');

        if (closeButton) {
            hide(true);
            return;
        }

        if (!installButton || !deferredPrompt) return;

        const prompt = deferredPrompt;
        deferredPrompt = null;
        installButton.disabled = true;

        prompt.prompt();
        Promise.resolve(prompt.userChoice).then(function (choice) {
            installButton.disabled = false;
            if (choice && choice.outcome === 'accepted') {
                store.set(INSTALLED_KEY, '1');
                hide(false);
                return;
            }
            // Отказ от системного диалога: баннер прячем, но не навсегда.
            hide(true);
        }).catch(function () {
            installButton.disabled = false;
            hide(true);
        });
    });

    // Пересчёт отступа плавающих кнопок делает ResizeObserver из script.js.
})();
