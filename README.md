# Davut Şencan Hair Studio — Sunuculu sürüm

Mevcut siyah / beyaz ve altın çerçeveli arayüzün ortak veritabanıyla çalışan sürümü. Node.js 24 veya üstü gerekir. Ek npm paketi yoktur.

## Durum

Bu paket test edilmiş sunucu uygulamasıdır. Henüz mevcut ChatGPT Sites demosunun yerine yayınlanmadı. Canlı demoda kayıtlar tarayıcıda kalmaya devam eder. Bu paket Hostinger hesabında **Node.js 24 çalıştırabilen ve kalıcı yazılabilir disk sağlayan** bir ortamda başlatılmalıdır. Hosting planı desteklemiyorsa bu dosyaları statik dosya olarak yüklemek yeterli olmaz. Paket / sunucu türü netleşince uygun dağıtım ayarları yapılmalıdır.

## Yerelde başlatma

1. Node.js 24 kurun. ZIP'i çıkartın.
2. `.env.example` dosyasını `.env` adıyla kopyalayın. İlk yönetici şifrenizi `ADMIN_PASSWORD` alanına en az 12 karakterle yazın. Satır içinde `#` veya boşluk varsa değeri çift tırnak içine alın.
3. Klasörde `npm start` çalıştırın. Windows'ta `BASLAT.bat` da kullanılabilir.
4. Müşteri: http://localhost:3000 — Admin: http://localhost:3000/admin

Şifre belirlemeden başlatırsanız güçlü bir şifre üretilip yalnız sunucudaki `data/ilk-giris.txt` dosyasına yazılır. Şifrenizi panelden değiştirin, ardından bu dosyayı silin. Demo şifresi üretim sunucusunda geçerli değildir.

İlk kurulumdan sonra `.env` içindeki ADMIN_PASSWORD değişikliği mevcut şifreyi sıfırlamaz. Şifreyi panelden değiştirin. Başka cihazdan erişmek için HOST=0.0.0.0 kullanılabilir; internet yayını için HTTPS yapılandırması gerekir.

## Tamamlananlar

- Tarayıcı depolaması yerine SQLite ortak kayıt sistemi; tüm cihazlar aynı sunucuya bağlanır.
- İşlem içinde saat / süre çakışma kontrolü; aynı saate eşzamanlı isteklerde tek kayıt.
- İstek tekrarında ikinci kayıt oluşturmayı engelleyen idempotency anahtarı.
- Telefon + rastgele kodla sorgulama, başlangıca kadar tarih/saat değiştirme ve iptal. Değişiklik uygun değilse eski randevu korunur; başarılı değişiklikte aynı kod ve hizmet/tutar bilgileri korunur.
- Saç & Sakal ve Bakımlar kategorilerinde çoklu hizmet seçimi; fiyatlar sunucuda hesaplanır. Perma, renklendirme ve keratin başlangıç fiyatıdır ve sarı bilgilendirmeyle gösterilir. Hizmet ve ücretler kayıt anında saklanır.
- Hizmet başına süreler belirtilmediği için süre, mevcut çalışan randevu süresine göre belirlenir; çoklu işlemler için işletme gerçek süreleri ayrıca netleştirmelidir.
- Fotoğraf, açıklama, çalışan ekleme / arşivleme, haftalık saatler, isteğe bağlı molalar, izinler ve randevu süresi ortak kaydedilir.
- Kadircan: salı 18:00–22:00; Enescan: çarşamba 18:00–22:00. Diğer varsayılanlar 09:00–22:00, mola yok. Bunlar panelden değiştirilebilir.
- Sunucuda scrypt parola özeti; HttpOnly / SameSite oturum; CSRF ve istek kaynağı doğrulaması; giriş ve sorgu deneme sınırları.
- Parola değişince tüm oturumlar kapanır. Oturumlar 8 saat sürer.
- Admin randevu listesi açıkken 30 saniyede bir yenilenir.
- İşletme ayarlarında parola değiştirme, şimdi yedekle ve JSON dışa aktarma.
- Sunucu açıkken ilk dakika içinde ve ardından en az 24 saatte bir tutarlı SQLite yedeği; varsayılan 30 günlük saklama.
- E-posta için kalıcı kuyruk ve tekrar deneme altyapısı. **Mail gönderimi varsayılan kapalıdır.**

## Canlı kurulum ayarları

`HOST=0.0.0.0`, hostingin verdiği `PORT`, `PUBLIC_ORIGIN=https://gercek-domaininiz` ve `COOKIE_SECURE=true` ayarlanmalıdır. Başlangıç komutu `npm start`, çalışma dizini bu klasördür. Ana veritabanı için DATA_DIR ve yedekler için BACKUP_DIR kalıcı diskte olmalıdır. Tek uygulama sunucusu örneği kullanın. Site açılmadan önce HTTPS'i ve yönetici parolasını doğrulayın.

`data`, `backups`, `.env` ve tüm sunucu kodunu web'in herkese açık `public_html` dizinine koymayın. Sunucu yalnız `public/` altındaki izin verilen dosyaları sunar; `/admin` ve `/admin.html` aynı korunan işlem panelini açar.

Veritabanı `data/appointments.sqlite` içindedir. Tarayıcıdan sayfa silinmesi randevuları silmez. Yedekler SQLite dosyasıdır; hesap özetleri ve müşteri bilgileri içerir, herkese açık paylaşmayın. Aynı sunucudaki yedekler sunucu kaybına karşı tek başına yeterli değildir; hosting üzerinden ayrı ve güvenli bir yere periyodik kopya alın.

## Yedekleme ve geri yükleme

Çalışan sunucuda `npm run backup` ile de tutarlı yedek oluşturabilirsiniz. Paneldeki JSON dışa aktarımı yönetici şifrelerini ve oturumlarını içermez; tam geri yükleme için SQLite yedeğini kullanın.

Geri yüklemeden önce **sunucuyu durdurun**. `.env` ayarlarını yükleyerek:

```
node --env-file-if-exists=.env tools/restore.cjs /tam/yol/sencan-yedek.sqlite
```

Araç eski veritabanını koruma kopyasına alır, geçerli yedeği kontrol eder, oturumları kapatır ve eski bekleyen mailleri yeniden göndermez. WAL/SHM dosyası varsa üzerine yazmayı reddeder; dosyaları çalışan sunucudan silmeyin. Geri yükleme sonrası sunucuyu tekrar başlatın. Bir yedeği gerçekten geri yükleyerek düzenli kontrol edin.

## E-posta (henüz bağlı değil)

Mail için doğrulanmış bir gönderici, gönderim hizmeti hesabı ve sunucuda gizli anahtar gereklidir. `.env` veya hostingin gizli ortam değişkenleri bölümünde:

- MAIL_ENABLED=true
- RESEND_API_KEY: sunucuya özel gönderim anahtarı
- BOOKING_NOTIFICATION_FROM: doğrulanmış `Davut Şencan Hair Studio <info@alanadiniz>`
- BOOKING_NOTIFICATION_TO: alıcı adres(ler)i, birden fazlaysa virgülle ayırın

Anahtarı tarayıcıya, kaynak deposuna veya sohbet mesajına koymayın. Yalnız kurulum tamamlandığında MAIL_ENABLED=true yapın. Anahtar eksikse açık mail yapılandırmasıyla başlatma reddedilir.

Bildirim randevu veritabanına kaydedildiği işlemde kuyruğa girer; sunucu en fazla 10 saniyede bir işler. Hata olursa yeniden denenir; mail hatası randevuyu silmez. Aynı bildirim için sağlayıcı idempotency anahtarı kullanılır. 5 başarısız denemeden sonra admin durumunda başarısız olarak görünür. Bildirimin iptalden önce teslim edildiği bir yarış durumunda mail geri alınamaz. Mail kapalıyken geçmiş kayıtlar daha sonra topluca mail olarak gönderilmez. Henüz SMTP desteği veya müşteri SMS/WhatsApp hatırlatması bağlı değildir.

## Mevcut demodaki veriler

Canlı demo ayarları ve örnek randevular kullanıcının kendi tarayıcısındadır. Bu sunucu o cihazın depolamasını kendiliğinden okuyamaz. Yeni sunucu yukarıdaki işletme ve çalışan varsayılanlarıyla başlar. Demo verilerini taşımak istenirse, gerçek yayından önce ayrıca dışa aktarıp doğrulayarak içe aktarma yapılmalıdır. Kullanıcıya özgü fotoğraflar kendiliğinden taşınmaz.

## Kontroller ve sınırlar

`npm test` gerçek HTTP istekleri ve ayrı geçici SQLite veritabanlarıyla testleri çalıştırır. Harici mail gönderimi testlerde taklit edilir; gerçek alıcıya test maili gönderilmez. Tarayıcıda manuel görsel kontrol ve Hostinger üzerinde kurulum henüz yapılmadı.

Bu sürüm ödeme tahsil etmez, yalnız ödeme yöntemi seçimini kaydeder. KVKK aydınlatması, saklama / silme politikası ve işletmenin gerçek iletişim bilgileri işletmeyle netleştirilip canlıya çıkmadan uygulanmalıdır. Çok şubeli kurulum ve çok sunucuya dağıtım bu pakette yer almaz.
