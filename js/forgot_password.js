document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('forgotForm');
    const emailInput = document.getElementById('email');
    const submitBtn = document.getElementById('submitBtn');
    
    // Блоки
    const loginBox = document.querySelector('.login-box');
    const errMsgDiv = document.getElementById('ErrMsgServer');
    const successMsgDiv = document.getElementById('SuccessMsg');

    // Функція показу помилки
    window.showError = function(msg) {
        errMsgDiv.innerHTML = `<p style="color: #d32f2f; margin:0; text-align: center;">${msg}</p>`;
        errMsgDiv.style.display = 'block';
        emailInput.classList.add('error');
    };

    // Функція приховання помилок
    window.hideMessages = function() {
        errMsgDiv.style.display = 'none';
        emailInput.classList.remove('error');
    };

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const email = emailInput.value.trim();
        
        if (!email) {
            showError('Введіть E-mail');
            return;
        }

        hideMessages();
        const originalText = submitBtn.innerText;
        submitBtn.disabled = true;
        submitBtn.innerText = 'Відправка...';

        const formData = new FormData();
        formData.append('email', email);

        fetch('/api/forgot_password_check.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json()) // ТУТ БУЛА ПРОБЛЕМА: Тепер просто res.json()
        .then(data => {
            if (data.success) {
                // === УСПІХ ===
                // 1. Ховаємо верхній блок (форму)
                loginBox.style.display = 'none'; 
                
                // 2. Ховаємо блок помилок (про всяк випадок)
                errMsgDiv.style.display = 'none';

                // 3. Показуємо тільки повідомлення про успіх
                successMsgDiv.style.display = 'block';
            } else {
                showError(data.message || 'Помилка перевірки');
            }
        })
        .catch(err => {
            console.error(err);
            showError('Помилка з\'єднання із сервером');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
        });
    });
});