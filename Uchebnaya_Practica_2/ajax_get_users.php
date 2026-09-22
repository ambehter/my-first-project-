<?php
// ajax_get_users.php - должен быть в корне проекта, НЕ в папке js!
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Проверка авторизации и прав администратора
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Доступ запрещен']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Получение параметров из GET-запроса
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';  // ← поисковый запрос
$limit = 10;
$offset = ($page - 1) * $limit;

// Построение SQL-запроса с поиском
$sql = "SELECT id, login, email, role, created_at FROM users WHERE 1=1";
$countSql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
$params = [];

// Добавление условия поиска, если передан search-параметр
if (!empty($search)) {
    $sql .= " AND (login LIKE :search OR email LIKE :search)";
    $countSql .= " AND (login LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}

// Подсчёт общего количества записей
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

// Генерация HTML для таблицы
ob_start();
if (empty($users)): ?>
    <tr>
        <td colspan="6" style="text-align: center;">Пользователи не найдены</td>
    </tr>
<?php else: ?>
    <?php foreach ($users as $user): ?>
    <tr>
        <td><?= h($user['id']) ?></td>
        <td><?= h($user['login']) ?></td>
        <td><?= h($user['email']) ?></td>
        <td><?= h($user['role']) ?></td>
        <td><?= h($user['created_at']) ?></td>
        <?php if (isAdmin() && $user['id'] != $_SESSION['user_id']): ?>
        <td>
            <a href="profile_edit.php?id=<?= $user['id'] ?>" class="btn-small">Редакт.</a>
            <a href="profile_delete.php?id=<?= $user['id'] ?>" class="btn-small btn-danger" onclick="return confirm('Удалить пользователя?')">Удалить</a>
        </td>
        <?php elseif (isAdmin()): ?>
        <td><em>(Это вы)</em></td>
        <?php else: ?>
        <td></td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
<?php endif;
$tableHtml = ob_get_clean();

// Генерация HTML для пагинации
ob_start();
if ($totalPages > 1): ?>
    <?php
    for ($i = 1; $i <= $totalPages; $i++):
        $isActive = ($i === $page) ? 'active' : '';
    ?>
    <a href="#" data-page="<?= $i ?>" class="ajax-page <?= $isActive ?>"><?= $i ?></a>
    <?php endfor; ?>
<?php endif;
$paginationHtml = ob_get_clean();

// Возврат JSON-ответа
echo json_encode([
    'success' => true,
    'html' => $tableHtml,
    'pagination' => $paginationHtml,
    'total' => $total,
    'page' => $page,
    'totalPages' => $totalPages,
    'search' => $search  // для отладки
]);