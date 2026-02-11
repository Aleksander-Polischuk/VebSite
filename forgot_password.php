<?php
  $title = 'Відновлення пароля';
  $list_css = ['/css/login.css', '/css/forgot_password.css']; 
  
  session_start();
  include "page_head.php";
?>
  <div id="wrapper"> 
     <header id="header">
        <img id="logoIco" src="/img/logo.png" alt="Логотип"> 
        <div class="LogoLine"></div>
     </header>
     
     <main class="login-box">
        <h1>Забули пароль?</h1>
        <p style="margin-bottom: 20px; color: #555; font-size: 15px; text-align: center;">
            Введіть вашу пошту, і ми надішлемо інструкції для скидання пароля.
        </p>

        <form method="post" id="forgotForm">
            <label class="field">
                <span>E-mail</span>
                <input type="email" name="email" id="email" placeholder="name@example.com" required>
                <p class="error-text" id="emailError" style="display:none; color:red; font-size:12px; margin-top:5px;"></p>
            </label>
           
            <button type="submit" id="submitBtn" class="btn-login">Надіслати</button>
            
            <div style="margin-top: 20px; text-align: center;">
                <a href="/login" class="forgot">Повернутися назад</a>
            </div>
        </form>
     </main> <div id="ErrMsgServer" style="display:none;">
        <p style="color: #d32f2f; margin:0; text-align: center;">Користувача не знайдено.</p>
     </div>

     <div id="SuccessMsg" style="display:none;">
        <div style="color: #27ae60; font-size: 16px; font-weight: bold; margin-bottom: 10px;">Лист надіслано!</div>
        <p style="color: #666;">Перевірте вашу пошту.</p>
        <a href="/login" class="btn-login" style="display:block; text-decoration:none; line-height:52px; margin-top:10px;">ОК</a>
     </div>

  </div>

<script src="/js/forgot_password.js"></script>
</body>
</html>