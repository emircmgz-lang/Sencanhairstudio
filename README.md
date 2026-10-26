# Davut Şencan Hair Studio — PHP + MySQL

Hostinger gibi PHP hostingleri için derlemesiz sürüm. Node.js, npm veya Composer gerekmez. Gereksinimler: PHP 8.2+, PDO MySQL, mbstring, MySQL/MariaDB ve InnoDB. E-posta daha sonra açılacaksa cURL gerekir. PHP 8.3 ve MariaDB 11.4 üzerinde test edilmiştir; gerçek Hostinger hesabında henüz kurulum yapılmadı.

## Önce bunu okuyun

Bu yeni bir PHP paketidir; GitHub'daki önceki `server.cjs` / `package.json` sürümünden farklıdır. Önceki sürümün dosyalarını PHP dosyalarıyla rastgele karıştırmayın. GitHub'a bu paketin kökünü yükleyin. Hostingde ise aşağıdaki yerleşimi kullanın:

```
domain-klasoru/
├── private/                  ← internetten erişilmeyen klasör
│   ├── config.example.php
│   ├── config.php            ← siz oluşturursunuz, GitHub'a girmez
│   ├── bootstrap.php
│   ├── domain.php
│   ├── schema.sql
│   ├── backup.php
│   ├── mail.php
│   └── cron.php
└── public_html/              ← domainin web kökü
    ├── index.html
    ├── admin.html
    ├── .htaccess
    ├── setup.php             ← kurulum sonrası silin
    ├── api/index.php
    ├── client.js
    └── ...tasarım dosyaları
```

`private` klasörü **public_html içine konmaz**. İkisi aynı seviyede olmalıdır. Bu yerleşim veritabanı parolaları ve yedeklerin doğrudan indirilmesini önler. Dosya erişim izinlerini hosting kullanıcısına göre ayarlayın; genel 777 izni vermeyin.

## Sabit htdocs ve Git güncellemeleri

Document Root sabit `htdocs` ise [otomatik yayınlama yönergesini](deploy/HTDOCS-KURULUM.md) kullanın. `deploy/publish.sh` her Git çekiminden sonra site dosyalarını aktarır; sunucuya özel ayarları korur.

## Kurulum

1. Mevcut site ve varsa gerçek veritabanını yedekleyin. Paket eski Node/SQLite kayıtlarını kendiliğinden taşımaz.
2. `public_html` içeriğini sitenin gerçek web köküne yükleyin. Dosyalar iç içe ikinci `public_html` klasöründe kalmasın. `.htaccess` dahil edilmelidir.
3. `private` klasörünü web kökünün yanına yükleyin.
4. hPanel → veritabanları bölümünden boş bir MySQL veritabanı ve kullanıcı oluşturun. Kullanıcıya o veritabanının yetkilerini verin. Hostingde veritabanı ve kullanıcı adlarında hesabınıza ait önek olabilir; tam adları kullanın.
5. `private/config.example.php` dosyasını `config.php` olarak kopyalayın. `db_host`, `db_port`, `db_name`, `db_user`, `db_password` değerlerini hostingin verdiği bilgilerle doldurun. Parolaları sohbete veya GitHub'a yazmayın.
6. `public_origin` gerçek HTTPS adresiniz olsun. `allowed_origins` kullanılıyorsa www adresiniz burada bulunabilir. `cookie_secure` canlıda true kalmalıdır.
7. `install_token` için kendinize özel en az 24 karakterlik rastgele bir anahtar oluşturun. Örnek dosyada bilinen ortak bir kurulum anahtarı bulunmaz. Bunu yalnız ilk kurulumda kullanacaksınız.
8. HTTPS aktifken `/setup.php` adresine gidin. Kurulum anahtarını ve en az 12 karakterlik yeni yönetici şifresini girin. Veritabanı tabloları oluşturulur; ilk yönetici atanır.
9. Kurulum tamamlanınca **setup.php dosyasını silin** ve config içindeki install_token değerini temizleyin. Kurulum tablosunda yönetici varsa kurulum sayfası zaten yeniden şifre atamaz.
10. `/admin.html` veya `/admin` adresinden giriş yapın. Demo şifresi bu sunucuda geçerli değildir. İşletme adı, adres, telefon, çalışan fotoğrafı ve saatlerini panelden ayarlayın.

## Otomatik yedekleme

Hostinger Cron görevlerine, her dakika `private/cron.php` dosyasını PHP CLI ile çalıştıran bir görev ekleyin. Tam yol hesap/domain yerleşimine ve hostingin PHP sürümüne bağlıdır. Paneldeki gerçek PHP 8.2+ çalıştırıcısını kullanın. Örnek:

```
/usr/bin/php /home/KULLANICI/domains/sencanhairstudio.com/private/cron.php
```

Bu örnekte KULLANICI yerine gerçek kullanıcı yolunuzu yazın. Cron, en az 24 saat arayla SQL yedeği oluşturur ve mail açıksa kuyrukta bekleyen bildirimleri işler. **Cron kurulmadan otomatik görevlerin çalıştığı varsayılmaz.** Admin ayarlarında son görev çalışması ve son yedek zamanı görünür. `Şimdi yedekle` düğmesi Cron olmadan da çalışır.

Yedekler `private/backups/sencan-....sql.gz` içine yazılır; varsayılan 30 gün saklanır. Ayrı ve güvenli bir yere düzenli kopyalayın; aynı hostingdeki yedekler hesap kaybına karşı tek başına yeterli değildir. Tam SQL yedeği yönetici parola özetini ve müşteri bilgilerini içerir; herkese açık paylaşmayın.

## Yedekten dönme

Sitenin dosyalarını da koruyun ve bakım sırasında yeni randevu alınmasını durdurun. Tercihen önce **boş bir deneme veritabanında** doğrulayın. Hosting phpMyAdmin → İçe aktar yoluyla `.sql.gz` yedeğini seçin. SQL, aynı `sc_` tablolarındaki mevcut kayıtları değiştirir; canlı verilerin üstüne yazmadan önce yeniden yedek alın. Yedek geri yüklendiğinde oturumlar kapanır; eski bekleyen bildirimler tekrar gönderilmemesi için atlanır. Sonra siteyi yeni / geri yüklenen veritabanına bağlayın ve yönetici girişini, sorgulamayı kontrol edin.

## E-posta bağlantısı

Şimdilik `mail_enabled` false kalır. Mail henüz gönderilmez. Doğrulanmış gönderici ve hizmet hesabı hazır olduğunda `config.php` içindeki `resend_api_key`, `mail_from` ve `mail_to` güvenli şekilde doldurulur; `mail_enabled` true yapılır. Gönderici örneği: `Davut Şencan Hair Studio <info@alanadiniz>`. Alıcılar PHP dizisi olarak yazılır: `['alici@ornek.com']`.

Yeni randevu ve bildirim kuyruğu aynı veritabanı işleminde kaydedilir. Cron gönderimi dener, sağlayıcı idempotency anahtarıyla yinelenen gönderimi azaltır. Hata olursa randevu kaybolmaz; en fazla 5 deneme yapılır. Mail kapalıyken alınan eski randevular sonradan kendiliğinden topluca gönderilmez. SMTP / WhatsApp / SMS bu pakete bağlı değildir. E-posta hatırlatıcıları ayrıca planlanmalıdır; mevcut kuyruk işletmeye **yeni randevu** bildirimi içindir.

## Korunan özellikler

- Siyah / beyaz tema, çalışan kutuları, fotoğraflar, telefon ve Instagram bağlantıları.
- İşletme adresinden yol tarifi ve arama bağlantıları.
- Çalışan ekleme, düzenleme, arşivleme, izinler, haftalık saatler ve isteğe bağlı molalar.
- Kadircan salı, Enescan çarşamba 18:00–22:00; diğer saatler panelden değiştirilebilir. Mola varsayılan kapalı.
- Saç ve Bakımlar kategorilerinde çoklu hizmet ve sunucuda fiyat hesaplama. Başlangıç fiyatları sarı açıklamayla görünür. Kayıt anındaki fiyat/süre korunur.
- Telefon + rastgele kodla başka cihazdan sorgulama, başlangıca kadar iptal ve tarih/saat değiştirme.
- Aynı veritabanındaki işleme kilit konarak eşzamanlı / çakışan randevuların engellenmesi. Tekrarlanan ağ isteği ikinci kayıt oluşturmaz.
- Sunucuda parola özeti, HttpOnly / SameSite oturum, CSRF ve kaynak kontrolü, deneme sınırları. Şifre değişince tüm oturumlar kapanır.
- Yönetici kayıt dışa aktarma; giriş yapmadan API'den randevu listesi alınamaz.

## Taşıma ve sınırlar

Canlı ChatGPT Sites demosundaki veriler o cihazın tarayıcısındadır; bu PHP paketi bu kayıtları kendiliğinden alamaz. Fotoğrafları ve ayarları yeni yönetim paneline yeniden girmeniz veya ayrıca taşıma işlemi yapmamız gerekir. Eski Node sürümünde gerçek SQLite kayıtları varsa silmeyin; MySQL'e ayrı bir taşıma yapılmalıdır.

Ödeme tahsil edilmez; ödeme yöntemi kaydedilir. Hizmetlere özel süreler henüz verilmediği için çalışanın tanımlı randevu süresi kullanılır. Gerçek işletme bilgileri, hizmet süreleri, KVKK aydınlatması ve saklama/silme politikası yayından önce netleştirilmelidir.

## Kontroller

`tests/` altındaki testler PHP 8.3 ve MariaDB 11.4 konteynerlerinde gerçek HTTP istekleriyle çalıştırıldı. Aynı saat, şifre, sorgu, iptal, saat değişimi, fotoğraf, fiyatlar, veritabanı kalıcılığı, SQL yedeği ve geri yükleme kontrol edildi. Gerçek mail gönderilmedi. Apache/LiteSpeed yapılandırması ve gerçek Hostinger hesabı burada doğrulanmadı; canlı kurulumdan sonra ayrıca kontrol edilmelidir.

## Çalışana özel e-posta

Güncel kurulum ve kullanım: [MAIL-GUNCELLEME.md](MAIL-GUNCELLEME.md). Alıcı adresleri admin panelinden düzenlenir; mail_to kullanılmaz. Müşteri hatırlatması yoktur.
