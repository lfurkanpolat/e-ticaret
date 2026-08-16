<?php
// =====================================================
// STOK RAPORLARI - admin/stok-raporlari.php
// =====================================================

// =====================================================
// NORMAL SAYFA
// =====================================================
$page_title = 'Stok Raporları';
include 'header.php';

// Session mesajlarını al ve temizle
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error']);
unset($_SESSION['success']);

// =====================================================
// FİLTRELEME
// =====================================================
$stok_durumu = isset($_GET['stok_durumu']) ? clean($_GET['stok_durumu']) : '';
$kategori_id = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$sayfa = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
$limit = 20;
$offset = ($sayfa - 1) * $limit;

// =====================================================
// KATEGORİ LİSTESİ
// =====================================================
try {
    $kategoriler = $db->query("SELECT * FROM kategoriler WHERE durum = 'aktif' ORDER BY ad")->fetchAll();
} catch (PDOException $e) {
    $kategoriler = [];
}

// =====================================================
// STOK İSTATİSTİKLERİ
// =====================================================
try {
    // Toplam ürün
    $stmt = $db->query("SELECT COUNT(*) as toplam FROM urunler");
    $toplam_urun = $stmt->fetch()['toplam'] ?? 0;
    
    // Toplam stok
    $stmt = $db->query("SELECT SUM(stok) as toplam FROM urunler");
    $toplam_stok = $stmt->fetch()['toplam'] ?? 0;
    
    // Stokta olmayan ürünler
    $stmt = $db->query("SELECT COUNT(*) as toplam FROM urunler WHERE stok <= 0");
    $stok_yok = $stmt->fetch()['toplam'] ?? 0;
    
    // Kritik stok (stok <= kritik_stok ve stok > 0)
    $stmt = $db->query("SELECT COUNT(*) as toplam FROM urunler WHERE stok <= kritik_stok AND stok > 0");
    $kritik_stok = $stmt->fetch()['toplam'] ?? 0;
    
    // Ortalama stok
    $stmt = $db->query("SELECT AVG(stok) as ortalama FROM urunler");
    $ortalama_stok = $stmt->fetch()['ortalama'] ?? 0;
    
    // En çok stok olan ürün
    $stmt = $db->query("SELECT ad, stok FROM urunler ORDER BY stok DESC LIMIT 1");
    $en_cok_stok = $stmt->fetch();
    
    // En az stok olan ürün (stok > 0)
    $stmt = $db->query("SELECT ad, stok FROM urunler WHERE stok > 0 ORDER BY stok ASC LIMIT 1");
    $en_az_stok = $stmt->fetch();
    
} catch (PDOException $e) {
    $toplam_urun = 0;
    $toplam_stok = 0;
    $stok_yok = 0;
    $kritik_stok = 0;
    $ortalama_stok = 0;
    $en_cok_stok = null;
    $en_az_stok = null;
    $error = 'Veritabanı hatası: ' . $e->getMessage();
}

// =====================================================
// ÜRÜN LİSTESİNİ ÇEK (Stok durumuna göre)
// =====================================================
$where = "1=1";
$params = [];

if ($stok_durumu == 'kritik') {
    $where .= " AND stok <= kritik_stok AND stok > 0";
} elseif ($stok_durumu == 'stok_yok') {
    $where .= " AND stok <= 0";
} elseif ($stok_durumu == 'stok_var') {
    $where .= " AND stok > 0";
}

if ($kategori_id > 0) {
    $where .= " AND kategori_id = ?";
    $params[] = $kategori_id;
}

if ($search) {
    $where .= " AND (ad LIKE ? OR urun_kodu LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Toplam kayıt sayısı
try {
    $countSql = "SELECT COUNT(*) as toplam FROM urunler WHERE $where";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $toplam_kayit = $stmt->fetch()['toplam'] ?? 0;
} catch (PDOException $e) {
    $toplam_kayit = 0;
}

$toplam_sayfa = ceil($toplam_kayit / $limit);

// Ürünleri çek
try {
    $sql = "
        SELECT u.*, k.ad as kategori_adi 
        FROM urunler u
        LEFT JOIN kategoriler k ON u.kategori_id = k.id
        WHERE $where
        ORDER BY 
            CASE 
                WHEN stok <= kritik_stok AND stok > 0 THEN 1 
                WHEN stok <= 0 THEN 2 
                ELSE 3 
            END,
            stok ASC
        LIMIT ? OFFSET ?
    ";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $urunler = $stmt->fetchAll();
} catch (PDOException $e) {
    $urunler = [];
    $error = 'Veritabanı hatası: ' . $e->getMessage();
}

// Stok durumu sınıfları
function getStokClass($stok, $kritik_stok) {
    if ($stok <= 0) return 'out-of-stock';
    if ($stok <= $kritik_stok) return 'low-stock';
    return 'in-stock';
}

function getStokLabel($stok, $kritik_stok) {
    if ($stok <= 0) return 'Stokta Yok';
    if ($stok <= $kritik_stok) return 'Kritik Stok';
    return 'Stokta Var';
}
?>

<!-- ============================================ -->
<!-- SAYFA BAŞLIĞI -->
<!-- ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-warehouse"></i> Stok Raporları</h2>
        <span class="page-count"><?php echo $toplam_urun; ?> ürün</span>
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
<!-- STOK İSTATİSTİKLERİ -->
<!-- ============================================ -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: #6C63FF;">
            <i class="fas fa-boxes"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo number_format($toplam_stok); ?></h3>
            <p>Toplam Stok</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #2ECC71;">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo number_format($toplam_urun - $stok_yok); ?></h3>
            <p>Stokta Var</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #F39C12;">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-info">
            <h3 style="color: #F39C12;"><?php echo number_format($kritik_stok); ?></h3>
            <p>Kritik Stok</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #FF6B6B;">
            <i class="fas fa-times-circle"></i>
        </div>
        <div class="stat-info">
            <h3 style="color: #FF6B6B;"><?php echo number_format($stok_yok); ?></h3>
            <p>Stokta Yok</p>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- STOK ÖZETİ -->
<!-- ============================================ -->
<div class="chart-card">
    <div class="chart-header">
        <h3>Stok Durumu Özeti</h3>
    </div>
    <div class="chart-body" style="max-width:400px; margin-left:auto; margin-right:auto;">
        <canvas id="stockChart" height="150"></canvas>
    </div>
</div>

<div class="stats-grid-small">
    <div class="stat-card-small">
        <div class="stat-icon-small" style="background: #2ECC71;">
            <i class="fas fa-arrow-up"></i>
        </div>
        <div class="stat-info-small">
            <span class="stat-number"><?php echo number_format($ortalama_stok, 1); ?></span>
            <span class="stat-label">Ortalama Stok</span>
        </div>
    </div>
    
    <div class="stat-card-small">
        <div class="stat-icon-small" style="background: #6C63FF;">
            <i class="fas fa-arrow-up"></i>
        </div>
        <div class="stat-info-small">
            <span class="stat-number"><?php echo $en_cok_stok ? $en_cok_stok['stok'] : 0; ?></span>
            <span class="stat-label">En Çok Stok: <?php echo $en_cok_stok ? $en_cok_stok['ad'] : '-'; ?></span>
        </div>
    </div>
    
    <div class="stat-card-small">
        <div class="stat-icon-small" style="background: #FF6B6B;">
            <i class="fas fa-arrow-down"></i>
        </div>
        <div class="stat-info-small">
            <span class="stat-number"><?php echo $en_az_stok ? $en_az_stok['stok'] : 0; ?></span>
            <span class="stat-label">En Az Stok: <?php echo $en_az_stok ? $en_az_stok['ad'] : '-'; ?></span>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- FİLTRELEME -->
<!-- ============================================ -->
<div class="filter-section">
    <form method="GET" class="filter-form">
        <div class="filter-row">
            <div class="filter-item">
                <input type="text" name="search" placeholder="Ürün adı, kod ara..." value="<?php echo $search; ?>">
            </div>
            <div class="filter-item">
                <select name="kategori">
                    <option value="0">Tüm Kategoriler</option>
                    <?php foreach ($kategoriler as $kat): ?>
                        <option value="<?php echo $kat['id']; ?>" <?php echo $kategori_id == $kat['id'] ? 'selected' : ''; ?>>
                            <?php echo $kat['ad']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-item">
                <select name="stok_durumu">
                    <option value="">Tüm Durumlar</option>
                    <option value="stok_var" <?php echo $stok_durumu == 'stok_var' ? 'selected' : ''; ?>>Stokta Var</option>
                    <option value="kritik" <?php echo $stok_durumu == 'kritik' ? 'selected' : ''; ?>>Kritik Stok</option>
                    <option value="stok_yok" <?php echo $stok_durumu == 'stok_yok' ? 'selected' : ''; ?>>Stokta Yok</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrele</button>
                <a href="<?php echo SITE_URL; ?>admin/stok-raporlari.php" class="btn btn-outline"><i class="fas fa-undo"></i> Temizle</a>
            </div>
        </div>
    </form>
</div>

<!-- ============================================ -->
<!-- ÜRÜN STOK LİSTESİ -->
<!-- ============================================ -->
<div class="table-card">
    <div class="table-header">
        <h3>Stok Listesi</h3>
        <div class="table-actions">
            <span class="stock-summary">
                <span class="in-stock-dot"></span> Stokta Var: <?php echo number_format($toplam_urun - $stok_yok); ?>
                <span class="low-stock-dot"></span> Kritik: <?php echo number_format($kritik_stok); ?>
                <span class="out-of-stock-dot"></span> Stokta Yok: <?php echo number_format($stok_yok); ?>
            </span>
        </div>
    </div>
    
    <?php if (!empty($urunler)): ?>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="60">#</th>
                    <th width="80">Resim</th>
                    <th>Ürün Bilgisi</th>
                    <th>Kategori</th>
                    <th>Stok</th>
                    <th>Kritik Stok</th>
                    <th>Durum</th>
                    <th width="100">İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($urunler as $urun): ?>
                <tr>
                    <td><?php echo $urun['id']; ?></td>
                    <td>
                        <?php if ($urun['resim_url']): ?>
                            <img src="<?php echo SITE_URL; ?>uploads/<?php echo $urun['resim_url']; ?>" class="product-thumb">
                        <?php else: ?>
                            <div class="product-thumb no-image"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo $urun['ad']; ?></strong>
                        <br><small style="color: var(--gray);">Kod: <?php echo $urun['urun_kodu']; ?></small>
                    </td>
                    <td><?php echo $urun['kategori_adi'] ?? '-'; ?></td>
                    <td>
                        <strong style="font-size:16px; <?php echo $urun['stok'] <= $urun['kritik_stok'] ? 'color: #FF6B6B;' : 'color: #2ECC71;'; ?>">
                            <?php echo number_format($urun['stok']); ?>
                        </strong>
                    </td>
                    <td><?php echo $urun['kritik_stok']; ?></td>
                    <td>
                        <span class="stock-badge <?php echo getStokClass($urun['stok'], $urun['kritik_stok']); ?>">
                            <?php echo getStokLabel($urun['stok'], $urun['kritik_stok']); ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?php echo SITE_URL; ?>admin/urun-duzenle.php?id=<?php echo $urun['id']; ?>" class="btn btn-sm btn-primary" title="Stok Düzenle">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($toplam_sayfa > 1): ?>
    <div class="pagination-section">
        <div class="pagination-info">
            Toplam <?php echo $toplam_kayit; ?> üründen <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $toplam_kayit); ?> arası
        </div>
        <div class="pagination">
            <?php for ($i = 1; $i <= $toplam_sayfa; $i++): ?>
                <?php if ($i == $sayfa): ?>
                    <span class="page-link active"><?php echo $i; ?></span>
                <?php elseif ($i <= 3 || $i > $toplam_sayfa - 3 || abs($i - $sayfa) <= 1): ?>
                    <a href="?sayfa=<?php echo $i; ?>&stok_durumu=<?php echo $stok_durumu; ?>&kategori=<?php echo $kategori_id; ?>&search=<?php echo $search; ?>" class="page-link">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-box-open"></i>
        <h3>Henüz ürün yok</h3>
        <p>Stok verisi bulunmuyor.</p>
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
    // STOK DURUMU PASTA GRAFİĞİ
    // ============================================
    const stockCtx = document.getElementById('stockChart');
    if (stockCtx) {
        const stokVar = <?php echo $toplam_urun - $stok_yok - $kritik_stok; ?>;
        const kritik = <?php echo $kritik_stok; ?>;
        const stokYok = <?php echo $stok_yok; ?>;
        
        new Chart(stockCtx, {
            type: 'doughnut',
            data: {
                labels: ['Stokta Var', 'Kritik Stok', 'Stokta Yok'],
                datasets: [{
                    data: [stokVar, kritik, stokYok],
                    backgroundColor: ['#2ECC71', '#F39C12', '#FF6B6B'],
                    borderWidth: 3,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                                return context.label + ': ' + context.parsed + ' ürün (' + percentage + '%)';
                            }
                        }
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