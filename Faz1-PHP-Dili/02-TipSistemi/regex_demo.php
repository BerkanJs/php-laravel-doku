<?php

// Calistirmak icin: php -S localhost:8000 (bu klasorde)
// Sonra: http://localhost:8000/regex_demo.php

header('Content-Type: text/plain; charset=utf-8');

var_dump(preg_match('/^[0-9]+$/', "12345"));   // int(1) - eslesti, sadece rakam
var_dump(preg_match('/^[0-9]+$/', "123a5"));   // int(0) - eslesmedi, harf iceriyor

$sonuc = preg_replace('/[0-9]/', '#', "Sifre123");
echo $sonuc . "\n";                             // "Sifre###" - her rakami # ile degistirdi

// E-posta formatina yakin basit bir kontrol (production icin filter_var daha uygun, burasi sadece regex ornegi):
var_dump(preg_match('/^[^@]+@[^@]+\.[^@]+$/', "test@example.com"));   // int(1)
var_dump(preg_match('/^[^@]+@[^@]+\.[^@]+$/', "gecersiz-adres"));     // int(0)
