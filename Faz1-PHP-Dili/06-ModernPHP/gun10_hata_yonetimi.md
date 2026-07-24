# Gün 10 — Hata Yönetimi: Exception, Try/Catch

## Bu Derste İlk Kez Göreceğin Syntax

| Yeni syntax | Örnek | Açıklama |
|-------------|-------|----------|
| `try`/`catch`/`finally` | `try { } catch (Exception $e) { } finally { }` | C# ile birebir aynı syntax |
| `throw` | `throw new Exception("mesaj");` | C# ile birebir aynı |
| Custom exception | `class ValidationException extends Exception {}` | Kendi exception tipini tanımlama |
| `Throwable` | `catch (Throwable $e) {}` | Hem `Error` hem `Exception`'ı kapsayan en üst arayüz |
| `set_exception_handler` | `set_exception_handler(function ($e) {...});` | Hiç yakalanmamış exception'lar için global bir "son çare" |

---

## Senaryo

Gün 2'de şu fonksiyonu yazmıştık:

```php
declare(strict_types=1);

function ikiyleCarp(int $sayi): int {
    return $sayi * 2;
}

echo ikiyleCarp("5");   // TypeError firlatir
```

O gün bunu çalıştırırsak script'in çöktüğünü söylemiştik. Şimdi bunu **yakalayıp** düzgün bir şekilde ele almayı öğreneceğiz. C#'a alışkın olduğun için doğal olarak şöyle yazarsın:

```php
try {
    echo ikiyleCarp("5");
} catch (Exception $e) {
    echo "Hata yakalandı: " . $e->getMessage();
}
```

Bunu çalıştırdığında **hâlâ çöker** — `catch (Exception $e)` bloğu hiç devreye girmez, script yine crash eder! C#'ta her exception `Exception` sınıfından türediği için `catch (Exception e)` her şeyi yakalar — ama PHP'de `TypeError` bir `Exception` **değildir**, o `Error` sınıfından türer. `Error` ve `Exception`, PHP'de **kardeş iki dal**dır, biri diğerinin altında değil. Doğru yakalama:

```php
try {
    echo ikiyleCarp("5");
} catch (Throwable $e) {          // Throwable -> hem Error hem Exception'i kapsayan en ust seviye
    echo "Hata yakalandı: " . $e->getMessage();
}
```

Bugünün konusu tam olarak bu: PHP'nin hata hiyerarşisi C#'takinden **farklı bir ağaç** — bunu bilmemek, "yakaladım sandığın" hataların production'da script'i çökertmeye devam etmesine yol açar.

---

## Analoji

**C# — tek soy ağacı:** C#'ta her hata, aynı büyük ailenin (`Exception`) bir ferdidir — `NullReferenceException`, `InvalidOperationException`, hepsi aynı kökten gelir. `catch (Exception e)` yazdığında, bu ailenin **tamamını** yakalarsın.

**PHP — iki kuzen dal:** PHP'de `Throwable` denen bir büyükbaba var, ama onun **iki ayrı çocuğu** var: `Error` ve `Exception`. Bunlar kardeş değil, **kuzen** gibi — ikisi de `Throwable`'dan türer ama biri diğerinin altında değil. `TypeError`, `DivisionByZeroError` gibi hatalar `Error` dalında yaşar (genelde **programcı hatası** — yanlış tip, sıfıra bölme). `ValidationException` gibi kendi tanımladığın hatalar ise `Exception` dalında yaşar (genelde **beklenen, ele alınması gereken** durumlar). Sadece "Exception dalına" bakarsan, "Error dalındaki" kuzenleri kaçırırsın.

---

## Teorik

### Exception hiyerarşisi

```
Throwable                          (en üst seviye arayüz)
├── Error                          (PHP'nin kendi/dahili hataları — genelde programcı hatası)
│   ├── TypeError                  (yanlış tip gönderildi — Gün 2'deki strict_types örneği)
│   ├── DivisionByZeroError        (sıfıra bölme)
│   └── ...
└── Exception                      (uygulama seviyesi hatalar — sen fırlatırsın, beklenir)
    ├── InvalidArgumentException
    ├── RuntimeException
    └── ValidationException (kendi tanımladığın)
```

PHP 8'den önce bazı `Error` türü hatalar (fatal error) **hiç yakalanamazdı** — script direkt çökerdi. PHP 8 ile `Error` de `Throwable`'ı implemente ettiği için en azından `catch (Throwable $e)` ile yakalanabilir hale geldi (ama `catch (Exception $e)` yine yakalamaz — çünkü `Error`, `Exception`'ın alt sınıfı değil).

### `try`/`catch`/`finally`

```php
try {
    // riskli kod
} catch (ValidationException $e) {     // en spesifik tipten en genele doğru sıralanır
    // sadece bu tip icin
} catch (Exception $e) {
    // diger Exception turleri icin
} finally {
    // HER durumda calisir - hata olsa da olmasa da (kaynak temizleme icin ideal)
}
```

### Custom exception

```php
class ValidationException extends Exception {
    // bos birakilabilir - Exception'in butun mekanizmasini (getMessage, getCode vb.) miras alir
}

function yasKontrolEt(int $yas) {
    if ($yas < 0) {
        throw new ValidationException("Yaş negatif olamaz");
    }
}
```

### `set_error_handler` / `set_exception_handler`

Bunlar, hiçbir `try/catch`'in yakalamadığı hatalar için **son çare** görevi görür — global bir "hiç kimse bu hatayı yakalamadıysa buraya gelsin" mekanizması:

```php
set_exception_handler(function (Throwable $e) {
    error_log("Yakalanmamış hata: " . $e->getMessage());
    echo "Bir şeyler ters gitti, bizi bilgilendirdiniz.";
});
```

Laravel'de bu mekanizmanın framework seviyesindeki karşılığı, uygulamanın **exception handler**'ıdır — tüm yakalanmamış exception'lar oraya düşer, orada loglanır ve kullanıcıya uygun bir hata sayfası/response üretilir (Faz2'de tekrar göreceğiz).

---

## C# ile Karşılaştırma

| Konu | C# | PHP |
|------|----|----|
| Syntax | `try {} catch (Exception e) {} finally {}` | `try {} catch (Exception $e) {} finally {}` — **birebir aynı** |
| Hiyerarşi | Her exception `Exception`'dan türer (tek ağaç) | `Error` ve `Exception` **ayrı dallar**, ikisi de `Throwable`'dan türer |
| "Hepsini yakala" | `catch (Exception e)` yeterli | `catch (Throwable $e)` gerekir — `catch (Exception $e)` bir `TypeError`'ı yakalamaz |
| Checked exception | Yok | Yok — bu noktada ikisi benzer |

**Kritik çıkarım:** `catch (Exception $e)` bir `TypeError`'ı yakalamaz çünkü `TypeError`, `Error` sınıfından türer, `Exception`'dan değil. "Her hatayı yakala" niyetindeysen, `catch (Throwable $e)` yazman gerekir.

---

## Kod ile Göster

Çalıştırmak için (ileride, PHP kurulduğunda):
```
cd Faz1-PHP-Dili/06-ModernPHP
php -S localhost:8000
```

### 1. `try_catch_temelleri_demo.php` — `Exception` vs `Error`, custom exception, `finally`

```php
declare(strict_types=1);

function ikiyleCarp(int $sayi): int {
    return $sayi * 2;
}

// --- YANLIS: catch (Exception) bir TypeError'i YAKALAMAZ ---
try {
    echo ikiyleCarp("5") . "\n";
} catch (\Exception $e) {
    echo "Bu satir hic calismaz - TypeError bir Exception degil\n";
}
// Yukaridaki try/catch YETERSIZ kalir, script normalde burada COKER.
// (Bu dosyada devam edebilmemiz icin asagida DOGRU yontemi ayri bir blokta gosteriyoruz.)
```

> Not: Yukarıdaki blok gerçekte script'i durdurur. Aşağıdaki doğru örnek, PHP'nin script'i durdurmadan devam edebilmesi için ayrı çalıştırılmalıdır — dosyada bilinçli olarak yorum satırına alındı, gerçek dosyada bu iki blok ayrı ayrı denenmelidir.

```php
// --- DOGRU: catch (Throwable) hem Error hem Exception'i yakalar ---
try {
    echo ikiyleCarp("5") . "\n";
} catch (\Throwable $e) {
    echo "Hata yakalandi: " . $e->getMessage() . " (tip: " . get_class($e) . ")\n";
    // "Hata yakalandi: ikiyleCarp(): Argument #1 ($sayi) must be of type int, string given (tip: TypeError)"
}

// --- Custom exception ---
class ValidationException extends Exception {}

function yasKontrolEt(int $yas): void {
    if ($yas < 0) {
        throw new ValidationException("Yaş negatif olamaz: $yas");
    }
}

try {
    yasKontrolEt(-5);
} catch (ValidationException $e) {
    echo "Validasyon hatasi: " . $e->getMessage() . "\n";
} finally {
    echo "finally HER DURUMDA calisir (hata olsa da olmasa da)\n";
}
```

### 2. `global_handler_demo.php` — `set_exception_handler` ile son çare yakalama

```php
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
```

---

## Kendini Test Et

1. `catch (Exception $e)` ile bir `TypeError` neden yakalanmaz?
2. Laravel'de tüm yakalanmamış exception'lar nereye gider (handler)?
