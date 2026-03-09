<?php
session_start();
include "config.php";

// 1. ПІДКЛЮЧЕННЯ ДО БД
$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');



// 2. Зміна контрагента
$post_counteragent_id = filter_input(INPUT_POST, 'set_counteragent_id', FILTER_VALIDATE_INT);
if ($post_counteragent_id !== null && $post_counteragent_id !== false) {
    $_SESSION['selected_counteragent_id'] = $post_counteragent_id;
    exit; 
}

// 3. ПЕРЕВІРКА АВТОРИЗАЦІЇ
if (!isset($_SESSION['id_users']) || $_SESSION['id_users'] === '') {
    session_destroy();
    header('Location: /login?account_type=1');
    exit;
}

// 4. ПІДГОТОВКА ДАНИХ
$selectedId = $_SESSION['selected_counteragent_id'] ?? null;
$userId = (int)$_SESSION['id_users'];
$orgId = (int)$IDOrganizations;


// --- Перевірка прав адміністратора ---
$isAdmin = false;
$admin_ids = [5]; //ID адмінів

if (in_array($userId, $admin_ids)) {
    $isAdmin = true;
}

$SQLExec = "
    SELECT 
        RC.ID,
        RC.`NAME`,
        RC.EDRPOU
    FROM ACCESS AS A
    
    INNER JOIN REF_COUNTERAGENT AS RC 
    ON (RC.ID = A.ID_REF_COUNTERAGENT AND 
        RC.ID_ORGANIZATIONS = A.ID_ORGANIZATIONS)
    
    WHERE A.ID_USERS = $userId AND A.ID_ORGANIZATIONS = $orgId";

$s_res = mysqli_query($link, $SQLExec);

$rows = [];
$activeRow = null;

// Визначаємо активного контрагента
while ($s_row = mysqli_fetch_assoc($s_res)) {
    $rows[] = $s_row;
    
    if (($selectedId && $s_row['ID'] == $selectedId) || (!$selectedId && $activeRow === null)) {
        $activeRow = $s_row;
    }
}

// Якщо ми знайшли активного 
if ($activeRow) {
    $_SESSION['selected_counteragent_id'] = $activeRow['ID'];
}


// 5. ПІДКЛЮЧЕННЯ ШАПКИ
$title = 'Особистий кабінет';
$list_css = ['/css/cabinet_ent.css', '/css/CustomAlert.css'];
include "page_head.php";
include "CustomAlert.php";
?>




<header class="header">
  <div class="header-main-row">
    <div class="header-left">
      <button class="burger" id="burgerBtn">☰</button>
      <div class="logo">
          <img id="logoIco" src="/img/logo.png"> 
      </div>
      
      <div class="user-select">
        <div class="user-select-box" id="userSelectBtn">
          <span class="u-icon">👤</span>
          <div class="u-text">
            <?php if ($activeRow): ?>
              ЄДРПОУ: <?php echo $activeRow['EDRPOU']; ?> <span><?php echo htmlspecialchars($activeRow['NAME']); ?></span>
            <?php endif; ?>
          </div>
          <span class="u-arrow">▼</span>
        </div>
        <div class="user-select-dropdown" id="userSelectDropdown">      
          <?php foreach ($rows as $row): ?>
            <div class="item" 
                 data-id="<?php echo $row['ID']; ?>" 
                 data-edrpou="<?php echo $row['EDRPOU']; ?>" 
                 data-name='<?php echo htmlspecialchars($row['NAME'], ENT_QUOTES); ?>'>
                <span><?php echo htmlspecialchars($row['NAME']); ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <!--
    <?php if ($isAdmin): ?>
    
    <div class="header-admin">
        <a href="/admin/admin_faq_add.php" title="Адміністративна панель" onclick="openAdminPanel(event, this.href)">
            <img src="/img/gear.svg" alt="Налаштування" class="admin-gear-icon">
        </a>
    </div>
    
    <?php endif; ?>
    
    -->
    <div class="header-right">
      <div class="header-center">
       <div class="balance <?php echo $balanceClass; ?>"><?php echo $balance; ?> грн</div>
        <div class="balance-sub">До сплати станом на <br><span><?php echo $lastDateFormatted; ?></span></div>
      </div>
    </div>
  </div> 
</header>

<div class="overlay" id="overlay"></div>

<div class="layout">
  <aside class="sidebar" id="sidebar">
    <nav>
        <h4>ГОЛОВНА</h4>
        <a class="active" href="#">Підприємства</a>
        <a href="#">Лічильники</a>
        <a href="#">Розрахунки за послуги</a>
        <a href="#">Передача показників</a>
        <a href="#">Історія показників</a>
       <!-- <a href="#">Історія показників_2</a> -->
        <a href="#">Рахунки</a>
        <a href="#">Тарифи</a>
        <a href="#">Поширені запитання</a>

        <h4>МОЇ ДАНІ</h4>
        <a href="#">Профіль</a>
        <a href="#">Зворотній зв'язок</a>
        
        <a href="/logout?account_type=1" class="btn-logout">Вихід</a>
    </nav>
</aside>

  <main class="content">
    <div class="content-box" id="mainContent">
      <span>Завантаження контенту...</span>
    </div>
  </main>
</div>

<script src="/js/CustomAlert.js"></script>
<script src="/js/cabinet_ent.js"></script>
<script src="/js/table_tree.js"></script>
<script src="js/personal_acc.js"></script>
<script src="js/input_meters.js"></script>
<script src="js/history_readings_2.js"></script>
<script src="js/feedback.js"></script>
<script src="js/Popular_Questions.js"></script>

<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<script>
// 1. Пріоритет віддаємо збереженій вкладці в браузері, якщо її немає — беремо з сесії PHP
<?php $defaultMenu = $_SESSION['active_menu'] ?? 'Підприємства'; ?>
const activeMenu = localStorage.getItem('activeCabinetPage') || <?php echo json_encode($defaultMenu); ?>; 

const userSelect = document.querySelector('.user-select');
const btn = document.getElementById('userSelectBtn');
const dropdown = document.getElementById('userSelectDropdown');
const textBox = btn.querySelector('.u-text');

// Функція для підсвітки активного пункту
function highlightActiveMenu(pageName) {
    document.querySelectorAll('.sidebar a').forEach(a => {
        a.classList.toggle('active', a.innerText.trim() === pageName);
    });
}

// Підсвічуємо вкладку одразу при завантаженні скрипта
highlightActiveMenu(activeMenu);

if (btn) {
    btn.addEventListener('click', () => userSelect.classList.toggle('open'));
}

if (dropdown) {
    dropdown.addEventListener('click', e => {
        const item = e.target.closest('.item');
        if (!item) return;

        const { id, edrpou, name } = item.dataset;
        textBox.innerHTML = `ЄДРПОУ: ${edrpou} <span>${name}</span>`;
        userSelect.classList.remove('open');

        const formData = new FormData();
        formData.append('set_counteragent_id', id);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(() => {
            if (typeof window.refreshActiveContent === 'function') {
                window.refreshActiveContent();
            }
            if (typeof updateHeaderBalance === 'function') {
                updateHeaderBalance();
            }
        });
    });
}

document.addEventListener('click', e => {
    if (userSelect && !userSelect.contains(e.target)) userSelect.classList.remove('open');
});

// Додаємо обробник для кліків по сайдбару, щоб зберігати вибір
document.querySelectorAll('.sidebar a').forEach(link => {
    link.addEventListener('click', function(e) {
        if (this.classList.contains('btn-logout')) return;
        
        const pageName = this.innerText.trim();
        // Зберігаємо назву сторінки в браузері
        localStorage.setItem('activeCabinetPage', pageName);
        highlightActiveMenu(pageName);
    });
});

function openAdminPanel(event, url) {
    event.preventDefault(); // Зупиняємо стандартний перехід за посиланням

    // 1. Намагаємося отримати посилання на вікно за ім'ям, але НЕ передаємо URL.
    // Це просто знаходить існуючу вкладку, не перевантажуючи її.
    var adminWin = window.open('', 'admin_panel');

    // 2. Перевіряємо, чи це вікно нове (пусте) або чи в ньому завантажена не та сторінка.
    // Якщо воно щойно відкрилося, location.href буде "about:blank".
    try {
        if (adminWin.location.href === 'about:blank') {
            adminWin.location.href = url; // Завантажуємо адмінку тільки якщо вкладка була порожньою
        }
    } catch (e) {
        // Якщо виникла помилка безпеки, просто завантажуємо URL
        adminWin.location.href = url;
    }

    // 3. Переводимо фокус на цю вкладку
    adminWin.focus();
}
</script>

</body>
</html>