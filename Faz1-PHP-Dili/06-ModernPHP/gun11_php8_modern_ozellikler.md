# Gün 11 — PHP 8.x Modern Özellikler

## Bu Derste İlk Kez Göreceğin Syntax

| Yeni syntax | Örnek | Açıklama |
|-------------|-------|----------|
| Enum (backed) | `enum Status: string { case Active = 'active'; }` | Sabit, sınırlı bir değer kümesi (PHP 8.1+) |
| `match` | `match($x) { 1 => 'bir', default => 'diğer' }` | `switch`'in değer döndüren, strict-comparison hâli |
| Nullsafe operatör | `$a?->b?->c` | Zincirde herhangi bir halka `null` ise, hata vermeden `null` döner (C# `?.` ile birebir aynı) |
| `readonly` property | `public readonly string $ad;` | Constructor'da bir kez set edilir, sonra asla değiştirilemez (PHP 8.1+) |
| Union type | `function f(int\|string $x) {}` | Parametrenin birden fazla tipten biri olabileceğini belirtir |

> Not: Named arguments'ı zaten Gün 3'te işlemiştik (`f(isim: 'Ali')`) — müfredatta burada da tekrar geçiyor ama yeni bir şey yok, atlıyoruz.

---

## Senaryo

CRM'de bir şirketin sahibinin adresinin şehrini yazdırmak istiyorsun: `$sirket->sahibi->adres->sehir`. Ama `sahibi` ya da `adres` `null` olabilir (her şirketin sahibi kayıtlı olmayabilir). PHP 8'den önce (ve nullsafe operatörü bilmezsen hâlâ) böyle yazman gerekirdi:

```php
if ($sirket !== null && $sirket->sahibi !== null && $sirket->sahibi->adres !== null) {
    $sehir = $sirket->sahibi->adres->sehir;
} else {
    $sehir = null;
}
```

Zincirdeki her halka için ayrı bir `null` kontrolü — okuması da yazması da yorucu. PHP 8'in **nullsafe operatörü** (`?->`) bunu tek satıra indirir:

```php
$sehir = $sirket?->sahibi?->adres?->sehir;   // zincirde herhangi bir yer null ise, hic hata vermeden null doner
```

C#'ta zaten `?.` operatörünü biliyorsun — `$sirket?->sahibi?->adres?->sehir` ile `sirket?.Sahibi?.Adres?.Sehir` **birebir aynı davranış**. Bugün bunun yanında PHP 8.x'in diğer önemli eklemelerini (`enum`, `match`, `readonly`, union types) da görüyoruz.

---

## Analoji

**Nullsafe (`?->`) — akıllı anahtar:** Bir kapı zincirinde (`$a->b->c->d`) her kapıyı tek tek kontrol etmek yerine, elindeki akıllı anahtar zincirdeki herhangi bir kapı kilitliyse (null ise) otomatik olarak durur ve sana "geçemedim, işte boş elin" (`null`) der — hata fırlatıp seni durdurmaz.

**`match` — otomat, `switch` — gişe memuru:** `switch`, para üstü vermeyi unutabilen bir gişe memuru gibidir — bir `case`'den `break` unutulursa bir sonrakine "kayar" (fall-through) ve gevşek (`==`) karşılaştırma yapar. `match` bir otomat gibidir — tam istediğin değeri verir, asla yanlışlıkla bir sonraki seçeneğe kaymaz, sıkı (`===`) karşılaştırma yapar, ve mutlaka bir **değer döndürür**.

**`readonly` — mühürlü zarf:** Bir zarf bir kez mühürlendikten (constructor'da set edildikten) sonra, kimse — class'ın kendisi bile — içeriğini değiştiremez. Değiştirmeye çalışırsan zarf yırtılır (hata fırlatılır).

**Enum — sabit bir menü:** Bir restoran menüsünde sadece listelenen yemekler sipariş edilebilir, "ben kendi uydurduğum bir yemek istiyorum" diyemezsin. Enum, bir değişkenin sadece **önceden tanımlanmış** değerlerden birini alabileceğini garanti eder.

---

## Teorik

### Enum (backed enum)

```php
enum Durum: string {
    case Aktif = 'aktif';
    case Pasif = 'pasif';
    case Beklemede = 'beklemede';

    public function etiket(): string {           // enum method taşıyabilir - C#'ta enum'lar bunu yapamaz
        return match($this) {
            Durum::Aktif => 'Aktif Kullanıcı',
            Durum::Pasif => 'Pasif Kullanıcı',
            Durum::Beklemede => 'Onay Bekliyor',
        };
    }
}

$d = Durum::Aktif;
echo $d->value . "\n";      // "aktif" - backed enum'un tasidigi gercek deger
echo $d->etiket() . "\n";   // "Aktif Kullanıcı" - enum'un kendi metodu
```

### `match` — `switch`'in modern hâli

```php
$kod = 2;

// switch - eski yontem, fall-through riski var, == (loose) karsilastirir
switch ($kod) {
    case 1:
        $sonuc = "bir";
        break;                // break UNUTULURSA bir sonraki case'e "kayar" (fall-through)
    case 2:
        $sonuc = "iki";
        break;
    default:
        $sonuc = "diger";
}

// match - modern yontem, DEGER DONDURUR, === (strict) karsilastirir, fall-through YOK
$sonuc = match($kod) {
    1 => "bir",
    2 => "iki",
    default => "diger",
};
```

### Nullsafe operatör

```php
class Adres { public function __construct(public ?string $sehir = null) {} }
class Sahip { public function __construct(public ?Adres $adres = null) {} }
class Sirket { public function __construct(public ?Sahip $sahibi = null) {} }

$sirket = new Sirket();   // sahibi null

$sehir = $sirket?->sahibi?->adres?->sehir;   // sahibi null oldugu icin zincir burada durur, $sehir = null
```

### Readonly properties

```php
class Company {
    public function __construct(
        public readonly string $ad,     // sadece constructor'da set edilir
    ) {}
}

$c = new Company("Acme A.Ş.");
echo $c->ad . "\n";        // okumak serbest

// $c->ad = "Beta Ltd.";   // HATA! Cannot modify readonly property - constructor disinda degistirilemez
```

### Union types

```php
function idYazdir(int|string $id): void {   // $id ya int ya string OLABILIR
    echo "ID: $id\n";
}

idYazdir(5);        // calisir - int
idYazdir("ABC123"); // calisir - string
```

---

## C# ile Karşılaştırma

| Konu | C# | PHP |
|------|----|----|
| `match` | Switch expression (`x switch { ... }`) — değer döner, strict comparison | `match` — aynı fikir, aynı davranış |
| Nullsafe | `?.` | `?->` — birebir aynı davranış |
| `readonly` | `init` accessor (constructor'da set, sonra değişmez) | `readonly` — aynı amaç |
| Enum | Sadece int tabanlı, method taşıyabilir | Backed enum string/int taşıyabilir, method taşıyabilir |
| Union type | Yok (yakını: generics/overload) | `int\|string` — doğrudan syntax desteği var |

---

## Kod ile Göster

Çalıştırmak için (ileride, PHP kurulduğunda):
```
cd Faz1-PHP-Dili/06-ModernPHP
php -S localhost:8000
```

### 1. `enum_ve_match_demo.php`

```php
enum Durum: string {
    case Aktif = 'aktif';
    case Pasif = 'pasif';
    case Beklemede = 'beklemede';

    public function etiket(): string {
        return match($this) {
            Durum::Aktif => 'Aktif Kullanici',
            Durum::Pasif => 'Pasif Kullanici',
            Durum::Beklemede => 'Onay Bekliyor',
        };
    }
}

$d = Durum::Aktif;
echo $d->value . "\n";
echo $d->etiket() . "\n";

$kod = 2;
$sonuc = match($kod) {
    1 => "bir",
    2 => "iki",
    default => "diger",
};
echo $sonuc . "\n";
```

### 2. `nullsafe_readonly_uniontypes_demo.php`

```php
class Adres { public function __construct(public ?string $sehir = null) {} }
class Sahip { public function __construct(public ?Adres $adres = null) {} }
class Sirket { public function __construct(public ?Sahip $sahibi = null) {} }

$sirket1 = new Sirket();
$sehir1 = $sirket1?->sahibi?->adres?->sehir;
var_dump($sehir1);   // NULL - zincir sahibi'nde durdu, hata FIRLATMADI

$sirket2 = new Sirket(new Sahip(new Adres("Istanbul")));
$sehir2 = $sirket2?->sahibi?->adres?->sehir;
var_dump($sehir2);   // string(8) "Istanbul"

class Company {
    public function __construct(public readonly string $ad) {}
}
$c = new Company("Acme A.S.");
echo $c->ad . "\n";
// $c->ad = "Beta Ltd.";   // yorumda birakildi - calistirilirsa Error firlatir

function idYazdir(int|string $id): void {
    echo "ID: $id\n";
}
idYazdir(5);
idYazdir("ABC123");
```

---

## Kendini Test Et

1. `match` ile `switch` arasındaki davranış farkı (fall-through, strict comparison) nedir?
2. Readonly property'yi constructor dışında değiştirmeye çalışırsan ne olur?
