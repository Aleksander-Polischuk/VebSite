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

$SQLExec = "
    SELECT 
        RC.ID,
        RC.`NAME`,
        RC.EDRPOU
    FROM ACCESS AS A
    
    INNER JOIN REF_COUNTERAGENT AS RC 
    ON (RC.ID = A.ID_REF_COUNTERAGENT AND 
        RC.ID_ORGANIZATIONS = A.ID_ORGANIZATIONS)
    
    WHERE (A.DEL = 0 OR A.DEL IS NULL) 
      AND A.ID_USERS = $userId 
      AND A.ID_ORGANIZATIONS = $orgId";

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

if ($activeRow === null && count($rows) > 0) {
    $activeRow = $rows[0];
}

if ($activeRow) {
    $_SESSION['selected_counteragent_id'] = $activeRow['ID'];
}

// ПІДКЛЮЧЕННЯ ШАПКИ
$title = 'Особистий кабінет';
$list_css = ['/css/table_style.css', '/css/cabinet_ent.css', '/css/CustomAlert.css'];
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
      
      <?php if (!empty($rows)): ?>
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
      <?php else: ?>
          <div class="blocking-notice-title" style="margin-left: 15px; margin-bottom: 0;">У вас немає доступних підприємств</div>
      <?php endif; ?>
    </div>
    
    <?php if (!empty($rows)): ?>
        <div class="header-right">
          <div class="header-center">
           <div class="balance <?php echo $balanceClass ?? ''; ?>"><?php echo $balance ?? '0.00'; ?> грн</div>
            <div class="balance-sub">До сплати станом на <br><span><?php echo $lastDateFormatted ?? date('d.m.Y'); ?></span></div>
          </div>
        </div>
    <?php endif; ?>
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
        <a href="#" id="nav-docs">Документи</a>
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

<script src="/js/CustomAlert.js?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/CustomAlert.js'); ?>"></script>
<script src="/js/cabinet_ent.js?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/cabinet_ent.js'); ?>"></script>
<script src="/js/table_tree.js?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/table_tree.js'); ?>"></script>
<script src="/js/personal_acc.js?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/personal_acc.js'); ?>"></script>
<script src="/js/input_meters.js?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/input_meters.js'); ?>"></script>
<script src="/js/history_readings.js?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/history_readings.js'); ?>"></script>
<script src="/js/feedback.js?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/feedback.js'); ?>"></script>
<script src="/js/Popular_Questions.js?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/Popular_Questions.js'); ?>"></script>
<script src="/js/ent_invoice.js?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/ent_invoice.js'); ?>"></script>

<link href="/css/quill.snow.css?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/css/quill.snow.css'); ?>" rel="stylesheet">
<script src="/js/libs/PDFmake.js?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/libs/PDFmake.js'); ?>" type="text/javascript"></script>
<script src="/js/libs/vfs_fonts.js?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/libs/vfs_fonts.js'); ?>" type="text/javascript"></script>
<script src="/js/libs/quill.js?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/libs/quill.js'); ?>" type="text/javascript"></script>


<script>
//Кількість непідписаних документів    
window.updateDocumentBadge = function() {
    const docLink = document.getElementById('nav-docs');
    if (!docLink) return;

    fetch('/api/get_unsigned_count.php')
    .then(res => res.json())
    .then(data => {
        let badge = docLink.querySelector('.menu-badge');
        if (data.count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'menu-badge';
                badge.title = "Кількість непідписаних документів"; 
                docLink.appendChild(badge);
            }
            badge.innerText = data.count;
        } else if (badge) {
            badge.remove(); 
        }
    })
    .catch(err => console.error("Помилка бейджика:", err));
};

// Викликаємо функцію одразу при завантаженні сторінки
window.updateDocumentBadge();    
    
// 1. Пріоритет віддаємо збереженій вкладці в браузері, якщо її немає — беремо з сесії PHP
<?php $defaultMenu = $_SESSION['active_menu'] ?? 'Підприємства'; ?>
const activeMenu = localStorage.getItem('activeCabinetPage') || <?php echo json_encode($defaultMenu); ?>; 

const userSelect = document.querySelector('.user-select');
const btn = document.getElementById('userSelectBtn');
const dropdown = document.getElementById('userSelectDropdown');

const textBox = btn ? btn.querySelector('.u-text') : null; 

// Функція для підсвітки активного пункту
function highlightActiveMenu(pageName) {
    document.querySelectorAll('.sidebar a').forEach(a => {
        a.classList.toggle('active', a.childNodes[0].textContent.trim() === pageName);
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
        if (textBox) {
            textBox.innerHTML = `ЄДРПОУ: ${edrpou} <span>${name}</span>`;
        }
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
            if (typeof window.updateDocumentBadge === 'function') {
                window.updateDocumentBadge();
            }
        });
    });
}

document.addEventListener('click', e => {
    if (userSelect && !userSelect.contains(e.target)) userSelect.classList.remove('open');
});

document.querySelectorAll('.sidebar a').forEach(link => {
    link.addEventListener('click', function(e) {
        if (this.classList.contains('btn-logout')) return;
        
        const pageName = this.childNodes[0].textContent.trim(); 
        
        localStorage.setItem('activeCabinetPage', pageName);
        highlightActiveMenu(pageName);
    });
});

// Глобальний обробник переходу по внутрішніх вкладках та посиланнях
document.addEventListener('click', function(e) {
    const link = e.target.closest('.quill-content a, .notice-text a');
    if (!link) return; 

    let href = link.getAttribute('href');
    if (!href) return;

    let rawTarget = '';
    try {
        rawTarget = decodeURIComponent(href).toLowerCase();
    } catch(err) {
        rawTarget = href.toLowerCase();
    }
    
    // Якщо це зовнішнє посилання АБО файл, дозволяємо браузеру просто відкрити його
    if (href.startsWith('http') || href.startsWith('mailto') || href.includes('/uploads/') || href.match(/\.(pdf|doc|docx|xls|xlsx|csv|png|jpg|zip|rar)$/i)) {
        link.setAttribute('target', '_blank'); // Відкрити в новому вікні
        return; 
    }
    
    // Очистка посилання від зайвих символів
    let cleanTarget = rawTarget.replace(/^https?:\/\//, '').replace(/^#|^\//, '').trim();
    
    // Пошук вкладки в лівому меню
    const sidebarLinks = document.querySelectorAll('.sidebar a');
    let foundMatch = false;

    for (let sidebarLink of sidebarLinks) {
        let linkText = sidebarLink.innerText.trim().toLowerCase();
        
        // Якщо назва вкладки збігається з посиланням
        if (cleanTarget.includes(linkText) || (cleanTarget.length > 3 && linkText.includes(cleanTarget))) {
            e.preventDefault(); 
            
            sidebarLink.click(); 
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            foundMatch = true;
            break; 
        }
    }

    // Якщо це якесь невідоме посилання, відкриваємо в новій вкладці
    if (!foundMatch) {
        link.setAttribute('target', '_blank'); 
    }
});
</script>

</body>
</html>