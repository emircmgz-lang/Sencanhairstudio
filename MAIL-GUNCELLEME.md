# Çalışana özel e-posta güncellemesi

## Yönetici
/admin.html → Çalışanlar → ilgili çalışan → Randevu bildirimi e-posta adresi → Değişiklikleri kaydet.
Yeni randevu yalnız seçilen çalışanın adresine gider. Müşteriye e-posta gönderilmez. Alan boşsa bildirim yoktur. Adresler müşteri config yanıtında yer almaz. Kuyruk işlenirken çalışanın en güncel kayıtlı adresi kullanılır. Kaydetme işlemi deneme e-postası göndermez.

## Hostingde bir defalık bağlantı
Mevcut gerçek private/config.php DOSYASINI KORUYUN. Veritabanını veya kurulumu sıfırlamayın. Şema değişikliği gerekmez; çalışan e-posta adresleri mevcut işletme JSON kaydında saklanır.

Bu sürüm Resend API kullanır, Hostinger SMTP şifresiyle çalışmaz. Gönderim hesabının sahibi Resend üzerinde sencanhairstudio.com alan adını doğrulamalı; hizmetin verdiği DNS kayıtlarını eklemeli. Mevcut mailbox MX kayıtlarını silmeyin. SPF/DKIM/DMARC yapılandırmasını mevcut e-posta hizmetiyle uyumlu tutun; birden fazla SPF kaydı oluşturmayın.

private/config.php içindeki alanlar:

```php
'mail_enabled' => true,
'resend_api_key' => 'SUNUCUDA_GIZLI_GONDERIM_ANAHTARI',
'mail_from' => 'Davut Şencan Hair Studio <info@sencanhairstudio.com>',
```

Gerçek anahtar GitHub'a veya sohbete yazılmaz. mail_to alanı artık kullanılmaz. PHP cURL ve dışarı HTTPS erişimi gerekir. Anahtar ve sender doğrulanmadan mail_enabled false kalsın. Her dakika PHP CLI ile private/cron.php çalıştırılmalı. Git çekiminden sonra deploy/publish.sh otomatik çalıştırılmalı (deploy yönergesine bakın). Daha sonra çalışan adresleri için sunucu dosyası düzenlenmez.

Mevcut sunucuda gerçek config.php varsa örnek/hazır config ile üzerine yazmayın; yalnız yukarıdaki mail alanlarını güncelleyin. İlk kurulum henüz yapılmadıysa README kurulum adımları geçerlidir.

## Kontrol
Admin panelinde bir çalışana kontrol ettiğiniz gerçek adresi kaydedin, o çalışana yeni test randevusu oluşturun. Cron sonrası mail kuyruğu durumunu ve alıcı posta kutusunu kontrol edin. Test randevusunu sonra iptal edin. Gönderim hizmetine kabul, gelen kutusu garantisi değildir.

Otomatik test: php tests/staff-email.php. Gerçek e-posta göndermez. Alıcı ayrımı, adres güncelleme/silme, doğrulama ve gizlilik kontrol edilir. Hosting üzerinde uçtan uca gerçek gönderim bu paket hazırlanırken yapılmadı.
