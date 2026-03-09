<?php
function getHistoryData($link, $counteragentId, $year, $orgId) {
    // ДОДАНО: res.ID_ENUM_GROUP_TYPE_SERVICE AS service_group
    $sql = "SELECT 
                rc.NAME AS contract_num, 
                ems.PERIOD, 
                res.NAME AS service_name, 
                res.ID_ENUM_GROUP_TYPE_SERVICE AS service_group, 
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

        if (!isset($tree[$cName])) {
            $tree[$cName] = [
                'total' => ['beg' => 0, 'vol' => 0, 'acc' => 0, 'recalc' => 0, 'paid' => 0, 'end' => 0, 'first_m' => 13, 'last_m' => 0],
                'months' => []
            ];
        }

        if (!isset($tree[$cName]['months'][$mName])) {
            $tree[$cName]['months'][$mName] = [
                'beg' => 0, 'vol' => 0, 'acc' => 0, 'recalc' => 0, 'paid' => 0, 'end' => 0, 
                'details' => [], 'm_num' => $mNum
            ];
        }

        // --- ЛОГІКА ДЛЯ КУБІВ ---
        // Перевіряємо, чи це послуга, яка має об'єм (група 1 - водопостачання/водовідведення)
        $isVolumeService = ($row['service_group'] == 1);
        $volValue = (float)$row['ACCRUAL_VOL'];

        $service = [
            'name'   => $row['service_name'],
            'beg'    => (float)$row['BEG_DEBT'],
            'vol'    => $isVolumeService ? $volValue : null, // Якщо абонплата, передаємо null
            'acc'    => (float)$row['ACCRUAL_SUM'],
            'recalc' => (float)$row['RECALC_SUM'],
            'paid'   => (float)$row['PAY_SUM'],
            'end'    => (float)$row['END_DEBT']
        ];
        
        $tree[$cName]['months'][$mName]['details'][] = $service;

        // --- РОЗРАХУНОК МІСЯЦЯ ---
        // Об'єм додаємо до загальної суми ТІЛЬКИ для води та стоків!
        if ($isVolumeService) {
            $tree[$cName]['months'][$mName]['vol'] += $volValue;
            $tree[$cName]['total']['vol']          += $volValue;
        }

        $tree[$cName]['months'][$mName]['acc']    += $service['acc'];
        $tree[$cName]['months'][$mName]['recalc'] += $service['recalc'];
        $tree[$cName]['months'][$mName]['paid']   += $service['paid'];
        $tree[$cName]['months'][$mName]['beg']    += $service['beg'];
        $tree[$cName]['months'][$mName]['end']    += $service['end'];

        // --- РОЗРАХУНОК ДОГОВОРУ (Підсумки) ---
        $tree[$cName]['total']['acc']    += $service['acc'];
        $tree[$cName]['total']['recalc'] += $service['recalc'];
        $tree[$cName]['total']['paid']   += $service['paid'];

        if ($mNum < $tree[$cName]['total']['first_m']) {
            $tree[$cName]['total']['first_m'] = $mNum;
        }
        if ($mNum > $tree[$cName]['total']['last_m']) {
            $tree[$cName]['total']['last_m'] = $mNum;
        }
    }

    foreach ($tree as $cKey => $cVal) {
        if (!empty($cVal['months'])) {
            $availableMonths = array_keys($cVal['months']);
            $latestMonthName = $availableMonths[0]; 
            $earliestMonthName = end($availableMonths); 

            $tree[$cKey]['total']['beg'] = $cVal['months'][$earliestMonthName]['beg'];
            $tree[$cKey]['total']['end'] = $cVal['months'][$latestMonthName]['end'];
        }
    }

    return $tree;
}
?>