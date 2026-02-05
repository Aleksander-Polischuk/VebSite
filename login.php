<?php
  $title = 'Вхід в особистий кабінет';
  $list_css = ['/css/login.css'];
  
  session_start();
  session_destroy();
  
  $account_type = (isset($_GET['account_type']) ? $_GET['account_type'] : -1);
  if ($account_type != 0 and $account_type != 1) {
		$account_type = -1;
  }
  
  include "page_head.php";
?>
  <div id="wrapper"> 
  	 <header id="header">
  	 	<img id="logoIco" src="/img/logo.png"> 
  	 	<div class="register-link">Немає акаунту? <a href="/registration">Зареєструватись</a></div>
  	 	<div class="LogoLine"> </div>
  	 		
  	 </header>
  	 
  	 <main class="login-box">
    	<h1>Вхід до особистого кабінету</h1>

	    <form method="post" id="loginForm">
	        <label class="field">
	            <span>Кабінет користувача</span>
	            <select name="account_type" id="consumer_type">
	           <?php 
	             if ($account_type == -1 or $account_type == 0) {
	                print ' <option value="0">Для фізичних осіб</option>'; 
				 } 
				 if ($account_type == -1 or $account_type == 1) {  
	                print ' <option value="1">Для юридичних осіб</option>';
				 }
			   ?>	 	 
	            </select>
	        </label>
	
	        <label class="field">
	            <span>Телефон</span>
	            <input type="tel" name="phone" id="phone" placeholder="Номер телефону">
	        </label>
	
	        <label class="field">
	            <div class="label-row">
	                <span>Пароль</span>
	                <a href="/forgotpassword" class="forgot">Забули пароль?</a>
	            </div>
	
	            <div class="password-wrap">
	                <input type="password" name="password" id="password" placeholder="Пароль">
	                <button type="button" class="toggle-password" aria-label="Показати пароль"></button>
	            </div>
	        </label>
	       
	        <button type="button" id="submitBtn" class="btn-login">Увійти</button>
	    </form>
	    
	    <div id="ErrMsgLogin">
           <p class="ErrMsgText">Некоректно вказано телефон або пароль
                Будь ласка перевірте і спробуйте ще раз або 
                скористайтеся формою відновлення пароля
           </p>
        </div>
        
        <div id="ErrMsgServer">
           <p class="ErrMsgText">Сталась помилка, спробуйте пізніше</p>
        </div>
               
	    <div id="Enterpriseinfo">
		 	Не маєте облікового запису для входу до кабінету?<br>
	        <p>Якщо у вас ще відсутні логін і пароль, будь ласка, зверніться до відділу роботи з юридичними особами.
	        Фахівці допоможуть зареєструвати вас і нададуть усі необхідні дані для доступу до особистого кабінету.</p>
	    </div> 
	 </main>

  </div>

<script src="/js/login.js"></script>

</body>
</html>