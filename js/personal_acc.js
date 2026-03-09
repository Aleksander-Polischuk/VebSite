/* =========================================
   ПЕРЕМИКАЧ ВИДИМОСТІ ПАРОЛЯ
   ========================================= */
function togglePasswordVisibility(inputId, iconElement) {
    const input = document.getElementById(inputId);
    if (input.classList.contains('masked-password')) {
        input.classList.remove('masked-password');
        input.classList.add('unmasked-password');
        iconElement.src = '/img/view.svg'; 
    } else {
        input.classList.remove('unmasked-password');
        input.classList.add('masked-password');
        iconElement.src = '/img/no-view.svg'; 
    }
}

function applyErrorStyle(inputElement, message) {
    if (!inputElement) return;
    
    const errorBlock = inputElement.parentElement.querySelector('.error-message') || 
                       inputElement.closest('form').querySelector('.error-message');

    inputElement.classList.add('input-error', 'shake-error');
    
    if (errorBlock) {
        errorBlock.innerText = message;
        errorBlock.style.display = 'block';
    }

    const clearError = () => {
        inputElement.classList.remove('input-error', 'shake-error');
        if (errorBlock) errorBlock.style.display = 'none';
        inputElement.removeEventListener('input', clearError);
    };
    
    inputElement.addEventListener('input', clearError);
}

/* =========================================
   ДИНАМІЧНА ВАЛІДАЦІЯ ПАРОЛЯ
   ========================================= */
function validatePersonalAccPassword() {
    const passInput = document.getElementById('new_sec_key');
    const confirmInput = document.getElementById('confirm_sec_key');
    const container = document.querySelector('.password-requirements'); // Весь блок з заголовком
    
    if (!passInput || !confirmInput || !container) return false;

    const p1 = passInput.value;
    const p2 = confirmInput.value;
    
    const reqLength = document.getElementById('req-length');
    const reqNumber = document.getElementById('req-number');
    const reqMatch = document.getElementById('req-match');

    if (!reqLength || !reqNumber || !reqMatch) return false;

    // 1. Перевірка довжини
    const isLengthOk = p1.length >= 6;
    reqLength.style.display = isLengthOk ? 'none' : 'flex';

    // 2. Перевірка на наявність цифри
    const isNumberOk = /\d/.test(p1);
    reqNumber.style.display = isNumberOk ? 'none' : 'flex';

    // 3. Перевірка на співпадіння паролів
    // Показуємо умову, якщо паролі не однакові АБО якщо вони обидва порожні
    const isMatchOk = (p1 === p2 && p1 !== '');
    reqMatch.style.display = isMatchOk ? 'none' : 'flex';

    // Перевіряємо, чи залишився хоча б один видимий пункт у списку
    const anyVisible = !isLengthOk || !isNumberOk || !isMatchOk;

    // Якщо всі пункти приховані (умови виконані) — ховаємо весь блок з заголовком
    container.style.display = anyVisible ? 'block' : 'none';

    return !anyVisible;
}

// Слухаємо події введення через делегування (на весь документ)
document.addEventListener('input', function(e) {
    if (e.target.id === 'new_sec_key' || e.target.id === 'confirm_sec_key') {
        validatePersonalAccPassword();
    }
});

/* =========================================
   ГОЛОВНА ФУНКЦІЯ ОНОВЛЕННЯ ПРОФІЛЮ
   ========================================= */
function updateProfileData(event, type) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData();

    if (type === 'email') {
        const emailInput = form.new_email;
        const emailValue = emailInput.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (emailValue === "") {
            return applyErrorStyle(emailInput, "Поле не може бути порожнім");
        }
        if (!emailRegex.test(emailValue)) {
            return applyErrorStyle(emailInput, "Введіть коректний формат (напр. user@gmail.com)");
        }

        showAlert(`Змінити пошту на <b>${emailValue}</b>?`, 'warning', 'Підтвердження', [
            { text: 'Так', className: 'btn-alert-ok', onClick: () => {
                formData.append('action', 'update_email');
                formData.append('email', emailValue);
                sendProfileUpdateToServer(formData, 'email');
                return true;
            }},
            { text: 'Скасувати', className: 'btn-alert-cancel' }
        ]);
    } 
    else if (type === 'password') {
        const pass1 = form.new_sec_key;

        // Використовуємо нашу нову функцію валідації перед збереженням
        if (!validatePersonalAccPassword()) {
            return applyErrorStyle(pass1, "Будь ласка, виконайте всі вимоги до пароля (усі пункти мають стати зеленими)");
        }

        showAlert("Зберегти новий пароль?", 'warning', 'Підтвердження', [
            { text: 'Зберегти', className: 'btn-alert-ok', onClick: () => {
                formData.append('action', 'update_password');
                formData.append('new_password', pass1.value);
                formData.append('confirm_password', form.confirm_sec_key.value);
                sendProfileUpdateToServer(formData, 'password', form);
                return true;
            }},
            { text: 'Скасувати', className: 'btn-alert-cancel' }
        ]);
    }
}

/* =========================================
   ВІДПРАВКА НА СЕРВЕР
   ========================================= */
function sendProfileUpdateToServer(formData, type, form = null) {
    fetch('/api/update_user_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        try {
            const res = JSON.parse(text);
            if (res.status === 'success') {
                showAlert(res.message, 'success', 'Успішно оновлено');
                if (type === 'password' && form) {
                    form.reset();
                    validatePersonalAccPassword(); // Скидаємо зелені галочки після очищення форми
                }
                if (type === 'email') {
                    if (window.refreshActiveContent) window.refreshActiveContent();
                    else setTimeout(() => window.location.reload(), 1500);
                }
            } else {
                showAlert("Помилка: " + (res.message || "Невідома помилка"), 'error', 'Відмова сервера');
            }
        } catch (e) {
            console.error("Крива відповідь від сервера:", text); 
            showAlert("Сервер повернув некоректні дані (дивіться консоль)", 'error', 'Помилка парсингу');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        showAlert("Не вдалося оновити дані. Перевірте мережу.", 'error', 'Помилка мережі');
    });
}