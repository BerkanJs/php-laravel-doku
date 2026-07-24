<?php

// Calistirmak icin: php -S localhost:8000 (bu klasorde)
// Sonra: http://localhost:8000/closure_ve_arrow_demo.php

header('Content-Type: text/plain; charset=utf-8');

$komisyonOrani = 0.1;

// use OLMADAN - bu YANLIS ornek, calistirinca hata verir (referans icin yorum satirinda birakildi):
// $fiyatiHesaplaHatali = function ($fiyat) {
//     return $fiyat + ($fiyat * $komisyonOrani);   // Undefined variable $komisyonOrani
// };

$fiyatiHesapla = function ($fiyat) use ($komisyonOrani) {   // use (deger) -> $komisyonOrani'nin o anki degerini kopyalar
    return $fiyat + ($fiyat * $komisyonOrani);
};
echo $fiyatiHesapla(100) . "\n";   // 110

$sayac = 0;
$arttir = function () use (&$sayac) {   // use (&referans) -> disaridaki gercek degiskeni degistirir
    $sayac++;
};
$arttir();
$arttir();
echo $sayac . "\n";   // 2 - disaridaki $sayac gercekten degisti (deger ile capture'da bu olmazdi)

$carpan = 3;
$ucKatinaCikar = fn($x) => $x * $carpan;   // arrow function - use YAZMADAN otomatik capture (ama sadece OKUMA icin)
echo $ucKatinaCikar(5) . "\n";   // 15

$uzunlukHesapla = strlen(...);   // first-class callable syntax (PHP 8.1+) - strlen'i bir Closure nesnesine cevirir
echo $uzunlukHesapla("merhaba") . "\n";   // 7
