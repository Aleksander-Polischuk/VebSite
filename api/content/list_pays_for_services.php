<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "config.php";
require_once __DIR__ . "/../data_services.php"; 

$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

if (!function_exists('getHistoryData')) {
    die("Помилка: Функція getHistoryData не знайдена у файлі data_services.php");
}

$selectedCounteragentId = $_SESSION['selected_counteragent_id'] ?? null;
$orgId = (int)($IDOrganizations ?? 1);

if (!$selectedCounteragentId) {
    echo "<div class='error-msg-padding text-negative'>Будь ласка, оберіть підприємство у верхній панелі.</div>";
    exit;
}

// ОТРИМАННЯ ДОСТУПНИХ РОКІВ
$years = [];
$sqlYears = "SELECT DISTINCT YEAR(PERIOD) as y FROM ENT_MUTUAL_SETTLEMENTS 
             WHERE ID_ORGANIZATIONS = ? AND ID_REF_COUNTERAGENT = ? ORDER BY y DESC";
$stmtY = mysqli_prepare($link, $sqlYears);
mysqli_stmt_bind_param($stmtY, "ii", $orgId, $selectedCounteragentId);
mysqli_stmt_execute($stmtY);
$resY = mysqli_stmt_get_result($stmtY);
while($rowY = mysqli_fetch_assoc($resY)) {
    if (!empty($rowY['y'])) $years[] = $rowY['y'];
}
if (empty($years)) $years[] = date('Y');

$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : $years[0];
$pays_data = getHistoryData($link, $selectedCounteragentId, $selectedYear, $orgId);
$caret_icon = '<img src="/img/caret-down-fill.svg" class="tree-icon icon-no-pointer" width="16" height="16" alt="">';

/**
 * Функція для форматування валюти
 */
function formatCurrency($value) {
    $formatted = number_format($value, 2, ',', ' ');
    $class = ($value < 0) ? 'class="text-negative"' : '';
    return "<span $class>$formatted</span>";
}
?>

<link href="../../css/list_pays_for_services.css" rel="stylesheet" type="text/css"/>

<div class="table-header-row sticky-header history-pays-header">
    <h3>Розрахунки за послуги</h3>

    <div class="header-controls">
        <button type="button" class="btn-tree-custom" onclick="stepTree(-1)" title="Згорнути рівень">
            <img src="/img/arrow-up.svg" width="16" height="16" alt="">
        </button>
        <button type="button" class="btn-tree-custom" onclick="stepTree(1)" title="Розгорнути рівень">
            <img src="/img/arrow-down.svg" width="16" height="16" alt="">
        </button>

        <select id="yearSelect" class="year-select-custom" onchange="changeYear(this.value)">
            <?php foreach($years as $y): ?>
                <option value="<?php echo $y; ?>" <?php echo ($y == $selectedYear) ? 'selected' : ''; ?>>
                    <?php echo $y; ?> рік
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="table-container">
    <table class="data-table tree-table shadow-table pays-history-table">
    <thead>
        <tr>
            <th>Період</th>
            <th>Початкове сальдо</th>
            <th>Нараховано, куб. м</th>
            <th>Нараховано, грн</th>
            <th>Перерахунок, грн</th>
            <th>Оплачено, грн</th>
            <th>Кінцеве сальдо</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($pays_data)): ?>
            <tr>
                <td colspan="7" class="cell-no-data">
                    Немає даних про розрахунки за <?php echo $selectedYear; ?> рік.
                </td>
            </tr>
        <?php else: 
            $c_idx = 0;
            foreach ($pays_data as $contractName => $data): 
                $c_idx++; $cId = "c_" . $c_idx; ?>
                <tr class="parent-row open" onclick="toggleTree(this, '<?php echo $cId; ?>')">
                    <td><?php echo $caret_icon; ?> <?php echo htmlspecialchars($contractName); ?></td>
                    <td>---</td> 
                    <td><?php echo number_format($data['total']['vol'], 3, ',', ' '); ?></td>
                    <td><?php echo formatCurrency($data['total']['acc']); ?></td>
                    <td><?php echo formatCurrency($data['total']['recalc']); ?></td> 
                    <td><?php echo formatCurrency($data['total']['paid']); ?></td>
                    <td>---</td>
                </tr>

                <?php 
                $m_idx = 0;
                foreach ($data['months'] as $mName => $m): 
                    $m_idx++; $mId = $cId . "_m" . $m_idx; ?>
                    <tr class="child-row show <?php echo $cId; ?> sub-parent" onclick="toggleTree(this, '<?php echo $mId; ?>')">
                        <td><?php echo $caret_icon; ?> <?php echo htmlspecialchars($mName); ?></td>
                        <td><?php echo formatCurrency($m['beg']); ?></td>
                        <td><?php echo number_format($m['vol'], 3, ',', ' '); ?></td>
                        <td><?php echo formatCurrency($m['acc']); ?></td>
                        <td><?php echo formatCurrency($m['recalc']); ?></td>
                        <td><?php echo formatCurrency($m['paid']); ?></td>
                        <td><?php echo formatCurrency($m['end']); ?></td>
                    </tr>

                    <?php foreach ($m['details'] as $service): ?>
                        <tr class="child-row <?php echo $mId; ?> detail-row">
                            <td><span class="bullet-icon">•</span> <?php echo htmlspecialchars($service['name']); ?></td>
                            <td data-label="Початкове сальдо:"><?php echo formatCurrency($service['beg']); ?></td>
                            <td data-label="Нараховано, куб. м:"><?php echo number_format($service['vol'], 3, ',', ' '); ?></td>
                            <td data-label="Нараховано, грн:"><?php echo formatCurrency($service['acc']); ?></td>
                            <td data-label="Перерахунок, грн:"><?php echo formatCurrency($service['recalc']); ?></td>
                            <td data-label="Оплачено, грн:"><?php echo formatCurrency($service['paid']); ?></td>
                            <td data-label="Кінцеве сальдо:"><?php echo formatCurrency($service['end']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
    </table>
</div>

<button id="scrollToTopBtn" title="Нагору">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
        <path fill-rule="evenodd" d="M8 12a.5.5 0 0 0 .5-.5V5.707l2.146 2.147a.5.5 0 0 0 .708-.708l-3-3a.5.5 0 0 0-.708 0l-3 3a.5.5 0 1 0 .708.708L7.5 5.707V11.5a.5.5 0 0 0 .5.5z"/>
    </svg>
</button>