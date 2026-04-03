document.addEventListener('DOMContentLoaded', () => {

    const form = document.getElementById('registrationForm');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const password2 = document.getElementById('password2');
    let emailCheckTimer;
    let lastCheckedEmail = '';

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

    /* =========================================
       ВАЛІДАЦІЯ ПАРОЛЯ
       ========================================= */
    function validateRegistrationPassword() {
        const p1 = password.value;
        const p2 = password2.value;
        const container = document.querySelector('.password-requirements');

        const reqLength = document.getElementById('req-length');
        const reqNumber = document.getElementById('req-number');
        const reqMatch = document.getElementById('req-match');

        if (!reqLength || !reqNumber || !reqMatch || !container) return false;

        // 1. Мінімум 6 символів
        const isLengthOk = p1.length >= 6;
        reqLength.style.display = isLengthOk ? 'none' : 'flex';

        // 2. Мінімум одна цифра
        const isNumberOk = /\d/.test(p1);
        reqNumber.style.display = isNumberOk ? 'none' : 'flex';

        // 3. Паролі співпадають (і не порожні)
        const isMatchOk = (p1 === p2 && p1 !== '');
        reqMatch.style.display = isMatchOk ? 'none' : 'flex';

        // Перевіряємо, чи залишився хоча б один видимий пункт
        const anyVisible = !isLengthOk || !isNumberOk || !isMatchOk;

        // Ховаємо весь блок, якщо всі умови виконані
        container.style.display = anyVisible ? 'block' : 'none';

        // Якщо все ок, чистимо старі текстові помилки під полями
        if (!anyVisible) {
            clearError(password, 'passwordError');
            clearError(password2, 'password2Error');
        }

        return !anyVisible;
    }

    document.addEventListener('input', function(e) {
        if (e.target.id === 'password' || e.target.id === 'password2') {
            validateRegistrationPassword();
        }
    });


    /* =========================================
       ВАЛІДАЦІЯ ПОШТИ
       ========================================= */
    function validateEmailFormat() {
        const value = email.value.trim();

        if (!value) {
            setError(email, 'emailError', 'Обовʼязкове до заповнення');
            return false;
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            setError(email, 'emailError', 'Невірний формат');
            return false;
        }

        clearError(email, 'emailError');
        return true;
    }

    function debounceCheckEmail() {
        clearTimeout(emailCheckTimer);

        const value = email.value.trim();
        if (value === lastCheckedEmail) return;

        emailCheckTimer = setTimeout(() => {
            checkEmailInDB(value);
        }, 500);
    }

    async function checkEmailInDB(emailValue) {
        try {
            const response = await fetch('/api/check_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email: emailValue })
            });

            if (!response.ok) {
                throw new Error('Network error');
            }

            const data = await response.json();
            lastCheckedEmail = emailValue;

            if (data.exists) {
                setError(email, 'emailError', 'E-mail вже зареєстрований');
            } else {
                clearError(email, 'emailError');
            }

        } catch (e) {
            console.error(e);
        }
    }

    email.addEventListener('input', () => {
        if (validateEmailFormat()) {
            debounceCheckEmail();
        } else {
            clearTimeout(emailCheckTimer);
        }
    });


    /* =========================================
       ВІДПРАВКА ФОРМИ
       ========================================= */
    form.addEventListener('submit', (e) => {
        e.preventDefault();  
        
        const isEmailFormatOk = validateEmailFormat();
        const isPassOk = validateRegistrationPassword();
        
        const isEmailUnique = !email.classList.contains('error'); 

        if (!isPassOk) {
            setError(password, 'passwordError', 'Будь ласка, виконайте всі вимоги до пароля');
        }

        // Відправляємо дані тільки якщо все ok та пошта валідна
        if (isEmailFormatOk && isEmailUnique && isPassOk) { 
            const formData = new FormData(form);

            fetch('/api/registration_ent_check.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect ?? '/login';
                } else {
                    if (data.code === 2) { 
                        const errMsg = document.getElementById('ErrMsgEmail');
                        errMsg.style.display = 'block';

                        setTimeout(() => {
                            errMsg.style.display = 'none';
                        }, 10000); // 10 секунд
                    }   
                }
            })
            .catch(error => {
                console.error(error);
                
                const errMsg = document.getElementById('ErrMsgServer');
                errMsg.style.display = 'block';

                setTimeout(() => {
                        errMsg.style.display = 'none';
                }, 10000); // 10 секунд
            });
        }
    });

    /* =========================================
       ДОПОМІЖНІ ФУНКЦІЇ
       ========================================= */
    function setError(input, errorId, message) {
        input.classList.add('error');
        input.closest('.field').classList.add('error');
        const errorEl = document.getElementById(errorId);
        if (errorEl) errorEl.innerText = message;
    }

    function clearError(input, errorId) {
        input.classList.remove('error');
        input.closest('.field').classList.remove('error');
        const errorEl = document.getElementById(errorId);
        if (errorEl) errorEl.innerText = '';
    }

});