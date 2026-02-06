<?php
session_start();
include "config.php";

// 1. ПІДКЛЮЧЕННЯ ДО БД
$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');



// 2. ОБРОБКА POST-ЗАПИТУ (Зміна контрагента)
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
    
    // ЛОГІКА ВИБОРУ КОНТРАГЕНТА
    // 1. Якщо користувач вже вибрав (є в сесії) і це поточний рядок -> це активний
    // 2. АБО якщо в сесії ще нічого немає (перший вхід) і це найперший рядок -> це активний
    if (($selectedId && $s_row['ID'] == $selectedId) || (!$selectedId && $activeRow === null)) {
        $activeRow = $s_row;
    }
}

// Якщо ми знайшли активного (або вибрали першого автоматично), 
// треба записати його в сесію, щоб інші сторінки його бачили.
if ($activeRow) {
    $_SESSION['selected_counteragent_id'] = $activeRow['ID'];
}


// 5. ПІДКЛЮЧЕННЯ ШАПКИ
$title = 'Особистий кабінет';
// ВИПРАВЛЕНО: Об'єднано стилі в один масив
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

    <div class="header-right">
  <div class="header-center">
   <div class="balance <?php echo $balanceClass; ?>"><?php echo $balance; ?> грн</div>
    <div class="balance-sub">До сплати станом на <br><span><?php echo $lastDateFormatted; ?></span></div>
  </div>
</div>
</header>

<div class="overlay" id="overlay"></div>

<div class="layout">
  <aside class="sidebar" id="sidebar">
    <nav>
        <h4>ГОЛОВНА</h4>
        <a class="active" href="#">Підприємства</a>
        <a href="#">Адреси</a>
        <a href="#">Розрахунки за послуги</a>
        <a href="#">Передача показників</a>
        <a href="#">Історія показників</a>

        <h4>МОЇ ДАНІ</h4>
        <a href="#">Профіль</a>
        <a href="#">Налаштування</a>
        
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


<script>
const userSelect = document.querySelector('.user-select');
const btn = document.getElementById('userSelectBtn');
const dropdown = document.getElementById('userSelectDropdown');
const textBox = btn.querySelector('.u-text');

// Відкриття/закриття списку
btn.addEventListener('click', () => userSelect.classList.toggle('open'));

// Вибір контрагента
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
        console.log('Контрагент змінений:', id);
        
        // Оновлюємо контент в таблицях
        if (typeof window.refreshActiveContent === 'function') {
            window.refreshActiveContent();
        }

        // Оновлюємо баланс та дату в шапці
        if (typeof updateHeaderBalance === 'function') {
            updateHeaderBalance();
        }
    });
});

document.addEventListener('click', e => {
    if (!userSelect.contains(e.target)) userSelect.classList.remove('open');
});

<?php $activeMenu = $_SESSION['active_menu'] ?? 'Підприємства'; ?>
const activeMenu = '<?php echo $activeMenu; ?>';
document.querySelectorAll('.sidebar a').forEach(a => {
    a.classList.toggle('active', a.innerText.trim() === activeMenu);
});
</script>

</body>
</html>