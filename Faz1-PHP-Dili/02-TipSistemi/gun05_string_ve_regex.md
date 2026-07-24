# Gün 5 — String İşlemleri ve Regex

## Bu Derste İlk Kez Göreceğin Syntax

| Yeni syntax | Örnek | Açıklama |
|-------------|-------|----------|
| Tek tırnak string | `'Merhaba $isim'` | İçindeki `$isim` **interpolate edilmez**, olduğu gibi yazdırılır |
| Heredoc | `<<<EOT ... EOT;` | Çok satırlı, çift tırnak gibi interpolate eden string |
| Nowdoc | `<<<'EOT' ... EOT;` | Çok satırlı, tek tırnak gibi interpolate ETMEYEN string |
| Regex (PCRE) | `preg_match('/^[0-9]+$/', $x)` | Metin desenleriyle eşleştirme — kendi başına küçük bir "dil" |

---

## Senaryo

Bir kayıt formunda kullanıcı adının en fazla 5 karakter olmasını istiyorsun:

```php
function kullaniciAdiGecerliMi($ad) {
    return strlen($ad) <= 5;
}

var_dump(kullaniciAdiGecerliMi("Ahmet"));   // true  - 5 karakter, beklenen
var_dump(kullaniciAdiGecerliMi("Öykü"));    // false! - ama "Öykü" sadece 4 harf!
```

`"Öykü"` görsel olarak **4 harf**. Ama `strlen("Öykü")` **6** döner, 4 değil! Neden? `strlen` **byte** sayar, **karakter** değil. UTF-8 kodlamasında İngilizce harfler (`a-z`, `0-9`) 1 byte kaplar, ama Türkçe'ye özgü harfler (`ç, ğ, ı, ö, ş, ü` ve büyük hâlleri) **2 byte** kaplar. `"Öykü"` = Ö(2 byte) + y(1) + k(1) + ü(2 byte) = **6 byte**, ama **4 karakter**.

Bu bug İngilizce test verisiyle çalışırken **hiç fark edilmez** — çünkü İngilizce harflerde byte sayısı = karakter sayısı. Türkçe (veya Almanca, Fransızca, herhangi bir Latin-dışı-karakterli dil) verisi gelene kadar saklı kalır, sonra production'da gerçek kullanıcı adlarıyla patlar. Çözüm: `strlen` yerine `mb_strlen` (multibyte strlen) kullanmak.

---

## Analoji

**ASCII harfler — tek kişilik kutular:** `a`, `b`, `1`, `2` gibi harfler UTF-8'de tek bir kutuya (1 byte) sığar. Kutu sayısı = harf sayısı.

**Türkçe'ye özgü harfler — iki kişilik kutular:** `ç, ğ, ı, ö, ş, ü` gibi harfler UTF-8'de **iki kutuya** ihtiyaç duyar. `strlen`, kutuları (byte'ları) sayan bir görevli gibidir — kaç harf olduğunu değil, kaç kutu dolu olduğunu söyler. `mb_strlen` ise gerçekten harfleri (karakterleri) sayan görevlidir, kutu boyutuyla ilgilenmez.

**C#'ta bu ayrım neden daha az hissedilir:** .NET string'leri **UTF-16** kullanır ve `.Length`, Türkçe'nin tüm harfleri dahil çoğu karakter için **karakter sayısını** doğru verir (Türkçe harfler UTF-16'da hep tek "code unit"). PHP'nin varsayılan string fonksiyonları ise **byte-oriented**'dır — yani PHP'de bu ayrımı bilerek yönetmen gerekir, C#'ta çoğunlukla arka planda hallolur.

---

## Teorik

### Tek tırnak vs çift tırnak

```php
$isim = "Ali";
echo "Merhaba $isim";    // "Merhaba Ali" - çift tırnak İÇİNDEKİ $degisken'i interpolate eder (değeriyle değiştirir)
echo 'Merhaba $isim';    // "Merhaba $isim" - tek tırnak OLDUĞU GİBİ yazdırır, interpolate etmez
```

Performans farkı: tek tırnak string'i PHP parse ederken interpolation aramaz, bu yüzden **teorik olarak** biraz daha hızlıdır — ama pratikte bu fark modern PHP'de ihmal edilebilir düzeydedir. Asıl önemli olan **davranış farkı**: değişken içeren metinlerde çift tırnak, sabit/literal metinlerde (regex pattern'leri gibi) tek tırnak tercih edilir.

### Heredoc / Nowdoc — çok satırlı string

```php
$isim = "Ali";

$heredoc = <<<EOT
Merhaba $isim,
Bu çok satırlı bir metin.
EOT;
// Heredoc -> çift tırnak gibi davranır, $isim interpolate edilir

$nowdoc = <<<'EOT'
Merhaba $isim,
Bu metin OLDUĞU GİBİ kalır.
EOT;
// Nowdoc -> tek tırnak gibi davranır (başlangıç etiketi tek tırnaklı), $isim interpolate EDİLMEZ
```

### Sık kullanılan string fonksiyonları

```php
str_contains("Merhaba Dünya", "Dünya");     // true (PHP 8+) - içeriyor mu
str_starts_with("Merhaba Dünya", "Merhaba"); // true (PHP 8+) - ile mi başlıyor
explode(",", "elma,armut,çilek");            // ["elma", "armut", "çilek"] - string'i diziye böler
implode(", ", ["elma", "armut", "çilek"]);   // "elma, armut, çilek" - diziyi string'e birleştirir
sprintf("Toplam: %d TL", 150);               // "Toplam: 150 TL" - C#'taki string.Format karşılığı
```

### Regex — `preg_match`, `preg_replace`

PHP regex motoru **PCRE** (Perl Compatible Regular Expressions) kullanır. Pattern'ler `/` (ya da başka bir delimiter) ile sarılır:

```php
preg_match('/^[0-9]+$/', "12345");         // 1 (eşleşti) - ^ baştan, $ sona kadar sadece rakam
preg_match('/^[0-9]+$/', "123a5");         // 0 (eşleşmedi) - harf içeriyor

preg_replace('/[0-9]/', '#', "Sifre123");  // "Sifre###" - her rakamı # ile değiştirir
```

`^` (başlangıç), `$` (bitiş), `[0-9]` (karakter sınıfı), `+` (bir veya daha fazla) — regex'in kendi mini-syntax'ı, PHP'ye özgü değil, C#'ın `Regex` sınıfıyla aynı PCRE mantığını paylaşır.

### Multibyte fonksiyonlar (`mb_*`)

```php
strlen("Öykü");      // 6 - BYTE sayar (Ö ve ü UTF-8'de 2'şer byte)
mb_strlen("Öykü");   // 4 - KARAKTER sayar (doğru sonuç)
```

Kural: Türkçe (veya herhangi bir non-ASCII) karakter içerebilecek her yerde `strlen` yerine `mb_strlen`, `substr` yerine `mb_substr`, `strtoupper` yerine `mb_strtoupper` kullanılmalı.

---

## C# ile Karşılaştırma

| Konu | C# | PHP |
|------|----|----|
| String interpolation | `$"Merhaba {isim}"` | Çift tırnak `"Merhaba $isim"` — ama tek tırnak `'...'` PHP'ye özgü, interpolate ETMEZ (C#'ta böyle bir ayrım yok) |
| Formatlama | `string.Format`/composite formatting | `sprintf` |
| Immutability | String immutable | String immutable (aynı) — ama `.` ile concat her seferinde yeni string yaratır, döngüde çok concat C#'taki `StringBuilder` ihtiyacına benzer bir performans deseni oluşturur |
| Karakter sayma | `.Length` — UTF-16, Türkçe dahil çoğu karakter doğru sayılır | `strlen` (byte) vs `mb_strlen` (karakter) — bilerek doğru olanı seçmen gerekir |
| Regex | `System.Text.RegularExpressions.Regex` | `preg_match`/`preg_replace`, ikisi de PCRE tabanlı, pattern syntax'ı büyük ölçüde ortak |

---

## Kod ile Göster

Çalıştırmak için (ileride, PHP kurulduğunda):
```
cd Faz1-PHP-Dili/02-TipSistemi
php -S localhost:8000
```

### 1. `string_temelleri_demo.php` — tırnak farkı, heredoc/nowdoc, sık kullanılan fonksiyonlar

```php
$isim = "Ali";

echo "Merhaba $isim\n";    // "Merhaba Ali" - cift tirnak interpolate eder
echo 'Merhaba $isim' . "\n";    // "Merhaba $isim" - tek tirnak OLDUGU GIBI yazdirir

$heredoc = <<<EOT
Merhaba $isim,
Bu cok satirli bir metin.
EOT;
echo $heredoc . "\n\n";    // Ali'nin adi interpolate edildi

$nowdoc = <<<'EOT'
Merhaba $isim,
Bu metin OLDUGU GIBI kalir.
EOT;
echo $nowdoc . "\n\n";     // $isim interpolate EDILMEDI, oldugu gibi yazildi

var_dump(str_contains("Merhaba Dunya", "Dunya"));      // true
var_dump(str_starts_with("Merhaba Dunya", "Merhaba")); // true

$parcalar = explode(",", "elma,armut,cilek");
print_r($parcalar);                                     // ["elma", "armut", "cilek"]
echo implode(" | ", $parcalar) . "\n";                   // "elma | armut | cilek"

echo sprintf("Toplam: %d TL\n", 150);                    // "Toplam: 150 TL"
```

### 2. `regex_demo.php` — `preg_match`, `preg_replace`

```php
var_dump(preg_match('/^[0-9]+$/', "12345"));   // int(1) - eslesti, sadece rakam
var_dump(preg_match('/^[0-9]+$/', "123a5"));   // int(0) - eslesmedi, harf iceriyor

$sonuc = preg_replace('/[0-9]/', '#', "Sifre123");
echo $sonuc . "\n";                             // "Sifre###" - her rakami # ile degistirdi

// E-posta formatina yakin basit bir kontrol (production icin filter_var daha uygun, burasi sadece regex ornegi):
var_dump(preg_match('/^[^@]+@[^@]+\.[^@]+$/', "test@example.com"));   // int(1)
```

### 3. `multibyte_demo.php` — `strlen` vs `mb_strlen` (senaryodaki bug'in cozumu)

```php
$isim1 = "Ahmet";
$isim2 = "Oyku";      // gercekte Turkce "Öykü" - ascii guvenli gostermek icin boyle yazildi, asagida gercek karakterle deniyoruz
$isimGercek = "Öykü";

echo "strlen('Ahmet') = " . strlen($isim1) . "\n";        // 5 - ascii, byte = karakter
echo "mb_strlen('Ahmet') = " . mb_strlen($isim1) . "\n";   // 5 - ayni sonuc

echo "strlen('Öykü') = " . strlen($isimGercek) . "\n";       // 6 - YANLIS: byte sayiyor (Ö ve ü 2'ser byte)
echo "mb_strlen('Öykü') = " . mb_strlen($isimGercek) . "\n"; // 4 - DOGRU: karakter sayiyor

function kullaniciAdiGecerliMiYanlis($ad) {
    return strlen($ad) <= 5;          // YANLIS - Turkce karakterlerde byte sayar, gercek karakter sayisi degil
}

function kullaniciAdiGecerliMiDogru($ad) {
    return mb_strlen($ad) <= 5;       // DOGRU - gercek karakter sayisini kontrol eder
}

var_dump(kullaniciAdiGecerliMiYanlis("Öykü"));   // false - YANLIS SONUC, "Öykü" 4 harf, 5'ten kucuk olmali
var_dump(kullaniciAdiGecerliMiDogru("Öykü"));    // true  - DOGRU SONUC
```

---

## Kendini Test Et

1. Tek tırnak ile çift tırnak string arasındaki performans ve davranış farkı ne?
2. `mb_strlen` kullanmazsan Türkçe karakterli string'lerde ne olur?
