<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF защита
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ошибка безопасности. Попробуйте снова.';
    } elseif (!verifyCaptcha($_POST['captcha'] ?? '')) {
        $error = 'Неверный ответ капчи';
    } else {
        $login = trim($_POST['login'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        
        // Валидация
        if (empty($login) || strlen($login) < 3 || strlen($login) > 50) {
            $error = 'Логин должен быть от 3 до 50 символов';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $login)) {
            $error = 'Логин может содержать только буквы, цифры и символ подчёркивания';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Неверный формат email';
        } elseif (strlen($password) < 6) {
            $error = 'Пароль должен быть не менее 6 символов';
        } elseif ($password !== $password_confirm) {
            $error = 'Пароли не совпадают';
        } else {
            // Проверка уникальности
            $stmt = $conn->prepare("SELECT id FROM users WHERE login = :login OR email = :email");
            $stmt->execute([':login' => $login, ':email' => $email]);
            if ($stmt->fetch()) {
                $error = 'Пользователь с таким логином или email уже существует';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (login, email, password_hash) VALUES (:login, :email, :hash)");
                if ($stmt->execute([':login' => $login, ':email' => $email, ':hash' => $password_hash])) {
                    $success = 'Регистрация успешна! Теперь вы можете войти.';
                } else {
                    $error = 'Ошибка при регистрации';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="form-box">
            <h1>Регистрация</h1>
            <?php if ($error): ?>
                <div class="error"><?= h($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?= h($success) ?></div>
                <p><a href="login.php">Перейти к входу</a></p>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <label>Логин:</label>
                    <input type="text" name="login" required minlength="3" maxlength="50" pattern="[a-zA-Z0-9_]+">
                    
                    <label>Email:</label>
                    <input type="email" name="email" required>
                    
                    <label>Пароль (мин. 6 символов):</label>
                    <input type="password" name="password" required minlength="6">
                    
                    <label>Подтверждение пароля:</label>
                    <input type="password" name="password_confirm" required>
                    
                    <label>Капча: <?= generateCaptcha() ?></label>
                    <input type="text" name="captcha" required>
                    
                    <button type="submit">Зарегистрироваться</button>
                </form>
                <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>