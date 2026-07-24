# Gün 30 — Test Temelleri: PHPUnit / Pest

## Bu Derste İlk Kez Göreceğin Syntax

| Yeni syntax | Örnek | Açıklama |
|-------------|-------|----------|
| Feature test | `$this->get('/companies');` | Gerçek bir HTTP isteğini SİMÜLE eder |
| `assertStatus()` | `$response->assertStatus(200);` | Response'un durumunu doğrular |
| `RefreshDatabase` | `use RefreshDatabase;` | Her testte temiz bir veritabanı garantiler |
| Factory | `Company::factory()->create()` | Sahte ama gerçekçi test verisi üretir |
| `actingAs()` | `$this->actingAs($user)->get(...)` | Testi giriş yapmış bir kullanıcı olarak çalıştırır |

---

## Senaryo: Bu oturum boyunca elle yaptığımız şeyi otomatikleştirmek

Gün 15'ten beri her yeni özellik için aynı döngüyü **elle** tekrarladık: `php artisan serve` başlat → `curl` ile istek at → response'u kontrol et → `tinker`'la veritabanını sorgula → sunucuyu kapat, temizle. Gün 25'te "admin olmayan biri silemiyor mu" diye test ederken tam olarak bunu yaptık — login, `curl` ile istek, response kontrolü, tinker ile DB kontrolü.

**Feature test, bu döngünün tamamını otomatikleştirir:**

```php
public function test_admin_olmayan_kullanici_sirket_silemez(): void
{
    $user = User::factory()->create(['is_admin' => false]);   // Gun 25'teki test@crm.com'un otomatik ureteni
    $company = Company::factory()->create();

    $response = $this->actingAs($user)->delete("/companies/{$company->id}");   // curl + login yerine TEK satir

    $response->assertStatus(403);                              // grep yerine TEK satir
    $this->assertDatabaseHas('companies', ['id' => $company->id]);   // tinker'daki count() kontrolu yerine
}
```

Bu testi çalıştırdığında, Gün 25'te 10 dakikamızı alan (server başlat, login, curl, tinker, temizle) süreç **saniyeler içinde, otomatik, tekrar tekrar** çalışır.

---

## Analoji

Manuel test, her ürünü elle kontrol eden bir kalite kontrolcü gibidir — doğru ama yavaş, ve insan unutabilir ("bu sefer boş email'i test etmeyi unuttum"). Otomatik test, aynı kontrolleri **hiç yorulmadan, hiç unutmadan, her seferinde aynı sırayla** yapan bir robot kolu gibidir — bir değişiklik yaptığında, "her şey hâlâ çalışıyor mu" sorusunun cevabını dakikalar değil saniyeler içinde alırsın.

---

## Teorik

### `$this->get()` ne yapıyor — gerçek bir HTTP isteği mi

**Hayır.** `$this->get('/companies')` bir socket açıp gerçek bir TCP bağlantısı kurmaz — Laravel'in kernel'ini (Gün 14.5'teki `bootstrap/app.php`'nin kurduğu uygulamayı) **doğrudan, aynı PHP process içinde** çağırır, sahte bir `Request` nesnesi oluşturup pipeline'dan (Gün 23'teki middleware zinciri dahil) geçirir. Bu yüzden çok hızlıdır — gerçek bir `php artisan serve` başlatmana hiç gerek yok.

### `RefreshDatabase` — her test gerçekten "temiz" mi

Gerçek kaynağa bakınca (`vendor/laravel/framework/.../RefreshDatabase.php`), mekanizma şöyle:
1. Test suite'i **ilk** çalıştığında `migrate:fresh` çalışır (tüm tablolar sıfırdan kurulur) — bu **sadece bir kere** olur
2. Her test metodundan **önce**, o testin veritabanı bağlantısında bir **transaction başlatılır** (`beginTransaction()`)
3. Test bitince (test başarılı da olsa başarısız da olsa) bu transaction **rollback edilir**

Yani her test, kendi yaptığı tüm `INSERT`/`UPDATE`/`DELETE`'leri görür (transaction içinde açık), ama test bitince bu değişiklikler **hiç commit edilmeden geri alınır** — bir sonraki test, bir öncekinin bıraktığı hiçbir veriyi görmez. Bu, Gün 19'da CRUD işlerken hep elle yaptığımız "test kaydını sil, veritabanını temiz bırak" işleminin **otomatik** hâli.

### Factory — sahte veri üretimi

```php
// database/factories/CompanyFactory.php
public function definition(): array
{
    return [
        'name' => fake()->company(),
        'city' => fake()->city(),
    ];
}
```

```php
$company = Company::factory()->create();          // veritabanina GERCEKTEN INSERT eder, Faker ile sahte ama gecerli veri uretir
$companies = Company::factory()->count(5)->create();   // 5 tane
```

`fake()` (Faker kütüphanesi), gerçekçi görünen rastgele veri üretir (`"Acme Corporation"`, `"İstanbul"` gibi) — Gün 19'daki `Company::create([...])` ile aynı mekanizmayı kullanır, sadece değerler elle değil Faker'dan gelir.

### `actingAs()` — auth gerektiren route'ları test etmek

Bizim `companies`/`contacts`/`deals` route'larımız Gün 24'ten beri `auth` middleware'i ile korunuyor — testte gerçek bir login formu doldurmak yerine:

```php
$user = User::factory()->create();
$response = $this->actingAs($user)->get('/companies');   // Auth::login($user) gibi dusun, ama test icin kisayol
```

### Unit test vs Feature test

- **Feature test** — gerçek HTTP + gerçek (test) veritabanı, "baştan sona çalışıyor mu" sorusuna cevap verir. Laravel'de çoğu test bu türdedir.
- **Unit test** — framework'ten bağımsız, tek bir class/metodun saf mantığını test eder (örn. bir hesaplama fonksiyonu) — DB/HTTP yok.

---

## C# ile Karşılaştırma

| Konu | C#/.NET | Laravel |
|------|---------|---------|
| Assertion | xUnit + FluentAssertions (`response.StatusCode.Should().Be(200)`) | `$response->assertStatus(200)` |
| In-process HTTP test | `WebApplicationFactory` | Feature test (`$this->get()`) — kavramsal olarak birebir aynı yaklaşım |
| Test verisi üretimi | Bogus + EF Core seed | Factory (Faker tabanlı) |
| Test DB izolasyonu | Genelde elle (in-memory DB ya da transaction scope) | `RefreshDatabase` — framework içinde hazır |

**Kritik çıkarım:** `$this->get()`'in gerçek bir socket açmadan, kernel'i doğrudan çağırarak çalışması — `WebApplicationFactory`'nin ASP.NET Core'da yaptığının birebir aynısı. İkisi de "gerçek sunucu başlatmadan, gerçek pipeline'ı test et" fikrini uyguluyor.

---

## Bugün CRM'de Ne Ekleyeceğiz

`CompanyFactory` (henüz yok), `CompanyControllerTest` — şirket oluşturma, listeleme, ve **Gün 25'te elle yaptığımız** "admin olmayan kullanıcı silemiyor" senaryosunu otomatik test eden 3 test metodu.

---

## Kendini Test Et

1. `$this->get('/companies')` gerçek bir HTTP sunucusu başlatıyor mu? Başlatmıyorsa, nasıl bir istek "simüle ediliyor"?
2. `RefreshDatabase` her testte gerçekten `migrate:fresh` mi çalıştırıyor, yoksa başka bir mekanizma mı kullanıyor?
3. `actingAs($user)` kullanmadan `auth` middleware'i korumalı bir route'u test etmeye çalışsan ne olurdu?
