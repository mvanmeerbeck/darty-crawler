<?php

require __DIR__ . '/vendor/autoload.php';

$client = new \Goutte\Client();
$crawler = $client->request('GET', 'https://www.darty.com/nav/extra/list?p=200&s=topa&cat=529');

$links = $crawler->filter('div.infos_container > h2 > a');

$products = [];
foreach ($links as $link) {
    $product = [
        'url' => 'https://www.darty.com' . $link->getAttribute('href'),
        'name' => $link->nodeValue
    ];
    $productCrawler = $client->request('GET', $product['url']);

    $product['price'] = $productCrawler->filter('meta[itemprop="price"]')->attr('content');
    $product['ratingValue'] = $productCrawler->filter('meta[itemprop="ratingValue"]')->attr('content');
    $product['ratingCount'] = $productCrawler->filter('meta[itemprop="ratingCount"]')->attr('content');

    $caracteristicNames = $productCrawler->filter('#product_caracteristics > div.product_bloc_content.bloc.ombre > table > tbody > tr > th')->each(function (\Symfony\Component\DomCrawler\Crawler $node, $i) {
        return $node->text();
    });
    $caracteristicValues = $productCrawler->filter('#product_caracteristics > div.product_bloc_content.bloc.ombre > table > tbody > tr > td')->each(function (\Symfony\Component\DomCrawler\Crawler $node, $i) {
        return $node->text();
    });

    foreach ($caracteristicNames as $i => $caracteristicName) {
        $product[$caracteristicName] = $caracteristicValues[$i];
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