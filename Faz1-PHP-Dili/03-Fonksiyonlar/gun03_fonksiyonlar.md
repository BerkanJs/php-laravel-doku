# Gün 3 — Fonksiyonlar, Scope ve Closures

## Bu Derste İlk Kez Göreceğin Syntax

| Yeni syntax | Örnek | Açıklama |
|-------------|-------|----------|
| Fonksiyon tanımı | `function topla($a, $b) { return $a + $b; }` | Gün 2'de sadece kullandık, bugün nasıl yazıldığını görüyoruz |
| Default parametre | `function f($x, $carpan = 2) {}` | Parametre verilmezse varsayılan değer kullanılır |
| Variadic (değişken sayıda parametre) | `function f(...$sayilar) {}` | Kaç parametre gönderilirse gönderilsin hepsini bir array olarak toplar |
| Named arguments | `f(carpan: 3, x: 5)` | Parametreleri isimleriyle, sırayı önemsemeden gönderme (PHP 8+) |
| Closure (anonim fonksiyon) | `function($x) use ($y) { return $x + $y; }` | İsimsiz fonksiyon, dış değişkeni `use` ile "içeri taşır" |
| Arrow function | `fn($x) => $x * 2` | Kısa closure syntax'ı (PHP 7.4+) |
| First-class callable | `strlen(...)` | Var olan bir fonksiyonu değer (Closure) olarak alma (PHP 8.1+) |

---

## Senaryo

Bir sepet toplamı hesaplarken, ürün fiyatlarına bir komisyon oranı eklemek istiyorsun. C#'a alışkın olduğun için doğal olarak şöyle yazarsın:

```php
$komisyonOrani = 0.1;

$fiyatiHesapla = function ($fiyat) {
    return $fiyat + ($fiyat * $komisyonOrani);   // $komisyonOrani burada erişilebilir olmalı, değil mi?
};

echo $fiyatiHesapla(100);
```

Bu kodu çalıştırdığında **hata alırsın**: `Undefined variable $komisyonOrani`. C#'ta bir lambda (`x => x + (x * komisyonOrani)`) çevresindeki değişkeni **otomatik olarak** yakalar — hiçbir ek syntax gerekmez. PHP'de closure'lar bunu **yapmaz**. Dış scope'taki bir değişkene closure içinden erişmek istiyorsan, bunu **açıkça** belirtmen gerekir:

```php
$fiyatiHesapla = function ($fiyat) use ($komisyonOrani) {   // use ($komisyonOrani) -> dışarıdaki değişkeni içeri "kopyala"
    return $fiyat + ($fiyat * $komisyonOrani);
};
```

Bu satırlık fark, C#'tan gelen geliştiricilerin PHP'de en çok unuttuğu şeylerden biridir. Bugünün konusu tam olarak bu: PHP'de bir fonksiyonun "dışarıyı" ne zaman, nasıl görebildiği.

---

## Analoji

**C# lambda/closure — camlı oda:** Bir odanın duvarları cam. İçeride oturan kişi (fonksiyon), dışarıda olup biten her şeyi otomatik olarak görür — ekstra bir şey yapmasına gerek yok, cam zaten şeffaf.

**PHP closure — ses geçirmez oda:** Fonksiyon, ses yalıtımlı bir odaya kapatılmış gibidir. Dışarıdaki hiçbir "ses" (değişken) kendiliğinden içeri sızmaz. Eğer dışarıdaki bir bilgiyi içeri taşımak istiyorsan, onu elinle bir kulaklıkla (`use ($degisken)`) içeri taşımalısın. Kulaklığı unutursan (`use` yazmazsan), oda içindeki kişi dışarıda ne olduğunu hiç bilemez.

**Arrow function (`fn`) — yarı camlı oda:** PHP 7.4 ile gelen `fn($x) => ...` syntax'ı, C#'ın camlı odasına daha yakın: dışarıdaki değişkenleri **otomatik** görür, `use` yazmana gerek yoktur — ama sadece **okumak** için, üstelik sadece **tek satırlık ifadeler** için kullanılabilir.

---

## Teorik

### Fonksiyon tanımı, default parametre, variadic

```php
function selamla($isim = "Misafir") {   // default parametre - çağrılırken $isim verilmezse "Misafir" kullanılır
    return "Merhaba, $isim";
}

function topla(...$sayilar) {           // variadic - ... ile kaç parametre gelirse gelsin bir array'de toplanır
    return array_sum($sayilar);          // array_sum, Gün 4'te detaylı işleyeceğimiz bir array fonksiyonu
}
```

### Named arguments (PHP 8+)

Bir fonksiyonu çağırırken parametreleri **isimleriyle**, sırayı önemsemeden gönderebilirsin:

```php
function dikdortgenAlani($genislik, $yukseklik) {
    return $genislik * $yukseklik;
}

dikdortgenAlani(genislik: 5, yukseklik: 3);   // sıra önemli değil, isim eşleşmesi yeterli
dikdortgenAlani(yukseklik: 3, genislik: 5);   // aynı sonuç
```

### Scope: local, global, static

- PHP'de fonksiyon içindeki bir değişken **varsayılan olarak local'dir** — fonksiyon dışındaki aynı isimli değişkenle **hiçbir ilgisi yoktur** (C#'ta da böyledir, bu kısım tanıdık).
- `global $x;` — fonksiyon içinden, fonksiyon dışındaki bir değişkene **açıkça** erişim izni verir (nadiren önerilir, ama PHP'de var).
- `static $x = 0;` — fonksiyon içinde tanımlanan bu değişken, fonksiyonun **birden fazla çağrısı arasında** değerini korur — ama **sadece aynı process/istek içinde**. Bir sonraki HTTP isteğinde yine sıfırdan başlar (Gün 1'in "istekler arası hiçbir şey kalıcı değil" kuralı burada da geçerli — bu, o kuralın istisnası değil, kapsamının netleşmesi: "aynı istek/process içindeki çağrılar arası" ile "istekler arası" farklı şeyler).

### Closures — `use` ile explicit capture

```php
$carpan = 3;
$ucKatinaCikar = function ($x) use ($carpan) {   // use ($carpan) -> dış scope'taki $carpan'ın DEĞERİNİ closure'a kopyalar
    return $x * $carpan;
};
echo $ucKatinaCikar(5);   // 15
```

`use ($carpan)` **değer olarak** (by value) kopyalar — closure tanımlandığı andaki değeri alır, sonradan dışarıda `$carpan` değişse bile closure'ın içindeki kopya değişmez. Eğer dışarıdaki değişkenin **kendisini** (referansını) paylaşmak istiyorsan:

```php
$sayac = 0;
$arttir = function () use (&$sayac) {   // &$sayac -> REFERANS ile capture, closure dışarıdaki gerçek değişkeni değiştirir
    $sayac++;
};
$arttir();
$arttir();
echo $sayac;   // 2 - dışarıdaki $sayac gerçekten değişti
```

### Arrow functions (PHP 7.4+) — implicit capture

```php
$carpan = 3;
$ucKatinaCikar = fn($x) => $x * $carpan;   // use ($carpan) YAZMADIK - fn otomatik olarak dış scope'u görür
echo $ucKatinaCikar(5);   // 15
```

Kısıtlama: arrow function gövdesi **tek bir ifade** olmalı (`{ }` blok yok, `return` yazılmaz — ifadenin sonucu otomatik döner). Birden fazla satır/işlem gerekiyorsa normal closure (`function() use (...) {}`) kullanılır.

### Callable type ve first-class callable syntax

PHP'de bir fonksiyonu "değer" olarak taşımanın birkaç yolu var:

```php
$fonksiyonAdi = 'strlen';
echo $fonksiyonAdi("merhaba");    // 7 - string olarak fonksiyon adını tutup çağırabilirsin

$closureOlarak = strlen(...);    // first-class callable syntax (PHP 8.1+) - strlen'i bir Closure nesnesine çevirir
echo $closureOlarak("merhaba");  // 7 - aynı sonuç, ama artık gerçek bir Closure nesnesi
```

C#'ta bunun net karşılığı yok — en yakını `Func<T,T>`/`Action<T>` ile metot referansı taşımak, ama PHP'de her callable'ın ortak tipi `Closure` ya da `callable`'dır.

---

## C# ile Karşılaştırma

| Konu | C# | PHP |
|------|----|----|
| Lambda/closure dış scope yakalama | Otomatik (derleyici karar verir) | `use ($x)` ile **elle** belirtilir — unutulursa değişkene erişilemez |
| Referans ile yakalama | `ref` ile mümkün ama nadiren kullanılır | `use (&$x)` — closure içinde açıkça yazılır |
| Kısa lambda syntax | `x => x * 2` | `fn($x) => $x * 2` — ama sadece tek ifade, otomatik capture |
| Fonksiyon tipi | `Func<T,T>`, `Action<T>` | Karşılığı yok — her callable `Closure` tipi veya `callable` |
| Named arguments | C# 4.0'dan beri var (`f(x: 5)`) | PHP 8'den beri var, aynı syntax mantığı |

---

## Kod ile Göster

Çalıştırmak için (ileride, PHP kurulduğunda):
```
cd Faz1-PHP-Dili/03-Fonksiyonlar
php -S localhost:8000
```

### 1. `fonksiyon_temelleri_demo.php` — default parametre, variadic, named arguments

```php
function selamla($isim = "Misafir") {
    return "Merhaba, $isim";       // $isim verilmezse varsayilan "Misafir" kullanilir
}

echo selamla() . "\n";              // "Merhaba, Misafir" - parametre verilmedi
echo selamla("Berkan") . "\n";      // "Merhaba, Berkan"

function toplam(...$sayilar) {      // ...$sayilar -> variadic, kac parametre gelirse bir array'de toplanir
    return array_sum($sayilar);     // array_sum -> array elemanlarini toplayan hazir fonksiyon (Gun 4'te detay)
}

echo toplam(1, 2, 3) . "\n";        // 6
echo toplam(10, 20) . "\n";         // 30 - farkli sayida parametre de calisir

function dikdortgenAlani($genislik, $yukseklik) {
    return $genislik * $yukseklik;
}

echo dikdortgenAlani(genislik: 5, yukseklik: 3) . "\n";   // 15 - named argument, sira onemsiz
echo dikdortgenAlani(yukseklik: 3, genislik: 5) . "\n";   // 15 - ayni sonuc, sira degisti
```

### 2. `scope_demo.php` — local, global, static local değişken

```php
$mesaj = "dis scope";

function scopeGoster() {
    // $mesaj burada YOK - fonksiyon ici scope disaridakinden tamamen izole
    echo $mesaj ?? "tanimsiz (dis scope'a erisim yok)";
}
scopeGoster();   // "tanimsiz (dis scope'a erisim yok)"

function globalIleEris() {
    global $mesaj;   // global -> disaridaki $mesaj'a ACIKCA erisim izni verir
    echo $mesaj;
}
globalIleEris();   // "dis scope"

function sayacArtir() {
    static $sayi = 0;   // static local degisken - fonksiyon COKLU CAGRILAR arasinda deger korur (AYNI istek/process icinde)
    $sayi++;
    return $sayi;
}
echo sayacArtir();   // 1
echo sayacArtir();   // 2 - ayni script calismasi icinde ikinci cagri, deger korundu
echo sayacArtir();   // 3
// NOT: bu script'i F5 ile YENIDEN calistirirsan (yeni istek), sayi yine 1'den baslar - Gun 1'in kurali burada da gecerli
```

### 3. `closure_ve_arrow_demo.php` — closure, `use`, arrow function, callable

```php
$komisyonOrani = 0.1;

// use OLMADAN - bu YANLIS ornek, calistirinca hata verir (referans icin yorum satirinda birakildi):
// $fiyatiHesaplaHatali = function ($fiyat) {
//     return $fiyat + ($fiyat * $komisyonOrani);   // Undefined variable $komisyonOrani
// };

$fiyatiHesapla = function ($fiyat) use ($komisyonOrani) {   // use (deger) -> $komisyonOrani'nin o anki degerini kopyalar
    return $fiyat + ($fiyat * $komisyonOrani);
};
echo $fiyatiHesapla(100) . "\n";   // 110

$sayac = 0;
$arttir = function () use (&$sayac) {   // use (&referans) -> disaridaki gercek degiskeni degistirir
    $sayac++;
};
$arttir();
$arttir();
echo $sayac . "\n";   // 2 - disaridaki $sayac gercekten degisti (deger ile capture'da bu olmazdi)

$carpan = 3;
$ucKatinaCikar = fn($x) => $x * $carpan;   // arrow function - use YAZMADAN otomatik capture (ama sadece OKUMA icin)
echo $ucKatinaCikar(5) . "\n";   // 15

$uzunlukHesapla = strlen(...);   // first-class callable syntax (PHP 8.1+) - strlen'i bir Closure nesnesine cevirir
echo $uzunlukHesapla("merhaba") . "\n";   // 7
```

---

## Kendini Test Et

1. `use ($x)` ile `use (&$x)` arasındaki fark ne, ne zaman hangisi gerekir?
2. Arrow function neden closure'dan daha az yazım gerektirir?
