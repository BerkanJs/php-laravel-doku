<?php

// Calistirmak icin: php -S localhost:8000 (bu klasorde)
// Sonra: http://localhost:8000/namespace_demo_index.php

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/namespace_demo_Sirket.php';    // Composer OLMADAN manuel dahil etme
require_once __DIR__ . '/namespace_demo_Musteri.php';   // __DIR__ -> bu dosyanin bulundugu klasorun tam yolu

use App\Models\Sirket;     // artik "Sirket" yazarak erisebiliriz, tam isim yazmaya gerek yok
use App\Models\Musteri;

$sirket = new Sirket("Acme A.S.");
$musteri = new Musteri("Ali Veli", "ali@example.com");

echo $sirket->ad . "\n";                            // "Acme A.S."
echo $musteri->ad . " - " . $musteri->email . "\n"; // "Ali Veli - ali@example.com"

// use YAZMADAN da erisilebilirdi, tam nitelikli isimle:
$sirket2 = new \App\Models\Sirket("Beta Ltd.");
echo $sirket2->ad . "\n";
