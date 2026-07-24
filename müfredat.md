# PHP & Laravel Müfredatı
## C# / .NET Deneyimli Geliştirici İçin

> **Hedef:** 5 haftada PHP dilini ve Laravel'in temel işleyişini kavramış, yeni işe pratik olarak hazır geliştirici.
>
> **Felsefe:** Mimari, design pattern, DDD, CQRS, mikroservis **kapsam dışı**. Amaç dili ve framework'ün nasıl çalıştığını anlamak — "neden böyle tasarlandı, en iyi mimari ne" sorusu bu müfredatın konusu değil.
>
> **Çalışma Yöntemi:** Her konu önce C#'taki karşılığıyla kıyaslanır (zaten bilinen zihin modeli üzerinden hızlı öğrenim), sonra PHP/Laravel tarafı kod ile gösterilir.

---

## KAPSAM DIŞI (bilinçli olarak dahil edilmedi)

- SOLID, GoF Design Patterns, DDD, CQRS → DotnetDoku Faz3'te zaten işlendi, burada tekrar yok
- Mikroservisler, Docker/K8s, message broker → DotnetDoku Faz5'in konusu, burada yok
- Performans/ölçek mühendisliği (Faz4 tarzı) → burada yok
- Bu müfredatın tek amacı: **PHP dilini ve Laravel'in çalışma mantığını** öğrenmek

---

## PROJE YAPISI

```
PhpLaravelDoku/
│
├── Faz1-PHP-Dili/
│   ├── 01-CalismaModeli/      → PHP çalışma modeli, superglobals demoları
│   ├── 02-TipSistemi/         → değişkenler, type juggling örnekleri
│   ├── 03-Fonksiyonlar/       → closures, scope demoları
│   ├── 04-Diziler/            → array fonksiyonları, indexed/associative
│   ├── 05-OOP/                → class, interface, trait, magic methods
│   └── 06-ModernPHP/          → Composer, namespace, PHP 8.x özellikleri
│
├── Faz2-Laravel-CRM/
│   └── crm-app/               → Tek büyüyen Laravel projesi (CRM)
│
└── müfredat.md
```

### Örnek Proje: Mini CRM

Faz2 boyunca tek bir uygulama kademeli büyür: basit bir **CRM (Müşteri İlişkileri Yönetimi)** sistemi.

**Domain:** Şirketler (Company), Kişiler (Contact), Fırsatlar (Deal), Görevler (Task), Kullanıcılar (roller: admin/satış temsilcisi)

| Aşama | Ne eklenir |
|-------|-----------|
| Başlangıç | Routing, controller, Blade ile şirket/kişi listeleme |
| Orta | Migration + Eloquent CRUD, ilişkiler (Company → Contact → Deal) |
| İleri | Form validation, auth (giriş), yetkilendirme (sadece admin siler) |
| Final | Dosya yükleme (logo/ek), API endpoint, temel test |

---

## GENEL BAKIŞ — 2 FAZLI YOL HARİTASI

| Faz | Hafta | Konu | Seviye |
|-----|-------|------|--------|
| 1 | 1–2 | PHP Dili Temelleri | Temel/Orta |
| 2 | 3–5 | Laravel Temelleri (CRM projesi ile) | Orta |

> Toplam süre: **5 hafta**, ~31 gün.

---

# FAZ 1 — PHP DİLİ: Zend Engine ve Temel Yapı

> C#'ı iyi bilen biri için PHP'yi öğrenmek yeni bir dil öğrenmek değil, farklı varsayımları öğrenmektir: interpreted vs compiled, loose typing vs strict typing, request-per-process model vs uzun yaşayan process.

---

## Hafta 1 — Çalışma Modeli, Tipler, Fonksiyonlar

### Gün 1 — PHP Nedir? Çalışma Modeli

**Teorik:**
- PHP interpreted bir dil — Zend Engine kaynağı parse eder, opcode'a çevirir, çalıştırır
- PHP-FPM (FastCGI Process Manager) — her HTTP isteği için worker process/thread
- OPcache — opcode'ları cache'ler, her istekte yeniden parse etmeyi önler
- Request lifecycle: Web server (Nginx/Apache) → PHP-FPM → script çalışır → response → **process/state sıfırlanır**
- `php artisan serve` (geliştirme) vs Nginx+PHP-FPM (production)

**C# ile karşılaştırma:**
- .NET: Kestrel içinde tek process, uzun yaşayan `AppDomain`, static state istekler arası **kalıcı**
- PHP: Her istek "temiz sayfa" — bir önceki isteğin static değişkeni, singleton'ı, in-memory cache'i **yaşamaz** (paylaşılan state için Redis/DB şart)
- Bu fark mimariyi doğrudan etkiler: PHP'de "in-process cache" güvenilmez, .NET'te güvenilir

**Sorular:**
- Neden PHP'de bir class'ın static property'si istekler arası veri tutamaz?
- OPcache olmadan her istekte ne olur?

---

### Gün 2 — Değişkenler, Tipler ve Type Juggling

**Teorik:**
- `$` prefix ile değişken tanımlama, dinamik tipleme (declare etmeden tip)
- Scalar types: `int`, `float`, `string`, `bool`; Compound: `array`, `object`; Special: `null`, `resource`
- Type juggling — otomatik tip dönüşümü: `"5" + 3` → `8`
- `==` (loose comparison) vs `===` (strict comparison) — PHP'nin en meşhur tuzağı
- `declare(strict_types=1)` — fonksiyon parametrelerinde strict type zorlama
- Superglobals: `$_GET`, `$_POST`, `$_SERVER`, `$_SESSION`, `$_COOKIE`, `$_FILES`

**C# ile karşılaştırma:**
- C#: derleme zamanında statik tip kontrolü, `"5" + 3` derleme hatası verir ya da string concat olur
- PHP: `strict_types` açılmadıkça runtime'da sessizce tip dönüşümü yapar — bu C#'tan gelen biri için en büyük tuzak
- `null` kontrolü: C# nullable reference types (`string?`) vs PHP'de her şey varsayılan nullable

**Kritik tuzak:**
```php
if ($password == "0") { ... }   // "abc" == 0 → true olabilir (eski PHP), her zaman === kullan
```

**Sorular:**
- `"10" == "1e1"` neden `true` döner?
- `strict_types=1` hangi katmanda (fonksiyon çağrısı) etkili olur, hangisinde olmaz?

---

### Gün 3 — Fonksiyonlar, Scope ve Closures

**Teorik:**
- Fonksiyon tanımı, default parametre, variadic (`...$args`), named arguments (PHP 8+)
- Değişken scope — fonksiyon içi local, `global` keyword, `static` local değişken
- Closures (anonymous function) — `function() use ($x) {}` — **`use` ile explicit capture**
- Arrow functions (PHP 7.4+) — `fn($x) => $x * 2` — implicit capture (C# lambda'ya en yakın)
- Callable type — fonksiyonu değer olarak taşıma
- First-class callable syntax (PHP 8.1+) — `strlen(...)`

**C# ile karşılaştırma:**
- C# lambda/closure **otomatik** olarak dış scope'u yakalar; PHP `function() use ($x)` ile **elle** belirtilir — unutulursa değişkene erişilemez
- `use (&$x)` → by reference capture, C#'ta `ref` yakalamaya benzer ama PHP'de closure içinde açıkça yazılır
- `Func<T,T>`/`Action<T>` → PHP'de karşılığı yok, her callable `Closure` tipi veya `callable`

**Sorular:**
- `use ($x)` ile `use (&$x)` arasındaki fark ne, ne zaman hangisi gerekir?
- Arrow function neden closure'dan daha az yazım gerektirir?

---

### Gün 4 — Diziler: PHP'nin İsviçre Çakısı

**Teorik:**
- PHP array = hem indexed hem associative hem de sıralı (ordered map) — tek yapı
- `array_map`, `array_filter`, `array_reduce` — LINQ'nun PHP karşılığı
- `foreach ($arr as $key => $value)` — hem index hem key erişimi
- Array destructuring — `[$a, $b] = $arr;`
- Spread operator (`...$arr`) — array birleştirme/fonksiyona yayma
- `array_merge` vs `+` operatörü — davranış farkı (key çakışması)
- Multi-dimensional array — nested associative array, JSON ile doğal eşleşme

**C# ile karşılaştırma:**
- C#: `List<T>`, `Dictionary<K,V>`, `T[]` ayrı ayrı tipler — PHP'de hepsi tek `array`
- `array_map/filter/reduce` ≈ LINQ `Select/Where/Aggregate` ama **eager** çalışır (LINQ'nun deferred execution'ı yok)
- PHP array kopyalama **value semantics** (varsayılan copy-on-write) — C# `List<T>` reference semantics'tir, bu köklü bir fark

**Sorular:**
- `$a = $b;` (ikisi de array) sonrası `$a`'yı değiştirmek `$b`'yi etkiler mi? C#'taki `List<T>` ile fark nedir?
- `array_merge(['a'=>1], ['a'=>2])` sonucu ne olur, `+` operatörüyle fark nedir?

---

### Gün 5 — String İşlemleri ve Regex

**Teorik:**
- String interpolation: `"Merhaba $isim"` vs `'Merhaba $isim'` (tek tırnak interpolate etmez)
- Heredoc / Nowdoc syntax — çok satırlı string
- Sık kullanılan fonksiyonlar: `str_contains`, `str_starts_with` (PHP 8+), `explode`/`implode`, `sprintf`
- `preg_match`, `preg_replace` — PCRE regex motoru
- Multibyte string fonksiyonları (`mb_*`) — UTF-8 güvenli işlemler, neden `strlen` yerine `mb_strlen`?

**C# ile karşılaştırma:**
- C# string interpolation `$"Merhaba {isim}"` ≈ PHP çift tırnak `"Merhaba $isim"` — ama PHP'de tek tırnak literal kalır, bu C#'ta yok
- `sprintf` ≈ C# `string.Format` / composite formatting
- PHP string immutable (C# gibi) ama `.` ile concat her seferinde yeni string yaratır — döngüde çok concat performans sorunudur (C#'taki `StringBuilder` ihtiyacına benzer, PHP'de daha az kritik ama aynı mantık)

**Sorular:**
- Tek tırnak ile çift tırnak string arasındaki performans ve davranış farkı ne?
- `mb_strlen` kullanmazsan Türkçe karakterli string'lerde ne olur?

---

### Gün 6 — OOP Temelleri: Class, Interface, Trait

**Teorik:**
- Class tanımı, `public`/`protected`/`private`, constructor (`__construct`)
- Constructor property promotion (PHP 8+) — `public function __construct(private string $ad) {}`
- Interface — C#'taki gibi sözleşme, `implements` keyword
- **Trait** — C#'ta karşılığı olmayan yapı: class'a "kopyala-yapıştır" gibi davranış ekleme (multiple inheritance'ın PHP çözümü)
- Trait çakışması — aynı isimli iki trait method'u nasıl çözülür (`insteadof`, `as`)
- Abstract class ve method

**C# ile karşılaştırma:**
- Interface ≈ birebir aynı kavram
- Trait'in en yakın C# karşılığı: **extension method** + **default interface method** karışımı — ama trait state (property) de taşıyabilir, extension method taşıyamaz
- PHP'de multiple inheritance yok ama multiple trait `use` edilebilir — C#'ta multiple interface inheritance var, class inheritance yok

**Sorular:**
- Bir class iki trait kullanıyor, ikisinde de `save()` metodu var — PHP nasıl çözer?
- Trait ile interface'i birlikte kullanmanın tipik senaryosu nedir?

---

### Gün 7 — Hafta 1 Özet

**Tekrar soruları:**
1. `==` yerine neden her zaman `===` kullanılmalı?
2. `use ($x)` closure'da unutulursa ne olur?
3. PHP array'i C#'taki `List<T>` gibi reference olarak mı davranır, yoksa value olarak mı?
4. Trait, interface'ten farklı olarak neyi çözer?

---

## Hafta 2 — İleri OOP, Composer, Modern PHP

### Gün 8 — OOP İleri: Static, Magic Methods

**Teorik:**
- `static` property/method — class'a ait, instance'a değil (C#'taki gibi ama DI ile nadiren kullanılır Laravel'de)
- Magic methods: `__get`, `__set`, `__call`, `__callStatic`, `__toString`, `__invoke`
- `__get`/`__set` — tanımsız property'e erişimi yakalama (Eloquent modellerinin temeli!)
- `__call` — tanımsız method çağrısını yakalama (Laravel'in "fluent" API'lerinin sırrı)
- Late static binding — `static::` vs `self::` farkı

**C# ile karşılaştırma:**
- `__get`/`__set` ≈ C# property'lerin get/set accessor'ı ama PHP'de **dinamik**, herhangi bir isimde property'e erişimi yakalayabilir — C#'ta bu ancak reflection ile mümkün
- `__call` ≈ C#'ta doğrudan karşılığı yok, en yakını `DynamicObject` (nadiren kullanılır); Laravel bunu her yerde kullanır (`$user->save()`, `Model::where()`)
- Bu magic method'lar Eloquent ORM'nin "sihir gibi çalışması"nın perde arkasıdır — Faz2'de tekrar karşımıza çıkacak

**Sorular:**
- `self::` ile `static::` arasındaki fark ne, inheritance'ta neden önemli?
- Laravel'de `User::where('email', $email)->first()` çalışırken hangi magic method devreye girer?

---

### Gün 9 — Namespace ve Composer (Autoloading)

**Teorik:**
- Namespace — `namespace App\Models;` — C# namespace ile birebir aynı fikir
- `use App\Models\User;` — C# `using` ile birebir aynı
- Composer — PHP'nin paket yöneticisi, `composer.json`/`composer.lock`
- PSR-4 autoloading standardı — namespace → dosya yolu eşlemesi, `require`/`include` elle yazmaya gerek bırakmaz
- `composer require paket/adi`, `composer install` vs `composer update` farkı
- `vendor/autoload.php` — otomatik yüklenen tüm bağımlılıklar

**C# ile karşılaştırma:**
- Composer ≈ NuGet, `composer.json` ≈ `.csproj`, `composer.lock` ≈ `packages.lock.json`
- PSR-4 autoloading ≈ .NET'in assembly/namespace çözümlemesi ama **derleme yok** — her istekte dosya sisteminden okunur (OPcache bunu hafifletir)
- Packagist ≈ NuGet.org

**Sorular:**
- `composer install` ile `composer update` arasındaki fark ne, CI/CD'de hangisi kullanılmalı?
- PSR-4 olmadan class autoload edilebilir mi?

---

### Gün 10 — Hata Yönetimi: Exception, Try/Catch

**Teorik:**
- `try`/`catch`/`finally` — C# ile birebir aynı syntax
- Exception hiyerarşisi: `Throwable` → `Error` | `Exception`
- `Error` — PHP'nin kendi hataları (TypeError, DivisionByZeroError) vs `Exception` — uygulama hataları
- Custom exception — `class ValidationException extends Exception {}`
- `set_error_handler`/`set_exception_handler` — global hata yakalama
- PHP 8'de error/exception birleşik hiyerarşi (eskiden fatal error yakalanamazdı)

**C# ile karşılaştırma:**
- Syntax birebir aynı: `try {} catch (Exception $e) {} finally {}`
- Fark: C#'ta her exception `Exception`'dan türer; PHP'de `Error` ve `Exception` **ayrı dallar**, `catch (Exception $e)` bir `TypeError`'ı yakalamaz — `catch (Throwable $e)` gerekir
- PHP'de checked exception yok (Java'daki gibi), C#'ta da yok — bu noktada ikisi benzer

**Sorular:**
- `catch (Exception $e)` ile bir `TypeError` neden yakalanmaz?
- Laravel'de tüm yakalanmamış exception'lar nereye gider (handler)?

---

### Gün 11 — PHP 8.x Modern Özellikler

**Teorik:**
- Enums (PHP 8.1+) — `enum Status: string { case Active = 'active'; }` — backed enum
- Match expression — `switch`'in modern, strict-comparison hali, değer döndürür
- Named arguments — `createUser(name: 'Ali', age: 30)`
- Nullsafe operator — `$user?->address?->city` (C# `?.` ile birebir aynı)
- Readonly properties (PHP 8.1+) — immutable property (C# `init` ile benzer amaç)
- Union types — `function foo(int|string $x)`

**C# ile karşılaştırma:**
- `match` ≈ C# switch expression (`x switch { ... }`) — ikisi de değer döner, strict comparison yapar
- Nullsafe `?->` ≈ C# `?.` birebir aynı davranış
- `readonly` property ≈ C# `init` accessor — ikisi de "constructor'da set, sonra değişmez" fikrini uygular
- Enum: PHP enum'ları method taşıyabilir ama C#'taki gibi arbitrary int değeri değil, backed enum ile string/int değer taşır

**Sorular:**
- `match` ile `switch` arasındaki davranış farkı (fall-through, strict comparison) nedir?
- Readonly property'yi constructor dışında değiştirmeye çalışırsan ne olur?

---

### Gün 12 — Composer Paketleri ve PHP Ekosistemi

**Teorik:**
- Yaygın paketler: Carbon (tarih/saat, C# `DateTime`/`DateOnly` karşılığı), Guzzle (HTTP client, C# `HttpClient` karşılığı)
- `.env` dosyası ve `vlucas/phpdotenv` — konfigürasyon yönetimi (Laravel bunu içinde barındırır)
- PHPStan/Psalm — static analysis (C#'ın derleyici tip kontrolüne kısmi alternatif, çünkü PHP'de derleme zamanı yok)
- PHP-CS-Fixer — code style (C# `dotnet format` karşılığı)

**C# ile karşılaştırma:**
- Guzzle ≈ `HttpClient`, ama `IHttpClientFactory` gibi pooling/lifecycle yönetimi elle yapılmalı
- PHPStan ≈ Roslyn analyzer'lara kısmi alternatif — ama derleme zamanı olmadığı için "compile-time güvenlik" PHP'de asla C# seviyesine ulaşmaz, bu **kalıcı bir mimari fark**

**Sorular:**
- PHP'de derleme zamanı tip kontrolü olmadığı için hangi hatalar ancak runtime'da/PHPStan ile yakalanabilir?

---

### Gün 13 — Hafta 2 Özet ve Faz 1 Sonu

**Faz 1 Genel Tekrar Soruları:**
1. PHP request lifecycle'ının C#'tan en temel farkı nedir ve bu neyi etkiler (state, cache)?
2. `__get`/`__call` magic method'ları olmadan Eloquent'in "sihirli" API'si nasıl çalışırdı?
3. Composer autoloading PSR-4 olmadan çalışabilir mi?
4. `readonly` property C#'taki `init` ile aynı amacı nasıl karşılar?

> Faz 1 bitti. Artık PHP'nin nasıl çalıştığını, tip sistemini, OOP yapısını ve modern syntax'ını biliyorsun. Faz 2'de bunları Laravel çatısı altında kullanacağız.

---

# FAZ 2 — LARAVEL TEMELLERİ (CRM Projesi ile)

> Laravel, Spring/ASP.NET Core'un PHP dünyasındaki karşılığı. Bu fazda framework'ün nasıl çalıştığını, Eloquent ORM'i ve temel web geliştirme akışını CRM projesi üzerinden öğreneceğiz. Mimari tartışması yok — "bu nasıl çalışıyor, nasıl kullanılır" odaklı.

### Bu Fazda Kodlayacaklarımız (`Faz2-Laravel-CRM/crm-app/`)

Tek uygulama, kademeli büyür — **Mini CRM**: Şirketler, Kişiler, Fırsatlar, Görevler.

---

## Hafta 3 — Laravel'in İskeleti

### Gün 14 — Laravel Nedir? Service Container ve Artisan

**Teorik:**
- Laravel = routing + ORM (Eloquent) + templating (Blade) + DI container hepsi bir arada (ASP.NET Core'un "batteries included" hali)
- Service Container (IoC Container) — Laravel'in kalbi, her şey buradan çözülür
- Constructor injection — `public function __construct(private UserRepository $repo)` — otomatik çözülür
- Service Provider — uygulama başlarken container'a servis "register" eden sınıflar
- Artisan CLI — `php artisan make:model`, `make:controller`, `migrate` vb. — kod iskeleti üretme
- Kurulum: `composer create-project laravel/laravel crm-app`, `.env` konfigürasyonu

**C# ile karşılaştırma:**
- Service Container ≈ ASP.NET Core `IServiceCollection`/`IServiceProvider` — constructor injection mantığı birebir aynı
- Service Provider ≈ `Program.cs`'teki `builder.Services.AddX()` çağrıları, ama Laravel'de her paket kendi provider'ını taşır (otomatik keşif)
- `php artisan` ≈ `dotnet` CLI komutları (`dotnet new`, `dotnet ef migrations add`)
- Lifetime: Laravel'de varsayılan **her istekte yeni instance** (C#'taki Scoped'a en yakın) — Singleton için `$this->app->singleton()` elle belirtilir

**Sorular:**
- Laravel'de bir servisi Singleton yapmak neden C#'takinden daha riskli olabilir? (Gün 1'deki process model ile bağlantısını kur)

---

### Gün 15 — Routing

**Teorik:**
- `routes/web.php` (browser, session tabanlı) vs `routes/api.php` (stateless, token tabanlı)
- `Route::get('/companies', [CompanyController::class, 'index'])`
- Route parametreleri — `Route::get('/companies/{id}', ...)`, route model binding (`{company}` → otomatik `Company` nesnesi)
- Named routes — `route('companies.show', $id)`
- Route grupları — prefix, middleware, namespace ortak tanımlama
- Resource routing — `Route::resource('companies', CompanyController::class)` → 7 CRUD route otomatik

**C# ile karşılaştırma:**
- Convention-based routing yok, **explicit route tanımı** şart — ASP.NET'in attribute routing'ine (`[HttpGet]`) daha yakın
- Route model binding ≈ ASP.NET Core model binding, ama Laravel doğrudan Eloquent modelini DB'den çekip inject eder (ASP.NET'te bunu elle yaparsın)
- `Route::resource` ≈ ASP.NET `MapControllers()` ile controller convention'ı otomatik CRUD üretmesi

**CRM'de bugün:** `routes/web.php`'e `companies` resource route'u eklenir.

---

### Gün 16 — Controllers ve Request/Response

**Teorik:**
- `php artisan make:controller CompanyController --resource` — 7 method iskeleti (index, create, store, show, edit, update, destroy)
- `Request` nesnesi — `$request->input('name')`, `$request->all()`, `$request->validate([...])`
- Response türleri — `view()`, `redirect()`, `response()->json()`
- Dependency injection controller constructor'ında ve method parametrelerinde (method injection)

**C# ile karşılaştırma:**
- Controller yapısı ≈ ASP.NET MVC Controller, action method'lar birebir aynı fikir
- `Request $request` inject ≈ ASP.NET `HttpContext.Request`, ama Laravel'de tipi doğrudan parametre olarak alınır (method injection ASP.NET'e daha yakın)
- `IActionResult` çeşitliliği ≈ Laravel'de `view()`/`redirect()`/`response()->json()` dönüş tipleri

**CRM'de bugün:** `CompanyController` yazılır — `index()` companies listesini view'a gönderir.

---

### Gün 17 — Blade Templating

**Teorik:**
- Blade syntax: `{{ $variable }}` (auto-escape, XSS koruması), `{!! $html !!}` (escape'siz, dikkat!)
- Directives: `@if`, `@foreach`, `@auth`, `@csrf`
- Layout ve inheritance: `@extends('layouts.app')`, `@section`, `@yield`
- Component'ler — `<x-alert type="error">` (Blade component, Razor partial view'a benzer ama daha güçlü)
- `@csrf` — form içinde CSRF token, neden zorunlu?

**C# ile karşılaştırma:**
- `{{ }}` ≈ Razor `@variable` — otomatik HTML encode ikisinde de var
- `@extends`/`@section`/`@yield` ≈ Razor `_Layout.cshtml` + `@RenderBody()`/`@RenderSection()`
- Blade component ≈ Razor Component (Blazor) veya partial view + view component karışımı
- `@csrf` ≈ ASP.NET Core `@Html.AntiForgeryToken()` — aynı güvenlik amacı

**CRM'de bugün:** `layouts/app.blade.php` + `companies/index.blade.php` — şirket listesi tablosu.

---

### Gün 18 — Migrations & Schema Builder

**Teorik:**
- `php artisan make:migration create_companies_table` — versiyonlanmış şema değişikliği
- Schema Builder — `Schema::create('companies', function (Blueprint $table) { $table->id(); $table->string('name'); })`
- Migration'ın `up()`/`down()` metodları — rollback desteği
- `php artisan migrate`, `migrate:rollback`, `migrate:fresh`
- Foreign key tanımı — `$table->foreignId('company_id')->constrained()`

**C# ile karşılaştırma:**
- Migration kavramı ≈ EF Core Migrations birebir aynı fikir (code-first schema versioning)
- Fark: Laravel migration'ı **Fluent PHP API** ile yazılır (EF Core da C# ile yazılır, ikisi de "kod olarak şema") ama Laravel migration dosyaları düz PHP sınıfı, EF Core'da `ModelBuilder`/Fluent API veya attribute tabanlı
- `migrate:fresh` ≈ EF Core'da veritabanını drop edip yeniden migrate etmenin karşılığı (sadece dev ortamında!)

**CRM'de bugün:** `companies`, `contacts` (company_id foreign key ile), `deals` tabloları migration'ları yazılır.

---

### Gün 19 — Eloquent ORM Temelleri

**Teorik:**
- Eloquent Model — her tablo için bir class, `class Company extends Model {}`
- Convention over configuration — `Company` modeli otomatik `companies` tablosuna bağlanır (plural, snake_case)
- CRUD: `Company::create([...])`, `Company::find($id)`, `$company->update([...])`, `$company->delete()`
- `$fillable`/`$guarded` — mass assignment koruması (neden gerekli — güvenlik)
- Query Builder — `Company::where('city', 'İstanbul')->orderBy('name')->get()`
- Eager/lazy — bugünlük sadece temel CRUD, ilişkiler yarın

**C# ile karşılaştırma:**
- Eloquent Model ≈ EF Core Entity + `DbSet<T>` karışımı — ama Eloquent'te **her model kendi query builder'ını taşır** (Active Record pattern), EF Core'da DbContext üzerinden erişilir (Data Mapper pattern) — **köklü mimari fark**
- `$fillable` ≈ EF Core'da doğrudan karşılığı yok (DTO/ViewModel ile mass assignment zaten dolaylı önlenir); Laravel'de model doğrudan request'ten doldurulabildiği için bu güvenlik katmanı şart
- Query Builder ≈ LINQ to Entities, ama **fluent method chain**, expression tree yok — SQL'e çevrimi daha doğrudan

**CRM'de bugün:** `Company` modeli + controller'da gerçek DB'den CRUD.

---

### Gün 20 — Hafta 3 Özet

**Tekrar soruları:**
1. Route model binding olmadan bir company'yi ID'den çekmek kaç satır kod gerektirirdi?
2. Active Record (Eloquent) ile Data Mapper (EF Core) arasındaki fark, testability açısından ne anlama gelir?
3. `$fillable` tanımlanmazsa hangi güvenlik açığı oluşur?

---

## Hafta 4 — İlişkiler, Validation, Auth

### Gün 21 — Eloquent İlişkiler

**Teorik:**
- `hasMany` — `Company::contacts()` → bir şirketin birden fazla kişisi
- `belongsTo` — `Contact::company()` → bir kişinin bir şirketi
- `belongsToMany` — many-to-many, pivot tablo (`deal_user` gibi)
- Eager loading — `Company::with('contacts')->get()` — N+1 problemi ve çözümü
- Lazy loading — `$company->contacts` (erişildiğinde otomatik sorgu) — ne zaman tehlikeli?

**C# ile karşılaştırma:**
- `hasMany`/`belongsTo` ≈ EF Core navigation properties (`ICollection<Contact>`, `Company Company`)
- `with()` ≈ EF Core `.Include()` — ikisi de N+1'i önlemenin yolu
- Lazy loading burada da (tıpkı EF Core'da olduğu gibi) production'da tehlikeli — her erişimde ayrı sorgu

**CRM'de bugün:** `Company hasMany Contact`, `Company hasMany Deal` ilişkileri kurulur, şirket detay sayfasında kişiler listelenir.

---

### Gün 22 — Form Request Validation

**Teorik:**
- Inline validation — `$request->validate(['name' => 'required|max:255'])`
- Form Request class — `php artisan make:request StoreCompanyRequest` — validation'ı controller'dan ayırma
- Validation rule'ları: `required`, `email`, `unique:companies,email`, `exists:companies,id`
- Hata mesajları — otomatik `$errors` değişkeni Blade'de, `old('name')` ile form'u eski değerle doldurma

**C# ile karşılaştırma:**
- Form Request ≈ DataAnnotations/FluentValidation + ASP.NET `ModelState.IsValid` birleşimi
- `unique:companies,email` ≈ FluentValidation'da custom async validator yazmak gerekir — Laravel'de built-in rule
- Validation hatası → otomatik redirect + `$errors` bag ≈ ASP.NET'te elle `ModelState` kontrolü + view'a taşıma

**CRM'de bugün:** `StoreContactRequest` — email zorunlu ve unique, şirket seçimi zorunlu.

---

### Gün 23 — Middleware

**Teorik:**
- Middleware — request/response pipeline'a giren katman, `handle($request, Closure $next)`
- `auth` middleware — giriş yapmamış kullanıcıyı login'e yönlendirir
- Global middleware vs route middleware vs middleware group
- Custom middleware yazmak — `php artisan make:middleware EnsureIsAdmin`
- `$next($request)` çağrısından önce/sonra kod — pipeline'ın neresinde çalıştığı

**C# ile karşılaştırma:**
- Middleware kavramı ve pipeline mantığı ASP.NET Core Middleware ile **birebir aynı** (ikisi de aynı "onion" fikrinden geliyor)
- `handle($request, Closure $next)` ≈ `public async Task InvokeAsync(HttpContext context, RequestDelegate next)`
- Kernel'de middleware sıralaması ≈ `Program.cs`'te `app.Use...()` sıralaması — sıra burada da kritik

**CRM'de bugün:** Sadece giriş yapmış kullanıcıların CRM'e erişebilmesi için `auth` middleware route grubuna eklenir.

---

### Gün 24 — Authentication (Laravel Breeze/Sanctum)

**Teorik:**
- Laravel Breeze — hazır login/register scaffolding (session tabanlı, web için)
- Laravel Sanctum — API token authentication (SPA/mobile için)
- `Auth::user()`, `Auth::check()`, `auth()->id()`
- Session tabanlı auth nasıl çalışır — cookie + server-side session store
- Password hashing — `Hash::make()`, `Hash::check()` (bcrypt varsayılan)

**C# ile karşılaştırma:**
- Breeze (session) ≈ ASP.NET Core Cookie Authentication + Identity scaffolding
- Sanctum (token) ≈ ASP.NET Core JWT Bearer authentication
- `Auth::user()` ≈ `HttpContext.User` / `ClaimsPrincipal`
- `Hash::make()` ≈ ASP.NET Identity `PasswordHasher<T>` — ikisi de salted hash kullanır

**CRM'de bugün:** Breeze kurulur, kullanıcı girişi eklenir, CRM sadece giriş yapanlara açılır.

---

### Gün 25 — Authorization (Gates & Policies)

**Teorik:**
- Gate — basit yetki kontrolü: `Gate::define('delete-company', fn($user, $company) => $user->is_admin)`
- Policy — model bazlı yetki class'ı: `php artisan make:policy CompanyPolicy --model=Company`
- `$this->authorize('delete', $company)` — controller içinde kullanım
- Blade'de `@can('delete', $company)` — view'da koşullu gösterim

**C# ile karşılaştırma:**
- Gate ≈ basit `IAuthorizationHandler` / manuel yetki kontrolü
- Policy ≈ ASP.NET Core `[Authorize(Policy = "...")]` + `IAuthorizationHandler` — resource-based authorization ile doğrudan eşleşir
- `@can` ≈ Razor'da `@if ((await AuthorizationService.AuthorizeAsync(...)).Succeeded)`

**CRM'de bugün:** `CompanyPolicy` — sadece admin rolündeki kullanıcı şirket silebilir.

---

### Gün 26 — Hafta 4 Özet

**Tekrar soruları:**
1. Middleware pipeline'da `$next($request)` çağrısından sonra yazılan kod ne zaman çalışır?
2. Gate ile Policy arasında ne zaman hangisini seçersin?
3. Session tabanlı auth ile token tabanlı auth'un CRM gibi bir web uygulamasında neden Sanctum yerine Breeze tercih edilir?

---

## Hafta 5 — Dosya, API, Test

### Gün 27 — File Upload & Storage

**Teorik:**
- `$request->file('logo')` — yüklenen dosyaya erişim
- `Storage::disk('public')->put(...)` — filesystem abstraction (local, S3 vs. arasında kod değişmeden geçiş)
- `php artisan storage:link` — public erişim için symlink
- Validation ile dosya kısıtlama — `'logo' => 'image|max:2048'`

**C# ile karşılaştırma:**
- `$request->file()` ≈ ASP.NET Core `IFormFile`
- `Storage` facade ≈ .NET'te elle yazılan bir `IFileStorageService` soyutlaması — Laravel bunu framework içinde hazır sunar

**CRM'de bugün:** Şirket logosu yükleme özelliği eklenir.

---

### Gün 28 — API Resources & JSON Response

**Teorik:**
- `php artisan make:resource CompanyResource` — Eloquent modelini JSON'a dönüştüren katman
- `CompanyResource::collection($companies)` — liste dönüşü
- API route'ları — `routes/api.php`, stateless, Sanctum token ile korunur
- `return response()->json([...], 201)`

**C# ile karşılaştırma:**
- API Resource ≈ DTO + AutoMapper/manuel mapping birleşimi — ASP.NET'te elle response DTO'su yazmaya karşılık gelir
- `routes/api.php` ayrımı ≈ ASP.NET'te Controller bazında `[ApiController]` + ayrı route prefix

**CRM'de bugün:** `/api/companies` endpoint'i — dış sistemin CRM verisine JSON olarak erişimi.

---

### Gün 29 — Queue & Jobs (Temel Düzeyde)

**Teorik:**
- Neden bazı işler senkron yapılmaz — örn. "yeni fırsat oluşunca email gönder" isteği yavaşlatmasın
- `php artisan make:job SendDealCreatedEmail` — job class
- `dispatch(new SendDealCreatedEmail($deal))` — kuyruğa atma
- Queue driver — `sync` (dev, hemen çalışır) vs `database`/`redis` (gerçek arka plan işleme)
- `php artisan queue:work` — worker process

**C# ile karşılaştırma:**
- Laravel Job/Queue ≈ .NET `IHostedService`/`BackgroundService` + bir mesaj kuyruğu (Hangfire'a en yakın fikir: dispatch edilen iş, arka planda işlenir, retry desteği var)
- Bu konu mimari/mikroservis değil, sadece "Laravel'de bir işi arka plana atmak nasıl çalışır" — CRM'de tek bir örnekle gösterilecek, derinlemesine işlenmeyecek

**CRM'de bugün:** Yeni fırsat (deal) oluşunca ilgili satış temsilcisine "email gönderildi" (log'a yazan sahte mail) job'ı queue'ya atılır.

---

### Gün 30 — Test Temelleri: PHPUnit / Pest

**Teorik:**
- Laravel varsayılan olarak PHPUnit ile gelir; Pest daha modern, okunabilir syntax sunan bir katman
- Feature test — `$response = $this->get('/companies'); $response->assertStatus(200);`
- `RefreshDatabase` trait — her testte temiz DB
- Factory — `Company::factory()->create()` — test verisi üretimi (Faker tabanlı)
- Unit test vs Feature test — Laravel'de çoğu test Feature test'tir (gerçek HTTP + DB)

**C# ile karşılaştırma:**
- PHPUnit `assertStatus` ≈ xUnit + FluentAssertions ile `response.StatusCode.Should().Be(200)`
- Feature test ≈ ASP.NET `WebApplicationFactory` ile integration test — kavramsal olarak birebir aynı yaklaşım
- Factory ≈ Bogus (C# test data generator) + EF Core seed birleşimi

**CRM'de bugün:** `CompanyControllerTest` — company oluşturma, listeleme, yetkisiz kullanıcının silememesi test edilir.

---

### Gün 31 — Hafta 5 Özet ve Faz 2 Sonu

**Tekrar soruları:**
1. Route, Controller, Middleware, Eloquent Model — bir HTTP isteği bu sırayla nasıl işlenir? (Baştan sona anlat)
2. Eloquent Active Record ile EF Core Data Mapper arasındaki fark test yazarken nasıl hissettiriyor?
3. CRM projesindeki `auth` middleware, `CompanyPolicy` ve `StoreContactRequest` — üçü birbirinden nasıl farklı sorumluluk taşıyor?

> Müfredat tamamlandı. Elimizde: routing, Blade, migration/Eloquent, ilişkiler, validation, auth/authorization, dosya yükleme, API endpoint ve temel test içeren çalışan bir Mini CRM var. Design pattern/mimari/mikroservis konuları bilinçli olarak dışarıda bırakıldı — istenirse ayrı bir faz olarak sonra eklenebilir.
