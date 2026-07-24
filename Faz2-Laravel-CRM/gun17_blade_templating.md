# Gün 17 — Blade Templating

## Bu Derste İlk Kez Göreceğin Syntax

| Yeni syntax | Örnek | Açıklama |
|-------------|-------|----------|
| `{{ }}` | `{{ $company['name'] }}` | Zaten Gün 15/16'da kullandık — otomatik HTML-escape eden çıktı |
| `{!! !!}` | `{!! $html !!}` | Escape YAPMADAN ham HTML basar — dikkatli kullanılır |
| `@if`/`@foreach`/`@auth` | `@if($condition) ... @endif` | Kontrol yapıları için Blade karşılığı |
| `@extends`/`@section`/`@yield` | `@extends('layouts.app')` | Layout kalıtımı |
| `@csrf` | `<form>@csrf</form>` | Form içine CSRF token basar |
| Blade component | `<x-alert type="error">...</x-alert>` | Yeniden kullanılabilir view parçası |

---

## Senaryo

[companies/index.blade.php](c:\Users\BERKAN\Desktop\PhpLaravelDoku\Faz2-Laravel-CRM\crm-app\resources\views\companies\index.blade.php) dosyasını Gün 15'te yazarken bilerek çok basit bıraktık — `<!DOCTYPE html>`'den `</html>`'e kadar her şeyi tek dosyaya yazdık. CRM büyüdükçe `contacts/index.blade.php`, `deals/index.blade.php` gibi başka view'lar da gelecek — her biri için `<head>`, navigasyon menüsü, `<footer>` gibi ortak HTML'i **tekrar tekrar** yazman gerekecek. Bir gün navigasyon menüsüne yeni bir link eklemen gerekirse, bunu **her view dosyasında ayrı ayrı** değiştirmen gerekir.

C#'ta bunun çözümü `_Layout.cshtml` + `@RenderBody()` — bir ortak iskelet yazarsın, her sayfa sadece kendine özgü kısmı doldurur. Blade'de aynı fikir `@extends`/`@section`/`@yield` üçlüsüyle çözülür — bugünün konusu bu.

---

## Analoji

Bir resmi mektup şablonu düşün: üst kısımda her zaman aynı antet (logo, adres) var, sadece ortadaki mektup metni değişiyor. `@extends('layouts.app')` demek "bu antetli kağıdı kullan" demektir; `@section('content')` ise "işte benim yazacağım kısım, sen antetin neresine koyacağını bilirsin" demektir. Layout dosyasındaki `@yield('content')`, antetin üzerinde "buraya mektup metni gelecek" diye bırakılmış boşluktur.

---

## Teorik

### `{{ }}` vs `{!! !!}`

```blade
{{ $company['name'] }}   {{-- otomatik HTML-escape - XSS'e karşı güvenli, C#'taki @variable ile aynı --}}
{!! $html !!}            {{-- escape YOK - $html içinde <script> varsa ÇALIŞIR, XSS riski --}}
```

`{!! !!}` sadece kendi ürettiğin, güvendiğin HTML için kullanılır (örn. bir zengin metin editöründen gelen içerik) — kullanıcıdan gelen ham veriyle **asla** kullanılmaz.

### Directives: `@if`, `@foreach`, `@auth`

```blade
@if ($companies->isEmpty())
    <p>Henüz şirket yok.</p>
@else
    @foreach ($companies as $company)
        <li>{{ $company['name'] }}</li>
    @endforeach
@endif

@auth
    <p>Hoş geldin, {{ auth()->user()->name }}</p>
@endauth
```

`@foreach`'i zaten [companies/index.blade.php](c:\Users\BERKAN\Desktop\PhpLaravelDoku\Faz2-Laravel-CRM\crm-app\resources\views\companies\index.blade.php)'da kullanmıştık. `@auth` (Gün 24'te Breeze kurulunca anlamlı olacak) — sadece giriş yapmış kullanıcıya gösterilecek içerik için.

### Layout ve inheritance: `@extends`, `@section`, `@yield`

**`layouts/app.blade.php`** (ortak iskelet):
```blade
<!DOCTYPE html>
<html>
<head><title>@yield('title', 'CRM')</title></head>
<body>
    <nav>...ortak navigasyon...</nav>
    @yield('content')
</body>
</html>
```

**`companies/index.blade.php`** (sadece kendine özgü kısım):
```blade
@extends('layouts.app')

@section('title', 'Şirketler')

@section('content')
    <h1>Şirketler</h1>
    <ul>
        @foreach ($companies as $company)
            <li>{{ $company['name'] }}</li>
        @endforeach
    </ul>
@endsection
```

`@yield('content')`, layout'ta "buraya alt sayfanın içeriği gelecek" diye bırakılan boşluktur; `@section('content') ... @endsection`, alt sayfanın o boşluğu neyle dolduracağını tanımlar.

### Blade component

```blade
<x-alert type="error" :message="$hata" />
```

Razor'daki partial view + view component'in birleşimi gibi düşünülebilir — hem HTML hem (opsiyonel) kendi PHP mantığını taşıyabilen, tekrar kullanılabilir bir parça.

### `@csrf` — neden zorunlu

```blade
<form method="POST" action="{{ route('companies.store') }}">
    @csrf
    <input type="text" name="name">
</form>
```

Laravel'de `web.php` route'ları (session tabanlı) varsayılan olarak **CSRF koruması** altındadır — `@csrf` formun içine gizli bir token basar, sunucu bu token'ı doğrulamadan POST/PUT/DELETE isteklerini **reddeder** (419 hatası). Bu, bir saldırganın senin oturumunu kullanarak farkında olmadan senin adına form göndermesini (Cross-Site Request Forgery) engeller. `@csrf` unutulursa form hiç çalışmaz — ASP.NET Core'daki `@Html.AntiForgeryToken()` ile birebir aynı amaç.

---

## C# ile Karşılaştırma

| Konu | C#/Razor | Blade |
|------|----------|-------|
| Değişken yazdırma | `@variable` (otomatik encode) | `{{ $variable }}` (otomatik encode) |
| Escape'siz yazdırma | `@Html.Raw(html)` | `{!! $html !!}` |
| Layout | `_Layout.cshtml` + `@RenderBody()`/`@RenderSection()` | `@extends` + `@section`/`@yield` |
| Yeniden kullanılabilir parça | Partial view / View Component / Blazor Component | Blade component (`<x-... />`) |
| CSRF token | `@Html.AntiForgeryToken()` | `@csrf` |

**Kritik çıkarım:** İkisi de aynı temel problemi çözüyor — "tekrar eden HTML'i tek yerde tut, sayfaya özgü kısmı ayrı yaz." Syntax'ları farklı ama zihniyet birebir aynı, bu yüzden C#'tan gelen biri için Blade'in öğrenilmesi gereken asıl kısmı, kavram değil sadece syntax.

---

## Kendini Test Et

1. `@yield('content')` ile `@section('content') ... @endsection` arasındaki ilişki nedir — hangisi hangisini "doldurur"?
2. `@csrf` unutulursa forma ne olur?
3. `{{ }}` yerine `{!! !!}` kullanmak ne zaman güvenlik açığına dönüşür?
