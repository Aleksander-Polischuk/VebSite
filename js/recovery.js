document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('recoveryForm');
    const submitBtn = document.getElementById('submitBtn');
    
    // Блоки
    const loginBox = document.querySelector('.login-box');
    const errMsgDiv = document.getElementById('ErrMsgServer');
    const successMsgDiv = document.getElementById('SuccessMsg');

    // Оченята для паролів
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.previousElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                this.classList.add('active');
            } else {
                input.type = 'password';
                this.classList.remove('active');
            }
        });
    });

    function showError(msg) {
        errMsgDiv.innerText = msg;
        errMsgDiv.style.display = 'block';
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        errMsgDiv.style.display = 'none';

        const p1 = form.password.value;
        const p2 = form.password_confirm.value;

        if (p1.length < 6) {
            showError('Пароль має бути не менше 6 символів');
            return;
        }

        if (p1 !== p2) {
            showError('Паролі не співпадають');
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
                // Ховаємо форму, показуємо успіх
                loginBox.style.display = 'none';
                successMsgDiv.style.display = 'block';
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