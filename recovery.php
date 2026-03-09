<?php
  // Отримуємо дані з URL
  $token = $_GET['token'] ?? '';
  $email = $_GET['email'] ?? '';

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
  $list_css = ['/css/login.css', '/css/forgot_password.css', '/css/recovery.css']; 
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
            <p class="recovery-subtitle">
                Придумайте новий пароль для входу.
            </p>

            <form method="post" id="recoveryForm">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <label class="field">
                    <span>Новий пароль</span>
                    <div class="password-wrap">
                        <input type="text" name="password" id="password" class="masked-password" autocomplete="off" required>
                        <button type="button" class="toggle-password"></button>
                    </div>
                </label>

                <label class="field">
                    <span>Підтвердження</span>
                    <div class="password-wrap">
                        <input type="text" name="password_confirm" id="password_confirm" class="masked-password" autocomplete="off" required>
                        <button type="button" class="toggle-password"></button>
                    </div>
                </label>
            
                <div class="password-requirements">
                    <p class="req-title">Вимоги до пароля:</p>
                    <ul class="req-list">
                        <li id="req-length">Мінімум 6 символів</li>
                        <li id="req-number">Містить хоча б одну цифру</li>
                        <li id="req-match">Паролі співпадають</li>
                    </ul>
                </div>
                <button type="submit" id="submitBtn" class="btn-login btn-recovery-submit">Зберегти пароль</button>
            </form>

        <?php else: ?>
            <h1 class="recovery-error-title">Помилка посилання</h1>
            <p class="recovery-error-desc">
                Це посилання для відновлення пароля недійсне або застаріло.
            </p>
            <div class="recovery-retry-wrap">
                <a href="/forgotpassword" class="btn-login btn-recovery-retry">Спробувати знову</a>
            </div>
        <?php endif; ?>
     </main>

     <div id="SuccessMsg" class="recovery-success-box" style="display:none;">
        <div class="success-heading">Пароль змінено!</div>
        <p class="success-text">Тепер ви можете увійти з новим паролем.</p>
        <a href="/login" class="btn-login btn-success-login">Увійти</a>
     </div>

     <div id="ErrMsgServer" class="recovery-server-error" style="display:none;"></div>

</div>

<?php if ($isValid): ?>
<script src="/js/recovery.js"></script>
<?php endif; ?>
</body>
</html>