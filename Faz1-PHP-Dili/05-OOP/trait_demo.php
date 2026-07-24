<?php

// Calistirmak icin: php -S localhost:8000 (bu klasorde)
// Sonra: http://localhost:8000/trait_demo.php

header('Content-Type: text/plain; charset=utf-8');

trait Loglanabilir {
    public function kaydet() {
        echo "Loglanabilir::kaydet calisti\n";
    }
}

trait Onbelleklenebilir {
    public function kaydet() {
        echo "Onbelleklenebilir::kaydet calisti\n";
    }
}

// --- Cakisma YOKKEN basit kullanim ---
trait SelamVerebilir {
    public function selamVer() {
        echo static::class . " diyor ki: Merhaba!\n";   // static::class -> o an calisan class'in adini verir
    }
}

class Company {
    use SelamVerebilir;
}

class Invoice {
    use SelamVerebilir;   // AYNI trait, IKI FARKLI, akraba OLMAYAN class'a eklendi - kod tekrari yok
}

(new Company())->selamVer();   // "Company diyor ki: Merhaba!"
(new Invoice())->selamVer();   // "Invoice diyor ki: Merhaba!"

echo "\n";

// --- Cakisma VARKEN cozum ---
class Urun {
    use Loglanabilir, Onbelleklenebilir {
        Loglanabilir::kaydet insteadof Onbelleklenebilir;   // cakisirsa Loglanabilir'inkini kullan
        Onbelleklenebilir::kaydet as kaydetOnbellek;         // digerini farkli isimle erisebilir yap
    }
}

$u = new Urun();
$u->kaydet();             // "Loglanabilir::kaydet calisti"
$u->kaydetOnbellek();      // "Onbelleklenebilir::kaydet calisti" - iki metoda da erisebiliyoruz
