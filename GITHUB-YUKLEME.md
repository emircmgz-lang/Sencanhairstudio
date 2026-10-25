# GitHub yükleme

ZIP dosyasını çıkartın. ZIP'in kendisini değil, içindeki dosyaları ve klasörleri yükleyin. Repository kökünde package.json, server.cjs ve public, lib, tests, tools klasörleri bulunmalıdır.

.env.example boş örnektir; yüklenebilir. Gerçek .env, data, backups, ilk-giris.txt, SQLite dosyaları, şifreler ve API anahtarları GitHub'a yüklenmez. .gitignore bu dosyaları komut satırıyla Git kullanırken dışarıda tutar; web sayfasına elle yüklerken dosyaları yine kendiniz kontrol edin.

GitHub → New repository → Private → Create repository → uploading an existing file / Add file → Upload files → ZIP içeriğini klasör yapısıyla ekleyin → Commit changes. İlk yüklemede README ekleme kutusunu boş bırakabilirsiniz; paket zaten README içerir.

Masaüstünde çıkarılmış klasörün içindeki tüm dosya ve klasörleri yükleme alanına sürüklemek en kolay yöntemdir. Telefonda tarayıcının Masaüstü site seçeneği kullanılabilir. Klasör yüklemesi desteklenmiyorsa dosyaları tek tek aynı dizinlere koymanız veya bilgisayardan yüklemeniz gerekir.

Bu Node.js + SQLite uygulamasıdır. GitHub kaynak kodu saklar; GitHub Pages sunucuyu çalıştırmaz. Gerçek çalışma için Node.js 24 ve kalıcı disk sağlayan hosting gerekir. Çalıştırma yönergesi README.md dosyasındadır.
