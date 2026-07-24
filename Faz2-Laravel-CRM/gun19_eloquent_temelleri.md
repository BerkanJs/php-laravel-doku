# Gün 19 — Eloquent ORM Temelleri

## Bu Derste İlk Kez Göreceğin Syntax

| Yeni syntax | Örnek | Açıklama |
|-------------|-------|----------|
| Eloquent Model | `class Company extends Model {}` | Her tablo için bir class — senin "entity"n karşılığı |
| CRUD | `Company::create([...])`, `Company::find($id)`, `$company->update([...])`, `$company->delete()` | Model üzerinden doğrudan veritabanı işlemleri |
| `$fillable` | `protected $fillable = ['name', 'city'];` | Toplu atamada (mass assignment) hangi kolonlara izin verildiği |
| Query Builder | `Company::where('city', 'İstanbul')->orderBy('name')->get()` | Fluent, zincirlenebilir sorgu oluşturma |

---

## Senaryo

Az önce sorduğun soruyla tam örtüşüyor: migration'ı yazdık ([create_companies_table.php](c:\Users\BERKAN\Desktop\PhpLaravelDoku\Faz2-Laravel-CRM\crm-app\database\migrations\2026_07_24_145904_create_companies_table.php)), ama henüz "entity" karşılığımız (`Company` class'ı) yok. Bugün onu yazıyoruz:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = ['name', 'city'];
}
```

Bu kadar. `Company` class'ı **hiçbir yerde** "benim tablom `companies`" diye açıkça belirtmiyor — Laravel bunu **convention** ile (class adının çoğulu, snake_case) otomatik çıkarıyor. EF Core'da `DbSet<Company> Companies` ile `DbContext`'e açıkça eklemen gerekirdi; burada böyle bir kayıt yok, sadece isimlendirme kuralına güveniliyor.

Şimdi `CompanyController::index()`'teki sabit diziyi gerçek sorguyla değiştirebiliriz:

```php
// Onceki (Gun 15-18): sabit dizi
$companies = [['id' => 1, 'name' => 'Acme A.S.', ...], ...];

// Simdi: gercek veritabani sorgusu
$companies = Company::all();
```

---

## Analoji

`Company::create([...])` yazdığında, `Company` class'ının **kendisi** hem "bu bir şirket" bilgisini hem "veritabanına nasıl gideceğini" biliyor — tek bir nesne hem kimliğini hem de nasıl kaydedileceğini taşıyor. EF Core'da bu iki iş ayrılır: `Company` sadece veriyi taşır (dumb POCO), veritabanına gitme işini `DbContext` yapar. Bu, aynı işi yapan iki farklı iş bölümü şekli — biri "hepsi bir arada" (Active Record), diğeri "ayrı sorumluluklar" (Data Mapper).

---

## Teorik

### Eloquent Model ve convention over configuration

```php
class Company extends Model {}
```

Hiçbir ek konfigürasyon olmadan, Laravel şunu varsayar: `Company` class'ı → `companies` tablosu (çoğul, snake_case), `id` primary key, `created_at`/`updated_at` timestamp kolonları (`Schema::create`'de `$table->timestamps()` ile zaten oluşturmuştuk). Bu varsayımların hepsini override edebilirsin ama genelde gerek kalmaz.

### CRUD

```php
Company::create(['name' => 'Acme A.Ş.', 'city' => 'İstanbul']);   // INSERT

$company = Company::find(1);          // SELECT * WHERE id = 1 (bulamazsa null)
$company = Company::findOrFail(1);    // bulamazsa 404 fırlatır (route model binding'in Gün15'te gördüğümüz otomatik davranışı)

$company->update(['city' => 'Ankara']);   // UPDATE

$company->delete();                    // DELETE
```

### `$fillable` — neden zorunlu bir güvenlik katmanı

```php
public function store(Request $request)
{
    Company::create($request->all());   // TEHLİKELİ! $request->all() ne varsa hepsini geçirir
}
```

Diyelim formda sadece `name` ve `city` alanı var ama saldırgan isteğe elle bir `is_admin=1` alanı ekleyip gönderdi — eğer `Company` tablosunda böyle bir kolon olsaydı (ya da ileride Task/User gibi bir modelde gerçekten varsa), `$request->all()` bunu da yakalar ve **isteğe bağlı olarak veritabanına yazardı**. `$fillable`, bu saldırıya karşı **beyaz liste**:

```php
protected $fillable = ['name', 'city'];   // SADECE bu ikisi toplu atamada kabul edilir
```

Artık `Company::create($request->all())` çağrılsa bile, `is_admin` gibi listede olmayan bir alan **sessizce yok sayılır**, veritabanına yazılmaz.

### Query Builder

```php
Company::where('city', 'İstanbul')
    ->orderBy('name')
    ->get();

Company::where('city', 'İstanbul')->first();   // ilk eşleşen tek kayıt
Company::count();                               // toplam kayıt sayısı
```

Fluent, zincirlenebilir syntax — her metot bir sonraki için genişletilebilir sorgu nesnesi döner, en sonda `get()`/`first()` gibi bir "tetikleyici" metotla gerçek SQL çalışır.

---

## C# ile Karşılaştırma

| Konu | EF Core | Eloquent |
|------|---------|----------|
| Temel desen | **Data Mapper** — Entity sadece veri taşır, `DbContext` üzerinden erişilir | **Active Record** — Model hem veriyi taşır hem kendi sorgu mantığını içerir |
| Tabloya bağlanma | `DbSet<Company>` ile `DbContext`'e açıkça eklenir | Convention (class adı → tablo adı), kayıt gerekmez |
| Migration ↔ Entity ilişkisi | Migration genelde Entity'den **otomatik üretilir** (`dotnet ef migrations add`) | Migration ve Model **birbirinden bağımsız, elle yazılır** — bağlantı sadece isimlendirme |
| Mass assignment koruması | Genelde DTO/ViewModel kullanıldığı için dolaylı önlenir | `$fillable`/`$guarded` — model doğrudan request'ten doldurulabildiği için **şart** |
| Sorgu syntax'ı | LINQ (expression tree, deferred execution) | Fluent method chain (`where()->orderBy()->get()`), doğrudan SQL'e çevrilir |

**Kritik çıkarım:** Eloquent'in Active Record deseni, EF Core'un Data Mapper deseninden **köklü bir mimari farktır** — bu sadece syntax farkı değil, "nesnenin sorumluluğu ne olmalı" sorusuna verilen farklı bir cevap. Bu fark testability'yi de etkiler (Gün 20'nin review sorularından biri tam bunu soruyor): Active Record'da bir Model'i mock'lamak, Data Mapper'daki gibi arayüz üzerinden kolay değildir — Eloquent Model'ler genelde gerçek (test) veritabanına karşı test edilir.

---

## Bugün CRM'de Ne Ekleyeceğiz

`app/Models/Company.php` oluşturulacak (`$fillable` ile), `CompanyController::index()`'teki sabit dizi `Company::all()` ile değiştirilecek.

---

## Kendini Test Et

1. `Company::create($request->all())` neden tehlikeli, `$fillable` bunu nasıl çözüyor?
2. Migration'da bir kolon adını değiştirip Model'i güncellemeyi unutursan ne zaman fark edersin — derleme zamanında mı, çalışma zamanında mı?
3. Active Record (Eloquent) ile Data Mapper (EF Core) arasındaki fark, bir Model'i test ederken nasıl hissettirir?
