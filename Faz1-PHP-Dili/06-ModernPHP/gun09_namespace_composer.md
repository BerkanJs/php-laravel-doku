# Gün 9 — Namespace ve Composer (Autoloading)

## Bu Derste İlk Kez Göreceğin Syntax

| Yeni syntax | Örnek | Açıklama |
|-------------|-------|----------|
| Namespace tanımı | `namespace App\Models;` | Dosyanın en üstünde — C#'taki `namespace` ile birebir aynı fikir |
| `use` ile import | `use App\Models\Sirket;` | C#'taki `using` ile birebir aynı |
| Tam nitelikli isim | `\App\Models\Sirket` | Başa `\` koyup namespace'i tam yazarak da erişilebilir |
| `require`/`require_once` | `require_once 'Sirket.php';` | Bir dosyayı elle dahil etme — Composer'dan ÖNCEKİ, ilkel yöntem |
| `composer.json` | (JSON dosyası) | Composer'ın proje tanım dosyası — PHP kodu değil, konfigürasyon |

---

## Senaryo

CRM projen büyüdükçe `Sirket`, `Musteri`, `Firsat`, `Gorev` gibi birçok class'ın olacağı belli. Bugüne kadar yazdığımız her demo dosyası **tek dosyaydı** — ama gerçek bir projede her class genelde **kendi dosyasında** yaşar. Bu class'ları birbirine bağlamak için, Composer olmadan, şöyle yazman gerekir:

```php
require_once 'Sirket.php';
require_once 'Musteri.php';
require_once 'Firsat.php';
require_once 'Gorev.php';
// ... projen büyüdükçe bu liste onlarca, yüzlerce satıra çıkar

$s = new Sirket("Acme A.Ş.");
```

Bunun sorunları: (1) her yeni class için elle bir `require` satırı eklemen gerekir — birini unutursan "Class not found" hatası alırsın, (2) iki farklı klasörde aynı isimde class olursa (`Sirket` adında iki class) çakışma olur çünkü PHP'de **namespace olmadan** tüm class'lar tek bir global alanda yaşar.

C#'ta bu sorunu `namespace` + derleyici çözer — sen sadece `using App.Models;` yazarsın, derleyici hangi assembly'de hangi class olduğunu bilir, elle dosya yolu belirtmene gerek yoktur. PHP'de aynı rahatlığı **namespace + Composer'ın PSR-4 autoloading'i** birlikte sağlar: tek bir `require 'vendor/autoload.php';` satırı yazarsın, gerisini Composer halleder.

---

## Analoji

**Namespace'siz PHP — soyadı olmayan bir köy:** Köydeki herkesin sadece adı var, soyadı yok. İki kişi "Ali" ismini taşıyorsa, birini çağırdığında hangisinin geldiği belirsizdir. `namespace App\Models;` yazmak, o class'a bir **soyadı** vermek gibidir — artık `App\Models\Sirket` ile `App\Muhasebe\Sirket` birbirinden ayrılabilir, isim çakışması olmaz.

**Composer/PSR-4 — otomatik telefon rehberi:** `require_once` ile elle dosya bağlamak, her aramak istediğinde birinin telefon numarasını **ezbere bilmen** gibidir. PSR-4 autoloading ise bir telefon rehberi gibi çalışır: "soyadı `App\Models` olan biri lazım" dediğinde, rehber (Composer) otomatik olarak doğru dosyaya bakar — sen numarayı ezberlemek zorunda değilsin.

---

## Teorik

### Namespace tanımı ve `use`

```php
// Sirket.php
namespace App\Models;      // bu dosyadaki class'lar App\Models namespace'ine ait

class Sirket {
    public function __construct(public string $ad) {}
}
```

```php
// index.php
use App\Models\Sirket;     // C#'taki "using App.Models;" ile ayni fikir

$s = new Sirket("Acme A.Ş.");   // artik sadece "Sirket" yazarak erisebiliriz
// ya da use YAZMADAN tam isimle: $s = new \App\Models\Sirket("Acme A.Ş.");
```

### Composer nedir

**Composer**, PHP'nin paket yöneticisidir — C#'taki **NuGet**'in birebir karşılığı:

| Composer | C#/.NET karşılığı |
|----------|--------------------|
| `composer.json` | `.csproj` — proje bağımlılıklarının tanımı |
| `composer.lock` | `packages.lock.json` — kilitlenen tam sürümler |
| Packagist (paket deposu) | NuGet.org |
| `composer require paket/adi` | `dotnet add package ...` |

### PSR-4 Autoloading

`composer.json` içinde şöyle bir tanım olur:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

Bu satır Composer'a şunu söyler: **"`App\` ile başlayan her namespace'i `src/` klasöründe ara."** Yani `App\Models\Sirket` class'ı, Composer'a göre `src/Models/Sirket.php` dosyasında olmalıdır — namespace'in kendisi, dosya yolunun **haritasıdır**. Bu yüzden PSR-4 olmadan (ya da yanlış klasöre koyarsan) class otomatik bulunamaz, yine elle `require` etmen gerekir.

Bu tanım yapıldıktan sonra tek satır yeter:

```php
require 'vendor/autoload.php';   // Composer'ın urettigi otomatik yukleme dosyasi

use App\Models\Sirket;
$s = new Sirket("Acme A.Ş.");    // require_once YOK - Composer dosyayi otomatik buldu
```

### `composer install` vs `composer update`

- `composer install` — `composer.lock` dosyasındaki **kilitli, tam** sürümleri kurar. CI/CD'de ve production'da her zaman bu kullanılmalı — herkes aynı sürümü alır, sürpriz olmaz.
- `composer update` — `composer.json`'daki sürüm aralıklarına göre **en güncel uyumlu** sürümleri bulur ve `composer.lock`'u günceller. Sadece bağımlılıkları bilerek güncellemek istediğinde, kontrollü şekilde çalıştırılır.

---

## C# ile Karşılaştırma

| Konu | C# | PHP |
|------|----|----|
| Namespace | `namespace App.Models;` | `namespace App\Models;` (ayırıcı `\`, `.` değil) |
| Import | `using App.Models;` | `use App\Models\Sirket;` |
| Paket yöneticisi | NuGet | Composer |
| Proje tanım dosyası | `.csproj` | `composer.json` |
| Kilit dosyası | `packages.lock.json` | `composer.lock` |
| Class bulma zamanı | Derleme zamanı (derleyici assembly'i bilir) | **Her istekte**, dosya sisteminden — derleme yok, OPcache bunu hafifletir ama ortadan kaldırmaz |

**Kritik çıkarım:** PSR-4 olmadan bir class **otomatik autoload edilemez** — o class'ı kullanmak istediğin her yerde elle `require`/`require_once` yazman gerekir. PSR-4, sadece bir "güzel olsun" kuralı değil, Composer'ın hangi dosyanın nerede olduğunu **bulabilmesinin** tek yoludur.

---

## Kod ile Göster

Bu klasörde namespace/use'un manuel `require` ile çalışan hâlini gösteren 3 dosya var (Composer kurulu olmadığı için PSR-4 autoloading'in kendisini — `vendor/autoload.php` üretimini — çalıştıramıyoruz, ama namespace/require mekanizması saf PHP olduğu için kurulum sonrası doğrudan çalışır).

Çalıştırmak için (ileride, PHP kurulduğunda):
```
cd Faz1-PHP-Dili/06-ModernPHP
php -S localhost:8000
```
sonra `http://localhost:8000/namespace_demo_index.php` adresini aç.

### 1. `namespace_demo_Sirket.php`

```php
<?php
namespace App\Models;

class Sirket {
    public function __construct(public string $ad) {}
}
```

### 2. `namespace_demo_Musteri.php`

```php
<?php
namespace App\Models;

class Musteri {
    public function __construct(public string $ad, public string $email) {}
}
```

### 3. `namespace_demo_index.php` — manuel require + use

```php
<?php
require_once __DIR__ . '/namespace_demo_Sirket.php';    // Composer OLMADAN manuel dahil etme
require_once __DIR__ . '/namespace_demo_Musteri.php';   // __DIR__ -> bu dosyanin bulundugu klasorun tam yolu

use App\Models\Sirket;     // artik "Sirket" yazarak erisebiliriz, tam isim yazmaya gerek yok
use App\Models\Musteri;

$sirket = new Sirket("Acme A.Ş.");
$musteri = new Musteri("Ali Veli", "ali@example.com");

echo $sirket->ad . "\n";                          // "Acme A.Ş."
echo $musteri->ad . " - " . $musteri->email . "\n"; // "Ali Veli - ali@example.com"

// use YAZMADAN da erisilebilirdi, tam nitelikli isimle:
$sirket2 = new \App\Models\Sirket("Beta Ltd.");
echo $sirket2->ad . "\n";
```

### 4. `composer_ornek.json` — örnek Composer/PSR-4 tanımı (referans amaçlı, çalıştırılmaz)

```json
{
    "name": "berkan/crm-app",
    "require": {
        "php": "^8.2"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

Bu dosya Faz2'de gerçek bir Laravel projesinde (`composer create-project laravel/laravel crm-app` ile) otomatik oluşacak ve genişleyecek — burada sadece PSR-4 eşlemesinin nasıl göründüğünü referans olarak tutuyoruz.

---

## Kendini Test Et

1. `composer install` ile `composer update` arasındaki fark ne, CI/CD'de hangisi kullanılmalı?
2. PSR-4 olmadan bir class autoload edilebilir mi?
