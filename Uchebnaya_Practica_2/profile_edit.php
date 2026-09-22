<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$conn = $db->getConnection();

$error = '';
$success = '';

// Определяем, какого пользователя редактируем
$edit_id = $_GET['id'] ?? $_SESSION['user_id'];

// Проверка прав: если редактируем не свой профиль, то нужны права администратора
if ($edit_id != $_SESSION['user_id'] && !isAdmin()) {
    header('Location: profile.php');
    exit;
}

// Получаем текущие данные редактируемого пользователя
$stmt = $conn->prepare("SELECT login, email FROM users WHERE id = :id");
$stmt->execute([':id' => $edit_id]);
$current = $stmt->fetch();

// Если пользователь не найден
if (!$current) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ошибка безопасности';
    } else {
        $email = trim($_POST['email'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Валидация email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Неверный формат email';
        } else {
            // Проверка уникальности email (если изменился)
            if ($email !== $current['email']) {
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
                $stmt->execute([':email' => $email, ':id' => $edit_id]);
                if ($stmt->fetch()) {
                    $error = 'Этот email уже используется другим пользователем';
                }
            }
            
            if (empty($error)) {
                // Обновление email
                $updateFields = ['email' => $email];
                
                // Обновление пароля, если указан (только для своего профиля или админ может сменить чужой)
                if (!empty($new_password)) {
                    if (strlen($new_password) < 6) {
                        $error = 'Новый пароль должен быть не менее 6 символов';
                    } elseif ($new_password !== $confirm_password) {
                        $error = 'Пароли не совпадают';
                    } else {
                        $updateFields['password_hash'] = password_hash($new_password, PASSWORD_DEFAULT);
                    }
                }
                
                if (empty($error)) {
                    $sql = "UPDATE users SET email = :email";
                    $params = [':email' => $email, ':id' => $edit_id];
                    
                    if (isset($updateFields['password_hash'])) {
                        $sql .= ", password_hash = :hash";
                        $params[':hash'] = $updateFields['password_hash'];
                    }
                    $sql .= " WHERE id = :id";
                    
                    $stmt = $conn->prepare($sql);
                    if ($stmt->execute($params)) {
                        $success = 'Профиль успешно обновлён';
                        // Обновляем данные сессии, если редактируем свой профиль
                        if ($edit_id == $_SESSION['user_id']) {
                            $_SESSION['user_email'] = $email;
                        }
                    } else {
                        $error = 'Ошибка при обновлении';
                    }
                }
            }
        }
    }
}

// Получаем актуальные данные после возможного обновления
$stmt = $conn->prepare("SELECT login, email FROM users WHERE id = :id");
$stmt->execute([':id' => $edit_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование профиля</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="form-box">
            <h1>
                Редактирование профиля
                <?php if ($edit_id != $_SESSION['user_id']): ?>
                    <span style="font-size: 14px; color: #667eea;">(Пользователь: <?= h($user['login']) ?>)</span>
                <?php endif; ?>
            </h1>
            
            <?php if ($error): ?>
                <div class="error"><?= h($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?= h($success) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <label>Email:</label>
                <input type="email" name="email" value="<?= h($user['email']) ?>" required>
                
                <?php if ($edit_id == $_SESSION['user_id'] || isAdmin()): ?>
                    <label>Новый пароль (оставьте пустым, чтобы не менять):</label>
                    <input type="password" name="new_password" minlength="6">
                    
                    <label>Подтверждение нового пароля:</label>
                    <input type="password" name="confirm_password">
                <?php else: ?>
                    <p class="info">Смена пароля для этого пользователя недоступна</p>
                <?php endif; ?>
                
                <button type="submit">Сохранить изменения</button>
                <a href="<?= ($edit_id == $_SESSION['user_id']) ? 'profile.php' : 'index.php'; ?>" class="btn-cancel">Отмена</a>
            </form>
        </div>
    </div>
    <style>
        .info {
            background: #e7f3ff;
            color: #0066cc;
            padding: 8px;
            border-radius: 5px;
            margin: 10px 0;
            font-size: 14px;
        }
    </style>
</body>
</html>