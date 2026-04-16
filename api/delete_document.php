<?php
require_once '../config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['id_users'])) {
    echo json_encode(['success' => false, 'error' => 'Сесія завершена.']);
    exit;
}

$docId = (int)($_POST['id'] ?? 0);
$orgId = (int)($IDOrganizations ?? 1);
$caId = (int)($_SESSION['selected_counteragent_id'] ?? 0);

if (!$docId || !$caId) {
    echo json_encode(['success' => false, 'error' => 'Не вказано ID документа або не обрано підприємство.']);
    exit;
}

$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

// 1. ПЕРЕВІРКА: чи існує документ, чи не підписаний він, і чи належить він контрагенту
$checkSql = "SELECT ID_ENUM_SIGN_STATUS 
             FROM DOC_COUNTER_READINGS 
             WHERE ID = ? AND ID_ORGANIZATIONS = ? AND ID_REF_COUNTERAGENT = ?";
$stmtCheck = mysqli_prepare($link, $checkSql);
mysqli_stmt_bind_param($stmtCheck, "iii", $docId, $orgId, $caId);
mysqli_stmt_execute($stmtCheck);
$res = mysqli_stmt_get_result($stmtCheck);
$doc = mysqli_fetch_assoc($res);

if (!$doc) {
    echo json_encode(['success' => false, 'error' => 'Документ не знайдено або у вас немає доступу.']);
    mysqli_close($link);
    exit;
}
if ($doc['ID_ENUM_SIGN_STATUS'] == 2) {
    echo json_encode(['success' => false, 'error' => 'Неможливо вилучити підписаний документ.']);
    mysqli_close($link);
    exit;
}

// === ПОЧАТОК ТРАНЗАКЦІЇ ===
mysqli_begin_transaction($link);

try {
    // 2. Видаляємо показники лічильників
    $sql1 = "DELETE FROM INF_NEW_COUNTER_READINGS WHERE ID_DOC_COUNTER_READINGS = ?";
    $stmt1 = mysqli_prepare($link, $sql1);
    mysqli_stmt_bind_param($stmt1, "i", $docId);
    mysqli_stmt_execute($stmt1);

    // 3. Видаляємо Акт
    $sql2 = "DELETE FROM DOC_COUNTER_READINGS 
             WHERE ID = ? AND 
                   ID_ORGANIZATIONS = ? AND 
                   ID_REF_COUNTERAGENT = ? AND 
                   DOC_PDF_SIGN_COUNTERAGENT IS NULL";
    $stmt2 = mysqli_prepare($link, $sql2);
    mysqli_stmt_bind_param($stmt2, "iii", $docId, $orgId, $caId);
    mysqli_stmt_execute($stmt2);

    // Якщо все пройшло успішно, фіксуємо зміни
    mysqli_commit($link);
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Якщо сталася помилка, відкочуємо видалення показників
    mysqli_rollback($link);
    echo json_encode(['success' => false, 'error' => 'Помилка БД: ' . $e->getMessage()]);
}

mysqli_close($link);