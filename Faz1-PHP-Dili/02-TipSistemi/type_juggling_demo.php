<?php

// Calistirmak icin: php -S localhost:8000 (bu klasorde)
// Sonra: http://localhost:8000/type_juggling_demo.php

header('Content-Type: text/plain; charset=utf-8');

echo "--- Otomatik tip donusumu (type juggling) ---\n";

var_dump("5" + 3);        // int(8)     - "+" operatoru string'i sayiya cevirir
var_dump("5" . 3);        // string(2) "53"  - "." operatoru ikisini de string yapar, ARITMETIK DEGIL BIRLESTIRMEDIR
                           // bunu "+" ile karistirirsak (C#'taki + hem toplama hem concat yapar) yanlis sonuc aliriz

echo "\n--- == (loose) vs === (strict) ---\n";

var_dump("10" == "1e1");   // bool(true) - ikisi de "sayisal string", PHP ikisini de sayiya cevirip karsilastirir: 10 == 10
                           // === kullansaydik -> false olurdu, cunku string olarak "10" ile "1e1" birebir ayni degil
var_dump("10" === "1e1");  // bool(false)

var_dump("5" == 5);        // bool(true)  - == donusum yapar
var_dump("5" === 5);       // bool(false) - === donusum yapmaz, string ile int asla esit sayilmaz

echo "\n--- Magic hash: gercek bir guvenlik acigi modeli ---\n";

var_dump("0e123" == "0e456");  // bool(true)  - ikisi de bilimsel gosterim (0 x 10^...) olarak yorumlanir, ikisi de 0'a esit
var_dump("0e123" === "0e456"); // bool(false) - === string'leri harf harf karsilastirir, birbirinden farkli
                               // SIFRE/HASH KARSILASTIRMASINDA HER ZAMAN === KULLAN
