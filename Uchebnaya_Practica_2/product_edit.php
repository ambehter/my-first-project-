<?php
// product_edit.php - редактирование товара (только для администратора)
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

$error = '';
$success = '';
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: show_products.php');
    exit;
}

// Получение данных товара
$stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute([':id' => $product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: show_products.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF защита
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ошибка безопасности';
    } else {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $category = trim($_POST['category'] ?? '');
        $stock = (int)($_POST['stock'] ?? 0);
        $image_url = trim($_POST['image_url'] ?? '');
        
        if (empty($name)) {
            $error = 'Название товара обязательно';
        } elseif ($price <= 0) {
            $error = 'Цена должна быть больше 0';
        } else {
            $stmt = $conn->prepare("UPDATE products SET name = :name, description = :description, price = :price, category = :category, stock = :stock, image_url = :image_url WHERE id = :id");
            if ($stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':price' => $price,
                ':category' => $category,
                ':stock' => $stock,
                ':image_url' => $image_url,
                ':id' => $product_id
            ])) {
                $success = 'Товар успешно обновлён';
                // Обновляем данные товара для отображения
                $product['name'] = $name;
                $product['description'] = $description;
                $product['price'] = $price;
                $product['category'] = $category;
                $product['stock'] = $stock;
                $product['image_url'] = $image_url;
            } else {
                $error = 'Ошибка при обновлении товара';
            }
        }
    }
}

// Список категорий для выпадающего списка
$categories = ['Электроника', 'Аксессуары', 'Одежда', 'Книги'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование товара</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <a href="index.php">Главная</a>
            <a href="xml_reader.php">🛒 Каталог (XML)</a>
            <a href="show_products.php">📦 Товары из БД</a>
            <a href="import_xml_to_db.php">📥 Импорт XML</a>
            <a href="profile.php">👤 Мой профиль</a>
            <a href="logout.php" class="logout">🚪 Выход (<?= h($_SESSION['user_login']) ?>)</a>
        </nav>
        
        <div class="form-box">
            <h1>✏️ Редактирование товара</h1>
            <p style="text-align: center; color: #666; margin-bottom: 20px;">ID товара: <?= $product_id ?></p>
            
            <?php if ($error): ?>
                <div class="error">❌ <?= h($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success">✅ <?= h($success) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="form-group">
                    <label>Название товара:</label>
                    <input type="text" name="name" value="<?= h($product['name']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Описание:</label>
                    <textarea name="description" rows="5"><?= h($product['description']) ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Цена (₽):</label>
                    <input type="number" name="price" step="0.01" min="0" value="<?= $product['price'] ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Категория:</label>
                    <select name="category" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat ?>" <?= $product['category'] == $cat ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Количество на складе:</label>
                    <input type="number" name="stock" min="0" value="<?= $product['stock'] ?>">
                </div>
                
                <div class="form-group">
                    <label>URL изображения:</label>
                    <input type="text" name="image_url" value="<?= h($product['image_url']) ?>" placeholder="https://... или assets/img/photo.jpg">
                    <?php if (!empty($product['image_url'])): ?>
                        <div>
                            <img src="<?= h($product['image_url']) ?>" class="preview-img" onerror="this.style.display='none'">
                        </div>
                    <?php endif; ?>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn">💾 Сохранить изменения</button>
                    <a href="show_products.php" class="btn-cancel">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>