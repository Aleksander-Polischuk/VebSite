<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "config.php";
$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

$userId = $_SESSION['id_users'] ?? 0;
$orgId = $IDOrganizations ?? 1; 
$selectedCounteragentId = $_SESSION['selected_counteragent_id'] ?? null;

if (!$selectedCounteragentId) {
    echo "<div style='padding:20px;'>Будь ласка, оберіть підприємство у списку зверху.</div>";
    exit;
}

// SQL-запит для тарифів
$sql_tariff = "
    SELECT 
        PERIOD, 
        NAME_GROUP, 
        SERVICE, 
        PRICE_WITHOUT_TAX, 
        PRICE_WITH_TAX 
    FROM INF_HISTORY_TARIFF 
    WHERE ID_ORGANIZATIONS = ? 
    ORDER BY NAME_GROUP ASC, PERIOD DESC
";

$stmt = mysqli_prepare($link, $sql_tariff);
mysqli_stmt_bind_param($stmt, "i", $orgId);
mysqli_stmt_execute($stmt);
$result_tariff = mysqli_stmt_get_result($stmt);

$tariffsByGroup = [];

if ($result_tariff) {
    while ($row = mysqli_fetch_assoc($result_tariff)) {
        $groupName = !empty($row['NAME_GROUP']) ? $row['NAME_GROUP'] : 'Інші послуги'; 
        $tariffsByGroup[$groupName][] = $row;
    }
}
?>

<link href="../../css/ent_list_accounts.css" rel="stylesheet" type="text/css"/>

<div class="table-header-row sticky-header" id="history-start">
    <h3 style="margin: 0; flex-grow: 1;">Тарифи на послуги</h3>  
</div>

<div id="tariffs-wrapper" style="padding-bottom: 20px;">
    <?php if (!empty($tariffsByGroup)): ?>
        
        <?php foreach ($tariffsByGroup as $groupName => $tariffs): ?>
            
            <div style="background-color: #f2f2f2; padding: 12px 15px; border-radius: 4px 4px 0 0; border: 1px solid #ddd; border-bottom: none; font-weight: bold; margin-top: 20px; color: #333; line-height: 1.4;">
                <?php echo htmlspecialchars($groupName); ?>
            </div>

            <div class="table-container" style="margin-bottom: 0; border-radius: 0 0 4px 4px; border: 1px solid #ddd; overflow-x: auto;">
                <table class="data-table tree-table" style="width: 100%; border-collapse: collapse; min-width: 600px; margin: 0;">
                    <thead style="background: #fafafa;">
                        <tr>
                            <th style="text-align: center; padding: 12px; width: 15%; border-bottom: 1px solid #ddd;">Період</th>
                            <th style="text-align: left; padding: 12px; width: 45%; border-bottom: 1px solid #ddd;">Послуга</th>
                            <th style="text-align: center; width: 20%; border-bottom: 1px solid #ddd;">Ціна (без ПДВ)</th>
                            <th style="text-align: center; width: 20%; border-bottom: 1px solid #ddd;">Ціна (з ПДВ)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tariffs as $tariff): ?>
                            <tr class="child-row detail-row show" style="display: table-row;">
                                <td align="center" style="border-bottom: 1px solid #eee; padding: 10px;">
                                    <?php echo date('d.m.Y', strtotime($tariff['PERIOD'])); ?>
                                </td>
                                <td style="padding: 10px 10px 10px 20px; border-bottom: 1px solid #eee; font-size: 13px; color: #666;">
                                    <span style="color: #4a76f2; margin-right: 8px;">•</span> 
                                    <?php echo htmlspecialchars($tariff['SERVICE']); ?>
                                </td>
                                <td align="center" style="border-bottom: 1px solid #eee; padding: 10px; font-weight: 500;">
                                    <?php echo number_format($tariff['PRICE_WITHOUT_TAX'], 3, '.', ' '); ?>
                                </td>
                                <td align="center" style="border-bottom: 1px solid #eee; padding: 10px; font-weight: 500;">
                                    <?php echo number_format($tariff['PRICE_WITH_TAX'], 3, '.', ' '); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endforeach; ?>
        
    <?php else: ?>
        <div style="padding: 30px; text-align: center; color: #666; background: #fff; border: 1px solid #ddd; border-radius: 4px; margin-top: 20px;">
            Дані про тарифи відсутні.
        </div>
    <?php endif; ?>
</div>