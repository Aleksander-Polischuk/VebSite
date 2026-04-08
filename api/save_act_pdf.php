<?php
require_once '../config.php'; // Ваше підключення до БД
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['act_pdf'])) {
    
    // Отримуємо бінарні дані файлу
    $pdfContent = file_get_contents($_FILES['act_pdf']['tmp_name']);
    
    $id_contract = $_POST['id_contract'] ?? null;
    $id_counteragent = $_SESSION['selected_counteragent_id'] ?? null;
    $id_organization = $_SESSION['id_organizations'] ?? 1;
    
    // Генеруємо ID (якщо у вас таблиця без AUTO_INCREMENT, інакше цей крок пропускаємо і ID з бази беремо)
    // Припустимо, у вас AUTO_INCREMENT на ID, тоді запит такий:
    
    $sql = "INSERT INTO DOC_COUNTER_READINGS 
            (ID_ORGANIZATIONS, ID_REF_COUNTERAGENT, ID_REF_CONTRACT, DOC_PDF, ID_ENUM_SIGN_STATUS) 
            VALUES (?, ?, ?, ?, 1)";
            
    $stmt = $conn->prepare($sql);
    
    // 'iiib' означає: int, int, int, blob (для mysqli)
    // Якщо використовуєте PDO, синтаксис буде трохи іншим
    $stmt->bind_param("iiis", $id_organization, $id_counteragent, $id_contract, $pdfContent);
    
    if ($stmt->execute()) {
        $new_doc_id = $stmt->insert_id;
        echo json_encode(['success' => true, 'doc_id' => $new_doc_id]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
}