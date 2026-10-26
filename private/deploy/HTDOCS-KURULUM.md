# Sabit htdocs için otomatik yayınlama

Sunucuda Bash, rsync ve flock gerekir. PHP uygulaması için build gerekmez.
Bu betik sunucuda çalıştırılır; GitHub Actions değildir.

Önerilen yerleşim:

```
domain-dizini/
  repo/       # Git checkout, internetten erişilmez
  private/    # betik PHP sunucu kodunu buraya aktarır
  htdocs/     # mevcut Document Root
```

1. Mevcut dosya ve veritabanını yedekleyin. Repoyu htdocs DIŞINDA repo/ dizinine klonlayın.
2. Sunucudaki gerçek domain dizinini aşağıdaki örnek yol yerine kullanın:

```bash
cd /gercek/domain-dizini/repo
bash deploy/publish.sh /gercek/domain-dizini --include-setup
```

3. private/config.example.php dosyasından private/config.php oluşturup veritabanı ve HTTPS ayarlarını doldurun. Parolalar GitHub'a girmez. /setup.php üzerinden ilk kurulumu tamamlayın; sonra htdocs/setup.php dosyasını silin ve install_token değerini temizleyin.
4. Hosting panelindeki Git yayınlama sonrası komutuna şunu girin (gerçek yollarla):

```bash
bash /gercek/domain-dizini/repo/deploy/publish.sh /gercek/domain-dizini
```

Bu komut her başarılı Git çekiminden sonra çalışmalıdır. Böylece elle taşıma gerekmez.
Panelde otomatik Git çekimi yoksa güncelleme komutu:

```bash
git -C /gercek/domain-dizini/repo pull --ff-only && bash /gercek/domain-dizini/repo/deploy/publish.sh /gercek/domain-dizini
```

Betik private/config.php, backups/, cron-status.json ve .cron.lock dosyalarını kopyalamaz. Sunucudaki dosyaları silmez. setup.php yalnız --include-setup seçeneğiyle aktarılır; normal güncellemeler kurulum sayfasını geri getirmez. Repodan kaldırılan eski kod dosyalarının sunucudan kaldırılması gerektiğinde ayrıca değerlendirilmelidir. Yayınlama sırasında kısa bakım penceresi kullanılması önerilir; bu betik atomik sürüm geçişi sağlamaz.

htdocs dizinine yalnız public_html İÇERİĞİ gider; private dosyaları asla htdocs içine kopyalanmaz. public dosyalar 644/klasörler 755, private dosyalar 640/klasörler 750 olur. PHP işleminin hosting kullanıcısı/grubu private dosyalarını okuyabilmelidir.

Cron görevi web isteği değildir; gerçek PHP CLI yoluyla her dakika /gercek/domain-dizini/private/cron.php çalıştırılmalıdır. E-posta bağlantısı yapılandırılmadıkça bildirim gönderilmez.
