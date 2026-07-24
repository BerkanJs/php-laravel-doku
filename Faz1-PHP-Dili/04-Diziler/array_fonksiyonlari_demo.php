<?php

// Calistirmak icin: php -S localhost:8000 (bu klasorde)
// Sonra: http://localhost:8000/array_fonksiyonlari_demo.php

header('Content-Type: text/plain; charset=utf-8');

$sayilar = [1, 2, 3, 4, 5];

$ikiKatlari = array_map(fn($x) => $x * 2, $sayilar);
echo "array_map sonucu:\n";
print_r($ikiKatlari);        // [2, 4, 6, 8, 10] - her eleman donusturuldu

echo "\narray_filter sonucu:\n";
$ciftler = array_filter($sayilar, fn($x) => $x % 2 === 0);
print_r($ciftler);           // [1 => 2, 3 => 4] - DIKKAT: orijinal index'ler KORUNUR, yeniden numaralanmaz
                              // bunu unutursak -> array_values($ciftler) ile yeniden indexlemen gerekebilir

echo "\narray_reduce sonucu:\n";
$toplam = array_reduce($sayilar, fn($tasinan, $x) => $tasinan + $x, 0);
echo $toplam . "\n";          // 15 - tum elemanlar tek degere indirgendi (0 baslangic degeri)
