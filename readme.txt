BORSA PULSE - TÜRKÇE KULLANIM KILAVUZU
======================================

1) PROJE NE YAPIYOR?
--------------------
Bu proje BIST hisseleri için:
- veri toplar,
- teknik analiz hesaplar,
- tarama skorları üretir,
- alarm gönderebilir,
- web panelinde sonuçları gösterir.

Ana giriş dosyaları:
- <project_root>/index.php
- <project_root>/public/index.php
- <project_root>/api/market.php
- <project_root>/cron/scan.php
- <project_root>/cron/alerts.php
- <project_root>/cron/refresh_data.php

Not:
Kök dizinde index.php eklendi. Böylece shared hosting veya cPanel üzerinde alan adını doğrudan proje köküne yönlendirseniz bile panel açılabilir. Eğer imkan varsa yine de en doğru yöntem public klasörünü web root yapmaktır.


2) KLASÖR YAPISI KISA AÇIKLAMA
------------------------------
- <project_root>/classes
  Uygulamanın PHP sınıfları burada yer alır.
- <project_root>/config
  Ortam ve uygulama ayarları burada tutulur.
- <project_root>/api
  JSON çıktı veren API dosyaları.
- <project_root>/cron
  Cron ile çalıştırılacak görev dosyaları.
- <project_root>/public
  Tarayıcıdan açılacak panel dosyaları.
- <project_root>/assets/js
  Dashboard javascript dosyaları.
- <project_root>/storage
  Cache, log ve SQL şeması.


3) KURULUM ADIMLARI
-------------------
Adım 1:
<project_root>/.env.example dosyasını kopyalayın ve adını .env yapın.

Adım 2:
.env içinde aşağıdaki alanları doldurun:
- APP_ENV
- APP_DEBUG
- APP_TIMEZONE
- TELEGRAM_BOT_TOKEN
- TELEGRAM_CHAT_ID
- DISCORD_WEBHOOK_URL
- ALERT_EMAIL_TO
- ALERT_EMAIL_FROM
- DB_HOST
- DB_PORT
- DB_DATABASE
- DB_USERNAME
- DB_PASSWORD
- FINNHUB_TOKEN

Adım 3:
Veritabanı gerekiyorsa <project_root>/storage/schema.sql dosyasını MySQL/MariaDB üzerinde çalıştırın.

Adım 4:
Sunucuda PHP 8.3+ ve cURL uzantısının açık olduğundan emin olun.

Adım 5:
Web erişimi için iki seçenek vardır:
- Önerilen: domain veya subdomain kökünü <project_root>/public klasörüne yönlendirin.
- Alternatif: domain kökünü proje klasörüne yönlendirin. Bu durumda <project_root>/index.php devreye girer.


4) PANELİ NASIL AÇARIM?
-----------------------
Tarayıcıdan şu yollardan biri açılır:
- https://alanadiniz.com/
- https://alanadiniz.com/index.php
- Eğer public ayrı root ise doğrudan public içeriği açılır.

Panel dosyası:
- <project_root>/public/index.php

Bu panel şunları gösterir:
- genel özet kartları
- tarama sonuçları
- skorlar
- yorumlar
- AJAX ile periyodik yenileme


5) API KULLANIMI
----------------
Tek hisse analizi:
GET /api/market.php?symbol=BIST:ASELS

Örnek:
https://alanadiniz.com/api/market.php?symbol=BIST:ASELS

Piyasa özeti:
GET /api/market.php?limit=5

Örnek:
https://alanadiniz.com/api/market.php?limit=10

API çıktısında temel olarak şunlar gelir:
- snapshot
- candles
- indicators
- scanners
- scores
- comments


6) CRON GÖREVLERİ
-----------------
Aşağıdaki dosyalar cron için hazırlanmıştır:

1. Tarama çalıştır:
php <project_root>/cron/scan.php

2. Alarm kontrolü yap:
php <project_root>/cron/alerts.php

3. Veri/cache yenile:
php <project_root>/cron/refresh_data.php

Örnek cPanel cron:
*/5 * * * * /usr/local/bin/php <project_root>/cron/scan.php
*/10 * * * * /usr/local/bin/php <project_root>/cron/alerts.php
0 * * * * /usr/local/bin/php <project_root>/cron/refresh_data.php


7) ESKİ SCRIPTLER NE İŞE YARAR?
-------------------------------
- <project_root>/hisse-isim-cek.php
  Piyasayı analiz eder ve çıktılarını dosyalara yazar.

- <project_root>/bot.php
  Oluşan grafik/alarm akışını bildirime dönüştürmek için kullanılır.

- <project_root>/chart.js
  Puppeteer tabanlı ekran görüntüsü/grafik üretimi için yardımcı script.

Not:
Yeni yapıda ana kullanım merkezi artık Platform servisleri, API ve dashboard tarafıdır.
Eski scriptler geriye dönük yardımcı giriş noktaları gibi düşünülebilir.


8) SIK KARŞILAŞILAN DURUMLAR
----------------------------
Sorun: index.php yoktu, site açılmıyordu.
Cevap: Artık <project_root>/index.php var. Kökten açılış için kullanılabilir.

Sorun: Veri sağlayıcıya erişilemiyor.
Cevap: Sistem bazı durumlarda fallback veri ile çalışabilir. Yine de gerçek kullanımda dış servis erişimleri kontrol edilmelidir.

Sorun: Telegram mesajı gitmiyor.
Cevap: .env içindeki TELEGRAM_BOT_TOKEN ve TELEGRAM_CHAT_ID alanlarını kontrol edin.

Sorun: API hata veriyor.
Cevap: PHP cURL, dosya izinleri, storage/logs klasörü ve .env ayarlarını kontrol edin.


9) BU PROJEDE DAHA NELER GELİŞTİRİLEBİLİR?
------------------------------------------
Öncelikli geliştirme önerileri:
- Gerçek OHLCV veri sağlayıcı adapterlarını tamamlamak
- Watchlist ve portfolio için kullanıcı paneli eklemek
- Hisse detay sayfası ve candle chart alanı eklemek
- TradingView Lightweight Charts entegrasyonunu tamamlamak
- Alarm tekrar kontrolü, cooldown ve rate limit mantığını güçlendirmek
- Gerçek kullanıcı giriş sistemi eklemek
- Scanner sonuçlarını veritabanına kalıcı yazmak
- Teknik göstergeler için test senaryoları eklemek
- API rate limit ve audit log altyapısını güçlendirmek
- Admin paneli ve ayar ekranı hazırlamak
- KAP ve bilanço verilerini sisteme bağlamak
- Heatmap ve sektör rotasyonu modülü eklemek
- Mobil uyumlu sade ekran ve premium üyelik katmanı oluşturmak


10) HIZLI BAŞLANGIÇ
-------------------
En hızlı kullanım sırası:
1. .env oluştur
2. Gerekirse schema.sql çalıştır
3. Tarayıcıdan /index.php aç
4. API için /api/market.php?limit=5 dene
5. Cron görevlerini sunucuya ekle
6. Telegram/Discord ayarlarını test et


11) GELİŞTİRİCİ NOTU
--------------------
Kodun çekirdeği artık bootstrap.php üzerinden açılır.
Yeni servis eklerken doğrudan dağınık script yazmak yerine classes altında sınıf eklemek daha doğru yaklaşımdır.
