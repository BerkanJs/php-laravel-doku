# Gün 4 — Diziler: PHP'nin İsviçre Çakısı

## Bu Derste İlk Kez Göreceğin Syntax

| Yeni syntax | Örnek | Açıklama |
|-------------|-------|----------|
| Associative array literal | `['ad' => 'Ali', 'yas' => 30]` | Gün 2'de sadece indexed hâlini (`[1,2,3]`) görmüştük — burada key=>value hâli |
| `foreach ... as $k => $v` | `foreach ($arr as $key => $value) {}` | Hem key hem value'ya aynı anda erişim |
| Array destructuring | `[$a, $b] = $arr;` | Array elemanlarını tek satırda ayrı değişkenlere ayırma |
| Spread operator | `[...$arr1, ...$arr2]` | Bir array'i başka bir array'in/çağrının içine "yayma" |
| `array_map`/`array_filter`/`array_reduce` | `array_map(fn($x) => $x * 2, $arr)` | Gün 3'te öğrendiğimiz closure/arrow function'ları array'lere uygulama |

---

## Senaryo

C#'ta bir `List<Urun>`'ü bir fonksiyona gönderip içinde bir eleman eklediğinde, dışarıdaki liste de bu değişikliği görür — çünkü `List<T>` **reference type**'tır, fonksiyona referansı gönderirsin.

PHP'de aynı beklentiyle kod yazarsan:

```php
function urunEkle($sepet) {
    $sepet[] = "Yeni Ürün";
    return;
}

$sepetim = ["Kalem", "Defter"];
urunEkle($sepetim);

print_r($sepetim);   // ["Kalem", "Defter"] - "Yeni Ürün" YOK!
```

Fonksiyonu çağırdın, içeride `$sepet[]`'e ekleme yaptın — ama dışarıdaki `$sepetim` hiç değişmedi. C#'tan gelen bir geliştirici için bu şaşırtıcıdır: "ben referansı gönderdim, neden değişmedi?" Cevap: **göndermedin**. PHP'de array'ler fonksiyona varsayılan olarak **kopyalanarak** (value semantics) gönderilir — `List<T>` gibi referans olarak değil. Bugünün konusu, PHP'nin bu "her şeyi tek bir array tipinde toplayan ama referans değil değer olarak davranan" yapısı.

---

## Analoji

**C# `List<T>` — bir binanın adresi:** İki kişiye aynı binanın adresini verirsin. Biri binaya girip bir oda boyarsa, diğer kişi de aynı binaya gittiğinde boyanmış odayı görür — çünkü ikisi de **aynı binaya** bakıyor (referans).

**PHP array — fotokopi makinesi:** `$b = $a;` yazdığında, PHP `$a`'nın bir **fotokopisini** çekip `$b`'ye verir. Artık `$b` üzerinde ne yaparsan yap (üstüne yazı yaz, sayfa ekle), `$a` bundan etkilenmez — çünkü onlar artık iki ayrı kağıt. (Perde arkasında PHP bu fotokopiyi "copy-on-write" ile tembel/optimize şekilde yapar — gerçek kopyalama sadece biri değişmeye başladığında olur — ama senin gördüğün davranış her zaman "ayrı kağıt" gibidir.)

---

## Teorik

### Tek yapı: indexed + associative + sıralı

C#'ta `List<T>` (sıralı liste), `Dictionary<K,V>` (key-value), `T[]` (sabit boyutlu dizi) ayrı ayrı tiplerdir. PHP'de bunların hepsi **tek bir yapı**: `array`.

```php
$indexliDizi = ["Kalem", "Defter", "Silgi"];              // sayısal index (0, 1, 2) otomatik atanır
$assocDizi = ["ad" => "Ali", "yas" => 30];                // kendi belirlediğin key'ler (string ya da int)
```

### `foreach` ile key ve value'ya erişim

```php
foreach ($assocDizi as $key => $value) {
    echo "$key: $value\n";
}
// $indexliDizi için de aynı syntax çalışır - key'ler bu sefer 0, 1, 2 olur
```

### `array_map`, `array_filter`, `array_reduce` — LINQ'nun PHP karşılığı

Gün 3'te öğrendiğimiz closure/arrow function'lar burada devreye giriyor:

```php
$sayilar = [1, 2, 3, 4, 5];

$ikiKatlari = array_map(fn($x) => $x * 2, $sayilar);          // her elemana fonksiyonu uygular -> [2,4,6,8,10]
$ciftler = array_filter($sayilar, fn($x) => $x % 2 === 0);     // sadece koşulu sağlayanları tutar -> [2, 4]
$toplam = array_reduce($sayilar, fn($tasinan, $x) => $tasinan + $x, 0);  // tek değere indirger -> 15
```

C#'ta `Select`/`Where`/`Aggregate`'e karşılık gelir — ama önemli bir fark var: LINQ **deferred execution** yapar (sorgu gerçekten `ToList()`/`foreach` ile "tüketilene" kadar çalışmaz), `array_map`/`filter`/`reduce` ise **eager**'dır — çağrıldığı an tüm array üzerinde hemen çalışır.

### Array destructuring

```php
$koordinat = [10, 20];
[$x, $y] = $koordinat;   // $x = 10, $y = 20 - tek satırda ayrıştırma
```

### Spread operator (`...`)

```php
$ilkGrup = [1, 2, 3];
$ikinciGrup = [4, 5];
$birlesik = [...$ilkGrup, ...$ikinciGrup];   // [1, 2, 3, 4, 5] - iki array'i birbirinin içine "yayar"
```

### `array_merge` vs `+` — key çakışmasında farklı davranır

```php
array_merge(['a' => 1], ['a' => 2]);   // ['a' => 2] - SAĞDAKİ kazanır (üzerine yazar)
['a' => 1] + ['a' => 2];               // ['a' => 1] - SOLDAKİ kazanır (+ operatörü mevcut key'i korur, eklemez)
```

Bu ince ama önemli bir fark — yanlış hatırlarsan (`+`'nın merge gibi davrandığını sanırsan) production'da sessiz bir bug'a dönüşür.

### Multi-dimensional array

```php
$sirket = [
    "ad" => "Acme A.Ş.",
    "calisanlar" => [
        ["ad" => "Ali", "departman" => "Satış"],
        ["ad" => "Ayşe", "departman" => "IT"],
    ],
];
```

Bu yapı, bir API'den JSON alıp `json_decode()` ile array'e çevirdiğinde karşına çıkacak birebir şekil — PHP array'i JSON ile doğal olarak eşleşir (Faz2'de API Resource konusunda tekrar göreceğiz).

---

## C# ile Karşılaştırma

| Konu | C# | PHP |
|------|----|----|
| Veri yapıları | `List<T>`, `Dictionary<K,V>`, `T[]` — ayrı tipler | Hepsi tek `array` |
| `Select`/`Where`/`Aggregate` | LINQ, **deferred execution** (lazy) | `array_map`/`filter`/`reduce`, **eager** (hemen çalışır) |
| Fonksiyona gönderme | `List<T>` **reference type** — içeride değişiklik dışarıya yansır | `array` **value semantics** (copy-on-write) — içeride değişiklik dışarıya yansımaz |
| Kopyalama (`$a = $b`) | `List<T> b = a;` → ikisi de aynı nesneyi gösterir | `$b = $a;` → `$b` ayrı bir kopyadır |

**Kritik çıkarım:** `$a = $b;` (ikisi de array) sonrası `$a`'yı değiştirmek `$b`'yi **etkilemez** — çünkü PHP array'i C#'taki `List<T>` gibi reference semantics değil, **value semantics** ile davranır. Bir array'in içeriğini bir fonksiyon içinde değiştirip dışarıya yansımasını istiyorsan, ya fonksiyondan yeni array'i `return` etmen ya da parametreyi referans olarak (`function f(&$arr)`) almanız gerekir.

---

## Kod ile Göster

Çalıştırmak için (ileride, PHP kurulduğunda):
```
cd Faz1-PHP-Dili/04-Diziler
php -S localhost:8000
```

### 1. `array_temelleri_demo.php` — indexed/associative, foreach, multi-dimensional, destructuring

```php
$indexliDizi = ["Kalem", "Defter", "Silgi"];
foreach ($indexliDizi as $key => $value) {
    echo "$key => $value\n";        // 0 => Kalem, 1 => Defter, 2 => Silgi
}

$assocDizi = ["ad" => "Ali", "yas" => 30];
foreach ($assocDizi as $key => $value) {
    echo "$key: $value\n";          // ad: Ali, yas: 30
}

$koordinat = [10, 20];
[$x, $y] = $koordinat;              // destructuring - tek satirda $x=10, $y=20
echo "x=$x, y=$y\n";

$sirket = [
    "ad" => "Acme A.S.",
    "calisanlar" => [
        ["ad" => "Ali", "departman" => "Satis"],
        ["ad" => "Ayse", "departman" => "IT"],
    ],
];
echo $sirket["calisanlar"][0]["ad"] . "\n";   // "Ali" - nested array'e index zinciriyle erisim
```

### 2. `array_fonksiyonlari_demo.php` — `array_map`, `array_filter`, `array_reduce`

```php
$sayilar = [1, 2, 3, 4, 5];

$ikiKatlari = array_map(fn($x) => $x * 2, $sayilar);
print_r($ikiKatlari);        // [2, 4, 6, 8, 10] - her eleman donusturuldu

$ciftler = array_filter($sayilar, fn($x) => $x % 2 === 0);
print_r($ciftler);           // [1 => 2, 3 => 4] - DIKKAT: orijinal index'ler KORUNUR, yeniden numaralanmaz
                              // bunu unutursak -> array_values($ciftler) ile yeniden indexlemen gerekebilir

$toplam = array_reduce($sayilar, fn($tasinan, $x) => $tasinan + $x, 0);
echo $toplam . "\n";          // 15 - tum elemanlar tek degere indirgendi (0 baslangic degeri)
```

### 3. `array_kopyalama_ve_birlestirme_demo.php` — value semantics ve merge vs `+`

```php
// --- Value semantics (senaryodaki bug'in kok nedeni) ---
function urunEkle($sepet) {
    $sepet[] = "Yeni Urun";   // bu SADECE fonksiyon icindeki KOPYAYI degistirir
    return $sepet;             // degisikligi geri dondurmek ZORUNDAYIZ, otomatik yansimaz
}

$sepetim = ["Kalem", "Defter"];
$sonuc = urunEkle($sepetim);

echo "Orijinal (degismedi): "; print_r($sepetim);   // ["Kalem", "Defter"]
echo "Fonksiyondan donen: "; print_r($sonuc);        // ["Kalem", "Defter", "Yeni Urun"]

// --- array_merge vs + ---
$a = ["a" => 1, "b" => 2];
$b = ["a" => 99, "c" => 3];

print_r(array_merge($a, $b));   // ["a" => 99, "b" => 2, "c" => 3] - SAGDAKI (b) kazanir, "a" uzerine yazildi
print_r($a + $b);                // ["a" => 1, "b" => 2, "c" => 3]  - SOLDAKI (a) kazanir, "a" korundu

// --- Spread operator ---
$ilkGrup = [1, 2, 3];
$ikinciGrup = [4, 5];
$birlesik = [...$ilkGrup, ...$ikinciGrup];
print_r($birlesik);   // [1, 2, 3, 4, 5]
```

---

## Kendini Test Et

1. `$a = $b;` (ikisi de array) sonrası `$a`'yı değiştirmek `$b`'yi etkiler mi? C#'taki `List<T>` ile fark nedir?
2. `array_merge(['a'=>1], ['a'=>2])` sonucu ne olur, `+` operatörüyle fark nedir?
