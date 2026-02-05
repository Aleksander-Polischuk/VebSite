$(function () {

    // Маска телефона
    $('#phone').mask('+38 (000) 000-00-00', {
    });

    // Показати / сховати пароль
    $('.toggle-password').on('click', function () {
        const input = $('#password');
        const isHidden = input.attr('type') === 'password';

        input.attr('type', isHidden ? 'text' : 'password');
        $(this).toggleClass('active');
    });
     
    // Зміна типу споживача населення / підприємство
    $('#consumer_type').on('change', function () {

        PrepareInput();
     }); 
    
    $('#submitBtn').on('click', function (e) {
        e.preventDefault(); // відключаєм стандартну відправку форми

        const form = document.getElementById('loginForm');
        const formData = new FormData(form);

        fetch('/api/login_check.php', {
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
                // помилка авторизації
                showErrorLogin();
            }
        })
        .catch(error => {
            console.error(error);
            showErrorServer();
        });
    });
    
    function PrepareInput() {
    
        const value = $('#consumer_type').val(); // "0" або "1"

        if (value === '0') { // Фіз особа
             $('#password').attr('placeholder', 'Пароль')
			 $('#Enterpriseinfo').css("display","none");
			 $('.register-link').css("display","");	
        
        } else if (value === '1') { // Юр особа
           $('#password').attr('placeholder', 'Пароль або код активації')
           $('#Enterpriseinfo').css("display",""); 
           $('.register-link').css("display","none"); 
              
        }
    }
    
    function showErrorLogin() {
    	const errMsg = document.getElementById('ErrMsgLogin');
    	errMsg.style.display = 'block';

    	setTimeout(() => {
        	errMsg.style.display = 'none';
    	}, 10000); // 10 секунд = 10000 мс
    }
    
    function showErrorServer() {
    	const errMsg = document.getElementById('ErrMsgServer');
    	errMsg.style.display = 'block';

    	setTimeout(() => {
        	errMsg.style.display = 'none';
    	}, 10000); // 10 секунд = 10000 мс
    }
 
    /*=====================*/
    PrepareInput($('#consumer_type').val());	
});