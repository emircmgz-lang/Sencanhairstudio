# Şifresiz SMTP güncellemesi

Bu paket son tasarım, gerçek PHP/MySQL randevu sistemi ve çalışana özel e-posta bildirimini korur. Varsayılan gönderim artık Resend yerine SMTP'dir.

## Kullanılan ayarlar

- SMTP sunucusu: mail.sencanhairstudio.com
- Port: 25 (kullanıcı port belirtmediği için standart port varsayıldı; doğrulanmış sunucu ayarı değildir)
- Kullanıcı adı/parola: yok; AUTH komutu gönderilmez
- Şifreleme: yok; STARTTLS komutu gönderilmez
- Gönderen: gerçek config.php içindeki mail_from (info@sencanhairstudio.com)

Sunucunun PHP uygulamasından dış alıcılara şifresiz relay izni vermesi gerekir. Kod bunu kendi başına açamaz. Sunucu bağlantıyı/relay'i reddederse panelde aşama ve SMTP kodu görünür. Şifreleme veya parola sonradan gerekirse bu adaptör bunları sessizce atlamaz; farklı SMTP yapılandırması gerekir.

## GitHub ve sunucu

Güncellemedeki klasörleri aynı yerlerine yükleyin; eski dosyaları topluca silmeyin. Özellikle private/smtp.php yeni dosyası da yüklenmelidir. Git çekme ve yayınlama işlemi tamamlanınca kod değişir; GitHub'a yükleme tek başına sunucuda çalışmaz.

Sunucudaki gerçek private/config.php, veritabanı, yedekler ve video dosyaları korunur. Pakette gerçek config.php, SMTP şifresi veya API anahtarı yoktur. Yeniden kurulum veya SQL tablo değişikliği gerekmez.

Mevcut config.php içinde mail_enabled=true ve mail_from tanımlıysa, yeni SMTP varsayılanları ayrıca config değiştirmeden kullanılır. mail_transport alanı yoksa smtp kabul edilir; eski resend_api_key kullanılmaz. Eğer özel olarak mail_transport=resend yazılmışsa, bu alan smtp yapılmalıdır. Örnek config dosyası gerçek config yerine yazılmamalıdır.

Port farklıysa private/config.php içinde smtp_port, sunucu farklıysa smtp_host ayarlanabilir. Ana makine adresine http:// yazılmaz.

## Test ve eski başarısız mailler

1. Admin panelini yenileyin. Mail bölümünde SMTP ayarlı görünür. Bu ifade yalnız ayarların hazır olduğunu belirtir; bağlantı veya teslim garantisi değildir.
2. Çalışan profilinde kendi kontrol ettiğiniz alıcı adresini kaydedin; yeni bir gelecek test randevusu oluşturun.
3. Mevcut her-dakika cron görevi private/cron.php dosyasını çalıştırmaya devam eder. Yol aynıysa yeniden görev oluşturmak gerekmez.
4. Durumda 'sunucuya kabul edilen' sayısını ve varsa son SMTP hatasını kontrol edin. SMTP kabulü alıcının gelen kutusuna kesin teslim garantisi değildir.
5. Önceden başarısız olan gelecekteki onaylı randevular için paneldeki yeniden sıraya alma düğmesini bir kez kullanın. Geçmiş, iptal edilmiş ve tamamlanmış randevular yeniden gönderilmez. Gönderilmiş işler de tekrar sıraya alınmaz.

Resend'in domain doğrulaması bu varsayılan SMTP yolunda kullanılmaz. Sunucunun normal SPF/DKIM, IP itibarı ve mail teslim ayarları yine önemlidir. Şifresiz/şifrelemesiz SMTP içeriği bağlantıda şifrelenmez; bu sunucunun verdiği yönteme göre uygulanmıştır.

DATA kabul yanıtından önce bağlantı kesilmesi teslim durumunu belirsiz bırakabilir. Sabit Message-ID kullanılsa da SMTP kesin tek-sefer teslim garantisi sağlamaz. DATA 250 kabulünden sonra QUIT bağlantısı kapanırsa iş tekrar gönderilmez.

## Doğrulama

Yalnız yerel sahte SMTP sunucusuyla gerçek protokol testi yapıldı: Türkçe MIME, AUTH/TLS göndermeme, HELO fallback, çok satırlı yanıtlar, RCPT/DATA reddi ve DATA kabulü sonrası bağlantı kapanması. Gerçek PHP/MySQL ile oturum/CSRF, yalnız gelecek/onaylı işleri yeniden sıraya alma, SMTP kabulü sonrası sent durumu ve temel 17 randevu akışı testi geçti. Gerçek mail.sencanhairstudio.com sunucusuna bağlantı veya canlı alıcıya e-posta gönderimi yapılmadı.
