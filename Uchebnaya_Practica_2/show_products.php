<?php
// show_products.php - отображение товаров из базы данных MySQL
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Проверка существования таблицы products
$table_exists = $conn->query("SHOW TABLES LIKE 'products'")->rowCount() > 0;

if ($table_exists) {
    $stmt = $conn->query("SELECT id, name, description, price, category, stock, image_url, created_at FROM products ORDER BY id DESC");
    $products = $stmt->fetchAll();
} else {
    $products = [];
}

// Обработка удаления товара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && isAdmin()) {
    $delete_id = (int)$_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id = :id");
    $stmt->execute([':id' => $delete_id]);
    header('Location: show_products.php?deleted=1');
    exit;
}

$deleted = isset($_GET['deleted']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Товары из базы данных</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <a href="index.php">🏠 Главная</a>
            <a href="xml_reader.php">🛒 Каталог (XML)</a>
            <a href="show_products.php" class="active">📦 Товары из БД</a>
            <a href="import_xml_to_db.php">📥 Импорт XML</a>
            <?php if (isLoggedIn()): ?>
                <a href="profile.php">👤 Мой профиль</a>
                <a href="logout.php" class="logout">🚪 Выход (<?= h($_SESSION['user_login']) ?>)</a>
            <?php else: ?>
                <a href="login.php">🔑 Вход</a>
                <a href="register.php">📝 Регистрация</a>
            <?php endif; ?>
        </nav>
        
        <h1>📋 Товары из базы данных</h1>
        
        <?php if ($deleted): ?>
            <div class="success">✅ Товар успешно удалён</div>
        <?php endif; ?>
        
        <div class="total-count">
            📊 Всего товаров в базе данных: <strong><?= count($products) ?></strong>
        </div>
        
        <?php if (!$table_exists): ?>
            <div class="error">
                ❌ Таблица 'products' не существует. 
                <a href="import_xml_to_db.php">Запустите импорт XML</a> для создания таблицы.
            </div>
        <?php elseif (empty($products)): ?>
            <div class="warning">
                ⚠️ В базе данных нет товаров. 
                <a href="import_xml_to_db.php">Импортируйте товары из XML-файла</a>.
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <?php if (isAdmin()): ?>
                    <form method="POST" class="delete-form" onsubmit="return confirm('Удалить товар «<?= h($product['name']) ?>»?')">
                        <input type="hidden" name="delete_id" value="<?= $product['id'] ?>">
                        <button type="submit" class="delete-btn" title="Удалить товар">✕</button>
                    </form>
                    <?php endif; ?>
                    
                    <img src="<?= h($product['image_url']) ?>" alt="<?= h($product['name']) ?>" onerror="this.src='https://via.placeholder.com/300x200?text=Нет+изображения'">
                    
                    <h3><?= h($product['name']) ?></h3>
                    <div class="description"><?= h(mb_substr($product['description'], 0, 100)) ?><?= mb_strlen($product['description']) > 100 ? '...' : '' ?></div>
                    
                    <div class="price"><?= number_format($product['price'], 2, ',', ' ') ?> ₽</div>
                    <span class="category">📁 <?= h($product['category']) ?></span>
                    
                    <?php
                    $stock_class = 'stock-high';
                    if ($product['stock'] <= 5 && $product['stock'] > 0) $stock_class = 'stock-low';
                    elseif ($product['stock'] <= 15) $stock_class = 'stock-medium';
                    ?>
                    <div class="stock-badge <?= $stock_class ?>">
                        <?= $product['stock'] > 0 ? "📦 В наличии: {$product['stock']} шт." : "❌ Нет в наличии" ?>
                    </div>
                    
                    <?php if (isAdmin()): ?>
                    <div class="admin-actions">
                        <a href="product_edit.php?id=<?= $product['id'] ?>" class="btn-primary">✏️ Редактировать</a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="import_xml_to_db.php" class="btn">🔄 Импортировать/Обновить товары из XML</a>
        </div>
    </div>
</body>
</html>