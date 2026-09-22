<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$results = [];
$search_term = '';

if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
    $search_term = trim($_GET['q']);
    $stmt = $conn->prepare("SELECT id, login, email, role, created_at FROM users WHERE login LIKE :search OR email LIKE :search LIMIT 20");
    $stmt->execute([':search' => "%$search_term%"]);
    $results = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Поиск пользователей</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <a href="index.php">Главная</a>
            <a href="search.php">Поиск</a>
            <?php if (isLoggedIn()): ?>
                <a href="profile.php">Профиль</a>
                <a href="logout.php">Выход</a>
            <?php else: ?>
                <a href="login.php">Вход</a>
            <?php endif; ?>
        </nav>
        
        <h1>Поиск пользователей</h1>
        
        <div class="search-form">
            <form method="GET">
                <input type="text" name="q" placeholder="Введите логин или email" value="<?= h($search_term) ?>" required>
                <button type="submit">Искать</button>
            </form>
        </div>
        
        <?php if (isset($_GET['q'])): ?>
            <h2>Результаты поиска: <?= h($search_term) ?></h2>
            <?php if (empty($results)): ?>
                <p>Ничего не найдено</p>
            <?php else: ?>
                <table class="users-table">
                    <thead>
                        <tr><th>ID</th><th>Логин</th><th>Email</th><th>Роль</th><th>Дата</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $user): ?>
                        <tr>
                            <td><?= h($user['id']) ?></td>
                            <td><?= h($user['login']) ?></td>
                            <td><?= h($user['email']) ?></td>
                            <td><?= h($user['role']) ?></td>
                            <td><?= h($user['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>