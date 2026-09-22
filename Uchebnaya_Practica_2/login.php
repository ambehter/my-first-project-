<?php
/**
 * Страница входа в систему
 * 
 * Учётные записи по умолчанию:
 * - Администратор: admin / password
 * - Пользователь: user1 / 123
 * - Пользователь: user2 / 123
 */

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$error = '';

// Если уже авторизован, перенаправляем на профиль
if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ошибка безопасности';
    } elseif (!verifyCaptcha($_POST['captcha'] ?? '')) {
        $error = 'Неверный ответ капчи';
    } else {
        $login_or_email = trim($_POST['login_or_email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        $stmt = $conn->prepare("SELECT id, login, email, password_hash, role FROM users WHERE login = :login OR email = :email");
        $stmt->execute([':login' => $login_or_email, ':email' => $login_or_email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_login'] = $user['login'];
            $_SESSION['user_role'] = $user['role'];
            
            // ПЕРЕНАПРАВЛЕНИЕ ПОСЛЕ УСПЕШНОГО ВХОДА
            header('Location: profile.php');
            exit;
        } else {
            $error = 'Неверный логин/email или пароль';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="form-box">
            <h1>Вход</h1>
            
            <?php if ($error): ?>
                <div class="error"><?= h($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <label>Логин или Email:</label>
                <input type="text" name="login_or_email" required>
                
                <label>Пароль:</label>
                <input type="password" name="password" required>
                
                <label>Капча: <?= generateCaptcha() ?></label>
                <input type="text" name="captcha" required>
                
                <button type="submit">Войти</button>
            </form>
            <p>Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
        </div>
    </div>
    
    <script>
        // Очистка формы после отправки (для предотвращения повторной отправки)
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>