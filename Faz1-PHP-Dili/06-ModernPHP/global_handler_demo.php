<?php

// Calistirmak icin: php -S localhost:8000 (bu klasorde)
// Sonra: http://localhost:8000/global_handler_demo.php

header('Content-Type: text/plain; charset=utf-8');

set_exception_handler(function (Throwable $e) {
    echo "GLOBAL HANDLER devreye girdi.\n";
    echo "Yakalanmamis hata: " . $e->getMessage() . "\n";
    echo "Tip: " . get_class($e) . "\n";
});

function riskliIslem() {
    throw new RuntimeException("Bu hata hicbir try/catch icinde degil");
}

riskliIslem();   // try/catch YOK - normalde script coker, ama set_exception_handler devreye girer
echo "Bu satir hic calismaz - handler devreye girdikten sonra script sonlanir.\n";
