<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$conn = $db->getConnection();

// Проверка, удаляет ли администратор другого пользователя
$delete_id = $_GET['id'] ?? $_SESSION['user_id'];

if (isAdmin() && $delete_id != $_SESSION['user_id']) {
    // Администратор удаляет другого пользователя
    $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute([':id' => $delete_id]);
    header('Location: index.php');
    exit;
} elseif ($delete_id == $_SESSION['user_id']) {
    // Пользователь удаляет сам себя - требуется подтверждение пароля
    $error = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Ошибка безопасности';
        } else {
            $password = $_POST['password'] ?? '';
            $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = :id");
            $stmt->execute([':id' => $_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if (password_verify($password, $user['password_hash'])) {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
                $stmt->execute([':id' => $_SESSION['user_id']]);
                session_destroy();
                header('Location: register.php?deleted=1');
                exit;
            } else {
                $error = 'Неверный пароль';
            }
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Удаление аккаунта</title>
        <link rel="stylesheet" href="assets/css/style.css">
    </head>
    <body>
        <div class="container">
            <div class="form-box">
                <h1>Удаление аккаунта</h1>
                <p class="warning">⚠️ Это действие необратимо. Все ваши данные будут удалены.</p>
                <?php if ($error): ?>
                    <div class="error"><?= h($error) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <label>Введите пароль для подтверждения:</label>
                    <input type="password" name="password" required>
                    <button type="submit" class="btn-danger">Удалить навсегда</button>
                    <a href="profile.php" class="btn-cancel">Отмена</a>
                </form>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
} else {
    header('Location: index.php');
    exit;
}
?>