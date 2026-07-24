<?php

// Calistirmak icin: php -S localhost:8000 (bu klasorde)
// Sonra: http://localhost:8000/scope_demo.php - F5 ile birkac kez yenile

header('Content-Type: text/plain; charset=utf-8');

$mesaj = "dis scope";

function scopeGoster() {
    // $mesaj burada YOK - fonksiyon ici scope disaridakinden tamamen izole
    echo $mesaj ?? "tanimsiz (dis scope'a erisim yok)";
    echo "\n";
}
scopeGoster();   // "tanimsiz (dis scope'a erisim yok)"

function globalIleEris() {
    global $mesaj;   // global -> disaridaki $mesaj'a ACIKCA erisim izni verir
    echo $mesaj . "\n";
}
globalIleEris();   // "dis scope"

function sayacArtir() {
    static $sayi = 0;   // static local degisken - fonksiyon COKLU CAGRILAR arasinda deger korur (AYNI istek/process icinde)
    $sayi++;
    return $sayi;
}
echo sayacArtir() . "\n";   // 1
echo sayacArtir() . "\n";   // 2 - ayni script calismasi icinde ikinci cagri, deger korundu
echo sayacArtir() . "\n";   // 3

echo "\nBu sayfayi F5 ile yenile: sayac yine 1'den baslayacak.\n";
echo "Neden: F5 = YENI bir HTTP istegi = YENI bir process/worker calistirmasi.\n";
echo "static local degisken sadece AYNI calistirma icindeki cagrilar arasinda deger korur,\n";
echo "istekler arasinda korumaz - Gun 1'in 'istekler arasi hicbir sey kalici degil' kurali burada da gecerli.\n";
