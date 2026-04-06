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

$userId = (int)$_SESSION['id_users'];
$orgId  = (int)($IDOrganizations ?? 1);

//Отримання налаштування періоду та ліміт споживання
$resOrg = mysqli_query($link, "SELECT CNT_READING_DAY_START, CNT_READING_DAY_END, ENT_MAX_VOL_BY_CNT FROM ORGANIZATIONS WHERE ID = $orgId");
$orgData = mysqli_fetch_assoc($resOrg);

if ($orgData) {
    $startAccepting = (int)$orgData['CNT_READING_DAY_START'];
    $endAccepting = (int)$orgData['CNT_READING_DAY_END'];
    $maxVolLimit = (float)$orgData['ENT_MAX_VOL_BY_CNT']; // Наш ліміт для перевірки
    $currentDay = (int)date('j');

    // Універсальна перевірка періоду блокування
    $isInsidePeriod = false;
    if ($startAccepting <= $endAccepting) {
        if ($currentDay >= $startAccepting && $currentDay <= $endAccepting) {
            $isInsidePeriod = true;
        }
    } else {
        if ($currentDay >= $startAccepting || $currentDay <= $endAccepting) {
            $isInsidePeriod = true;
        }
    }

    if (!$isInsidePeriod) {
        echo json_encode([
            'status' => 'error', 
            'message' => "Помилка: Прийом показників заблоковано (період прийому з $startAccepting по $endAccepting число)."
        ]);
        exit;
    }
}

// Отримуємо JSON
$inputData = json_decode(file_get_contents('php://input'), true);

if (!$inputData || !is_array($inputData)) {
    echo json_encode(['status' => 'error', 'message' => 'Некоректні дані']);
    exit;
}

$sql = "INSERT INTO INF_NEW_COUNTER_READINGS 
        (ID_USERS, ID_ORGANIZATIONS, ID_REF_ACCOUNT, ID_REF_SERVICE, ID_REF_COUNTER, CNT_LAST, CNT_CURRENT, SEND, ERR_CODE) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0)";

$stmt = mysqli_prepare($link, $sql);

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Помилка підготовки запиту: ' . mysqli_error($link)]);
    exit;
}

$insertedCount = 0;

// Початок транзакції
mysqli_begin_transaction($link);

try {
    foreach ($inputData as $row) {
        // Валідація даних
        $accId   = (int)$row['id_ref_account'];
        $servId  = (int)$row['id_ref_service'];
        $countId = (int)$row['id_ref_counter'];
        $last    = (float)$row['cnt_last'];
        $curr    = (float)$row['cnt_current'];
        
        $diff = $curr - $last;

        // Перевірка на від'ємне споживання
        if ($curr < $last) {
            throw new Exception("Показник не може бути меншим за попередній ($curr < $last)");
        }
        
        // ПЕРЕВІРКА ЛІМІТУ
        // Якщо ліміт в БД більше 0 і різниця перевищує ліміт
        if ($maxVolLimit > 0 && $diff > $maxVolLimit) {
            throw new Exception("Перевищено максимально допустимий об'єм споживання ($diff м³). Максимальний ліміт: $maxVolLimit м³.");
        }

        // Прив'язка параметрів
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
            throw new Exception("Помилка при вставці запису: " . mysqli_stmt_error($stmt));
        }
        $insertedCount++;
    }

    mysqli_commit($link); // Зберігаємо зміни
    echo json_encode(['status' => 'success', 'count' => $insertedCount]);

} catch (Exception $e) {
    mysqli_rollback($link); // Відміняємо все, якщо була помилка ліміту або БД
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

mysqli_stmt_close($stmt);
mysqli_close($link);
?>