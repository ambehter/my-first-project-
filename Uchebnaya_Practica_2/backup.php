<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Только для администратора
requireAdmin();

$db = new Database();
$conn = $db->getConnection();
$backupDir = 'backups/';
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}
$message = '';
$messageType = '';

// Обработка создания бэкапа
if (isset($_POST['create_backup'])) {
    $filename = $backupDir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    // Попытка использовать mysqldump
    $command = "mysqldump --user=" . DB_USER . " --password=" . DB_PASS . " --host=" . DB_HOST . " " . DB_NAME . " > " . $filename;
    system($command, $output);
    
    if (file_exists($filename) && filesize($filename) > 0) {
        $message = "Резервная копия успешно создана: " . basename($filename);
        $messageType = "success";
    } else {
        // Альтернативный метод через PDO
        $backupContent = "-- Резервная копия " . DB_NAME . " от " . date('Y-m-d H:i:s') . "\n--\n\n";
        $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            // Структура таблицы
            $create = $conn->query("SHOW CREATE TABLE $table")->fetch();
            $backupContent .= "DROP TABLE IF EXISTS $table;\n";
            $backupContent .= $create['Create Table'] . ";\n\n";
            
            // Данные таблицы
            $rows = $conn->query("SELECT * FROM $table")->fetchAll();
            if (count($rows) > 0) {
                $backupContent .= "INSERT INTO $table VALUES\n";
                $values = [];
                foreach ($rows as $row) {
                    $escaped = array_map(function($val) use ($conn) {
                        return $val === null ? 'NULL' : $conn->quote($val);
                    }, $row);
                    $values[] = "(" . implode(",", $escaped) . ")";
                }
                $backupContent .= implode(",\n", $values) . ";\n\n";
            }
        }
        
        if (file_put_contents($filename, $backupContent)) {
            $message = "Резервная копия создана (метод PDO): " . basename($filename);
            $messageType = "success";
        } else {
            $message = "Ошибка при создании резервной копии";
            $messageType = "error";
        }
    }
}

// Обработка восстановления из файла на сервере
if (isset($_POST['restore_backup']) && isset($_POST['backup_file'])) {
    $backupFile = $backupDir . basename($_POST['backup_file']);
    
    if (file_exists($backupFile)) {
        // Авто-бэкап перед восстановлением
        $autoBackup = $backupDir . 'auto_before_restore_' . date('Y-m-d_H-i-s') . '.sql';
        copy($backupFile, $autoBackup);
        
        // Восстановление
        $sql = file_get_contents($backupFile);
        try {
            $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
            $conn->exec($sql);
            $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
            $message = "База данных успешно восстановлена из файла: " . basename($backupFile);
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Ошибка при восстановлении: " . $e->getMessage();
            $messageType = "error";
        }
    } else {
        $message = "Файл резервной копии не найден";
        $messageType = "error";
    }
}

// Загрузка своего файла для восстановления
if (isset($_FILES['restore_file']) && $_FILES['restore_file']['error'] === UPLOAD_ERR_OK) {
    $allowedExt = ['sql'];
    $fileExt = strtolower(pathinfo($_FILES['restore_file']['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExt, $allowedExt)) {
        $message = "Разрешены только SQL-файлы";
        $messageType = "error";
    } elseif ($_FILES['restore_file']['size'] > 10 * 1024 * 1024) {
        $message = "Размер файла не должен превышать 10 МБ";
        $messageType = "error";
    } else {
        $uploadedFile = $backupDir . 'uploaded_' . date('Y-m-d_H-i-s') . '.sql';
        if (move_uploaded_file($_FILES['restore_file']['tmp_name'], $uploadedFile)) {
            // Авто-бэкап перед восстановлением
            $autoBackup = $backupDir . 'auto_before_restore_' . date('Y-m-d_H-i-s') . '.sql';
            $sql = file_get_contents($uploadedFile);
            try {
                $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
                $conn->exec($sql);
                $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
                $message = "База данных восстановлена из загруженного файла";
                $messageType = "success";
            } catch (PDOException $e) {
                $message = "Ошибка при восстановлении: " . $e->getMessage();
                $messageType = "error";
            }
        } else {
            $message = "Ошибка при загрузке файла";
            $messageType = "error";
        }
    }
}

// Список доступных бэкапов
$backups = glob($backupDir . '*.sql');
rsort($backups);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Резервное копирование</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .backup-list { list-style: none; padding: 0; }
        .backup-list li { background: #f5f5f5; margin: 10px 0; padding: 10px; border-radius: 5px; display: flex; justify-content: space-between; align-items: center; }
        .backup-list form { margin: 0; display: inline; }
        .btn-small { padding: 5px 10px; font-size: 12px; margin: 0 5px; }
    </style>
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <a href="index.php">Главная</a>
            <a href="admin_dashboard.php">Админ-панель</a>
            <a href="show_products.php">Товары</a>
            <a href="backup.php" class="active">📁 Бэкап</a>
            <a href="profile.php">Мой профиль</a>
            <a href="logout.php" class="logout">Выход (<?= h($_SESSION['user_login']) ?>)</a>
        </nav>
        
        <h1>📁 Резервное копирование базы данных</h1>
        
        <?php if ($message): ?>
            <div class="<?= $messageType === 'success' ? 'success' : 'error' ?>"><?= h($message) ?></div>
        <?php endif; ?>
        
        <div class="form-box" style="max-width: 800px;">
            <h2>Создать резервную копию</h2>
            <form method="POST">
                <button type="submit" name="create_backup" class="btn btn-green">💾 Создать резервную копию сейчас</button>
            </form>
            
            <h2 style="margin-top: 30px;">Восстановление из файла на сервере</h2>
            <form method="POST">
                <select name="backup_file" style="width: 100%; padding: 10px; margin: 10px 0;">
                    <option value="">-- Выберите файл резервной копии --</option>
                    <?php foreach ($backups as $backup): ?>
                        <option value="<?= basename($backup) ?>"><?= basename($backup) ?> (<?= round(filesize($backup) / 1024, 2) ?> KB)</option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="restore_backup" class="btn" style="background: #f39c12;" onclick="return confirm('Восстановление удалит текущие данные! Продолжить?')">🔄 Восстановить выбранный</button>
            </form>
            
            <h2 style="margin-top: 30px;">Загрузить свой SQL-файл</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="restore_file" accept=".sql" required style="margin: 10px 0;">
                <button type="submit" class="btn" style="background: #3498db;" onclick="return confirm('Восстановление удалит текущие данные! Продолжить?')">📤 Загрузить и восстановить</button>
            </form>
        </div>
        
        <div class="info-box" style="margin-top: 20px;">
            <h3>⚠️ Важно!</h3>
            <ul>
                <li>Перед восстановлением автоматически создаётся резервная копия в папке <code>backups/</code> с префиксом <code>auto_before_restore_</code></li>
                <li>Файлы резервных копий защищены от прямого доступа из браузера через .htaccess</li>
                <li>Рекомендуется регулярно создавать бэкапы и скачивать их на локальный компьютер</li>
            </ul>
        </div>
    </div>
</body>
</html>