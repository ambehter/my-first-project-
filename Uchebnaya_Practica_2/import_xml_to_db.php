<?php
// import_xml_to_db.php - импорт товаров из XML в базу данных
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Только для администратора
if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$message = '';
$message_type = '';

// Создание таблицы products, если она не существует (без CHECK)
$create_table_sql = "
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(100),
    stock INT DEFAULT 0,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->exec($create_table_sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $xml_file = 'products.xml';
    
    if (!file_exists($xml_file)) {
        $message = 'Файл products.xml не найден в корне проекта!';
        $message_type = 'error';
    } else {
        $xml = simplexml_load_file($xml_file);
        
        if ($xml === false) {
            $message = 'Ошибка загрузки XML-файла. Проверьте синтаксис XML.';
            $message_type = 'error';
        } else {
            $imported = 0;
            $updated = 0;
            $errors = 0;
            
            foreach ($xml->product as $product) {
                $name = (string)$product->name;
                $description = (string)$product->description;
                $price = (float)$product->price;
                $category = (string)$product->category;
                $stock = (int)$product->stock;
                $image_url = (string)$product->image_url;
                
                // Проверка существования товара по названию
                $stmt = $conn->prepare("SELECT id FROM products WHERE name = :name");
                $stmt->execute([':name' => $name]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    // Обновление существующего товара
                    $stmt = $conn->prepare("UPDATE products SET description = :description, price = :price, category = :category, stock = :stock, image_url = :image_url WHERE id = :id");
                    if ($stmt->execute([
                        ':description' => $description,
                        ':price' => $price,
                        ':category' => $category,
                        ':stock' => $stock,
                        ':image_url' => $image_url,
                        ':id' => $existing['id']
                    ])) {
                        $updated++;
                    } else {
                        $errors++;
                    }
                } else {
                    // Вставка нового товара
                    $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, stock, image_url) VALUES (:name, :description, :price, :category, :stock, :image_url)");
                    if ($stmt->execute([
                        ':name' => $name,
                        ':description' => $description,
                        ':price' => $price,
                        ':category' => $category,
                        ':stock' => $stock,
                        ':image_url' => $image_url
                    ])) {
                        $imported++;
                    } else {
                        $errors++;
                    }
                }
            }
            
            $message = "✅ Импорт завершён! Добавлено: $imported, Обновлено: $updated, Ошибок: $errors";
            $message_type = 'success';
        }
    }
}

// Проверка количества товаров в БД после импорта
$stmt = $conn->query("SELECT COUNT(*) as count FROM products");
$productCount = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Импорт XML в базу данных</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .product-count {
            background: #e7f3ff;
            padding: 10px 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        .btn-green {
            background: #27ae60;
        }
        .btn-green:hover {
            background: #219a52;
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <a href="index.php">Главная</a>
            <a href="xml_reader.php">🛒 Каталог (XML)</a>
            <a href="show_products.php">📦 Товары из БД</a>
            <a href="import_xml_to_db.php" class="active">📥 Импорт XML</a>
            <?php if (isLoggedIn()): ?>
                <a href="profile.php">👤 Мой профиль</a>
                <a href="logout.php" class="logout">🚪 Выход (<?= h($_SESSION['user_login']) ?>)</a>
            <?php else: ?>
                <a href="login.php">🔑 Вход</a>
                <a href="register.php">📝 Регистрация</a>
            <?php endif; ?>
        </nav>
        
        <h1>📥 Импорт товаров из XML в базу данных</h1>
        
        <div class="product-count">
            📊 Текущее количество товаров в базе данных: <strong><?= $productCount ?></strong>
        </div>
        
        <?php if ($message): ?>
            <div class="<?= $message_type === 'success' ? 'success' : 'error' ?>">
                <?= h($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <h3>📋 Инструкция по импорту:</h3>
            <ol style="margin-left: 20px;">
                <li>Убедитесь, что вы вошли как <strong>администратор</strong> (логин: admin, пароль: admin123)</li>
                <li>Файл <code>products.xml</code> должен находиться в корневой директории проекта</li>
                <li>При импорте товары обновляются по названию (если товар существует - обновляется, если нет - добавляется)</li>
                <li>Изображения должны находиться в папке <code>assets/img/</code></li>
            </ol>
        </div>
        
        <div class="info-box">
            <h3>📄 Структура XML-файла:</h3>
            <pre>&lt;products&gt;
    &lt;product id="1"&gt;
        &lt;name&gt;Название товара&lt;/name&gt;
        &lt;description&gt;Описание&lt;/description&gt;
        &lt;price&gt;999.00&lt;/price&gt;
        &lt;category&gt;Категория&lt;/category&gt;
        &lt;stock&gt;10&lt;/stock&gt;
        &lt;image_url&gt;assets/img/photo.jpg&lt;/image_url&gt;
    &lt;/product&gt;
&lt;/products&gt;</pre>
        </div>
        
        <form method="POST" style="text-align: center;">
            <button type="submit" class="btn btn-green">🚀 Начать импорт из products.xml</button>
        </form>
        
        <p style="margin-top: 20px; text-align: center;">
            <a href="show_products.php" class="btn">📋 Посмотреть товары в базе данных</a>
        </p>
    </div>
</body>
</html>