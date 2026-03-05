<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../config.php"; 

$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

// === ПЕРЕВІРКА НА АДМІНІСТРАТОРА ===
$admin_ids = [5]; //ID адміна
$userId = $_SESSION['id_users'] ?? 0;

if (!$userId || !in_array($userId, $admin_ids)) {
    header("Location: /login"); 
    exit;
}

// === ВИЗНАЧЕННЯ АДМІНА ОРГАНІЗАЦІЇ ===
$orgId = 0;
$stmt_access = mysqli_prepare($link, "SELECT ID_ORGANIZATIONS FROM ACCESS WHERE ID_USERS = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_access, "i", $userId);
mysqli_stmt_execute($stmt_access);
$res_access = mysqli_stmt_get_result($stmt_access);
if ($row = mysqli_fetch_assoc($res_access)) {
    $orgId = (int)$row['ID_ORGANIZATIONS'];
}
mysqli_stmt_close($stmt_access);

// Якщо в базі для цього адміна не прописана організація — блокуємо сторінку
if ($orgId === 0) {
    die("<h3 style='text-align:center; margin-top:50px; color:#c62828;'>Помилка доступу: Ваш акаунт не прив'язаний до жодної організації. Перевірте таблицю ACCESS.</h3>");
}
// ======================================================================

// Обробка форми (Додавання або Редагування)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'toggle_active') {
        $id = (int)$_POST['faq_id'];
        $is_active = (int)$_POST['is_active'];
        
        // Оновлюємо тільки якщо питання належить організації цього адміна
        $stmt = mysqli_prepare($link, "UPDATE REF_POPULAR_QUESTIONS SET is_active = ? WHERE ID = ? AND ID_ORGANIZATION = ?");
        mysqli_stmt_bind_param($stmt, "iii", $is_active, $id, $orgId);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        echo json_encode(['success' => $success]);
        exit;
    }
    // =======================================
    
    $question = mysqli_real_escape_string($link, trim($_POST['question']));
    $content_data = $_POST['content_data']; 
    $sort_order = (int)$_POST['sort_order'];
    $id = (int)($_POST['faq_id'] ?? 0);

    if (!empty($question) && !empty($content_data)) {
        if ($action === 'add_faq') {
            // Зберігаємо питання від імені організації адміна
            $stmt = mysqli_prepare($link, "INSERT INTO REF_POPULAR_QUESTIONS (question, content_type, content_data, sort_order, is_active, ID_ORGANIZATION) VALUES (?, 'html', ?, ?, 1, ?)");
            mysqli_stmt_bind_param($stmt, "ssii", $question, $content_data, $sort_order, $orgId);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['faq_msg'] = "<div class='alert success'>✅ Нове питання успішно додано!</div>";
            } else {
                $_SESSION['faq_msg'] = "<div class='alert error'>❌ Помилка: " . mysqli_error($link) . "</div>";
            }
            mysqli_stmt_close($stmt);

        } elseif ($action === 'edit_faq' && $id > 0) {
            // Оновлюємо питання тільки якщо воно належить організації адміна
            $stmt = mysqli_prepare($link, "UPDATE REF_POPULAR_QUESTIONS SET question = ?, content_data = ?, sort_order = ?, content_type = 'html' WHERE ID = ? AND ID_ORGANIZATION = ?");
            mysqli_stmt_bind_param($stmt, "ssiii", $question, $content_data, $sort_order, $id, $orgId);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['faq_msg'] = "<div class='alert success'>✅ Питання успішно оновлено!</div>";
            } else {
                $_SESSION['faq_msg'] = "<div class='alert error'>❌ Помилка: " . mysqli_error($link) . "</div>";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $_SESSION['faq_msg'] = "<div class='alert error'>❌ Заповніть заголовок та текст відповіді!</div>";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Витягуємо повідомлення із сесії
$message = '';
if (isset($_SESSION['faq_msg'])) {
    $message = $_SESSION['faq_msg'];
    unset($_SESSION['faq_msg']);
}

// Вивід в таблицю тільки ті питання, які належать до організації поточного адміна
$sql = "SELECT ID, question, content_type, content_data, sort_order, is_active FROM REF_POPULAR_QUESTIONS WHERE ID_ORGANIZATION = $orgId ORDER BY sort_order ASC";
$res = mysqli_query($link, $sql);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Управління FAQ</title>
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <link href="../css/admin_faq_add.css" rel="stylesheet" type="text/css"/>
    <script src="../js/CustomAlert.js" type="text/javascript" defer></script>
    <link href="../css/CustomAlert.css" rel="stylesheet" type="text/css"/>
</head>
<body>
<?php include_once "../CustomAlert.php"; ?>
<div class="admin-container">
    <h2 id="form-title">Додати нове питання в FAQ</h2>
    
    <?php echo $message; ?>

    <form id="faqForm" method="POST">
        <input type="hidden" name="action" id="form-action" value="add_faq">
        <input type="hidden" name="faq_id" id="faq_id" value="0">
        
        <div class="form-group">
            <label>Заголовок питання:</label>
            <input type="text" name="question" id="faq_question" placeholder="Наприклад: Як стати абонентом?" required>
        </div>

        <div class="form-group">
            <label>Порядковий номер (сортування):</label>
            <input type="number" name="sort_order" id="faq_sort_order" value="10" required>
        </div>

        <div class="form-group">
            <label>Текст відповіді:</label>
            <div id="editor-container"></div>
            <input type="hidden" name="content_data" id="content_data">
        </div>

        <button type="submit" class="btn-submit" id="btn-save">Додати питання</button>
        <button type="button" class="btn-cancel" id="btn-cancel" onclick="resetForm()">Скасувати редагування</button>
    </form>
</div>

<div class="admin-container">
    <h2>Існуючі питання</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Сорт.</th>
                <th>Питання</th>
                <th>Формат</th>
                <th>Статус</th>
                <th>Дія</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($res && mysqli_num_rows($res) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($res)): ?>
                    <tr>
                        <td><?php echo $row['ID']; ?></td>
                        <td><?php echo $row['sort_order']; ?></td>
                        <td><strong><?php echo htmlspecialchars($row['question']); ?></strong></td>
                        <td>
                            <span class="badge <?php echo ($row['content_type'] === 'html') ? 'badge-html' : ''; ?>">
                                <?php echo htmlspecialchars($row['content_type']); ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <input type="checkbox" 
                                   style="transform: scale(1.5); cursor: pointer;" 
                                   onchange="toggleActive(<?php echo $row['ID']; ?>, this.checked)" 
                                   <?php echo ($row['is_active'] == 1) ? 'checked' : ''; ?>>
                        </td>
                        <td>
                            <div id="raw_content_<?php echo $row['ID']; ?>" style="display: none;">
                                <?php echo htmlspecialchars($row['content_data']); ?>
                            </div>
                            
                            <button type="button" class="btn-edit" 
                                onclick="editFAQ(
                                    <?php echo $row['ID']; ?>, 
                                    '<?php echo addslashes(htmlspecialchars($row['question'])); ?>', 
                                    <?php echo $row['sort_order']; ?>
                                )">
                                ✏️ Редагувати
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6">Питань поки немає.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>  
    
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script src="../js/admin_faq_add.js" type="text/javascript"></script>
</body>
</html>