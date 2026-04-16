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
    $id_organization = (int)($IDOrganizations ?? 1); 
    $id_users = (int)$_SESSION['id_users'];
    
    mysqli_begin_transaction($link);
    
    try {
        // Зберігаємо АКТ
        $sql = "INSERT INTO DOC_COUNTER_READINGS 
                (ID_ORGANIZATIONS, ID_REF_COUNTERAGENT, ID_REF_CONTRACT, DOC_PDF, ID_ENUM_SIGN_STATUS) 
                VALUES (?, ?, ?, ?, 1)";
                
        $stmt = mysqli_prepare($link, $sql);
        if (!$stmt) throw new Exception('Помилка підготовки запиту акту: ' . mysqli_error($link));
        
        mysqli_stmt_bind_param($stmt, "iiis", $id_organization, $id_counteragent, $id_contract, $pdfContent);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Помилка виконання запиту акту: ' . mysqli_stmt_error($stmt));
        }
        
        $new_doc_id = mysqli_insert_id($link); 
        mysqli_stmt_close($stmt);

        // ЗБЕРІГАЄМО ПОКАЗНИКИ ЛІЧИЛЬНИКІВ З ПРИВ'ЯЗКОЮ ДО АКТУ
        if (isset($_POST['meters_data'])) {
            $meters_data = json_decode($_POST['meters_data'], true);
            
            if (is_array($meters_data) && count($meters_data) > 0) {
                $sql_meter = "INSERT INTO INF_NEW_COUNTER_READINGS 
                              (ID_USERS, ID_ORGANIZATIONS, ID_REF_ACCOUNT, ID_REF_SERVICE, ID_REF_COUNTER, CNT_LAST, CNT_CURRENT, ID_DOC_COUNTER_READINGS, ID_ENUM_SIGN_STATUS) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
                
                $stmt_meter = mysqli_prepare($link, $sql_meter);
                if (!$stmt_meter) throw new Exception('Помилка підготовки запиту лічильників: ' . mysqli_error($link));

                foreach ($meters_data as $meter) {
                    $id_account = (int)$meter['idAccount'];
                    $id_service = (int)$meter['idService'];
                    $id_counter = (int)$meter['idCounter'];

                    $cnt_last = (float)str_replace(',', '.', $meter['prevValue']);
                    $cnt_current = (float)str_replace(',', '.', $meter['currValue']);

                    mysqli_stmt_bind_param($stmt_meter, "iiiiiddi", 
                        $id_users, $id_organization, $id_account, $id_service, $id_counter, 
                        $cnt_last, $cnt_current, $new_doc_id
                    );
                    
                    if (!mysqli_stmt_execute($stmt_meter)) {
                        throw new Exception('Помилка збереження показника лічильника: ' . mysqli_stmt_error($stmt_meter));
                    }
                }
                mysqli_stmt_close($stmt_meter);
            }
        }

        mysqli_commit($link);

        echo json_encode([
            'success' => true, 
            'doc_id' => $new_doc_id
        ]);

    } catch (Exception $e) {
        mysqli_rollback($link);
        
        echo json_encode([
            'success' => false, 
            'error' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Дані не отримано або файл порожній']);
}

mysqli_close($link);