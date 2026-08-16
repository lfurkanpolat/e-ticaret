<?php
// =====================================================
// ÜRÜN YÖNETİMİ - admin/urunler.php
// =====================================================

// =====================================================
// AJAX İSTEKLERİ - HEADER'DAN ÖNCE
// =====================================================

// SİLME İŞLEMİ (AJAX ile)
if (isset($_POST['ajax_sil']) && isset($_POST['urun_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $urun_id = (int)$_POST['urun_id'];
    $response = ['success' => false, 'message' => ''];
    
    try {
        // 1. Tüm resim URL'lerini topla
        $resim_urls = [];
        
        // Ana resim
        $stmt = $db->prepare("SELECT resim_url FROM urunler WHERE id = ?");
        $stmt->execute([$urun_id]);
        $urun = $stmt->fetch();
        if ($urun && $urun['resim_url']) {
            $resim_urls[] = $urun['resim_url'];
        }
        
        // Ek resimler
        $stmt = $db->prepare("SELECT resim_url FROM urun_resimleri WHERE urun_id = ?");
        $stmt->execute([$urun_id]);
        $ek_resimler = $stmt->fetchAll();
        foreach ($ek_resimler as $resim) {
            $resim_urls[] = $resim['resim_url'];
        }
        
        // 2. Tüm resim dosyalarını sil
        foreach ($resim_urls as $url) {
            $filename = basename($url);
            $name_parts = pathinfo($filename);
            $name_without_ext = $name_parts['filename'];
            $ext = $name_parts['extension'];
            
            // Orijinal resim
            $file_path = '../uploads/urunler/' . $filename;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Thumbnail resim
            $thumb_filename = $name_without_ext . '_thumb.' . $ext;
            $thumb_path = '../uploads/urunler/thumb/' . $thumb_filename;
            if (file_exists($thumb_path)) {
                unlink($thumb_path);
            }
        }
        
        // 3. Veritabanından sil
        $stmt = $db->prepare("DELETE FROM urun_resimleri WHERE urun_id = ?");
        $stmt->execute([$urun_id]);
        
        $stmt = $db->prepare("DELETE FROM urunler WHERE id = ?");
        $stmt->execute([$urun_id]);
        
        $response['success'] = true;
        $response['message'] = 'Ürün ve tüm resimleri silindi!';
    } catch (PDOException $e) {
        $response['message'] = 'Hata: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// DURUM GÜNCELLEME (AJAX ile)
if (isset($_POST['ajax_durum']) && isset($_POST['urun_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $urun_id = (int)$_POST['urun_id'];
    $yeni_durum = $_POST['yeni_durum'];
    $response = ['success' => false, 'message' => ''];
    
    try {
        $stmt = $db->prepare("UPDATE urunler SET durum = ? WHERE id = ?");
        $stmt->execute([$yeni_durum, $urun_id]);
        
        $response['success'] = true;
        $response['message'] = 'Ürün durumu güncellendi!';
        $response['yeni_durum'] = $yeni_durum;
    } catch (PDOException $e) {
        $response['message'] = 'Hata: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// ÜRÜN DÜZENLEME (AJAX ile)
if (isset($_POST['ajax_duzenle']) && isset($_POST['urun_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $urun_id = (int)$_POST['urun_id'];
    $response = ['success' => false, 'message' => '', 'data' => null];
    
    try {
        $stmt = $db->prepare("
            SELECT u.*, k.ad as kategori_adi, m.ad as marka_adi 
            FROM urunler u
            LEFT JOIN kategoriler k ON u.kategori_id = k.id
            LEFT JOIN markalar m ON u.marka_id = m.id
            WHERE u.id = ?
        ");
        $stmt->execute([$urun_id]);
        $urun = $stmt->fetch();
        
        if ($urun) {
            $response['success'] = true;
            $response['data'] = $urun;
        } else {
            $response['message'] = 'Ürün bulunamadı!';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Hata: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// ÜRÜN GÜNCELLEME (AJAX ile)
if (isset($_POST['ajax_guncelle']) && isset($_POST['urun_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $urun_id = (int)$_POST['urun_id'];
    $response = ['success' => false, 'message' => ''];
    
    try {
        $ad = clean($_POST['ad']);
        $slug = clean($_POST['slug']);
        $aciklama = clean($_POST['aciklama']);
        $urun_kodu = clean($_POST['urun_kodu']);
        $fiyat = (float)$_POST['fiyat'];
        $indirimli_fiyat = !empty($_POST['indirimli_fiyat']) ? (float)$_POST['indirimli_fiyat'] : null;
        $maliyet = !empty($_POST['maliyet']) ? (float)$_POST['maliyet'] : null;
        $stok = (int)$_POST['stok'];
        $kritik_stok = (int)$_POST['kritik_stok'];
        $kategori_id = (int)$_POST['kategori_id'];
        $marka_id = isset($_POST['marka_id']) && $_POST['marka_id'] !== '' ? (int)$_POST['marka_id'] : null;
        $durum = clean($_POST['durum']);
        
        // Validasyon
        $errors = [];
        if (empty($ad)) $errors[] = 'Ürün adı boş olamaz.';
        if (empty($slug)) $errors[] = 'Slug boş olamaz.';
        if (empty($urun_kodu)) $errors[] = 'Ürün kodu boş olamaz.';
        if ($fiyat <= 0) $errors[] = 'Fiyat 0\'dan büyük olmalıdır.';
        if ($kategori_id <= 0) $errors[] = 'Kategori seçilmelidir.';
        
        if (empty($errors)) {
            // Resim güncelleme varsa
            $resim_url = null;
            if (isset($_FILES['resim']) && $_FILES['resim']['error'] == 0) {
                $upload_dir = '../uploads/urunler/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $ext = pathinfo($_FILES['resim']['name'], PATHINFO_EXTENSION);
                $resim_url = 'urun_' . time() . '_' . uniqid() . '.' . $ext;
                $upload_path = $upload_dir . $resim_url;
                
                if (move_uploaded_file($_FILES['resim']['tmp_name'], $upload_path)) {
                    // Eski resmi sil
                    $stmt = $db->prepare("SELECT resim_url FROM urunler WHERE id = ?");
                    $stmt->execute([$urun_id]);
                    $eski_urun = $stmt->fetch();
                    if ($eski_urun && $eski_urun['resim_url']) {
                        $old_file = '../uploads/urunler/' . $eski_urun['resim_url'];
                        if (file_exists($old_file)) unlink($old_file);
                    }
                } else {
                    $resim_url = null;
                }
            }
            
            if ($resim_url) {
                $sql = "UPDATE urunler SET 
                            ad = ?, slug = ?, aciklama = ?, urun_kodu = ?, 
                            fiyat = ?, indirimli_fiyat = ?, maliyet = ?,
                            stok = ?, kritik_stok = ?, kategori_id = ?, marka_id = ?, durum = ?,
                            resim_url = ?
                        WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$ad, $slug, $aciklama, $urun_kodu, $fiyat, $indirimli_fiyat, 
                               $maliyet, $stok, $kritik_stok, $kategori_id, $marka_id, $durum, 
                               $resim_url, $urun_id]);
            } else {
                $sql = "UPDATE urunler SET 
                            ad = ?, slug = ?, aciklama = ?, urun_kodu = ?, 
                            fiyat = ?, indirimli_fiyat = ?, maliyet = ?,
                            stok = ?, kritik_stok = ?, kategori_id = ?, marka_id = ?, durum = ?
                        WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$ad, $slug, $aciklama, $urun_kodu, $fiyat, $indirimli_fiyat, 
                               $maliyet, $stok, $kritik_stok, $kategori_id, $marka_id, $durum, $urun_id]);
            }
            
            $response['success'] = true;
            $response['message'] = 'Ürün güncellendi!';
        } else {
            $response['message'] = implode('<br>', $errors);
        }
    } catch (PDOException $e) {
        $response['message'] = 'Hata: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// =====================================================
// NORMAL POST İŞLEMLERİ
// =====================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once '../includes/config.php';
    
    // TOPLU İŞLEM
    if (isset($_POST['toplu_islem']) && isset($_POST['urun_ids']) && is_array($_POST['urun_ids']) && !empty($_POST['urun_ids'])) {
        $urun_ids = array_map('intval', $_POST['urun_ids']);
        $islem = $_POST['toplu_islem'];
        
        if (empty($islem)) {
            $_SESSION['error'] = 'Lütfen bir işlem seçin!';
            header("Location: " . SITE_URL . "admin/urunler.php");
            exit();
        }
        
        $ids_str = implode(',', $urun_ids);
        
        try {
            if ($islem == 'aktif') {
                $db->query("UPDATE urunler SET durum = 'aktif' WHERE id IN ($ids_str)");
                $_SESSION['success'] = count($urun_ids) . ' ürün aktifleştirildi!';
            } elseif ($islem == 'pasif') {
                $db->query("UPDATE urunler SET durum = 'pasif' WHERE id IN ($ids_str)");
                $_SESSION['success'] = count($urun_ids) . ' ürün pasifleştirildi!';
            } elseif ($islem == 'sil') {
                // Resimleri sil
                $stmt = $db->query("SELECT resim_url FROM urunler WHERE id IN ($ids_str)");
                while ($urun = $stmt->fetch()) {
                    if ($urun['resim_url']) {
                        $filename = basename($urun['resim_url']);
                        $name_parts = pathinfo($filename);
                        $name_without_ext = $name_parts['filename'];
                        $ext = $name_parts['extension'];
                        
                        $file_path = '../uploads/urunler/' . $filename;
                        if (file_exists($file_path)) unlink($file_path);
                        
                        $thumb_filename = $name_without_ext . '_thumb.' . $ext;
                        $thumb_path = '../uploads/urunler/thumb/' . $thumb_filename;
                        if (file_exists($thumb_path)) unlink($thumb_path);
                    }
                }
                
                // Ek resimleri sil
                $stmt = $db->query("SELECT resim_url FROM urun_resimleri WHERE urun_id IN ($ids_str)");
                while ($resim = $stmt->fetch()) {
                    $filename = basename($resim['resim_url']);
                    $name_parts = pathinfo($filename);
                    $name_without_ext = $name_parts['filename'];
                    $ext = $name_parts['extension'];
                    
                    $file_path = '../uploads/urunler/' . $filename;
                    if (file_exists($file_path)) unlink($file_path);
                    
                    $thumb_filename = $name_without_ext . '_thumb.' . $ext;
                    $thumb_path = '../uploads/urunler/thumb/' . $thumb_filename;
                    if (file_exists($thumb_path)) unlink($thumb_path);
                }
                
                $db->query("DELETE FROM urun_resimleri WHERE urun_id IN ($ids_str)");
                $db->query("DELETE FROM urunler WHERE id IN ($ids_str)");
                $_SESSION['success'] = count($urun_ids) . ' ürün ve tüm resimleri silindi!';
            } else {
                $_SESSION['error'] = 'Geçersiz işlem!';
            }
            header("Location: " . SITE_URL . "admin/urunler.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Hata: ' . $e->getMessage();
            header("Location: " . SITE_URL . "admin/urunler.php");
            exit();
        }
    } elseif (isset($_POST['toplu_islem']) && (!isset($_POST['urun_ids']) || empty($_POST['urun_ids']))) {
        $_SESSION['error'] = 'Lütfen en az bir ürün seçin!';
        header("Location: " . SITE_URL . "admin/urunler.php");
        exit();
    }
}

// =====================================================
// NORMAL SAYFA
// =====================================================
$page_title = 'Ürün Yönetimi';
include 'header.php';

// Session mesajlarını al ve temizle
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error']);
unset($_SESSION['success']);

// =====================================================
// FİLTRELEME VE ARAMA
// =====================================================
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$kategori_id = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$marka_id = isset($_GET['marka']) ? (int)$_GET['marka'] : 0;
$durum = isset($_GET['durum']) ? clean($_GET['durum']) : '';
$stok_durumu = isset($_GET['stok']) ? clean($_GET['stok']) : '';
$sayfa = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
$limit = 20;
$offset = ($sayfa - 1) * $limit;

// =====================================================
// KATEGORİ VE MARKA LİSTESİNİ ÇEK (Filtre için)
// =====================================================
try {
    $kategoriler = $db->query("SELECT * FROM kategoriler WHERE durum = 'aktif' ORDER BY ad")->fetchAll();
} catch (PDOException $e) {
    $kategoriler = [];
}

try {
    $markalar = $db->query("SELECT * FROM markalar WHERE durum = 'aktif' ORDER BY ad")->fetchAll();
} catch (PDOException $e) {
    $markalar = [];
}

// =====================================================
// ÜRÜN LİSTESİNİ ÇEK
// =====================================================
$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND (u.ad LIKE ? OR u.urun_kodu LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($kategori_id > 0) {
    $where .= " AND u.kategori_id = ?";
    $params[] = $kategori_id;
}
if ($marka_id > 0) {
    $where .= " AND u.marka_id = ?";
    $params[] = $marka_id;
}
if ($durum) {
    $where .= " AND u.durum = ?";
    $params[] = $durum;
}
if ($stok_durumu == 'kritik') {
    $where .= " AND u.stok <= u.kritik_stok AND u.stok > 0";
} elseif ($stok_durumu == 'stok_yok') {
    $where .= " AND u.stok <= 0";
} elseif ($stok_durumu == 'stok_var') {
    $where .= " AND u.stok > 0";
}

try {
    $count = $db->prepare("SELECT COUNT(*) as toplam FROM urunler u WHERE $where");
    $count->execute($params);
    $toplam_kayit = $count->fetch()['toplam'];
} catch (PDOException $e) {
    $toplam_kayit = 0;
}

$toplam_sayfa = ceil($toplam_kayit / $limit);

try {
    $sql = "SELECT u.*, k.ad as kategori_adi, m.ad as marka_adi 
            FROM urunler u
            LEFT JOIN kategoriler k ON u.kategori_id = k.id
            LEFT JOIN markalar m ON u.marka_id = m.id
            WHERE $where
            ORDER BY u.olusturma_tarihi DESC
            LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $urunler = $stmt->fetchAll();
} catch (PDOException $e) {
    $urunler = [];
    $error = 'Veritabanı hatası!';
}
?>

<!-- ============================================ -->
<!-- SAYFA BAŞLIĞI -->
<!-- ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-box"></i> Ürün Yönetimi</h2>
        <span class="page-count"><?php echo $toplam_kayit; ?> ürün</span>
    </div>
    <div class="page-header-right">
        <a href="<?php echo SITE_URL; ?>admin/urun-ekle.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Yeni Ürün Ekle
        </a>
    </div>
</div>

<!-- ============================================ -->
<!-- FİLTRELEME VE ARAMA -->
<!-- ============================================ -->
<div class="filter-section">
    <form method="GET" class="filter-form">
        <div class="filter-row">
            <div class="filter-item">
                <input type="text" name="search" placeholder="Ürün ara..." value="<?php echo $search; ?>">
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
                <select name="marka">
                    <option value="0">Tüm Markalar</option>
                    <?php foreach ($markalar as $marka): ?>
                        <option value="<?php echo $marka['id']; ?>" <?php echo $marka_id == $marka['id'] ? 'selected' : ''; ?>>
                            <?php echo $marka['ad']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-item">
                <select name="durum">
                    <option value="">Tüm Durumlar</option>
                    <option value="aktif" <?php echo $durum == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                    <option value="pasif" <?php echo $durum == 'pasif' ? 'selected' : ''; ?>>Pasif</option>
                    <option value="stok_yok" <?php echo $durum == 'stok_yok' ? 'selected' : ''; ?>>Stok Yok</option>
                </select>
            </div>
            <div class="filter-item">
                <select name="stok">
                    <option value="">Tüm Stok</option>
                    <option value="stok_var" <?php echo $stok_durumu == 'stok_var' ? 'selected' : ''; ?>>Stokta Var</option>
                    <option value="stok_yok" <?php echo $stok_durumu == 'stok_yok' ? 'selected' : ''; ?>>Stokta Yok</option>
                    <option value="kritik" <?php echo $stok_durumu == 'kritik' ? 'selected' : ''; ?>>Kritik Stok</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrele</button>
                <a href="<?php echo SITE_URL; ?>admin/urunler.php" class="btn btn-outline"><i class="fas fa-undo"></i> Temizle</a>
            </div>
        </div>
    </form>
</div>

<!-- ============================================ -->
<!-- HATA VE BAŞARI MESAJLARI -->
<!-- ============================================ -->
<?php if ($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<!-- ============================================ -->
<!-- ÜRÜN LİSTESİ TABLOSU -->
<!-- ============================================ -->
<div class="table-card">
    <div class="table-header">
        <h3>Ürün Listesi</h3>
        <div class="table-actions">
            <form method="POST" class="bulk-form" id="bulkForm" onsubmit="return confirmBulk()">
                <select name="toplu_islem" class="bulk-select">
                    <option value="">Toplu İşlem</option>
                    <option value="aktif">Aktifleştir</option>
                    <option value="pasif">Pasifleştir</option>
                    <option value="sil">Sil</option>
                </select>
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fas fa-check"></i> Uygula
                </button>
            </form>
        </div>
    </div>
    
    <?php if (!empty($urunler)): ?>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="40"><input type="checkbox" id="selectAll"></th>
                    <th width="60">#</th>
                    <th width="80">Resim</th>
                    <th>Ürün Bilgisi</th>
                    <th>Kategori</th>
                    <th>Marka</th>
                    <th>Fiyat</th>
                    <th>Stok</th>
                    <th>Durum</th>
                    <th width="140">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($urunler as $urun): ?>
                <tr id="urun-row-<?php echo $urun['id']; ?>">
                    <td><input type="checkbox" name="urun_ids[]" value="<?php echo $urun['id']; ?>" class="urun-checkbox"></td>
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
                        <br><small>Kod: <?php echo $urun['urun_kodu']; ?></small>
                    </td>
                    <td><?php echo $urun['kategori_adi'] ?? '-'; ?></td>
                    <td><?php echo $urun['marka_adi'] ?? '-'; ?></td>
                    <td>
                        <?php if ($urun['indirimli_fiyat'] && $urun['indirimli_fiyat'] < $urun['fiyat']): ?>
                            <span class="old-price"><?php echo number_format($urun['fiyat'], 2, ',', '.'); ?> ₺</span>
                            <br><span class="current-price"><?php echo number_format($urun['indirimli_fiyat'], 2, ',', '.'); ?> ₺</span>
                        <?php else: ?>
                            <span class="current-price"><?php echo number_format($urun['fiyat'], 2, ',', '.'); ?> ₺</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="stock-badge <?php echo $urun['stok'] <= 0 ? 'out-of-stock' : ($urun['stok'] <= $urun['kritik_stok'] ? 'low-stock' : 'in-stock'); ?>">
                            <?php echo $urun['stok']; ?> adet
                        </span>
                    </td>
                    <td>
                        <span class="status-badge <?php echo $urun['durum']; ?>" id="durum-badge-<?php echo $urun['id']; ?>">
                            <?php 
                            $labels = ['aktif' => 'Aktif', 'pasif' => 'Pasif', 'stok_yok' => 'Stok Yok'];
                            echo $labels[$urun['durum']] ?? $urun['durum'];
                            ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editProduct(<?php echo $urun['id']; ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="changeStatus(<?php echo $urun['id']; ?>, '<?php echo $urun['durum']; ?>')">
                            <i class="fas fa-sync"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteProduct(<?php echo $urun['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
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
                    <a href="?sayfa=<?php echo $i; ?>&search=<?php echo $search; ?>&kategori=<?php echo $kategori_id; ?>&marka=<?php echo $marka_id; ?>&durum=<?php echo $durum; ?>&stok=<?php echo $stok_durumu; ?>" class="page-link">
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
        <p>İlk ürününü ekle!</p>
        <a href="<?php echo SITE_URL; ?>admin/urun-ekle.php" class="btn btn-primary" style="margin-top:16px;">
            <i class="fas fa-plus"></i> Ürün Ekle
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================ -->
<!-- MODALLAR -->
<!-- ============================================ -->

<!-- DÜZENLEME MODAL -->
<div class="modal" id="editModal">
    <div class="modal-overlay" onclick="closeModal('editModal')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Ürün Düzenle</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editForm" enctype="multipart/form-data">
                <input type="hidden" name="urun_id" id="edit_urun_id">
                <input type="hidden" name="ajax_guncelle" value="1">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Ürün Adı *</label>
                        <input type="text" name="ad" id="edit_ad" required>
                    </div>
                    <div class="form-group">
                        <label>Slug *</label>
                        <input type="text" name="slug" id="edit_slug" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Ürün Kodu *</label>
                        <input type="text" name="urun_kodu" id="edit_urun_kodu" required>
                    </div>
                    <div class="form-group">
                        <label>Kategori *</label>
                        <select name="kategori_id" id="edit_kategori_id" required>
                            <option value="">Seçin</option>
                            <?php foreach ($kategoriler as $kat): ?>
                                <option value="<?php echo $kat['id']; ?>"><?php echo $kat['ad']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Marka</label>
                        <select name="marka_id" id="edit_marka_id">
                            <option value="">Marka Seçin</option>
                            <?php foreach ($markalar as $marka): ?>
                                <option value="<?php echo $marka['id']; ?>"><?php echo $marka['ad']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Durum</label>
                        <select name="durum" id="edit_durum">
                            <option value="aktif">Aktif</option>
                            <option value="pasif">Pasif</option>
                            <option value="stok_yok">Stok Yok</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Açıklama</label>
                    <textarea name="aciklama" id="edit_aciklama" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Fiyat *</label>
                        <input type="number" name="fiyat" id="edit_fiyat" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>İndirimli Fiyat</label>
                        <input type="number" name="indirimli_fiyat" id="edit_indirimli_fiyat" step="0.01">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Maliyet</label>
                        <input type="number" name="maliyet" id="edit_maliyet" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Stok *</label>
                        <input type="number" name="stok" id="edit_stok" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Kritik Stok</label>
                    <input type="number" name="kritik_stok" id="edit_kritik_stok">
                </div>
                
                <div class="form-group">
                    <label>Resim</label>
                    <input type="file" name="resim" accept="image/*">
                    <div id="edit_current_image" style="margin-top:8px;"></div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">İptal</button>
                    <button type="submit" class="btn btn-primary" id="editSubmitBtn">
                        <i class="fas fa-save"></i> Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SİLME MODAL -->
<div class="modal" id="deleteModal">
    <div class="modal-overlay" onclick="closeModal('deleteModal')"></div>
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h3 style="color:#FF6B6B;"><i class="fas fa-exclamation-triangle"></i> Silme Onayı</h3>
            <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p style="text-align:center;font-size:16px;margin-bottom:20px;">Bu ürünü silmek istediğine emin misin?</p>
            <input type="hidden" id="delete_urun_id">
            <div class="form-actions" style="justify-content:center;">
                <button class="btn btn-outline" onclick="closeModal('deleteModal')">İptal</button>
                <button class="btn btn-danger" id="confirmDeleteBtn"><i class="fas fa-trash"></i> Evet, Sil</button>
            </div>
        </div>
    </div>
</div>

<!-- DURUM MODAL -->
<div class="modal" id="statusModal">
    <div class="modal-overlay" onclick="closeModal('statusModal')"></div>
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h3><i class="fas fa-sync"></i> Durum Değiştir</h3>
            <button class="modal-close" onclick="closeModal('statusModal')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="status_urun_id">
            <div class="form-group">
                <select id="status_select" style="width:100%;padding:10px;border:2px solid var(--light-gray);border-radius:10px;font-size:14px;">
                    <option value="aktif">Aktif</option>
                    <option value="pasif">Pasif</option>
                    <option value="stok_yok">Stok Yok</option>
                </select>
            </div>
            <div class="form-actions" style="justify-content:center;">
                <button class="btn btn-outline" onclick="closeModal('statusModal')">İptal</button>
                <button class="btn btn-primary" id="confirmStatusBtn"><i class="fas fa-check"></i> Değiştir</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- JAVASCRIPT -->
<!-- ============================================ -->
<script>
// ============================================
// TOPLU İŞLEM ONAY
// ============================================
function confirmBulk() {
    var checkboxes = document.querySelectorAll('.urun-checkbox:checked');
    
    if (checkboxes.length === 0) {
        alert('Lütfen en az bir ürün seçin!');
        return false;
    }
    
    var select = document.querySelector('.bulk-select');
    if (select.value === '') {
        alert('Lütfen bir işlem seçin!');
        return false;
    }
    
    if (select.value === 'sil') {
        return confirm(checkboxes.length + ' ürünü silmek istediğinize emin misiniz?\nBu işlem geri alınamaz!');
    }
    
    return confirm(checkboxes.length + ' ürün için seçilen işlemi uygulamak istediğinize emin misiniz?');
}

// ============================================
// TÜMÜNÜ SEÇ
// ============================================
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.urun-checkbox').forEach(function(cb) {
        cb.checked = this.checked;
    }, this);
});

// ============================================
// MODAL
// ============================================
function openModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.active').forEach(function(m) {
            m.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
});

// ============================================
// DÜZENLE
// ============================================
function editProduct(id) {
    var btn = document.getElementById('editSubmitBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Yükleniyor...';
    btn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>admin/urunler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: 'ajax_duzenle=1&urun_id=' + id
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            var u = data.data;
            document.getElementById('edit_urun_id').value = u.id;
            document.getElementById('edit_ad').value = u.ad;
            document.getElementById('edit_slug').value = u.slug;
            document.getElementById('edit_urun_kodu').value = u.urun_kodu;
            document.getElementById('edit_kategori_id').value = u.kategori_id;
            document.getElementById('edit_marka_id').value = u.marka_id || '';
            document.getElementById('edit_aciklama').value = u.aciklama || '';
            document.getElementById('edit_fiyat').value = u.fiyat;
            document.getElementById('edit_indirimli_fiyat').value = u.indirimli_fiyat || '';
            document.getElementById('edit_maliyet').value = u.maliyet || '';
            document.getElementById('edit_stok').value = u.stok;
            document.getElementById('edit_kritik_stok').value = u.kritik_stok;
            document.getElementById('edit_durum').value = u.durum;
            
            var imgDiv = document.getElementById('edit_current_image');
            if (u.resim_url) {
                imgDiv.innerHTML = '<img src="<?php echo SITE_URL; ?>uploads/' + u.resim_url + '" style="max-width:120px;max-height:120px;border-radius:8px;border:1px solid #ddd;"><br><small>Mevcut resim</small>';
            } else {
                imgDiv.innerHTML = '<small>Resim yok</small>';
            }
            
            openModal('editModal');
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(function(e) { alert('Hata: ' + e); })
    .finally(function() {
        btn.innerHTML = '<i class="fas fa-save"></i> Güncelle';
        btn.disabled = false;
    });
}

// ============================================
// GÜNCELLE
// ============================================
document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = document.getElementById('editSubmitBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Güncelleniyor...';
    btn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>admin/urunler.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(this)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            alert(data.message);
            closeModal('editModal');
            location.reload();
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(function(e) { alert('Hata: ' + e); })
    .finally(function() {
        btn.innerHTML = '<i class="fas fa-save"></i> Güncelle';
        btn.disabled = false;
    });
});

// ============================================
// SİL
// ============================================
function deleteProduct(id) {
    document.getElementById('delete_urun_id').value = id;
    openModal('deleteModal');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    var id = document.getElementById('delete_urun_id').value;
    var btn = this;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Siliniyor...';
    btn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>admin/urunler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: 'ajax_sil=1&urun_id=' + id
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            alert(data.message);
            closeModal('deleteModal');
            var row = document.getElementById('urun-row-' + id);
            if (row) {
                row.style.transition = 'all 0.3s';
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                setTimeout(function() {
                    row.remove();
                }, 300);
            }
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(function(e) { alert('Hata: ' + e); })
    .finally(function() {
        btn.innerHTML = '<i class="fas fa-trash"></i> Evet, Sil';
        btn.disabled = false;
    });
});

// ============================================
// DURUM DEĞİŞTİR
// ============================================
function changeStatus(id, current) {
    document.getElementById('status_urun_id').value = id;
    document.getElementById('status_select').value = current;
    openModal('statusModal');
}

document.getElementById('confirmStatusBtn').addEventListener('click', function() {
    var id = document.getElementById('status_urun_id').value;
    var yeni = document.getElementById('status_select').value;
    var btn = this;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Değiştiriliyor...';
    btn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>admin/urunler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: 'ajax_durum=1&urun_id=' + id + '&yeni_durum=' + yeni
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            var badge = document.getElementById('durum-badge-' + id);
            var labels = { 'aktif': 'Aktif', 'pasif': 'Pasif', 'stok_yok': 'Stok Yok' };
            badge.textContent = labels[yeni] || yeni;
            badge.className = 'status-badge ' + yeni;
            closeModal('statusModal');
            alert(data.message);
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(function(e) { alert('Hata: ' + e); })
    .finally(function() {
        btn.innerHTML = '<i class="fas fa-check"></i> Değiştir';
        btn.disabled = false;
    });
});
</script>

<?php
include 'footer.php';
?>