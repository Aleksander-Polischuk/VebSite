$(function () {
   
    // Показати / сховати пароль
    $('.toggle-password').on('click', function () {
        const input = $('#password');
        const isHidden = input.attr('type') === 'password';

        input.attr('type', isHidden ? 'text' : 'password');
        $('.toggle-password').toggleClass('active');
        
        const inputcp = $('#password2');
        const isHiddencp = inputcp.attr('type') === 'password';

        inputcp.attr('type', isHiddencp ? 'text' : 'password');
    });
});

document.addEventListener('DOMContentLoaded', () => {

    const form = document.getElementById('registrationForm');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const password2 = document.getElementById('password2');

    const MIN_PASSWORD_LENGTH = 6;
    let emailCheckTimer = null;
    let lastCheckedEmail = '';

    email.addEventListener('input', () => {
    	validateEmail();
    	debounceCheckEmail();
    });

    password.addEventListener('input', validatePassword);
    password2.addEventListener('input', validatePasswordConfirm);

    form.addEventListener('submit', (e) => {
      e.preventDefault();  
        
      validateEmail();
      validatePassword();
      validatePasswordConfirm();

      if (isFormValid()) { // Якщо все ок 
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
                // Якщо все ок 
                window.location.href = data.redirect ?? '/login';
            } else {
                if (data.code === 2) { 
                	const errMsg = document.getElementById('ErrMsgEmail');
    				errMsg.style.display = 'block';

    				setTimeout(() => {
        				errMsg.style.display = 'none';
    				}, 10000); // 10 секунд = 10000 мс
                }	
            }
        })
        .catch(error => {
            console.error(error);
            
            const errMsg = document.getElementById('ErrMsgServer');
    		errMsg.style.display = 'block';

    		setTimeout(() => {
        			errMsg.style.display = 'none';
    		}, 10000); // 10 секунд = 10000 мс
        });
        
        
        
        
      }
    });

    /* ---------- EMAIL ---------- */
    function validateEmail() {
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

    	if (!validateEmail()) return;

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

  	/* ---------- PASSWORD ---------- */
  	function validatePassword() {
    	const value = password.value;

    	if (!value) {
      		setError(password, 'passwordError', 'Обовʼязкове до заповнення');
      		return;
    	}

    	if (value.length < MIN_PASSWORD_LENGTH) {
      		setError(
        		password,
        		'passwordError',
        		`Мінімум ${MIN_PASSWORD_LENGTH} символів`
      		);
      		return;
    	}

    	clearError(password, 'passwordError');

    	if (password2.value) {
      		validatePasswordConfirm();
    	}
  	}

  	function validatePasswordConfirm() {
    	if (!password2.value) {
              setError(password2, 'password2Error', 'Обовʼязкове до заповнення');
              return;
        }

        if (password.value !== password2.value) {
              setError(password2, 'password2Error', 'Паролі не співпадають');
              return;
        }
        
    	clearError(password2, 'password2Error');
  	}

  	/* ---------- HELPERS ---------- */
  	function setError(input, errorId, message) {
    	input.classList.add('error');
    	input.closest('.field').classList.add('error');
    	document.getElementById(errorId).innerText = message;
  	}

  	function clearError(input, errorId) {
    	input.classList.remove('error');
    	input.closest('.field').classList.remove('error');
    	document.getElementById(errorId).innerText = '';
  	}

  	function isFormValid() {
    	return (
      		!email.classList.contains('error') &&
      		!password.classList.contains('error') &&
      		!password2.classList.contains('error') &&
      		email.value !== '' &&
      		password.value !== '' &&
      		password2.value !== ''
    	);
  	}

});