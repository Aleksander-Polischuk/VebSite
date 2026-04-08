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

$caret_icon = '<img src="/img/caret-down-fill.svg" class="tree-icon" width="16" height="16" alt="" style="pointer-events: none;">';

$sql_tariff = "
    SELECT 
        PERIOD, 
        NAME_GROUP, 
        SERVICE, 
        PRICE_WITHOUT_TAX, 
        PRICE_WITH_TAX 
    FROM INF_HISTORY_TARIFF 
    WHERE ID_ORGANIZATIONS = ? 
    ORDER BY PERIOD DESC, NAME_GROUP ASC";

$stmt = mysqli_prepare($link, $sql_tariff);
mysqli_stmt_bind_param($stmt, "i", $orgId);
mysqli_stmt_execute($stmt);
$result_tariff = mysqli_stmt_get_result($stmt);

$treeData = [];
if ($result_tariff) {
    while ($row = mysqli_fetch_assoc($result_tariff)) {
        $dateKey = date('d.m.Y', strtotime($row['PERIOD']));
        $groupKey = !empty($row['NAME_GROUP']) ? $row['NAME_GROUP'] : 'Інші послуги';
        $treeData[$dateKey][$groupKey][] = $row;
    }
}
?>
<link href="../../css/Tariffs.css" rel="stylesheet" type="text/css"/>
<div class="table-header-row sticky-header" id="history-start">
    <h3 style="margin: 0;">Тарифи на послуги</h3>  

    <div class="header-controls">
        <button type="button" class="btn-tree-custom" onclick="stepTree(-1)" title="Згорнути все">
            <img src="/img/arrow-up.svg" width="16" height="16" alt="Згорнути" style="pointer-events: none;">
        </button>
        <button type="button" class="btn-tree-custom" onclick="stepTree(1)" title="Розгорнути все">
            <img src="/img/arrow-down.svg" width="16" height="16" alt="Розгорнути" style="pointer-events: none;">
        </button>
    </div>
</div>

<div class="table-container" id="history-container">
    <table class="data-table tree-table shadow-table tariffs-table">
        <thead>
            <tr>
                <th>Послуга</th>
                <th>Ціна (без ПДВ)</th>
                <th>Ціна (з ПДВ)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($treeData)): ?>
                <tr><td colspan="3" class="cell-no-data">Дані відсутні.</td></tr>
            <?php else: 
                $d_idx = 0;
                foreach ($treeData as $date => $groups): 
                    $d_idx++;
                    $dateId = "d_" . $d_idx;
            ?>
                <tr class="parent-row open" onclick="toggleTreeDeep(this, '<?php echo $dateId; ?>')">
                    <td colspan="3">
                        <?php echo $caret_icon; ?> <strong>Період: <?php echo $date; ?></strong>
                    </td>
                </tr>

                <?php 
                $g_idx = 0;
                foreach ($groups as $groupName => $services): 
                    $g_idx++;
                    $groupId = $dateId . "_g" . $g_idx;
                ?>
                    <tr class="child-row <?php echo $dateId; ?> sub-parent show open" onclick="toggleTree(this, '<?php echo $groupId; ?>')">
                        <td colspan="3">
                            <?php echo $caret_icon; ?> <?php echo htmlspecialchars($groupName); ?>
                        </td>
                    </tr>

                    <?php foreach ($services as $s): ?>
                        <tr class="child-row <?php echo $groupId; ?> detail-row show">
                            <td>
                                <span class="bullet-icon">•</span> 
                                <?php echo htmlspecialchars($s['SERVICE']); ?>
                            </td>
                            <td class="text-muted">
                                <?php echo number_format($s['PRICE_WITHOUT_TAX'], 3, '.', ' '); ?>
                            </td>
                            <td class="text-muted">
                                <?php echo number_format($s['PRICE_WITH_TAX'], 3, '.', ' '); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php endforeach; ?>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="../../js/table_tree.js" type="text/javascript"></script>