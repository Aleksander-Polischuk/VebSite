/* ========================================================================
   НАЛАШТУВАННЯ ТА КЕШУВАННЯ ІКОНОК
   ======================================================================== */
const ICON_PATHS = {
    'success': '/img/check-square-fill.svg',
    'error':   '/img/x-square-fill.svg',
    'warning': '/img/exclamation-triangle-fill.svg'
};

// 2. Функція отримання іконки (Спочатку LocalStorage, потім Сервер)
function getIconSrc(type) {
    const cached = localStorage.getItem('cached_icon_' + type);
    if (cached) {
        return cached;
    }
    return ICON_PATHS[type] || '';
}

// 3. Функція кешування
(async function initIconCache() {
    for (const [name, url] of Object.entries(ICON_PATHS)) {
        if (!localStorage.getItem('cached_icon_' + name)) {
            try {
                const response = await fetch(url);
                if (response.ok) {
                    const blob = await response.blob();
                    const reader = new FileReader();
                    reader.onloadend = () => {
                        localStorage.setItem('cached_icon_' + name, reader.result);
                        console.log(`✅ Іконка [${name}] закешована.`);
                    };
                    reader.readAsDataURL(blob);
                }
            } catch (err) {
                console.warn(`⚠️ Кешування не вдалося для [${name}]:`, err);
            }
        }
    }
})();

/* ========================================================================
   ОСНОВНА ЛОГІКА ALERT
   ======================================================================== */

// Кешуємо елементи DOM
const alertOverlay = document.getElementById('customAlertOverlay');
const alertHeader  = document.querySelector('.custom-alert-header');
const alertText    = document.getElementById('customAlertText');
const alertTitle   = document.getElementById('customAlertTitle');
const alertIcon    = document.getElementById('customAlertIcon');
const alertButtons = document.getElementById('customAlertButtons');
const alertSubText = document.getElementById('customAlertSubText'); // Новий елемент

/**
 * Показати модальне вікно (CustomAlert).
 * * @param {string} message - Текст повідомлення (HTML дозволено).
 * @param {'success'|'error'|'warning'} [type='success'] - Тип вікна.
 * @param {string|null} [title=null] - Заголовок. Якщо null - береться document.title.
 * @param {Array<object>} [buttons=[]] - Масив кнопок.
 * @param {string|null} [subMessage=null] -Текст під основним блоком (напр. "Зберегти?").
 */
function showAlert(message, type = 'success', title = null, buttons = [], subMessage = null) {
    if (!alertOverlay) {
        console.error("CustomAlert HTML не знайдено!");
        alert(message); // Fallback
        return;
    }

    // 1. Основний Текст
    alertText.innerHTML = message;

    // 2. Додатковий текст (Питання знизу) - НОВЕ
    if (subMessage) {
        alertSubText.innerText = subMessage;
        alertSubText.style.display = 'block';
    } else {
        alertSubText.innerText = '';
        alertSubText.style.display = 'none';
    }

    // 3. Заголовок
    alertTitle.innerText = title ? title : document.title;

    // 4. Тип та Стилі
    alertHeader.classList.remove('type-success', 'type-error', 'type-warning');
    const safeType = ['success', 'error', 'warning'].includes(type) ? type : 'success';
    alertHeader.classList.add(`type-${safeType}`);

    // 5. Іконка (з підтримкою Offline)
    const iconSrc = getIconSrc(safeType);
    if (iconSrc) {
        alertIcon.innerHTML = `<img src="${iconSrc}" alt="${safeType}" style="width: 100%; height: 100%;">`;
    } else {
        alertIcon.innerHTML = '';
    }

    // 6. Кнопки
    alertButtons.innerHTML = ''; 
    
    if (Array.isArray(buttons) && buttons.length > 0) {
        buttons.forEach(btnConfig => {
            const btn = document.createElement('button');
            btn.innerText = btnConfig.text || 'OK';
            btn.className = btnConfig.className || 'btn-alert-ok'; 
            
            btn.onclick = function() {
                // Якщо у кнопки є своя дія, виконуємо її. 
                // Якщо дія повертає false, вікно НЕ закривається.
                if (typeof btnConfig.onClick === 'function') {
                    const shouldClose = btnConfig.onClick(); 
                    if (shouldClose !== false) closeAlert();
                } else {
                    closeAlert();
                }
            };
            alertButtons.appendChild(btn);
        });
    } else {
        // Стандартна кнопка OK
        const okBtn = document.createElement('button');
        okBtn.innerText = 'OK';
        okBtn.className = 'btn-alert-ok';
        okBtn.onclick = closeAlert;
        alertButtons.appendChild(okBtn);
    }

    // 7. Показ та Фокус
    alertOverlay.style.display = 'flex';
    
    setTimeout(() => {
        // Фокус на останню кнопку (зазвичай це "Скасувати" або "ОК")
        const btns = alertButtons.querySelectorAll('button');
        if(btns.length > 0) btns[btns.length - 1].focus();
    }, 100);
}

/**
 * Закрити вікно та очистити дані
 */
function closeAlert() {
    if (!alertOverlay) return;

    alertOverlay.style.display = 'none';  
    alertText.innerHTML = '';
    
    // Очищаємо нижній текст
    if (alertSubText) {
        alertSubText.innerText = '';
        alertSubText.style.display = 'none'; 
    }
}

// Закриття по кліку на фон та ESC
document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', (e) => {
        if (e.target.id === 'customAlertOverlay') closeAlert();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && alertOverlay && alertOverlay.style.display === 'flex') {
            closeAlert();
        }
    });
});