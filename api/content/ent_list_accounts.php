<?php
// Перевіряємо, чи сесія вже запущена
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "config.php";
$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

// Отримуємо дані з сесії
$userId = $_SESSION['id_users'] ?? 0;
$orgId = $IDOrganizations ?? 1; 
$selectedCounteragentId = $_SESSION['selected_counteragent_id'] ?? null;

if (!$selectedCounteragentId) {
    echo "<div style='padding:20px;'>Будь ласка, оберіть підприємство у списку зверху.</div>";
    exit;
}

// SVG іконка стрілки
$caret_icon = '<img src="/img/caret-down-fill.svg" class="tree-icon" width="16" height="16" alt="" style="pointer-events: none;">';

// Текст попередження
$warning_text_msg = '<span style="color: #d32f2f; font-weight: normal; font-size: 12px; margin-left: 10px;">— Термін повірки спливає або минув!</span>';

// SQL запит
$SQLExec = "
    SELECT 
        rc.ID as ContractID,
        rc.`NAME` as ContractName,
        acc.ID_REF_COUNTERAGENT,
        CONCAT(IFNULL(rci.`NAME`, ''), ', ', IFNULL(rs.`NAME`, ''), ', буд. ', IFNULL(rh.`NAME`, '')) AS Address,
        rcn.ID as CounterID,
        rcn.DATE_NEXT_VERIFICATION
    FROM ACCESS acc
    LEFT JOIN REF_COUNTERAGENT rct ON (rct.id = acc.ID_REF_COUNTERAGENT)
    LEFT JOIN REF_CONTRACT rc ON (rc.ID_REF_COUNTERAGENT = rct.id)
    LEFT JOIN REF_ACCOUNT ra ON (ra.ID_REF_CONTRACT = rc.id)
    LEFT JOIN REF_HOUSE rh ON (rh.id = ra.ID_REF_HOUSE)
    LEFT JOIN REF_STREET rs ON (rs.id = rh.ID_REF_STREET)
    LEFT JOIN REF_CITY rci ON (rci.id = rs.ID_REF_CITY)
    LEFT JOIN REF_COUNTER rcn ON (rcn.ID_REF_ACCOUNT = ra.id)
    
    WHERE acc.ID_USERS = " . (int)$userId . " 
      AND acc.ID_ORGANIZATIONS = " . (int)$orgId . "
      AND acc.ID_REF_COUNTERAGENT = " . (int)$selectedCounteragentId . "
    ORDER BY rc.ID, Address";

$s_res = mysqli_query($link, $SQLExec);

// === ЛОГІКА ОБРОБКИ ДАНИХ ===
$treeData = [];
$dateThreshold = date('Y-m-d', strtotime('+1 month'));

while ($row = mysqli_fetch_assoc($s_res)) {
    $cID = $row['ContractID'];
    $addr = $row['Address'];
    
    // 1. Ініціалізація Договору
    if (!isset($treeData[$cID])) {
        $treeData[$cID] = [
            'name' => $row['ContractName'] ?? 'Без номера',
            'addresses' => []
        ];
    }
    
    // 2. Ініціалізація Адреси
    if (!isset($treeData[$cID]['addresses'][$addr])) {
        $treeData[$cID]['addresses'][$addr] = [
            'min_date' => null, 
            'has_warning' => false
        ];
    }
    
    // 3. Обробка лічильника (шукаємо найменшу дату тільки для цієї адреси)
    if (!empty($row['CounterID']) && !empty($row['DATE_NEXT_VERIFICATION'])) {
        $currDate = $row['DATE_NEXT_VERIFICATION'];
        
        // Перевіряємо, чи ця дата менша за вже записану для цієї адреси
        if ($treeData[$cID]['addresses'][$addr]['min_date'] === null || 
            $currDate < $treeData[$cID]['addresses'][$addr]['min_date']) {
            
            $treeData[$cID]['addresses'][$addr]['min_date'] = $currDate;
        }

        // Перевірка на простроченість (тільки для адреси)
        if ($currDate <= $dateThreshold) {
            $treeData[$cID]['addresses'][$addr]['has_warning'] = true;
        }
    }
}
?>
<!--Comment -->
<link href="../../css/ent_list_accounts.css" rel="stylesheet" type="text/css"/>

<div class="table-header-row sticky-header" id="history-start">
     <h3 style="margin: 0;">Список адрес та лічильників</h3>  
     
     <div class="header-controls">
       <button type="button" class="btn-tree-custom" onclick="stepTree(-1)" title="Згорнути договори">
            <img src="/img/arrow-up.svg" width="16" height="16" alt="Згорнути" style="pointer-events: none;">
        </button>
        <button type="button" class="btn-tree-custom" onclick="stepTree(1)" title="Розгорнути договори">
            <img src="/img/arrow-down.svg" width="16" height="16" alt="Розгорнути" style="pointer-events: none;">
        </button>
    </div>
</div>

<div class="table-container">
    <table class="data-table tree-table shadow-table address-list" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th style="text-align: left; padding: 12px; width: 40%;">Об'єкти</th>
                <th style="text-align: left; padding: 12px; width: 60%;">Найближча повірка</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (empty($treeData)): 
            ?>
                <tr>
                    <td colspan="2" style="padding: 20px; text-align: center;">Даних не знайдено.</td>
                </tr>
            <?php 
            else:
                $c_idx = 0;
                foreach ($treeData as $contractData): 
                    $c_idx++;
                    $contractGroupId = "c_group_" . $c_idx;
            ?>
                    <tr class="parent-row open" onclick="toggleTree(this, '<?php echo $contractGroupId; ?>')">
                        <td>
                            <?php echo $caret_icon; ?>
                            <strong>Договір:</strong> <?php echo htmlspecialchars($contractData['name']); ?>
                        </td>
                        <td></td> </tr>

                    <?php 
                    foreach ($contractData['addresses'] as $addressName => $addrData): 
                        // Формування дати ТІЛЬКИ для адреси
                        $addrDateOutput = '-';
                        if ($addrData['min_date']) {
                            $dValA = date('d.m.Y', strtotime($addrData['min_date']));
                            
                            if ($addrData['has_warning']) {
                                // Червона дата + текст
                                $addrDateOutput = '<span style="color: #d32f2f; font-weight: bold;">' . $dValA . '</span>' . $warning_text_msg;
                            } else {
                                // Звичайна дата
                                $addrDateOutput = $dValA;
                            }
                        } else {
                            $addrDateOutput = '<span style="color: #999; font-style: italic;">Лічильники відсутні або без дати</span>';
                        }
                    ?>
                        <tr class="child-row show <?php echo $contractGroupId; ?> detail-row">
                            <td style="padding-left: 40px; border-bottom: 1px solid #eee; vertical-align: middle;">
                                <span style="color: #3C9ADC; margin-right: 5px;">•</span>
                                <?php echo htmlspecialchars($addressName); ?>
                            </td>
                            
                            <td style="text-align: left; border-bottom: 1px solid #eee; vertical-align: middle;">
                                <?php echo $addrDateOutput; ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>