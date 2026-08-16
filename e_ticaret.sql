-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 16 Ağu 2026, 21:54:00
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `e_ticaret`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `favoriler`
--

CREATE TABLE `favoriler` (
  `id` int(11) NOT NULL,
  `kullanici_id` int(11) NOT NULL,
  `urun_id` int(11) NOT NULL,
  `ekleme_tarihi` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `favoriler`
--

INSERT INTO `favoriler` (`id`, `kullanici_id`, `urun_id`, `ekleme_tarihi`) VALUES
(7, 5, 10, '2026-08-12 22:39:47');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kampanyalar`
--

CREATE TABLE `kampanyalar` (
  `id` int(11) NOT NULL,
  `ad` varchar(100) NOT NULL,
  `kod` varchar(50) NOT NULL,
  `indirim_tipi` enum('yuzde','sabit') NOT NULL,
  `indirim_miktari` decimal(10,2) NOT NULL,
  `min_sepet_tutari` decimal(10,2) DEFAULT NULL,
  `baslangic_tarihi` datetime NOT NULL,
  `bitis_tarihi` datetime NOT NULL,
  `aktif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `kampanyalar`
--

INSERT INTO `kampanyalar` (`id`, `ad`, `kod`, `indirim_tipi`, `indirim_miktari`, `min_sepet_tutari`, `baslangic_tarihi`, `bitis_tarihi`, `aktif`) VALUES
(1, 'Hoş Geldin İndirimi', 'HOSGELDIN10', 'yuzde', 10.00, 100.00, '2026-08-01 21:52:56', '2026-08-31 21:52:56', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kargo_firmalari`
--

CREATE TABLE `kargo_firmalari` (
  `id` int(11) NOT NULL,
  `ad` varchar(100) NOT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `web_sitesi` varchar(255) DEFAULT NULL,
  `takip_url` varchar(255) DEFAULT NULL,
  `ucret` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ucretsiz_siparis_tutari` decimal(10,2) DEFAULT NULL,
  `agirlik_baslangic` decimal(8,2) DEFAULT NULL,
  `agirlik_bitis` decimal(8,2) DEFAULT NULL,
  `aktif` tinyint(1) DEFAULT 1,
  `sira` int(11) DEFAULT 0,
  `olusturma_tarihi` datetime DEFAULT current_timestamp(),
  `guncelleme_tarihi` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `kargo_firmalari`
--

INSERT INTO `kargo_firmalari` (`id`, `ad`, `logo_url`, `web_sitesi`, `takip_url`, `ucret`, `ucretsiz_siparis_tutari`, `agirlik_baslangic`, `agirlik_bitis`, `aktif`, `sira`, `olusturma_tarihi`, `guncelleme_tarihi`) VALUES
(1, 'Aras Kargo', '', 'https://www.araskargo.com', 'https://www.araskargo.com/takip/{takip_no}', 89.00, 1000.00, NULL, 1.00, 1, 1, '2026-08-05 22:58:02', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kategoriler`
--

CREATE TABLE `kategoriler` (
  `id` int(11) NOT NULL,
  `ust_id` int(11) DEFAULT NULL,
  `ad` varchar(100) NOT NULL,
  `aciklama` longtext NOT NULL,
  `slug` varchar(100) NOT NULL,
  `durum` enum('aktif','pasif') DEFAULT 'aktif',
  `icon` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `kategoriler`
--

INSERT INTO `kategoriler` (`id`, `ust_id`, `ad`, `aciklama`, `slug`, `durum`, `icon`) VALUES
(1, NULL, 'Elektronik', 'Hayatınızı kolaylaştıran, işinizi hızlandıran ve eğlencenizi üst seviyeye taşıyan en yeni teknolojik ürünler tek bir adreste. Akıllı telefonlardan ev elektroniğine, bilgisayar bileşenlerinden giyilebilir teknolojiye kadar geniş ürün yelpazemizle ihtiyacınız olan tüm çözümleri sunuyoruz.', 'elektronik', 'aktif', 'fa-solid fa-laptop'),
(2, NULL, 'Giyim', '', 'giyim', 'aktif', 'fa-solid fa-shirt'),
(4, 1, 'Telefon', '', 'telefon', 'aktif', ''),
(5, 1, 'Laptop', '', 'laptop', 'aktif', ''),
(6, 2, 'Erkek Giyim', '', 'erkek-giyim', 'aktif', ''),
(7, 2, 'Kadın Giyim', '', 'kadin-giyim', 'aktif', ''),
(13, NULL, 'Kozmetik', '', 'kozmetik', 'aktif', 'fa-solid fa-spray-can-sparkles'),
(14, 13, 'Yüz Bakım', '', 'yuz-bakim', 'aktif', ''),
(15, NULL, 'Kitap', '', 'kitap', 'aktif', 'fa-solid fa-book'),
(16, NULL, 'Gıda', '', 'gida', 'aktif', 'fa-solid fa-utensils'),
(17, NULL, 'Saat &amp; Aksesuar', '', 'saat-aksesuar', 'aktif', 'fa-solid fa-stopwatch');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanicilar`
--

CREATE TABLE `kullanicilar` (
  `id` int(11) NOT NULL,
  `ad` varchar(50) NOT NULL,
  `soyad` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `sifre` varchar(255) NOT NULL,
  `yetki` enum('admin','user') DEFAULT 'user',
  `durum` enum('aktif','pasif') DEFAULT 'aktif',
  `kayit_tarihi` datetime DEFAULT current_timestamp(),
  `son_giris` datetime DEFAULT NULL,
  `telefon` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `kullanicilar`
--

INSERT INTO `kullanicilar` (`id`, `ad`, `soyad`, `email`, `sifre`, `yetki`, `durum`, `kayit_tarihi`, `son_giris`, `telefon`) VALUES
(2, 'Test', 'User', 'test@site.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'aktif', '2026-08-01 21:52:56', NULL, '5416665522'),
(5, 'Furkan', 'POLAT', 'eticaret@eticaret.com', '$2y$10$7amhNah3hZstCiadAxxhO.edOrQFyNOCPFNDmDDP8OmTlBmwgycDa', 'admin', 'aktif', '2026-08-02 21:25:31', '2026-08-16 21:01:35', '5416665522'),
(6, 'Emre', 'POLAT', 'emre@shop.com', '$2y$10$UftJ0c9aThOiqsMgK8kTHu5.flFuCMs89nTDHLgXjMRZEvDuMDeUO', 'user', 'aktif', '2026-08-07 22:16:16', NULL, '5416665522');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanici_adresleri`
--

CREATE TABLE `kullanici_adresleri` (
  `id` int(11) NOT NULL,
  `kullanici_id` int(11) NOT NULL,
  `adres_basligi` varchar(50) NOT NULL,
  `ad` varchar(50) NOT NULL,
  `soyad` varchar(50) NOT NULL,
  `telefon` varchar(20) NOT NULL,
  `il` varchar(50) NOT NULL,
  `ilce` varchar(50) NOT NULL,
  `acik_adres` text NOT NULL,
  `varsayilan` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `kullanici_adresleri`
--

INSERT INTO `kullanici_adresleri` (`id`, `kullanici_id`, `adres_basligi`, `ad`, `soyad`, `telefon`, `il`, `ilce`, `acik_adres`, `varsayilan`) VALUES
(1, 5, 'İşyerim', 'Furkan', 'POLAT', '5416160383', 'İstanbul', 'Ataşehir', 'Merkez mah. merkez sok. no 11', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `log_istemci`
--

CREATE TABLE `log_istemci` (
  `id` int(11) NOT NULL,
  `kullanici_id` int(11) DEFAULT NULL,
  `islem_turu` enum('giris','cikis','kayit','siparis','odeme','urun_ekle','urun_sil','guncelle','silme','hata') NOT NULL,
  `aciklama` text NOT NULL,
  `ip_adresi` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `referer_url` varchar(255) DEFAULT NULL,
  `sayfa_url` varchar(255) DEFAULT NULL,
  `islem_tarihi` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `markalar`
--

CREATE TABLE `markalar` (
  `id` int(11) NOT NULL,
  `ad` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `aciklama` text DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `web_sitesi` varchar(255) DEFAULT NULL,
  `durum` enum('aktif','pasif') DEFAULT 'aktif',
  `olusturma_tarihi` datetime DEFAULT current_timestamp(),
  `guncelleme_tarihi` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `markalar`
--

INSERT INTO `markalar` (`id`, `ad`, `slug`, `aciklama`, `logo_url`, `web_sitesi`, `durum`, `olusturma_tarihi`, `guncelleme_tarihi`) VALUES
(2, 'Apple', 'apple', '', '', '', 'aktif', '2026-08-05 21:24:43', NULL),
(3, 'Calliel', 'calliel', '', '', '', 'aktif', '2026-08-05 21:30:54', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `promo_banners`
--

CREATE TABLE `promo_banners` (
  `id` int(11) NOT NULL,
  `baslik` varchar(255) NOT NULL,
  `alt_baslik` varchar(255) DEFAULT NULL,
  `aciklama` text DEFAULT NULL,
  `buton_metni` varchar(100) DEFAULT NULL,
  `buton_link` varchar(255) DEFAULT NULL,
  `renk` varchar(50) DEFAULT 'purple',
  `resim_url` varchar(255) DEFAULT NULL,
  `sira` int(11) DEFAULT 0,
  `durum` enum('aktif','pasif') DEFAULT 'aktif',
  `olusturma_tarihi` datetime DEFAULT current_timestamp(),
  `guncelleme_tarihi` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `promo_banners`
--

INSERT INTO `promo_banners` (`id`, `baslik`, `alt_baslik`, `aciklama`, `buton_metni`, `buton_link`, `renk`, `resim_url`, `sira`, `durum`, `olusturma_tarihi`, `guncelleme_tarihi`) VALUES
(1, 'HAFTANIN FIRSATI', 'Kaçırılmayacak fırsatlar', NULL, 'Ürünü İncele', 'public/urunler.php', 'purple', 'http://localhost/e-ticaret/uploads/userUpload/productImage.png', 1, 'aktif', '2026-08-12 22:42:16', NULL),
(2, 'EKSTRA %10 İNDİRİM', 'Üyelere özel fırsatlar', NULL, 'Üye Ol', 'public/kayit.php', 'dark', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=500', 2, 'aktif', '2026-08-12 22:42:16', NULL),
(3, 'KARGO BEDAVA', '750 TL üzeri siparişlerde', NULL, 'Alışverişe Başla', 'public/urunler.php', 'orange', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=500', 3, 'aktif', '2026-08-12 22:42:16', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `sepet`
--

CREATE TABLE `sepet` (
  `id` int(11) NOT NULL,
  `kullanici_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `urun_id` int(11) NOT NULL,
  `varyant_id` int(11) DEFAULT NULL,
  `adet` int(11) NOT NULL DEFAULT 1,
  `birim_fiyat` decimal(10,2) NOT NULL,
  `eklenme_tarihi` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `sepet`
--

INSERT INTO `sepet` (`id`, `kullanici_id`, `session_id`, `urun_id`, `varyant_id`, `adet`, `birim_fiyat`, `eklenme_tarihi`) VALUES
(10, NULL, 'n2mtr0hehprpa1s653hst3encq', 11, NULL, 1, 257.00, '2026-08-07 22:44:57'),
(32, 5, NULL, 11, NULL, 1, 257.00, '2026-08-12 22:36:22'),
(34, 5, NULL, 10, 6, 1, 119999.00, '2026-08-16 22:47:48');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `siparisler`
--

CREATE TABLE `siparisler` (
  `id` int(11) NOT NULL,
  `siparis_no` varchar(20) NOT NULL,
  `kullanici_id` int(11) NOT NULL,
  `fatura_adres_id` int(11) NOT NULL,
  `teslimat_adres_id` int(11) NOT NULL,
  `toplam_tutar` decimal(10,2) NOT NULL,
  `odeme_yontemi` enum('kredi_karti','havale','kapida') NOT NULL,
  `odeme_durumu` enum('beklemede','onaylandi','iptal') DEFAULT 'beklemede',
  `siparis_durumu` enum('hazirlaniyor','kargoda','teslim') DEFAULT 'hazirlaniyor',
  `kargo_sirketi` varchar(100) DEFAULT NULL,
  `kargo_takip_no` varchar(100) DEFAULT NULL,
  `olusturma_tarihi` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `siparisler`
--

INSERT INTO `siparisler` (`id`, `siparis_no`, `kullanici_id`, `fatura_adres_id`, `teslimat_adres_id`, `toplam_tutar`, `odeme_yontemi`, `odeme_durumu`, `siparis_durumu`, `kargo_sirketi`, `kargo_takip_no`, `olusturma_tarihi`) VALUES
(1, 'SP-20260816-0E0DE5', 5, 1, 1, 122257.00, 'kredi_karti', 'beklemede', 'hazirlaniyor', NULL, NULL, '2026-08-16 21:48:48'),
(2, 'SP-20260816-E79923', 5, 1, 1, 122257.00, 'kredi_karti', 'beklemede', 'hazirlaniyor', NULL, NULL, '2026-08-16 21:51:42'),
(3, 'SP-20260816-637A53', 5, 1, 1, 122257.00, 'kredi_karti', 'beklemede', 'hazirlaniyor', NULL, NULL, '2026-08-16 21:53:10'),
(4, 'SP-20260816-4B1CEB', 5, 1, 1, 122257.00, 'kredi_karti', 'beklemede', 'hazirlaniyor', NULL, NULL, '2026-08-16 21:55:32'),
(8, 'SP-20260816-2B8C74', 5, 1, 1, 286.90, 'kredi_karti', 'beklemede', 'hazirlaniyor', NULL, NULL, '2026-08-16 22:01:22'),
(9, 'SP-20260816-5218CF', 5, 1, 1, 286.90, 'kredi_karti', 'beklemede', 'hazirlaniyor', NULL, NULL, '2026-08-16 22:03:33'),
(10, 'SP-20260816-94BB13', 5, 1, 1, 286.90, 'kredi_karti', 'beklemede', 'hazirlaniyor', NULL, NULL, '2026-08-16 22:05:45'),
(11, 'SP-20260816-977543', 5, 1, 1, 286.90, 'kredi_karti', 'beklemede', 'hazirlaniyor', NULL, NULL, '2026-08-16 22:06:49'),
(12, 'SP-20260816-D67B35', 5, 1, 1, 286.90, 'kredi_karti', 'beklemede', 'hazirlaniyor', NULL, NULL, '2026-08-16 22:07:09'),
(13, 'SP-20260816-C252AD', 5, 1, 1, 286.90, 'kredi_karti', 'beklemede', 'hazirlaniyor', NULL, NULL, '2026-08-16 22:09:00'),
(14, 'SP-20260816-7354CD', 5, 1, 1, 286.90, 'kredi_karti', 'beklemede', 'hazirlaniyor', NULL, NULL, '2026-08-16 22:09:11'),
(15, 'SP-20260816-8DB430', 5, 1, 1, 286.90, 'kredi_karti', 'beklemede', 'hazirlaniyor', NULL, NULL, '2026-08-16 22:10:16'),
(16, 'SP-20260816-38881D', 5, 1, 1, 286.90, 'kredi_karti', 'beklemede', 'hazirlaniyor', NULL, NULL, '2026-08-16 22:10:27'),
(17, 'SP-20260816-12403A', 5, 1, 1, 286.90, 'kredi_karti', 'beklemede', 'hazirlaniyor', NULL, NULL, '2026-08-16 22:10:41'),
(18, 'SP-20260816-2A7F1D', 5, 1, 1, 286.90, 'kredi_karti', 'beklemede', 'hazirlaniyor', NULL, NULL, '2026-08-16 22:11:30'),
(19, 'SP-20260816-B87A49', 5, 1, 1, 286.90, 'kredi_karti', 'beklemede', 'hazirlaniyor', NULL, NULL, '2026-08-16 22:12:11'),
(20, 'SP-20260816-46B28C', 5, 1, 1, 286.90, 'kredi_karti', 'beklemede', 'hazirlaniyor', NULL, NULL, '2026-08-16 22:12:36'),
(21, 'SP-20260816-5B73FD', 5, 1, 1, 286.90, 'kredi_karti', 'beklemede', 'hazirlaniyor', NULL, NULL, '2026-08-16 22:13:25'),
(22, 'SP-20260816-76B1B6', 5, 1, 1, 286.90, 'kredi_karti', 'beklemede', 'hazirlaniyor', NULL, NULL, '2026-08-16 22:13:43'),
(23, 'SP-20260816-61102E', 5, 1, 1, 286.90, 'kredi_karti', 'beklemede', 'hazirlaniyor', NULL, NULL, '2026-08-16 22:17:26');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `siparis_detay`
--

CREATE TABLE `siparis_detay` (
  `id` int(11) NOT NULL,
  `siparis_id` int(11) NOT NULL,
  `urun_id` int(11) NOT NULL,
  `varyant_id` int(11) DEFAULT NULL,
  `urun_adi` varchar(200) NOT NULL,
  `urun_kodu` varchar(50) DEFAULT NULL,
  `adet` int(11) NOT NULL,
  `birim_fiyat` decimal(10,2) NOT NULL,
  `toplam_fiyat` decimal(10,2) NOT NULL,
  `iade_notu` text DEFAULT NULL,
  `iade_durumu` enum('yok','talep_edildi','onaylandi','reddedildi','tamamlandi') DEFAULT 'yok'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `siparis_detay`
--

INSERT INTO `siparis_detay` (`id`, `siparis_id`, `urun_id`, `varyant_id`, `urun_adi`, `urun_kodu`, `adet`, `birim_fiyat`, `toplam_fiyat`, `iade_notu`, `iade_durumu`) VALUES
(1, 2, 10, 6, 'iPhone 17 Pro Max 256 GB (Apple Türkiye Garantili)', 'PRD004D9B504', 1, 122000.00, 122000.00, NULL, 'yok'),
(2, 2, 11, NULL, 'Aydınlatıcı, Leke Ve Siyah Nokta Karşıtı Kojik Asit Tonik 200 ml', 'PRDCA5F0E137', 1, 257.00, 257.00, NULL, 'yok'),
(3, 3, 10, 6, 'iPhone 17 Pro Max 256 GB (Apple Türkiye Garantili)', 'PRD004D9B504', 1, 122000.00, 122000.00, NULL, 'yok'),
(4, 3, 11, NULL, 'Aydınlatıcı, Leke Ve Siyah Nokta Karşıtı Kojik Asit Tonik 200 ml', 'PRDCA5F0E137', 1, 257.00, 257.00, NULL, 'yok'),
(5, 4, 10, 6, 'iPhone 17 Pro Max 256 GB (Apple Türkiye Garantili)', 'PRD004D9B504', 1, 122000.00, 122000.00, NULL, 'yok'),
(6, 4, 11, NULL, 'Aydınlatıcı, Leke Ve Siyah Nokta Karşıtı Kojik Asit Tonik 200 ml', 'PRDCA5F0E137', 1, 257.00, 257.00, NULL, 'yok'),
(11, 8, 11, NULL, 'Aydınlatıcı, Leke Ve Siyah Nokta Karşıtı Kojik Asit Tonik 200 ml', 'PRDCA5F0E137', 1, 257.00, 257.00, NULL, 'yok'),
(12, 9, 11, NULL, 'Aydınlatıcı, Leke Ve Siyah Nokta Karşıtı Kojik Asit Tonik 200 ml', 'PRDCA5F0E137', 1, 257.00, 257.00, NULL, 'yok'),
(13, 10, 11, NULL, 'Aydınlatıcı, Leke Ve Siyah Nokta Karşıtı Kojik Asit Tonik 200 ml', 'PRDCA5F0E137', 1, 257.00, 257.00, NULL, 'yok'),
(14, 11, 11, NULL, 'Aydınlatıcı, Leke Ve Siyah Nokta Karşıtı Kojik Asit Tonik 200 ml', 'PRDCA5F0E137', 1, 257.00, 257.00, NULL, 'yok'),
(15, 12, 11, NULL, 'Aydınlatıcı, Leke Ve Siyah Nokta Karşıtı Kojik Asit Tonik 200 ml', 'PRDCA5F0E137', 1, 257.00, 257.00, NULL, 'yok'),
(16, 13, 11, NULL, 'Aydınlatıcı, Leke Ve Siyah Nokta Karşıtı Kojik Asit Tonik 200 ml', 'PRDCA5F0E137', 1, 257.00, 257.00, NULL, 'yok'),
(17, 14, 11, NULL, 'Aydınlatıcı, Leke Ve Siyah Nokta Karşıtı Kojik Asit Tonik 200 ml', 'PRDCA5F0E137', 1, 257.00, 257.00, NULL, 'yok'),
(18, 15, 11, NULL, 'Aydınlatıcı, Leke Ve Siyah Nokta Karşıtı Kojik Asit Tonik 200 ml', 'PRDCA5F0E137', 1, 257.00, 257.00, NULL, 'yok'),
(19, 16, 11, NULL, 'Aydınlatıcı, Leke Ve Siyah Nokta Karşıtı Kojik Asit Tonik 200 ml', 'PRDCA5F0E137', 1, 257.00, 257.00, NULL, 'yok'),
(20, 17, 11, NULL, 'Aydınlatıcı, Leke Ve Siyah Nokta Karşıtı Kojik Asit Tonik 200 ml', 'PRDCA5F0E137', 1, 257.00, 257.00, NULL, 'yok'),
(21, 18, 11, NULL, 'Aydınlatıcı, Leke Ve Siyah Nokta Karşıtı Kojik Asit Tonik 200 ml', 'PRDCA5F0E137', 1, 257.00, 257.00, NULL, 'yok'),
(22, 19, 11, NULL, 'Aydınlatıcı, Leke Ve Siyah Nokta Karşıtı Kojik Asit Tonik 200 ml', 'PRDCA5F0E137', 1, 257.00, 257.00, NULL, 'yok'),
(23, 20, 11, NULL, 'Aydınlatıcı, Leke Ve Siyah Nokta Karşıtı Kojik Asit Tonik 200 ml', 'PRDCA5F0E137', 1, 257.00, 257.00, NULL, 'yok'),
(24, 21, 11, NULL, 'Aydınlatıcı, Leke Ve Siyah Nokta Karşıtı Kojik Asit Tonik 200 ml', 'PRDCA5F0E137', 1, 257.00, 257.00, NULL, 'yok'),
(25, 22, 11, NULL, 'Aydınlatıcı, Leke Ve Siyah Nokta Karşıtı Kojik Asit Tonik 200 ml', 'PRDCA5F0E137', 1, 257.00, 257.00, NULL, 'yok'),
(26, 23, 11, NULL, 'Aydınlatıcı, Leke Ve Siyah Nokta Karşıtı Kojik Asit Tonik 200 ml', 'PRDCA5F0E137', 1, 257.00, 257.00, NULL, 'yok');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `site_ayarlari`
--

CREATE TABLE `site_ayarlari` (
  `id` int(11) NOT NULL,
  `site_title` varchar(255) NOT NULL DEFAULT 'E-Ticaret Sitesi',
  `site_desc` text DEFAULT NULL,
  `site_keywords` varchar(255) DEFAULT NULL,
  `site_logo` varchar(255) DEFAULT NULL,
  `site_favicon` varchar(255) DEFAULT NULL,
  `isletme_adi` varchar(255) NOT NULL,
  `isletme_adres` text NOT NULL,
  `isletme_telefon` varchar(50) NOT NULL,
  `isletme_email` varchar(100) NOT NULL,
  `isletme_harita` text DEFAULT NULL,
  `facebook_url` varchar(255) DEFAULT NULL,
  `twitter_url` varchar(255) DEFAULT NULL,
  `instagram_url` varchar(255) DEFAULT NULL,
  `youtube_url` varchar(255) DEFAULT NULL,
  `whatsapp_url` varchar(255) DEFAULT NULL,
  `footer_copyright` varchar(255) DEFAULT NULL,
  `footer_about` text DEFAULT NULL,
  `smtp_host` varchar(100) DEFAULT NULL,
  `smtp_port` varchar(10) DEFAULT NULL,
  `smtp_username` varchar(100) DEFAULT NULL,
  `smtp_password` varchar(255) DEFAULT NULL,
  `smtp_encryption` varchar(10) DEFAULT NULL,
  `iyzico_api_key` varchar(255) DEFAULT NULL,
  `iyzico_secret_key` varchar(255) DEFAULT NULL,
  `iyzico_mode` enum('test','live') DEFAULT 'test',
  `site_durum` enum('aktif','bakim') DEFAULT 'aktif',
  `bakim_mesaji` text DEFAULT NULL,
  `guncelleme_tarihi` datetime DEFAULT NULL,
  `kredi_karti_aktif` tinyint(1) DEFAULT 1,
  `kredi_karti_baslik` varchar(100) DEFAULT 'Kredi Kartı',
  `kredi_karti_aciklama` text DEFAULT NULL,
  `kredi_karti_komisyon` decimal(5,2) DEFAULT 0.00,
  `havale_aktif` tinyint(1) DEFAULT 1,
  `havale_baslik` varchar(100) DEFAULT 'Havale / EFT',
  `havale_aciklama` text DEFAULT NULL,
  `havale_banka_adi` varchar(100) DEFAULT NULL,
  `havale_hesap_adi` varchar(100) DEFAULT NULL,
  `havale_iban` varchar(50) DEFAULT NULL,
  `havale_hesap_no` varchar(50) DEFAULT NULL,
  `kapida_aktif` tinyint(1) DEFAULT 1,
  `kapida_baslik` varchar(100) DEFAULT 'Kapıda Ödeme',
  `kapida_aciklama` text DEFAULT NULL,
  `kapida_ek_ucret` decimal(10,2) DEFAULT 0.00,
  `varsayilan_odeme` varchar(50) DEFAULT 'kredi_karti',
  `odeme_bekleme_suresi` int(11) DEFAULT 3,
  `odeme_bildirim` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `site_ayarlari`
--

INSERT INTO `site_ayarlari` (`id`, `site_title`, `site_desc`, `site_keywords`, `site_logo`, `site_favicon`, `isletme_adi`, `isletme_adres`, `isletme_telefon`, `isletme_email`, `isletme_harita`, `facebook_url`, `twitter_url`, `instagram_url`, `youtube_url`, `whatsapp_url`, `footer_copyright`, `footer_about`, `smtp_host`, `smtp_port`, `smtp_username`, `smtp_password`, `smtp_encryption`, `iyzico_api_key`, `iyzico_secret_key`, `iyzico_mode`, `site_durum`, `bakim_mesaji`, `guncelleme_tarihi`, `kredi_karti_aktif`, `kredi_karti_baslik`, `kredi_karti_aciklama`, `kredi_karti_komisyon`, `havale_aktif`, `havale_baslik`, `havale_aciklama`, `havale_banka_adi`, `havale_hesap_adi`, `havale_iban`, `havale_hesap_no`, `kapida_aktif`, `kapida_baslik`, `kapida_aciklama`, `kapida_ek_ucret`, `varsayilan_odeme`, `odeme_bekleme_suresi`, `odeme_bildirim`) VALUES
(1, 'Shoponline', 'Türkiye&#039;nin en güvenilir e-ticaret platformu', 'e-ticaret, alışveriş, online satış', '', '', 'XYZ E-Ticaret A.Ş.', 'Levent Mah. Büyükdere Cad. No:123, Şişli/İstanbul', '0850 123 45 67', 'info@site.com', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d...', 'https://facebook.com/site', 'https://twitter.com/site', 'https://instagram.com/XXX', 'https://youtube.com/site', 'https://wa.me/905551234567', 'Tüm hakları saklıdır.', 'Türkiye&#039;nin en güvenilir e-ticaret platformu. Binlerce ürün, uygun fiyatlar ve hızlı kargo ile alışverişin keyfini çıkarın.', 'smtp.gmail.com', '587', 'info@site.com', '', 'tls', '', '', 'live', 'aktif', 'Sitemiz bakımda, en kısa sürede hizmetinizdeyiz.', '2026-08-12 22:51:02', 1, 'Kredi Kartı', 'Kredi kartınızla güvenli ödeme yapın.', 0.00, 1, 'Havale / EFT', 'Havale veya EFT ile ödeme yapabilirsiniz.', '', '', '', '', 1, 'Kapıda Ödeme', 'Kapıda ödeme ile siparişinizi teslim alın.', 0.00, 'kredi_karti', 3, 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `slider`
--

CREATE TABLE `slider` (
  `id` int(11) NOT NULL,
  `baslik` varchar(255) NOT NULL,
  `alt_baslik` varchar(255) DEFAULT NULL,
  `aciklama` text DEFAULT NULL,
  `resim_url` varchar(255) NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `buton_metni` varchar(100) DEFAULT NULL,
  `sira` int(11) DEFAULT 0,
  `durum` enum('aktif','pasif') DEFAULT 'aktif',
  `olusturma_tarihi` datetime DEFAULT current_timestamp(),
  `guncelleme_tarihi` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `slider`
--

INSERT INTO `slider` (`id`, `baslik`, `alt_baslik`, `aciklama`, `resim_url`, `link_url`, `buton_metni`, `sira`, `durum`, `olusturma_tarihi`, `guncelleme_tarihi`) VALUES
(1, 'Yeni Sezon Fırsatları', 'En yeni ürünler sizleri bekliyor', 'Kaliteli ürünler, uygun fiyatlar ve hızlı kargo ile alışverişin keyfini çıkarın.', 'iphone-17-pro-max.png', 'public/urunler.php', 'Hemen Alışverişe Başla', 1, 'aktif', '2026-08-01 22:32:31', NULL),
(4, 'HOŞGELDİN İNDİRİMİNİ KAÇIRMA', 'HOSGELDIN10', '', 'slider_1785959090_6a7392b264a31.png', 'http://localhost/e-ticaret/public/index.php', 'HOSGELDIN10', 1, 'aktif', '2026-08-05 22:44:50', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `urunler`
--

CREATE TABLE `urunler` (
  `id` int(11) NOT NULL,
  `kategori_id` int(11) NOT NULL,
  `ad` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `aciklama` text DEFAULT NULL,
  `urun_kodu` varchar(50) NOT NULL,
  `fiyat` decimal(10,2) NOT NULL,
  `indirimli_fiyat` decimal(10,2) DEFAULT NULL,
  `maliyet` decimal(10,2) DEFAULT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `kritik_stok` int(11) DEFAULT 5,
  `resim_url` varchar(255) DEFAULT NULL,
  `durum` enum('aktif','pasif','stok_yok') DEFAULT 'aktif',
  `olusturma_tarihi` datetime DEFAULT current_timestamp(),
  `guncelleme_tarihi` datetime DEFAULT NULL,
  `marka_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `urunler`
--

INSERT INTO `urunler` (`id`, `kategori_id`, `ad`, `slug`, `aciklama`, `urun_kodu`, `fiyat`, `indirimli_fiyat`, `maliyet`, `stok`, `kritik_stok`, `resim_url`, `durum`, `olusturma_tarihi`, `guncelleme_tarihi`, `marka_id`) VALUES
(10, 1, 'iPhone 17 Pro Max 256 GB (Apple Türkiye Garantili)', 'iphone-17-pro-max-256-gb-apple-turkiye-garantili', 'Apple iPhone 17 Pro Max 256 GB, günlük iş akışınızdan yaratıcı projelerinize kadar her alanda üstün performansı mümkün kılar. Profesyonel kullanıcılar, içerik üreticileri ve teknoloji meraklıları için tasarlanan bu akıllı telefonla hayatınızı kolaylaştırabilirsiniz. Geleceğin teknolojilerini bugünden kullanarak iş ve eğlence dünyasında fark yaratabilirsiniz.\r\n\r\nGelişmiş Kameralar Yardımıyla Görselliği Çarpıcı Hale Getirin\r\nÜçlü kamera sistemi ve 48 megapiksel çözünürlüğe sahip iPhone 17 Pro Max, her anınızı mükemmel kalitede yakalar. Üstelik 4K Ultra HD video kayıt yapmanıza olanak sağlar. Portre modu ve görüntü sabitleyici özelliği ve zoom fonksiyonlarını kullanarak profesyonel seviyede fotoğraflar çekebilirsiniz. 18 megapiksel ön kamerasının avantajıyla selfie çekimlerinize hayran bırakabilir ve video aramalarınızı üst düzeyde gerçekleştirebilirsiniz. Otomatik odaklanma, entegre flaş ve panorama özellikleri ile her çekim koşulunda optimal sonuçlar verir.\r\n\r\nA19 Pro İşlemci ile Performansı Çok Güçlü\r\nGüncel tüm uygulamaları ve oyunları akıcı çalıştırmak için iPhone 17 Pro Max&#039;in Apple A19 Pro işlemcisini ve 4.26 GHz işlemci hızını deneyimleyebilirsiniz. 12 GB RAM kapasitesi, çoklu görevleri aynı anda verimli bir şekilde yürütebilmenizi sağlar. iPhone 17 Pro Max’in tüm medyalarınız ve uygulamalarınızı muhafaza etmek için 256 GB dahili depolama alanı mevcuttur. Yeni iOS 26 işletim sistemiyle donatılan ürün, yapay zeka desteğiyle kişiselleştirilmiş deneyimler yaşayabilmenize katkı sağlar.\r\n\r\nÇok Yönlü Şarj Desteği ve Güçlü Batarya ile Yıllara Meydan Okuyun\r\n5088 mAh Li-Ion batarya kapasitesine sahip Apple iPhone 17 Pro Max ile gün boyu kesintisiz kullanımın keyfini sürebilirsiniz. Hızlı şarj özelliği, kablosuz şarj desteği, USB Type-C bağlantı portu hızlı data transferi ve şarj işlemlerine imkan tanır.\r\n\r\nGeleceğin Bağlantı Teknolojileriyle İletişimde Hız Kesmeyin\r\nUltra hızlı internet bağlantısı için iPhone 17 Pro Max’in 5G teknolojisinden faydalanabilirsiniz. Bluetooth 6.0 sürümü sayesinde ses aksesuarlarınızı sorunsuz bağlayabilir ve gelişmiş ses kalitesi deneyimleyebilirsiniz. Wi-Fi bağlantısı ev ve ofis ağlarına hızlıca bağlanabilir, NFC desteğiyle temassız ödeme işlemleri gerçekleştirebilirsiniz.\r\n\r\nOLED Ekranla Tüm Detayları ve Renkleri Öne Çıkarın\r\nCihazın 6.9 inç OLED ekran teknolojisi, gerçek renkler, derinlik algısı ve detaylı izleme keyfi sunar. Maksimum netliğe sahip görüntüler için 2868x1320 piksel çözünürlük ve 460 ppi piksel yoğunluğuna sahiptir. 120 Hz ekran yenileme hızı, akıcı animasyonlar ve duyarlı dokunmatik deneyimi yaşatır. HDR10 desteği ile film ve video içeriklerini sinema kalitesinde izleyebilirsiniz.', 'PRD004D9B504', 122500.00, 119999.00, 116000.00, 5, 5, 'urunler/20260802_225757_6a6fa1450f2d0.jpg', 'aktif', '2026-08-02 22:57:57', NULL, 2),
(11, 14, 'Aydınlatıcı, Leke Ve Siyah Nokta Karşıtı Kojik Asit Tonik 200 ml', 'calli-el-aydinlatici-leke-ve-siyah-nokta-karsiti-kojik-asit-tonik-200-ml', '', 'PRDCA5F0E137', 300.00, 257.00, 150.00, 100, 5, 'urunler/20260805_211828_6a737e74187da.png', 'aktif', '2026-08-05 21:18:28', NULL, 3);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `urun_resimleri`
--

CREATE TABLE `urun_resimleri` (
  `id` int(11) NOT NULL,
  `urun_id` int(11) NOT NULL,
  `resim_url` varchar(255) NOT NULL,
  `resim_adi` varchar(255) DEFAULT NULL,
  `ana_resim` tinyint(1) DEFAULT 0,
  `sira` int(11) DEFAULT 0,
  `olusturma_tarihi` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `urun_resimleri`
--

INSERT INTO `urun_resimleri` (`id`, `urun_id`, `resim_url`, `resim_adi`, `ana_resim`, `sira`, `olusturma_tarihi`) VALUES
(11, 10, 'urunler/20260802_225757_6a6fa1450f2d0.jpg', '153500-1_large.jpg', 1, 0, '2026-08-02 22:57:57'),
(12, 10, 'urunler/20260802_225757_6a6fa1451a71a.webp', 'apple-iphone-17-pro-max-256gb-kozmik-turuncu-1214.webp', 0, 1, '2026-08-02 22:57:57'),
(13, 11, 'urunler/20260805_211828_6a737e74187da.png', 'aaa.png', 1, 0, '2026-08-05 21:18:28');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `urun_varyantlari`
--

CREATE TABLE `urun_varyantlari` (
  `id` int(11) NOT NULL,
  `urun_id` int(11) NOT NULL,
  `varyant_kodu` varchar(50) NOT NULL,
  `renk` varchar(30) DEFAULT NULL,
  `beden` varchar(20) DEFAULT NULL,
  `fiyat` decimal(10,2) DEFAULT NULL,
  `indirimli_fiyat` decimal(10,2) DEFAULT NULL,
  `maliyet` decimal(10,2) DEFAULT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `resim_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `urun_varyantlari`
--

INSERT INTO `urun_varyantlari` (`id`, `urun_id`, `varyant_kodu`, `renk`, `beden`, `fiyat`, `indirimli_fiyat`, `maliyet`, `stok`, `resim_url`) VALUES
(5, 10, '5464465100', 'Mavi', '', 122000.00, 119999.00, 115000.00, 5, 'varyant_1785957248_6a738b80d7203.jpg'),
(6, 10, '546464400', 'Turuncu', '', 125000.00, 122000.00, 116550.00, 3, 'varyant_1785957295_6a738baf4e1c8.webp');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `urun_yorumlari`
--

CREATE TABLE `urun_yorumlari` (
  `id` int(11) NOT NULL,
  `urun_id` int(11) NOT NULL,
  `kullanici_id` int(11) NOT NULL,
  `puan` int(11) NOT NULL CHECK (`puan` between 1 and 5),
  `yorum` text NOT NULL,
  `durum` enum('beklemede','onaylandi') DEFAULT 'beklemede',
  `olusturma_tarihi` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `favoriler`
--
ALTER TABLE `favoriler`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favori` (`kullanici_id`,`urun_id`),
  ADD KEY `urun_id` (`urun_id`),
  ADD KEY `idx_kullanici` (`kullanici_id`);

--
-- Tablo için indeksler `kampanyalar`
--
ALTER TABLE `kampanyalar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kod` (`kod`),
  ADD KEY `idx_kod` (`kod`);

--
-- Tablo için indeksler `kargo_firmalari`
--
ALTER TABLE `kargo_firmalari`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_aktif` (`aktif`),
  ADD KEY `idx_sira` (`sira`),
  ADD KEY `idx_agirlik` (`agirlik_baslangic`,`agirlik_bitis`);

--
-- Tablo için indeksler `kategoriler`
--
ALTER TABLE `kategoriler`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_ust` (`ust_id`),
  ADD KEY `idx_slug` (`slug`);

--
-- Tablo için indeksler `kullanicilar`
--
ALTER TABLE `kullanicilar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- Tablo için indeksler `kullanici_adresleri`
--
ALTER TABLE `kullanici_adresleri`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kullanici` (`kullanici_id`);

--
-- Tablo için indeksler `log_istemci`
--
ALTER TABLE `log_istemci`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kullanici` (`kullanici_id`),
  ADD KEY `idx_islem_turu` (`islem_turu`),
  ADD KEY `idx_tarih` (`islem_tarihi`),
  ADD KEY `idx_ip` (`ip_adresi`);

--
-- Tablo için indeksler `markalar`
--
ALTER TABLE `markalar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_durum` (`durum`);

--
-- Tablo için indeksler `promo_banners`
--
ALTER TABLE `promo_banners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_durum` (`durum`),
  ADD KEY `idx_sira` (`sira`);

--
-- Tablo için indeksler `sepet`
--
ALTER TABLE `sepet`
  ADD PRIMARY KEY (`id`),
  ADD KEY `urun_id` (`urun_id`),
  ADD KEY `idx_kullanici` (`kullanici_id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `sepet_ibfk_3` (`varyant_id`);

--
-- Tablo için indeksler `siparisler`
--
ALTER TABLE `siparisler`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `siparis_no` (`siparis_no`),
  ADD KEY `fatura_adres_id` (`fatura_adres_id`),
  ADD KEY `teslimat_adres_id` (`teslimat_adres_id`),
  ADD KEY `idx_kullanici` (`kullanici_id`),
  ADD KEY `idx_siparis_no` (`siparis_no`);

--
-- Tablo için indeksler `siparis_detay`
--
ALTER TABLE `siparis_detay`
  ADD PRIMARY KEY (`id`),
  ADD KEY `urun_id` (`urun_id`),
  ADD KEY `varyant_id` (`varyant_id`),
  ADD KEY `idx_siparis` (`siparis_id`);

--
-- Tablo için indeksler `site_ayarlari`
--
ALTER TABLE `site_ayarlari`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `slider`
--
ALTER TABLE `slider`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_durum` (`durum`),
  ADD KEY `idx_sira` (`sira`);

--
-- Tablo için indeksler `urunler`
--
ALTER TABLE `urunler`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `urun_kodu` (`urun_kodu`),
  ADD KEY `idx_kategori` (`kategori_id`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_urun_kodu` (`urun_kodu`);

--
-- Tablo için indeksler `urun_resimleri`
--
ALTER TABLE `urun_resimleri`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_urun` (`urun_id`),
  ADD KEY `idx_ana_resim` (`ana_resim`);

--
-- Tablo için indeksler `urun_varyantlari`
--
ALTER TABLE `urun_varyantlari`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `varyant_kodu` (`varyant_kodu`),
  ADD KEY `idx_urun` (`urun_id`),
  ADD KEY `idx_varyant_kodu` (`varyant_kodu`);

--
-- Tablo için indeksler `urun_yorumlari`
--
ALTER TABLE `urun_yorumlari`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_urun` (`urun_id`),
  ADD KEY `idx_kullanici` (`kullanici_id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `favoriler`
--
ALTER TABLE `favoriler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `kampanyalar`
--
ALTER TABLE `kampanyalar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `kargo_firmalari`
--
ALTER TABLE `kargo_firmalari`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `kategoriler`
--
ALTER TABLE `kategoriler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Tablo için AUTO_INCREMENT değeri `kullanicilar`
--
ALTER TABLE `kullanicilar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `kullanici_adresleri`
--
ALTER TABLE `kullanici_adresleri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `log_istemci`
--
ALTER TABLE `log_istemci`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `markalar`
--
ALTER TABLE `markalar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `promo_banners`
--
ALTER TABLE `promo_banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `sepet`
--
ALTER TABLE `sepet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- Tablo için AUTO_INCREMENT değeri `siparisler`
--
ALTER TABLE `siparisler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Tablo için AUTO_INCREMENT değeri `siparis_detay`
--
ALTER TABLE `siparis_detay`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Tablo için AUTO_INCREMENT değeri `site_ayarlari`
--
ALTER TABLE `site_ayarlari`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `slider`
--
ALTER TABLE `slider`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `urunler`
--
ALTER TABLE `urunler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Tablo için AUTO_INCREMENT değeri `urun_resimleri`
--
ALTER TABLE `urun_resimleri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Tablo için AUTO_INCREMENT değeri `urun_varyantlari`
--
ALTER TABLE `urun_varyantlari`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `urun_yorumlari`
--
ALTER TABLE `urun_yorumlari`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `favoriler`
--
ALTER TABLE `favoriler`
  ADD CONSTRAINT `favoriler_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favoriler_ibfk_2` FOREIGN KEY (`urun_id`) REFERENCES `urunler` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `kategoriler`
--
ALTER TABLE `kategoriler`
  ADD CONSTRAINT `kategoriler_ibfk_1` FOREIGN KEY (`ust_id`) REFERENCES `kategoriler` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `kullanici_adresleri`
--
ALTER TABLE `kullanici_adresleri`
  ADD CONSTRAINT `kullanici_adresleri_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `log_istemci`
--
ALTER TABLE `log_istemci`
  ADD CONSTRAINT `log_istemci_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `sepet`
--
ALTER TABLE `sepet`
  ADD CONSTRAINT `sepet_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sepet_ibfk_2` FOREIGN KEY (`urun_id`) REFERENCES `urunler` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sepet_ibfk_3` FOREIGN KEY (`varyant_id`) REFERENCES `urun_varyantlari` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `siparisler`
--
ALTER TABLE `siparisler`
  ADD CONSTRAINT `siparisler_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `siparisler_ibfk_2` FOREIGN KEY (`fatura_adres_id`) REFERENCES `kullanici_adresleri` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `siparisler_ibfk_3` FOREIGN KEY (`teslimat_adres_id`) REFERENCES `kullanici_adresleri` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `siparis_detay`
--
ALTER TABLE `siparis_detay`
  ADD CONSTRAINT `siparis_detay_ibfk_1` FOREIGN KEY (`siparis_id`) REFERENCES `siparisler` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `siparis_detay_ibfk_2` FOREIGN KEY (`urun_id`) REFERENCES `urunler` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `siparis_detay_ibfk_3` FOREIGN KEY (`varyant_id`) REFERENCES `urun_varyantlari` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `urunler`
--
ALTER TABLE `urunler`
  ADD CONSTRAINT `urunler_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategoriler` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `urun_resimleri`
--
ALTER TABLE `urun_resimleri`
  ADD CONSTRAINT `urun_resimleri_ibfk_1` FOREIGN KEY (`urun_id`) REFERENCES `urunler` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `urun_varyantlari`
--
ALTER TABLE `urun_varyantlari`
  ADD CONSTRAINT `urun_varyantlari_ibfk_1` FOREIGN KEY (`urun_id`) REFERENCES `urunler` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `urun_yorumlari`
--
ALTER TABLE `urun_yorumlari`
  ADD CONSTRAINT `urun_yorumlari_ibfk_1` FOREIGN KEY (`urun_id`) REFERENCES `urunler` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `urun_yorumlari_ibfk_2` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
