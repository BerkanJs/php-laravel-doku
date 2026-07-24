# Gün 18 — Migrations & Schema Builder

## Bu Derste İlk Kez Göreceğin Syntax

| Yeni syntax | Örnek | Açıklama |
|-------------|-------|----------|
| Migration oluşturma | `php artisan make:migration create_companies_table` | Zaman damgalı, versiyonlanmış bir migration dosyası üretir |
| Schema Builder | `Schema::create('companies', function (Blueprint $table) {...})` | Fluent API ile tablo tanımlama |
| Kolon tanımları | `$table->id(); $table->string('name');` | Tablo kolonlarını tip tipe tanımlama |
| Foreign key | `$table->foreignId('company_id')->constrained();` | Başka bir tabloya referans + FK constraint |
| Migration çalıştırma | `php artisan migrate`, `migrate:rollback`, `migrate:fresh` | Şemayı uygulama/geri alma/sıfırlama |

---

## Senaryo

Bugün CRM'e üç tablo ekleyeceğiz: `companies`, `contacts` (her contact bir company'ye ait — `company_id` foreign key ile), `deals`. `contacts` tablosunun migration'ını `companies`'den **önce** oluşturup çalıştırırsan:

```
php artisan make:migration create_contacts_table
php artisan make:migration create_companies_table
php artisan migrate
```

`migrate` komutu, migration dosyalarını **isim sırasına göre** (dosya adının başındaki zaman damgasına göre) çalıştırır. `contacts` dosyası önce oluşturulduğu için önce çalışır — ama `contacts` migration'ı `company_id` için `companies` tablosuna referans veren bir foreign key constraint içeriyor. **`companies` tablosu henüz yok.** Sonuç: `SQLSTATE... foreign key constraint fails` hatası, migration yarım kalır.

Bu, EF Core Migrations'a zaten aşina olduğun için kavramsal olarak yabancı değil — ama Laravel'de migration'ların **çalışma sırasının dosya adındaki zaman damgasıyla belirlendiğini** bilmek önemli: referans veren tablo (contacts), referans verilen tablodan (companies) **sonra** oluşturulmalı/çalıştırılmalı.

---

## Analoji

Bir inşaatta çatıyı duvarlardan önce yapamazsın — çatının oturacağı bir duvar olması gerekir. Migration dosyaları da böyle: her biri zaman damgasıyla numaralanmış bir inşaat adımı, ve bir adım kendinden **önceki bir adımın** (burada: `companies` tablosunun) var olduğunu varsayıyorsa, o adımın gerçekten önce çalışmış olması gerekir.

---

## Teorik

### Migration oluşturma

```
php artisan make:migration create_companies_table
```

`database/migrations/` altında `2026_07_24_120000_create_companies_table.php` gibi zaman damgalı bir dosya üretir — dosya adındaki zaman damgası, migration'ların çalışma **sırasını** belirler.

### Migration'ın yapısı: `up()` / `down()`

```php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
```

`up()` — şemayı ileri alır (tabloyu oluşturur/değiştirir). `down()` — aynı değişikliği **geri alır** (rollback desteği). Bu ikili, EF Core Migrations'daki `Up()`/`Down()` metotlarıyla birebir aynı fikir.

### Schema Builder — sık kullanılan kolon tipleri

```php
$table->id();                              // auto-increment primary key
$table->string('name');                    // VARCHAR
$table->string('email')->unique();         // unique constraint
$table->text('description')->nullable();   // TEXT, null olabilir
$table->decimal('amount', 10, 2);          // ondalıklı sayı (10 basamak, 2 ondalık)
$table->timestamps();                      // created_at + updated_at otomatik
```

### Foreign key tanımı

```php
Schema::create('contacts', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->foreignId('company_id')->constrained();   // companies.id'ye FK, kolon adından tablo adını tahmin eder
    $table->timestamps();
});
```

`foreignId('company_id')->constrained()` iki şeyi birden yapar: (1) `company_id` diye bir `BIGINT UNSIGNED` kolonu oluşturur, (2) bunu `companies` tablosunun `id` kolonuna **foreign key constraint** olarak bağlar (kolon adından `companies` tablosunu otomatik çıkarır — `company_id` → `companies`).

### Migration'ları çalıştırma

```
php artisan migrate            // henüz çalışmamış migration'ları sırayla çalıştırır (up())
php artisan migrate:rollback   // SON çalıştırılan migration grubunu geri alır (down())
php artisan migrate:fresh      // TÜM tabloları siler, en baştan migrate eder (SADECE dev ortamında!)
```

---

## C# ile Karşılaştırma

| Konu | EF Core Migrations | Laravel Migrations |
|------|---------------------|----------------------|
| Temel fikir | Code-first şema versiyonlama | Aynı fikir — birebir |
| Yazım şekli | C#, `ModelBuilder`/Fluent API ya da attribute tabanlı | Fluent PHP API (`Schema::create`, `Blueprint`) |
| Sıralama | Migration geçmişi tablo üzerinden, ID/tarih | Dosya adındaki zaman damgası |
| İleri/geri alma | `Up()`/`Down()` | `up()`/`down()` |
| Şemayı sıfırlama (dev) | Veritabanını drop edip yeniden migrate | `migrate:fresh` |

**Kritik çıkarım:** İki sistem de "kod olarak şema" fikrini paylaşıyor — asıl fark, Laravel'de migration dosyalarının **düz PHP sınıfları** olması ve sıranın **dosya adındaki zaman damgasından** gelmesi. Bu yüzden bugünkü gibi foreign key'li tablolar eklerken, **referans veren tablo migration'ının, referans verilenden sonraki bir zaman damgasına sahip olması** gerekiyor.

---

## Bugün CRM'de Ne Ekleyeceğiz

`companies`, `contacts` (`company_id` foreign key ile), `deals` tabloları için migration'lar — sırasıyla `companies` önce, sonra ona referans veren `contacts` ve `deals`.

---

## Kendini Test Et

1. `contacts` migration'ı `companies`'den önce çalışırsa ne olur?
2. `migrate:rollback` ile `migrate:fresh` arasındaki fark nedir, hangisi ne zaman kullanılır?
