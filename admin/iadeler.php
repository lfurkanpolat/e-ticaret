<?php
// =====================================================
// İADE YÖNETİMİ - admin/iadeler.php
// =====================================================

// =====================================================
// AJAX İSTEKLERİ - HEADER'DAN ÖNCE
// =====================================================

// İADE DURUMU GÜNCELLE (AJAX)
if (isset($_POST['ajax_iade_durum']) && isset($_POST['iade_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $iade_id = (int)$_POST['iade_id'];
    $yeni_durum = $_POST['yeni_durum'];
    $response = ['success' => false, 'message' => ''];
    
    try {
        $stmt = $db->prepare("UPDATE siparis_detay SET iade_durumu = ? WHERE id = ?");
        $stmt->execute([$yeni_durum, $iade_id]);
        
        $response['success'] = true;
        $response['message'] = 'İade durumu güncellendi!';
        $response['yeni_durum'] = $yeni_durum;
    } catch (PDOException $e) {
        $response['message'] = 'Hata: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// İADE DETAY (AJAX)
if (isset($_POST['ajax_iade_detay']) && isset($_POST['iade_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $iade_id = (int)$_POST['iade_id'];
    $response = ['success' => false, 'data' => null];
    
    try {
        $stmt = $db->prepare("
            SELECT sd.*, 
                   u.ad as urun_adi, u.urun_kodu,
                   s.siparis_no, s.kullanici_id,
                   k.ad as kullanici_ad, k.soyad as kullanici_soyad, k.email
            FROM siparis_detay sd
            LEFT JOIN urunler u ON sd.urun_id = u.id
            LEFT JOIN siparisler s ON sd.siparis_id = s.id
            LEFT JOIN kullanicilar k ON s.kullanici_id = k.id
            WHERE sd.id = ?
        ");
        $stmt->execute([$iade_id]);
        $iade = $stmt->fetch();
        
        if ($iade) {
            $response['success'] = true;
            $response['data'] = $iade;
        } else {
            $response['message'] = 'İade bulunamadı!';
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
    
    // TOPLU İADE DURUM GÜNCELLEME
    if (isset($_POST['toplu_islem']) && isset($_POST['iade_ids'])) {
        $iade_ids = array_map('intval', $_POST['iade_ids']);
        $islem = $_POST['toplu_islem'];
        $ids_str = implode(',', $iade_ids);
        
        try {
            $db->query("UPDATE siparis_detay SET iade_durumu = '$islem' WHERE id IN ($ids_str)");
            $_SESSION['success'] = count($iade_ids) . ' iade güncellendi!';
            header("Location: " . SITE_URL . "admin/iadeler.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Hata: ' . $e->getMessage();
            header("Location: " . SITE_URL . "admin/iadeler.php");
            exit();
        }
    }
}

// =====================================================
// NORMAL SAYFA
// =====================================================
$page_title = 'İade Yönetimi';
include 'header.php';

// Session mesajlarını al ve temizle
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error']);
unset($_SESSION['success']);

// =====================================================
// FİLTRELEME
// =====================================================
$durum = isset($_GET['durum']) ? clean($_GET['durum']) : '';
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$sayfa = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
$limit = 20;
$offset = ($sayfa - 1) * $limit;

// =====================================================
// İADE LİSTESİNİ ÇEK
// =====================================================
$where = "sd.iade_durumu != 'yok'";
$params = [];

if ($durum) {
    $where .= " AND sd.iade_durumu = ?";
    $params[] = $durum;
}

if ($search) {
    $where .= " AND (s.siparis_no LIKE ? OR u.ad LIKE ? OR k.ad LIKE ? OR k.soyad LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Toplam iade sayısı
try {
    $countSql = "
        SELECT COUNT(*) as toplam 
        FROM siparis_detay sd
        LEFT JOIN siparisler s ON sd.siparis_id = s.id
        LEFT JOIN urunler u ON sd.urun_id = u.id
        LEFT JOIN kullanicilar k ON s.kullanici_id = k.id
        WHERE $where
    ";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $toplam_kayit = $stmt->fetch()['toplam'] ?? 0;
} catch (PDOException $e) {
    $toplam_kayit = 0;
    $error = 'Veritabanı hatası: ' . $e->getMessage();
}

$toplam_sayfa = ceil($toplam_kayit / $limit);

// İadeleri çek
// İadeleri çek
try {
    $sql = "
        SELECT sd.*, 
               u.ad as urun_adi, u.urun_kodu,
               s.siparis_no,
               k.ad as kullanici_ad, k.soyad as kullanici_soyad, k.email,
               v.renk, v.beden
        FROM siparis_detay sd
        LEFT JOIN urunler u ON sd.urun_id = u.id
        LEFT JOIN urun_varyantlari v ON sd.varyant_id = v.id
        LEFT JOIN siparisler s ON sd.siparis_id = s.id
        LEFT JOIN kullanicilar k ON s.kullanici_id = k.id
        WHERE $where
        ORDER BY sd.id DESC
        LIMIT ? OFFSET ?
    ";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $iadeler = $stmt->fetchAll();
} catch (PDOException $e) {
    $iadeler = [];
    $error = 'Veritabanı hatası: ' . $e->getMessage();
}

// İade durumları
$iade_durumlari = [
    'talep_edildi' => 'Talep Edildi',
    'onaylandi' => 'Onaylandı',
    'reddedildi' => 'Reddedildi',
    'tamamlandi' => 'Tamamlandı'
];
?>

<!-- ============================================ -->
<!-- SAYFA BAŞLIĞI -->
<!-- ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-undo-alt"></i> İade Yönetimi</h2>
        <span class="page-count"><?php echo $toplam_kayit; ?> iade</span>
    </div>
</div>

<!-- ============================================ -->
<!-- FİLTRELEME -->
<!-- ============================================ -->
<div class="filter-section">
    <form method="GET" class="filter-form">
        <div class="filter-row">
            <div class="filter-item">
                <input type="text" name="search" placeholder="Sipariş No, Ürün, Müşteri..." value="<?php echo $search; ?>">
            </div>
            <div class="filter-item">
                <select name="durum">
                    <option value="">Tüm Durumlar</option>
                    <?php foreach ($iade_durumlari as $key => $value): ?>
                        <option value="<?php echo $key; ?>" <?php echo $durum == $key ? 'selected' : ''; ?>>
                            <?php echo $value; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrele</button>
                <a href="<?php echo SITE_URL; ?>admin/iadeler.php" class="btn btn-outline"><i class="fas fa-undo"></i> Temizle</a>
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
<!-- İADE LİSTESİ -->
<!-- ============================================ -->
<div class="table-card">
    <div class="table-header">
        <h3>İade Listesi</h3>
        <div class="table-actions">
            <form method="POST" class="bulk-form">
                <select name="toplu_islem" class="bulk-select">
                    <option value="">Toplu İşlem</option>
                    <option value="onaylandi">Onayla</option>
                    <option value="reddedildi">Reddet</option>
                    <option value="tamamlandi">Tamamla</option>
                </select>
                <button type="submit" name="toplu_islem" class="btn btn-sm btn-primary" onclick="return confirmBulk()">
                    <i class="fas fa-check"></i> Uygula
                </button>
            </form>
        </div>
    </div>
    
    <?php if (!empty($iadeler)): ?>
    <div class="table-responsive">
        <form method="POST" id="iadeForm">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" id="selectAll"></th>
                        <th>#</th>
                        <th>Sipariş No</th>
                        <th>Ürün</th>
                        <th>Müşteri</th>
                        <th>Adet</th>
                        <th>Tutar</th>
                        <th>Durum</th>
                        <th>İade Notu</th>
                        <th width="140">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($iadeler as $iade): ?>
                    <tr id="iade-row-<?php echo $iade['id']; ?>">
                        <td><input type="checkbox" name="iade_ids[]" value="<?php echo $iade['id']; ?>" class="iade-checkbox"></td>
                        <td><?php echo $iade['id']; ?></td>
                        <td>
                            <strong>#<?php echo $iade['siparis_no']; ?></strong>
                        </td>
                        <td>
                            <?php echo $iade['urun_adi']; ?>
                            <br><small style="color: var(--gray);"><?php echo $iade['urun_kodu']; ?></small>
                        </td>
                        <td>
                            <?php echo $iade['kullanici_ad'] . ' ' . $iade['kullanici_soyad']; ?>
                            <br><small style="color: var(--gray);"><?php echo $iade['email']; ?></small>
                        </td>
                        <td><?php echo $iade['adet']; ?></td>
                        <td><strong><?php echo number_format($iade['toplam_fiyat'], 2, ',', '.'); ?> ₺</strong></td>
                        <td>
                            <span class="status-badge <?php echo $iade['iade_durumu']; ?>" id="durum-badge-<?php echo $iade['id']; ?>">
                                <?php echo $iade_durumlari[$iade['iade_durumu']] ?? $iade['iade_durumu']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($iade['iade_notu']): ?>
                                <span title="<?php echo $iade['iade_notu']; ?>">
                                    <?php echo mb_substr($iade['iade_notu'], 0, 30) . (strlen($iade['iade_notu']) > 30 ? '...' : ''); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: var(--gray);">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary" onclick="viewIade(<?php echo $iade['id']; ?>)" title="Detay">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-warning" onclick="changeStatus(<?php echo $iade['id']; ?>, '<?php echo $iade['iade_durumu']; ?>')" title="Durum Değiştir">
                                    <i class="fas fa-sync"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    </div>
    
    <?php if ($toplam_sayfa > 1): ?>
    <div class="pagination-section">
        <div class="pagination-info">
            Toplam <?php echo $toplam_kayit; ?> iadeden <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $toplam_kayit); ?> arası
        </div>
        <div class="pagination">
            <?php for ($i = 1; $i <= $toplam_sayfa; $i++): ?>
                <?php if ($i == $sayfa): ?>
                    <span class="page-link active"><?php echo $i; ?></span>
                <?php elseif ($i <= 3 || $i > $toplam_sayfa - 3 || abs($i - $sayfa) <= 1): ?>
                    <a href="?sayfa=<?php echo $i; ?>&durum=<?php echo $durum; ?>&search=<?php echo $search; ?>" class="page-link">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-undo-alt"></i>
        <h3>İade talebi yok</h3>
        <p>Henüz hiç iade talebi bulunmuyor.</p>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================ -->
<!-- İADE DETAY MODAL -->
<!-- ============================================ -->
<div class="modal" id="detailModal">
    <div class="modal-overlay" onclick="closeModal('detailModal')"></div>
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3><i class="fas fa-file-invoice"></i> İade Detayı</h3>
            <button class="modal-close" onclick="closeModal('detailModal')">&times;</button>
        </div>
        <div class="modal-body" id="detailContent">
            <div style="text-align:center; padding:40px;">
                <i class="fas fa-spinner fa-spin" style="font-size:32px;color:var(--gray);"></i>
                <p>Yükleniyor...</p>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- DURUM DEĞİŞTİRME MODAL -->
<!-- ============================================ -->
<div class="modal" id="statusModal">
    <div class="modal-overlay" onclick="closeModal('statusModal')"></div>
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h3><i class="fas fa-sync"></i> İade Durumu Değiştir</h3>
            <button class="modal-close" onclick="closeModal('statusModal')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="status_iade_id">
            <div class="form-group">
                <select id="status_select" style="width:100%;padding:10px;border:2px solid var(--light-gray);border-radius:10px;font-size:14px;">
                    <?php foreach ($iade_durumlari as $key => $value): ?>
                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                    <?php endforeach; ?>
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
    var checkboxes = document.querySelectorAll('.iade-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Lütfen en az bir iade seçin!');
        return false;
    }
    var select = document.querySelector('.bulk-select');
    if (select.value === '') {
        alert('Lütfen bir işlem seçin!');
        return false;
    }
    return confirm(checkboxes.length + ' iade için durum değiştirilecek. Devam et?');
}

// ============================================
// TÜMÜNÜ SEÇ
// ============================================
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.iade-checkbox').forEach(function(cb) {
        cb.checked = this.checked;
    }, this);
});

// ============================================
// MODAL İŞLEMLERİ
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
// İADE DETAY GÖSTER
// ============================================
function viewIade(id) {
    const content = document.getElementById('detailContent');
    content.innerHTML = `
        <div style="text-align:center; padding:40px;">
            <i class="fas fa-spinner fa-spin" style="font-size:32px;color:var(--gray);"></i>
            <p>Yükleniyor...</p>
        </div>
    `;
    openModal('detailModal');
    
    fetch('<?php echo SITE_URL; ?>admin/iadeler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax_iade_detay=1&iade_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const i = data.data;
            
            let html = `
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
                    <div>
                        <h4 style="font-weight:700;margin-bottom:8px;">İade Bilgileri</h4>
                        <p><strong>İade ID:</strong> #${i.id}</p>
                        <p><strong>Sipariş No:</strong> #${i.siparis_no}</p>
                        <p><strong>Durum:</strong> <span class="status-badge ${i.iade_durumu}">${i.iade_durumu}</span></p>
                        <p><strong>Ürün:</strong> ${i.urun_adi}</p>
                        <p><strong>Ürün Kodu:</strong> ${i.urun_kodu}</p>
                        <p><strong>Adet:</strong> ${i.adet}</p>
                        <p><strong>Toplam Tutar:</strong> <strong style="color:var(--primary);">${Number(i.toplam_fiyat).toLocaleString('tr-TR')} ₺</strong></p>
                    </div>
                    <div>
                        <h4 style="font-weight:700;margin-bottom:8px;">Müşteri Bilgileri</h4>
                        <p><strong>Ad Soyad:</strong> ${i.kullanici_ad} ${i.kullanici_soyad}</p>
                        <p><strong>Email:</strong> ${i.email}</p>
                    </div>
                </div>
                <div>
                    <h4 style="font-weight:700;margin-bottom:8px;">İade Notu</h4>
                    <p style="background:var(--light-gray);padding:12px;border-radius:8px;min-height:60px;">
                        ${i.iade_notu || 'Not girilmemiş.'}
                    </p>
                </div>
            `;
            
            content.innerHTML = html;
        } else {
            content.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
        }
    })
    .catch(error => {
        content.innerHTML = `<div class="alert alert-error">Bir hata oluştu: ${error}</div>`;
    });
}

// ============================================
// DURUM DEĞİŞTİR
// ============================================
function changeStatus(id, current) {
    document.getElementById('status_iade_id').value = id;
    document.getElementById('status_select').value = current;
    openModal('statusModal');
}

document.getElementById('confirmStatusBtn').addEventListener('click', function() {
    const id = document.getElementById('status_iade_id').value;
    const yeni_durum = document.getElementById('status_select').value;
    const btn = this;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Değiştiriliyor...';
    btn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>admin/iadeler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax_iade_durum=1&iade_id=' + id + '&yeni_durum=' + yeni_durum
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.getElementById('durum-badge-' + id);
            const durumlar = {
                'talep_edildi': 'Talep Edildi',
                'onaylandi': 'Onaylandı',
                'reddedildi': 'Reddedildi',
                'tamamlandi': 'Tamamlandı'
            };
            badge.textContent = durumlar[yeni_durum] || yeni_durum;
            badge.className = 'status-badge ' + yeni_durum;
            closeModal('statusModal');
            alert(data.message);
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        alert('Bir hata oluştu: ' + error);
    })
    .finally(() => {
        btn.innerHTML = '<i class="fas fa-check"></i> Değiştir';
        btn.disabled = false;
    });
});
</script>


<?php
include 'footer.php';
?>