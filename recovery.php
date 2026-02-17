<?php
  // Отримуємо дані з URL
  $token = $_GET['token'] ?? '';
  $email = $_GET['email'] ?? '';

  // Підключаємо БД для перевірки токена
  include ('config.php');
  $link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
  mysqli_set_charset($link, 'utf8');

  // Перевіряємо, чи існує такий токен у цього користувача
  $isValid = false;
  if ($token && $email) {
      $sql = "SELECT ID FROM USERS WHERE EMAIL = ? AND RECOVERY_TOKEN = ?";
      $stmt = mysqli_prepare($link, $sql);
      mysqli_stmt_bind_param($stmt, "ss", $email, $token);
      mysqli_stmt_execute($stmt);
      mysqli_stmt_store_result($stmt);
      if (mysqli_stmt_num_rows($stmt) > 0) {
          $isValid = true;
      }
  }

  $title = 'Новий пароль';
  $list_css = ['/css/login.css', '/css/forgot_password.css']; 
  include "page_head.php";
?>

<div id="wrapper"> 
     <header id="header">
        <img id="logoIco" src="/img/logo.png" alt="Логотип"> 
        <div class="LogoLine"></div>
     </header>
     
     <main class="login-box">
        <?php if ($isValid): ?>
            <h1>Новий пароль</h1>
            <p style="margin-bottom: 20px; color: #555; font-size: 15px; text-align: center;">
                Придумайте новий пароль для входу.
            </p>

            <form method="post" id="recoveryForm">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <label class="field">
                    <span>Новий пароль</span>
                    <div class="password-wrap">
                        <input type="password" name="password" id="password"required>
                        <button type="button" class="toggle-password"></button>
                    </div>
                </label>

                <label class="field">
                    <span>Підтвердження</span>
                    <div class="password-wrap">
                        <input type="password" name="password_confirm" id="password_confirm" required>
                        <button type="button" class="toggle-password"></button>
                    </div>
                </label>
            
                <button type="submit" id="submitBtn" class="btn-login" style="margin-top: 10px;">Зберегти пароль</button>
            </form>

        <?php else: ?>
            <h1 style="color: #d32f2f;">Помилка посилання</h1>
            <p style="text-align: center; color: #555;">
                Це посилання для відновлення пароля недійсне або застаріло.
            </p>
            <div style="text-align: center; margin-top: 20px;">
                <a href="/forgotpassword" class="btn-login" style="display:inline-block; width:auto; padding: 0 30px; text-decoration:none; line-height: 52px; color: #fff !important;">Спробувати знову</a>
            </div>
        <?php endif; ?>
     </main>

     <div id="SuccessMsg" style="display:none; text-align:center; width: 420px; margin: 50px auto 0; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
        <div style="color: #27ae60; font-size: 16px; font-weight: bold; margin-bottom: 10px;">Пароль змінено!</div>
        <p style="color: #666;">Тепер ви можете увійти з новим паролем.</p>
        <a href="/login" class="btn-login" style="display:block; text-decoration:none; line-height:52px; margin-top:10px; color: #fff !important;">Увійти</a>
     </div>

     <div id="ErrMsgServer" style="display:none; width: 420px; margin: 15px auto 0; border: 1px solid #ff0000; border-radius: 5px; padding: 10px; background: #fff0f0; text-align:center; color: #d32f2f;"></div>

</div>

<?php if ($isValid): ?>
<script src="/js/recovery.js"></script>
<?php endif; ?>
</body>
</html>