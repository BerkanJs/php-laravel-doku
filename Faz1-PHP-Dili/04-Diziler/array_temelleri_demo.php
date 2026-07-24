<?php

// Calistirmak icin: php -S localhost:8000 (bu klasorde)
// Sonra: http://localhost:8000/array_temelleri_demo.php

header('Content-Type: text/plain; charset=utf-8');

$indexliDizi = ["Kalem", "Defter", "Silgi"];
foreach ($indexliDizi as $key => $value) {
    echo "$key => $value\n";        // 0 => Kalem, 1 => Defter, 2 => Silgi
}

echo "\n";

$assocDizi = ["ad" => "Ali", "yas" => 30];
foreach ($assocDizi as $key => $value) {
    echo "$key: $value\n";          // ad: Ali, yas: 30
}

echo "\n";

$koordinat = [10, 20];
[$x, $y] = $koordinat;              // destructuring - tek satirda $x=10, $y=20
echo "x=$x, y=$y\n\n";

$sirket = [
    "ad" => "Acme A.S.",
    "calisanlar" => [
        ["ad" => "Ali", "departman" => "Satis"],
        ["ad" => "Ayse", "departman" => "IT"],
    ],
];
echo $sirket["calisanlar"][0]["ad"] . "\n";   // "Ali" - nested array'e index zinciriyle erisim
echo $sirket["calisanlar"][1]["departman"] . "\n";   // "IT"
