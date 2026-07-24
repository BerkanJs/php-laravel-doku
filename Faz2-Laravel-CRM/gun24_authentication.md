# Gün 24 — Authentication (Laravel Breeze/Sanctum)

## Bu Derste İlk Kez Göreceğin Syntax

| Yeni syntax | Örnek | Açıklama |
|-------------|-------|----------|
| `Auth::check()` | `if (Auth::check()) {...}` | Kullanıcı giriş yapmış mı |
| `Auth::user()` | `Auth::user()->name` | Giriş yapmış kullanıcının kendisi (bir `User` Eloquent nesnesi) |
| `auth()->id()` | `auth()->id()` | Giriş yapmış kullanıcının ID'si (kısayol helper) |
| `Hash::make()` | `Hash::make($sifre)` | Şifreyi bcrypt ile hash'ler |
| `Hash::check()` | `Hash::check($girilenSifre, $hashliSifre)` | Girilen şifre ile hash'i karşılaştırır |

---

## Senaryo: "Giriş yaptım" derken gerçekte ne oluyor

Kullanıcı bir login formuna email/şifre girip gönderdiğinde, arka planda **tam olarak** şunlar oluyor:

**1) Şifre karşılaştırması — asla düz metin karşılaştırma değil**

```php
if (Hash::check($request->password, $user->password)) {
    // giris basarili
}
```

Veritabanındaki `password` kolonu şifrenin kendisini DEĞİL, **bcrypt hash'ini** tutar (`Hash::make()` ile üretilmiş — Gün 19'da `User` modelinde gördüğümüz `'password' => 'hashed'` cast'i, `create()` sırasında bunu otomatik yapıyordu). `Hash::check($girilen, $hash)` neden `$girilen === $hash` gibi basit bir karşılaştırma DEĞİL: bcrypt her hash'lemede farklı bir "salt" kullanır, yani aynı şifre iki kere hash'lense bile **iki farklı string** üretir. `Hash::check()` bu salt'ı hash'in içinden çıkarıp, girilen şifreyi **aynı salt'la** yeniden hash'leyip öyle karşılaştırır — bu yüzden `==`/`===` asla kullanılmaz, özel bir fonksiyon gerekir.

**2) Giriş başarılıysa — `Auth::login($user)` ne yapıyor**

```php
Auth::login($user);
```

Bu satır çalışınca, Laravel `$user->id`'yi **session'a yazar** (Gün 1/2'de defalarca vurguladığımız, PHP process'inin dışında yaşayan tek kalıcı depo). Response'la birlikte tarayıcıya bir session cookie'si gider.

**3) Sonraki her istekte — `Auth::check()` bu bilgiyi nereden buluyor**

Kullanıcı bir sonraki sayfaya geçtiğinde, tarayıcı o session cookie'sini otomatik geri gönderir. Laravel'in `StartSession` middleware'i (Gün 23'te stack trace'te gördüğümüz, hep orada duran middleware) bu cookie'den session'ı yükler, session içindeki `user_id`'yi bulur, **o ID'ye ait `User` kaydını veritabanından çeker** ve `Auth::user()`'ın döneceği nesne olarak hazırlar. `Auth::check()` sadece "session'da bir user_id var mı" diye bakar.

**Zincirin tamamı:** Login formu → `Hash::check()` → `Auth::login()` (session'a yaz) → cookie tarayıcıya gider → sonraki istekte cookie geri gelir → `StartSession` middleware session'ı okur → `Auth::user()`/`Auth::check()` bu veriyi kullanır → Gün 23'teki `auth` middleware'i bu bilgiye bakıp izin verir/reddeder.

---

## Analoji

Bir binaya giriş kartı almak gibi düşün: `Hash::check()` güvenlik görevlisinin kimliğini kontrol etmesi (parmak izini her seferinde yeniden okuyup kayıtla eşleştirmesi — kayıtlı "parmak izi fotoğrafı" değil, aynı yöntemle yeniden ölçüp eşleştirme). `Auth::login()`, sana bir giriş kartı (session) vermesi. Bir sonraki gün binaya girerken artık parmak izini yeniden okutmuyorsun, sadece kartı (cookie) gösteriyorsun — güvenlik görevlisi kartın geçerli olup olmadığına bakıyor (`Auth::check()`).

---

## Teorik

### Laravel Breeze — hazır scaffolding

```
composer require laravel/breeze --dev
php artisan breeze:install
npm install && npm run build
php artisan migrate
```

Bu komutlar login/register/şifre sıfırlama sayfalarını, ilgili controller'ları ve route'ları **otomatik** üretir — `users` tablosu zaten Gün 18'den beri vardı (Laravel'in varsayılan migration'ı). Breeze, session tabanlı (web uygulamaları için) auth'u tam kurulu getirir.

### Laravel Sanctum — token tabanlı (bugün kullanmayacağız)

SPA/mobil uygulamalar için, session/cookie yerine her istekte bir **token** gönderilir (`Authorization: Bearer <token>`). CRM bir web uygulaması olduğu için bugün Breeze yeterli — Sanctum, Gün 28'de API Resources işlenirken gündeme gelebilir.

### `Auth::user()`, `Auth::check()`, `auth()->id()`

```php
if (Auth::check()) {              // session'da giris yapilmis bir kullanici var mi
    $isim = Auth::user()->name;   // varsa, o kullanicinin User nesnesi (Gun 19'daki Eloquent Model)
    $id = auth()->id();           // kisayol: Auth::user()->id ile ayni
}
```

---

## C# ile Karşılaştırma

| Konu | C#/ASP.NET Core | Laravel |
|------|------------------|---------|
| Session tabanlı auth | Cookie Authentication + Identity scaffolding | Breeze |
| Token tabanlı auth | JWT Bearer authentication | Sanctum |
| Giriş yapmış kullanıcı | `HttpContext.User` / `ClaimsPrincipal` | `Auth::user()` |
| Şifre hash'leme | `PasswordHasher<T>` (salted hash) | `Hash::make()` (bcrypt, salted) |
| Şifre karşılaştırma | `PasswordHasher.VerifyHashedPassword()` | `Hash::check()` — ikisi de aynı sebeple `==` kullanmaz |

**Kritik çıkarım:** Session tabanlı auth'un temel mekaniği (server-side session + cookie) C#'takiyle birebir aynı fikir — asıl önemli olan, Gün 1/2'den beri vurguladığımız "PHP process kendi belleğinde hiçbir şeyi güvenle saklayamaz" ilkesinin, auth'un TEMELİNİ oluşturması: giriş bilgisi mutlaka session (yani process dışı bir depo) üzerinden taşınır, yoksa bir sonraki istekte kullanıcı "tanınmaz".

---

## Bugün CRM'de Ne Ekleyeceğiz

Breeze kurulacak, `/login`/`/register` sayfaları otomatik gelecek. Sonra Gün 23'te ertelediğimiz gerçek `auth` middleware'i `companies`/`contacts` route'larına eklenip CRM sadece giriş yapmış kullanıcılara açılacak.

> Not: Breeze kurulumu `npm install`/`npm run build` gerektirir (Node.js) — bu adıma geldiğimizde önce Node/npm'in bu makinede kurulu olup olmadığını kontrol edeceğiz.

---

## Kendini Test Et

1. `Hash::check()` neden `==` ya da `===` ile değiştirilemez — bcrypt'in salt kullanması bunu nasıl etkiliyor?
2. `Auth::login($user)` çağrıldığında hangi veri nereye yazılıyor, bir sonraki istekte bu veri nasıl geri bulunuyor?
3. Session tabanlı auth ile token tabanlı auth'un CRM gibi bir web uygulamasında neden Sanctum yerine Breeze tercih edilir?
