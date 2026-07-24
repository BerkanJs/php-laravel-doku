<?php

// Calistirmak icin: php -S localhost:8000 (bu klasorde)
// Sonra: http://localhost:8000/static_ve_late_binding_demo.php

header('Content-Type: text/plain; charset=utf-8');

class Sayac {
    public static int $toplam = 0;

    public static function arttir(): int {
        return ++self::$toplam;    // self:: - static property'e class icinden erisim
    }
}

echo Sayac::arttir() . "\n";   // 1 - instance olusturmadan cagrildi
echo Sayac::arttir() . "\n";   // 2

echo "\n";

class UstSinif {
    public static function olustur(): static {
        return new static();       // static:: -> CAGIRAN class'i kullanir
    }

    public static function olusturSelf(): self {
        return new self();         // self:: -> her zaman UstSinif uretir
    }
}

class AltSinif extends UstSinif {}

echo get_class(AltSinif::olustur()) . "\n";       // "AltSinif" - static:: cagirani dikkate aldi
echo get_class(AltSinif::olusturSelf()) . "\n";    // "UstSinif" - self:: yazildigi class'a sadik kaldi
