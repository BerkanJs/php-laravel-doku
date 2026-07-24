<?php

// Calistirmak icin: php -S localhost:8000 (bu klasorde)
// Sonra: http://localhost:8000/magic_methods_demo.php

header('Content-Type: text/plain; charset=utf-8');

class DinamikModel {
    private array $veri = [];

    public function __get($ad) {
        echo "[__get cagrildi: $ad]\n";
        return $this->veri[$ad] ?? null;
    }

    public function __set($ad, $deger) {
        echo "[__set cagrildi: $ad = $deger]\n";
        $this->veri[$ad] = $deger;
    }

    public function __call($ad, $args) {
        return "Cagrilan metot: $ad, parametreler: " . implode(', ', $args);
    }

    public static function __callStatic($ad, $args) {
        return "Static cagrilan metot: $ad";
    }

    public function __toString(): string {
        return "DinamikModel(" . implode(',', $this->veri) . ")";
    }

    public function __invoke() {
        return "Nesne bir fonksiyon gibi cagrildi!";
    }
}

$model = new DinamikModel();

$model->ad = "Ali";           // __set devreye girer - "ad" diye bir property TANIMLI DEGIL
echo $model->ad . "\n";        // __get devreye girer - "Ali" doner

echo "\n";

echo $model->herhangiBirMetot("param1", "param2") . "\n";   // __call devreye girer - metot TANIMLI DEGIL
echo DinamikModel::herhangiBirStaticMetot() . "\n";          // __callStatic devreye girer

echo "\n";

echo $model . "\n";    // __toString devreye girer (echo bir nesneyi string'e cevirmeye calisir)

echo $model() . "\n";   // __invoke devreye girer - nesne fonksiyon gibi cagrildi
