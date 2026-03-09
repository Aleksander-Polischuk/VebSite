        <?php
  $title = 'Реєстрація в особистому кабінеті';
  $list_css = ['/css/registration_ent.css'];
  
  session_start();
  
  // Якщо сюди потрапили без id_ent_registration
  if (!isset($_SESSION['id_ent_registration'])) {
		header('Location: /');
        exit; 
	}
  
  include "page_head.php";
?>
  <div id="wrapper"> 
  	 <header id="header">
  	 	<img id="logoIco" src="/img/logo.png"> 
  	 	<div class="LogoLine"> </div>
  	 		
  	 </header>
  	 
  	 <main class="registration-box">
    	<h1>Реєстрація</h1>

	    <form method="post" id="registrationForm">
	        <label class="field">
	            <span class="fdescr">E-mail (електронна адреса)</span>
	            <input type="text" name="email" id="email" placeholder="E-mail (електронна адреса)">
	           
	            <span class="error-icon">!</span>
                <p class="error-text" id="emailError"></p>
	        </label>
	
	        <label class="field">
	            <div class="label-row">
	                <span class="fdescr">Пароль</span>
	            </div>
	
	            <div class="password-wrap">
                    <input type="text" name="password" id="password" class="masked-password" autocomplete="off" placeholder="Пароль">
	                <button type="button" class="toggle-password" aria-label="Показати пароль"></button>
	            </div>
	            
	            <span class="error-icon">!</span>
                <p class="error-text" id="passwordError"></p>
	        </label>
	       
	       <label class="field">
	            <div class="label-row">
	                <span class="fdescr">Підтвердіть пароль</span>
	            </div>
	
	            <div class="password-wrap">
                    <input type="text" name="password2" id="password2" class="masked-password" autocomplete="off" placeholder="Підтвердіть пароль">
	                <button type="button" class="toggle-password" aria-label="Показати пароль"></button>
	            </div>
	            
	            <span class="error-icon">!</span>
                <p class="error-text" id="password2Error"></p>
	        </label>
	       
            <div class="password-requirements">
                <p class="req-title">Вимоги до пароля:</p>
                <ul class="req-list">
                    <li id="req-length">Мінімум 6 символів</li>
                    <li id="req-number">Містить хоча б одну цифру</li>
                    <li id="req-match">Паролі співпадають</li>
                </ul>
            </div>
	       
	       
	        <button type="submit" class="btn-registration">Зареєструватися</button>
	    </form>
        
        <div id="ErrMsgServer">
           <p class="ErrMsgText">Сталась помилка, спробуйте пізніше</p>
        </div>
        
        <div id="ErrMsgEmail">
           <p class="ErrMsgText">Така адреса електронної пошти вже зареєстрована в системі, перевірте правильність і спробуйте ще раз
           </p>
        </div>
        
	 </main>

  </div>

<script src="/js/registration_ent.js"></script>

</body>
</html>