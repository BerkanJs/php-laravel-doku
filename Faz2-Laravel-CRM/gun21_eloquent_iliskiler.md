# Gün 21 — Eloquent İlişkiler

## Bu Derste İlk Kez Göreceğin Syntax

| Yeni syntax | Örnek | Açıklama |
|-------------|-------|----------|
| `hasMany` | `public function contacts(): HasMany { return $this->hasMany(Contact::class); }` | "Bir Company'nin birden fazla Contact'ı var" |
| `belongsTo` | `public function company(): BelongsTo { return $this->belongsTo(Company::class); }` | "Bir Contact'ın bir Company'si var" |
| `belongsToMany` | `return $this->belongsToMany(User::class);` | Many-to-many, pivot tablo üzerinden |
| Eager loading | `Company::with('contacts')->get()` | İlişkili veriyi **tek ek sorguda** önceden çeker |
| Lazy loading | `$company->contacts` | İlişkiye ilk erişildiğinde **o an** sorgu atar |

---

## Önce en temel soru: `hasMany()` çağrıldığında veritabanına gidiyor mu?

**Hayır.** Bu, bu dersin anlaşılması için en kritik nokta, o yüzden en başta netleştirelim.

```php
public function contacts(): HasMany
{
    return $this->hasMany(Contact::class);
}
```

`$company->contacts()` yazdığında (parantezli, gerçek bir metot çağrısı olarak), bu metot çalışır ve geriye bir **`HasMany` nesnesi** döner. Bu nesne veri DEĞİLDİR — "ne sorgulanması gerektiğini bilen ama henüz sorgulamamış" bir query builder'dır (Gün 19'daki `Company::where(...)` ile tamamen aynı aileden). İçinde hazır duran şey şudur: *"gerektiğinde `SELECT * FROM contacts WHERE company_id = 5` çalıştır"* (5 = o an elindeki `$company`'nin id'si) — ama bu SQL **henüz çalıştırılmamıştır.**

SQL'in gerçekten çalışması için iki yoldan biri gerekir:

**Yol 1 — açıkça `->get()` çağırmak:**
```php
$contactlar = $company->contacts()->get();   // SIMDI SQL calisti, $contactlar gercek Contact nesnelerinden olusan bir liste
```

**Yol 2 — parantezsiz erişim (`$company->contacts`):**
```php
$contactlar = $company->contacts;   // parantez YOK
```

Bu ikincisi kafa karıştırıcı çünkü `Company` class'ında gerçekte `contacts` diye bir property **yok**, sadece `contacts()` diye bir metot var. Peki `$company->contacts` (parantezsiz) yazınca ne oluyor? Burada devreye Gün 8'de gördüğümüz `__get()` magic method'u giriyor:

1. PHP, `$company` nesnesinde gerçek bir `contacts` property'si arar — bulamaz
2. Eloquent'in temel `Model` class'ındaki `__get('contacts')` tetiklenir
3. Eloquent kontrol eder: "`contacts` adında bir metot var mı? Evet. Bu metot bir ilişki (`HasMany`, `BelongsTo` vb.) mi döndürüyor? Evet."
4. Eloquent o metodu senin yerine çağırır, sonucuna `->get()` uygular — **SQL burada, tam bu adımda çalışır**
5. Sonucu `$company` nesnesinin içine **cache'ler** — aynı `$company` üzerinde `->contacts`'a tekrar erişirsen, SQL bir daha çalışmaz, cache'ten döner

Yani `$company->contacts` yazman, parantez yazmadığın için "daha basit" görünüyor ama arka planda **hem metot çağrılıyor hem SQL çalıştırılıyor** — sadece bunu senin yerine Eloquent yapıyor.

---

## Analoji

`hasMany()` metodunun kendisi bir **sipariş formu** doldurmak gibidir — "bana şu şirkete ait kişileri getir" yazılı bir kağıt hazırlarsın, ama kağıdı henüz kimseye vermedin, mutfağa (veritabanına) hiçbir şey gitmedi. `->get()` çağırmak ya da parantezsiz `->contacts` yazmak, o kağıdı mutfağa **fiilen göndermek** — işte tam o an mutfak (veritabanı) çalışmaya başlıyor.

---

## Teorik

### `hasMany` / `belongsTo` — ikisi de aynı foreign key'i, farklı yönden okur

```php
// Company.php
public function contacts(): HasMany
{
    return $this->hasMany(Contact::class);
    // calistiginda uretilecek SQL: SELECT * FROM contacts WHERE company_id = [bu company'nin id'si]
}

// Contact.php
public function company(): BelongsTo
{
    return $this->belongsTo(Company::class);
    // calistiginda uretilecek SQL: SELECT * FROM companies WHERE id = [bu contact'in company_id'si]
}
```

İkisi de Gün 18'de migration'da tanımladığımız `contacts.company_id` kolonunu kullanıyor — `hasMany` "bana bağlı olanları getir" (bir çoktan), `belongsTo` "benim bağlı olduğumu getir" (çoktan bire) yönünde okuyor. Hangi tarafın `hasMany`, hangisinin `belongsTo` olduğuna karar veren şey: foreign key'i **fiziksel olarak tutan taraf** (`contacts.company_id`) her zaman `belongsTo` tarafıdır.

### `belongsToMany` (many-to-many) — bugün kullanmayacağız ama mekanizması

```php
public function users(): BelongsToMany
{
    return $this->belongsToMany(User::class);
}
```

One-to-many'den farklı olarak, iki tablo arasında **üçüncü bir pivot tablo** gerekir (örn. `deal_user` — hem `deal_id` hem `user_id` kolonlu ayrı bir tablo). Çalıştığında ürettiği SQL bir `JOIN` içerir: `SELECT users.* FROM users INNER JOIN deal_user ON ... WHERE deal_user.deal_id = ...`. CRM'in bu aşamasında ihtiyacımız yok, ileride (bir Deal'e birden fazla satış temsilcisi atanabilirse) gerekebilir.

### N+1 problemi — "kaç SQL sorgusu gerçekten çalışıyor"

```php
$companies = Company::all();
// SU AN 1 SQL calisti: "SELECT * FROM companies"
// $companies, 10 tane gercek Company nesnesinden olusan bir liste (Collection)

foreach ($companies as $company) {
    echo $company->contacts->count();
    // HER DONGU ADIMINDA: $company->contacts parantezsiz erisim -> __get tetiklenir
    // -> "SELECT * FROM contacts WHERE company_id = X" SIMDI calisir (X o adimdaki company'nin id'si)
}
// TOPLAM: 1 (companies) + 10 (her company icin ayri contacts sorgusu) = 11 SQL sorgusu
```

10 şirket varsa **11 ayrı veritabanı gidiş-gelişi** oluyor — her biri network/DB gecikmesi taşıyor. 1000 şirket olsa 1001 sorgu olurdu. Buna **N+1 problemi** denir.

### Eager loading (`with()`) — aynı işi kaç sorguya indiriyor

```php
$companies = Company::with('contacts')->get();
// BU SATIR calistiginda TAM OLARAK 2 SQL sorgusu calisir:
//   1) SELECT * FROM companies
//   2) SELECT * FROM contacts WHERE company_id IN (1, 2, 3, ..., 10)   <- TUM ilgili contact'lar TEK sorguda
// Eloquent, hangi contact'in hangi company'ye ait oldugunu bellekte eslestirip
// her $company nesnesinin icine ONCEDEN yerlestirir (cache'ler)

foreach ($companies as $company) {
    echo $company->contacts->count();
    // $company->contacts parantezsiz erisilse bile YENI SQL CALISMAZ
    // cunku veri yukarida ADIM 2'de zaten cekilip $company'nin icine kondu
}
// TOPLAM: 2 SQL sorgusu (10 sirket de olsa, 1000 sirket de olsa DEGISMEZ)
```

Kural basit: bir ilişkiye bir döngü içinde erişeceksen, döngüden **önce** `with()` ile eager load et — kaç kayıt olursa olsun sorgu sayısı sabit kalır.

---

## C# ile Karşılaştırma

| Konu | EF Core | Eloquent |
|------|---------|----------|
| One-to-many | `ICollection<Contact> Contacts` (navigation property) | `hasMany()` metodu |
| Many-to-one | `Company Company` (navigation property) | `belongsTo()` metodu |
| Metot çağrıldığında DB'ye gidiyor mu | `.Contacts` erişimi lazy loading açıksa proxy üzerinden tetiklenir | `hasMany()`'nin KENDİSİ asla gitmez — sadece `->get()` ya da parantezsiz erişim (`__get`) tetikler |
| N+1'i önleme | `.Include(c => c.Contacts)` | `->with('contacts')` |
| Lazy loading riski | Proxy ile lazy loading açıksa aynı risk var | Aynı risk — Eloquent'te bu **her zaman, varsayılan olarak** açık, kapatma seçeneği yok |

**Kritik çıkarım:** `with()` ile `.Include()` **kavramsal olarak birebir aynı çözüm** — ikisi de "ilişkili veriyi tek ek sorguda, döngüden önce çek" fikrini uyguluyor, ikisi de sorgu sayısını N+1'den 2'ye indiriyor. Fark, Eloquent'te lazy loading'in **her zaman açık ve varsayılan** olması — EF Core'da bunu sen açıp kapatabiliyorsun, Eloquent'te bu davranış hep orada, bu yüzden dikkatsiz kod N+1'e çok daha kolay düşer.

---

## Bugün CRM'de Ne Ekleyeceğiz

`Contact` ve `Deal` modelleri oluşturulacak (henüz yoklar, sadece migration'ları var). `Company::contacts()`, `Company::deals()` (hasMany) ve `Contact::company()`, `Deal::company()` (belongsTo) ilişkileri kurulacak. `CompanyController::show()` doldurulup şirket detay sayfasında o şirketin kişileri listelenecek — `with('contacts')` kullanarak, N+1'e düşmeden.

---

## Kendini Test Et

1. `$company->contacts()` (parantezli) ile `$company->contacts` (parantezsiz) arasındaki fark ne — hangisi ne zaman SQL çalıştırır?
2. `Company::with('contacts')->get()` olmadan bir döngüde `$company->contacts` kullanmanın maliyeti nedir — 10 şirket, 100 şirket için kaç SQL sorgusu çalışır?
