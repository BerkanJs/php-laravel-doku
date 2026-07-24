<?php
declare(strict_types=1);   // bu dosyanin en ustunde olmali - dosyadaki fonksiyon cagrilarinda tip zorlamasi acilir

// function ad(tip $parametre): donusTipi { ... } -> bu minimal fonksiyon syntax'ini Gun 3'te detayli isleyecegiz
function ikiyleCarp(int $sayi): int
{
    return $sayi * 2;
}

header('Content-Type: text/plain; charset=utf-8');

echo "ikiyleCarp(5) = " . ikiyleCarp(5) . "\n";   // 10 - int gonderdik, sorun yok

echo "\nSimdi string gonderiyoruz: ikiyleCarp(\"5\")\n";
echo "declare(strict_types=1) ACIKKEN bu satir TypeError firlatir ve script burada durur.\n";
echo "(declare(strict_types=1) OLMASAYDI -> PHP \"5\"'i sessizce 5'e cevirir, hata vermezdi)\n\n";

echo ikiyleCarp("5");   // TypeError firlatir! "5" bir string, strict_types acikken PHP bunu int'e CEVIRMEZ

// Asagidaki satir yukaridaki hata yuzunden hic calismaz - sadece bilgi icin:
// var_dump("5" == 5);   // yine bool(true) olurdu - strict_types bunu degistirmez, == hala loose calisir
