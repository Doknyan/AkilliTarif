# Akıllı Tarif 🍳

Akıllı Tarif, mutfağınızdaki malzemeleri en verimli şekilde kullanmanızı sağlayan, yapay zeka destekli bir yemek tarifi ve mutfak yönetim platformudur.

## 🚀 Proje Hakkında

Bu proje, Web Programlama dersi dönem ödevi kapsamında geliştirilmiştir. Kullanıcıların evlerindeki malzemeleri takip etmelerine, diyet tercihlerine (vegan, glutensiz vb.) göre tarif aramalarına ve kendi tariflerini paylaşmalarına olanak tanır.

### ✨ Temel Özellikler

- **Mutfak Dolabı (Smart Pantry):** Elinizdeki malzemeleri ekleyin, son kullanma tarihlerini takip edin.
- **Gelişmiş Filtreleme:** Malzemeye, kaloriye, süreye ve beslenme tipine göre arama.
- **Şef Sistemi:** Onaylı şefler tarafından paylaşılan profesyonel tarifler.
- **Yapay Zeka Entegrasyonu:** AI destekli görsel işleme ve tarif önerileri.
- **Responsive Tasarım:** Mobil, tablet ve masaüstü cihazlarla tam uyumlu arayüz.
- **Admin Paneli:** Kullanıcı yönetimi, şeflik başvuruları ve içerik denetimi.

## 🛠️ Kullanılan Teknolojiler

- **Backend:** PHP 8.x (PDO)
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, Bootstrap 5, JavaScript, jQuery
- **AI/Automation:** Python (OpenCV, PIL), OpenAI API
- **Tools:** WAMP/XAMPP, Composer

## 📥 Kurulum

1. Bu depoyu klonlayın:
   ```bash
   git clone https://github.com/kullaniciadi/akilli-tarif.git
   ```
2. `data/yemek_projesi_db.sql` dosyasını MySQL veritabanınıza içe aktarın.
3. `functions/db.php` dosyasındaki veritabanı bağlantı bilgilerini kendi yerel ayarlarınıza göre güncelleyin.
4. Projeyi bir yerel sunucuda (WAMP, XAMPP vb.) çalıştırın.

## 📊 Veritabanı Şeması

Proje, ilişkisel bir veritabanı mimarisi üzerine kurulmuştur. Tablolar arasındaki ilişkileri gösteren ERD diyagramına `img/erd_diagram.png` adresinden ulaşabilirsiniz.

## 👥 Ekip ve Katkılar

Bu proje üç kişilik bir ekip tarafından geliştirilmiştir:

- **[Tuana Nur GÜLBE]**: Proje mimarisi, Backend geliştirme (PHP/PDO), Veritabanı tasarımı, AI entegrasyonu ve Python otomasyon scriptleri.
- **Begüm Su ERAYDIN**: Veritabanı yönetimi, QA & Test süreçleri, veri girişi ve teknik dökümantasyon.
- **Merve ÇELEBİOĞLU**: Frontend geliştirme, UI/UX tasarımı, Responsive yapı ve kullanıcı etkileşimleri.

## 📄 Lisans

Bu proje eğitim amaçlı geliştirilmiştir. Tüm hakları saklıdır.
