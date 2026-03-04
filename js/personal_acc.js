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

// Функція показу помилки з текстом
function applyErrorStyle(inputElement, message) {
    if (!inputElement) return;
    
    // Знаходимо блок для тексту помилки
    const errorBlock = inputElement.parentElement.querySelector('.error-message') || 
                       inputElement.closest('form').querySelector('.error-message');

    inputElement.classList.add('input-error', 'shake-error');
    
    if (errorBlock) {
        errorBlock.innerText = message;
        errorBlock.style.display = 'block';
    }

    // Очищення при введенні
    const clearError = () => {
        inputElement.classList.remove('input-error', 'shake-error');
        if (errorBlock) errorBlock.style.display = 'none';
        inputElement.removeEventListener('input', clearError);
    };
    
    inputElement.addEventListener('input', clearError);
}

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

        // Якщо все ок — викликаємо підтвердження
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
        const pass2 = form.confirm_sec_key;

        if (pass1.value.length < 6) {
            return applyErrorStyle(pass1, "Пароль занадто короткий (мінімум 6 символів)");
        }
        
        if (pass1.value !== pass2.value) {
            return applyErrorStyle(pass2, "Паролі не збігаються");
        }

        showAlert("Зберегти новий пароль?", 'warning', 'Підтвердження', [
            { text: 'Зберегти', className: 'btn-alert-ok', onClick: () => {
                formData.append('action', 'update_password');
                formData.append('new_password', pass1.value);
                formData.append('confirm_password', pass2.value);
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
                if (type === 'password' && form) form.reset();
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