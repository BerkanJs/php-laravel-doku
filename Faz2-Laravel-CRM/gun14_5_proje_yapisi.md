# Gün 14.5 — crm-app Proje Yapısı

> Ek gün — müfredatta yok, senin isteğinle eklendi. Aşağıdaki her şey bugün kurduğumuz projenin (`Laravel Framework 13.21.1`) birebir kendisi — dosyaların gerçek içeriği okunarak yazıldı.

---

## `public/index.php`

Tüm HTTP isteklerinin girdiği tek dosya. İçeriği (özetlenmiş):

```php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->handleRequest(Request::capture());
```

Üç iş yapar: (1) Composer autoloader'ı yükler, (2) `bootstrap/app.php`'yi çalıştırıp yapılandırılmış uygulama nesnesini alır, (3) o nesneye isteği işletir.

**Neden tek dosya:** Web sunucusu (Nginx/Apache) yalnızca `public/` klasörünü dışarıya açar; `app/`, `config/`, `.env` gibi klasörler URL ile hiç erişilemez. Bu, kod ve statik/erişilebilir dosyaların fiziksel olarak ayrılmasını sağlayan bir güvenlik sınırıdır (C#'taki `wwwroot/` ile aynı fikir). `index.php` kendi içinde iş mantığı barındırmaz, sadece `bootstrap/app.php`'ye yönlendirir — bu yüzden içeriği hep aynı kalır, proje büyüse de değişmez.

---

## `bootstrap/app.php`

Uygulamanın başlangıç yapılandırmasını tanımlayan dosya:

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // global middleware (Gün 23'te dolduracağız)
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // yakalanmamış hataların nasıl ele alınacağı (Gün 10'daki set_exception_handler'ın framework seviyesi)
    })->create();
```

Üç şeyi tanımlar: hangi route dosyalarının yükleneceği (`withRouting`), tüm isteklere uygulanacak middleware'ler (`withMiddleware`), yakalanmamış hataların nasıl ele alınacağı (`withExceptions`).

**Neden ayrı bir dosyada, neden `index.php`'nin içinde değil:** Bu üç ayar istek başına değil, **uygulama başına bir kere** geçerlidir — her HTTP isteğinde yeniden tanımlanmaları gereksiz iş yükü ve tutarsızlık riski oluşturur. `index.php` her istekte çalışır ama sadece `bootstrap/app.php`'yi çağırıp ondan dönen hazır `$app` nesnesini kullanır; kurulum mantığının kendisi `index.php`'de tekrarlanmaz. C#'taki karşılığı `Program.cs`'teki `builder.Services.AddX()`/`app.UseX()` çağrıları — onlar da uygulama ilk açılırken bir kere çalışır, her istekte değil.

**Laravel 11 öncesiyle farkı:** Laravel 10 ve öncesinde bu üç ayar tek dosyada değil, üç ayrı dosyaya dağılmıştı: `app/Http/Kernel.php` (middleware sırası + hangi route dosyalarının okunacağı), `app/Exceptions/Handler.php` (hata yönetimi). Laravel 11 bu üçünü `bootstrap/app.php` altında birleştirdi. `crm-app`'te bu yüzden `Kernel.php`/`Handler.php` dosyaları **yok**.

### `bootstrap/providers.php`

Hangi Service Provider'ların yükleneceğinin listesi:

```php
return [
    AppServiceProvider::class,
];
```

Yeni bir Service Provider oluşturduğunda (`php artisan make:provider`), otomatik olarak bu listeye eklenir.

### `bootstrap/cache/`

`packages.php` ve `services.php` — Laravel'in performans için ürettiği önbellek dosyaları. Elle düzenlenmez; bozulursa/eskirse `php artisan optimize:clear` ile silinip yeniden üretilir.

---

## `routes/web.php`

Tarayıcıdan gelen isteklerin hangi kodu çalıştıracağını tanımlar (session ve CSRF koruması otomatik uygulanır). Şu an içeriği:

```php
Route::get('/', function () {
    return view('welcome');
});
```

Tek satır: `/` adresine GET isteği gelirse `welcome` view'unu döndür. Gün 15'te buraya `companies` resource route'unu ekleyeceğiz.

## `routes/console.php`

Aynı fikrin Artisan CLI komutları için hâli. Şu an sadece hazır gelen `inspire` komutu var (`php artisan inspire` çalıştırıldığında bir alıntı yazdırır).

**Not:** Laravel 10 ve öncesinde varsayılan olarak bir de `routes/api.php` gelirdi. Laravel 11+'da bu dosya varsayılan gelmiyor — API route'larına ihtiyaç duyduğumuzda (Gün 28) `php artisan install:api` komutu ile eklenecek.

---

## `app/` klasörü

CRM'e özel yazacağımız kodun tamamı buraya girer. PSR-4 eşlemesi (`"App\\": "app/"`, Gün 9) sayesinde `App\` ile başlayan her class, bu klasördeki karşılık gelen dosya yolunda aranır.

### `app/Http/Controllers/Controller.php`

```php
abstract class Controller
{
    //
}
```

Boş bir temel class. Tüm controller'lar bundan `extends` edilir (Gün 16'da yazacağımız `CompanyController` gibi) — ortak bir üst tip sağlar, ileride tüm controller'ların paylaşacağı ortak bir metot eklemek istersen buraya eklenir.

### `app/Models/User.php`

Hazır gelen tek Eloquent modeli:

```php
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
```

`#[Fillable]`/`#[Hidden]` PHP 8 attribute'ları — hangi kolonların toplu atama (mass assignment) ile doldurulabileceğini ve hangilerinin JSON'a çevrilirken gizleneceğini tanımlar (Gün 19'da Eloquent'i işlerken tekrar geleceğiz). Faz2'de buraya `Company.php`, `Contact.php`, `Deal.php`, `Task.php` eklenecek.

### `app/Providers/AppServiceProvider.php`

```php
class AppServiceProvider extends ServiceProvider
{
    public function register(): void { }
    public function boot(): void { }
}
```

Gün 14'te gördüğümüz Service Provider. `register()` içine `$this->app->bind(...)` gibi container kayıtları yazılır; `boot()` içine, tüm servisler register edildikten sonra çalışması gereken kurulum kodu yazılır.

### Faz2 ilerledikçe `app/` altında oluşacaklar

`Http/Requests/` (Gün 22, form validation), `Http/Middleware/` (Gün 23), `Policies/` (Gün 25).

---

## `database/`

### `database/database.sqlite`

Gerçek veritabanı dosyası. MySQL değil **SQLite** kullanılıyor — modern Laravel'in yeni varsayılanı: dosya tabanlı, ayrı bir veritabanı sunucusu kurmaya gerek yok. Kurulum sırasında (`composer create-project`'in `post-create-project-cmd` script'i) otomatik oluşturuldu ve migrate edildi.

### `database/migrations/`

Üç hazır migration dosyası var:
- `0001_01_01_000000_create_users_table.php` — `users`, `password_reset_tokens`, `sessions` tabloları
- `0001_01_01_000001_create_cache_table.php` — `cache` tablosu
- `0001_01_01_000002_create_jobs_table.php` — `jobs` tablosu

`cache` ve `jobs` tablolarının var olma sebebi: `.env`'de `CACHE_STORE=database` ve `QUEUE_CONNECTION=database` ayarlı — yani cache ve queue verisi de veritabanında tutuluyor, bu yüzden onlara ait tablolar gerekiyor. Gün 18'de buraya `create_companies_table`, `create_contacts_table`, `create_deals_table`, `create_tasks_table` migration'larını ekleyeceğiz.

### `database/factories/UserFactory.php`, `database/seeders/DatabaseSeeder.php`

Test/örnek veri üretimi. `UserFactory`, sahte `User` kayıtları üretir (`User::factory()->create()`); `DatabaseSeeder`, `php artisan db:seed` çalıştırıldığında hangi verinin ekleneceğini tanımlar (şu an tek bir test kullanıcısı oluşturuyor). Gün 30'da test yazarken kullanacağız.

---

## `resources/`

- `resources/views/welcome.blade.php` — şu an tek Blade view. Gün 17'de kendi `layouts/app.blade.php` ve `companies/index.blade.php` gibi view'larımızı buraya ekleyeceğiz.
- `resources/css/app.css`, `resources/js/app.js` — Vite ile derlenecek ham CSS/JS kaynakları. Henüz `npm run build` çalıştırılmadığı için derlenmiş hâlleri yok.

---

## `config/`

Uygulama ayarları — C#'taki `appsettings.json` karşılığı. Her dosya bir alana ait bir PHP array'i döndürür, değerler genelde `.env`'den `env('DEĞİŞKEN', varsayılan)` ile okunur. En sık bakacakların:

- `config/database.php` — veritabanı bağlantıları. `'default' => env('DB_CONNECTION', 'sqlite')` satırı, `.env`'deki `DB_CONNECTION=sqlite` ile birleşince SQLite'ı varsayılan yapıyor.
- `config/session.php` — session driver'ı (`.env`'de `SESSION_DRIVER=database`).

Kalan dosyalar (`app.php`, `auth.php`, `cache.php`, `filesystems.php`, `logging.php`, `mail.php`, `queue.php`, `services.php`) ilgili gün geldiğinde (auth → Gün 24, filesystems → Gün 27, queue → Gün 29) tek tek açılacak. Genelde bu dosyaların kendisi değil, `.env`'deki değerler değiştirilir.

---

## Arka planda duran, elle nadiren dokunulan yerler

- **`storage/`** — çalışma zamanı verisi: `storage/app/` yüklenen dosyalar (Gün 27), `storage/framework/` Laravel'in kendi iç önbellekleri, `storage/logs/laravel.log` uygulama logları (Gün 10'daki `error_log`'un framework seviyesi). Laravel kendi yazıp okuyor, sen genelde sadece `logs/` klasörüne bakarsın.
- **`vendor/`** — Composer'ın indirdiği tüm paketler (Laravel'in kendisi dahil). Elle düzenlenmez, `composer.json`/`composer.lock` üzerinden yönetilir (Gün 9).
- **`tests/`** — `tests/Feature/ExampleTest.php` (gerçek HTTP+DB testi), `tests/Unit/ExampleTest.php` (framework'ten bağımsız saf mantık testi), `tests/TestCase.php` (tüm testlerin miras aldığı temel class). Gün 30'da buraya kendi testlerimizi ekleyeceğiz.
- **`.env`** — senin makinene özel gerçek değerler (`APP_KEY`, DB bağlantısı vb.) — git'e girmez. **`.env.example`** — aynı anahtarların değersiz şablonu — git'e girer, yeni bir geliştirici bunu kopyalayıp kendi `.env`'ini oluşturur.

---

## Kök dizindeki diğer dosyalar

| Dosya | Ne yapar |
|---|---|
| `artisan` | CLI giriş noktası — `php artisan ...` bu dosyayı çalıştırır (C#'taki `dotnet` CLI'ın kendisi) |
| `composer.json` | Bağımlılıklar + PSR-4 autoload haritası + kurulum script'leri (`.csproj` karşılığı) |
| `composer.lock` | Kilitli tam sürümler (`packages.lock.json` karşılığı) |
| `package.json` | Node.js tarafı bağımlılıklar (Vite, Tailwind) — frontend asset derleme için |
| `vite.config.js` | Vite (frontend build aracı) ayarları |
| `phpunit.xml` | PHPUnit test çalıştırma ayarları |
| `README.md` | Laravel'in kendi tanıtım dosyası |
| `.gitignore` | `vendor/`, `.env`, `storage/*.key` gibi git'e girmeyecek dosya/klasörlerin listesi |

---

## Bir HTTP isteğinin işlenme sırası (dosya dosya)

1. İstek `public/index.php`'ye ulaşır
2. `index.php`, `vendor/autoload.php`'yi yükler (Composer autoload)
3. `index.php`, `bootstrap/app.php`'yi çalıştırır — route/middleware/exception yapılandırması buradan okunur, `$app` nesnesi oluşur
4. `$app->handleRequest(...)` çağrılır — gelen isteğin URL'i ve metodu, `routes/web.php`'deki tanımlarla eşleştirilir
5. Eşleşen route'un controller'ı (`app/Http/Controllers/`) veya closure'ı çalıştırılır — bu adımda Service Container (Gün 14), controller'ın constructor'ındaki bağımlılıkları otomatik çözer
6. Controller, gerekiyorsa `app/Models/` üzerinden veritabanına erişir
7. Controller, `resources/views/` altındaki bir Blade dosyasını render eder (ya da JSON döner)
8. Response tarayıcıya döner

`php artisan` komutları aynı zinciri `artisan` dosyası üzerinden, adım 1'in yerini CLI komutu alarak izler.

---

## `.env`'de kurulumda otomatik ayarlanan, dikkat edilmesi gereken değerler

- `APP_KEY` — kurulumda otomatik üretildi, şifreleme ve session imzalama için kullanılır
- `DB_CONNECTION=sqlite` — MySQL değil SQLite
- `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database` — üçü de veritabanı tabanlı. Gün 1/2'de işlediğimiz kural burada da geçerli: PHP process'i kendi belleğinde hiçbir şeyi isteğe kalıcı saklayamaz, bu yüzden Laravel varsayılan olarak session/cache/queue'yu process dışı bir depoya (burada veritabanına) yazıyor.

---

## Kendini Test Et

1. `public/index.php` içeriği neden bu kadar kısa — asıl kurulum mantığı hangi dosyada?
2. `bootstrap/app.php` olmasaydı, her HTTP isteğinde hangi işleri elle tekrar yapman gerekirdi?
3. Laravel 11 öncesinde `bootstrap/app.php`'nin işini hangi dosyalar yapıyordu?
4. `database/migrations/`'da `cache` ve `jobs` tabloları neden var — bunlar CRM verisiyle ilgili değil, öyleyse ne işe yarıyorlar?
