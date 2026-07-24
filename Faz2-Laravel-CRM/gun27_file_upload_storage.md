# Gün 27 — File Upload & Storage

## Bu Derste İlk Kez Göreceğin Syntax

| Yeni syntax | Örnek | Açıklama |
|-------------|-------|----------|
| `$request->file()` | `$request->file('logo')` | Yüklenen dosyaya erişim |
| `->store()` | `$request->file('logo')->store('logos', 'public')` | Dosyayı bir disk'e kaydeder, yolu döndürür |
| `Storage::disk()` | `Storage::disk('public')->put(...)` | Filesystem soyutlaması |
| `storage:link` | `php artisan storage:link` | `storage/app/public`'i `public/storage`'a sembolik link'ler |

---

## Senaryo: Yüklenen bir logo dosyası nereye gidiyor, tarayıcı ona nasıl ulaşıyor

Gün 14.5'te önemli bir kural öğrenmiştik: **web sunucusu SADECE `public/` klasörünü dışarıya açar** — `storage/`, `app/`, `config/` gibi klasörlere URL ile hiç erişilemez. Şimdi bir şirket logosu yüklendiğinde bu dosya nereye kaydediliyor?

```php
$path = $request->file('logo')->store('logos', 'public');
// $path = "logos/AbC123XyZ.png" gibi bir deger doner
```

Bu satır çalışınca, dosya **`storage/app/public/logos/AbC123XyZ.png`** konumuna kaydedilir — yani `public/` klasörünün **dışına**. Gün 14.5'in kuralına göre bu dosyaya tarayıcıdan **doğrudan erişilemez** — `storage/` dışarıya kapalı. Peki nasıl erişilecek?

**`php artisan storage:link`** tam olarak bu sorunu çözer: `public/storage` adında bir **sembolik link (symlink)** oluşturur, bu link `storage/app/public`'i **işaret eder**. Yani dosyanın kendisi hâlâ `storage/app/public/logos/...`'de duruyor, ama `public/storage/logos/...` üzerinden de (symlink sayesinde) erişilebilir hale geliyor — çünkü `public/storage`, web sunucusunun gördüğü `public/` klasörünün **içinde**.

**Zincirin tamamı:** Dosya yüklenir → `storage/app/public/logos/`'a kaydedilir (Gün 14.5'in kuralı gereği bu ANDA erişilemez) → `storage:link` ile oluşturulan symlink, bunu `public/storage/logos/`'a da "görünür" kılar → tarayıcı `http://site.com/storage/logos/AbC123XyZ.png` adresinden dosyaya ulaşır.

---

## Analoji

`storage/app/public/` bir deponun arka odası gibi düşün — müşteri (tarayıcı) oraya doğrudan giremez. `storage:link` ile oluşturulan symlink, o arka odaya açılan, mağazanın (public/) içinden geçilebilen bir **kapı** koymak gibidir. Dosyanın kendisi yer değiştirmez, sadece ona ulaşan yeni bir yol açılır.

---

## Teorik

### `$request->file()` ile dosyaya erişim

```php
if ($request->hasFile('logo')) {
    $dosya = $request->file('logo');   // UploadedFile nesnesi
    $orijinalAd = $dosya->getClientOriginalName();
    $boyut = $dosya->getSize();
}
```

### `->store()` — dosyayı kaydetme

```php
$path = $request->file('logo')->store('logos', 'public');
// 1. parametre: hangi ALT KLASORE (logos/)
// 2. parametre: hangi DISK'e (public - config/filesystems.php'de tanimli)
// $path = "logos/rastgele-benzersiz-isim.png" - DOSYA ADI OTOMATIK URETILIR (cakisma olmasin diye)
```

Dikkat: `$path` (örn. `"logos/xyz.png"`) veritabanına kaydedilir — dosyanın **kendisi değil**, sadece **yolu**. `Company` modelinde bir `logo_path` kolonu olur, içinde bu string tutulur.

### `Storage::disk()` — filesystem soyutlaması

```php
Storage::disk('public')->put('logos/ozel-isim.png', $icerik);
Storage::disk('public')->exists('logos/xyz.png');
Storage::disk('public')->delete('logos/xyz.png');
```

`config/filesystems.php`'de (Gün 14.5'te gördüğümüz config dosyalarından biri) `public`, `local`, `s3` gibi disk'ler tanımlıdır. Kod, hangi disk kullanıldığını **bilmeden** çalışır — bugün `public` (yerel disk) kullanıyoruz, ileride `s3`'e geçilirse (bulut depolama) **kodun tek satırı bile değişmez**, sadece config değişir.

### Validation ile dosya kısıtlama

```php
$request->validate([
    'logo' => 'image|max:2048',   // resim olmali, en fazla 2048 KB (2MB)
]);
```

Bu, Gün 22'de gördüğümüz validation mekanizmasının aynısı — `image` kuralı dosyanın gerçekten bir resim (jpg/png/gif/vb.) olduğunu MIME type üzerinden kontrol eder, sadece uzantıya bakmaz.

### Görüntülemek için

```blade
<img src="{{ Storage::url($company->logo_path) }}">
```

`Storage::url()`, kayıtlı `logo_path`'i (`"logos/xyz.png"`) tam URL'e (`"/storage/logos/xyz.png"`) çevirir — symlink'in nereye kurulduğunu elle bilmen gerekmez.

---

## C# ile Karşılaştırma

| Konu | ASP.NET Core | Laravel |
|------|---------------|---------|
| Yüklenen dosya | `IFormFile` | `UploadedFile` (`$request->file()`) |
| Depolama soyutlaması | Elle yazılan bir `IFileStorageService` | `Storage` facade — framework içinde hazır |
| Yerel/bulut geçişi | Elle interface + iki implementasyon yazman gerekir | Sadece `config/filesystems.php`'deki disk değişir, kod aynı kalır |
| Public erişim | `wwwroot/` içine direkt yazarsın | `storage/app/public` + symlink — bilinçli olarak dolaylı |

**Kritik çıkarım:** Laravel'in dosyayı bilerek `public/`'in dışına (`storage/app/public/`'e) kaydedip sonra symlink ile "görünür" kılması, rastgele bir tasarım değil — Gün 14.5'teki güvenlik sınırının (sadece `public/` dışarıya açık) doğal bir sonucu. Yüklenen dosyaların relatif konumu, hangi disk kullanıldığından bağımsız (`Storage::url()` sayesinde) — bu da S3'e geçişi kod değişikliği olmadan mümkün kılıyor.

---

## Bugün CRM'de Ne Ekleyeceğiz

`companies` tablosuna `logo_path` kolonu (migration), `CompanyController::store()`/`update()`'e logo yükleme, `companies/show.blade.php`'de logo gösterimi.

---

## Kendini Test Et

1. `storage:link` çalıştırılmazsa, yüklenen bir dosyaya tarayıcıdan erişmeye çalışırsan ne olur?
2. Veritabanında `logo_path` kolonunda tam olarak ne saklanıyor — dosyanın kendisi mi, bir yol mu?
3. `Storage::disk('public')` yerine `Storage::disk('s3')` kullanmaya geçilseydi, `CompanyController`'daki kod satırlarından kaçı değişirdi?
