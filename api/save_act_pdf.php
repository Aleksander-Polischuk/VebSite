<?php
require_once '../config.php'; 
session_start();

header('Content-Type: application/json');

// Перевірка сесії
if (!isset($_SESSION['id_users'])) {
    echo json_encode(['success' => false, 'error' => 'Сесія завершена. Авторизуйтесь знову.']);
    exit;
}

$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
if (!$link) {
    echo json_encode(['success' => false, 'error' => 'Помилка з\'єднання: ' . mysqli_connect_error()]);
    exit;
}
mysqli_set_charset($link, 'utf8');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['act_pdf'])) {
    
    $pdfContent = file_get_contents($_FILES['act_pdf']['tmp_name']);
    
    $id_contract = isset($_POST['id_contract']) ? (int)$_POST['id_contract'] : 0;
    $id_counteragent = $_SESSION['selected_counteragent_id'] ?? 0;
    $id_organization = (int)($IDOrganizations ?? 1); // Глобальна змінна з config.php
    
    $sql = "INSERT INTO DOC_COUNTER_READINGS 
            (ID_ORGANIZATIONS, ID_REF_COUNTERAGENT, ID_REF_CONTRACT, DOC_PDF, ID_ENUM_SIGN_STATUS) 
            VALUES (?, ?, ?, ?, 1)";
            
    $stmt = mysqli_prepare($link, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iiis", $id_organization, $id_counteragent, $id_contract, $pdfContent);
        
        if (mysqli_stmt_execute($stmt)) {
            $new_doc_id = mysqli_insert_id($link); 

            echo json_encode([
                'success' => true, 
                'doc_id' => $new_doc_id
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'error' => 'Помилка виконання: ' . mysqli_stmt_error($stmt)
            ]);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['success' => false, 'error' => 'Помилка підготовки запиту: ' . mysqli_error($link)]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Дані не отримано або файл порожній']);
}

mysqli_close($link);