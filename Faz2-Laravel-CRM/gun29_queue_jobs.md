# Gün 29 — Queue & Jobs (Temel Düzeyde)

## Bu Derste İlk Kez Göreceğin Syntax

| Yeni syntax | Örnek | Açıklama |
|-------------|-------|----------|
| Job oluşturma | `php artisan make:job SendDealCreatedEmail` | Kuyruğa atılabilir bir iş class'ı |
| `ShouldQueue` | `class SendDealCreatedEmail implements ShouldQueue` | Bu job'ın kuyruklanabilir olduğunu işaretler |
| `dispatch()` | `dispatch(new SendDealCreatedEmail($deal))` | Job'ı kuyruğa ekler |
| `queue:work` | `php artisan queue:work` | Kuyruktaki işleri işleyen ayrı bir process |

---

## Senaryo: Gün 18'deki gizemli `jobs` tablosunun cevabı

Gün 18'de migration'ları yazarken üç tablo oluşmuştu: `users`, `cache`, **`jobs`**. O gün `jobs` tablosunun `.env`'deki `QUEUE_CONNECTION=database` yüzünden var olduğunu söylemiştik ama **neden** bir queue'nun tabloya ihtiyaç duyduğunu açıklamamıştık. Bugün o soruyu cevaplıyoruz.

Bir fırsat (deal) oluşturulduğunda, satış temsilcisine bir bildirim e-postası göndermek istiyorsun. Bunu `store()` içinde doğrudan yaparsan:

```php
public function store(Request $request)
{
    $deal = Deal::create($request->validated());
    Mail::to($temsilci)->send(new DealCreatedMail($deal));   // e-posta gonderimi YAVAS bir islem (network, SMTP)
    return redirect()->route('deals.index');
}
```

Kullanıcı "Kaydet" butonuna bastığında, **e-posta gerçekten gönderilene kadar** (SMTP sunucusuyla konuşma, birkaç yüz milisaniye - birkaç saniye sürebilir) sayfa **beklemek zorunda kalır**. Kullanıcı deneyimi kötüleşir, üstelik SMTP sunucusu o an yavaşsa/erişilemezse, tüm istek çöker.

### `dispatch()` çağrıldığında gerçekte ne oluyor (adım adım)

```php
dispatch(new SendDealCreatedEmail($deal));
```

**Bu satır e-postayı GÖNDERMEZ.** Yaptığı şey: `SendDealCreatedEmail` nesnesini (içindeki `$deal` verisiyle birlikte) **serialize edip**, Gün 18'deki `jobs` tablosuna **bir satır olarak INSERT eder**. Bu INSERT işlemi hızlıdır (birkaç milisaniye), `store()` metodu hemen devam eder, kullanıcı **beklemeden** sayfaya yönlendirilir.

Peki e-posta ne zaman gerçekten "gönderiliyor" (bizim CRM'de: log'a yazılıyor)? Bunun için **tamamen ayrı, sürekli çalışan bir process** gerekir:

```
php artisan queue:work
```

Bu komut, `php artisan serve`'den **bağımsız**, kendi başına çalışan bir process başlatır. Bu process sürekli `jobs` tablosunu kontrol eder, işlenmemiş bir satır bulunca onu okur, `SendDealCreatedEmail` nesnesini geri kurar (deserialize eder) ve `handle()` metodunu çağırır — **işte e-posta (ya da bizim sahte log satırımız) tam bu anda üretilir**, dispatch() anında değil.

**Bu neden Gün 1'in konusuyla doğrudan bağlantılı:** PHP-FPM'de bir HTTP isteği bitince o worker'ın belleği sıfırlanıyordu (Gün 1) — yani bir istek içinde "arka planda çalışacak bir thread başlat" gibi bir şey **yapamazsın**, istek tamamen bitmeden hiçbir şey "arkada" devam edemez. Bu yüzden PHP'de "arka plan işi", C#'taki `BackgroundService` gibi aynı process içinde yaşayan bir şey değil — **tamamen ayrı bir process** (`queue:work`) ve bu iki process arasında veri taşıyan **paylaşılan bir depo** (yine Gün 1'in "process dışı depo" ilkesi — burada `jobs` tablosu) gerektirir.

---

## Analoji

`dispatch()`, bir restoranda garsonun siparişi mutfağa **yazıp bir fişe asması** gibidir — garson (senin `store()` metodun) hemen bir sonraki masaya geçer, siparişin pişmesini beklemez. Aşçı (`queue:work` process'i), kendi hızında fişleri sırayla alıp pişirir. Fiş panosu (`jobs` tablosu), garson ile aşçının **aynı anda aynı yerde olmadan** iletişim kurmasını sağlayan paylaşılan nokta.

---

## Teorik

### Job oluşturma

```
php artisan make:job SendDealCreatedEmail
```

```php
class SendDealCreatedEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(public Deal $deal) {}   // constructor property promotion (Gun 6) - deal'i job'a tasir

    public function handle(): void
    {
        Log::info("E-posta gonderildi (sahte): Deal #{$this->deal->id} icin {$this->deal->title}");
    }
}
```

`ShouldQueue` interface'i, bu job'ın **kuyruklanabilir** olduğunu işaretler — bu interface olmadan `dispatch()` çağrılsa bile job **hemen, senkron** çalışır (kuyruğa hiç girmez).

### Queue driver: `sync` vs `database`/`redis`

- **`sync`** (bazı vanilla Laravel kurulumlarında dev varsayılanı) — `dispatch()` çağrıldığı AN `handle()` senkron çalışır, kuyruk yok, `queue:work`'e gerek yok. Bizim CRM'de bu **değil**.
- **`database`** (bizim `.env`'imizdeki gerçek varsayılan, Gün 14.5) — `dispatch()` bir satır INSERT eder, gerçek işleme `queue:work` çalışırken olur.
- **`redis`** — production'da yaygın, aynı fikir ama tablo yerine Redis kullanır, daha hızlı.

### `dispatch()`

```php
dispatch(new SendDealCreatedEmail($deal));
```

---

## C# ile Karşılaştırma

| Konu | C#/.NET | Laravel |
|------|---------|---------|
| Arka plan işi tanımı | `IHostedService`/`BackgroundService` | Job class (`ShouldQueue`) |
| İşe ekleme | Kuyruk kütüphanesi (Hangfire vb.) ile `BackgroundJob.Enqueue(...)` | `dispatch()` |
| İşleyici process | Genelde AYNI process içinde (`BackgroundService` uygulamayla birlikte yaşar) | **AYRI** process (`queue:work`) — Gün 1'in process modeli farkı burada da geçerli |
| Retry desteği | Hangfire'da var | Laravel'de var (varsayılan deneme sayısı ayarlanabilir) |

**Kritik çıkarım:** En yakın C# karşılığı `BackgroundService` değil, **Hangfire** gibi bir kuyruk kütüphanesi — çünkü .NET'te `BackgroundService` aynı process içinde yaşarken, Laravel'de `queue:work` **PHP-FPM'den tamamen bağımsız, ayrı bir process** olmak zorunda (Gün 1'in "PHP process istekler arası hiçbir şey taşımaz" kuralının doğal sonucu).

> Bu konu mimari/mikroservis tartışması değil — CRM'de tek bir örnekle gösteriliyor, derinlemesine işlenmeyecek (queue prioritization, failed job handling gibi konular kapsam dışı).

---

## Bugün CRM'de Ne Ekleyeceğiz

`SendDealCreatedEmail` job'ı (log'a yazan sahte "e-posta"), `DealController` henüz yok — bugün minimal bir `store()` ile birlikte oluşturulacak, `dispatch()` ile kuyruğa atılacak, `queue:work` ile gerçekten işlendiği doğrulanacak.

---

## Kendini Test Et

1. `dispatch(new SendDealCreatedEmail($deal))` çağrıldığı an, e-posta (log satırı) hemen mi üretilir, yoksa ne zaman?
2. `queue:work` çalışmıyorsa, kuyruğa atılan işler ne olur — kaybolur mu, yoksa bekler mi?
3. Queue driver `sync` olsaydı, bugünkü `jobs` tablosuna hiç ihtiyaç olur muydu?
