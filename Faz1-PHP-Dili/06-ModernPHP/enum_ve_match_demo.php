<?php

// Calistirmak icin: php -S localhost:8000 (bu klasorde)
// Sonra: http://localhost:8000/enum_ve_match_demo.php

header('Content-Type: text/plain; charset=utf-8');

enum Durum: string {
    case Aktif = 'aktif';
    case Pasif = 'pasif';
    case Beklemede = 'beklemede';

    public function etiket(): string {           // enum method tasiyabilir - C#'ta enum'lar bunu yapamaz
        return match($this) {
            Durum::Aktif => 'Aktif Kullanici',
            Durum::Pasif => 'Pasif Kullanici',
            Durum::Beklemede => 'Onay Bekliyor',
        };
    }
}

$d = Durum::Aktif;
echo $d->value . "\n";      // "aktif" - backed enum'un tasidigi gercek deger
echo $d->etiket() . "\n";   // "Aktif Kullanici" - enum'un kendi metodu

echo "\n";

$kod = 2;

// switch - eski yontem, fall-through riski var, == (loose) karsilastirir
switch ($kod) {
    case 1:
        $sonucSwitch = "bir";
        break;
    case 2:
        $sonucSwitch = "iki";
        break;
    default:
        $sonucSwitch = "diger";
}
echo "switch sonucu: $sonucSwitch\n";

// match - modern yontem, DEGER DONDURUR, === (strict) karsilastirir, fall-through YOK
$sonucMatch = match($kod) {
    1 => "bir",
    2 => "iki",
    default => "diger",
};
echo "match sonucu: $sonucMatch\n";
