# Yorum sistemi + cron'suz otomatik döngü

## Yükleme
Değişen/yeni dosyaları aynı yerlerine yükleyin (eski dosyaları silmeyin). SQL çalıştırmanız gerekmez: ilk API isteğinde `private/migrate.php` yeni tabloları ve `ip_hash` sütununu kendisi ekler. `private/config.php` değişmez.

Yeni: `private/migrate.php`, `private/tick.php`, `private/reviews.php`, `public_html/reviews.js|css`, `tick.js`, `admin-reviews.js`.
Değişen: `api/index.php`, `index.html`, `admin.html`, `admin.js`, `server-admin.js`, `private/schema.sql`, `backup.php`, `mail.php`, `gizlilik-politikasi.html`.

## Yorumlar
- Ana sayfada menüde **Yorumlar** bağlantısı ve sayfa altında yorum listesi vardır; herkes okur.
- Yazma formu yalnızca sunucu şunu doğrularsa görünür: aynı IP'den alınmış, **başlangıç saati geçmiş**, iptal edilmemiş ve henüz yorumlanmamış randevu (son 30 gün). Her randevu için tek yorum.
- IP düz saklanmaz, sunucuya özel tuzlu HMAC özeti saklanır (IPv6'da /64 öneki). Gizlilik metnine eklendi.
- Admin panelinde **Yorumlar** sekmesi: isim, puan, metin düzenleme; sitede gizle/yayınla.
- Cloudflare vb. arkasındaysanız `config.php` içine `'ip_header' => 'HTTP_CF_CONNECTING_IP'` ekleyin. Süre için `'review_window_days' => 30`.

**Sınır:** IP ile doğrulama kesin kimlik değildir. Ortak Wi-Fi/operatör IP'sinde başkası yazabilir; müşteri randevudan sonra farklı ağa geçerse (Wi-Fi → mobil veri) formu göremez.

## Cron'suz döngü
`tick.js` (site ve panel açıkken dakikada bir) ve her API isteğinin sonu sunucuyu dürter. Sunucu işi en fazla ~55 sn'de bir, kilitle tek seferde yapar: mail kuyruğu + tekrar denemeler (8 deneme, 2→30 dk aralık), günlük yedek, eski oturum/limit temizliği. Durum admin → İşletme Ayarları → Güvenlik'te görünür.
Hiç ziyaretçi/panel yoksa döngü de çalışmaz. İsterseniz ücretsiz bir uptime servisinden `https://alanadiniz.com/api/tick` adresine dakikada bir GET attırabilirsiniz.
`private/cron.php` durur; ileride cron açılırsa aynı kilidi kullanır.
