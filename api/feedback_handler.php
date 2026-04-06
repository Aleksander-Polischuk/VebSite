<?php
ob_start(); 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

$userId = $_SESSION['id_users'] ?? 0;
if (!$userId) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Сесія закінчилася.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_feedback') {
    $to = "abon.cv.pokazniky@gmail.com"; /// Тут необхідно вставити адресу сапорту
    $subjectText = trim($_POST['subject'] ?? 'Без теми');
    $messageBody = $_POST['message'] ?? '';
    
    include "../config.php";
    $link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
    
    if (!$link) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Помилка БД']);
        exit;
    }

    mysqli_set_charset($link, 'utf8');
    
    // Отримуємо EMAIL та PHONE
    $userRes = mysqli_query($link, "SELECT EMAIL, PHONE FROM USERS WHERE ID = " . (int)$userId);
    $userData = mysqli_fetch_assoc($userRes);
    
    $userEmail = $userData['EMAIL'] ?? 'Невідомо';
    $userPhone = $userData['PHONE'] ?? 'Не вказано';

    $boundary = md5(uniqid(time()));

    //ДИНАМІЧНЕ ІМ'Я ВІДПРАВНИКА
    $fromName = "Звернення від $userEmail ($userPhone)";
    
    $fromNameEncoded = "=?UTF-8?B?" . base64_encode($fromName) . "?=";

    //ЗАГОЛОВКИ
    $headers = "MIME-Version: 1.0\r\n";

    $headers .= "From: $fromNameEncoded <noreply@cv.kgonline.in.ua>\r\n";
    $headers .= "Reply-To: $userEmail\r\n";
    $headers .= "Return-Path: noreply@zenit.org.ua\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

    // Тема з кодуванням для кирилиці
    $subjectEncoded = "=?UTF-8?B?" . base64_encode($subjectText) . "?=";

    // Тіло повідомлення
    $body = "--$boundary\r\n";
    $body .= "Content-Type: text/html; charset=utf-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    
    $body .= "<h3>Нове повідомлення (ID користувача: $userId)</h3>";
    $body .= "<p><b>Телефон:</b> <a href='tel:$userPhone'>$userPhone</a></p>"; 
    $body .= "<p><b>Email:</b> $userEmail</p>";
    $body .= "<p><b>Тема:</b> $subjectText</p>";
    $body .= "<hr><div>$messageBody</div>\r\n\r\n";

    // Обробка файлів
    if (!empty($_FILES['files']['name'][0])) {
        foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {
            if (is_uploaded_file($tmpName)) {
                $fileName = $_FILES['files']['name'][$key];
                $fileType = $_FILES['files']['type'][$key];
                $fileContent = file_get_contents($tmpName);
                $fileData = chunk_split(base64_encode($fileContent));

                $body .= "--$boundary\r\n";
                $body .= "Content-Type: $fileType; name=\"$fileName\"\r\n";
                $body .= "Content-Disposition: attachment; filename=\"$fileName\"\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $body .= $fileData . "\r\n\r\n";
            }
        }
    }
    $body .= "--$boundary--";

    // Надсилання
    $mailSent = mail($to, $subjectEncoded, $body, $headers);

    ob_clean(); 
    if ($mailSent) {
        echo json_encode(['status' => 'success', 'message' => 'Повідомлення надіслано на вашу пошту!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Помилка при відправці. Перевірте налаштування mail() на сервері.']);
    }
    
    mysqli_close($link);
    exit;
}