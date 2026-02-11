<?php
header('Content-Type: application/json');

// Функція відправки HTML листа
function SendRecoveryMail($to, $token) {
    $subject = "Відновлення пароля develop.kgonline.in.ua";
    $subject = '=?utf-8?B?'.base64_encode($subject).'?=';
    
    // Вказати дійсну пошту домену
    $headers .= "From: noreply@develop.kgonline.in.ua\r\n";
    
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP\r\n";
    $headers .= "X-Priority: 2 (High)\r\n";
 
    // Формуємо посилання
    $link = "http://develop.kgonline.in.ua/recovery?token=$token&email=" . urlencode($to);

    // ЗМІНА 2: Завантажуємо шаблон з файлу
    $templatePath = '../templates/recovery_mail.html';
    
    if (file_exists($templatePath)) {
        $message = file_get_contents($templatePath);
        // ЗМІНА 3: Замінюємо мітку {{LINK}} на реальне посилання (двічі, бо вона є і в кнопці, і текстом)
        $message = str_replace('{{LINK}}', $link, $message);
    } else {
        // Запасний варіант, якщо файл шаблону не знайдено
        $message = "Посилання для відновлення: $link";
    }

    mail($to, $subject, $message, $headers);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

include ('../config.php');
$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email порожній']);
    exit;
}

// 1. Шукаємо користувача
$sql = "SELECT ID, EMAIL FROM USERS WHERE EMAIL = ?";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $userId = $row['ID'];
    
    // 2. Генеруємо унікальний токен
    $token = bin2hex(random_bytes(16)); 

    // 3. ЗБЕРІГАЄМО ТОКЕН У БАЗУ
    $sqlToken = "UPDATE USERS SET RECOVERY_TOKEN = '$token' WHERE ID = $userId";
    mysqli_query($link, $sqlToken);

    // 4. Відправляємо красивий лист
    SendRecoveryMail($email, $token);
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Такий E-mail не знайдено']);
}
?>