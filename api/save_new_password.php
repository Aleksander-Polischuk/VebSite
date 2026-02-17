<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

include ('../config.php');
$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

$email = trim($_POST['email'] ?? '');
$token = trim($_POST['token'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($token) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Не всі дані отримані']);
    exit;
}

// 1. Ще раз перевіряємо токен перед зміною (безпека)
$sql = "SELECT ID FROM USERS WHERE EMAIL = ? AND recovery_token = ?";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "ss", $email, $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    // 2. Хешуємо новий пароль
    $newHash = password_hash($password, PASSWORD_DEFAULT);

    // 3. Оновлюємо пароль і видаляємо токен (recovery_token = NULL)
    // Важливо видалити токен
    $updateSql = "UPDATE USERS "
                ."SET PASSWD_HASH = ?, "
                ."RECOVERY_TOKEN = NULL "
                ."WHERE EMAIL    = ? AND "
                ."RECOVERY_TOKEN = ?";
    $stmtUpdate = mysqli_prepare($link, $updateSql);
    mysqli_stmt_bind_param($stmtUpdate, "sss", $newHash, $email, $token);
    
    if (mysqli_stmt_execute($stmtUpdate)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Помилка бази даних']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Помилка токена. Спробуйте почати спочатку.']);
}
?>