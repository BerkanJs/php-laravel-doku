<?php

// Calistirmak icin: php -S localhost:8000 (bu klasorde)
// Sonra: http://localhost:8000/array_kopyalama_ve_birlestirme_demo.php

header('Content-Type: text/plain; charset=utf-8');

// --- Value semantics (senaryodaki bug'in kok nedeni) ---
function urunEkle($sepet) {
    $sepet[] = "Yeni Urun";   // bu SADECE fonksiyon icindeki KOPYAYI degistirir
    return $sepet;             // degisikligi geri dondurmek ZORUNDAYIZ, otomatik yansimaz
}

$sepetim = ["Kalem", "Defter"];
$sonuc = urunEkle($sepetim);

echo "Orijinal (degismedi): "; print_r($sepetim);   // ["Kalem", "Defter"]
echo "Fonksiyondan donen: "; print_r($sonuc);        // ["Kalem", "Defter", "Yeni Urun"]

echo "\n";

// --- array_merge vs + ---
$a = ["a" => 1, "b" => 2];
$b = ["a" => 99, "c" => 3];

echo "array_merge (sagdaki kazanir):\n";
print_r(array_merge($a, $b));   // ["a" => 99, "b" => 2, "c" => 3] - SAGDAKI (b) kazanir, "a" uzerine yazildi

echo "\n+ operatoru (soldaki kazanir):\n";
print_r($a + $b);                // ["a" => 1, "b" => 2, "c" => 3]  - SOLDAKI (a) kazanir, "a" korundu

echo "\n";

// --- Spread operator ---
$ilkGrup = [1, 2, 3];
$ikinciGrup = [4, 5];
$birlesik = [...$ilkGrup, ...$ikinciGrup];
echo "Spread ile birlesik:\n";
print_r($birlesik);   // [1, 2, 3, 4, 5]
