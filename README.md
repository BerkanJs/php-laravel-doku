# PHP & Laravel Öğrenim Notları

Yeni başlayanlar için adım adım PHP ve Laravel dokümantasyonu. Her gün bir konuyu işler, örnek kodla gösterir.

## İçerik

- **Faz1-PHP-Dili/** — PHP'nin temelleri: değişkenler, fonksiyonlar, diziler, OOP, modern PHP (Composer, namespace, PHP 8.x)
- **Faz2-Laravel-CRM/** — Laravel temelleri: routing, Eloquent, auth, dosya yükleme, API, test — hepsi tek bir Mini CRM projesi üzerinde

Her konunun `.md` ders notu, yanında da çalışan örnek kod bulunur.

## Örnek Proje: Mini CRM

Faz2'deki Laravel projesi (`Faz2-Laravel-CRM/crm-app`), Şirket → Kişi → Fırsat ilişkisiyle basit bir CRM'dir.

```bash
cd Faz2-Laravel-CRM/crm-app
composer install
npm install && npm run build
php artisan migrate
php artisan serve
```
