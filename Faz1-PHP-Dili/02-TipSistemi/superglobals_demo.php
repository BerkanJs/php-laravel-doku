<?php

// Çalıştırmak için: php -S localhost:8000 (bu klasörde)
// Sonra: http://localhost:8000/superglobals_demo.php?ad=Berkan adresini aç, F5 ile yenile
// $_SESSION['ziyaret'] her yenilemede artacak - sayac_demo.php'deki düz değişkenin aksine.

session_start();                            // session_start() çağrılmadan $_SESSION kullanılamaz - PHP bunu otomatik başlatmaz
                                             // bunu unutursak -> $_SESSION'a yazdığımız veri bir sonraki istekte kaybolur

// $_GET - URL query string'inden gelen veri (?ad=Berkan gibi)
// ?? işareti "null coalescing operatörü": solundaki değer yoksa/null ise sağındaki varsayılan değeri kullanır
// yani "$_GET['ad'] varsa onu al, yoksa 'belirtilmedi' yaz" demek - detaylı tip/hata konuları Gün 2'de
$adGet = $_GET['ad'] ?? 'belirtilmedi';     // ?? kullanmasaydık -> key yoksa "Undefined array key" warning'i alırdık

// $_POST - form/body üzerinden gönderilen veri (URL'de görünmez, GET'ten farkı budur)
$adPost = $_POST['ad'] ?? 'form gönderilmedi';

// $_SERVER - istek ve sunucu hakkında meta veri (C#'taki HttpContext.Request/Connection karışımı)
$metod = $_SERVER['REQUEST_METHOD'];        // GET mi POST mu - C#'ta Request.Method karşılığı

// $_SESSION - istekler arası KALICI veri (static property'nin aksine!)
$_SESSION['ziyaret'] = ($_SESSION['ziyaret'] ?? 0) + 1;
                                             // Bu sayaç F5 ile ARTAR (static property'nin tersine)
                                             // çünkü session verisi process belleğinde değil, PHP'nin session storage'ında (varsayılan: sunucu diskinde bir dosya) tutulur

// $_COOKIE - tarayıcıya yazılan, her istekte tarayıcının geri gönderdiği veri
$cerezVarMiydi = isset($_COOKIE['test_cerez']);
setcookie('test_cerez', 'evet', time() + 3600);   // 1 saat geçerli çerez yazar; time() olmadan hemen expire olurdu

// $_FILES - yüklenen dosyalar (detaylı işleniş Gün27'de, File Upload konusunda)
$dosyaVarMi = !empty($_FILES);

header('Content-Type: text/plain; charset=utf-8');

echo "GET ad: $adGet\n";
echo "POST ad: $adPost\n";
echo "Metod: $metod\n";
echo "Session ziyaret sayısı: {$_SESSION['ziyaret']}\n";
echo "Çerez daha önce var mıydı: " . ($cerezVarMiydi ? 'evet' : 'hayır') . "\n";
echo "Dosya yüklendi mi: " . ($dosyaVarMi ? 'evet' : 'hayır') . "\n";
