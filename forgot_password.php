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
        <p class="forgot-subtitle">
            Введіть вашу пошту, і ми надішлемо інструкції для скидання пароля.
        </p>

        <form method="post" id="forgotForm" novalidate>
            <label class="field">
                <span>E-mail</span>
                <input type="email" name="email" id="email" placeholder="name@example.com" required>
                <p class="error-text" id="emailError"></p>
            </label>
           
            <button type="submit" id="submitBtn" class="btn-login">Надіслати</button>
            
            <div class="back-link-wrap">
                <a href="/login" class="forgot">Повернутися назад</a>
            </div>
        </form>
    </main> 

    <div id="ErrMsgServer">
        <p class="error-server-text">Користувача не знайдено.</p>
    </div>

    <div id="SuccessMsg">
        <div class="success-title">Лист надіслано!</div>
        <p class="success-text">Перевірте вашу пошту.</p>
        <a href="/login" class="btn-login btn-success-ok">ОК</a>
    </div>
</div>

<script src="/js/forgot_password.js?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/forgot_password.js'); ?>"></script>
</body>
</html>