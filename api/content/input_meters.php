<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "config.php"; 

$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
if (!$link) {
    echo "<div class='error-msg-padding text-negative'>Помилка з'єднання з базою даних.</div>";
    exit;
}
mysqli_set_charset($link, 'utf8');

$selectedCounteragentId = $_SESSION['selected_counteragent_id'] ?? null;
$orgId = (int)($IDOrganizations ?? 1);
$userId = $_SESSION['id_users'] ?? 0;

if (!$selectedCounteragentId) {
    echo "<div class='error-msg-padding text-negative'>Будь ласка, оберіть підприємство.</div>";
    exit;
}

// 1. Отримуємо налаштування організації: період прийому та ліміти об'ємів
$orgQuery = "SELECT CNT_READING_DAY_START, CNT_READING_DAY_END, ENT_MAX_VOL_BY_CNT, ENT_MAX_VOL_BY_CNT_WARNING FROM ORGANIZATIONS WHERE ID = ?";
$stmtOrg = mysqli_prepare($link, $orgQuery);
mysqli_stmt_bind_param($stmtOrg, "i", $orgId);
mysqli_stmt_execute($stmtOrg);
$orgRes = mysqli_stmt_get_result($stmtOrg);
$orgData = mysqli_fetch_assoc($orgRes);

$startAccepting = (int)($orgData['CNT_READING_DAY_START'] ?? 1); 
$endAccepting = (int)($orgData['CNT_READING_DAY_END'] ?? 1);
$maxVolLimit = (float)($orgData['ENT_MAX_VOL_BY_CNT'] ?? 0); 
$warningVolLimit = (float)($orgData['ENT_MAX_VOL_BY_CNT_WARNING'] ?? 0);

$currentDay = (int)date('j');

// 2. Універсальна перевірка періоду прийому (працює навіть при переході через місяць)
$isInsidePeriod = false;
if ($startAccepting <= $endAccepting) {
    if ($currentDay >= $startAccepting && $currentDay <= $endAccepting) {
        $isInsidePeriod = true;
    }
} else {
    if ($currentDay >= $startAccepting || $currentDay <= $endAccepting) {
        $isInsidePeriod = true;
    }
}

$isBlocked = !$isInsidePeriod;

/**
 * Функція отримання даних лічильників
 */
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
        INNER JOIN REF_ACCOUNT ra ON (ra.id = iaac.ID_REF_ACCOUNT)
        INNER JOIN REF_CONTRACT rc ON (rc.id = ra.ID_REF_CONTRACT)
        INNER JOIN REF_COUNTER rcn ON (rcn.id = iaac.ID_REF_COUNTER)
        INNER JOIN ACCESS acc ON (acc.ID_REF_COUNTERAGENT = rc.ID_REF_COUNTERAGENT AND acc.ID_ORGANIZATIONS = iaac.ID_ORGANIZATIONS)
        LEFT JOIN REF_HOUSE rh ON (rh.id = ra.ID_REF_HOUSE)
        LEFT JOIN REF_STREET rs ON (rs.id = rh.ID_REF_STREET)
        LEFT JOIN REF_CITY rci ON (rci.id = rs.ID_REF_CITY)
        LEFT JOIN REF_TYPE_COUNTER rtc ON (rtc.id = rcn.ID_REF_TYPE_COUNTER)
        WHERE acc.ID_USERS = ? AND iaac.ID_ORGANIZATIONS = ? AND acc.ID_REF_COUNTERAGENT = ?
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

$meters_data = $isBlocked ? [] : getMetersData($link, $selectedCounteragentId, $userId, $orgId);
$caret_icon = '<img src="/img/caret-down-fill.svg" class="tree-icon icon-no-pointer" width="16" height="16" alt="">';
?>

<link href="../../css/input_meters.css" rel="stylesheet" type="text/css"/>

<div class="table-header-row sticky-header" id="history-start">
    <h3>Передача показників</h3>
    <?php if (!$isBlocked): ?>
    <div class="header-controls">
        <button type="button" class="btn-tree-custom" onclick="stepTree(-1)" title="Згорнути рівень">
            <img src="/img/arrow-up.svg" width="16" height="16" alt="Згорнути">
        </button>
        <button type="button" class="btn-tree-custom" onclick="stepTree(1)" title="Розгорнути рівень">
            <img src="/img/arrow-down.svg" width="16" height="16" alt="Розгорнути">
        </button>
    </div>
    <?php endif; ?>
</div>

<div class="table-container">
    <?php if ($isBlocked): ?>
        <div class="blocking-notice" style="padding: 50px 20px; text-align: center; background: #fff; border-radius: 12px; border: 1px solid #eee; margin: 20px 0;">
            <div style="margin-bottom: 15px;">
                <img src="/img/exclamation-circle_red.svg" width="60" height="60" alt="Увага">
            </div>
            <h4 style="color: #d9534f; font-size: 1.4em; margin-bottom: 10px;">Прийом показників призупинено</h4>
            <p style="color: #666; font-size: 1.1em; line-height: 1.6;">
                Згідно з графіком підприємства, прийом показників здійснюється у період з <b><?= $startAccepting ?>-го</b> по <b><?= $endAccepting ?>-те</b> число. <br>
                Наступний період прийому розпочнеться <b><?= $startAccepting ?>-го</b> числа. Дякуємо за розуміння!
            </p>
        </div>
    <?php else: ?>
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
                        <tr class="parent-row open" onclick="toggleTree(this, '<?= $cId ?>')">
                            <td><?= $caret_icon ?> Договір <?= htmlspecialchars($contractName) ?></td>
                            <td></td><td></td>
                            <td><span id="sum_contract_<?= $c_idx ?>" class="font-bold">0,000</span></td>
                        </tr>
                        <?php foreach ($contractData['addresses'] as $addrName => $meters): 
                            $a_idx++; $aId = $cId . "_a_" . $a_idx; ?>
                            <tr class="child-row show <?= $cId ?> sub-parent open" onclick="toggleTree(this, '<?= $aId ?>')">
                                <td><?= $caret_icon ?> <?= htmlspecialchars($addrName) ?></td>
                                <td></td><td></td>
                                <td><span id="sum_address_<?= $a_idx ?>" class="sub-total-val contract-group-<?= $c_idx ?> font-bold text-muted">0,000</span></td>
                            </tr>
                            <?php foreach ($meters as $meter):
                                $inputId = "reading_" . $meter['CounterID'];
                                $diffId = "diff_" . $meter['CounterID'];
                                $rawPrevVal = number_format($meter['LastVal'], 3, '.', '');
                                $curVal = $meter['CurrentVal'];
                                $hasCurrent = ($curVal !== null); ?>
                                <tr class="child-row show <?= $aId ?> detail-row">
                                    <td><span class="bullet-icon">•</span> <?= htmlspecialchars($meter['CounterName']) ?></td>
                                    <td><?= number_format($meter['LastVal'], 3, ',', ' ') ?></td>
                                    <td>
                                        <div class="input-wrapper">
                                            <input type="text" inputmode="decimal" id="<?= $inputId ?>" 
                                                   class="input-reading address-group-<?= $a_idx ?> <?= $hasCurrent ? 'warning' : '' ?>" 
                                                   placeholder="0.000" autocomplete="off"
                                                   value="<?= $hasCurrent ? number_format($curVal, 3, '.', '') : '' ?>" 
                                                   data-prev="<?= $rawPrevVal ?>"
                                                   data-max-vol="<?= $maxVolLimit ?>"
                                                   data-warning-vol="<?= $warningVolLimit ?>"
                                                   data-account="<?= $meter['ID_REF_ACCOUNT'] ?>"
                                                   data-service="<?= $meter['ID_REF_SERVICE'] ?>"
                                                   data-counter="<?= $meter['ID_REF_COUNTER'] ?>"
                                                   data-contract-name="<?= htmlspecialchars($contractName) ?>"
                                                   data-address-name="<?= htmlspecialchars($addrName) ?>"
                                                   data-counter-name="<?= htmlspecialchars($meter['CounterName']) ?>"
                                                   onblur="formatOnBlur(this)"
                                                   oninput="handleMeterInput(this, <?= $rawPrevVal ?>, '<?= $diffId ?>', <?= $a_idx ?>, <?= $c_idx ?>)">
                                            
                                            <div class="error-icon" <?= $hasCurrent ? 'style="display:flex; opacity:1; visibility:visible; background-color:#f39c12;"' : '' ?>>
                                                <?= $hasCurrent ? 'i' : '!' ?>
                                                <span class="error-tooltip"><?= $hasCurrent ? 'Показник за поточний місяць вже передано' : 'Помилка' ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span id="<?= $diffId ?>" class="diff-val"><?= $hasCurrent ? number_format($curVal - $meter['LastVal'], 3, ',', ' ') : '0,000' ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php if (!empty($meters_data) && !$isBlocked): ?>
<div class="bottom-controls">
    <button class="btn-save" onclick="saveReadings()">Зберегти показники</button>
</div>
<?php endif; ?>