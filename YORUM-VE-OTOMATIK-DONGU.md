# Güncelleme notları (yorumlar, galeri, duyuru, takvim, cron'suz döngü)

## Yükleme
Dosyaları aynı yerlerine yükleyin, eskileri silmeyin. SQL gerekmez: ilk API isteğinde `private/migrate.php` yeni tabloları (`sc_reviews`, `sc_gallery`, `sc_kv`) kendisi oluşturur. `config.php` değişmez.
Yeni: `private/migrate.php tick.php reviews.php gallery.php`, `public_html/reviews.js|css gallery.js|css calendar.js tick.js admin-reviews.js admin-gallery.js`.

## Yorumlar
- Girişin hemen altında son 3 yorum + "Tüm yorumları gör" / "Yorum yaz" / "Google'da puan ver" düğmeleri. Tüm yorumlar pencerede açılır, usta filtresiyle.
- Herkes yazabilir (randevu/saat/IP şartı yok). Ad, usta (isteğe bağlı), puan, metin. Bağlantı içeren yorum reddedilir, gizli bot tuzağı + IP başına saatte 5 yorum sınırı var.
- Çalışan bazlı puan: usta kartında "★ 4,8 · 12 yorum"; tıklayınca o ustanın yorumları açılır.
- Admin → Yorumlar: isim, usta, puan, metin düzenleme; gizle/yayınla.
- Google bağlantısı: Admin → İşletme Ayarları → "Google yorum bağlantısı".

## Galeri
- Menüde "Bize ulaşın" yerine "Galeri". Bölümde ilk 6 görsel, "Tüm galeriyi gör" ile devamı, tıklayınca büyür (kaydırma/ok tuşları). Görsel yoksa bölüm ve menü bağlantısı gizlenir.
- "Bize ulaşın" (adres/yol tarifi) bölümü galerinin altında duruyor, footer bağlantısı da.
- Admin → Galeri: çoklu görsel yükle (JPG/JPEG/PNG/WebP, otomatik küçültülür), açıklama, ↑↓ sıralama, sil. En fazla 40.

## Duyuru
Admin → İşletme Ayarları → "Duyuru (tatil / kapalı gün)". Doluysa sayfanın en üstünde bant olarak görünür; isteğe bağlı son gün geçince kalkar.

## Takvime ekle
Randevu onay ekranında ve "Randevumu sorgula" kartında: Google Takvim + .ics (iPhone/Outlook). Randevudan 1 saat önce hatırlatma içerir.

## Usta fotoğrafı
Fotoğraf artık 4:3 kırpılıp iç oval çerçevenin tam içine oturuyor. Mevcut ustaların fotoğrafını panelden bir kez yeniden yükleyin (eski kayıtlar düşük çözünürlüklü kare). JPG/JPEG artık her yerde kabul edilir.

## Cron'suz döngü
`tick.js` + her API isteğinin sonu sunucuyu dürter; mail kuyruğu/tekrar deneme, günlük yedek ve temizlik ~55 sn'de bir, kilitle tek seferde çalışır. Ziyaretçi/panel yoksa çalışmaz; isterseniz ücretsiz uptime servisinden `https://alanadiniz.com/api/tick` adresine dakikada bir GET attırın.
