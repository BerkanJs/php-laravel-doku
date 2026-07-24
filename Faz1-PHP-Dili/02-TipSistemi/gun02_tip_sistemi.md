# Gün 2 — Değişkenler, Tipler ve Type Juggling

## Bu Derste İlk Kez Göreceğin Syntax

| Yeni syntax | Örnek | Açıklama |
|-------------|-------|----------|
| Fonksiyon çağırma | `gettype($x)` | PHP'nin hazır (built-in) bir fonksiyonunu çağırma. Kendi fonksiyonunu yazmayı Gün 3'te göreceğiz — burada sadece var olan bir fonksiyonu kullanıyoruz, C#'ta `Console.WriteLine(x)` çağırmak gibi düşün. |
| `??` (null coalescing) | `$_GET['ad'] ?? 'yok'` | Sol taraf tanımsız/null ise sağ taraftaki varsayılanı kullanır. |
| `declare(strict_types=1);` | dosyanın en üstüne yazılır | Bu dosyadaki fonksiyon çağrılarında tip zorlamasını açar (aşağıda detaylı). |

---

## Senaryo

2015 civarında birçok gerçek PHP uygulamasında şu koda benzer bir şifre kontrolü vardı:

```php
if ($kullanicidanGelenHash == $veritabanindakiHash) {
    // giriş başarılı
}
```

Geliştirici `==` kullanmış — "eşit mi" demek istemiş, gayet masum görünüyor. Ama bazı hash değerleri `"0e12345"` gibi `0e` ile başlayan rakamlardan oluşuyorsa, PHP bunu **bilimsel gösterim (scientific notation)** olarak yorumluyor: `0e12345` = `0 × 10^12345` = **0**. Yani `"0e12345" == "0e99999"` ifadesi `true` döner — çünkü PHP ikisini de sayıya çevirip `0 == 0` olarak karşılaştırır, iki farklı hash aynı sanılır!

Bu, **type juggling magic hash** olarak bilinen gerçek bir güvenlik açığıydı (birçok CTF ve gerçek sızma testinde kullanıldı). Kök neden: `==` operatörü, karşılaştırdığı iki string "sayı gibi görünüyorsa" onları **sayıya çevirip** karşılaştırır — sen bunu istemesen bile.

C#'ta böyle bir şey olmaz: `"0e12345" == "0e99999"` her zaman string karşılaştırması yapar, tip dönüşümü yaşanmaz. PHP'de ise bu, dilin en temel davranışlarından biri — bugünün konusu tam olarak bu: **PHP tipleri nasıl ele alıyor, ne zaman sessizce dönüştürüyor, bundan nasıl kaçınırsın.**

---

## Analoji

**C#'ta sınır kapısı:** Sıkı bir pasaport kontrolü düşün. Pasaportun (değişkenin tipi) beklenen formatta değilse sınır memuru (derleyici) seni **derleme zamanında** geri çevirir. `"5" + 3` yazarsan derleyici ya hata verir ya da bunu açıkça string birleştirme olarak yorumlar — belirsizlik yok.

**PHP'de gümrük memuru:** Aynı kapıda bu sefer çok "yardımsever" bir memur var. Elindeki pasaport sayı gibi görünüyorsa (`"5"`), seni otomatik olarak sayı sınıfına sokuyor, aritmetik yapabiliyorsun (`"5" + 3` → `8`). Bu genelde işine yarar — ama bazen memur öyle esnek davranıyor ki, aslında birbirinden tamamen farklı iki şeyi (`"0e123"` ile `"0e456"`, ya da `"abc"` ile `0`) "aynı" sayıp seni yanlış geçiriyor. `===` kullanmak, memura "hayır, pasaportun TÜRÜNÜ de kontrol et, sadece görünüşe bakma" demektir.

---

## Teorik

### Değişken tanımlama ve dinamik tipleme

PHP'de bir değişkeni `$` ile tanımlarsın, tip belirtmene gerek yoktur — tip, atadığın değere göre **çalışma zamanında (runtime)** belirlenir:

```php
$sayi = 5;        // int
$sayi = "beş";    // aynı değişken şimdi string - PHP buna izin verir, C#'ta bu derleme hatası olurdu
```

C#'ta `var sayi = 5;` yazsan bile `sayi` derleme zamanında `int` olarak **sabitlenir** — sonra ona string atayamazsın. PHP'de değişkenin tipi hayatı boyunca değişebilir.

### Tipler

| Kategori | Tipler |
|----------|--------|
| Scalar (tekil değer) | `int`, `float`, `string`, `bool` |
| Compound (bileşik) | `array`, `object` |
| Special (özel) | `null`, `resource` |

Bir değişkenin o an hangi tipte olduğunu görmek için `gettype($x)` veya daha detaylı `var_dump($x)` kullanılır (kod örneklerinde göreceğiz).

### Type Juggling (otomatik tip dönüşümü)

PHP, farklı tipleri bir işlemde (`+`, `==`, `.` vb.) bir arada gördüğünde, elinden geldiğince onları **birbirine uydurmaya çalışır** — buna type juggling denir:

```php
"5" + 3      // 8        - string "5" sayıya çevrilir, aritmetik yapılır
"5" . 3      // "53"     - . (birleştirme) operatörü ikisini de string yapar
"5" == 5     // true     - == sayısal karşılaştırma yapar, string'i sayıya çevirir
```

### `==` (loose comparison) vs `===` (strict comparison)

- `==` **değer** eşitliğine bakar, gerekirse tip dönüştürür ("loose" - gevşek)
- `===` hem **değer** hem **tip** eşitliğine bakar, dönüşüm yapmaz ("strict" - katı)

```php
"5" == 5     // true   - tipler farklı (string vs int) ama == dönüştürüp karşılaştırır
"5" === 5    // false  - tipler farklı, === dönüşüm yapmaz, direkt false
```

PHP'nin en meşhur tuzağı budur — yukarıdaki senaryodaki magic hash açığının kök nedeni de `==`'in bu davranışı.

### `declare(strict_types=1)`

Fonksiyon parametrelerinde tip belirtebilirsin (`function f(int $x)`). Varsayılan olarak PHP, çağırırken uymayan bir tip gelirse (örn. `"5"` string'i) sessizce `int`'e çevirip kabul eder ("weak typing"). Dosyanın en başına `declare(strict_types=1);` yazarsan, bu dönüşüm **kapanır** — uymayan tip gelirse `TypeError` fırlatılır.

> Önemli: `strict_types` sadece **fonksiyon çağrılarını** etkiler, `==` gibi operatörleri etkilemez. `"5" == 5` `strict_types=1` açıkken bile yine `true` döner — bunun için `===` kullanman gerekir. Bu ikisi birbirinden bağımsız iki mekanizma.

### Superglobals

PHP'de bazı diziler her yerden (herhangi bir fonksiyon/dosya içinden, `global` yazmadan) erişilebilir — bunlara **superglobal** denir:

- `$_GET` — URL query string (`?ad=Berkan`)
- `$_POST` — form/body verisi
- `$_SERVER` — istek/sunucu meta verisi
- `$_SESSION` — istekler arası kalıcı veri (Gün 1'de bahsettiğimiz "kural dışı" yapı)
- `$_COOKIE` — tarayıcıya yazılan veri
- `$_FILES` — yüklenen dosyalar (Gün 27'de detaylı)

---

## C# ile Karşılaştırma

| Konu | C# | PHP |
|------|----|----|
| Tip belirleme zamanı | Derleme zamanı (statik) | Çalışma zamanı (dinamik) |
| `"5" + 3` | Derleme hatası ya da string concat (belirsizlik yok) | `8` — otomatik sayıya çevrilir |
| `==` | Her zaman tip-güvenli (aynı tip için) | Tipler farklıysa dönüşüm yapar |
| Nullable | `string?` ile açıkça işaretlenir | Her değişken varsayılan olarak null olabilir |
| Tip zorlama | Her zaman zorunlu | `declare(strict_types=1)` ile opsiyonel açılır |

---

## Kritik Tuzak

```php
if ($password == "0") { ... }
```

Kullanıcı şifre olarak `"abc"` girse bile, **PHP 7 ve öncesinde** `"abc" == 0` ifadesi `true` dönebiliyordu (string sayıya çevrilemeyince 0 kabul ediliyordu). **PHP 8'de bu davranış değişti** — artık `0` sayısı string'e çevrilip `"abc" == "0"` olarak karşılaştırılıyor, bu da `false` veriyor. Yani PHP 8 bu spesifik tuzağı kısmen düzeltti, ama genel kural değişmedi: **iki farklı tipi `==` ile karşılaştırıyorsan, PHP'nin hangi yöne dönüşüm yapacağını ezbere bilmen gerekir.** Bu yüzden pratik kural her zaman aynı: **karşılaştırmada `===` kullan**, PHP versiyonuna güvenme.

---

## Kod ile Göster

Çalıştırmak için (ileride, PHP kurulduğunda):
```
cd Faz1-PHP-Dili/02-TipSistemi
php -S localhost:8000
```

### 1. `tip_gosterme_demo.php` — bir değişkenin tipini görmek

```php
$sayi = 5;
$ondalik = 5.5;
$metin = "merhaba";
$dogruMu = true;
$dizi = [1, 2, 3];
$bosDeger = null;

echo gettype($sayi) . "\n";       // "integer"  - gettype() bir built-in fonksiyon, değişkenin tipini string olarak döner
echo gettype($ondalik) . "\n";    // "double"   - PHP'de float'ın iç adı "double"dır (tarihsel sebep, C'den miras)
echo gettype($metin) . "\n";      // "string"
echo gettype($dogruMu) . "\n";    // "boolean"
echo gettype($dizi) . "\n";       // "array"
echo gettype($bosDeger) . "\n";   // "NULL"

var_dump($sayi);      // int(5)              - var_dump hem tipi hem değeri birlikte gösterir, gettype'tan daha detaylı
var_dump($metin);     // string(8) "merhaba" - 8 -> string'in byte uzunluğu
```

### 2. `type_juggling_demo.php` — otomatik dönüşüm ve `==` vs `===`

```php
var_dump("5" + 3);        // int(8)     - "+" operatörü string'i sayıya çevirir
var_dump("5" . 3);        // string(2) "53"  - "." operatörü ikisini de string yapar, ARİTMETİK DEĞİL BİRLEŞTİRMEDİR
                           // bunu "+" ile karıştırırsak (C#'taki + hem toplama hem concat yapar) yanlış sonuç alırız

var_dump("10" == "1e1");  // bool(true) - ikisi de "sayısal string", PHP ikisini de sayıya çevirip karşılaştırır: 10 == 10
                          // === kullansaydık -> false olurdu, çünkü string olarak "10" ile "1e1" birebir aynı değil

var_dump("5" == 5);       // bool(true)  - == dönüşüm yapar
var_dump("5" === 5);      // bool(false) - === dönüşüm yapmaz, string ile int asla eşit sayılmaz

// Yukarıdaki senaryodaki "magic hash" açığının küçük bir modeli:
var_dump("0e123" == "0e456"); // bool(true)  - ikisi de bilimsel gösterim (0 x 10^...) olarak yorumlanır, ikisi de 0'a eşit
var_dump("0e123" === "0e456"); // bool(false) - === string'leri harf harf karşılaştırır, birbirinden farklıdır
                                // ŞİFRE/HASH KARŞILAŞTIRMASINDA HER ZAMAN === KULLAN
```

### 3. `strict_types_demo.php` — `declare(strict_types=1)` neyi değiştirir

```php
<?php
declare(strict_types=1);   // bu dosyanın en üstünde olmalı - dosyadaki fonksiyon çağrılarında tip zorlaması açılır

// function ad(tip $parametre): donusTipi { ... }  -> bu minimal fonksiyon syntax'ını Gün 3'te detaylı işleyeceğiz
function ikiyleCarp(int $sayi): int
{
    return $sayi * 2;
}

echo ikiyleCarp(5);       // 10 - int gönderdik, sorun yok

echo ikiyleCarp("5");     // TypeError fırlatır! "5" bir string, strict_types açıkken PHP bunu int'e ÇEVİRMEZ
                          // declare(strict_types=1) OLMASAYDI -> PHP "5"'i sessizce 5'e çevirir, hata vermezdi

// Dikkat: strict_types sadece FONKSİYON PARAMETRELERİNİ etkiler
var_dump("5" == 5);       // yine bool(true) - strict_types bunu değiştirmez, == hala loose çalışır
```

---

## Kendini Test Et

1. `"10" == "1e1"` neden `true` döner?
2. `strict_types=1` hangi katmanda (fonksiyon çağrısı) etkili olur, hangisinde olmaz?
