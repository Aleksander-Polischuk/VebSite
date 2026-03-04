<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

include "../config.php"; 
$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

$userId = $_SESSION['id_users'] ?? 0;
$action = $_POST['action'] ?? '';

// Перевірка авторизації
if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'Ваша сесія закінчилася. Будь ласка, авторизуйтесь знову.']);
    mysqli_close($link);
    exit;
}

// =========================================
// 1. ОНОВЛЕННЯ EMAIL
// =========================================
if ($action === 'update_email') {
    $emailRaw = $_POST['email'] ?? $_POST['new_email'] ?? '';
    $email = filter_var(trim($emailRaw), FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Введено некоректну адресу електронної пошти.']);
        mysqli_close($link);
        exit;
    }

    $stmt = mysqli_prepare($link, "UPDATE USERS SET EMAIL = ? WHERE ID = ?");
    mysqli_stmt_bind_param($stmt, "si", $email, $userId);
    
    if (mysqli_stmt_execute($stmt)) {
        if (ob_get_length()) ob_clean();    
        echo json_encode(['status' => 'success', 'message' => 'Електронну пошту успішно змінено на ' . $email]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Помилка при оновленні бази даних.']);
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($link);
    exit;
}

// =========================================
// 2. ОНОВЛЕННЯ ПАРОЛЯ
// =========================================
if ($action === 'update_password') {
    $newPass = $_POST['new_password'] ?? ''; 
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($newPass) || mb_strlen($newPass) < 6) {
        echo json_encode(['status' => 'error', 'message' => 'Пароль занадто короткий. Мінімум 6 символів.']);
        mysqli_close($link);
        exit;
    }

    if ($newPass !== $confirmPass) {
        echo json_encode(['status' => 'error', 'message' => 'Паролі у полях не збігаються.']);
        mysqli_close($link);
        exit;
    }

    // Хешуємо пароль перед записом в USERS
    $newHash = password_hash($newPass, PASSWORD_BCRYPT);

    $stmt = mysqli_prepare($link, "UPDATE USERS SET PASSWD_HASH = ? WHERE ID = ?");
    mysqli_stmt_bind_param($stmt, "si", $newHash, $userId);
    
    if (mysqli_stmt_execute($stmt)) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['status' => 'success', 'message' => 'Ваш новий пароль успішно збережено!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Не вдалося оновити пароль у базі даних.']);
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($link);
    exit;
}

// Якщо action не розпізнано
echo json_encode(['status' => 'error', 'message' => 'Невідома дія: ' . htmlspecialchars($action)]);
mysqli_close($link);
exit;
?>