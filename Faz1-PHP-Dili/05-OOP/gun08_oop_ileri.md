# Gün 8 — OOP İleri: Static, Magic Methods

## Bu Derste İlk Kez Göreceğin Syntax

| Yeni syntax | Örnek | Açıklama |
|-------------|-------|----------|
| Static property/method | `public static int $sayi;` / `public static function f() {}` | Class'a ait, instance'a değil — C# ile aynı kavram |
| `self::` | `self::$sayi` | O an **yazıldığı** class'a referans (aşağıda detaylı) |
| `static::` | `static::$sayi` | O an **çağrılan** class'a referans — late static binding |
| `__get` / `__set` | `public function __get($ad) {}` | Tanımsız bir property'e erişimi/atamayı yakalar |
| `__call` / `__callStatic` | `public function __call($ad, $args) {}` | Tanımsız bir metot çağrısını yakalar |
| `__toString` | `public function __toString(): string {}` | Nesne `string`'e çevrilmeye çalışıldığında (örn. `echo $obj`) devreye girer |
| `__invoke` | `public function __invoke() {}` | Nesne, bir fonksiyon gibi `$obj()` çağrıldığında devreye girer |

---

## Senaryo

Laravel'de (Faz2'de yazacağımız) şu satırı göreceksin:

```php
$user = User::where('email', 'ali@example.com')->first();
echo $user->name;
$user->save();
```

Buradaki gariplik: `User` class'ının kaynak koduna baksan, `where` diye bir **static method** muhtemelen doğrudan tanımlı değil. `$user` nesnesinin `name` diye bir **property**'si muhtemelen elle tanımlanmamış (veritabanı tablosunun kolonları class'a otomatik yansıyor). `save()` metodu da her model için ayrı ayrı yazılmamış. Yani: **var olmayan** şeylere erişiyormuşsun gibi görünüyor, ama kod çalışıyor!

Bu "sihir" bir illüzyon değil — PHP'nin **magic method**'ları sayesinde çalışan gerçek bir mekanizma. Bugün bu mekanizmayı sıfırdan, kendi basit örneklerimizle inşa edeceğiz — böylece Faz2'de Eloquent'e geldiğimizde "büyü" değil, "tanıdık bir teknik" göreceksin.

```php
class BasitModel
{
    private array $veri = [];

    public function __get($ad)              // tanımsız bir property'e erişilince PHP bunu OTOMATİK çağırır
    {
        return $this->veri[$ad] ?? null;
    }

    public function __set($ad, $deger)       // tanımsız bir property'e atama yapılınca PHP bunu OTOMATİK çağırır
    {
        $this->veri[$ad] = $deger;
    }
}

$m = new BasitModel();
$m->ad = "Ali";     // __set devreye girdi, "ad" diye bir property class'ta TANIMLI DEĞİL
echo $m->ad;        // __get devreye girdi, "Ali" yazdırıldı
```

`BasitModel` class'ında hiçbir yerde `$ad` diye bir property yok — ama `$m->ad = "Ali"` yazınca PHP, class'ta böyle bir property bulamayınca **otomatik olarak** `__set` metodunu çağırıyor. Aynı şey okurken `__get` ile oluyor. Eloquent'in "her veritabanı kolonu otomatik property gibi görünüyor" sihri, birebir bu mekanizma.

---

## Analoji

**Normal property — kendi odası olan biri:** Class'ta tanımlı bir property, evde kendi adı yazılı sabit bir odaya sahip biri gibidir. Birisi o odayı sorunca direkt gidip bulunur.

**`__get`/`__set` — resepsiyon görevlisi:** Sorulan oda gerçekten yoksa, devreye bir resepsiyon görevlisi (`__get`) girer: "O isimde bir oda yok ama ben senin için arka odadaki bir kutuya bakayım" der ve kutudan (`$this->veri` array'i) cevabı getirir. Aynı şekilde birisi olmayan bir odaya bir şey bırakmaya çalışınca (`$m->ad = "Ali"`), resepsiyon görevlisi (`__set`) devreye girip onu arka odaya kaydeder.

**`__call` — aynı resepsiyon görevlisi, ama metotlar için:** Birisi class'ta var olmayan bir metodu çağırmaya çalışınca (`$model->kaydet()`), yine resepsiyon görevlisi devreye girer ve "böyle bir metot yok ama ben senin adına bir şey yapabilirim" der.

**`self::` vs `static::` — aile fotoğrafındaki isim etiketi:** `self::` her zaman fotoğrafın **çekildiği** class'ın ismini yazar (kod nerede yazıldıysa o class). `static::` ise fotoğrafa **kim bakıyorsa** (hangi alt class çağırdıysa) onun ismini yazar — inheritance zincirinde "gerçekte hangi class çalışıyor" sorusuna cevap verir.

---

## Teorik

### Static property/method

```php
class Sayac {
    public static int $toplam = 0;         // static property - class'a ait, TEK kopya (instance'lar arasında paylasilir)

    public static function arttir(): int {  // static method - instance olusturmadan cagrilabilir
        return ++self::$toplam;
    }
}

echo Sayac::arttir();   // 1 - instance (new Sayac()) OLUŞTURMADAN, class üzerinden direkt çağrıldı
echo Sayac::arttir();   // 2 - aynı $toplam paylaşılıyor
```

> Hatırlatma (Gün 1'den): bu `static` property de, tıpkı basit değişkenler gibi, sadece **aynı process/istek içinde** kalıcıdır — istekler arası yine sıfırlanır.

### `self::` vs `static::` — Late Static Binding

```php
class UstSinif {
    public static function olustur(): static {
        return new static();   // static:: -> ÇAĞIRAN class'ı kullanır (late static binding)
    }

    public static function olusturSelf(): self {
        return new self();     // self:: -> YAZILDIĞI class'ı kullanır (her zaman UstSinif)
    }
}

class AltSinif extends UstSinif {}

var_dump(AltSinif::olustur());       // AltSinif nesnesi! - static:: çağıranı (AltSinif) dikkate aldı
var_dump(AltSinif::olusturSelf());   // UstSinif nesnesi! - self:: her zaman yazıldığı class'a sadık kaldı
```

Bu fark inheritance'ta kritik: bir üst class'ta `self::` kullanırsan, alt class'lar o metodu miras alsa bile hep üst class'ı üretir — genelde istenen bu **değildir**. Bu yüzden Laravel gibi framework'ler `static::` kullanır (örn. `User::create()` çağrıldığında gerçekten `User` nesnesi dönsün ister, temel `Model` class'ı değil).

### Magic Methods

```php
class DinamikModel {
    private array $veri = [];

    public function __get($ad) {                     // tanımsız property OKUMA
        return $this->veri[$ad] ?? null;
    }

    public function __set($ad, $deger) {              // tanımsız property YAZMA
        $this->veri[$ad] = $deger;
    }

    public function __call($ad, $args) {              // tanımsız METOT çağrısı (instance üzerinden)
        return "Çağrılan metot: $ad, parametreler: " . implode(', ', $args);
    }

    public static function __callStatic($ad, $args) {  // tanımsız STATIC metot çağrısı
        return "Static çağrılan metot: $ad";
    }

    public function __toString(): string {             // nesne string'e çevrilmeye çalışılınca
        return "DinamikModel(" . implode(',', $this->veri) . ")";
    }

    public function __invoke() {                       // nesne fonksiyon gibi çağrılınca: $nesne()
        return "Nesne bir fonksiyon gibi çağrıldı!";
    }
}
```

---

## C# ile Karşılaştırma

| Konu | C# | PHP |
|------|----|----|
| Static property/method | Aynı kavram, aynı syntax mantığı | Aynı kavram |
| `__get`/`__set` | Property get/set accessor — ama **belirli, önceden tanımlı** bir property için | PHP'de **dinamik** — herhangi bir isimdeki tanımsız property'yi yakalayabilir. C#'ta bu ancak reflection ile mümkün |
| `__call` | Doğrudan karşılığı yok — en yakını `DynamicObject` (nadiren kullanılır) | Laravel bunu her yerde kullanır (`$user->save()`, `Model::where()`) |
| `self`/`static` (bu bağlamda) | `this` instance içindir, static context'te böyle bir ayrım yok | `self::` = yazıldığı class, `static::` = çağıran class (late static binding) — C#'ta karşılığı yok |

**Kritik çıkarım:** Bu magic method'lar Eloquent ORM'nin "sihir gibi çalışması"nın perde arkasıdır. `User::where(...)` çalışırken muhtemelen `__callStatic` (ya da benzer bir mekanizma), `$user->name` çalışırken `__get` devreye girer — Faz2'de Eloquent'i işlerken bu günü hatırlayacağız.

---

## Kod ile Göster

Çalıştırmak için (ileride, PHP kurulduğunda):
```
cd Faz1-PHP-Dili/05-OOP
php -S localhost:8000
```

### 1. `static_ve_late_binding_demo.php` — static property/method, `self::` vs `static::`

```php
class Sayac {
    public static int $toplam = 0;

    public static function arttir(): int {
        return ++self::$toplam;    // self:: - static property'e class icinden erisim
    }
}

echo Sayac::arttir() . "\n";   // 1 - instance olusturmadan cagrildi
echo Sayac::arttir() . "\n";   // 2

class UstSinif {
    public static function olustur(): static {
        return new static();       // static:: -> CAGIRAN class'i kullanir
    }

    public static function olusturSelf(): self {
        return new self();         // self:: -> her zaman UstSinif uretir
    }
}

class AltSinif extends UstSinif {}

echo get_class(AltSinif::olustur()) . "\n";       // "AltSinif" - static:: cagirani dikkate aldi
echo get_class(AltSinif::olusturSelf()) . "\n";    // "UstSinif" - self:: yazildigi class'a sadik kaldi
```

### 2. `magic_methods_demo.php` — `__get`, `__set`, `__call`, `__callStatic`, `__toString`, `__invoke`

```php
class DinamikModel {
    private array $veri = [];

    public function __get($ad) {
        echo "[__get cagrildi: $ad]\n";
        return $this->veri[$ad] ?? null;
    }

    public function __set($ad, $deger) {
        echo "[__set cagrildi: $ad = $deger]\n";
        $this->veri[$ad] = $deger;
    }

    public function __call($ad, $args) {
        return "Cagrilan metot: $ad, parametreler: " . implode(', ', $args);
    }

    public static function __callStatic($ad, $args) {
        return "Static cagrilan metot: $ad";
    }

    public function __toString(): string {
        return "DinamikModel(" . implode(',', $this->veri) . ")";
    }

    public function __invoke() {
        return "Nesne bir fonksiyon gibi cagrildi!";
    }
}

$model = new DinamikModel();

$model->ad = "Ali";           // __set devreye girer - "ad" diye bir property TANIMLI DEGIL
echo $model->ad . "\n";        // __get devreye girer - "Ali" doner

echo $model->herhangiBirMetot("param1", "param2") . "\n";   // __call devreye girer - metot TANIMLI DEGIL
echo DinamikModel::herhangiBirStaticMetot() . "\n";          // __callStatic devreye girer

echo $model . "\n";    // __toString devreye girer (echo bir nesneyi string'e cevirmeye calisir)

echo $model() . "\n";   // __invoke devreye girer - nesne fonksiyon gibi cagrildi
```

---

## Kendini Test Et

1. `self::` ile `static::` arasındaki fark ne, inheritance'ta neden önemli?
2. Laravel'de `User::where('email', $email)->first()` çalışırken hangi magic method devreye girer?
