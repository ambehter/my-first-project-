<?php
$hash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if ($password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Генератор хэша</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #667eea; }
        .container { max-width: 500px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        input, button { padding: 10px; width: 100%; margin: 10px 0; }
        button { background: #667eea; color: white; border: none; cursor: pointer; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Генератор хэша пароля</h1>
        <form method="POST">
            <input type="text" name="password" placeholder="Введите короткий пароль (например: 123)" required>
            <button type="submit">Сгенерировать</button>
        </form>
        <?php if ($hash): ?>
            <h3>Хэш (60 символов):</h3>
            <pre><?= $hash ?></pre>
            <h3>SQL запрос:</h3>
            <pre>UPDATE users SET password_hash = '<?= $hash ?>' WHERE login = 'admin';</pre>
        <?php endif; ?>
    </div>
</body>
</html>