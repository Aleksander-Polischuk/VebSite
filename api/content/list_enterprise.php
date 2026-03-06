<?php
/** * LOGIC PART (Controller)
 * Отримуємо та готуємо всі дані заздалегідь
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "config.php";
$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

$userId = (int)($_SESSION['id_users'] ?? 0);
$orgId = (int)($IDOrganizations ?? 0);

// Запит до бази даних
$SQLExec = "
    SELECT 
        RC.ID,
        RC.`NAME`,
        RC.EDRPOU
    FROM ACCESS AS A
    INNER JOIN REF_COUNTERAGENT AS RC ON (RC.ID = A.ID_REF_COUNTERAGENT AND RC.ID_ORGANIZATIONS = A.ID_ORGANIZATIONS)
    WHERE 
       A.ID_USERS = $userId AND 
       A.ID_ORGANIZATIONS = $orgId";

$s_res = mysqli_query($link, $SQLExec);

// Обробка автовибору (якщо нічого не обрано, беремо перше підприємство)
$enterprises = [];
while ($row = mysqli_fetch_array($s_res)) {
    if (empty($_SESSION['selected_counteragent_id']) && empty($enterprises)) {
        $_SESSION['selected_counteragent_id'] = $row['ID'];
    }
    $enterprises[] = $row;
}
?>

<link href="../../css/list_enterprise.css" rel="stylesheet" type="text/css"/>

<div class="table-header-row sticky-header enterprise-header">
     <h3>Список підприємств</h3>    
</div>

<div class="table-container">
    <table class="data-table fixed-layout simple-list shadow-table">
        <thead>
            <tr>
                <th>Назва</th>
                <th>ЄДРПОУ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($enterprises)): ?>
                <tr>
                    <td colspan="2" class="cell-no-data">Підприємств не знайдено.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($enterprises as $ent): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ent['NAME']); ?></td>
                        <td><?php echo htmlspecialchars($ent['EDRPOU']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>