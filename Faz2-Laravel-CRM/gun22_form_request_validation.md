# Gün 22 — Form Request Validation

## Bu Derste İlk Kez Göreceğin Syntax

| Yeni syntax | Örnek | Açıklama |
|-------------|-------|----------|
| Inline validation | `$request->validate(['name' => 'required|max:255'])` | Controller içinde doğrudan doğrulama |
| Form Request class | `php artisan make:request StoreContactRequest` | Doğrulama kurallarını ayrı bir class'a taşıma |
| Validation rule'ları | `'email' => 'required\|email\|unique:contacts,email'` | Kural zinciri, `\|` ile ayrılır |
| `$errors` | `@foreach ($errors->all() as $error)` | Blade'de otomatik hazır bulunan hata çantası |
| `old('name')` | `<input value="{{ old('name') }}">` | Validation başarısız olunca formu eski değerlerle doldurma |

---

## Senaryo

Gün 21'de `Contact` modelini yazdık — `company_id` foreign key ile. Şimdi `StoreContactRequest` olmadan `store()` metodunu şöyle yazdığını hayal et:

```php
public function store(Request $request)
{
    Contact::create($request->all());   // hicbir dogrulama yok
    return redirect()->route('companies.index');
}
```

`$fillable` (Gün 19) `name`, `email`, `company_id` alanlarının **toplu atanabilir** olmasına izin veriyor — ama bu alanların **doğru değerler taşıdığını garanti etmiyor**. İki senaryo:

1. Kullanıcı `email` alanını boş bırakırsa: `$fillable` buna izin verir, `Contact::create()` çalışır, veritabanına **boş email'li bir kayıt** girer.
2. Form'daki `company_id` alanı manipüle edilip var olmayan bir ID (örn. `999`) gönderilirse: Gün 18'de `contacts.company_id` üzerine kurduğumuz **foreign key constraint** devreye girer, veritabanı `SQLSTATE... FOREIGN KEY constraint failed` hatası fırlatır. Bu, Gün 10'da işlediğimiz gibi **yakalanmamış bir exception** olarak kullanıcıya çirkin bir 500 sayfası gösterir — hâlbuki asıl istediğimiz, kullanıcıya "geçerli bir şirket seç" diye **anlaşılır bir form hatası** göstermek.

Bugünün konusu: verinin veritabanına **gitmeden önce** doğrulanması.

---

## Analoji

Validation, gümrükten önceki bir bagaj kontrolü gibidir — yasak bir eşya (geçersiz veri) uçağa (veritabanına) binmeden önce, kapıda yakalanır. `$fillable` (Gün 19) sadece "hangi valizlerin kontrol edileceğini" belirler (hangi alanlara toplu atama izni var); validation ise "o valizlerin içinde ne olduğunu" kontrol eder.

---

## Teorik

### Inline validation — `$request->validate()`

```php
public function store(Request $request)
{
    $veri = $request->validate([
        'name' => 'required|max:255',
        'email' => 'required|email|unique:contacts,email',
        'company_id' => 'required|exists:companies,id',
    ]);

    Contact::create($veri);
    return redirect()->route('companies.index');
}
```

**Bu satır çalışınca gerçekte ne oluyor:** `$request->validate([...])` her kuralı sırayla kontrol eder. Hepsi geçerse, doğrulanmış veriyi bir array olarak **döndürür** (`$veri`) ve kod bir sonraki satıra devam eder. **Herhangi bir kural başarısız olursa**, bu metot bir `ValidationException` **fırlatır** (Gün 10'daki exception mekanizması!) — ama bunu sen `try/catch` ile yakalamana gerek yok, Laravel'in **varsayılan exception handler'ı** bunu otomatik yakalar ve şunu yapar: kullanıcıyı **önceki forma geri yönlendirir**, hataları **session'a flash mesaj olarak** yazar (Gün 1/2'deki "session, istekler arası hayatta kalan tek şey" ilkesi burada devrede), form alanlarının eski değerlerini de (`old()` ile erişilebilecek şekilde) session'a koyar.

### Validation kuralları

```php
'name' => 'required|max:255',                    // zorunlu, en fazla 255 karakter
'email' => 'required|email|unique:contacts,email', // zorunlu, email formatinda, contacts tablosunda BENZERSIZ
'company_id' => 'required|exists:companies,id',    // zorunlu, companies tablosunda BOYLE BIR id GERCEKTEN var mi
```

`unique:contacts,email` ve `exists:companies,id` — ikisi de arka planda **veritabanına ek bir SELECT sorgusu** atar (örn. `SELECT COUNT(*) FROM contacts WHERE email = ?`), bu yüzden validate() çağrısı bazı kurallar için gerçekten DB'ye gidiyor — sadece PHP tarafında string kontrolü yapmıyor.

### Form Request class — validation'ı controller'dan ayırma

```
php artisan make:request StoreContactRequest
```

```php
class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // bu istegi yapan kullanici yetkili mi (Gun 25'te Policy ile birlikte anlamli olacak)
    }

    public function rules(): array
    {
        return [
            'name' => 'required|max:255',
            'email' => 'required|email|unique:contacts,email',
            'company_id' => 'required|exists:companies,id',
        ];
    }
}
```

Controller'da kullanımı:

```php
public function store(StoreContactRequest $request)   // Request yerine StoreContactRequest tip belirtildi
{
    Contact::create($request->validated());   // kurallar ZATEN bu satirdan ONCE, method injection sirasinda calisti
    return redirect()->route('companies.index');
}
```

**Burada ne zaman ne oluyor:** `store()` metodu çağrılmadan **önce**, Laravel'in Service Container'ı (Gün 14) `StoreContactRequest`'i inject etmeye çalışırken, önce `authorize()`'ı, sonra `rules()`'daki kuralları otomatik çalıştırır. Kurallar geçmezse `store()` metodunun **içindeki kod hiç çalışmaz** — kullanıcı doğrudan forma geri yönlendirilir. Yani validation, controller metodunun **gövdesine girmeden önce**, dışarıda gerçekleşiyor.

### `$errors` ve `old()` — Blade tarafında

```blade
@if ($errors->any())
    <ul>
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

<input type="text" name="name" value="{{ old('name') }}">
```

`$errors`, sen hiç tanımlamadan Blade'de **her zaman hazır** bulunan bir değişken (Laravel'in bir middleware'i, session'daki hata varsa onu otomatik `$errors`'a doldurur). `old('name')`, bir önceki başarısız denemede kullanıcının o alana ne yazdığını session'dan geri getirir — kullanıcı formu **baştan doldurmak zorunda kalmaz**.

---

## C# ile Karşılaştırma

| Konu | C#/ASP.NET Core | Laravel |
|------|------------------|---------|
| Doğrulama tanımı | DataAnnotations (`[Required]`, `[EmailAddress]`) ya da FluentValidation | Form Request class'ında `rules()` metodu |
| Doğrulama sonucu kontrolü | Elle `ModelState.IsValid` kontrolü | Otomatik — `store()` gövdesine hiç girilmez, otomatik redirect |
| Benzersizlik kontrolü | FluentValidation'da elle async validator yazman gerekir | `unique:contacts,email` — built-in, hazır kural |
| Hataları view'a taşıma | Elle `ModelState` view'a taşınır | `$errors` otomatik, hiç kod yazmadan Blade'de hazır |

**Kritik çıkarım:** Laravel'de validation başarısız olduğunda **otomatik redirect + `$errors` bag** oluşur — ASP.NET Core'da bunu elle `ModelState` kontrolü yapıp view'a taşıman gerekirdi. Bu, Laravel'in "conventions over configuration" felsefesinin bir başka örneği: standart CRUD validation akışı için neredeyse hiç boilerplate yazmıyorsun.

---

## Bugün CRM'de Ne Ekleyeceğiz

`StoreContactRequest` — `email` zorunlu ve `contacts` tablosunda benzersiz, `company_id` zorunlu ve gerçekten var olan bir şirkete ait olmalı. `ContactController` henüz yok — bunu da bugün oluşturacağız (şu ana kadar sadece `Contact` modelimiz vardı, controller'ı yoktu).

---

## Kendini Test Et

1. `unique:contacts,email` kuralı PHP tarafında mı çalışıyor, yoksa veritabanına gidiyor mu?
2. `StoreContactRequest` kullanıldığında, validation kuralları `store()` metodunun içinde mi çalışıyor, yoksa öncesinde mi?
3. Validation başarısız olduğunda kullanıcı hangi mekanizma sayesinde formu **yeniden doldurmak zorunda kalmıyor**?
