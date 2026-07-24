<?php

// Calistirmak icin: php -S localhost:8000 (bu klasorde)
// Sonra: http://localhost:8000/nullsafe_readonly_uniontypes_demo.php

header('Content-Type: text/plain; charset=utf-8');

class Adres { public function __construct(public ?string $sehir = null) {} }
class Sahip { public function __construct(public ?Adres $adres = null) {} }
class Sirket { public function __construct(public ?Sahip $sahibi = null) {} }

$sirket1 = new Sirket();   // sahibi null
$sehir1 = $sirket1?->sahibi?->adres?->sehir;
var_dump($sehir1);   // NULL - zincir sahibi'nde durdu, hata FIRLATMADI

$sirket2 = new Sirket(new Sahip(new Adres("Istanbul")));
$sehir2 = $sirket2?->sahibi?->adres?->sehir;
var_dump($sehir2);   // string(8) "Istanbul"

echo "\n";

class Company {
    public function __construct(public readonly string $ad) {}
}
$c = new Company("Acme A.S.");
echo $c->ad . "\n";
// $c->ad = "Beta Ltd.";   // yorumda birakildi - calistirilirsa Error firlatir (readonly property degistirilemez)

echo "\n";

function idYazdir(int|string $id): void {
    echo "ID: $id\n";
}
idYazdir(5);
idYazdir("ABC123");
