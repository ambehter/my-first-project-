<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Проверка авторизации
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, login, email, role, created_at FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Мой профиль</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <a href="index.php">Главная</a>
            <a href="xml_reader.php">🛒 Каталог (XML)</a>
            <a href="show_products.php">📦 Товары из БД</a>
            <?php if (isAdmin()): ?>
                <a href="import_xml_to_db.php">📥 Импорт XML</a>
            <?php endif; ?>
            <a href="profile.php" class="active">👤 Мой профиль</a>
            <?php if (isAdmin()): ?>
                <a href="index.php">👥 Пользователи</a>
            <?php endif; ?>
            <a href="logout.php" class="logout">🚪 Выход (<?= h($_SESSION['user_login']) ?>)</a>
        </nav>
        
        <div class="profile-card">
            <h1>Профиль пользователя</h1>
            <p><strong>ID:</strong> <?= h($user['id']) ?></p>
            <p><strong>Логин:</strong> <?= h($user['login']) ?></p>
            <p><strong>Email:</strong> <?= h($user['email']) ?></p>
            <p><strong>Роль:</strong> <?= h($user['role']) ?></p>
            <p><strong>Дата регистрации:</strong> <?= date('d.m.Y H:i:s', strtotime($user['created_at'])) ?></p>
            
            <div class="profile-actions">
                <a href="profile_edit.php" class="btn">Редактировать профиль</a>
                <a href="profile_delete.php" class="btn btn-danger" onclick="return confirm('Вы уверены?')">Удалить аккаунт</a>
            </div>
        </div>
    </div>
</body>
</html>