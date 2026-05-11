# Borsa Pulse

Saf PHP ile geliştirilen, BIST hisseleri için tarama, teknik analiz, alarm ve dashboard altyapısı sağlayan modüler bir platform iskeleti.

## Öne çıkanlar

- Native PHP 8.3+ bootstrap ve özel autoload yapısı
- Dosya tabanlı cache, rate limit, retry ve timeout desteğine sahip HTTP katmanı
- Teknik analiz motoru: RSI, SMA, EMA, MACD, Bollinger Bands, ATR, Fibonacci
- Tarama modülleri: low-float, 12 aylık dip analizi, hacim patlaması, formasyon sıkışması
- Akıllı skor motoru ve rule-based yorum üretimi
- Telegram, Discord, Email ve browser alarm kanalları
- API endpointleri, cron girişleri, public dashboard ve MySQL şeması

## Klasör yapısı

- `/classes` çekirdek uygulama sınıfları
- `/classes/Scanners` tarama motorları
- `/classes/Indicators` teknik analiz hesaplamaları
- `/classes/Services` servisler ve platform orkestrasyonu
- `/classes/Alerts` alarm kanalları
- `/classes/Cache` cache sürücüleri
- `/api` JSON endpointleri
- `/cron` cPanel cron giriş dosyaları
- `/config` uygulama konfigürasyonları
- `/storage` cache, log ve SQL şeması
- `/public` dashboard giriş noktası
- `/assets/js` istemci tarafı scriptleri

## Kurulum

1. `<project_root>/.env.example` dosyasını `.env` olarak kopyalayın.
2. Telegram/Discord/email ve veritabanı ayarlarını doldurun.
3. `storage/schema.sql` içeriğini MySQL/MariaDB üzerinde çalıştırın.
4. Web kökünü mümkünse `public/` dizinine yönlendirin.
5. Shared hosting kullanıyorsanız kök dizindeki `index.php` dosyası `public/index.php` için köprü görevi görür.

## API

- `GET /api/market.php?limit=5` : skorlu piyasa özeti
- `GET /api/market.php?symbol=BIST:ASELS` : tek hisse analizi

## Cron örnekleri

```bash
php <project_root>/cron/scan.php
php <project_root>/cron/alerts.php
php <project_root>/cron/refresh_data.php
```

## Güvenlik notları

- Gizli anahtarlar `.env` üzerinden yüklenir.
- PDO prepared statements için örnek repository katmanı eklendi.
- Session, CSRF ve XSS yardımcıları bootstrap altında hazırlandı.


## Türkçe kullanım kılavuzu

- Ayrıntılı Türkçe kurulum ve kullanım anlatımı için `<project_root>/readme.txt` dosyasına bakın.
- Kök erişim için `<project_root>/index.php` eklendi; `public/index.php` panelini yükler.
