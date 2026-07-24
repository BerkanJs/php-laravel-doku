# Gün 1 — PHP Nedir? Çalışma Modeli

> Bu derste ilk kez PHP kodu göreceksin. Bu yüzden önce en temel yazım kurallarını (aşağıda "PHP Yazım Kuralları" bölümü), sonra bugünün asıl konusunu — PHP'nin bir HTTP isteğini nasıl çalıştırdığını — işleyeceğiz. Kod örnekleri bilerek `class`, `static` gibi ileri konuları içermiyor; o konular Gün 6 ve Gün 8'de geliyor.

---

## PHP Yazım Kuralları — Hızlı Bakış

Aşağıdaki derste geçecek kodu okuyabilmen için bilmen gereken 5 kural:

| Kural | PHP | Açıklama |
|-------|-----|----------|
| Dosya etiketi | `<?php ... ?>` (kapanış etiketi genelde yazılmaz) | Bir dosyanın neresinin PHP kodu olduğunu belirtir. C#'ta böyle bir etikete gerek yok, tüm `.cs` dosyası zaten kod. |
| Değişken | `$isim = "Ali";` | Her değişken `$` ile başlar. C#'ta `var isim = "Ali";` — `$` işareti dışında mantık aynı: değer ata, tip otomatik belirlenir. |
| Satır sonu | `;` | Her komut (statement) noktalı virgülle biter — C# ile birebir aynı. |
| Ekrana yazdırma | `echo "Merhaba";` | Response'a/konsola metin yazdırır — C#'taki `Console.WriteLine` / `Response.Write` karışımı. |
| Yorum satırı | `// tek satır` veya `/* çok satırlı */` | C# ile birebir aynı syntax. |

Bu kadarı, aşağıdaki tüm kod örneklerini okuman için yeterli. Şimdi asıl konuya geçiyoruz.

---

## Senaryo

C#/.NET'te bir "şu an sitede kaç kişi var" sayacı yazman istendiğini düşün. Alışkın olduğun yaklaşım:

```csharp
public static class ZiyaretciSayaci
{
    public static int Sayi = 0;
}

// Her istekte:
ZiyaretciSayaci.Sayi++;
```

Bu kod Kestrel process'i açık kaldığı sürece `Sayi` değerini büyütmeye devam eder — çünkü .NET uygulaması tek bir process içinde, sürekli açık kalarak çalışır; `static` bir alan o process'in belleğinde kalıcıdır.

Şimdi PHP'de en basit haliyle aynı fikri dene — sadece bir değişkeni 1 arttırıp ekrana yazdıran bir script:

```php
<?php
$sayac = 1;
echo $sayac;
```

Bu sayfayı tarayıcıda aç, F5 ile yenile. Her seferinde **1** görürsün. 2, 3, 4 değil. Yeni başlayan biri burada "kodda bir hata mı var" diye kod okur, bug arar — ama kodda hiçbir hata yok, script her istekte zaten $sayac'ı 1 olarak tanımlıyor ve öyle yazdırıyor.

Soru şu: Bu davranış PHP'de **her zaman** böyle mi? Yani PHP'nin belleğinde HİÇBİR şey iki istek arasında kalıcı olamaz mı? Cevap: doğru, ve bunun nedeni bugünün konusu — **PHP'nin bir HTTP isteğini nasıl çalıştırdığı.**

---

## Analoji

**.NET / Kestrel:** Sabah açılan, akşama kadar kapanmayan bir ofis düşün. Aynı masa, aynı çalışan, aynı not defteri gün boyu orada durur. Bir müşteri gelip deftere bir şey yazsa, bir sonraki müşteri geldiğinde o yazı hâlâ deftede durur — çünkü ofis (process) hiç kapanmadı.

**PHP-FPM:** Fast-food tezgahı gibi düşün. Her müşteri geldiğinde ona **temiz bir tepsi** verilir. Müşteri işini bitirip gidince tepsi çöpe atılır (worker/process sıfırlanır). Bir sonraki müşteri geldiğinde önceki müşterinin tepsisinde ne yazdığını **göremez** — tertemiz bir tepsi alır. Tezgahın "bugün kaç müşteri geldi" gibi ortak bir bilgiyi tutması gerekiyorsa, bu bilgi tepsinin üzerinde değil, **tezgahın dışında ayrı bir yerde** (bir defter — yani Redis/DB/dosya) tutulmalı.

Bu yüzden PHP'de "in-process cache" (process'in kendi belleğinde tuttuğu geçici veri) güvenilmezdir; .NET'te güvenilirdir.

---

## Teorik

### PHP nasıl çalıştırılır — Zend Engine

PHP **derlenen** değil **yorumlanan (interpreted)** bir dildir. Bir `.php` dosyası çalıştırıldığında:

1. **Zend Engine** kaynak kodu okur (parse eder)
2. Bunu **opcode**'lara (düşük seviye komutlara) çevirir
3. Bu opcode'ları sırayla çalıştırır

C#'ta bu iş `dotnet build` sırasında (derleme zamanında) biter ve elinde IL (Intermediate Language) kalır. PHP'de ise bu adım **her istekte** olur — OPcache devreye girmezse (aşağıda).

### PHP-FPM (FastCGI Process Manager)

Production'da PHP script'lerini kim çalıştırır? **PHP-FPM**. Nginx/Apache gelen HTTP isteğini doğrudan çalıştırmak yerine PHP-FPM'e devreder. PHP-FPM bir **worker process havuzu** yönetir:

- Her gelen istek, havuzdaki boşta bir worker'a atanır
- Worker script'i baştan sona çalıştırır, response'u üretir
- İstek bitince worker "temizlenir" ve havuza geri döner — bir sonraki isteğe hazırdır, ama önceki isteğin belleği/state'i **yoktur**

### OPcache

Zend Engine her istekte dosyayı yeniden parse edip opcode üretseydi bu yavaş olurdu. **OPcache** üretilen opcode'ları paylaşılan bellekte (shared memory) cache'ler. Dosya değişmediği sürece bir sonraki istekte parse adımı atlanır.

> Önemli: OPcache **kodun kendisini** cache'ler, isteğin ürettiği **veriyi/state'i** değil. OPcache açık olsa bile yukarıdaki `$sayac` örneği yine sıfırlanır — OPcache bu davranışı değiştirmez, sadece parse performansını iyileştirir.

### Request Lifecycle

```
Tarayıcı → Nginx/Apache → PHP-FPM (boş worker seç) → script çalışır (opcode'lar, OPcache'ten okunmuş olabilir)
         → response üretilir → tarayıcıya dönülür → worker'ın state'i sıfırlanır (bir sonraki isteğe hazır)
```

Her adımda kritik nokta: **response döndükten sonra o isteğe ait her şey kaybolur.** Bir sonraki istek bir öncekinin mirasını devralmaz.

### Dev vs Prod sunucu

- Geliştirmede PHP'nin kendi built-in sunucusu kullanılır: `php -S localhost:8000` — basit, tek thread'li, production için uygun değil
- Laravel projelerinde bunu saran bir kısayol var: `php artisan serve` (Faz2'de kullanacağız — arka planda yine aynı built-in sunucuyu çalıştırıyor)
- Production'da gerçek kurulum: Nginx/Apache (statik dosya + reverse proxy) + PHP-FPM (worker havuzu, gerçek PHP çalıştırma)

---

## C# ile Karşılaştırma

| Konu | .NET / Kestrel | PHP-FPM |
|------|-----------------|---------|
| Process modeli | Tek process, uzun yaşayan `AppDomain` | İstek başına worker, kısa ömürlü |
| Bellekteki state (değişken, static alan) | İstekler arası **kalıcı** | İstekler arası **kaybolur** |
| Derleme | `dotnet build` ile önceden IL üretilir | Her istekte parse edilir (OPcache bunu hafifletir, ortadan kaldırmaz) |
| Paylaşılan cache | In-memory cache (`IMemoryCache`) güvenilir | In-memory cache güvenilmez — Redis/DB şart |

**Kritik çıkarım:** PHP'de bellekte tuttuğun hiçbir şey — ister sıradan bir değişken, ister (Gün 6/8'de göreceğimiz) bir class'ın static property'si olsun — iki farklı HTTP isteği arasında yaşamaz. Çünkü her istek ayrı bir worker/process'te, sıfırdan başlayan bir çalıştırmadır. Paylaşılan/kalıcı veri için tek doğru yol: process'in dışında yaşayan bir depo — **session, dosya, Redis, veritabanı.**

---

## Kod ile Göster

Bu klasörde bir demo dosyası var: `sayac_demo.php`. **PHP kurulu olmadığı için şu an bilgisayarında çalıştırmadık — kurulum yapıldığında aşağıdaki adımlarla test edebilirsin.**

Çalıştırmak için (ileride, PHP kurulduğunda):
```
cd Faz1-PHP-Dili/01-CalismaModeli
php -S localhost:8000
```
sonra tarayıcıda `http://localhost:8000/sayac_demo.php` adresini aç.

### `sayac_demo.php` — bellekteki hiçbir şeyin isteği aşamadığını gösterir

```php
$sayac = 1;         // $  -> PHP'de her değişken $ ile başlar (C#'ta böyle bir işaret yok)
                     // =  -> atama operatörü, C#'taki ile aynı
                     // ;  -> her satır noktalı virgülle biter

echo "Bu istekteki sayaç değeri: $sayac\n";
// echo -> ekrana/response'a yazdırır
// "..." içinde $sayac otomatik olarak değeriyle değiştirilir ("string interpolation", Gün 5'te detaylı)
```

Sayfayı F5 ile kaç kez yenilersen yenile, hep **1** göreceksin — çünkü her istekte script baştan çalışıyor, `$sayac = 1;` satırı yeniden işleniyor. Bu, "Gün 6/8'de göreceğimiz class'ların static property'leri de dahil, PHP belleğinde hiçbir şey isteği aşamaz" kuralının en yalın hâli.

> Not: PHP'nin `$_SESSION` gibi bu kuralın **dışında kalan** (istekler arası kalıcı olan) yapıları da var — bunu Gün 2'de superglobals konusuyla birlikte göreceğiz.

---

## Kendini Test Et

1. Neden PHP'de sıradan bir değişken (veya ileride göreceğimiz bir class'ın static property'si) istekler arası veri tutamaz?
2. OPcache olmadan her istekte ne olur? OPcache bu "istekler arası state kaybolur" davranışını değiştirir mi?
