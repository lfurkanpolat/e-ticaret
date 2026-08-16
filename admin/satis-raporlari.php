<?php
// =====================================================
// SATIŞ RAPORLARI - admin/satis-raporlari.php
// =====================================================

// =====================================================
// NORMAL SAYFA
// =====================================================
$page_title = 'Satış Raporları';
include 'header.php';

// Session mesajlarını al ve temizle
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error']);
unset($_SESSION['success']);

// =====================================================
// FİLTRELEME
// =====================================================
$periyot = isset($_GET['periyot']) ? clean($_GET['periyot']) : 'aylik';
$tarih_baslangic = isset($_GET['tarih_baslangic']) ? clean($_GET['tarih_baslangic']) : date('Y-m-d', strtotime('-30 days'));
$tarih_bitis = isset($_GET['tarih_bitis']) ? clean($_GET['tarih_bitis']) : date('Y-m-d');

// =====================================================
// RAPOR VERİLERİNİ ÇEK
// =====================================================

// 1. GENEL İSTATİSTİKLER
try {
    // Toplam sipariş
    $stmt = $db->query("SELECT COUNT(*) as toplam FROM siparisler");
    $toplam_siparis = $stmt->fetch()['toplam'] ?? 0;
    
    // Toplam gelir (onaylanmış)
    $stmt = $db->query("SELECT SUM(toplam_tutar) as toplam FROM siparisler WHERE odeme_durumu = 'onaylandi'");
    $toplam_gelir = $stmt->fetch()['toplam'] ?? 0;
    
    // Toplam sipariş adedi
    $stmt = $db->query("SELECT SUM(adet) as toplam FROM siparis_detay");
    $toplam_urun = $stmt->fetch()['toplam'] ?? 0;
    
    // Ortalama sipariş tutarı
    $stmt = $db->query("SELECT AVG(toplam_tutar) as ortalama FROM siparisler WHERE odeme_durumu = 'onaylandi'");
    $ortalama_tutar = $stmt->fetch()['ortalama'] ?? 0;
} catch (PDOException $e) {
    $toplam_siparis = 0;
    $toplam_gelir = 0;
    $toplam_urun = 0;
    $ortalama_tutar = 0;
    $error = 'Veritabanı hatası: ' . $e->getMessage();
}

// 2. PERİYODA GÖRE SATIŞ VERİLERİ
try {
    if ($periyot == 'gunluk') {
        // Günlük satışlar (son 30 gün)
        $sql = "
            SELECT 
                DATE(olusturma_tarihi) as tarih,
                COUNT(*) as siparis_sayisi,
                SUM(toplam_tutar) as gelir,
                COUNT(DISTINCT kullanici_id) as musteri_sayisi
            FROM siparisler 
            WHERE odeme_durumu = 'onaylandi' 
                AND DATE(olusturma_tarihi) BETWEEN ? AND ?
            GROUP BY DATE(olusturma_tarihi)
            ORDER BY tarih ASC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$tarih_baslangic, $tarih_bitis]);
        $satis_verileri = $stmt->fetchAll();
        
        $grafik_etiketleri = [];
        $grafik_siparis = [];
        $grafik_gelir = [];
        
        foreach ($satis_verileri as $row) {
            $grafik_etiketleri[] = date('d.m', strtotime($row['tarih']));
            $grafik_siparis[] = $row['siparis_sayisi'];
            $grafik_gelir[] = $row['gelir'];
        }
        
    } elseif ($periyot == 'haftalik') {
        // Haftalık satışlar (son 12 hafta)
        $sql = "
            SELECT 
                YEARWEEK(olusturma_tarihi, 1) as hafta,
                DATE(MIN(olusturma_tarihi)) as hafta_baslangic,
                COUNT(*) as siparis_sayisi,
                SUM(toplam_tutar) as gelir,
                COUNT(DISTINCT kullanici_id) as musteri_sayisi
            FROM siparisler 
            WHERE odeme_durumu = 'onaylandi'
                AND olusturma_tarihi >= DATE_SUB(NOW(), INTERVAL 12 WEEK)
            GROUP BY YEARWEEK(olusturma_tarihi, 1)
            ORDER BY hafta ASC
            LIMIT 12
        ";
        $stmt = $db->query($sql);
        $satis_verileri = $stmt->fetchAll();
        
        $grafik_etiketleri = [];
        $grafik_siparis = [];
        $grafik_gelir = [];
        
        foreach ($satis_verileri as $row) {
            $grafik_etiketleri[] = date('d.m', strtotime($row['hafta_baslangic']));
            $grafik_siparis[] = $row['siparis_sayisi'];
            $grafik_gelir[] = $row['gelir'];
        }
        
    } else { // aylik
        // Aylık satışlar (son 12 ay)
        $sql = "
            SELECT 
                DATE_FORMAT(olusturma_tarihi, '%Y-%m') as ay,
                COUNT(*) as siparis_sayisi,
                SUM(toplam_tutar) as gelir,
                COUNT(DISTINCT kullanici_id) as musteri_sayisi
            FROM siparisler 
            WHERE odeme_durumu = 'onaylandi'
                AND olusturma_tarihi >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(olusturma_tarihi, '%Y-%m')
            ORDER BY ay ASC
        ";
        $stmt = $db->query($sql);
        $satis_verileri = $stmt->fetchAll();
        
        $grafik_etiketleri = [];
        $grafik_siparis = [];
        $grafik_gelir = [];
        
        $aylar = [
            '01' => 'Ocak', '02' => 'Şubat', '03' => 'Mart', '04' => 'Nisan',
            '05' => 'Mayıs', '06' => 'Haziran', '07' => 'Temmuz', '08' => 'Ağustos',
            '09' => 'Eylül', '10' => 'Ekim', '11' => 'Kasım', '12' => 'Aralık'
        ];
        
        foreach ($satis_verileri as $row) {
            $ay_parca = explode('-', $row['ay']);
            $grafik_etiketleri[] = $aylar[$ay_parca[1]] . ' ' . $ay_parca[0];
            $grafik_siparis[] = $row['siparis_sayisi'];
            $grafik_gelir[] = $row['gelir'];
        }
    }
} catch (PDOException $e) {
    $satis_verileri = [];
    $grafik_etiketleri = [];
    $grafik_siparis = [];
    $grafik_gelir = [];
    $error = 'Veritabanı hatası: ' . $e->getMessage();
}

// 3. EN ÇOK SATAN ÜRÜNLER
try {
    $stmt = $db->query("
        SELECT 
            u.ad as urun_adi,
            u.urun_kodu,
            SUM(sd.adet) as toplam_satis,
            SUM(sd.toplam_fiyat) as toplam_gelir,
            COUNT(DISTINCT sd.siparis_id) as siparis_sayisi
        FROM siparis_detay sd
        LEFT JOIN urunler u ON sd.urun_id = u.id
        LEFT JOIN siparisler s ON sd.siparis_id = s.id
        WHERE s.odeme_durumu = 'onaylandi'
        GROUP BY sd.urun_id
        ORDER BY toplam_satis DESC
        LIMIT 10
    ");
    $cok_satanlar = $stmt->fetchAll();
} catch (PDOException $e) {
    $cok_satanlar = [];
}

// 4. EN ÇOK SATAN KATEGORİLER
try {
    $stmt = $db->query("
        SELECT 
            k.ad as kategori_adi,
            COUNT(sd.id) as satis_sayisi,
            SUM(sd.adet) as toplam_adet,
            SUM(sd.toplam_fiyat) as toplam_gelir
        FROM siparis_detay sd
        LEFT JOIN urunler u ON sd.urun_id = u.id
        LEFT JOIN kategoriler k ON u.kategori_id = k.id
        LEFT JOIN siparisler s ON sd.siparis_id = s.id
        WHERE s.odeme_durumu = 'onaylandi'
        GROUP BY u.kategori_id
        ORDER BY toplam_adet DESC
        LIMIT 5
    ");
    $kategori_satis = $stmt->fetchAll();
} catch (PDOException $e) {
    $kategori_satis = [];
}

// 5. ÖDEME YÖNTEMLERİNE GÖRE DAĞILIM
try {
    $stmt = $db->query("
        SELECT 
            odeme_yontemi,
            COUNT(*) as siparis_sayisi,
            SUM(toplam_tutar) as toplam_gelir
        FROM siparisler
        WHERE odeme_durumu = 'onaylandi'
        GROUP BY odeme_yontemi
    ");
    $odeme_dagilim = $stmt->fetchAll();
} catch (PDOException $e) {
    $odeme_dagilim = [];
}

// 6. AYLIK SİPARİŞ DURUMU DAĞILIMI
try {
    $stmt = $db->query("
        SELECT 
            siparis_durumu,
            COUNT(*) as sayi
        FROM siparisler
        GROUP BY siparis_durumu
    ");
    $durum_dagilim = $stmt->fetchAll();
} catch (PDOException $e) {
    $durum_dagilim = [];
}

// Para birimi formatı
function para_format($deger) {
    return number_format($deger, 2, ',', '.') . ' ₺';
}

// Yüzde hesaplama
function yuzde_hesapla($deger, $toplam) {
    if ($toplam == 0) return 0;
    return round(($deger / $toplam) * 100, 1);
}
?>

<!-- ============================================ -->
<!-- SAYFA BAŞLIĞI -->
<!-- ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-chart-bar"></i> Satış Raporları</h2>
    </div>
</div>

<!-- ============================================ -->
<!-- HATA MESAJLARI -->
<!-- ============================================ -->
<?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- ============================================ -->
<!-- FİLTRELEME -->
<!-- ============================================ -->
<div class="filter-section">
    <form method="GET" class="filter-form">
        <div class="filter-row">
            <div class="filter-item">
                <select name="periyot">
                    <option value="gunluk" <?php echo $periyot == 'gunluk' ? 'selected' : ''; ?>>Günlük</option>
                    <option value="haftalik" <?php echo $periyot == 'haftalik' ? 'selected' : ''; ?>>Haftalık</option>
                    <option value="aylik" <?php echo $periyot == 'aylik' ? 'selected' : ''; ?>>Aylık</option>
                </select>
            </div>
            <?php if ($periyot == 'gunluk'): ?>
                <div class="filter-item">
                    <input type="date" name="tarih_baslangic" value="<?php echo $tarih_baslangic; ?>">
                </div>
                <div class="filter-item">
                    <input type="date" name="tarih_bitis" value="<?php echo $tarih_bitis; ?>">
                </div>
            <?php endif; ?>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Göster</button>
                <a href="<?php echo SITE_URL; ?>admin/satis-raporlari.php" class="btn btn-outline"><i class="fas fa-undo"></i> Temizle</a>
            </div>
        </div>
    </form>
</div>

<!-- ============================================ -->
<!-- GENEL İSTATİSTİKLER -->
<!-- ============================================ -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: #6C63FF;">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo number_format($toplam_siparis); ?></h3>
            <p>Toplam Sipariş</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #2ECC71;">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo para_format($toplam_gelir); ?></h3>
            <p>Toplam Gelir</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #F39C12;">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo number_format($toplam_urun); ?></h3>
            <p>Satılan Ürün</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #FF6B6B;">
            <i class="fas fa-receipt"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo para_format($ortalama_tutar); ?></h3>
            <p>Ortalama Sipariş</p>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- SATIŞ GRAFİĞİ -->
<!-- ============================================ -->
<div class="chart-card">
    <div class="chart-header">
        <h3>Satış Grafiği</h3>
        <div class="chart-actions">
            <span><?php echo ucfirst($periyot); ?> Bazında</span>
        </div>
    </div>
    <div class="chart-body">
        <canvas id="salesChart" height="200"></canvas>
    </div>
</div>

<!-- ============================================ -->
<!-- SİPARİŞ DURUMU VE ÖDEME DAĞILIMI -->
<!-- ============================================ -->
<div class="charts-grid-2">
    
    <!-- Sipariş Durumu Dağılımı -->
    <div class="chart-card">
        <div class="chart-header">
            <h3>Sipariş Durumu Dağılımı</h3>
        </div>
        <div class="chart-body">
            <canvas id="statusChart" height="180"></canvas>
        </div>
    </div>
    
    <!-- Ödeme Yöntemi Dağılımı -->
    <div class="chart-card">
        <div class="chart-header">
            <h3>Ödeme Yöntemi Dağılımı</h3>
        </div>
        <div class="chart-body">
            <canvas id="paymentChart" height="180"></canvas>
        </div>
    </div>
    
</div>

<!-- ============================================ -->
<!-- EN ÇOK SATAN ÜRÜNLER -->
<!-- ============================================ -->
<div class="table-card">
    <div class="table-header">
        <h3><i class="fas fa-fire" style="color: #FF6B6B;"></i> En Çok Satan Ürünler</h3>
    </div>
    
    <?php if (!empty($cok_satanlar)): ?>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ürün</th>
                    <th>Kod</th>
                    <th>Toplam Satış</th>
                    <th>Sipariş Sayısı</th>
                    <th>Toplam Gelir</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cok_satanlar as $index => $urun): ?>
                <tr>
                    <td><span class="rank-badge <?php echo $index < 3 ? 'top' : ''; ?>">#<?php echo $index + 1; ?></span></td>
                    <td><strong><?php echo $urun['urun_adi'] ?? 'Ürün silinmiş'; ?></strong></td>
                    <td><code><?php echo $urun['urun_kodu'] ?? '-'; ?></code></td>
                    <td><strong><?php echo number_format($urun['toplam_satis']); ?></strong></td>
                    <td><?php echo number_format($urun['siparis_sayisi']); ?></td>
                    <td><strong style="color: var(--primary);"><?php echo para_format($urun['toplam_gelir']); ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-chart-line"></i>
        <p>Henüz satış verisi yok.</p>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================ -->
<!-- KATEGORİ SATIŞLARI -->
<!-- ============================================ -->
<div class="table-card">
    <div class="table-header">
        <h3><i class="fas fa-tags"></i> Kategori Bazında Satışlar</h3>
    </div>
    
    <?php if (!empty($kategori_satis)): ?>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th>Satış Sayısı</th>
                    <th>Toplam Adet</th>
                    <th>Toplam Gelir</th>
                    <th>Oran</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $toplam_kategori_gelir = array_sum(array_column($kategori_satis, 'toplam_gelir'));
                foreach ($kategori_satis as $kategori): 
                ?>
                <tr>
                    <td><strong><?php echo $kategori['kategori_adi'] ?? 'Kategori yok'; ?></strong></td>
                    <td><?php echo number_format($kategori['satis_sayisi']); ?></td>
                    <td><?php echo number_format($kategori['toplam_adet']); ?></td>
                    <td><strong style="color: var(--primary);"><?php echo para_format($kategori['toplam_gelir']); ?></strong></td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo yuzde_hesapla($kategori['toplam_gelir'], $toplam_kategori_gelir); ?>%;">
                                <?php echo yuzde_hesapla($kategori['toplam_gelir'], $toplam_kategori_gelir); ?>%
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-tags"></i>
        <p>Henüz kategori verisi yok.</p>
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
    // SATIŞ GRAFİĞİ
    // ============================================
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        const labels = <?php echo json_encode($grafik_etiketleri); ?>;
        const siparisData = <?php echo json_encode($grafik_siparis); ?>;
        const gelirData = <?php echo json_encode($grafik_gelir); ?>;
        
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Sipariş Sayısı',
                        data: siparisData,
                        borderColor: '#6C63FF',
                        backgroundColor: 'rgba(108, 99, 255, 0.1)',
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y1'
                    },
                    {
                        label: 'Gelir (₺)',
                        data: gelirData,
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
    // SİPARİŞ DURUMU DAĞILIMI (Pasta)
    // ============================================
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        const durumlar = <?php 
            $labels = [];
            $data = [];
            $colors = [
                '#FFF3CD', '#D1ECF1', '#CCE5FF', '#D4EDDA', '#F8D7DA', '#F5C6CB'
            ];
            foreach ($durum_dagilim as $index => $row) {
                $labels[] = $row['siparis_durumu'];
                $data[] = $row['sayi'];
            }
            echo json_encode(['labels' => $labels, 'data' => $data, 'colors' => $colors]);
        ?>;
        
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: durumlar.labels,
                datasets: [{
                    data: durumlar.data,
                    backgroundColor: durumlar.colors.slice(0, durumlar.labels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    // ============================================
    // ÖDEME YÖNTEMİ DAĞILIMI (Pasta)
    // ============================================
    const paymentCtx = document.getElementById('paymentChart');
    if (paymentCtx) {
        const odemeler = <?php 
            $labels = [];
            $data = [];
            $colors = ['#6C63FF', '#2ECC71', '#F39C12', '#FF6B6B'];
            $odeme_etiket = [
                'kredi_karti' => 'Kredi Kartı',
                'havale' => 'Havale/EFT',
                'kapida' => 'Kapıda Ödeme'
            ];
            foreach ($odeme_dagilim as $index => $row) {
                $labels[] = $odeme_etiket[$row['odeme_yontemi']] ?? $row['odeme_yontemi'];
                $data[] = $row['siparis_sayisi'];
            }
            echo json_encode(['labels' => $labels, 'data' => $data, 'colors' => $colors]);
        ?>;
        
        new Chart(paymentCtx, {
            type: 'pie',
            data: {
                labels: odemeler.labels,
                datasets: [{
                    data: odemeler.data,
                    backgroundColor: odemeler.colors.slice(0, odemeler.labels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
});
</script>


<?php
include 'footer.php';
?>