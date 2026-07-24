<?php

// Calistirmak icin: php -S localhost:8000 (bu klasorde)
// Sonra: http://localhost:8000/interface_ve_abstract_demo.php

header('Content-Type: text/plain; charset=utf-8');

interface Loglanabilir {
    public function logla(string $mesaj): void;
}

class Siparis implements Loglanabilir {
    public function logla(string $mesaj): void {
        echo "[SIPARIS LOG] $mesaj\n";
    }
}

$s = new Siparis();
$s->logla("Yeni siparis olusturuldu");

echo "\n";

abstract class Sekil {
    abstract public function alanHesapla(): float;   // govde yok - alt sinif ZORUNLU olarak yazacak

    public function bilgiVer(): string {
        return "Bu sekilin alani: " . $this->alanHesapla();
    }
}

class Dikdortgen extends Sekil {
    public function __construct(private float $genislik, private float $yukseklik) {}

    public function alanHesapla(): float {
        return $this->genislik * $this->yukseklik;
    }
}

$d = new Dikdortgen(5, 3);
echo $d->bilgiVer() . "\n";   // "Bu sekilin alani: 15"

// $sekil = new Sekil();   // HATA vermesi icin yorumda birakildi - abstract class instantiate edilemez
