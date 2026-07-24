# Gün 28 — API Resources & JSON Response

## Bu Derste İlk Kez Göreceğin Syntax

| Yeni syntax | Örnek | Açıklama |
|-------------|-------|----------|
| Resource oluşturma | `php artisan make:resource CompanyResource` | Model → JSON dönüşüm katmanı |
| `toArray()` | `public function toArray($request) { return [...]; }` | Hangi alanların, nasıl JSON'a döneceğini tanımlar |
| `::collection()` | `CompanyResource::collection($companies)` | Bir listeyi topluca dönüştürür |
| `routes/api.php` | (dosya) | Stateless API route'ları |

---

## Senaryo: Neden `return response()->json($company)` yeterli değil

En basit hâliyle şunu yazabilirdin:

```php
Route::get('/api/companies', function () {
    return response()->json(Company::all());   // Eloquent modelini DOGRUDAN JSON'a cevir
});
```

Bunu çalıştırırsan, dönen JSON'da **modelin tüm kolonları** olduğu gibi çıkar:

```json
{"id": 5, "name": "Delta A.Ş.", "city": "Bursa", "logo_path": "logos/L7AmGsli....png", "created_at": "...", "updated_at": "..."}
```

İki gerçek sorun var:
1. **`logo_path` ham bir relative path** (`"logos/xyz.png"`) — Gün 27'de `Storage::url()` ile tam URL'e çevirmemiz gerektiğini öğrenmiştik. Dışarıdaki bir sistem bu path'i **tek başına kullanamaz**, tam URL'e ihtiyacı var.
2. **Veritabanı şeman, doğrudan API'nin dış yüzü oluyor.** Yarın `companies` tablosuna hassas bir kolon eklersen (örn. iç notlar), o kolon da **otomatik olarak** JSON'a sızar — sen istemesen bile. Ayrıca bir kolon adını değiştirirsen (örn. `city` → `sehir`), API'yi kullanan dış sistem **hiç haberin olmadan** kırılır.

**API Resource**, bu ikisini çözer: DB şeması ile API'nin dışarıya gösterdiği şekil arasına bilinçli bir **dönüşüm katmanı** koyar — tıpkı Gün 19'daki `$fillable`'ın "hangi alanlar İÇERİ yazılabilir" sorusuna cevap vermesi gibi, Resource da "hangi alanlar DIŞARI çıkar, nasıl görünür" sorusuna cevap verir.

```php
class CompanyResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'city' => $this->city,
            'logo_url' => $this->logo_path ? Storage::url($this->logo_path) : null,   // artik TAM URL
            // logo_path'in kendisi, created_at, updated_at -> BILINCLI OLARAK DISARIDA BIRAKILDI
        ];
    }
}
```

---

## Analoji

Ham Eloquent modelini JSON'a çevirmek, mutfağın (veritabanının) içini olduğu gibi müşteriye göstermek gibidir — hangi malzemenin nerede durduğu, hangi tencerenin kirli olduğu her şey görünür. API Resource, o mutfaktan çıkacak **tabağı** hazırlayan aşçı gibidir — müşteriye sadece sunulması gereken, düzgün sunulan kısmı verir, mutfağın iç düzeni (DB şeması) değişse bile müşterinin gördüğü tabak aynı kalabilir.

---

## Teorik

### Resource oluşturma ve `toArray()`

```
php artisan make:resource CompanyResource
```

Üretilen class'ın tek işi `toArray($request)` metodunu doldurmak — bu metot, `$this->` üzerinden (Resource, arkadaki modelin proxy'sidir) modelin alanlarına erişip istediğin **array yapısını** döndürür.

### Tekil kullanım vs `::collection()`

```php
return new CompanyResource($company);        // tek bir kayit
return CompanyResource::collection($companies);  // bir liste (her eleman icin toArray() calisir)
```

**Ne zaman ne oluyor:** Controller'dan bir `CompanyResource`/`CompanyResource::collection(...)` `return` edildiğinde, Laravel bunun bir Resource olduğunu tanır, otomatik olarak her kayıt için `toArray()`'i çalıştırır, sonucu JSON'a çevirir ve `Content-Type: application/json` header'ıyla birlikte response üretir — sen elle `response()->json(...)` yazmana gerek kalmaz (`::collection()` kullanınca sonuç varsayılan olarak `{"data": [...]}` şeklinde bir zarf içine alınır).

### API route'ları — `routes/api.php`

Gün 15'te not etmiştik: Laravel 11+'da `routes/api.php` **varsayılan gelmiyor**. Eklemek için:

```
php artisan install:api
```

Bu komut hem `routes/api.php`'yi oluşturur hem **Sanctum**'u kurar (Gün 24'te bahsettiğimiz token tabanlı auth paketi). `routes/api.php`'deki route'lar **stateless**'tir — `web.php`'nin aksine session/CSRF koruması yoktur, her istek kendi kimlik bilgisini (token) taşır.

```php
// routes/api.php
Route::get('/companies', function () {
    return CompanyResource::collection(Company::all());
});
```

### `response()->json([...], 201)`

```php
return response()->json(['message' => 'Şirket oluşturuldu'], 201);   // 201 Created
```

İkinci parametre HTTP status code — Resource kullanmadığın, elle bir JSON response üretmen gereken durumlarda (örn. bir işlem sonucu mesajı) kullanılır.

---

## C# ile Karşılaştırma

| Konu | ASP.NET Core | Laravel |
|------|---------------|---------|
| Model → dış response dönüşümü | DTO + AutoMapper (ya da elle mapping) | API Resource (`toArray()`) |
| Liste dönüşü | `IEnumerable<CompanyDto>` | `CompanyResource::collection(...)` |
| API route ayrımı | `[ApiController]` + ayrı route prefix | `routes/api.php`, ayrı dosya |
| Auth | JWT Bearer middleware | Sanctum (`install:api` ile gelir) |

**Kritik çıkarım:** API Resource, ASP.NET'teki "asla Entity'yi doğrudan dışarı verme, her zaman bir DTO'ya map et" prensibinin Laravel karşılığı — aynı disiplin, farklı syntax. İkisinin de kök sebebi aynı: iç veri modelini dış sözleşmeden **ayırmak**.

---

## Bugün CRM'de Ne Ekleyeceğiz

`php artisan install:api` ile `routes/api.php` + Sanctum kurulacak, `CompanyResource` oluşturulup `logo_path`'i `logo_url`'e çevirecek, `/api/companies` endpoint'i eklenecek.

---

## Kendini Test Et

1. `return response()->json(Company::all())` ile `return CompanyResource::collection(Company::all())` arasındaki fark, `logo_path` alanı için somut olarak ne değiştirir?
2. `routes/api.php`'deki route'lar `routes/web.php`'dekilerden hangi bakımdan farklı davranır (session/CSRF)?
3. Veritabanında bir kolon adı değişirse, API Resource kullanıyorsan dış sisteme etkisi ne olur — Resource kullanmasaydın ne olurdu?
