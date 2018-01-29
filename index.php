<?php

require __DIR__ . '/vendor/autoload.php';

$caracteristics = [
    'Nombre de couverts',
    'Format',
    'Classe énergétique',
    'Consommation d\'eau',
    'Consommation d\'eau par cycle',
    'Consommation d\'énergie',
    'Niveau sonore (Norme EN 60704-3)',
    'Nombre de programmes',
    'Qualité de séchage',
    'Sécurités',
    'Hauteur',
    'Largeur (cm)',
    'Profondeur (cm)',
    'Finition',
    'Origine de fabrication',
    'Disponibilité des pièces détachées',
];

$client = new \Goutte\Client();

// Create and use a guzzle client instance that will time out after 90 seconds
$guzzleClient = new \GuzzleHttp\Client([
    'verify' => false,
]);

$client->setClient($guzzleClient);

$crawler = $client->request('GET', 'https://www.darty.com/nav/extra/list?p=200&s=topa&cat=529');

$links = $crawler->filter('div.infos_container > h2 > a');

$products = [];
foreach ($links as $link) {
    echo 'https://www.darty.com' . $link->getAttribute('href') . PHP_EOL;
    $product = [
        'Url' => 'https://www.darty.com' . $link->getAttribute('href'),
        'Nom' => $link->nodeValue,
    ];
    $productCrawler = $client->request('GET', $product['Url']);

    if (0 === $productCrawler->filter('meta[itemprop="price"]')->count()) {
        continue;
    }

    $product['Prix'] = $productCrawler->filter('meta[itemprop="price"]')->attr('content');
    $product['Marque'] = $productCrawler->filter('#darty_product_brand')->text();

    if ($productCrawler->filter('meta[itemprop="ratingValue"]')->count()) {
        $product['ratingValue'] = $productCrawler->filter('meta[itemprop="ratingValue"]')->attr('content');
    } else {
        $product['ratingValue'] = null;
    }

    if ($productCrawler->filter('meta[itemprop="ratingCount"]')->count()) {
        $product['ratingCount'] = $productCrawler->filter('meta[itemprop="ratingCount"]')->attr('content');
    } else {
        $product['ratingCount'] = 0;
    }

    $caracteristicNames = $productCrawler->filter('#product_caracteristics > div.product_bloc_content.bloc.ombre > table > tbody > tr > th')->each(function (\Symfony\Component\DomCrawler\Crawler $node, $i) {
        return $node->text();
    });
    $caracteristicValues = $productCrawler->filter('#product_caracteristics > div.product_bloc_content.bloc.ombre > table > tbody > tr > td')->each(function (\Symfony\Component\DomCrawler\Crawler $node, $i) {
        return $node->text();
    });

    foreach ($caracteristics as $caracteristic) {
        if (false !== $key = array_search($caracteristic, $caracteristicNames)) {
            $product[$caracteristic] = $caracteristicValues[$key];
        } else {
            $product[$caracteristic] = null;
        }
    }

    $products[] = $product;
    sleep(1);
}

$handle = fopen('products.csv', 'w');

fputcsv($handle, array_keys($products[0]));

foreach ($products as $product) {
    fputcsv($handle, $product);
}

fclose($handle);