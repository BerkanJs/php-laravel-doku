# Gün 23 — Middleware

## Bu Derste İlk Kez Göreceğin Syntax

| Yeni syntax | Örnek | Açıklama |
|-------------|-------|----------|
| Middleware class | `class LogRequests { public function handle($request, Closure $next) {...} }` | Request/response pipeline'ına giren bir katman |
| `$next($request)` | `return $next($request);` | Pipeline'da bir SONRAKİ katmana geç |
| Middleware oluşturma | `php artisan make:middleware LogRequests` | Artisan ile boş middleware iskeleti |
| Route'a middleware ekleme | `Route::middleware('auth')->group(...)` | Bir route grubuna belirli middleware'i uygulama |

---

## Senaryo: Aslında Gün 15'ten beri middleware kullanıyorsun, hiç görmeden

`companies/index.blade.php`'yi ilk yazdığımız gün (Gün 15) bir bug'a takılmıştık — hatırlarsan `storage/logs/laravel.log`'daki hata mesajının **stack trace**'inde şu isimler geçiyordu:

```
Illuminate/Session/Middleware/StartSession->handle(...)
Illuminate/Foundation/Http/Middleware/PreventRequestForgery->handle(...)  (CSRF, Gun 17/22)
Illuminate/View/Middleware/ShareErrorsFromSession->handle(...)  ($errors, Gun 22)
Illuminate/Cookie/Middleware/EncryptCookies->handle(...)
```

Bunların hepsi **middleware**. Yani `/companies`'e attığın HER istek, controller'a ulaşmadan **önce** bu katmanlardan sırayla geçiyordu — session'ı başlatan, CSRF token'ı kontrol eden, `$errors` değişkenini hazırlayan middleware'ler. Sen bunları hiç yazmadın, `bootstrap/app.php`'nin `withMiddleware()` kısmında (Gün 14.5) Laravel'in varsayılan `web` middleware grubu olarak zaten hazır geliyorlar. Bugünün konusu: bu katmanların **nasıl çalıştığı** ve kendi katmanını nasıl ekleyeceğin.

---

## Analoji

Middleware'leri iç içe soğan katmanları gibi düşün. İstek en dıştaki katmandan girer, her katman "işimi bitirdim, sıradaki katmana geç" der (`$next($request)`), en içteki katmana (controller'ına) ulaşır. Response geri dönerken **aynı katmanlardan tersten** geçer — her katmanın `$next()`'ten SONRAKİ kodu, response dışarı çıkarken çalışır.

---

## Teorik

### Middleware'in yapısı ve çalışma sırası — "önce/sonra" ne demek

```php
class LogRequests
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('ISTEK GELDI: ' . $request->path());   // $next() ONCESI - istek ICERI girerken calisir

        $response = $next($request);   // pipeline'da bir SONRAKI katmana (ya da controller'a) gec, sonucunu bekle

        Log::info('RESPONSE GIDIYOR: ' . $response->status());   // $next() SONRASI - response DISARI cikarken calisir

        return $response;
    }
}
```

**Bu kod tam olarak ne zaman, ne sırayla çalışıyor:** `$next($request)` çağrılana kadar olan kod, istek controller'a **ulaşmadan önce** çalışır. `$next($request)`'in kendisi, ya bir sonraki middleware'i ya da (hiç kalmadıysa) controller'ı çalıştırır ve onun ürettiği response'u geri verir. `$next()`'ten sonraki kod, controller çalışıp bir response ürettikten **sonra**, o response tarayıcıya gitmeden hemen önce çalışır. Birden fazla middleware varsa (mesela `auth` + `LogRequests` ikisi birden), bunlar iç içe geçer:

```
Istek gelir
  -> Middleware A'nin $next() ONCESI kodu
    -> Middleware B'nin $next() ONCESI kodu
      -> Controller calisir, response uretilir
    -> Middleware B'nin $next() SONRASI kodu
  -> Middleware A'nin $next() SONRASI kodu
Response tarayiciya gider
```

### `auth` middleware — nasıl çalışır (mantığı, kodu Gün 24'te)

```php
public function handle(Request $request, Closure $next)
{
    if (! Auth::check()) {                          // Auth::check() -> kullanici giris yapmis mi (Gun 24'te anlamli olacak)
        return redirect()->route('login');            // giris yapmamissa -> controller'a HIC ULASMADAN redirect
    }

    return $next($request);                          // giris yapmissa -> pipeline'a devam
}
```

Kritik nokta: `Auth::check()` başarısız olursa `$next($request)` **hiç çağrılmaz** — controller'ın kodu **çalışmaz bile**. Middleware, isteği controller'a ulaşmadan önce durdurabilir.

### Global middleware vs route middleware vs middleware group

- **Global middleware** — her isteğe uygulanır (örn. `TrimStrings`, Gün 14.5'te gördüğümüz varsayılanlardan biri)
- **Route middleware** — sadece belirttiğin route'lara: `Route::middleware('auth')->group(function () {...})`
- **Middleware group** — birden fazla middleware'i bir isim altında toplama (`web` grubu tam olarak bu — session, CSRF, error sharing hepsi birden)

### Custom middleware yazmak

```
php artisan make:middleware LogRequests
```

`app/Http/Middleware/LogRequests.php` üretir, sen `handle()` metodunu doldurursun. Sonra `bootstrap/app.php`'de `withMiddleware()` içinde kaydedersin (global) ya da route'ta `->middleware(LogRequests::class)` ile kullanırsın (spesifik).

---

## C# ile Karşılaştırma

| Konu | ASP.NET Core Middleware | Laravel Middleware |
|------|--------------------------|---------------------|
| Temel fikir | Pipeline, "onion" modeli | **Birebir aynı** — ikisi de aynı fikirden geliyor |
| Metot imzası | `InvokeAsync(HttpContext context, RequestDelegate next)` | `handle(Request $request, Closure $next)` |
| Sıradakine geçme | `await next(context);` | `return $next($request);` |
| Sıralama önemi | `Program.cs`'teki `app.Use...()` sırası kritik | `bootstrap/app.php`/route grubu sırası kritik — aynı risk |
| Route'a özel middleware | `[Authorize]` attribute ya da endpoint filter | `Route::middleware('auth')` |

**Kritik çıkarım:** İki framework de birebir aynı "onion" modelini kullanıyor — bu, Gün 17/22'de zaten C#'la aynı olduğunu söylediğimiz birkaç kavramdan biri daha. `$next()`'ten önceki kod isteği karşılarken, sonraki kod response'u işlerken çalışır; bir middleware `$next()`'i hiç çağırmazsa, pipeline orada durur, controller'a hiç ulaşılmaz.

---

## Bugün CRM'de Ne Ekleyeceğiz

Gerçek `auth` middleware'i route grubuna eklemek için önce bir login sisteminin (Gün 24, Breeze) var olması gerekiyor — henüz yok, `Auth::check()` her zaman false dönerdi ve her sayfa sonsuz redirect'e girerdi. Bu yüzden bugün pipeline mekaniğini **gerçek, test edilebilir** bir custom middleware (`LogRequests`) ile göstereceğiz; `auth` middleware'inin route'lara eklenmesi Gün 24 ile birlikte gelecek.

---

## Kendini Test Et

1. Bir middleware'in `handle()` metodunda `$next($request)` hiç çağrılmazsa ne olur?
2. `$next($request)`'ten ÖNCEKİ kod ile SONRAKİ kod, istek/response akışının hangi noktasında çalışır?
3. İki middleware art arda uygulanırsa (`auth` + `LogRequests`), çalışma sırası nasıl iç içe geçer?
