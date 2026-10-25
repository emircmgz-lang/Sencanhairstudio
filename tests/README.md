# Yerel doğrulama

Bu test canlı hosting veya gerçek müşteri veritabanında çalıştırılmaz. Docker'da ayrı PHP 8.3 ve MariaDB 11.4 konteynerleriyle doğrulanmıştır.

`Dockerfile`, PHP CLI ve PDO MySQL eklentili test imajıdır. `integration.cjs`, localhost:18083 üzerinde çalışan dört PHP test işçisine gerçek HTTP istekleri yapar. Node.js yalnız bu geliştirici test dosyası için kullanılır; uygulamanın hostingde çalışması için gerekmez.

Test konteyner adları `sencan-php-web` ve `sencan-mysql-test`; test veritabanları `sencan_test` ve geri yükleme için `sencan_restore` olmalıdır. Test için boş veritabanı ve `private/config.php` içinde yerel deneme ayarları gerekir. Test kodundaki parola ve kurulum anahtarı yalnız bu ayrı test ortamı içindir; gerçek config.php'ye kopyalanmamalıdır.

Entegrasyon testi yalnız yeni, boş test veritabanında başlar; tekrar koşulurken test veritabanları temizlenmelidir. MariaDB root erişimi yalnız izole test ağı içindedir, dış porta açılmaz. PHP test konteynerine paket `/app` konumunda bağlanır, `php -S 0.0.0.0:8080 -t /app/public_html` komutuyla başlatılır ve dışarı yalnız `127.0.0.1:18083` portuna bağlanır. `PHP_CLI_SERVER_WORKERS=4` ile eşzamanlı istekler sınanır. Testte gerçek e-posta gönderilmez.

Kontroller: kurulumu kilitleme, eşzamanlı saat çakışması, diğer istemciden sorgu, sunucuda fiyat doğrulaması, idempotency, saat değiştirme, CSRF, fotoğraf ve izinler, ortak işletme ayarları, iptal, SQL yedeği ve geri yükleme, parola değişimi, süreç yeniden başlatmasında kalıcılık, özel dosyaların web kökünden ayrılması ve Cron.
