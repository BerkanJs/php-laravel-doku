<?php

// REFERANS DOSYASI - bu su an CALISTIRILAMAZ.
// Carbon, Guzzle ve phpdotenv paketleri Composer ile kurulmadigi surece bu dosya hata verir.
// Faz2'de gercek bir Laravel projesinde bu paketler kurulunca (veya composer require ile eklenince)
// birebir bu sekilde kullanilacak.

require 'vendor/autoload.php';   // Composer autoload - Gun 9'da gorduk, bu dosya olmadan asagidaki use'lar calismaz

use Carbon\Carbon;
use GuzzleHttp\Client;

// --- Carbon ornegi ---
$simdi = Carbon::now();
$ucGunSonra = $simdi->copy()->addDays(3);   // copy() olmadan addDays orijinali de degistirirdi (mutable nesne)
echo $ucGunSonra->format('Y-m-d') . "\n";
echo $ucGunSonra->diffForHumans() . "\n";     // "3 gun sonra" gibi okunabilir cikti

// --- Guzzle ornegi ---
$client = new Client();
$response = $client->get('https://api.example.com/companies');
$data = json_decode($response->getBody(), true);
print_r($data);

// --- .env ornegi ---
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
echo $_ENV['DB_HOST'] . "\n";

// Bu paketleri gercekten kurmak icin (ileride Composer kurulunca):
// composer require nesbot/carbon guzzlehttp/guzzle vlucas/phpdotenv
// composer require --dev phpstan/phpstan friendsofphp/php-cs-fixer
