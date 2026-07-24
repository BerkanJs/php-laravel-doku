<?php

// Calistirmak icin: php -S localhost:8000 (bu klasorde)
// Sonra: http://localhost:8000/class_ve_constructor_demo.php

header('Content-Type: text/plain; charset=utf-8');

class CompanyEski {
    private string $ad;

    public function __construct(string $ad) {
        $this->ad = $ad;              // klasik yontem - parametreyi elle property'e ata
    }

    public function adiGetir(): string {
        return $this->ad;
    }
}

class Company {
    public function __construct(
        private string $ad,                      // constructor property promotion (PHP 8+)
        private string $sehir = "Istanbul"        // default parametre burada da calisir
    ) {}
    // govde BOS - $ad ve $sehir otomatik property oldu

    public function adiGetir(): string {
        return $this->ad;
    }

    public function bilgiVer(): string {
        return "{$this->ad} - {$this->sehir}";
    }
}

$c1 = new CompanyEski("Acme A.S.");
echo $c1->adiGetir() . "\n";

$c2 = new Company("Beta Ltd.");
echo $c2->bilgiVer() . "\n";          // "Beta Ltd. - Istanbul" - default sehir kullanildi

$c3 = new Company("Gamma A.S.", "Ankara");
echo $c3->bilgiVer() . "\n";          // "Gamma A.S. - Ankara"
