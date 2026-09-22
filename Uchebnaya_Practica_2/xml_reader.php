<?php
// xml_reader.php - чтение XML-файла и отображение с фильтрацией
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$products = [];
$search_category = $_GET['category'] ?? '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;

$xml_file = 'products.xml';
if (file_exists($xml_file)) {
    $xml = simplexml_load_file($xml_file);
    if ($xml !== false) {
        foreach ($xml->product as $product) {
            $product_data = [
                'id' => (int)$product['id'],
                'name' => (string)$product->name,
                'description' => (string)$product->description,
                'price' => (float)$product->price,
                'category' => (string)$product->category,
                'stock' => (int)$product->stock,
                'image_url' => (string)$product->image_url
            ];
            
            if (!empty($search_category) && $product_data['category'] !== $search_category) {
                continue;
            }
            if ($min_price > 0 && $product_data['price'] < $min_price) {
                continue;
            }
            $products[] = $product_data;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Каталог товаров из XML</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <a href="index.php">🏠 Главная</a>
            <a href="xml_reader.php" class="active">🛒 Каталог (XML)</a>
            <a href="show_products.php">📦 Товары из БД</a>
            <?php if (isAdmin()): ?>
                <a href="import_xml_to_db.php">📥 Импорт XML</a>
            <?php endif; ?>
            <?php if (isLoggedIn()): ?>
                <a href="profile.php">👤 Мой профиль</a>
                <a href="logout.php" class="logout">🚪 Выход (<?= h($_SESSION['user_login']) ?>)</a>
            <?php else: ?>
                <a href="login.php">🔑 Вход</a>
                <a href="register.php">📝 Регистрация</a>
            <?php endif; ?>
        </nav>
        
        <h1>🛒 Каталог товаров (из XML-файла)</h1>
        
        <div class="filter-box">
            <form method="GET">
                <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                    <div>
                        <label>Категория:</label>
                        <select name="category">
                            <option value="">Все категории</option>
                            <option value="Электроника" <?= $search_category == 'Электроника' ? 'selected' : '' ?>>📱 Электроника</option>
                            <option value="Аксессуары" <?= $search_category == 'Аксессуары' ? 'selected' : '' ?>>🖱️ Аксессуары</option>
                            <option value="Одежда" <?= $search_category == 'Одежда' ? 'selected' : '' ?>>👕 Одежда</option>
                            <option value="Книги" <?= $search_category == 'Книги' ? 'selected' : '' ?>>📚 Книги</option>
                        </select>
                    </div>
                    <div>
                        <label>Цена от (₽):</label>
                        <input type="number" name="min_price" value="<?= $min_price ?>" step="1000" min="0">
                    </div>
                    <div>
                        <button type="submit" class="btn">🔍 Применить фильтр</button>
                        <a href="xml_reader.php" class="btn-cancel">Сбросить</a>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if (empty($products)): ?>
            <div class="warning">⚠️ Товары не найдены. Убедитесь, что файл products.xml существует.</div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <img src="<?= h($product['image_url']) ?>" alt="<?= h($product['name'])?>" onerror="this.src='https://via.placeholder.com/300x200?text=Нет+изображения'">
                    <h3><?= h($product['name']) ?></h3>
                    <p class="description"><?= h(mb_substr($product['description'], 0, 100)) ?>...</p>
                    <div class="price"><?= number_format($product['price'], 0, ',', ' ') ?> ₽</div>
                    <span class="category">📁 <?= h($product['category']) ?></span>
                    <div class="stock"><?= $product['stock'] > 0 ? "✅ В наличии: {$product['stock']} шт." : "❌ Нет в наличии" ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <strong>ℹ️ Информация:</strong> Данные загружены из файла <code>products.xml</code>.
            Всего товаров: <strong><?= count($products) ?></strong>
        </div>
    </div>
</body>
</html>