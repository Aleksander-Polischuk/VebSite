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
    echo "<div class='error-msg-padding text-negative'>Будь ласка, оберіть підприємство.</div>";
    exit;
}

function getMetersData($link, $counteragentId, $userId, $orgId) {
    $sql = "
        SELECT 
            rc.`NAME` as ContractName,
            CONCAT(IFNULL(rci.`NAME`, ''), ', ', IFNULL(rs.`NAME`, ''), ', буд. ', IFNULL(rh.`NAME`, '')) AS Address,
            rcn.ID as CounterID,
            CONCAT(IFNULL(rtc.`NAME`, ''), ' №', IFNULL(rcn.FIRM_NUM, '')) AS CounterName,
            iaac.LAST_INDICATION as LastVal,
            (SELECT CNT_CURRENT 
             FROM INF_NEW_COUNTER_READINGS incr 
             WHERE incr.ID_REF_COUNTER = iaac.ID_REF_COUNTER 
               AND incr.ID_ORGANIZATIONS = iaac.ID_ORGANIZATIONS 
               AND incr.ID_USERS = ? 
               AND MONTH(incr.MTIME) = MONTH(CURRENT_DATE())
               AND YEAR(incr.MTIME) = YEAR(CURRENT_DATE())
             ORDER BY incr.MTIME DESC LIMIT 1) AS CurrentVal,
            iaac.ID_REF_ACCOUNT, iaac.ID_REF_COUNTER, iaac.ID_REF_SERVICE 
        
        FROM INF_ACTIVE_ACCOUNT_COUNTER iaac
        
        INNER JOIN REF_ACCOUNT ra 
        ON (ra.id = iaac.ID_REF_ACCOUNT)
        
        INNER JOIN REF_CONTRACT rc 
        ON (rc.id = ra.ID_REF_CONTRACT)
        
        INNER JOIN REF_COUNTER rcn 
        ON (rcn.id = iaac.ID_REF_COUNTER)
        
        INNER JOIN ACCESS acc 
        ON (acc.ID_REF_COUNTERAGENT = rc.ID_REF_COUNTERAGENT AND 
            acc.ID_ORGANIZATIONS = iaac.ID_ORGANIZATIONS)
        
        LEFT JOIN REF_HOUSE rh 
        ON (rh.id = ra.ID_REF_HOUSE)
        
        LEFT JOIN REF_STREET rs 
        ON (rs.id = rh.ID_REF_STREET)
        
        LEFT JOIN REF_CITY rci 
        ON (rci.id = rs.ID_REF_CITY)
        
        LEFT JOIN REF_TYPE_COUNTER rtc 
        ON (rtc.id = rcn.ID_REF_TYPE_COUNTER)
        
        WHERE acc.ID_USERS = ? AND 
              iaac.ID_ORGANIZATIONS = ? AND 
              acc.ID_REF_COUNTERAGENT = ?
        ORDER BY rc.ID, Address, CounterName";

    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "iiii", $userId, $userId, $orgId, $counteragentId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $tree = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $tree[$row['ContractName'] ?: 'Без договору']['addresses'][$row['Address'] ?: 'Без адреси'][] = $row;
    }
    return $tree;
}

$meters_data = getMetersData($link, $selectedCounteragentId, $userId, $orgId);
$caret_icon = '<img src="/img/caret-down-fill.svg" class="tree-icon icon-no-pointer" width="16" height="16" alt="">';
?>

<link href="../../css/input_meters.css" rel="stylesheet" type="text/css"/>

<div class="table-header-row sticky-header" id="history-start">
    <h3>Передача показників</h3>
    <div class="header-controls">
        <button type="button" class="btn-tree-custom" onclick="stepTree(-1)" title="Згорнути рівень">
            <img src="/img/arrow-up.svg" width="16" height="16" alt="Згорнути">
        </button>
        <button type="button" class="btn-tree-custom" onclick="stepTree(1)" title="Розгорнути рівень">
            <img src="/img/arrow-down.svg" width="16" height="16" alt="Розгорнути">
        </button>
    </div>
</div>

<div class="table-container">
    <table class="data-table tree-table shadow-table fixed-layout">
    <thead>
        <tr>
            <th>Період / Об'єкт</th>
            <th>Попередні</th>
            <th>Поточні</th>
            <th>Різниця</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($meters_data)): ?>
            <tr><td colspan="4" class="cell-no-data">Немає активних лічильників.</td></tr>
        <?php else: 
            $c_idx = 0; $a_idx = 0;
            foreach ($meters_data as $contractName => $contractData): 
                $c_idx++; $cId = "c_" . $c_idx; ?>
                <tr class="parent-row open" onclick="toggleTree(this, '<?php echo $cId; ?>')">
                    <td><?php echo $caret_icon; ?> Договір <?php echo htmlspecialchars($contractName); ?></td>
                    <td></td><td></td>
                    <td><span id="sum_contract_<?php echo $c_idx; ?>" class="font-bold">0,000</span></td>
                </tr>
                <?php foreach ($contractData['addresses'] as $addrName => $meters): 
                    $a_idx++; $aId = $cId . "_a_" . $a_idx; ?>
                    <tr class="child-row show <?php echo $cId; ?> sub-parent open" onclick="toggleTree(this, '<?php echo $aId; ?>')">
                        <td><?php echo $caret_icon; ?> <?php echo htmlspecialchars($addrName); ?></td>
                        <td></td><td></td>
                        <td><span id="sum_address_<?php echo $a_idx; ?>" class="sub-total-val contract-group-<?php echo $c_idx; ?> font-bold text-muted">0,000</span></td>
                    </tr>
                    <?php foreach ($meters as $meter):
                        $inputId = "reading_" . $meter['CounterID'];
                        $diffId = "diff_" . $meter['CounterID'];
                        $rawPrevVal = number_format($meter['LastVal'], 3, '.', '');
                        $curVal = $meter['CurrentVal'];
                        $hasCurrent = ($curVal !== null); ?>
                        <tr class="child-row show <?php echo $aId; ?> detail-row">
                            <td><span class="bullet-icon">•</span> <?php echo htmlspecialchars($meter['CounterName']); ?></td>
                            <td><?php echo number_format($meter['LastVal'], 3, ',', ' '); ?></td>
                            <td>
                                <div class="input-wrapper">
                                    <input type="text" inputmode="decimal" id="<?php echo $inputId; ?>" 
                                           class="input-reading address-group-<?php echo $a_idx; ?> <?php echo $hasCurrent ? 'warning' : ''; ?>" 
                                           placeholder="0.000" autocomplete="off"
                                           value="<?php echo $hasCurrent ? number_format($curVal, 3, '.', '') : ''; ?>" 
                                           data-prev="<?php echo $rawPrevVal; ?>"
                                           data-account="<?php echo $meter['ID_REF_ACCOUNT']; ?>"
                                           data-service="<?php echo $meter['ID_REF_SERVICE']; ?>"
                                           data-counter="<?php echo $meter['ID_REF_COUNTER']; ?>"
                                           data-contract-name="<?php echo htmlspecialchars($contractName); ?>"
                                           data-address-name="<?php echo htmlspecialchars($addrName); ?>"
                                           data-counter-name="<?php echo htmlspecialchars($meter['CounterName']); ?>"
                                           onblur="formatOnBlur(this)"
                                           oninput="handleMeterInput(this, <?php echo $rawPrevVal; ?>, '<?php echo $diffId; ?>', <?php echo $a_idx; ?>, <?php echo $c_idx; ?>)">
                                    <div class="error-icon" <?php echo $hasCurrent ? 'style="display:flex; opacity:1; visibility:visible; background-color:#f39c12;"' : ''; ?>>
                                        <?php echo $hasCurrent ? 'i' : '!'; ?>
                                        <span class="error-tooltip"><?php echo $hasCurrent ? 'Показник за поточний місяць вже передано' : 'Помилка'; ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><span id="<?php echo $diffId; ?>" class="diff-val"><?php echo $hasCurrent ? number_format($curVal - $meter['LastVal'], 3, ',', ' ') : '0,000'; ?></span></td>
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