<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

requireAdmin();

$db = new Database();
$conn = $db->getConnection();

// Статистика
$userCount = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$productCount = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
$recentUsers = $conn->query("SELECT id, login, email, role, created_at FROM users ORDER BY id DESC LIMIT 5")->fetchAll();

// Системная информация
$mysqlVersion = $conn->getAttribute(PDO::ATTR_SERVER_VERSION);
$phpVersion = phpversion();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .stat-number { font-size: 48px; font-weight: bold; color: #667eea; }
        .stat-label { color: #666; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <a href="index.php">Главная</a>
            <a href="admin_dashboard.php" class="active">📊 Админ-панель</a>
            <a href="show_products.php">📦 Товары</a>
            <a href="backup.php">💾 Бэкап</a>
            <a href="profile.php">👤 Мой профиль</a>
            <a href="logout.php" class="logout">🚪 Выход (<?= h($_SESSION['user_login']) ?>)</a>
        </nav>
        
        <h1>Панель администратора</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $userCount ?></div>
                <div class="stat-label">Пользователей</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $productCount ?></div>
                <div class="stat-label">Товаров в БД</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= date('d.m.Y') ?></div>
                <div class="stat-label">Текущая дата</div>
            </div>
        </div>
        
        <div class="info-box">
            <h3>Системная информация</h3>
            <p>PHP версия: <code><?= $phpVersion ?></code></p>
            <p>MySQL версия: <code><?= $mysqlVersion ?></code></p>
            <p>Корень сайта: <code><?= $_SERVER['DOCUMENT_ROOT'] ?></code></p>
        </div>
        
        <div class="form-box" style="max-width: 800px;">
            <h2>Последние зарегистрированные пользователи</h2>
            <table class="users-table">
                <thead>
                    <tr><th>ID</th><th>Логин</th><th>Email</th><th>Роль</th><th>Дата</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recentUsers as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= h($user['login']) ?></td>
                        <td><?= h($user['email']) ?></td>
                        <td><?= $user['role'] ?></td>
                        <td><?= $user['created_at'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="text-align: center; margin-top: 15px;"><a href="index.php" class="btn">📋 Все пользователи</a></p>
        </div>
        
        <div class="info-box" style="margin-top: 20px;">
            <h3>Быстрые действия</h3>
            <p><a href="backup.php" class="btn btn-green">💾 Создать бэкап БД</a></p>
            <p><a href="import_xml_to_db.php" class="btn">📥 Импорт товаров из XML</a></p>
            <p><a href="hash.php" class="btn" style="background: #95a5a6;">🔐 Генератор хэша пароля</a></p>
        </div>
    </div>
</body>
</html>