# Gün 12 — Composer Paketleri ve PHP Ekosistemi

> Bugün yeni bir PHP dil syntax'ı yok — bugünün konusu, Composer ile kurulan **hazır kütüphaneler** ve **geliştirme araçları**. Bu paketler bu makinede kurulu olmadığı için (Composer henüz kurulmadı) kod örnekleri **referans amaçlıdır** — Faz2'de gerçek bir Laravel projesinde bunları gerçekten kurup çalıştıracağız.

---

## Senaryo

CRM'de bir fırsatın (deal) "3 iş günü içinde takip edilmesi gerekiyor" kuralını uygulaman istendi. PHP'nin kendi (native) `DateTime` sınıfıyla:

```php
$tarih = new DateTime();
$tarih->modify('+3 days');
echo $tarih->format('Y-m-d');
```

Bu çalışır ama iş günü hesaplama, zaman dilimi yönetimi, okunabilir karşılaştırmalar (`$tarih1->isPast()` gibi) native `DateTime` ile çok daha fazla kod ister. **Carbon** paketi (Composer ile kurulan bir kütüphane) bunu çok daha okunabilir hale getirir:

```php
use Carbon\Carbon;

$tarih = Carbon::now()->addDays(3);
echo $tarih->format('Y-m-d');
echo $tarih->isPast() ? 'geçmiş' : 'gelecek';
```

Bu, bugünün konusu: PHP'nin **çekirdek dili küçük** tutulmuş, ama Composer/Packagist ekosistemi üzerinden bu boşluk hazır paketlerle dolduruluyor — tıpkı C#'ta NuGet üzerinden `Newtonsoft.Json` ya da `Polly` kurman gibi.

---

## Teorik

### Carbon — tarih/saat kütüphanesi

C#'taki `DateTime`/`DateOnly`'nin PHP karşılığı, PHP'nin native `DateTime`'ını **sarmalayan (wrap eden)** bir kütüphane:

```php
use Carbon\Carbon;

Carbon::now();                          // şu an
Carbon::now()->addDays(3);              // 3 gün sonrası
Carbon::parse('2026-08-01')->diffForHumans();  // "3 hafta sonra" gibi okunabilir çıktı
```

### Guzzle — HTTP client

C#'taki `HttpClient`'ın PHP karşılığı — dış bir API'ye istek atarken kullanılır:

```php
use GuzzleHttp\Client;

$client = new Client();
$response = $client->get('https://api.example.com/companies');
$data = json_decode($response->getBody(), true);
```

### `.env` dosyası ve `vlucas/phpdotenv`

Veritabanı şifresi, API anahtarı gibi ortam bazlı ayarlar koda **gömülmemeli** — bunun için `.env` dosyası kullanılır:

```
# .env dosyası
DB_HOST=localhost
DB_PASSWORD=gizli_sifre
```

```php
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
echo $_ENV['DB_HOST'];   // "localhost"
```

Laravel bu paketi (`vlucas/phpdotenv`) zaten içinde barındırır — Faz2'de `.env` dosyasını doğrudan kullanacağız, elle kurmamıza gerek kalmayacak.

### PHPStan / Psalm — static analysis

PHP'de derleme zamanı olmadığı için (Gün 1'i hatırla — her istekte yorumlanır), tip hataları normalde ancak **çalışma zamanında** ortaya çıkar. PHPStan/Psalm, kodu **çalıştırmadan** analiz ederek bu hataların bir kısmını önceden yakalar:

```
vendor/bin/phpstan analyse src/
```

### PHP-CS-Fixer — code style

C#'taki `dotnet format`'ın karşılığı — kod stilini (girinti, boşluk, tırnak tipi vb.) otomatik standartlaştırır.

---

## C# ile Karşılaştırma

| Konu | C# | PHP |
|------|----|----|
| Tarih/saat | `DateTime`/`DateOnly` (native, zaten yeterli) | Native `DateTime` yetersiz kalır, **Carbon** paketiyle tamamlanır |
| HTTP client | `HttpClient` (native) | Native `curl` fonksiyonları düşük seviyeli, **Guzzle** ile sarmalanır |
| Ayar yönetimi | `appsettings.json` + `IConfiguration` | `.env` + `phpdotenv` |
| Static analysis | Roslyn analyzers, derleme zamanında zaten güçlü tip kontrolü var | PHPStan/Psalm — **kısmi alternatif**, ama derleme zamanı olmadığı için PHP'nin "compile-time güvenliği" C# seviyesine **asla ulaşmaz** — bu kalıcı bir mimari fark |
| Kod formatlama | `dotnet format` | PHP-CS-Fixer |

**Kritik çıkarım:** C#'ta çoğu tip hatası derleme anında yakalanır — yanlış tipte bir parametre gönderirsen proje **derlenmez bile**. PHP'de derleme zamanı olmadığı için bu hatalar ya **runtime'da** (script çalışırken, örn. Gün 2'deki `TypeError` gibi) ya da **PHPStan/Psalm gibi ayrı bir statik analiz aracı çalıştırılırsa** (CI/CD pipeline'ında, kod çalıştırılmadan önce) yakalanabilir. PHPStan hiçbir zaman Roslyn'in verdiği garantiyi veremez çünkü PHP'nin kendisi derlenmiyor.

---

## Kod ile Göster

Bu paketler (Carbon, Guzzle, phpdotenv, PHPStan) bu makinede kurulu değil (Composer henüz kurulmadı) — bu yüzden burada **referans amaçlı, kurulunca çalışacak** örnekler bırakıyoruz. Faz2'de gerçek bir Laravel projesi kurulunca bu paketler zaten hazır gelecek ya da `composer require` ile eklenecek.

### `paket_ornekleri_referans.php` — Composer kurulunca çalışacak örnek kullanım

```php
<?php
require 'vendor/autoload.php';   // Composer autoload - Gün 9'da gördük

use Carbon\Carbon;
use GuzzleHttp\Client;

// --- Carbon ornegi ---
$simdi = Carbon::now();
$ucGunSonra = $simdi->copy()->addDays(3);   // copy() olmadan addDays orijinali de degistirirdi (mutable nesne)
echo $ucGunSonra->format('Y-m-d') . "\n";
echo $ucGunSonra->diffForHumans() . "\n";     // "3 gun sonra" gibi okunabilir cikti

// --- Guzzle ornegi ---
$client = new Client();
$response = $client->get('https://api.example.com/companies');
$data = json_decode($response->getBody(), true);
print_r($data);

// --- .env ornegi ---
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
echo $_ENV['DB_HOST'] . "\n";
```

Bu paketleri gerçekten kurmak için (ileride Composer kurulunca):
```
composer require nesbot/carbon guzzlehttp/guzzle vlucas/phpdotenv
composer require --dev phpstan/phpstan friendsofphp/php-cs-fixer
```

---

## Kendini Test Et

1. PHP'de derleme zamanı tip kontrolü olmadığı için hangi hatalar ancak runtime'da/PHPStan ile yakalanabilir?
