# Son tasarım + gerçek randevu + çalışana özel mail

Bu paket PHP/MySQL sürümüdür. Demo giriş şifresi veya tarayıcıda tutulan sahte randevu API'si içermez.

## GitHub'a yükleme

ZIP'i çıkarın. İçindeki public_html, private, deploy, tests klasörlerini ve kök dosyalarını reponun aynı seviyesine yükleyip mevcut kod dosyalarını güncelleyin. Tüm repoyu veya çalışan veritabanını silmeyin.

Gerçek private/config.php, API anahtarı, yedekler ve yüklenen videolar bu pakete dahil değildir ve GitHub'a yüklenmez. Sunucudaki mevcut config.php korunur. Yeni kurulum yapıldıysa setup.php tekrar çalıştırılmaz; mevcut randevular korunur. Henüz ilk kurulum yapılmadıysa README'deki ilk kurulum adımları geçerlidir.

Yeni SQL tablosu/migrasyon gerekmez. Yeni sayfa ayarları mevcut işletme JSON kaydına eklenir.

## Sunucu güncellemesi

Git'ten güncellemeyi çektikten sonra mevcut deploy/publish.sh çalıştırılmalıdır. Otomatik çekme/yayınlama bağlı değilse GitHub'a yüklemek tek başına siteyi güncellemez. private klasörü web kökünün dışında kalır. Domainin gerçek web kökü public_html içeriğini sunmalıdır; htdocs yerleşimi için deploy/HTDOCS-KURULUM.md kullanılır.

Video dosyaları sunucuda private/media içinde tutulur; yayınlama betiği bunları ve mevcut config/backup/cron durumunu korur. PHP kullanıcısının private dizinine yazma ve kendi dosyalarını okuma yetkisi olmalıdır. 777 vermeyin.

public_html/.user.ini, destekleyen PHP-FPM/CGI hostinglerde 64M dosya ve 70M POST sınırı ayarlar. Etkinleşmesi birkaç dakika sürebilir. Sunucunun web istek limiti de en az bu büyüklüğe izin vermelidir. Sunucu bu ayarlara izin vermiyorsa daha küçük video seçin veya hostingden limit ayarı isteyin. PHP fileinfo, mbstring, PDO MySQL ve mail için cURL gerekir.

## Yönetim

- admin.html: mevcut gerçek yönetici şifrenizle giriş yapın.
- Çalışanlar: her çalışanın bildirim e-postasını kaydedin. Yalnız randevu alınan çalışana gönderilir. Boş alan bildirim göndermez.
- İşletme Ayarları: kalın başlık ve ince açıklama, Hakkımızda başlığı/metni/fotoğrafları ve giriş videosu yönetilir.
- Hakkımızda fotoğrafları en fazla 5 adet; küçültülüp ortak veritabanına kaydedilir.
- Giriş videosu en fazla 60 MB MP4/WebM; özel dosya alanına yüklenir, doğrulanmış medya endpointinden yayınlanır. Ses kapalıdır. Telefonda otomatik oynatma cihaz ayarına bağlı olabilir.

## Mail

Mevcut config.php içindeki mail_enabled, resend_api_key ve mail_from alanları kullanılır. Resend domain doğrulaması ve geçerli gönderim anahtarı gerekir. mail_from: Davut Şencan Hair Studio <info@sencanhairstudio.com>. mail_to kullanılmaz. Müşteri hatırlatması yoktur.

Her dakika PHP CLI ile private/cron.php çalışmalıdır. Bu görev zaten kuruluysa yolu değişmedikçe yeniden kurmaya gerek yoktur. Gerçek API anahtarı veya webmail şifresi GitHub'a girmez.

Bu paket gerçek e-posta göndermeden test edilmiştir. Canlıda bağlantı kurulduğunda kendi adresinize bir çalışan test randevusu oluşturarak teslimatı kontrol edin.

## Yedekler

SQL yedeği randevuları, çalışanları, Hakkımızda fotoğraflarını ve sayfa ayarlarını içerir. Video dosyaları SQL yedeğine gömülmez: private/media klasörü ayrıca hosting dosya yedeğine dahil edilmelidir. config.php de güvenli hosting yedeğinde korunmalıdır. Tema tercihi yalnız tarayıcıya özeldir.

## Doğrulama

Gerçek PHP 8.3 ve MariaDB 11.4 ile randevu/kimlik/saat değiştirme/yedekleme akışları; ayrı HTTP oturumlarında metin ve medya erişimi; video Range/HEAD istekleri; oturum ve CSRF kontrolleri; alıcı seçimi ve e-posta gizliliği test edildi. Gerçek hosting kurulumu, gerçek mail teslimatı ve cihazda görsel kontrol yapılmadı.
