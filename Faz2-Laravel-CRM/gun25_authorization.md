# Gün 25 — Authorization (Gates & Policies)

## Bu Derste İlk Kez Göreceğin Syntax

| Yeni syntax | Örnek | Açıklama |
|-------------|-------|----------|
| Gate | `Gate::define('delete-company', fn($user, $company) => $user->is_admin)` | Basit, isimlendirilmiş bir yetki kuralı |
| Policy | `php artisan make:policy CompanyPolicy --model=Company` | Bir model'e özel yetki kurallarını toplayan class |
| `$this->authorize()` | `$this->authorize('delete', $company)` | Controller içinde yetki kontrolü, başarısızsa exception fırlatır |
| `@can` | `@can('delete', $company)` | Blade'de koşullu gösterim, exception fırlatmaz |

---

## Senaryo: Authentication ile Authorization arasındaki fark

Gün 24'te `auth` middleware'ini ekledik — artık `/companies`'e girmek için giriş yapmış olman **yeterli**. Ama şu soruyu hiç sormadık: **giriş yapmış OLMAK, her şeyi yapabilmek anlamına mı geliyor?** Şu an `test@crm.com` ile giriş yapan biri de, yarın kayıt olacak herhangi biri de, teorik olarak (destroy() dolduğunda) **her şirketi silebilir** — çünkü kontrolümüz sadece "giriş yapmış mı" (authentication), "bu kullanıcının BU işlemi yapmaya hakkı var mı" (authorization) değil.

Bunlar iki ayrı soru:
- **Authentication (Gün 24):** Sen kimsin? → `Auth::check()`
- **Authorization (bugün):** Sen, BU işlemi, BU kayıt üzerinde yapabilir misin? → Gate/Policy

### `$this->authorize('delete', $company)` çağrıldığında gerçekte ne oluyor

```php
public function destroy(Company $company)
{
    $this->authorize('delete', $company);   // basarisizsa buradan sonrasi HIC calismaz
    $company->delete();
    return redirect()->route('companies.index');
}
```

Adım adım:
1. `$this->authorize('delete', $company)`, Laravel'in `Gate` sistemine "delete" adlı yetkiyi `$company` nesnesi için kontrol etmesini söyler
2. Gate, `$company`'nin tipine bakar (`App\Models\Company`) ve bu model için kayıtlı bir **Policy** olup olmadığını arar — **convention over configuration**: `Company` modeli için otomatik olarak `App\Policies\CompanyPolicy` aranır (Gün 19'daki `Company` → `companies` tablo eşlemesiyle aynı isimlendirme mantığı)
3. Bulunca, `CompanyPolicy::delete($user, $company)` metodunu çağırır — `$user` **otomatik olarak** `Auth::user()`'dan (şu an giriş yapmış kişi) enjekte edilir, sen bunu elle geçirmezsin
4. Bu metot `true` dönerse, `authorize()`'dan sonraki kod çalışmaya devam eder
5. `false` dönerse (ya da metot kendisi bir exception fırlatırsa), `authorize()` bir `AuthorizationException` **fırlatır** (Gün 10'un mekanizması!) — bu da Laravel'in varsayılan handler'ı tarafından yakalanıp **403 Forbidden** response'una çevrilir. `destroy()`'un geri kalanı **hiç çalışmaz**.

---

## Analoji

Authentication, binaya giren herkesin kart okutmasıdır (Gün 24). Authorization ise, kartını okuttuktan sonra, "bu kata çıkabilir misin, bu odayı açabilir misin" diye **her kapıda ayrı ayrı** kontrol edilmesidir. Kart geçerli olması (giriş yapmış olman), her kapının açılacağı anlamına gelmez.

---

## Teorik

### Gate — basit, tek kural

```php
// AppServiceProvider::boot() icinde
Gate::define('delete-company', function (User $user, Company $company) {
    return $user->is_admin;
});
```

Kullanımı: `Gate::allows('delete-company', $company)` (bool döner) ya da `Gate::authorize('delete-company', $company)` (başarısızsa exception fırlatır). Gate, tek bir closure'a bağlı — bir modelin **birden fazla** işlemi (view, create, update, delete) için ayrı ayrı Gate tanımlamak dağınıklaşır. Bunun için Policy var.

### Policy — model bazlı, toplu

```
php artisan make:policy CompanyPolicy --model=Company
```

```php
class CompanyPolicy
{
    public function delete(User $user, Company $company): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Company $company): bool
    {
        return $user->is_admin;
    }
}
```

Her metot bir "yetenek" (ability) temsil eder — `delete`, `update`, `view`, `create` gibi standart isimler otomatik eşleşir (`$this->authorize('delete', $company)` → `CompanyPolicy::delete()`). CRM büyüdükçe her model için ayrı bir Policy class'ı, düzenli bir yapı sağlar.

> Not: `is_admin` kolonu şu an `users` tablosunda **yok** — kodu yazarken önce bunu ekleyen bir migration gerekecek.

### Blade'de `@can`

```blade
@can('delete', $company)
    <form method="POST" action="{{ route('companies.destroy', $company) }}">
        @csrf @method('DELETE')
        <button type="submit">Sil</button>
    </form>
@endcan
```

**Mekanik fark:** `$this->authorize()` başarısız olunca **exception fırlatır** (sayfa 403 gösterir). `@can` ise sadece `Gate::allows(...)`'u kontrol eder (bool), başarısızsa **hiçbir hata vermez, sadece o bloğu render etmez** — "Sil" butonu admin olmayan bir kullanıcıya hiç görünmez bile. İkisi genelde birlikte kullanılır: `@can` butonu gizler (kullanıcı deneyimi), `$this->authorize()` sunucu tarafında gerçek güvenliği sağlar (çünkü `@can` sadece görünürlüğü kontrol eder — biri butonu görmeden bile doğrudan `DELETE /companies/5` isteği atabilir, bu yüzden controller'daki `authorize()` **asıl güvenlik katmanı**).

---

## C# ile Karşılaştırma

| Konu | ASP.NET Core | Laravel |
|------|---------------|---------|
| Basit yetki kontrolü | Manuel `IAuthorizationHandler` | Gate |
| Resource-based (model'e özel) authorization | `IAuthorizationHandler` + resource parametresi, `[Authorize(Policy = "...")]` | Policy — doğrudan eşleşir |
| Controller'da kontrol | `await AuthorizationService.AuthorizeAsync(User, resource, "policy")` | `$this->authorize('ability', $model)` |
| View'da koşullu gösterim | `@if ((await AuthorizationService.AuthorizeAsync(...)).Succeeded)` | `@can('ability', $model)` |

**Kritik çıkarım:** Laravel'in Policy sistemi, ASP.NET Core'un resource-based authorization'ıyla neredeyse birebir aynı fikri uyguluyor — ikisi de "bu kullanıcı, bu belirli kayıt üzerinde, bu işlemi yapabilir mi" sorusuna cevap veriyor. Fark, Laravel'in bunu **convention** (model adı → Policy adı) ile otomatikleştirmesi; ASP.NET'te policy'leri elle `Program.cs`'te kaydetmen gerekir.

---

## Bugün CRM'de Ne Ekleyeceğiz

Önce `users` tablosuna `is_admin` kolonu ekleyen bir migration, sonra `CompanyPolicy` (`delete` metodu — sadece admin), `CompanyController::destroy()` doldurulup `$this->authorize('delete', $company)` eklenecek, `companies/show.blade.php`'de `@can('delete', $company)` ile silme butonu sadece admin'e gösterilecek.

---

## Kendini Test Et

1. `$this->authorize()` ile `@can` arasındaki fark ne — hangisi exception fırlatır, hangisi sadece görünürlüğü kontrol eder?
2. Bir kullanıcı admin olmadığı halde, "Sil" butonunu hiç görmeden doğrudan `DELETE /companies/5` isteği atarsa ne olur — `@can` bunu engeller mi?
3. `$this->authorize('delete', $company)` çağrıldığında Laravel hangi Policy class'ını, nasıl bulur?
