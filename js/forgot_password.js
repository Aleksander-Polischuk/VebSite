document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('forgotForm');
    const emailInput = document.getElementById('email');
    const submitBtn = document.getElementById('submitBtn');
    
    const loginBox = document.querySelector('.login-box');
    const errMsgDiv = document.getElementById('ErrMsgServer');
    const successMsgDiv = document.getElementById('SuccessMsg');

    /* ---------- ВАЛІДАЦІЯ ТА ПОМИЛКИ ---------- */

    function setError(input, errorId, message) {
        input.classList.add('error');
        const errorEl = document.getElementById(errorId);
        if (errorEl) {
            errorEl.innerText = message;
            errorEl.style.display = 'block';
        }
    }

    function clearError(input, errorId) {
        input.classList.remove('error');
        const errorEl = document.getElementById(errorId);
        if (errorEl) {
            errorEl.innerText = '';
            errorEl.style.display = 'none';
        }
        errMsgDiv.style.display = 'none'; // Ховаємо також загальну помилку сервера
    }

    function validateEmailFormat() {
        const value = emailInput.value.trim();
        if (!value) {
            setError(emailInput, 'emailError', 'Введіть E-mail');
            return false;
        }
        // Регулярний вираз для перевірки формату пошти
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            setError(emailInput, 'emailError', 'Невірний формат пошти');
            return false;
        }
        clearError(emailInput, 'emailError');
        return true;
    }

    // Очищуємо помилки при введенні
    emailInput.addEventListener('input', () => {
        clearError(emailInput, 'emailError');
    });

    /* ---------- ВІДПРАВКА ФОРМИ ---------- */

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Перевіряємо формат перед відправкою
        if (!validateEmailFormat()) {
            return;
        }

        const originalText = submitBtn.innerText;
        submitBtn.disabled = true;
        submitBtn.innerText = 'Відправка...';

        const formData = new FormData();
        formData.append('email', emailInput.value.trim());

        fetch('/api/forgot_password_check.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // === УСПІХ ===
                loginBox.style.display = 'none'; 
                errMsgDiv.style.display = 'none';
                successMsgDiv.style.display = 'block';
            } else {
                // Якщо користувача не знайдено — показуємо загальну помилку сервера
                setError(emailInput, 'emailError', data.message || 'Користувача не знайдено');
                errMsgDiv.style.display = 'block';
            }
        })
        .catch(err => {
            console.error(err);
            setError(emailInput, 'emailError', 'Помилка з\'єднання із сервером');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
        });
    });
});