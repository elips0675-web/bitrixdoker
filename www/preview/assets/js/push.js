/*!
 * Web Push: включение и выключение уведомлений о бронировании.
 *
 * Уведомления получают только сотрудники: карточка выводится в личном кабинете
 * администратора. Браузер отдаёт подписку (endpoint + ключи), она уходит на
 * local/ajax/push.php и хранится в опции модуля.
 *
 * Требования: HTTPS (или localhost), поддержка Push API и service worker.
 * Пути и публичный ключ передаёт шаблон в window.NLB_PUSH.
 */

(function () {
    'use strict';

    var config = window.NLB_PUSH || {};
    var card = document.querySelector('[data-push-card]');
    if (!card) return;

    var statusNode = card.querySelector('[data-push-status]');
    var enableButton = card.querySelector('[data-push-enable]');
    var disableButton = card.querySelector('[data-push-disable]');

    var setStatus = function (text, tone) {
        if (!statusNode) return;
        statusNode.textContent = text || '';
        statusNode.dataset.tone = tone || '';
    };

    var showButtons = function (subscribed) {
        if (enableButton) enableButton.hidden = subscribed;
        if (disableButton) disableButton.hidden = !subscribed;
    };

    var base64ToBytes = function (value) {
        var padding = '='.repeat((4 - (value.length % 4)) % 4);
        var base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
        var raw = window.atob(base64);
        var bytes = new Uint8Array(raw.length);
        for (var i = 0; i < raw.length; i++) {
            bytes[i] = raw.charCodeAt(i);
        }
        return bytes;
    };

    var post = function (action, extra) {
        var body = new URLSearchParams();
        body.set('action', action);
        body.set('sessid', config.sessid || '');
        Object.keys(extra || {}).forEach(function (key) {
            body.set(key, extra[key]);
        });

        return window.fetch(config.endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        }).then(function (response) {
            return response.json().catch(function () {
                return { ok: false };
            });
        });
    };

    var supported = function () {
        return !!(config.enabled
            && 'serviceWorker' in navigator
            && 'PushManager' in window
            && 'Notification' in window);
    };

    if (!supported()) {
        setStatus(config.unsupportedText || 'Уведомления не поддерживаются этим браузером.', 'muted');
        if (enableButton) enableButton.disabled = true;
        return;
    }

    var registration = null;

    var ready = function () {
        if (registration) return Promise.resolve(registration);
        return navigator.serviceWorker.ready.then(function (value) {
            registration = value;
            return value;
        });
    };

    var refresh = function () {
        return ready()
            .then(function (reg) {
                return reg.pushManager.getSubscription();
            })
            .then(function (subscription) {
                showButtons(!!subscription);
                if (subscription) {
                    setStatus(config.enabledText || 'Уведомления включены.', 'success');
                } else {
                    setStatus(config.idleText || 'Уведомления выключены.', 'muted');
                }
            })
            .catch(function () {
                setStatus(config.idleText || 'Уведомления выключены.', 'muted');
            });
    };

    var requestPermission = function () {
        if (Notification.permission === 'granted') {
            return Promise.resolve(true);
        }
        if (Notification.permission === 'denied') {
            return Promise.resolve(false);
        }
        return Notification.requestPermission().then(function (permission) {
            return permission === 'granted';
        });
    };

    var enable = function () {
        if (enableButton) enableButton.disabled = true;
        setStatus(config.pendingText || 'Запрашиваем разрешение…', 'muted');

        requestPermission()
            .then(function (granted) {
                if (!granted) {
                    throw new Error('permission_denied');
                }
                return ready();
            })
            .then(function (reg) {
                return reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: base64ToBytes(config.publicKey || '')
                });
            })
            .then(function (subscription) {
                return post('subscribe', { subscription: JSON.stringify(subscription.toJSON()) })
                    .then(function (result) {
                        if (!result || !result.ok) {
                            throw new Error((result && result.error) || 'subscribe_failed');
                        }
                        return subscription;
                    });
            })
            .then(function () {
                showButtons(true);
                setStatus(config.enabledText || 'Уведомления включены.', 'success');
            })
            .catch(function (error) {
                var code = error && error.message ? error.message : '';
                if (code === 'permission_denied') {
                    setStatus(config.deniedText || 'Разрешение на уведомления не выдано.', 'error');
                } else {
                    setStatus(config.errorText || 'Не удалось включить уведомления. Попробуйте позже.', 'error');
                }
            })
            .then(function () {
                if (enableButton) enableButton.disabled = false;
            });
    };

    var disable = function () {
        if (disableButton) disableButton.disabled = true;

        ready()
            .then(function (reg) {
                return reg.pushManager.getSubscription();
            })
            .then(function (subscription) {
                if (!subscription) return null;
                var endpoint = subscription.endpoint;
                return subscription.unsubscribe().then(function () {
                    return post('unsubscribe', { endpoint: endpoint });
                });
            })
            .then(function () {
                showButtons(false);
                setStatus(config.idleText || 'Уведомления выключены.', 'muted');
            })
            .catch(function () {
                setStatus(config.errorText || 'Не удалось выключить уведомления. Попробуйте позже.', 'error');
            })
            .then(function () {
                if (disableButton) disableButton.disabled = false;
            });
    };

    if (enableButton) {
        enableButton.addEventListener('click', enable);
    }
    if (disableButton) {
        disableButton.addEventListener('click', disable);
    }

    refresh();
})();
