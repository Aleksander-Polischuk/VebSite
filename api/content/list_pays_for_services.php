<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1 ПІДКЛЮЧЕННЯ
include "config.php";
require_once __DIR__ . "/../data_services.php"; 

$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

// Перевірка, чи функція тепер доступна
if (!function_exists('getHistoryData')) {
    die("Помилка: Функція getHistoryData все ще не знайдена у файлі data_services.php");
}
// 2 ВХІДНІ ПАРАМЕТРИ
$selectedCounteragentId = $_SESSION['selected_counteragent_id'] ?? null;
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : 2025;
$orgId = (int)($IDOrganizations ?? 1);

if (!$selectedCounteragentId) {
    echo "<div style='padding:20px; color: #d9534f;'>Будь ласка, оберіть підприємство у верхній панелі для перегляду розрахунків.</div>";
    exit;
}

// SVG іконка згортання/розгортання таблиці
$caret_icon = '<img src="/img/caret-down-fill.svg" class="tree-icon" width="16" height="16" alt="" style="pointer-events: none;">';

// 3 ОТРИМАННЯ ДАНИХ
$pays_data = getHistoryData($link, $selectedCounteragentId, $selectedYear, $orgId);

?>

<link href="../../css/list_pays_for_services.css" rel="stylesheet" type="text/css"/>

<div class="table-header-row sticky-header" id="history-start">
    <h3 style="margin: 0; flex-grow: 1;">Історія розрахунків</h3>

    <div class="header-controls">
        
       <button type="button" class="btn-tree-custom" onclick="stepTree(-1)" title="Згорнути рівень">
            <img src="/img/arrow-up.svg" width="16" height="16" alt="Згорнути" style="pointer-events: none;">
        </button>
        
       <button type="button" class="btn-tree-custom" onclick="stepTree(1)" title="Розгорнути рівень">
            <img src="/img/arrow-down.svg" width="16" height="16" alt="Розгорнути" style="pointer-events: none;">
        </button>

        <select id="yearSelect" class="year-select-custom" onchange="changeYear(this.value)">
            <?php for($y = 2024; $y <= 2026; $y++): ?>
                <option value="<?= $y ?>" <?= ($y == $selectedYear) ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>
</div>

<div class="table-container" id="history-container">
    <table class="data-table tree-table shadow-table" style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr>
            <th style="text-align: left; padding: 12px;">Період</th>
            <th style="text-align:center">Початкове сальдо</th>
            <th style="text-align:center">Нараховано, куб. м</th>
            <th style="text-align:center">Нараховано, грн</th>
            <th style="text-align:center">Перерахунок, грн</th>
            <th style="text-align:center">Оплачено, грн</th>
            <th style="text-align:center">Кінцеве сальдо</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        if (empty($pays_data)): ?>
            <tr>
                <td colspan="6" style="padding: 30px; text-align: center; color: #666;">
                    Немає даних про розрахунки за <?php echo $selectedYear; ?> рік.
                </td>
            </tr>
        <?php
        else:
        $c_idx = 0;
            foreach ($pays_data as $contractName => $data): 
                $c_idx++;
                $cId = "c_" . $c_idx;
            ?>
                <tr class="parent-row open" onclick="toggleTree(this, '<?php echo $cId; ?>')">
                    <td><?php echo $caret_icon; ?> <?php echo htmlspecialchars($contractName); ?></td>
                    <td align="center">---</td> 
                    <td align="center"><?php echo number_format($data['total']['vol'], 3, ',', ' '); ?></td>
                    <td align="center"><?php echo formatCurrency($data['total']['acc']); ?></td>
                    <td align="center"><?php echo formatCurrency($data['total']['recalc']); ?></td> 
                    <td align="center"><?php echo formatCurrency($data['total']['paid']); ?></td>
                    <td align="center">---</td>
                </tr>

                <?php 
                $m_idx = 0;
                foreach ($data['months'] as $mName => $m): 
                    $m_idx++;
                    $mId = $cId . "_m" . $m_idx;
                ?>
                    <tr class="child-row show <?php echo $cId; ?> sub-parent" onclick="toggleTree(this, '<?php echo $mId; ?>')">
                        <td style="padding-left: 30px;"><?php echo $caret_icon; ?> <?php echo htmlspecialchars($mName); ?></td>
                        <td align="center"><?php echo formatCurrency($m['beg']); ?></td>
                        <td align="center"><?php echo number_format($m['vol'], 3, ',', ' '); ?></td>
                        <td align="center"><?php echo formatCurrency($m['acc']); ?></td>
                        <td align="center"><?php echo formatCurrency($m['recalc']); ?></td>
                        <td align="center"><?php echo formatCurrency($m['paid']); ?></td>
                        <td align="center"><?php echo formatCurrency($m['end']); ?></td>
                    </tr>

                    <?php foreach ($m['details'] as $service): ?>
                        <tr class="child-row <?php echo $mId; ?> detail-row">
                            <td style="padding-left: 65px; font-size: 13px; color: #666;">
                                <span style="color: #4a76f2; margin-right: 8px;">•</span> 
                                <?php echo htmlspecialchars($service['name']); ?>
                            </td>
                            <td align="center"><?php echo formatCurrency($service['beg']); ?></td>
                            <td align="center"><?php echo number_format($service['vol'], 3, ',', ' '); ?></td>
                            <td align="center"><?php echo formatCurrency($service['acc']); ?></td>
                            <td align="center"><?php echo formatCurrency($service['recalc']); ?></td>
                            <td align="center"><?php echo formatCurrency($service['paid']); ?></td>
                            <td align="center"><?php echo formatCurrency($service['end']); ?></td>
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

<?php
// Функція для форматування чисел та додавання класу, якщо число від'ємне
function formatCurrency($value) {
    $formatted = number_format($value, 2, ',', ' ');
    $class = ($value < 0) ? 'class="text-negative"' : '';
    return "<span $class>$formatted</span>";
}
?>
