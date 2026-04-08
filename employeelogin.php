<?php
// employeelogin.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config.php";

$errorMessage = "";
$code = $_GET['code'] ?? '';

if (empty($code)) {
    $errorMessage = "Не вказано код авторизації.";
} else {
    // Підключення до БД
    $link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
    if (!$link) {
        $errorMessage = "Помилка з'єднання з базою даних.";
    } else {
        mysqli_set_charset($link, 'utf8');

        // Пошук коду
        $queryLogin = "SELECT ID, ID_USERS FROM EMPLOYEE_LOGIN WHERE CODE = ?";
        $stmtLogin = mysqli_prepare($link, $queryLogin);
        mysqli_stmt_bind_param($stmtLogin, "s", $code);
        mysqli_stmt_execute($stmtLogin);
        $resultLogin = mysqli_stmt_get_result($stmtLogin);

        if ($rowLogin = mysqli_fetch_assoc($resultLogin)) {
            $userId = (int)$rowLogin['ID_USERS'];
            $loginId = (int)$rowLogin['ID'];

            // Дані користувача
            $queryUser = "SELECT IS_ENT FROM USERS WHERE ID = ?";
            $stmtUser = mysqli_prepare($link, $queryUser);
            mysqli_stmt_bind_param($stmtUser, "i", $userId);
            mysqli_stmt_execute($stmtUser);
            $resultUser = mysqli_stmt_get_result($stmtUser);

            if ($rowUser = mysqli_fetch_assoc($resultUser)) {
                $is_ent = (int)$rowUser['IS_ENT'];

                // Авторизація
                $_SESSION['id_users'] = $userId;
                
                $updateTimeQuery = "UPDATE USERS SET MTIME_LOGIN = NOW() WHERE ID = $userId";
                mysqli_query($link, $updateTimeQuery);

                // Видалення коду
                $deleteQuery = "DELETE FROM EMPLOYEE_LOGIN WHERE ID = ?";
                $stmtDelete = mysqli_prepare($link, $deleteQuery);
                mysqli_stmt_bind_param($stmtDelete, "i", $loginId);
                mysqli_stmt_execute($stmtDelete);
                mysqli_stmt_close($stmtDelete);

                // Редирект в кабінет
                $redirect = ($is_ent == 0 ? 'cabinet.php' : 'cabinet_ent.php');
                header("Location: " . $redirect);
                exit();

            } else {
                 $errorMessage = "Користувача не знайдено.";
            }
            mysqli_stmt_close($stmtUser);

        } else {
            $errorMessage = "Недійсний або вже використаний код авторизації.";
        }
        mysqli_stmt_close($stmtLogin);
        mysqli_close($link);
    }
}

// Якщо виникла помилка - CustomAlert + редирект на login.php
if (!empty($errorMessage)):

    $title = 'Помилка авторизації';
    $list_css = ['/css/login.css', '/css/CustomAlert.css'];
    
    include "page_head.php";
    include "CustomAlert.php";
?>
  <script src="/js/CustomAlert.js"></script>
  <script>
      document.addEventListener("DOMContentLoaded", function() {
          if (typeof showAlert === 'function') {
              showAlert(
                  "<?php echo addslashes($errorMessage); ?>", 
                  "error", 
                  "Помилка входу", 
                  [
                      {
                          text: "На сторінку входу", 
                          className: "btn-alert-ok",
                          onClick: () => window.location.href = "login.php" 
                      }
                  ]
              );
          } else {
              alert("<?php echo addslashes($errorMessage); ?>");
              window.location.href = "login.php";
          }
      });
  </script>
</body>
</html>
<?php endif; ?>