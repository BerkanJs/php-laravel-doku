<?php

// Calistirmak icin: php -S localhost:8000 (bu klasorde)
// Sonra: http://localhost:8000/multibyte_demo.php
// Bu dosya UTF-8 kodlu kaydedilmis olmali (Turkce karakterler dogru calissin diye).

header('Content-Type: text/plain; charset=utf-8');

$isimAscii = "Ahmet";
$isimTurkce = "Öykü";

echo "strlen('Ahmet') = " . strlen($isimAscii) . "\n";        // 5 - ascii, byte = karakter
echo "mb_strlen('Ahmet') = " . mb_strlen($isimAscii) . "\n";   // 5 - ayni sonuc

echo "strlen('Öykü') = " . strlen($isimTurkce) . "\n";        // 6 - YANLIS: byte sayiyor (Ö ve ü 2'ser byte)
echo "mb_strlen('Öykü') = " . mb_strlen($isimTurkce) . "\n";   // 4 - DOGRU: karakter sayiyor

function kullaniciAdiGecerliMiYanlis($ad) {
    return strlen($ad) <= 5;          // YANLIS - Turkce karakterlerde byte sayar, gercek karakter sayisi degil
}

function kullaniciAdiGecerliMiDogru($ad) {
    return mb_strlen($ad) <= 5;       // DOGRU - gercek karakter sayisini kontrol eder
}

echo "\n";
var_dump(kullaniciAdiGecerliMiYanlis("Öykü"));   // false - YANLIS SONUC, "Öykü" 4 harf, 5'ten kucuk olmali
var_dump(kullaniciAdiGecerliMiDogru("Öykü"));    // true  - DOGRU SONUC
