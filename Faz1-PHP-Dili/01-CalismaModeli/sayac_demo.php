<?php

// Calistirmak icin: php -S localhost:8000 (bu klasorde)
// Sonra: http://localhost:8000/sayac_demo.php adresini birkac kez yenile (F5)
// Beklenen sonuc: Sayac HER ZAMAN 1 gosterir - asla 2, 3 olmaz.
// Not: burada class/static gibi ileri OOP konulari YOK - sadece duz bir degisken.
//      Ayni "istekler arasi hicbir sey kalici degil" kurali, Gun 6/8'de gorecegimiz
//      class'lar ve static property'ler icin de gecerli olacak.

$sayac = 1;         // $  -> PHP'de her degisken $ ile baslar (C#'ta boyle bir isaret yok)
                    // =  -> atama operatoru, C#'taki ile ayni
                    // ;  -> her satir (statement) noktali virgul ile biter, unutulursa hata alinir

echo "Bu istekteki sayac degeri: $sayac\n";
// echo -> ekrana/response'a yazdirir (C#'taki Console.WriteLine/Response.Write karisimi)
// "..." icinde $sayac otomatik olarak degeriyle degistirilir (buna "string interpolation" denir, Gun 5'te detayli islenecek)
// \n -> yeni satir (C#'taki \n ile ayni)

echo "Sayfayi yenile (F5) - her seferinde '1' goreceksin, '2', '3' degil.\n";
echo "Neden: Her istek PHP-FPM'de (veya php -S'de) YENI bir process/worker'da calisir.\n";
echo "Bu script bastan calisir - bir onceki isteğin \$sayac degeri hafizada YOK.\n\n";

echo "Kalici bir sayac istersen bu degisken YETMEZ, sunlardan biri gerekir:\n";
echo "- Dosyaya yaz/oku (basit ama concurrency riski tasir)\n";
echo "- Redis INCR komutu (production standardi, atomik artis garantisi verir)\n";
echo "- Veritabanina UPDATE (garanti ama en yavas secenek)\n";
