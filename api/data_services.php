<?php
function getHistoryData($link, $counteragentId, $year, $orgId) {
    $sql = "SELECT 
                rc.NAME AS contract_num, 
                ems.PERIOD, 
                res.NAME AS service_name, 
                ems.BEG_DEBT,
                ems.ACCRUAL_VOL, 
                ems.ACCRUAL_SUM, 
                ems.RECALC_SUM, 
                ems.PAY_SUM, 
                ems.END_DEBT
            FROM ENT_MUTUAL_SETTLEMENTS ems
            INNER JOIN REF_CONTRACT rc ON ems.ID_REF_CONTRACT = rc.ID
            LEFT JOIN ENUM_TYPE_SERVICE res ON ems.ID_ENUM_TYPE_SERVICE = res.ID
            WHERE ems.ID_ORGANIZATIONS = ? 
              AND ems.ID_REF_COUNTERAGENT = ? 
              AND YEAR(ems.PERIOD) = ?
            ORDER BY ems.PERIOD DESC, ems.ID_REF_CONTRACT ASC, ems.ID_ENUM_TYPE_SERVICE ASC";

    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "iii", $orgId, $counteragentId, $year);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $tree = [];
    $ukrMonths = [1=>'Січень', 2=>'Лютий', 3=>'Березень', 4=>'Квітень', 5=>'Травень', 6=>'Червень', 
                  7=>'Липень', 8=>'Серпень', 9=>'Вересень', 10=>'Жовтень', 11=>'Листопад', 12=>'Грудень'];

    while ($row = mysqli_fetch_assoc($result)) {
        $cName = "Договір " . $row['contract_num'];
        $mNum = (int)date('m', strtotime($row['PERIOD']));
        $mName = $ukrMonths[$mNum];

        // Ініціалізація структури Договору
        if (!isset($tree[$cName])) {
            $tree[$cName] = [
                'total' => ['beg' => 0, 'vol' => 0, 'acc' => 0, 'recalc' => 0, 'paid' => 0, 'end' => 0, 'first_m' => 13, 'last_m' => 0],
                'months' => []
            ];
        }

        // Ініціалізація структури Місяця
        if (!isset($tree[$cName]['months'][$mName])) {
            $tree[$cName]['months'][$mName] = [
                'beg' => 0, 'vol' => 0, 'acc' => 0, 'recalc' => 0, 'paid' => 0, 'end' => 0, 
                'details' => [], 'm_num' => $mNum
            ];
        }

        // Дані окремої послуги
        $service = [
            'name'   => $row['service_name'],
            'beg'    => (float)$row['BEG_DEBT'],
            'vol'    => (float)$row['ACCRUAL_VOL'],
            'acc'    => (float)$row['ACCRUAL_SUM'],
            'recalc' => (float)$row['RECALC_SUM'],
            'paid'   => (float)$row['PAY_SUM'],
            'end'    => (float)$row['END_DEBT']
        ];
        
        $tree[$cName]['months'][$mName]['details'][] = $service;

        // --- РОЗРАХУНОК МІСЯЦЯ ---
        // Сумуємо все, крім сальдо
        $tree[$cName]['months'][$mName]['vol']    += $service['vol'];
        $tree[$cName]['months'][$mName]['acc']    += $service['acc'];
        $tree[$cName]['months'][$mName]['recalc'] += $service['recalc'];
        $tree[$cName]['months'][$mName]['paid']   += $service['paid'];
        
        // Початкове сальдо місяця = сума BEG_DEBT всіх послуг цього місяця (бо вони паралельні)
        $tree[$cName]['months'][$mName]['beg']    += $service['beg'];
        // Кінцеве сальдо місяця = сума END_DEBT всіх послуг цього місяця
        $tree[$cName]['months'][$mName]['end']    += $service['end'];

        // --- РОЗРАХУНОК ДОГОВОРУ (Підсумки) ---
        $tree[$cName]['total']['vol']    += $service['vol'];
        $tree[$cName]['total']['acc']    += $service['acc'];
        $tree[$cName]['total']['recalc'] += $service['recalc'];
        $tree[$cName]['total']['paid']   += $service['paid'];

        // Визначаємо початкове сальдо договору (сума beg самого першого місяця року)
        if ($mNum < $tree[$cName]['total']['first_m']) {
            $tree[$cName]['total']['first_m'] = $mNum;
            // Це тимчасово, перерахуємо фінально після циклу
        }
        // Визначаємо кінцеве сальдо договору (сума end самого останнього місяця року)
        if ($mNum > $tree[$cName]['total']['last_m']) {
            $tree[$cName]['total']['last_m'] = $mNum;
        }
    }

    // Фінальна корекція сальдо договору (беремо суми початкового місяця та кінцевого)
    foreach ($tree as $cKey => $cVal) {
        // Перевіряємо, чи є взагалі дані в місяцях для цього договору
        if (!empty($cVal['months'])) {
            // Отримуємо назви всіх місяців, які є в масиві
            $availableMonths = array_keys($cVal['months']);

            // Оскільки ORDER BY PERIOD DESC, перший елемент масиву — це останній місяць року (Грудень)
            // останній елемент масиву — це перший місяць року (Січень/червень тощо)
            $latestMonthName = $availableMonths[0]; 
            $earliestMonthName = end($availableMonths); 

            // Початкове сальдо всього договору — це старт найдавнішого місяця
            $tree[$cKey]['total']['beg'] = $cVal['months'][$earliestMonthName]['beg'];

            // Кінцеве сальдо всього договору — це фініш найновішого місяця
            $tree[$cKey]['total']['end'] = $cVal['months'][$latestMonthName]['end'];
        }
    }

    return $tree;
}
?>