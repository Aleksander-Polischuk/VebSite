<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "config.php";
$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

$userId = (int)($_SESSION['id_users'] ?? 0);
$orgId = (int)($IDOrganizations ?? 0);

$SQLExec = "
    SELECT 
        RC.ID,
        RC.`NAME`,
        RC.EDRPOU
    FROM ACCESS AS A
    INNER JOIN REF_COUNTERAGENT AS RC ON (RC.ID = A.ID_REF_COUNTERAGENT AND RC.ID_ORGANIZATIONS = A.ID_ORGANIZATIONS)
    WHERE 
       A.ID_USERS = $userId AND 
       A.ID_ORGANIZATIONS = $orgId AND
       (A.DEL = 0 OR A.DEL IS NULL)";

$s_res = mysqli_query($link, $SQLExec);

$enterprises = [];
while ($row = mysqli_fetch_array($s_res)) {
    if (empty($_SESSION['selected_counteragent_id']) && empty($enterprises)) {
        $_SESSION['selected_counteragent_id'] = $row['ID'];
    }
    $enterprises[] = $row;
}

// Якщо підприємств немає взагалі, очищаємо сесію
if (empty($enterprises)) {
    unset($_SESSION['selected_counteragent_id']);
}

?>
<link href="../../css/list_enterprise.css" rel="stylesheet" type="text/css"/>

<div class="table-header-row sticky-header enterprise-header">
     <h3>Список підприємств</h3>    
</div>

<div class="table-container">
    <?php if (empty($enterprises)): ?>
        <div class="blocking-notice">
            <div class="blocking-notice-icon-wrapper">
                <img src="/img/exclamation-triangle-fill.svg" class="blocking-notice-icon" alt="Увага">
            </div>
            <h4 class="blocking-notice-title">У вас немає доступних підприємств</h4>
            <p class="blocking-notice-text">
                Наразі за вашим обліковим записом не закріплено жодного активного підприємства, або доступ було призупинено. Дякуємо за розуміння!
            </p>
        </div>
    <?php else: ?>
        <table class="data-table fixed-layout simple-list shadow-table">
            <thead>
                <tr>
                    <th>Назва</th>
                    <th>ЄДРПОУ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($enterprises as $ent): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ent['NAME']); ?></td>
                        <td><?php echo htmlspecialchars($ent['EDRPOU']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>