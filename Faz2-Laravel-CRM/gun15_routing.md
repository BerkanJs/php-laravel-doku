# Gün 15 — Routing

## Bu Derste İlk Kez Göreceğin Syntax

| Yeni syntax | Örnek | Açıklama |
|-------------|-------|----------|
| Route tanımı | `Route::get('/companies', [CompanyController::class, 'index']);` | URL → controller metodu eşlemesi |
| Route parametresi | `Route::get('/companies/{id}', ...)` | URL'den değişken bir parça yakalama |
| Route model binding | `Route::get('/companies/{company}', ...)` | Parametreyi otomatik bir Eloquent modeline çevirme |
| Named route | `route('companies.show', $id)` | Route'a isim vererek URL üretme |
| Route grubu | `Route::prefix('admin')->group(function () {...});` | Ortak prefix/middleware ile birden fazla route tanımlama |
| Resource routing | `Route::resource('companies', CompanyController::class);` | 7 CRUD route'unu tek satırda üretme |

---

## Senaryo

CRM'de bir şirketin detay sayfasını göstermek istiyorsun: `/companies/5` adresine gidildiğinde, ID'si 5 olan `Company` kaydını bulup göstermek. C#/ASP.NET Core'da bunu şöyle yazardın:

```csharp
[HttpGet("companies/{id}")]
public IActionResult Show(int id)
{
    var company = _context.Companies.Find(id);   // veritabanından ELLE çekiyorsun
    if (company == null) return NotFound();
    return View(company);
}
```

Laravel'de aynı işi **route tanımının içine parametre tipini yazarak** yaptırabilirsin:

```php
Route::get('/companies/{company}', function (Company $company) {
    return view('companies.show', ['company' => $company]);
});
```

Dikkat: burada `Company::find($id)` diye bir satır **yok**. `{company}` parametresini `Company $company` tipiyle karşılayınca, Laravel URL'deki ID'yi alıp veritabanında **otomatik** arar, bulamazsa **otomatik** 404 döner, bulursa hazır `Company` nesnesini fonksiyona/controller'a inject eder. Buna **route model binding** deniyor — Gün 8'de gördüğümüz "container otomatik resolve eder" mantığının route parametrelerine uygulanmış hâli.

Bugünün konusu: URL'lerin nasıl kod'a bağlandığı — bu binding dahil.

---

## Analoji

Bir vale (vale parking) düşün. Sen valeye sadece bir **fiş numarası** (`{id}`) verirsin. Vale, o numarayı alıp arka planda arabayı (gerçek `Company` nesnesini) bulup önüne getirir — sen otoparka gidip aramazsın. Route model binding tam olarak bu: sen URL'de sadece bir ID görürsün, ama controller'ın eline geçen şey zaten **hazır, veritabanından çekilmiş nesnenin kendisi**.

Route grupları ise ortak bir özelliği (prefix, middleware) paylaşan route'ları tek bir "zarfın" içine koymak gibidir — her birine ayrı ayrı aynı etiketi yapıştırmak yerine, hepsini aynı zarfa koyup zarfın üstüne bir kere yazarsın.

---

## Teorik

### `web.php` vs `api.php`

- `routes/web.php` — tarayıcı isteği bekler, **session ve CSRF koruması** otomatik uygulanır (Gün 22'de CSRF'i göreceğiz)
- `routes/api.php` — stateless, token tabanlı (Laravel 11+'da varsayılan gelmiyor, Gün 28'de `php artisan install:api` ile ekleyeceğiz)

CRM'in web arayüzü (`companies`, `contacts` sayfaları) `web.php`'e yazılacak.

### Temel route tanımı

```php
Route::get('/companies', [CompanyController::class, 'index']);
Route::post('/companies', [CompanyController::class, 'store']);
```

`Route::get`/`post`/`put`/`delete` — HTTP metoduna göre ayrı tanım. `[CompanyController::class, 'index']` — hangi controller'ın hangi metodunun çalışacağı (C#'taki `[HttpGet]` attribute'unun controller action'a bağlanmasıyla aynı fikir, ama Laravel'de bu eşleme controller'ın İÇİNDE değil, **ayrı bir dosyada** merkezi olarak tutulur).

### Route parametreleri ve route model binding

```php
Route::get('/companies/{id}', function ($id) {
    // $id sadece bir string/int, Company nesnesi DEĞİL
});

Route::get('/companies/{company}', function (Company $company) {
    // $company hazır, veritabanından çekilmiş bir Company nesnesi
    // bulunamazsa Laravel OTOMATİK 404 döner - if (company == null) yazmana gerek yok
});
```

Fark, parametre adı ile tip belirtimi: `{company}` yazıp fonksiyon/metotta `Company $company` tipini belirtirsen, Laravel bunu otomatik model'e çevirir — parametre adı model'in **değişken adıyla eşleşmeli** (varsayılan olarak `id` kolonuna göre arar).

### Named routes

```php
Route::get('/companies/{company}', [CompanyController::class, 'show'])->name('companies.show');

// Kullanımı:
route('companies.show', $company->id);   // "/companies/5" üretir
```

URL'yi elle string olarak yazmak yerine isimle üretmek — URL yapısı değişse bile (`/companies/5` → `/firmalar/5`), `route('companies.show', ...)` çağıran her yer otomatik doğru URL'i üretmeye devam eder.

### Route grupları

```php
Route::prefix('admin')->middleware('auth')->group(function () {
    Route::get('/companies', [CompanyController::class, 'index']);
    Route::get('/contacts', [ContactController::class, 'index']);
});
```

Her iki route da otomatik `/admin/companies`, `/admin/contacts` olur ve ikisine de `auth` middleware'i uygulanır — tek tek yazmak yerine grup tanımı.

### Resource routing

```php
Route::resource('companies', CompanyController::class);
```

Bu **tek satır**, şu 7 route'u otomatik oluşturur:

| Metod | URL | Controller metodu | Named route |
|-------|-----|--------------------|--------------|
| GET | `/companies` | `index` | `companies.index` |
| GET | `/companies/create` | `create` | `companies.create` |
| POST | `/companies` | `store` | `companies.store` |
| GET | `/companies/{company}` | `show` | `companies.show` |
| GET | `/companies/{company}/edit` | `edit` | `companies.edit` |
| PUT/PATCH | `/companies/{company}` | `update` | `companies.update` |
| DELETE | `/companies/{company}` | `destroy` | `companies.destroy` |

Bu yedi metodun hepsi `CompanyController`'da (Gün 16'da yazacağız) tanımlı olmalı.

---

## C# ile Karşılaştırma

| Konu | C#/ASP.NET Core | Laravel |
|------|------------------|---------|
| Route tanımlama şekli | Convention-based routing YA DA attribute routing (`[HttpGet("companies/{id}")]`) — controller'ın İÇİNDE | Her zaman **explicit**, `routes/web.php` gibi ayrı bir dosyada merkezi — attribute routing'e daha yakın ama controller'dan fiziksel olarak ayrı |
| Model binding | Var, ama entity'yi DB'den çekmek genelde elle (`_context.Find(id)`) | `{company}` + tip belirtimi yeterli — Laravel DB sorgusunu ve 404 kontrolünü kendisi yapar |
| Route grupları | `[Route("admin/[controller]")]` ya da `MapGroup()` (minimal API) | `Route::prefix()->middleware()->group()` |
| Toplu CRUD route üretimi | `MapControllers()` + controller convention'ı | `Route::resource()` — 7 route'u tek satırda, isimlendirmesi dahil |

**Kritik çıkarım:** Laravel'de convention-based routing yok — her route'un `routes/web.php`'de **açıkça** tanımlı olması gerekir (ASP.NET'in attribute routing'ine benziyor, ama route tanımları controller'ın kendisinde değil, ayrı, merkezi bir dosyada duruyor). `Route::resource` bu açıklığı korurken, tekrarı ortadan kaldırıyor.

---

## Bugün CRM'de Ne Ekleyeceğiz

`routes/web.php`'e `companies` için `Route::resource(...)` eklenecek — kodu ayrı adımda, Gün 16'daki `CompanyController` ile birlikte yazacağız (controller olmadan resource route'u test edemeyiz, 7 metottan biri çağrılınca "controller metodu yok" hatası alırız).

---

## Kendini Test Et

1. Route model binding olmadan bir company'yi ID'den çekmek kaç satır kod gerektirirdi?
2. `Route::resource('companies', CompanyController::class)` hangi 7 route'u, hangi controller metotlarıyla eşleştirerek üretir?
