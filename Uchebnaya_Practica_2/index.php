<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Параметры пагинации и поиска
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = 10;
$offset = ($page - 1) * $limit;

// Построение запроса с поиском
$sql = "SELECT id, login, email, role, created_at FROM users WHERE 1=1";
$countSql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (login LIKE :search OR email LIKE :search)";
    $countSql .= " AND (login LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}

// Получение общего количества
$stmt = $conn->prepare($countSql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$total = $stmt->fetch()['total'];
$totalPages = ceil($total / $limit);

// Получение пользователей для текущей страницы
$sql .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);

foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление пользователями</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-XXXXXXXXXX');
    </script>
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <a href="index.php">🏠 Главная</a>
            <a href="xml_reader.php">🛒 Каталог (XML)</a>
            <a href="show_products.php">📦 Товары из БД</a>
            <?php if (isAdmin()): ?>
                <a href="import_xml_to_db.php">📥 Импорт XML</a>
            <?php endif; ?>
            <?php if (isLoggedIn()): ?>
                <a href="profile.php">👤 Мой профиль</a>
                <?php if (isAdmin()): ?>
                    <a href="index.php">👥 Пользователи</a>
                <?php endif; ?>
                <a href="logout.php" class="logout">🚪 Выход (<?= h($_SESSION['user_login']) ?>)</a>
            <?php else: ?>
                <a href="login.php">🔑 Вход</a>
                <a href="register.php">📝 Регистрация</a>
            <?php endif; ?>
        </nav>
        
        <h1>👥 Пользователи системы</h1>
        
        <div class="total-count">
            📊 Всего пользователей: <strong><?= $total ?></strong>
        </div>
        
        <div class="search-box">
            <form method="GET">
                <input type="text" name="search" placeholder="Поиск по логину или email" value="<?= h($search) ?>">
                <button type="submit">🔍 Найти</button>
                <?php if (!empty($search)): ?>
                    <a href="index.php">Сбросить</a>
                <?php endif; ?>
            </form>
        </div>
        
        <?php if (empty($users)): ?>
            <div class="warning">⚠️ Пользователи не найдены</div>
        <?php else: ?>
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Логин</th>
                        <th>Email</th>
                        <th>Роль</th>
                        <th>Дата регистрации</th>
                        <?php if (isAdmin()): ?>
                            <th>Действия</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= h($user['id']) ?></td>
                        <td><?= h($user['login']) ?></td>
                        <td><?= h($user['email']) ?></td>
                        <td><?= h($user['role']) ?></td>
                        <td><?= h($user['created_at']) ?></td>
                        <?php if (isAdmin() && $user['id'] != $_SESSION['user_id']): ?>
                            <td>
                                <a href="profile_edit.php?id=<?= $user['id'] ?>" class="btn-small">✏️ Редакт.</a>
                                <a href="profile_delete.php?id=<?= $user['id'] ?>" class="btn-small btn-danger" onclick="return confirm('Удалить пользователя?')">🗑️ Удалить</a>
                            </td>
                        <?php elseif (isAdmin()): ?>
                            <td><em>(Это вы)</em></td>
                        <?php else: ?>
                            <td><a href="profile.php?id=<?= $user['id'] ?>" class="btn-small">👁️ Просмотр</a></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php
                $queryParams = $_GET;
                for ($i = 1; $i <= $totalPages; $i++):
                    $queryParams['page'] = $i;
                    $url = '?' . http_build_query($queryParams);
                    $activeClass = ($i == $page) ? 'active' : '';
                ?>
                <a href="<?= $url ?>" class="<?= $activeClass ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <script src="assets/js/main.js"></script>
</body>
</html>