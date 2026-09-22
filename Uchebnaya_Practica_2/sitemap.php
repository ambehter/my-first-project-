<?php
// sitemap.php
header('Content-Type: application/xml; charset=utf-8');
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

$baseUrl = 'http://test-xml.local';
$pages = [
    ['url' => '/index.php', 'priority' => '1.0', 'changefreq' => 'daily'],
    ['url' => '/xml_reader.php', 'priority' => '0.9', 'changefreq' => 'daily'],
    ['url' => '/show_products.php', 'priority' => '0.8', 'changefreq' => 'daily']
];

// Получаем товары для карты сайта
$products = $conn->query("SELECT id FROM products")->fetchAll();
foreach ($products as $product) {
    $pages[] = ['url' => '/product.php?id=' . $product['id'], 'priority' => '0.7', 'changefreq' => 'weekly'];
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <?php foreach ($pages as $page): ?>
    <url>
        <loc><?= $baseUrl . $page['url'] ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq><?= $page['changefreq'] ?></changefreq>
        <priority><?= $page['priority'] ?></priority>
    </url>
    <?php endforeach; ?>
</urlset>