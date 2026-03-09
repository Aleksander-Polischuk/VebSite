document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('recoveryForm');
    const submitBtn = document.getElementById('submitBtn');
    
    const loginBox = document.querySelector('.login-box');
    const errMsgDiv = document.getElementById('ErrMsgServer');
    const successMsgDiv = document.getElementById('SuccessMsg');

    const passInput = document.getElementById('password');
    const confirmInput = document.getElementById('password_confirm');

    // Контейнер та елементи вимог
    const reqContainer = document.querySelector('.password-requirements');
    const reqLength = document.getElementById('req-length');
    const reqNumber = document.getElementById('req-number');
    const reqMatch = document.getElementById('req-match');

    // Оченята (залишаємо без змін)
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.previousElementSibling;
            if (input.classList.contains('masked-password')) {
                input.classList.remove('masked-password');
                input.classList.add('unmasked-password');
                this.classList.add('active'); 
            } else {
                input.classList.remove('unmasked-password');
                input.classList.add('masked-password');
                this.classList.remove('active'); 
            }
        });
    });

    function showError(msg) {
        errMsgDiv.innerText = msg;
        errMsgDiv.style.display = 'block';
    }

    // --- ФУНКЦІЯ ДИНАМІЧНОЇ ВАЛІДАЦІЇ ---
    function validatePassword() {
        if (!reqContainer) return false;

        const p1 = passInput.value;
        const p2 = confirmInput.value;

        // 1. Мінімум 6 символів
        const isLengthOk = p1.length >= 6;
        reqLength.style.display = isLengthOk ? 'none' : 'flex';

        // 2. Мінімум одна цифра
        const isNumberOk = /\d/.test(p1);
        reqNumber.style.display = isNumberOk ? 'none' : 'flex';

        // 3. Паролі співпадають
        const isMatchOk = (p1 === p2 && p1 !== '');
        reqMatch.style.display = isMatchOk ? 'none' : 'flex';

        // Перевіряємо, чи залишився хоча б один видимий пункт
        const anyVisible = !isLengthOk || !isNumberOk || !isMatchOk;

        // Ховаємо весь блок, якщо всі умови виконані
        reqContainer.style.display = anyVisible ? 'block' : 'none';

        return !anyVisible;
    }

    passInput.addEventListener('input', validatePassword);
    confirmInput.addEventListener('input', validatePassword);

    // Відправка форми
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        errMsgDiv.style.display = 'none';

        if (!validatePassword()) {
            showError('Будь ласка, виконайте всі вимоги до пароля');
            return;
        }

        const originalText = submitBtn.innerText;
        submitBtn.disabled = true;
        submitBtn.innerText = 'Збереження...';

        const formData = new FormData(form);

        fetch('/api/save_new_password.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loginBox.style.display = 'none';
                successMsgDiv.style.display = 'block';
                reqContainer.style.display = 'none'; // Про всяк випадок
            } else {
                showError(data.message || 'Помилка зміни пароля');
            }
        })
        .catch(err => {
            console.error(err);
            showError('Помилка з\'єднання');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
        });
    });
});