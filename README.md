# PHP & Laravel Müfredatı

> C#/.NET deneyimli bir geliştiricinin PHP dilini ve Laravel framework'ünü öğrenme sürecinin günlük kayıtları.

**Hedef:** 5 haftada PHP dilini ve Laravel'in temel işleyişini kavramış, yeni işe pratik olarak hazır geliştirici olmak.

**Yöntem:** Her konu önce C#'taki karşılığıyla kıyaslanır (bilinen zihin modeli üzerinden hızlı öğrenim), sonra PHP/Laravel tarafı gerçek kodla gösterilir ve mümkün olduğunca canlı olarak test edilir.

## Kapsam Dışı

Bu bilinçli bir seçim — aşağıdakiler bu müfredatın konusu değil:

- SOLID, GoF Design Patterns, DDD, CQRS
- Mikroservisler, Docker/K8s, message broker
- Performans/ölçek mühendisliği, "en iyi mimari ne" soruları

Amaç tek şey: **PHP dilinin ve Laravel'in nasıl çalıştığını** anlamak.

## Yapı

```
PhpLaravelDoku/
├── Faz1-PHP-Dili/              Hafta 1-2: PHP dili temelleri
│   ├── 01-CalismaModeli/       PHP çalışma modeli, superglobals
│   ├── 02-TipSistemi/          değişkenler, type juggling, string/regex
│   ├── 03-Fonksiyonlar/        closures, arrow function, scope
│   ├── 04-Diziler/             array fonksiyonları, indexed/associative
│   ├── 05-OOP/                 class, interface, trait, magic methods
│   └── 06-ModernPHP/           namespace, Composer, PHP 8.x özellikleri
│
├── Faz2-Laravel-CRM/           Hafta 3-5: Laravel (tek büyüyen proje)
│   ├── gun14_*.md ... gun30_*.md   Günlük ders notları
│   └── crm-app/                Gerçek Laravel projesi (Mini CRM)
│
└── müfredat.md                 Tüm 5 haftalık planın kaynağı
```

Her klasördeki `.md` dosyası bir günün dersini, yanındaki `.php`/proje dosyaları o günün çalışan kodunu içerir.

## Örnek Proje: Mini CRM

Faz 2 boyunca tek bir Laravel uygulaması kademeli olarak büyür.

**Domain:** Şirketler (Company) → Kişiler (Contact) → Fırsatlar (Deal), kullanıcı rolleri (admin / satış temsilcisi)

| Aşama | Eklenenler |
|-------|-----------|
| Başlangıç | Routing, controller, Blade ile listeleme |
| Orta | Migration + Eloquent CRUD, ilişkiler |
| İleri | Form validation, authentication, authorization (sadece admin siler) |
| Final | Dosya yükleme, API resource, queue/job, feature test |

## İlerleme

| Faz | Konu | Durum |
|-----|------|-------|
| Faz 1 | PHP Dili (Gün 1-12) | ✅ Tamamlandı |
| Faz 2 | Laravel + CRM (Gün 14-30) | ✅ Tamamlandı |

Özet günleri (Gün 7, 13, 20, 26, 31) bilinçli olarak atlandı — her hafta sonunda tekrar ders yazılmadı.

### Faz 2 Gün Akışı

| Gün | Konu |
|-----|------|
| 14 | Laravel Nedir? Service Container / Artisan |
| 14.5 | Proje yapısı (dosya haritası) |
| 15 | Routing |
| 16 | Controllers / Request-Response |
| 17 | Blade Templating |
| 18 | Migrations |
| 19 | Eloquent Temelleri |
| 21 | Eloquent İlişkiler |
| 22 | Form Request Validation |
| 23 | Middleware |
| 24 | Authentication (Breeze) |
| 25 | Authorization (Gates & Policies) |
| 27 | File Upload & Storage |
| 28 | API Resources & JSON Response |
| 29 | Queue & Jobs |
| 30 | Test Temelleri (PHPUnit/Pest) |

## Ortamı Kurup Çalıştırmak

- **PHP:** 8.3.30
- **Composer:** 2.9.4
- **Node.js:** 24.18.0 (Breeze'in asset build adımı için — Laravel 13 + Vite/rolldown en az `20.19.0` gerektirir)

```bash
cd Faz2-Laravel-CRM/crm-app
composer install
npm install && npm run build
php artisan migrate
php artisan storage:link
php artisan serve
```

Testleri çalıştırmak için:

```bash
php artisan test
```

## Neden Bu Repo

Bu, bir kursun bittiğinde silinen ders notları değil — her gün gerçek kodla, curl/tinker ile canlı doğrulanmış, C# zihin modeli üzerinden kıyaslanmış bir öğrenim günlüğü. Amaç ileride "bu neden böyle çalışıyordu" sorusuna dönüp bakılabilecek bir kaynak bırakmak.
