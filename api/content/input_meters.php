<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "config.php"; 

$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

$selectedCounteragentId = $_SESSION['selected_counteragent_id'] ?? null;
$orgId = (int)($IDOrganizations ?? 1);
$userId = $_SESSION['id_users'] ?? 0;

if (!$selectedCounteragentId) {
    echo "<div style='padding:20px; color: #d9534f;'>Будь ласка, оберіть підприємство.</div>";
    exit;
}

// -------------------------------------------------------------------------
// ФУНКЦІЯ ОТРИМАННЯ ДАНИХ
// -------------------------------------------------------------------------
function getMetersData($link, $counteragentId, $userId, $orgId) {
    $sql = "
        SELECT 
            rc.ID as ContractID,
            rc.`NAME` as ContractName,
            
            CONCAT(
                IFNULL(rci.`NAME`, ''), ', ',
                IFNULL(rs.`NAME`, ''), ', буд. ',
                IFNULL(rh.`NAME`, '')
            ) AS Address,
            
            rcn.ID as CounterID,
            CONCAT(
                IFNULL(rtc.`NAME`, ''), ' №',
                IFNULL(rcn.FIRM_NUM, '')
            ) AS CounterName,
            
            rcn.LAST_INDICATION as LastVal,
            ra.id as id_ref_account,
            rcn.id as id_ref_counter,
            rsr.id as id_ref_service 

        FROM ACCESS acc
        LEFT JOIN REF_COUNTERAGENT rct ON (rct.id = acc.ID_REF_COUNTERAGENT)
        LEFT JOIN REF_CONTRACT rc ON (rc.ID_REF_COUNTERAGENT = rct.id)
        LEFT JOIN REF_ACCOUNT ra ON (ra.ID_REF_CONTRACT = rc.id)
        LEFT JOIN REF_HOUSE rh ON (rh.id = ra.ID_REF_HOUSE)
        LEFT JOIN REF_STREET rs ON (rs.id = rh.ID_REF_STREET)
        LEFT JOIN REF_CITY rci ON (rci.id = rs.ID_REF_CITY)
        LEFT JOIN REF_COUNTER rcn ON (rcn.ID_REF_ACCOUNT = ra.id)
        LEFT JOIN REF_TYPE_COUNTER rtc ON (rtc.id = rcn.ID_REF_TYPE_COUNTER)
        LEFT JOIN REF_SERVICE rsr ON (rsr.id = rcn.ID_REF_SERVICE)


        WHERE acc.ID_USERS = ?
          AND acc.ID_ORGANIZATIONS = ?
          AND acc.ID_REF_COUNTERAGENT = ?
          AND rcn.ID IS NOT NULL -- Показуємо тільки якщо є лічильник

        ORDER BY rc.ID, Address
    ";

    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "iii", $userId, $orgId, $counteragentId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $tree = [];

   while ($row = mysqli_fetch_assoc($result)) {
        $contractName = $row['ContractName'] ?: 'Без договору';
        $address = $row['Address'] ?: 'Без адреси';

        // Grouping: Contract -> Address -> Meters
        $tree[$contractName]['addresses'][$address][] = [
            'id'       => $row['CounterID'],
            'name'     => $row['CounterName'],
            'last_val' => (float)$row['LastVal'],
            'account_id' => $row['id_ref_account'],
            'service_id' => $row['id_ref_service'],
            'counter_id' => $row['id_ref_counter']
        ];
    }
    
    return $tree;
}

$meters_data = getMetersData($link, $selectedCounteragentId, $userId, $orgId);

// SVG іконка згортання/розгортання таблиці
$caret_icon = '<img src="/img/caret-down-fill.svg" class="tree-icon" width="16" height="16" alt="" style="pointer-events: none;">';;

?>

<link href="../../css/input_meters.css" rel="stylesheet" type="text/css"/>

<div class="table-header-row sticky-header" id="history-start">
    <h3 style="margin: 0;">Передача показників</h3>

    <div class="header-controls">
        <!--Стрілка згортання таблиці -->
       <button type="button" class="btn-tree-custom" onclick="stepTree(-1)" title="Згорнути все">
            <img src="/img/arrow-up.svg" width="16" height="16" alt="Згорнути" style="pointer-events: none;">
        </button>
        
       <!--Стрілка розгортання таблиці -->
        <button type="button" class="btn-tree-custom" onclick="stepTree(1)" title="Розгорнути все">
            <img src="/img/arrow-down.svg" width="16" height="16" alt="Розгорнути" style="pointer-events: none;">
        </button>
    </div>
</div>

<div class="table-container">
    <table class="data-table tree-table shadow-table" style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr>
            <th style="text-align: left; padding: 12px; width: 40%;">Період / Об'єкт</th>
            <th style="text-align:center; width: 20%;">Попередні</th>
            <th style="text-align:center; width: 20%;">Поточні</th>
            <th style="text-align:center; width: 20%;">Різниця</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        if (empty($meters_data)): ?>
            <tr>
                <td colspan="4" style="text-align:center; padding: 20px;">
                    Немає лічильників для обраного підприємства.
                </td>
            </tr>
        <?php else: 
            $c_idx = 0;
            $a_idx = 0;

            foreach ($meters_data as $contractName => $contractData): 
                $c_idx++;
                $cId = "c_" . $c_idx;
        ?>
                <tr class="parent-row open" onclick="toggleTree(this, '<?php echo $cId; ?>')">
                    <td><?php echo $caret_icon; ?> Договір <?php echo htmlspecialchars($contractName); ?></td>

                    <td></td> <td></td> <td align="center">
                        <span id="sum_contract_<?php echo $c_idx; ?>" style="font-weight:bold;">0,000</span>
                    </td>
                </tr>

                <?php 
                foreach ($contractData['addresses'] as $addrName => $meters): 
                    $a_idx++;
                    $aId = $cId . "_a_" . $a_idx;
                ?>
                    <tr class="child-row show <?php echo $cId; ?> sub-parent open" onclick="toggleTree(this, '<?php echo $aId; ?>')">
                        <td style="padding-left: 30px; font-weight: 500;">
                            <?php echo $caret_icon; ?> <?php echo htmlspecialchars($addrName); ?>
                        </td>

                        <td></td> <td></td> <td align="center">
                            <span id="sum_address_<?php echo $a_idx; ?>" 
                                  class="sub-total-val contract-group-<?php echo $c_idx; ?>" 
                                  style="font-weight:bold; color: #555;">0,000</span>
                        </td>
                    </tr>

                    <?php 
                    // РІВЕНЬ 3: ЛІЧИЛЬНИКИ (Тут змін немає, все вірно)
                    foreach ($meters as $meter):
                        $inputId = "reading_" . $meter['id'];
                        $diffId = "diff_" . $meter['id'];

                        $rawPrevVal = number_format($meter['last_val'], 3, '.', '');
                        $displayPrevVal = number_format($meter['last_val'], 3, ',', ' ');
                    ?>
                        <tr class="child-row show <?php echo $aId; ?> detail-row">
                            <td style="padding-left: 65px; color: #444;">
                                <span style="color: #3C9ADC;">•</span> 
                                <?php echo htmlspecialchars($meter['name']); ?>
                            </td>

                            <td align="center">
                                <?php echo $displayPrevVal; ?>
                            </td>

                            <td align="center">
                                <div class="input-wrapper">
                                    <input type="text" 
                                           inputmode="decimal"
                                           id="<?php echo $inputId; ?>" 
                                           class="input-reading address-group-<?php echo $a_idx; ?>" 
                                           placeholder="0.000"
                                           autocomplete="off"

                                           data-prev="<?php echo $rawPrevVal; ?>"
                                           data-account="<?php echo $meter['account_id']; ?>"
                                           data-service="<?php echo $meter['service_id']; ?>"
                                           data-counter="<?php echo $meter['counter_id']; ?>"

                                           onblur="formatOnBlur(this)"
                                           oninput="handleMeterInput(this, <?php echo $rawPrevVal; ?>, '<?php echo $diffId; ?>', <?php echo $a_idx; ?>, <?php echo $c_idx; ?>)">

                                    <div class="error-icon">
                                        !
                                        <span class="error-tooltip">Помилка</span>
                                    </div>
                                </div>
                            </td>

                            <td align="center">
                                <span id="<?php echo $diffId; ?>" class="diff-val">0,000</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
</div>

<div class="bottom-controls">
    <button class="btn-save" onclick="saveReadings()">Зберегти показники</button>
</div>