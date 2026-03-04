<?php
// api/save_meters.php

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config.php";

// Перевірка авторизації
if (!isset($_SESSION['id_users'])) {
    echo json_encode(['status' => 'error', 'message' => 'Немає авторизації']);
    exit;
}

$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
if (!$link) {
    echo json_encode(['status' => 'error', 'message' => 'Помилка БД: ' . mysqli_connect_error()]);
    exit;
}
mysqli_set_charset($link, 'utf8');

// Отримуємо JSON
$inputData = json_decode(file_get_contents('php://input'), true);

if (!$inputData || !is_array($inputData)) {
    echo json_encode(['status' => 'error', 'message' => 'Некоректні дані']);
    exit;
}

$userId = (int)$_SESSION['id_users'];
$orgId  = (int)($IDOrganizations ?? 1);

$sql = "INSERT INTO INF_NEW_COUNTER_READINGS 
        (ID_USERS, ID_ORGANIZATIONS, ID_REF_ACCOUNT, ID_REF_SERVICE, ID_REF_COUNTER, CNT_LAST, CNT_CURRENT, SEND, ERR_CODE) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0)";

$stmt = mysqli_prepare($link, $sql);

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Помилка підготовки запиту: ' . mysqli_error($link)]);
    exit;
}

$insertedCount = 0;
$errors = [];

 //Початок транзакції
mysqli_begin_transaction($link);

try {
    foreach ($inputData as $row) {
        // Валідація даних
        $accId   = (int)$row['id_ref_account'];
        $servId  = (int)$row['id_ref_service'];
        $countId = (int)$row['id_ref_counter'];
        $last    = (float)$row['cnt_last'];
        $curr    = (float)$row['cnt_current'];

        // Додаткова перевірка на сервері
        if ($curr < 0) {
            throw new Exception("Значення не може бути від'ємним");
        }
        
        //Додати перевірку полів ще й в БД
        
        // Прив'язка параметрів (i - int, d - double/decimal)
        mysqli_stmt_bind_param($stmt, "iiiiidd", 
            $userId, 
            $orgId, 
            $accId, 
            $servId, 
            $countId, 
            $last, 
            $curr
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Помилка при вставці: " . mysqli_stmt_error($stmt));
        }
        $insertedCount++;
    }

    mysqli_commit($link); // Зберігаємо зміни
    echo json_encode(['status' => 'success', 'count' => $insertedCount]);

} catch (Exception $e) {
    mysqli_rollback($link); // Відміняємо, якщо щось пішло не так
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

mysqli_stmt_close($stmt);
mysqli_close($link);
?>