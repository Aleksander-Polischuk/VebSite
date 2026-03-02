<?php
// Отримуємо параметр сторінки
$page = isset($_GET['page']) ? $_GET['page'] : '';

session_start();
include "../config.php";

$_SESSION['active_menu'] = $page;

switch ($page) {
    case 'Підприємства':
        include "content/list_enterprise.php";
        break;
    case 'Лічильники':
        include "content/ent_list_accounts.php";
        break;
    case 'Розрахунки за послуги':
        include "content/list_pays_for_services.php";
        break;
    case 'Передача показників':
        include "content/input_meters.php";
        break;
    case 'Історія показників':
        include "content/history_readings_2.php";
        break;
    /*case 'Історія показників_2':
        include "content/history_readings_2.php";
        break;*/
    case 'Рахунки':
        include "content/ent_invoice.php";
        break;
    case 'Профіль':
        echo "<h2>Ваш профіль</h2><p>Ім'я: ФОП АФЧО</p>";
        break;
    default:
        echo "<h2>Помилка</h2><p>Сторінку не знайдено.</p>";
        break;
}
?>