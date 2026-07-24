<?php

// Calistirmak icin: php -S localhost:8000 (bu klasorde)
// Sonra: http://localhost:8000/tip_gosterme_demo.php

$sayi = 5;
$ondalik = 5.5;
$metin = "merhaba";
$dogruMu = true;
$dizi = [1, 2, 3];
$bosDeger = null;

header('Content-Type: text/plain; charset=utf-8');

echo gettype($sayi) . "\n";       // "integer"  - gettype() bir built-in fonksiyon, degiskenin tipini string olarak doner
echo gettype($ondalik) . "\n";    // "double"   - PHP'de float'in ic adi "double"dir (tarihsel sebep, C'den miras)
echo gettype($metin) . "\n";      // "string"
echo gettype($dogruMu) . "\n";    // "boolean"
echo gettype($dizi) . "\n";       // "array"
echo gettype($bosDeger) . "\n";   // "NULL"

echo "\n--- var_dump ile detayli goruntu ---\n";

var_dump($sayi);      // int(5)              - var_dump hem tipi hem degeri birlikte gosterir, gettype'tan daha detayli
var_dump($ondalik);   // float(5.5)
var_dump($metin);     // string(8) "merhaba" - 8 -> string'in byte uzunlugu
var_dump($dogruMu);   // bool(true)
var_dump($dizi);      // array(3) { ... }    - her elemani index'iyle birlikte gosterir
var_dump($bosDeger);  // NULL
