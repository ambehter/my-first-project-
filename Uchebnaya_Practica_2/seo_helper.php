<?php
// seo_helper.php
function generateSlug($string) {
    $string = transliterator_transliterate('Any-Latin; Latin-ASCII; [\u0080-\u7fff] remove', $string);
    $string = preg_replace('/[^a-zA-Z0-9-\s]/', '', $string);
    $string = strtolower(trim($string));
    $string = preg_replace('/[\s-]+/', '-', $string);
    return $string;
}

function generateMetaTags($title, $description, $keywords = '') {
    $defaultTitle = 'Интернет-магазин техники';
    $defaultDesc = 'Лучшие цены на ноутбуки, смартфоны и аксессуары. Быстрая доставка, гарантия качества.';
    
    return [
        'title' => empty($title) ? $defaultTitle : h($title) . ' | ' . $defaultTitle,
        'description' => empty($description) ? $defaultDesc : h($description),
        'keywords' => h($keywords)
    ];
}

function generateBreadcrumbs($items) {
    $html = '<div class="breadcrumbs">';
    $html .= '<a href="index.php">Главная</a>';
    foreach ($items as $item) {
        if (isset($item['url'])) {
            $html .= ' / <a href="' . $item['url'] . '">' . h($item['name']) . '</a>';
        } else {
            $html .= ' / <span>' . h($item['name']) . '</span>';
        }
    }
    $html .= '</div>';
    return $html;
}

function generateProductSchema($product) {
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "Product",
        "name" => $product['name'],
        "description" => $product['description'],
        "image" => $product['image_url'],
        "offers" => [
            "@type" => "Offer",
            "price" => $product['price'],
            "priceCurrency" => "RUB",
            "availability" => $product['stock'] > 0 ? "https://schema.org/InStock" : "https://schema.org/OutOfStock"
        ]
    ];
    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';
}
?>