<?php
declare(strict_types=1);

// Calistirmak icin: php -S localhost:8000 (bu klasorde)
// Sonra: http://localhost:8000/try_catch_temelleri_demo.php

header('Content-Type: text/plain; charset=utf-8');

function ikiyleCarp(int $sayi): int {
    return $sayi * 2;
}

// --- YANLIS ornek (calistirilirsa script'i durdurur, bu yuzden yorumda birakildi) ---
// try {
//     echo ikiyleCarp("5") . "\n";
// } catch (\Exception $e) {
//     echo "Bu satir hic calismaz - TypeError bir Exception degil\n";
// }
// Yukaridaki blok YETERSIZ kalir - catch(Exception) bir TypeError'i yakalamaz, script coker.

// --- DOGRU: catch (Throwable) hem Error hem Exception'i yakalar ---
try {
    echo ikiyleCarp("5") . "\n";
} catch (\Throwable $e) {
    echo "Hata yakalandi: " . $e->getMessage() . " (tip: " . get_class($e) . ")\n";
}

echo "\n";

// --- Custom exception ---
class ValidationException extends Exception {}

function yasKontrolEt(int $yas): void {
    if ($yas < 0) {
        throw new ValidationException("Yas negatif olamaz: $yas");
    }
}

try {
    yasKontrolEt(-5);
} catch (ValidationException $e) {
    echo "Validasyon hatasi: " . $e->getMessage() . "\n";
} finally {
    echo "finally HER DURUMDA calisir (hata olsa da olmasa da)\n";
}
