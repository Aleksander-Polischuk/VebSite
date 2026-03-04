<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "config.php";
$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

$userId = $_SESSION['id_users'] ?? 0;

if (!$userId) {
    echo "<div style='padding:20px; color:red'>Помилка: Користувач не авторизований.</div>";
    exit;
}

// Отримуємо актуальні дані користувача
$sql = "SELECT PHONE, EMAIL, IS_CONFIRMED FROM USERS WHERE ID = ?";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$userData = mysqli_stmt_get_result($stmt)->fetch_assoc();
?>

<link href="/css/personal_acc.css" rel="stylesheet" type="text/css"/>

<div class="profile-page-container">
    <div class="profile-grid">
        <div class="profile-section">
            <h4 class="section-title">Налаштування безпеки</h4>

            <form id="formEmail" class="profile-form" onsubmit="updateProfileData(event, 'email')" novalidate>
                <label class="field-label">Змінити електронну пошту</label>
                <div class="input-with-btn">
                    <input type="email" id="new_email_input" name="new_email" placeholder="new-email@example.com" autocomplete="off">
                    <button type="submit" class="btn-update">Оновити</button>
                </div>
                <div class="error-message" id="error-email"></div>
            </form>

            <div class="spacer-line"></div>

            <form id="formPassword" class="profile-form" onsubmit="updateProfileData(event, 'password')">
                <label class="field-label">Зміна паролю доступу</label>
                <div class="input-stack">
                    <div class="password-wrapper">
                        <input type="text" id="new_sec_key" name="new_sec_key" placeholder="Новий пароль" class="masked-password" autocomplete="off">
                        <img src="/img/no-view.svg" class="toggle-password" alt="Показати" onclick="togglePasswordVisibility('new_sec_key', this)">
                        <div class="error-message" id="error-new_sec_key"></div>
                    </div>

                    <div class="password-wrapper">
                        <input type="text" id="confirm_sec_key" name="confirm_sec_key" placeholder="Підтвердіть новий пароль" class="masked-password" autocomplete="off">
                        <img src="/img/no-view.svg" class="toggle-password" alt="Показати" onclick="togglePasswordVisibility('confirm_sec_key', this)">
                        <div class="error-message" id="error-confirm_sec_key"></div>
                    </div>
                </div>
                <button type="submit" class="btn-submit-blue">Зберегти новий пароль</button>
            </form>
        </div>
        
        <div class="profile-section">
            <h4 class="section-title">Особисті дані</h4>
            
            <div class="info-group">
                <label>НОМЕР ТЕЛЕФОНУ</label>
                <div class="static-field">
                    <?php echo htmlspecialchars($userData['PHONE']); ?>
                </div>
            </div>

            <div class="info-group">
                <label>ПОТОЧНА ПОШТА</label>
                <div class="static-field">
                    <?php echo htmlspecialchars($userData['EMAIL'] ?: 'не вказана'); ?>
                </div>
            </div>
        </div>

    </div>
</div>