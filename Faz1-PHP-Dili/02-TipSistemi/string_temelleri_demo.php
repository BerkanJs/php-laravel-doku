<?php

// Calistirmak icin: php -S localhost:8000 (bu klasorde)
// Sonra: http://localhost:8000/string_temelleri_demo.php

header('Content-Type: text/plain; charset=utf-8');

$isim = "Ali";

echo "Merhaba $isim\n";         // "Merhaba Ali" - cift tirnak interpolate eder
echo 'Merhaba $isim' . "\n";    // "Merhaba $isim" - tek tirnak OLDUGU GIBI yazdirir

$heredoc = <<<EOT
Merhaba $isim,
Bu cok satirli bir metin.
EOT;
echo $heredoc . "\n\n";    // Ali'nin adi interpolate edildi

$nowdoc = <<<'EOT'
Merhaba $isim,
Bu metin OLDUGU GIBI kalir.
EOT;
echo $nowdoc . "\n\n";     // $isim interpolate EDILMEDI, oldugu gibi yazildi

var_dump(str_contains("Merhaba Dunya", "Dunya"));      // true
var_dump(str_starts_with("Merhaba Dunya", "Merhaba")); // true

$parcalar = explode(",", "elma,armut,cilek");
print_r($parcalar);                                     // ["elma", "armut", "cilek"]
echo implode(" | ", $parcalar) . "\n";                   // "elma | armut | cilek"

echo sprintf("Toplam: %d TL\n", 150);                    // "Toplam: 150 TL"
