<?php
// =====================================================
// ADMIN DASHBOARD - admin/index.php
// =====================================================

$page_title = 'Dashboard';

// Header'ı dahil et
include 'header.php';

// =====================================================
// TEMEL İSTATİSTİKLER
// =====================================================

// Toplam kullanıcı
try {
    $stmt = $db->query("SELECT COUNT(*) as toplam FROM kullanicilar");
    $toplam_kullanici = $stmt->fetch()['toplam'] ?? 0;
} catch (PDOException $e) {
    $toplam_kullanici = 0;
}

// Aktif kullanıcı
try {
    $stmt = $db->query("SELECT COUNT(*) as toplam FROM kullanicilar WHERE durum = 'aktif'");
    $aktif_kullanici = $stmt->fetch()['toplam'] ?? 0;
} catch (PDOException $e) {
    $aktif_kullanici = 0;
}

// Toplam ürün
try {
    $stmt = $db->query("SELECT COUNT(*) as toplam FROM urunler");
    $toplam_urun = $stmt->fetch()['toplam'] ?? 0;
} catch (PDOException $e) {
    $toplam_urun = 0;
}

// Aktif ürün
try {
    $stmt = $db->query("SELECT COUNT(*) as toplam FROM urunler WHERE durum = 'aktif'");
    $aktif_urun = $stmt->fetch()['toplam'] ?? 0;
} catch (PDOException $e) {
    $aktif_urun = 0;
}

// Stokta olmayan ürünler
try {
    $stmt = $db->query("SELECT COUNT(*) as toplam FROM urunler WHERE stok <= 0");
    $stok_yok = $stmt->fetch()['toplam'] ?? 0;
} catch (PDOException $e) {
    $stok_yok = 0;
}

// Kritik stok (5 ve altı)
try {
    $stmt = $db->query("SELECT COUNT(*) as toplam FROM urunler WHERE stok <= kritik_stok AND stok > 0");
    $kritik_stok = $stmt->fetch()['toplam'] ?? 0;
} catch (PDOException $e) {
    $kritik_stok = 0;
}

// Toplam sipariş
try {
    $stmt = $db->query("SELECT COUNT(*) as toplam FROM siparisler");
    $toplam_siparis = $stmt->fetch()['toplam'] ?? 0;
} catch (PDOException $e) {
    $toplam_siparis = 0;
}

// Bekleyen sipariş
try {
    $stmt = $db->query("SELECT COUNT(*) as toplam FROM siparisler WHERE siparis_durumu = 'hazirlaniyor'");
    $bekleyen_siparis = $stmt->fetch()['toplam'] ?? 0;
} catch (PDOException $e) {
    $bekleyen_siparis = 0;
}

// Kargodaki sipariş
try {
    $stmt = $db->query("SELECT COUNT(*) as toplam FROM siparisler WHERE siparis_durumu = 'kargoda'");
    $kargodaki_siparis = $stmt->fetch()['toplam'] ?? 0;
} catch (PDOException $e) {
    $kargodaki_siparis = 0;
}

// Teslim edilen sipariş
try {
    $stmt = $db->query("SELECT COUNT(*) as toplam FROM siparisler WHERE siparis_durumu = 'teslim'");
    $teslim_siparis = $stmt->fetch()['toplam'] ?? 0;
} catch (PDOException $e) {
    $teslim_siparis = 0;
}

// Toplam gelir (onaylanmış ödemeler)
try {
    $stmt = $db->query("SELECT SUM(toplam_tutar) as toplam FROM siparisler WHERE odeme_durumu = 'onaylandi'");
    $toplam_gelir = $stmt->fetch()['toplam'] ?? 0;
} catch (PDOException $e) {
    $toplam_gelir = 0;
}

// Bekleyen ödemeler
try {
    $stmt = $db->query("SELECT SUM(toplam_tutar) as toplam FROM siparisler WHERE odeme_durumu = 'beklemede'");
    $bekleyen_odeme = $stmt->fetch()['toplam'] ?? 0;
} catch (PDOException $e) {
    $bekleyen_odeme = 0;
}

// Bugünkü siparişler
try {
    $stmt = $db->query("SELECT COUNT(*) as toplam FROM siparisler WHERE DATE(olusturma_tarihi) = CURDATE()");
    $bugun_siparis = $stmt->fetch()['toplam'] ?? 0;
} catch (PDOException $e) {
    $bugun_siparis = 0;
}

// Bugünkü gelir
try {
    $stmt = $db->query("SELECT SUM(toplam_tutar) as toplam FROM siparisler WHERE DATE(olusturma_tarihi) = CURDATE() AND odeme_durumu = 'onaylandi'");
    $bugun_gelir = $stmt->fetch()['toplam'] ?? 0;
} catch (PDOException $e) {
    $bugun_gelir = 0;
}

// Toplam kategori
try {
    $stmt = $db->query("SELECT COUNT(*) as toplam FROM kategoriler");
    $toplam_kategori = $stmt->fetch()['toplam'] ?? 0;
} catch (PDOException $e) {
    $toplam_kategori = 0;
}

// Toplam yorum
try {
    $stmt = $db->query("SELECT COUNT(*) as toplam FROM urun_yorumlari");
    $toplam_yorum = $stmt->fetch()['toplam'] ?? 0;
} catch (PDOException $e) {
    $toplam_yorum = 0;
}

// Bekleyen yorum
try {
    $stmt = $db->query("SELECT COUNT(*) as toplam FROM urun_yorumlari WHERE durum = 'beklemede'");
    $bekleyen_yorum = $stmt->fetch()['toplam'] ?? 0;
} catch (PDOException $e) {
    $bekleyen_yorum = 0;
}

// =====================================================
// AYLIK SATIŞ VERİLERİ (Grafik için)
// =====================================================
$aylik_satis = [];
$aylik_gelir = [];

for ($i = 1; $i <= 12; $i++) {
    $aylik_satis[$i] = 0;
    $aylik_gelir[$i] = 0;
}

try {
    $stmt = $db->query("
        SELECT 
            MONTH(olusturma_tarihi) as ay,
            COUNT(*) as siparis_sayisi,
            SUM(toplam_tutar) as gelir
        FROM siparisler 
        WHERE odeme_durumu = 'onaylandi' 
        AND YEAR(olusturma_tarihi) = YEAR(CURDATE())
        GROUP BY MONTH(olusturma_tarihi)
    ");
    while ($row = $stmt->fetch()) {
        $aylik_satis[$row['ay']] = $row['siparis_sayisi'];
        $aylik_gelir[$row['ay']] = $row['gelir'];
    }
} catch (PDOException $e) {}

// =====================================================
// HAFTALIK SATIŞ VERİLERİ (Bar grafik için)
// =====================================================
$gunler = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
$haftalik_satis = array_fill(0, 7, 0);

try {
    $stmt = $db->query("
        SELECT 
            WEEKDAY(olusturma_tarihi) as gun,
            COUNT(*) as siparis_sayisi
        FROM siparisler 
        WHERE odeme_durumu = 'onaylandi' 
        AND DATE(olusturma_tarihi) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY WEEKDAY(olusturma_tarihi)
    ");
    while ($row = $stmt->fetch()) {
        $haftalik_satis[$row['gun']] = $row['siparis_sayisi'];
    }
} catch (PDOException $e) {}

// =====================================================
// SON SİPARİŞLER
// =====================================================
try {
    $stmt = $db->prepare("
        SELECT s.*, u.ad, u.soyad, u.email 
        FROM siparisler s
        LEFT JOIN kullanicilar u ON s.kullanici_id = u.id
        ORDER BY s.olusturma_tarihi DESC
        LIMIT 8
    ");
    $stmt->execute();
    $son_siparisler = $stmt->fetchAll();
} catch (PDOException $e) {
    $son_siparisler = [];
}

// =====================================================
// KRİTİK STOKTAKİ ÜRÜNLER
// =====================================================
try {
    $stmt = $db->prepare("
        SELECT u.*, k.ad as kategori_adi 
        FROM urunler u
        LEFT JOIN kategoriler k ON u.kategori_id = k.id
        WHERE u.stok <= u.kritik_stok AND u.stok > 0
        ORDER BY u.stok ASC
        LIMIT 5
    ");
    $stmt->execute();
    $kritik_urunler = $stmt->fetchAll();
} catch (PDOException $e) {
    $kritik_urunler = [];
}

// =====================================================
// EN ÇOK SATAN ÜRÜNLER
// =====================================================
try {
    $stmt = $db->prepare("
        SELECT u.ad, u.urun_kodu, u.fiyat, u.resim_url, 
               COUNT(sd.id) as satis_adeti,
               SUM(sd.adet) as toplam_adet
        FROM siparis_detay sd
        LEFT JOIN urunler u ON sd.urun_id = u.id
        GROUP BY sd.urun_id
        ORDER BY toplam_adet DESC
        LIMIT 5
    ");
    $stmt->execute();
    $cok_satanlar = $stmt->fetchAll();
} catch (PDOException $e) {
    $cok_satanlar = [];
}

// =====================================================
// SON ZİYARETÇİLER (Demo veri - gerçek log tablosu yoksa)
// =====================================================
$son_ziyaretciler = [];
try {
    // Eğer log_istemci tablosu varsa
    $stmt = $db->prepare("
        SELECT * FROM log_istemci 
        ORDER BY islem_tarihi DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $son_ziyaretciler = $stmt->fetchAll();
} catch (PDOException $e) {
    // Tablo yoksa boş bırak
    $son_ziyaretciler = [];
}
?>

<!-- ============================================ -->
<!-- İSTATİSTİK KARTLARI -->
<!-- ============================================ -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: #6C63FF;">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo number_format($toplam_siparis); ?></h3>
            <p>Toplam Sipariş</p>
            <span class="stat-change up">
                <i class="fas fa-arrow-up"></i> Bugün +<?php echo $bugun_siparis; ?>
            </span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #2ECC71;">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo number_format($toplam_gelir, 2, ',', '.'); ?> ₺</h3>
            <p>Toplam Gelir</p>
            <span class="stat-change up">
                <i class="fas fa-arrow-up"></i> Bugün +<?php echo number_format($bugun_gelir, 2, ',', '.'); ?> ₺
            </span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #F39C12;">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo number_format($toplam_urun); ?></h3>
            <p>Toplam Ürün</p>
            <span class="stat-change <?php echo $stok_yok > 0 ? 'down' : 'up'; ?>">
                <?php echo $aktif_urun; ?> aktif, <?php echo $stok_yok; ?> stok yok
            </span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #FF6B6B;">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo number_format($toplam_kullanici); ?></h3>
            <p>Toplam Kullanıcı</p>
            <span class="stat-change up">
                <i class="fas fa-arrow-up"></i> <?php echo $aktif_kullanici; ?> aktif
            </span>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- İKİNCİL İSTATİSTİKLER -->
<!-- ============================================ -->
<div class="stats-grid-secondary">
    <div class="stat-card-small">
        <div class="stat-icon-small" style="background: #E74C3C;">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info-small">
            <span class="stat-number"><?php echo $bekleyen_siparis; ?></span>
            <span class="stat-label">Bekleyen Sipariş</span>
        </div>
    </div>
    
    <div class="stat-card-small">
        <div class="stat-icon-small" style="background: #3498DB;">
            <i class="fas fa-truck"></i>
        </div>
        <div class="stat-info-small">
            <span class="stat-number"><?php echo $kargodaki_siparis; ?></span>
            <span class="stat-label">Kargodaki Sipariş</span>
        </div>
    </div>
    
    <div class="stat-card-small">
        <div class="stat-icon-small" style="background: #2ECC71;">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info-small">
            <span class="stat-number"><?php echo $teslim_siparis; ?></span>
            <span class="stat-label">Teslim Edilen</span>
        </div>
    </div>
    
    <div class="stat-card-small">
        <div class="stat-icon-small" style="background: #F39C12;">
            <i class="fas fa-star"></i>
        </div>
        <div class="stat-info-small">
            <span class="stat-number"><?php echo $toplam_yorum; ?></span>
            <span class="stat-label">Toplam Yorum</span>
            <?php if ($bekleyen_yorum > 0): ?>
                <span class="stat-badge">+<?php echo $bekleyen_yorum; ?> onay bekliyor</span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="stat-card-small">
        <div class="stat-icon-small" style="background: #9B59B6;">
            <i class="fas fa-tags"></i>
        </div>
        <div class="stat-info-small">
            <span class="stat-number"><?php echo $toplam_kategori; ?></span>
            <span class="stat-label">Kategori</span>
        </div>
    </div>
    
    <div class="stat-card-small">
        <div class="stat-icon-small" style="background: #E67E22;">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-info-small">
            <span class="stat-number" style="color: #E67E22;"><?php echo $kritik_stok; ?></span>
            <span class="stat-label">Kritik Stok</span>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- GRAFİKLER -->
<!-- ============================================ -->
<div class="charts-grid">
    
    <!-- Aylık Satış Grafiği -->
    <div class="chart-card">
        <div class="chart-header">
            <h3>Aylık Satış ve Gelir</h3>
            <div class="chart-actions">
                <span><?php echo date('Y'); ?> Yılı</span>
            </div>
        </div>
        <div class="chart-body">
            <canvas id="monthlyChart" height="200"></canvas>
        </div>
    </div>
    
    <!-- Haftalık Satış Grafiği -->
    <div class="chart-card">
        <div class="chart-header">
            <h3>Haftalık Satış</h3>
            <div class="chart-actions">
                <span>Son 7 Gün</span>
            </div>
        </div>
        <div class="chart-body">
            <canvas id="weeklyChart" height="200"></canvas>
        </div>
    </div>
    
</div>

<!-- ============================================ -->
<!-- KRİTİK STOK & ÇOK SATANLAR -->
<!-- ============================================ -->
<div class="charts-grid-2">
    
    <!-- Kritik Stoktaki Ürünler -->
    <div class="chart-card">
        <div class="chart-header">
            <h3><i class="fas fa-exclamation-triangle" style="color: #E67E22;"></i> Kritik Stoktaki Ürünler</h3>
            <a href="<?php echo SITE_URL; ?>admin/urunler.php?stok=kritik" class="view-all">Tümünü Gör →</a>
        </div>
        <div class="chart-body">
            <?php if (!empty($kritik_urunler)): ?>
                <div class="stock-list">
                    <?php foreach ($kritik_urunler as $urun): ?>
                        <div class="stock-item">
                            <div class="stock-info">
                                <span class="stock-name"><?php echo $urun['ad']; ?></span>
                                <span class="stock-category"><?php echo $urun['kategori_adi'] ?? 'Kategori yok'; ?></span>
                            </div>
                            <div class="stock-status">
                                <span class="stock-count <?php echo $urun['stok'] <= 2 ? 'danger' : 'warning'; ?>">
                                    <?php echo $urun['stok']; ?> adet
                                </span>
                                <div class="stock-bar">
                                    <div class="stock-bar-fill" style="width: <?php echo ($urun['stok'] / $urun['kritik_stok']) * 100; ?>%;"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle" style="color: #2ECC71;"></i>
                    <p>Kritik stokta ürün bulunmuyor.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- En Çok Satanlar -->
    <div class="chart-card">
        <div class="chart-header">
            <h3><i class="fas fa-fire" style="color: #FF6B6B;"></i> En Çok Satanlar</h3>
            <a href="<?php echo SITE_URL; ?>admin/satis-raporlari.php" class="view-all">Tümünü Gör →</a>
        </div>
        <div class="chart-body">
            <?php if (!empty($cok_satanlar)): ?>
                <div class="top-selling-list">
                    <?php foreach ($cok_satanlar as $index => $urun): ?>
                        <div class="top-selling-item">
                            <div class="top-selling-rank">#<?php echo $index + 1; ?></div>
                            <div class="top-selling-info">
                                <span class="top-selling-name"><?php echo $urun['ad'] ?? 'Ürün silinmiş'; ?></span>
                                <span class="top-selling-code"><?php echo $urun['urun_kodu'] ?? ''; ?></span>
                            </div>
                            <div class="top-selling-stats">
                                <span class="top-selling-count">
                                    <i class="fas fa-shopping-cart"></i> <?php echo $urun['toplam_adet'] ?? 0; ?>
                                </span>
                                <span class="top-selling-price">
                                    <?php echo number_format($urun['fiyat'] ?? 0, 2, ',', '.'); ?> ₺
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-chart-line"></i>
                    <p>Henüz satış verisi yok.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
</div>

<!-- ============================================ -->
<!-- SON SİPARİŞLER TABLOSU -->
<!-- ============================================ -->
<div class="table-card">
    <div class="table-header">
        <h3><i class="fas fa-clock"></i> Son Siparişler</h3>
        <a href="<?php echo SITE_URL; ?>admin/siparisler.php" class="view-all">Tüm Siparişler →</a>
    </div>
    
    <?php if (!empty($son_siparisler)): ?>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Sipariş No</th>
                    <th>Müşteri</th>
                    <th>Tutar</th>
                    <th>Durum</th>
                    <th>Ödeme</th>
                    <th>Tarih</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($son_siparisler as $siparis): ?>
                <tr>
                    <td><strong>#<?php echo $siparis['siparis_no']; ?></strong></td>
                    <td>
                        <?php echo $siparis['ad'] . ' ' . $siparis['soyad']; ?>
                        <br>
                        <small style="color: var(--gray);"><?php echo $siparis['email']; ?></small>
                    </td>
                    <td><strong><?php echo number_format($siparis['toplam_tutar'], 2, ',', '.'); ?> ₺</strong></td>
                    <td>
                        <span class="status-badge <?php echo $siparis['siparis_durumu']; ?>">
                            <?php 
                            $durumlar = [
                                'hazirlaniyor' => '<i class="fas fa-spinner fa-spin"></i> Hazırlanıyor',
                                'kargoda' => '<i class="fas fa-truck"></i> Kargoda',
                                'teslim' => '<i class="fas fa-check-circle"></i> Teslim Edildi'
                            ];
                            echo $durumlar[$siparis['siparis_durumu']] ?? $siparis['siparis_durumu'];
                            ?>
                        </span>
                    </td>
                    <td>
                        <span class="payment-badge <?php echo $siparis['odeme_durumu']; ?>">
                            <?php 
                            $odeme_durumlar = [
                                'beklemede' => '<i class="fas fa-clock"></i> Bekliyor',
                                'onaylandi' => '<i class="fas fa-check"></i> Onaylandı',
                                'iptal' => '<i class="fas fa-times"></i> İptal'
                            ];
                            echo $odeme_durumlar[$siparis['odeme_durumu']] ?? $siparis['odeme_durumu'];
                            ?>
                        </span>
                    </td>
                    <td><?php echo date('d.m.Y H:i', strtotime($siparis['olusturma_tarihi'])); ?></td>
                    <td>
                        <a href="<?php echo SITE_URL; ?>admin/siparis-detay.php?id=<?php echo $siparis['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <p>Henüz sipariş yok.</p>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================ -->
<!-- CHART.JS LİBRARY -->
<!-- ============================================ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // AYLIK SATIŞ VE GELİR GRAFİĞİ
    // ============================================
    const monthlyCtx = document.getElementById('monthlyChart');
    if (monthlyCtx) {
        const aylar = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 
                       'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
        
        const satisVerileri = [
            <?php for ($i = 1; $i <= 12; $i++): ?>
                <?php echo $aylik_satis[$i] ?? 0; ?>,
            <?php endfor; ?>
        ];
        
        const gelirVerileri = [
            <?php for ($i = 1; $i <= 12; $i++): ?>
                <?php echo $aylik_gelir[$i] ?? 0; ?>,
            <?php endfor; ?>
        ];
        
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: aylar,
                datasets: [
                    {
                        label: 'Sipariş Sayısı',
                        data: satisVerileri,
                        borderColor: '#6C63FF',
                        backgroundColor: 'rgba(108, 99, 255, 0.1)',
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y1'
                    },
                    {
                        label: 'Gelir (₺)',
                        data: gelirVerileri,
                        borderColor: '#2ECC71',
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y2'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    },
                    y2: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('tr-TR') + ' ₺';
                            }
                        }
                    }
                }
            }
        });
    }
    
    // ============================================
    // HAFTALIK SATIŞ GRAFİĞİ
    // ============================================
    const weeklyCtx = document.getElementById('weeklyChart');
    if (weeklyCtx) {
        const gunler = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
        const haftalikVeri = [
            <?php for ($i = 0; $i < 7; $i++): ?>
                <?php echo $haftalik_satis[$i] ?? 0; ?>,
            <?php endfor; ?>
        ];
        
        new Chart(weeklyCtx, {
            type: 'bar',
            data: {
                labels: gunler,
                datasets: [{
                    label: 'Sipariş Sayısı',
                    data: haftalikVeri,
                    backgroundColor: [
                        'rgba(108, 99, 255, 0.8)',
                        'rgba(108, 99, 255, 0.7)',
                        'rgba(108, 99, 255, 0.6)',
                        'rgba(108, 99, 255, 0.7)',
                        'rgba(108, 99, 255, 0.8)',
                        'rgba(108, 99, 255, 0.9)',
                        'rgba(108, 99, 255, 0.5)'
                    ],
                    borderColor: '#6C63FF',
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
    
});
</script>

<!-- ============================================ -->
<!-- DASHBOARD ÖZEL CSS -->
<!-- ============================================ -->
<style>
/* ============================================
   İKİNCİL İSTATİSTİK KARTLARI
   ============================================ */
.stats-grid-secondary {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 16px;
    margin-bottom: 30px;
}

.stat-card-small {
    background: var(--white);
    border-radius: var(--radius);
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: var(--shadow);
    transition: var(--transition);
}

.stat-card-small:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
}

.stat-icon-small {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--white);
    font-size: 18px;
    flex-shrink: 0;
}

.stat-info-small {
    flex: 1;
}

.stat-number {
    display: block;
    font-size: 20px;
    font-weight: 800;
    line-height: 1.2;
}

.stat-label {
    display: block;
    font-size: 12px;
    color: var(--gray);
}

.stat-badge {
    display: inline-block;
    font-size: 10px;
    background: #FFF3CD;
    color: #856404;
    padding: 1px 8px;
    border-radius: 50px;
    margin-top: 2px;
}

/* ============================================
   KRİTİK STOK LİSTESİ
   ============================================ */
.stock-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.stock-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 14px;
    background: var(--light-gray);
    border-radius: 10px;
    transition: var(--transition);
}

.stock-item:hover {
    background: #E8E8F0;
}

.stock-info {
    display: flex;
    flex-direction: column;
}

.stock-name {
    font-weight: 600;
    font-size: 14px;
}

.stock-category {
    font-size: 12px;
    color: var(--gray);
}

.stock-status {
    text-align: right;
    min-width: 80px;
}

.stock-count {
    display: block;
    font-weight: 700;
    font-size: 14px;
}

.stock-count.danger {
    color: #E74C3C;
}

.stock-count.warning {
    color: #F39C12;
}

.stock-bar {
    width: 100%;
    height: 4px;
    background: #E8E8F0;
    border-radius: 4px;
    margin-top: 4px;
    overflow: hidden;
}

.stock-bar-fill {
    height: 100%;
    background: var(--primary);
    border-radius: 4px;
    transition: width 0.5s ease;
}

/* ============================================
   EN ÇOK SATANLAR
   ============================================ */
.top-selling-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.top-selling-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 10px 14px;
    background: var(--light-gray);
    border-radius: 10px;
    transition: var(--transition);
}

.top-selling-item:hover {
    background: #E8E8F0;
}

.top-selling-rank {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: var(--primary);
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 13px;
    flex-shrink: 0;
}

.top-selling-rank:nth-child(1) { background: #FFD700; }
.top-selling-rank:nth-child(2) { background: #C0C0C0; }
.top-selling-rank:nth-child(3) { background: #CD7F32; }

.top-selling-info {
    flex: 1;
}

.top-selling-name {
    display: block;
    font-weight: 600;
    font-size: 14px;
}

.top-selling-code {
    display: block;
    font-size: 12px;
    color: var(--gray);
}

.top-selling-stats {
    display: flex;
    align-items: center;
    gap: 16px;
}

.top-selling-count {
    font-size: 13px;
    color: var(--gray);
}

.top-selling-count i {
    color: var(--primary);
}

.top-selling-price {
    font-weight: 700;
    font-size: 14px;
    color: var(--primary);
}

/* ============================================
   TABLO RESPONSIVE
   ============================================ */
.table-responsive {
    overflow-x: auto;
}

/* ============================================
   PAYMENT BADGE
   ============================================ */
.payment-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 600;
}

.payment-badge.beklemede {
    background: #FFF3CD;
    color: #856404;
}

.payment-badge.onaylandi {
    background: #D4EDDA;
    color: #155724;
}

.payment-badge.iptal {
    background: #F8D7DA;
    color: #721C24;
}

/* ============================================
   RESPONSIVE
   ============================================ */
@media (max-width: 1200px) {
    .stats-grid-secondary {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .stats-grid-secondary {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stat-card-small {
        padding: 12px 16px;
    }
    
    .stat-number {
        font-size: 16px;
    }
    
    .stock-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .stock-status {
        width: 100%;
        text-align: left;
    }
    
    .top-selling-item {
        flex-wrap: wrap;
    }
    
    .top-selling-stats {
        width: 100%;
        justify-content: flex-start;
        padding-left: 44px;
    }
}

@media (max-width: 480px) {
    .stats-grid-secondary {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
// Footer'ı dahil et
include 'footer.php';
?>