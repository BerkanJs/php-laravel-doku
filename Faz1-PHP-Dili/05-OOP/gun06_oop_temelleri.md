# Gün 6 — OOP Temelleri: Class, Interface, Trait

## Bu Derste İlk Kez Göreceğin Syntax

| Yeni syntax | Örnek | Açıklama |
|-------------|-------|----------|
| Class tanımı | `class Company { }` | C#'taki `class` ile birebir aynı |
| Erişim belirleyiciler | `public`, `protected`, `private` | C# ile birebir aynı kavram |
| Constructor | `__construct()` | C#'taki class adıyla aynı isimli constructor'ın PHP karşılığı |
| Constructor property promotion | `__construct(private string $ad) {}` | Parametreyi doğrudan property'e dönüştürme (PHP 8+) |
| Interface | `interface Loglanabilir { }` / `implements Loglanabilir` | C# ile birebir aynı kavram |
| Trait | `trait Loglanabilir { }` / `use Loglanabilir;` | **C#'ta karşılığı yok** — class'a "yapıştırılan" davranış |
| Abstract | `abstract class`, `abstract function` | C# ile birebir aynı kavram |

---

## Senaryo

CRM'de hem `Company` hem `Invoice` (fatura) sınıfının "işlem geçmişini logla" davranışına ihtiyacı var — mesela her ikisi de `logla($mesaj)` metoduna sahip olmalı ve bunu aynı şekilde yapmalı. Ama bu iki sınıfın **ortak bir üst sınıfı yok** ve olması da mantıklı değil (bir fatura bir şirket değildir, aralarında "is-a" ilişkisi yok).

C#'ta bu problemi çözmenin yolları sınırlı: ya davranışı her iki class'a **ayrı ayrı kopyalarsın** (kod tekrarı), ya bir `ILoglanabilir` interface'i tanımlayıp her class'ta metodu **yeniden yazarsın** (yine kod tekrarı, sadece sözleşme paylaşılır), ya da C# 8+ ile "default interface method" kullanırsın (nispeten yeni, sınırlı kullanım alanı var).

PHP'nin bu soruna çözümü **trait**: bir davranış bloğunu yazarsın, sonra hangi class'a istersen ona "yapıştırırsın" — class'ların birbirleriyle hiçbir akrabalığı (inheritance ilişkisi) olması gerekmez:

```php
trait Loglanabilir {
    public function logla($mesaj) {
        echo "[LOG] " . static::class . ": $mesaj\n";
    }
}

class Company {
    use Loglanabilir;   // Company artik logla() metoduna sahip
}

class Invoice {
    use Loglanabilir;   // Invoice de ayni metoda sahip - kod TEKRARLANMADI, tek yerde yazildi
}
```

Peki ya iki farklı trait, aynı isimde bir metot tanımlıyorsa ve bir class ikisini birden kullanmak istiyorsa? Bu, bugünün ikinci önemli sorusu — aşağıda **Trait Çakışması** bölümünde çözüyoruz.

---

## Analoji

**Interface — sözleşme:** C#'taki ile birebir aynı: "bu class'ı kullanan biri şu metotları bulacağına güvenebilir" sözü. İçinde davranış (kod) yok, sadece imza var.

**Trait — fotokopi edilebilir davranış kartı:** Bir tarif kartı düşün — üstünde yazılı adımlar var (kod). Bu kartı istediğin kadar çoğaltıp istediğin mutfağa (class'a) verebilirsin; mutfakların birbiriyle akraba olması gerekmez. İki farklı kart aynı adımı ("pişir") farklı şekilde tarif ediyorsa, aşçıya (PHP'ye) hangisini izleyeceğini **açıkça söylemen** gerekir — bu da trait çakışması çözümü (`insteadof`/`as`).

**Abstract class — yarım bırakılmış plan:** "Bu bir bina planı ama bazı odaların ne olacağı belirtilmemiş, sen (alt sınıf) doldurmalısın" — C#'taki abstract class ile birebir aynı fikir.

---

## Teorik

### Class, erişim belirleyiciler, constructor

```php
class Company {
    private string $ad;              // private - sadece class icinden erisilir, C# ile ayni

    public function __construct(string $ad) {   // __construct - C#'taki constructor'in PHP karsiligi
        $this->ad = $ad;              // $this -> C#'taki "this" ile ayni
    }

    public function adiGetir(): string {
        return $this->ad;
    }
}

$c = new Company("Acme A.S.");
echo $c->adiGetir();   // "Acme A.S."
```

### Constructor property promotion (PHP 8+)

Yukarıdaki kod tekrarı (parametreyi alıp property'e atamak) çok yaygın olduğu için PHP 8 bir kısayol ekledi:

```php
class Company {
    public function __construct(
        private string $ad,          // "private string $ad" hem parametre hem property tanımlar - ikisi tek satırda
        private string $sehir = "İstanbul"
    ) {}
    // constructor gövdesi BOŞ - property ataması otomatik yapılır

    public function adiGetir(): string {
        return $this->ad;
    }
}
```

### Interface

```php
interface Loglanabilir {
    public function logla(string $mesaj): void;   // sadece imza, gövde yok
}

class Company implements Loglanabilir {
    public function logla(string $mesaj): void {   // sözleşmeyi yerine getirmek ZORUNDA
        echo "[LOG] $mesaj\n";
    }
}
```

### Trait ve Trait Çakışması

```php
trait Loglanabilir {
    public function kaydet() {
        echo "Loglanabilir::kaydet calisti\n";
    }
}

trait Onbelleklenebilir {
    public function kaydet() {
        echo "Onbelleklenebilir::kaydet calisti\n";
    }
}

class Urun {
    use Loglanabilir, Onbelleklenebilir {
        Loglanabilir::kaydet insteadof Onbelleklenebilir;   // cakisma var, Loglanabilir'inkini kullan
        Onbelleklenebilir::kaydet as kaydetOnbellek;         // Onbelleklenebilir'inkini FARKLI bir isimle sakla
    }
}

$u = new Urun();
$u->kaydet();            // "Loglanabilir::kaydet calisti"
$u->kaydetOnbellek();     // "Onbelleklenebilir::kaydet calisti"
```

`insteadof` → "şu trait'in metodunu kullan, diğerini yok say". `as` → "diğer trait'in metodunu farklı bir isimle sakla, o da erişilebilir kalsın".

### Abstract class ve method

```php
abstract class Sekil {
    abstract public function alanHesapla(): float;   // govde yok - alt sinif ZORUNLU olarak yazacak

    public function bilgiVer(): string {              // normal metot - alt siniflar direkt kullanabilir
        return "Bu sekilin alani: " . $this->alanHesapla();
    }
}

class Dikdortgen extends Sekil {
    public function __construct(private float $genislik, private float $yukseklik) {}

    public function alanHesapla(): float {            // abstract metodu doldurmak ZORUNLU
        return $this->genislik * $this->yukseklik;
    }
}

// $s = new Sekil();  // HATA! abstract class direkt instantiate edilemez
$d = new Dikdortgen(5, 3);
echo $d->bilgiVer();   // "Bu sekilin alani: 15"
```

---

## C# ile Karşılaştırma

| Konu | C# | PHP |
|------|----|----|
| Interface | Aynı kavram | Aynı kavram — `implements` |
| Trait | Karşılığı yok — en yakını **extension method + default interface method** karışımı | Var — ama trait **state (property) de taşıyabilir**, extension method taşıyamaz |
| Multiple inheritance | Class'ta yok, interface'te var (multiple interface) | Class'ta yok, ama **multiple trait `use` edilebilir** — trait'ler class inheritance'ın yerini kısmen dolduruyor |
| Constructor | `public Company(string ad) { this.ad = ad; }` | `public function __construct(private string $ad) {}` — property promotion ile daha kısa |
| Abstract class | Aynı kavram | Aynı kavram |

**Kritik çıkarım:** Bir class iki trait kullanıyor ve ikisinde de aynı isimde metot varsa, PHP bunu **otomatik çözmez** — `insteadof` ile hangisinin kullanılacağını, `as` ile diğerinin hangi isimle erişilebilir kalacağını **açıkça** belirtmen gerekir. Aksi halde "trait method collision" hatası alırsın.

---

## Kod ile Göster

Çalıştırmak için (ileride, PHP kurulduğunda):
```
cd Faz1-PHP-Dili/05-OOP
php -S localhost:8000
```

### 1. `class_ve_constructor_demo.php` — class, erişim belirleyiciler, constructor, property promotion

```php
class CompanyEski {
    private string $ad;

    public function __construct(string $ad) {
        $this->ad = $ad;              // klasik yontem - parametreyi elle property'e ata
    }

    public function adiGetir(): string {
        return $this->ad;
    }
}

class Company {
    public function __construct(
        private string $ad,                      // constructor property promotion (PHP 8+)
        private string $sehir = "Istanbul"        // default parametre burada da calisir
    ) {}
    // govde BOS - $ad ve $sehir otomatik property oldu

    public function adiGetir(): string {
        return $this->ad;
    }

    public function bilgiVer(): string {
        return "{$this->ad} - {$this->sehir}";
    }
}

$c1 = new CompanyEski("Acme A.S.");
echo $c1->adiGetir() . "\n";

$c2 = new Company("Beta Ltd.");
echo $c2->bilgiVer() . "\n";          // "Beta Ltd. - Istanbul" - default sehir kullanildi

$c3 = new Company("Gamma A.S.", "Ankara");
echo $c3->bilgiVer() . "\n";          // "Gamma A.S. - Ankara"
```

### 2. `interface_ve_abstract_demo.php` — interface + implements, abstract class + method

```php
interface Loglanabilir {
    public function logla(string $mesaj): void;
}

class Siparis implements Loglanabilir {
    public function logla(string $mesaj): void {
        echo "[SIPARIS LOG] $mesaj\n";
    }
}

$s = new Siparis();
$s->logla("Yeni siparis olusturuldu");

abstract class Sekil {
    abstract public function alanHesapla(): float;

    public function bilgiVer(): string {
        return "Bu sekilin alani: " . $this->alanHesapla();
    }
}

class Dikdortgen extends Sekil {
    public function __construct(private float $genislik, private float $yukseklik) {}

    public function alanHesapla(): float {
        return $this->genislik * $this->yukseklik;
    }
}

$d = new Dikdortgen(5, 3);
echo $d->bilgiVer() . "\n";   // "Bu sekilin alani: 15"

// $sekil = new Sekil();   // HATA vermesi icin yorumda birakildi - abstract class instantiate edilemez
```

### 3. `trait_demo.php` — trait paylaşımı ve çakışma çözümü

```php
trait Loglanabilir {
    public function kaydet() {
        echo "Loglanabilir::kaydet calisti\n";
    }
}

trait Onbelleklenebilir {
    public function kaydet() {
        echo "Onbelleklenebilir::kaydet calisti\n";
    }
}

// --- Cakisma YOKKEN basit kullanim ---
trait SelamVerebilir {
    public function selamVer() {
        echo static::class . " diyor ki: Merhaba!\n";   // static::class -> o an calisan class'in adini verir
    }
}

class Company {
    use SelamVerebilir;
}

class Invoice {
    use SelamVerebilir;   // AYNI trait, IKI FARKLI, akraba OLMAYAN class'a eklendi - kod tekrari yok
}

(new Company())->selamVer();   // "Company diyor ki: Merhaba!"
(new Invoice())->selamVer();   // "Invoice diyor ki: Merhaba!"

echo "\n";

// --- Cakisma VARKEN cozum ---
class Urun {
    use Loglanabilir, Onbelleklenebilir {
        Loglanabilir::kaydet insteadof Onbelleklenebilir;   // cakisirsa Loglanabilir'inkini kullan
        Onbelleklenebilir::kaydet as kaydetOnbellek;         // digerini farkli isimle eriseblir yap
    }
}

$u = new Urun();
$u->kaydet();             // "Loglanabilir::kaydet calisti"
$u->kaydetOnbellek();      // "Onbelleklenebilir::kaydet calisti" - iki metoda da erisebiliyoruz
```

---

## Kendini Test Et

1. Bir class iki trait kullanıyor, ikisinde de `save()` metodu var — PHP nasıl çözer?
2. Trait ile interface'i birlikte kullanmanın tipik senaryosu nedir?
