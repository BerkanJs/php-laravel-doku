# Gün 14 — Laravel Nedir? Service Container ve Artisan

## Bu Derste İlk Kez Göreceğin Syntax

| Yeni syntax | Örnek | Açıklama |
|-------------|-------|----------|
| Artisan CLI | `php artisan make:controller X` | Laravel'in kod iskeleti üretme komutu |
| Service binding | `$this->app->bind(X::class, Y::class);` | Container'a "X istenirse Y ver" talimatı |
| Singleton binding | `$this->app->singleton(X::class, ...);` | Container'a "X'ten sadece TEK instance olsun" talimatı |

---

## Senaryo

CRM'de bir `CompanyController`'ın veritabanına erişmesi için bir `CompanyRepository`'ye ihtiyacı var. Constructor'a şöyle yazıyorsun:

```php
class CompanyRepository {
    public function __construct(private DatabaseConnection $db) {}
}

class CompanyController {
    public function __construct(private CompanyRepository $repo) {}

    public function index() {
        // $this->repo burada hazır ve kullanılabilir
    }
}
```

Dikkat: **hiçbir yerde** `new CompanyRepository(new DatabaseConnection(...))` yazmadık. `CompanyController` çağrıldığında Laravel, `CompanyRepository`'ye ihtiyacı olduğunu görür, `CompanyRepository`'nin de `DatabaseConnection`'a ihtiyacı olduğunu görür, ikisini de otomatik inşa eder ve constructor'lara sırayla enjekte eder.

Bunun ASP.NET Core'daki `IServiceCollection`/constructor injection ile aynı fikir olduğunu zaten biliyorsun. Ama önemli bir fark var: ASP.NET Core'da (birkaç framework istisnası dışında) **her concrete class'ı elle register etmen gerekir** — `builder.Services.AddScoped<CompanyRepository>();` yazmazsan, container onu resolve edemez, `InvalidOperationException` alırsın. Laravel'de ise `CompanyRepository` gibi somut (concrete) bir class, **hiç register edilmeden** otomatik çözülür — Laravel, PHP'nin Reflection API'sini kullanarak constructor'ın tip ipuçlarına (`private DatabaseConnection $db` gibi) bakar ve zinciri kendi kendine kurar.

Register etmen gereken tek durum: bir **interface**'in hangi concrete class ile karşılanacağını belirtmek (çünkü interface'in kendisi instantiate edilemez, Laravel hangi implementasyonu seçeceğini bilemez) — bu iş **Service Provider**'larda yapılır.

---

## Teorik

### Laravel nedir

Laravel, ASP.NET Core'un "batteries included" felsefesinin PHP karşılığı — routing, ORM (Eloquent), templating (Blade) ve bir DI container hepsi tek framework içinde, birbirine entegre gelir.

### Service Container (IoC Container)

Laravel'in kalbi budur — controller'lar, job'lar, middleware'ler dahil hemen hemen her şey bu container üzerinden **çözülür (resolve edilir)**. Yukarıdaki senaryoda gördüğümüz otomatik constructor injection, bu container'ın işidir.

### Service Provider

Container'a "X istenirse Y ver" gibi kuralları **kaydeden (register eden)** class'lardır — uygulama başlarken çalışırlar:

```php
class RepositoryServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->bind(
            CompanyRepositoryInterface::class,   // interface istenirse
            EloquentCompanyRepository::class      // bu concrete class'ı ver
        );
    }
}
```

C#'taki karşılığı `Program.cs` içindeki `builder.Services.AddScoped<ICompanyRepository, EloquentCompanyRepository>();` satırlarıdır — farkı, Laravel'de her paketin (hatta senin kendi kodunun) kendi Service Provider'ını taşıyabilmesi ve bunların otomatik keşfedilmesidir.

### Artisan CLI

C#'taki `dotnet` CLI'ın (`dotnet new`, `dotnet ef migrations add`) karşılığı — kod iskeleti üretir, veritabanı işlemleri yapar:

```
php artisan make:controller CompanyController
php artisan make:model Company -m       // -m: migration dosyasi da uretir
php artisan migrate
php artisan route:list
php artisan tinker                      // interaktif REPL - container'i canli test etmek icin ideal
```

### Kurulum

```
composer create-project laravel/laravel crm-app
```

Bu komut hem Laravel'in kendisini hem tüm bağımlılıklarını indirir, `.env.example`'ı `.env`'e kopyalar ve otomatik bir uygulama anahtarı (`APP_KEY`) üretir.

### Lifetime: Laravel'de varsayılan davranış

Laravel'de container'dan çözülen bir servis, varsayılan olarak **her istekte yeni bir instance**'tır — bu C#'taki **Scoped** lifetime'a en yakın davranış. **Singleton** istiyorsan bunu elle belirtmen gerekir:

```php
$this->app->singleton(ConfigCache::class, function ($app) {
    return new ConfigCache();
});
```

> Hatırlatma (Gün 1'den): Laravel'de bir servisi Singleton yapmak, C#'takinden **daha riskli** olabilir — çünkü PHP-FPM'de her HTTP isteği ayrı bir worker/process'te çalışır. C#'ta singleton, uygulama açık kaldığı sürece (uzun ömürlü process) güvenle paylaşılan state tutabilir. PHP'de ise "singleton" dediğin şey aslında sadece **tek bir istek içinde** tekil kalır — bir sonraki istekte yeniden oluşturulur. Eğer singleton'ın içine "isteğe özel olmayan, kalıcı olması gereken" bir veri koyarsan (örn. bir sayaç), Gün 1'deki hatayı burada da yaparsın; kalıcı veri yine Redis/DB'de olmalı.

---

## C# ile Karşılaştırma

| Konu | C#/ASP.NET Core | Laravel |
|------|------------------|---------|
| Container | `IServiceCollection`/`IServiceProvider` | Service Container |
| Concrete class resolve | Elle register edilmesi ZORUNLU (yoksa exception) | Reflection ile OTOMATİK resolve edilir, register gerekmez |
| Interface binding | `AddScoped<IX, Y>()` | Service Provider içinde `$this->app->bind(X::class, Y::class)` |
| Servis kaydı yeri | `Program.cs` | Service Provider class'ları (paket başına ayrı, otomatik keşif) |
| CLI aracı | `dotnet` | `php artisan` |
| Varsayılan lifetime | Transient (belirtilmezse) | Fiilen isteğe özel (Scoped'a en yakın) |
| Singleton riski | Düşük (process uzun yaşar) | Yüksek — Gün 1'deki process modeli nedeniyle "kalıcı" sanılan veri her istekte sıfırlanır |

---

## Kod ile Göster — Gerçek Proje Kurulumu

Bugün ilk kez **gerçek bir Laravel projesi** kuruyoruz — bundan sonraki tüm günler bu projeye (`Faz2-Laravel-CRM/crm-app/`) kod ekleyerek ilerleyecek.

```
cd Faz2-Laravel-CRM
composer create-project laravel/laravel crm-app
cd crm-app
php artisan --version
```

### Container'ı canlı gözlemleme: `php artisan tinker`

Tinker, Laravel uygulamasının içinde çalışan interaktif bir PHP konsoludur (REPL) — container'ın otomatik resolve davranışını canlı görmek için ideal:

```php
// tinker icinde:
class Ornek {
    public function __construct() {
        echo "Ornek olusturuldu\n";
    }
}

app(Ornek::class);   // "Ornek olusturuldu" yazdirir - HIC register etmeden, container class'i kendi kurdu
```

`Faz2-Laravel-CRM/crm-app/` kurulduktan sonra bu klasördeki gerçek dosyalarla (özellikle `app/Providers/AppServiceProvider.php`) devam edeceğiz.

---

## Kendini Test Et

1. Laravel'de bir servisi Singleton yapmak neden C#'takinden daha riskli olabilir? (Gün 1'deki process model ile bağlantısını kur)
