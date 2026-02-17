<?php
    $token = $_GET['token'] ?? '';
    $email = $_GET['email'] ?? '';

    include ('config.php');
    $link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
    mysqli_set_charset($link, 'utf8');

    $message = '';
    $success = false;

    if ($token && $email) {
        // Перевіряємо токен
        $sql = "SELECT ID, IS_ENT FROM USERS WHERE EMAIL = ? AND RECOVERY_TOKEN = ?";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $email, $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $userId = $row['ID'];
            $isEnt  = $row['IS_ENT'];

            // АКТИВУЄМО:
            // 1. Очищаємо токен (recovery_token = NULL)
            // 2. Ставимо IS_CONFIRMED = 1
            $updateSql = "UPDATE USERS SET RECOVERY_TOKEN = NULL, IS_CONFIRMED = 1 WHERE ID = ?";
            $updStmt = mysqli_prepare($link, $updateSql);
            mysqli_stmt_bind_param($updStmt, "i", $userId);
            
            if (mysqli_stmt_execute($updStmt)) {
                $success = true;
                
                // Можна одразу залогінити користувача
                session_start();
                $_SESSION['id_users'] = $userId;
                $_SESSION['is_ent']   = $isEnt;
            } else {
                $message = "Помилка бази даних при активації.";
            }
        } else {
            $message = "Посилання недійсне або вже використане.";
        }
    } else {
        $message = "Невірні параметри посилання.";
    }

    // Підключаємо шапку і стилі
    $title = 'Активація акаунту';
    $list_css = ['/css/login.css', '/css/forgot_password.css'];
    include "page_head.php";
?>

<div id="wrapper"> 
     <header id="header">
        <img id="logoIco" src="/img/logo.png" alt="Логотип"> 
        <div class="LogoLine"></div>
     </header>
     
     <main class="login-box" style="text-align: center; margin-top: 50px; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
        <?php if ($success): ?>
            <div style="color: #27ae60; font-size: 60px; margin-bottom: 20px;">✓</div>
            <h1>Акаунт активовано!</h1>
            <p style="color: #555;">Дякуємо, ваша пошта підтверджена.</p>
            
            <a href="/cabinet_ent" class="btn-login" style="display:block; text-decoration:none; line-height:52px; margin-top:20px; color: #fff !important;">Перейти в кабінет</a>

        <?php else: ?>
            <h1 style="color: #d32f2f;">Помилка</h1>
            <p style="color: #555;"><?php echo htmlspecialchars($message); ?></p>
            <a href="/login" class="forgot" style="display:block; margin-top:20px;">Перейти на вхід</a>
        <?php endif; ?>
     </main>
</div>
</body>
</html>