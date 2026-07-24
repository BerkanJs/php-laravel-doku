# Gün 16 — Controllers ve Request/Response

> Bugünün "CRM'de bugün" görevi (`CompanyController` yazılır, `index()` companies listesini view'a gönderir) Gün 15 ile birlikte, kodun controller'sız test edilemediği için erken yazıldı. Bu derste o kodu ([app/Http/Controllers/CompanyController.php](c:\Users\BERKAN\Desktop\PhpLaravelDoku\Faz2-Laravel-CRM\crm-app\app\Http\Controllers\CompanyController.php)) referans alıp Request/Response konularını üstüne inşa ediyoruz.

## Bu Derste İlk Kez Göreceğin Syntax

| Yeni syntax | Örnek | Açıklama |
|-------------|-------|----------|
| Request injection | `public function store(Request $request)` | Method parametresi olarak istek nesnesini alma |
| `$request->input()` / `->all()` | `$request->input('name')` | Form/body verisine erişim |
| `redirect()` | `return redirect()->route('companies.index');` | Kullanıcıyı başka bir route'a yönlendirme |
| `response()->json()` | `return response()->json($data);` | JSON response döndürme |

---

## Senaryo

`CompanyController`'ın `store()` metodunu dolduracağımız gün geldiğinde (Gün 19'da Eloquent ile), formdan gelen şirket adını okuman gerekecek. C#'ta bunun birkaç yolu var: `HttpContext.Request.Form["name"]`, ya da model binding ile `[FromForm] CompanyDto dto`. Laravel'de de zaten controller'ımızda hazır duran bir parametre var — `store(Request $request)` (Artisan bunu otomatik üretti, Gün 14'teki Service Container/Artisan dersinde gördük):

```php
public function store(Request $request)
{
    //
}
```

Bu `$request`, C#'taki `HttpContext.Request`'in karşılığı, ama **doğrudan method parametresi olarak inject edilmiş** hâli — ayrıca `HttpContext` gibi global bir nesneden çekmene gerek yok. İçini dolduracağımız zaman:

```php
$ad = $request->input('name');   // tek bir alanı oku
$hepsi = $request->all();         // tüm form verisini array olarak al
```

Peki `store()` işini bitirince ne döndürecek? `index()` gibi bir `view()` değil — kullanıcı şirket ekledikten sonra listeye geri yönlendirilmeli. Bu da bugünün ikinci konusu: **Response türleri**.

---

## Analoji

Controller'ın her action metodu, gelen bir zarfı (`Request`) açıp içeriğini okuyan, sonra da duruma göre **farklı türde bir cevap** (`Response`) hazırlayan bir memur gibi düşünülebilir:
- `index()` → "işte sana bir sayfa" (`view()`)
- `store()` → "kaydettim, şimdi seni başka bir sayfaya gönderiyorum" (`redirect()`)
- API endpoint'i → "işte veri, ham JSON olarak" (`response()->json()`)

Aynı memur (controller), aynı tür zarfı (`Request`) alıyor ama işin sonunda verdiği cevap değişiyor — hangi cevabın uygun olduğuna action'ın **amacına** göre karar veriliyor.

---

## Teorik

### Controller yapısı (zaten üretildi, Gün 14/15)

`php artisan make:controller CompanyController --resource` — 7 method iskeleti üretir: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`. Bunu Gün 15'te zaten çalıştırdık, [CompanyController.php](c:\Users\BERKAN\Desktop\PhpLaravelDoku\Faz2-Laravel-CRM\crm-app\app\Http\Controllers\CompanyController.php)'da hâlihazırda duruyor.

### `Request` nesnesi

```php
public function store(Request $request)
{
    $ad = $request->input('name');           // tek alan, yoksa null döner
    $adVarsayilanli = $request->input('name', 'İsimsiz Şirket');   // yoksa varsayılan değer
    $hepsi = $request->all();                 // tüm form verisi, array olarak
    $sadeceBazilari = $request->only(['name', 'city']);   // sadece belirtilen alanlar
}
```

`$request->validate([...])` de burada kullanılabilir (form validation) — ama bunu tam olarak Gün 22'de, Form Request Validation konusunda detaylı işleyeceğiz.

### Response türleri

```php
return view('companies.index', ['companies' => $companies]);   // Gün 15'te index()'te zaten kullandık

return redirect()->route('companies.index');                    // isimlendirilmiş route'a yönlendirme (Gün 15)

return redirect()->route('companies.index')->with('success', 'Şirket eklendi');  // flash mesajla birlikte

return response()->json(['id' => 1, 'name' => 'Acme A.Ş.']);    // JSON - API endpoint'lerinde (Gün 28)
```

### Dependency injection: constructor vs method injection

Gün 14'te constructor injection'ı görmüştük (`__construct(private CompanyRepository $repo)`). Controller'larda bir de **method injection** var — her action metodunun kendi parametrelerine, o metoda özel bağımlılıklar enjekte edilebilir:

```php
class CompanyController extends Controller
{
    public function __construct(private SomeSharedService $servis) {}   // TÜM metotlarda ortak, constructor injection

    public function store(Request $request) { }          // sadece store()'a özel, method injection
    public function show(Request $request, Company $company) { }   // Request + route model binding BİRLİKTE
}
```

`Request`'in neden constructor'da değil method'da inject edildiğine dikkat: her action'ın kendi isteğe özel ihtiyaçları farklı olabilir (bazı action'lar `Request`'e ihtiyaç duymaz, bazıları hem `Request` hem route parametresine ihtiyaç duyar) — method injection bu esnekliği sağlıyor.

---

## C# ile Karşılaştırma

| Konu | C#/ASP.NET Core | Laravel |
|------|------------------|---------|
| Controller yapısı | Controller class, action method'lar | Aynı fikir — controller class, action metotları |
| Request erişimi | `HttpContext.Request` (global-ish) ya da model binding | `Request $request` — doğrudan method parametresi (ASP.NET'in method injection'ına daha yakın) |
| Form verisi okuma | `Request.Form["name"]` ya da `[FromForm]` model binding | `$request->input('name')`, `$request->all()` |
| Dönüş tipi çeşitliliği | `IActionResult` (`View()`, `Redirect()`, `Json()`) | `view()`, `redirect()`, `response()->json()` — kavramsal olarak birebir aynı çeşitlilik |

**Kritik çıkarım:** Laravel'de bir action'ın ne döndüreceği katı bir tipe bağlı değil (C#'taki `IActionResult` gibi tek bir arayüzün farklı implementasyonları) — basitçe `view()`, `redirect()` ya da `response()->json()` çağrısının sonucunu `return` edersin, hepsi Laravel'in response pipeline'ında işlenir.

---

## Bugüne Kadar Yazılmış Kodun Bu Dersle İlişkisi

[CompanyController.php](c:\Users\BERKAN\Desktop\PhpLaravelDoku\Faz2-Laravel-CRM\crm-app\app\Http\Controllers\CompanyController.php)'daki `index()` zaten bir `view()` response'u örneği. `store`, `update`, `destroy` metotları şu an boş — Gün 19'da (Eloquent) içleri `$request->all()` okuyup `Company::create(...)` çağıracak, sonra `redirect()->route('companies.index')` dönecek şekilde dolacak. Bugün sadece kavramı gördük, kodu Gün 19'da tamamlayacağız.

---

## Kendini Test Et

1. `$request->input('name')` ile `$request->all()` arasındaki fark ne, ne zaman hangisi tercih edilir?
2. `store()` metodunun neden `view()` değil `redirect()` döndürmesi daha doğru olur?
