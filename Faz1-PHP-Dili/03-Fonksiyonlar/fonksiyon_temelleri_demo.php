<?php

// Calistirmak icin: php -S localhost:8000 (bu klasorde)
// Sonra: http://localhost:8000/fonksiyon_temelleri_demo.php

header('Content-Type: text/plain; charset=utf-8');

function selamla($isim = "Misafir") {
    return "Merhaba, $isim";       // $isim verilmezse varsayilan "Misafir" kullanilir
}

echo selamla() . "\n";              // "Merhaba, Misafir" - parametre verilmedi
echo selamla("Berkan") . "\n";      // "Merhaba, Berkan"

function toplam(...$sayilar) {      // ...$sayilar -> variadic, kac parametre gelirse bir array'de toplanir
    return array_sum($sayilar);     // array_sum -> array elemanlarini toplayan hazir fonksiyon (Gun 4'te detay)
}

echo toplam(1, 2, 3) . "\n";        // 6
echo toplam(10, 20) . "\n";         // 30 - farkli sayida parametre de calisir

function dikdortgenAlani($genislik, $yukseklik) {
    return $genislik * $yukseklik;
}

echo dikdortgenAlani(genislik: 5, yukseklik: 3) . "\n";   // 15 - named argument, sira onemsiz
echo dikdortgenAlani(yukseklik: 3, genislik: 5) . "\n";   // 15 - ayni sonuc, sira degisti
