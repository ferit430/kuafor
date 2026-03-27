# Berber & Kuaför Randevu Sistemi Kurulum Kılavuzu (Güncellendi)

Sistem artık MacOS görünümü (glassmorphism), Türkçe dil desteği ve Salon Sahibi Paneli ile tam donanımlıdır.

## 1. Veritabanı Kurulumu
1. [sql/database_schema.sql](file:///c:/xampp/htdocs/Berberim/sql/database_schema.sql) dosyasını phpMyAdmin üzerinden içe aktarın.

## 2. Admin Paneli (Sistem Yöneticisi)
1. URL: `http://localhost/Berberim/admin/`
2. Şifre: **1**
3. Bu panelden yeni kayıt olan salonları onaylayabilir veya reddedebilirsiniz.

## 3. Salon Sahibi Paneli (İşletme Yönetimi)
1. URL: `http://localhost/Berberim/salon/login.php`
2. **Not**: Önce `users` tablosuna bir `salon_owner` rolünde kullanıcı eklemelisiniz.
3. Bu panelden Hizmetlerinizi, Personelinizi ve Randevularınızı MacOS şıklığında yönetebilirsiniz.

## 4. Flutter Mobil Uygulama
1. Uygulama tamamen Türkçeleştirilmiştir.
2. `mobile/` dizininde `flutter run` ile başlatabilirsiniz.
3. Tasarım Material 3 Siyah + Altın (Luxury) konseptindedir.

## 5. Tasarım Notları
- **Glassmorphism**: Tüm web panelleri MacOS benzeri bulanık cam efektiyle (backdrop-filter) tasarlanmıştır.
- **Tipografi**: 'Inter' ve Sistem fontları kullanılarak Apple standartlarında bir okuma deneyimi sunulmuştur.
