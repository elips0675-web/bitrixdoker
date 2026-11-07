/*!
 * Уведомление о cookie.
 *
 * Сайт ставит только технические cookie Битрикса: сессию, авторизацию и защиту
 * форм. Аналитических и рекламных трекеров нет, поэтому выбор пользователя
 * сейчас ничего не блокирует — он сохраняется на случай, если такие скрипты
 * появятся. Значение пишется в localStorage, чтобы не спрашивать повторно.
 *
 * Выбор можно изменить: кнопка «Cookie» в подвале сайта открывает уведомление снова.
 */

(function () {
    'use strict';

    const doc = document;
    const notice = doc.getElementById('nlbCookieNotice');
    if (!notice) return;

    const KEY = 'nlb_cookie_choice';
    const syncNotices = function () {
        if (typeof window.nlbSyncNotices === 'function') {
            window.nlbSyncNotices();
        }
    };

    const readChoice = function () {
        try {
            const raw = window.localStorage.getItem(KEY);
            if (!raw) return null;
            const parsed = JSON.parse(raw);
            return parsed && parsed.value ? parsed.value : null;
        } catch (error) {
            return null;
        }
    };

    const saveChoice = function (value) {
        try {
            window.localStorage.setItem(KEY, JSON.stringify({
                value: value,
                savedAt: new Date().toISOString()
            }));
        } catch (error) {
            // Приватный режим: выбор не сохранится, уведомление покажется при следующем визите.
        }

        doc.dispatchEvent(new CustomEvent('nlb:cookie-choice', {
            bubbles: true,
            detail: { value: value }
        }));
    };

    const hide = function () {
        notice.hidden = true;
        syncNotices();
    };

    const show = function () {
        notice.hidden = false;
        syncNotices();
    };

    if (!readChoice()) {
        // Небольшая задержка: даём странице отрисоваться, чтобы баннер не мигал.
        window.setTimeout(show, 800);
    }

    notice.addEventListener('click', function (event) {
        const choiceButton = event.target.closest('[data-cookie-choice]');
        if (!choiceButton) return;

        saveChoice(choiceButton.dataset.cookieChoice);
        hide();
    });

    doc.querySelectorAll('[data-cookie-settings]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            show();
        });
    });
})();
