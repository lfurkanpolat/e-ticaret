<?php
// =====================================================
// SİSTEM LOGLARI - admin/loglar.php
// =====================================================

// =====================================================
// NORMAL SAYFA
// =====================================================
$page_title = 'Sistem Logları';
include 'header.php';

// Session mesajlarını al ve temizle
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error']);
unset($_SESSION['success']);

// =====================================================
// LOG TEMİZLEME
// =====================================================
if (isset($_GET['temizle']) && $_GET['temizle'] == '1') {
    try {
        // 30 günden eski logları sil
        $stmt = $db->prepare("DELETE FROM log_istemci WHERE islem_tarihi < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute();
        $silinen = $stmt->rowCount();
        $success = $silinen . ' eski log kaydı temizlendi!';
    } catch (PDOException $e) {
        $error = 'Veritabanı hatası: ' . $e->getMessage();
    }
}

// =====================================================
// FİLTRELEME
// =====================================================
$islem_turu = isset($_GET['islem_turu']) ? clean($_GET['islem_turu']) : '';
$kullanici_id = isset($_GET['kullanici_id']) ? (int)$_GET['kullanici_id'] : 0;
$tarih_baslangic = isset($_GET['tarih_baslangic']) ? clean($_GET['tarih_baslangic']) : '';
$tarih_bitis = isset($_GET['tarih_bitis']) ? clean($_GET['tarih_bitis']) : '';
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$sayfa = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
$limit = 50;
$offset = ($sayfa - 1) * $limit;

// =====================================================
// İŞLEM TÜRLERİ
// =====================================================
$islem_turleri = [
    'giris' => 'Giriş Yapma',
    'cikis' => 'Çıkış Yapma',
    'kayit' => 'Kayıt Olma',
    'siparis' => 'Sipariş',
    'odeme' => 'Ödeme',
    'urun_ekle' => 'Ürün Ekleme',
    'urun_sil' => 'Ürün Silme',
    'guncelle' => 'Güncelleme',
    'silme' => 'Silme',
    'hata' => 'Hata'
];

// =====================================================
// KULLANICI LİSTESİ (Filtre için)
// =====================================================
try {
    $kullanicilar = $db->query("SELECT id, ad, soyad, email FROM kullanicilar ORDER BY ad")->fetchAll();
} catch (PDOException $e) {
    $kullanicilar = [];
}

// =====================================================
// LOG LİSTESİNİ ÇEK
// =====================================================
$where = "1=1";
$params = [];

if ($islem_turu) {
    $where .= " AND islem_turu = ?";
    $params[] = $islem_turu;
}

if ($kullanici_id > 0) {
    $where .= " AND kullanici_id = ?";
    $params[] = $kullanici_id;
}

if ($tarih_baslangic) {
    $where .= " AND DATE(islem_tarihi) >= ?";
    $params[] = $tarih_baslangic;
}

if ($tarih_bitis) {
    $where .= " AND DATE(islem_tarihi) <= ?";
    $params[] = $tarih_bitis;
}

if ($search) {
    $where .= " AND (aciklama LIKE ? OR ip_adresi LIKE ? OR sayfa_url LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Toplam log sayısı
try {
    $countSql = "SELECT COUNT(*) as toplam FROM log_istemci WHERE $where";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $toplam_kayit = $stmt->fetch()['toplam'] ?? 0;
} catch (PDOException $e) {
    $toplam_kayit = 0;
    $error = 'Veritabanı hatası: ' . $e->getMessage();
}

$toplam_sayfa = ceil($toplam_kayit / $limit);

// Logları çek
try {
    $sql = "
        SELECT l.*, u.ad, u.soyad, u.email
        FROM log_istemci l
        LEFT JOIN kullanicilar u ON l.kullanici_id = u.id
        WHERE $where
        ORDER BY l.islem_tarihi DESC
        LIMIT ? OFFSET ?
    ";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $loglar = $stmt->fetchAll();
} catch (PDOException $e) {
    $loglar = [];
    $error = 'Veritabanı hatası: ' . $e->getMessage();
}

// Log renkleri
function getLogColor($islem_turu) {
    $colors = [
        'giris' => '#2ECC71',
        'cikis' => '#E74C3C',
        'kayit' => '#3498DB',
        'siparis' => '#9B59B6',
        'odeme' => '#F39C12',
        'urun_ekle' => '#1ABC9C',
        'urun_sil' => '#E74C3C',
        'guncelle' => '#3498DB',
        'silme' => '#E74C3C',
        'hata' => '#E74C3C'
    ];
    return $colors[$islem_turu] ?? '#95A5A6';
}

function getLogIcon($islem_turu) {
    $icons = [
        'giris' => 'fa-sign-in-alt',
        'cikis' => 'fa-sign-out-alt',
        'kayit' => 'fa-user-plus',
        'siparis' => 'fa-shopping-cart',
        'odeme' => 'fa-credit-card',
        'urun_ekle' => 'fa-plus-circle',
        'urun_sil' => 'fa-minus-circle',
        'guncelle' => 'fa-edit',
        'silme' => 'fa-trash',
        'hata' => 'fa-exclamation-triangle'
    ];
    return $icons[$islem_turu] ?? 'fa-circle';
}
?>

<!-- ============================================ -->
<!-- SAYFA BAŞLIĞI -->
<!-- ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-history"></i> Sistem Logları</h2>
        <span class="page-count"><?php echo $toplam_kayit; ?> kayıt</span>
    </div>
    <div class="page-header-right">
        <a href="?temizle=1" class="btn btn-outline" onclick="return confirm('30 günden eski tüm log kayıtları silinecek. Devam etmek istediğinize emin misiniz?')">
            <i class="fas fa-broom"></i> Eski Logları Temizle
        </a>
    </div>
</div>

<!-- ============================================ -->
<!-- HATA VE BAŞARI MESAJLARI -->
<!-- ============================================ -->
<?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<!-- ============================================ -->
<!-- FİLTRELEME -->
<!-- ============================================ -->
<div class="filter-section">
    <form method="GET" class="filter-form">
        <div class="filter-row">
            <div class="filter-item">
                <input type="text" name="search" placeholder="Açıklama, IP, URL ara..." value="<?php echo $search; ?>">
            </div>
            <div class="filter-item">
                <select name="islem_turu">
                    <option value="">Tüm İşlemler</option>
                    <?php foreach ($islem_turleri as $key => $value): ?>
                        <option value="<?php echo $key; ?>" <?php echo $islem_turu == $key ? 'selected' : ''; ?>>
                            <?php echo $value; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-item">
                <select name="kullanici_id">
                    <option value="0">Tüm Kullanıcılar</option>
                    <?php foreach ($kullanicilar as $kullanici): ?>
                        <option value="<?php echo $kullanici['id']; ?>" <?php echo $kullanici_id == $kullanici['id'] ? 'selected' : ''; ?>>
                            <?php echo $kullanici['ad'] . ' ' . $kullanici['soyad'] . ' (' . $kullanici['email'] . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="filter-row" style="margin-top:8px;">
            <div class="filter-item">
                <input type="date" name="tarih_baslangic" placeholder="Başlangıç" value="<?php echo $tarih_baslangic; ?>">
            </div>
            <div class="filter-item">
                <input type="date" name="tarih_bitis" placeholder="Bitiş" value="<?php echo $tarih_bitis; ?>">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrele</button>
                <a href="<?php echo SITE_URL; ?>admin/loglar.php" class="btn btn-outline"><i class="fas fa-undo"></i> Temizle</a>
            </div>
        </div>
    </form>
</div>

<!-- ============================================ -->
<!-- LOG LİSTESİ -->
<!-- ============================================ -->
<div class="table-card">
    <div class="table-header">
        <h3>Log Listesi</h3>
        <div class="table-actions">
            <span class="log-info">Son 30 gün</span>
        </div>
    </div>
    
    <?php if (!empty($loglar)): ?>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>İşlem</th>
                    <th>Kullanıcı</th>
                    <th>Açıklama</th>
                    <th>IP Adresi</th>
                    <th>Sayfa</th>
                    <th>Tarih</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($loglar as $log): ?>
                <tr>
                    <td><?php echo $log['id']; ?></td>
                    <td>
                        <span class="log-badge" style="background: <?php echo getLogColor($log['islem_turu']); ?>;">
                            <i class="fas <?php echo getLogIcon($log['islem_turu']); ?>"></i>
                            <?php echo $islem_turleri[$log['islem_turu']] ?? $log['islem_turu']; ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($log['kullanici_id']): ?>
                            <strong><?php echo $log['ad'] . ' ' . $log['soyad']; ?></strong>
                            <br><small style="color: var(--gray);"><?php echo $log['email']; ?></small>
                        <?php else: ?>
                            <span style="color: var(--gray);">Sistem</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo $log['aciklama']; ?>
                        <?php if ($log['referer_url']): ?>
                            <br><small style="color: var(--gray);">
                                <i class="fas fa-arrow-right"></i> <?php echo $log['referer_url']; ?>
                            </small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <code><?php echo $log['ip_adresi'] ?? '-'; ?></code>
                    </td>
                    <td>
                        <?php if ($log['sayfa_url']): ?>
                            <small style="color: var(--gray);"><?php echo $log['sayfa_url']; ?></small>
                        <?php else: ?>
                            <span style="color: var(--gray);">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo date('d.m.Y H:i:s', strtotime($log['islem_tarihi'])); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($toplam_sayfa > 1): ?>
    <div class="pagination-section">
        <div class="pagination-info">
            Toplam <?php echo $toplam_kayit; ?> kayıttan <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $toplam_kayit); ?> arası
        </div>
        <div class="pagination">
            <?php for ($i = 1; $i <= $toplam_sayfa; $i++): ?>
                <?php if ($i == $sayfa): ?>
                    <span class="page-link active"><?php echo $i; ?></span>
                <?php elseif ($i <= 3 || $i > $toplam_sayfa - 3 || abs($i - $sayfa) <= 1): ?>
                    <a href="?sayfa=<?php echo $i; ?>&islem_turu=<?php echo $islem_turu; ?>&kullanici_id=<?php echo $kullanici_id; ?>&tarih_baslangic=<?php echo $tarih_baslangic; ?>&tarih_bitis=<?php echo $tarih_bitis; ?>&search=<?php echo $search; ?>" class="page-link">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-history"></i>
        <h3>Henüz log kaydı yok</h3>
        <p>Sistem aktiviteleri burada görünecek.</p>
    </div>
    <?php endif; ?>
</div>



<?php
include 'footer.php';
?>