<?php
// =====================================================
// KULLANICI YÖNETİMİ - admin/kullanicilar.php
// =====================================================

// =====================================================
// AJAX İSTEKLERİ - HEADER'DAN ÖNCE
// =====================================================

// KULLANICI DURUMU GÜNCELLE (AJAX)
if (isset($_POST['ajax_durum']) && isset($_POST['kullanici_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $kullanici_id = (int)$_POST['kullanici_id'];
    $yeni_durum = $_POST['yeni_durum'];
    $response = ['success' => false, 'message' => ''];
    
    try {
        $stmt = $db->prepare("UPDATE kullanicilar SET durum = ? WHERE id = ?");
        $stmt->execute([$yeni_durum, $kullanici_id]);
        
        $response['success'] = true;
        $response['message'] = 'Kullanıcı durumu güncellendi!';
        $response['yeni_durum'] = $yeni_durum;
    } catch (PDOException $e) {
        $response['message'] = 'Hata: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// KULLANICI DETAY (AJAX)
if (isset($_POST['ajax_detay']) && isset($_POST['kullanici_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $kullanici_id = (int)$_POST['kullanici_id'];
    $response = ['success' => false, 'data' => null];
    
    try {
        $stmt = $db->prepare("
            SELECT k.*, 
                   (SELECT COUNT(*) FROM siparisler WHERE kullanici_id = k.id) as toplam_siparis,
                   (SELECT SUM(toplam_tutar) FROM siparisler WHERE kullanici_id = k.id AND odeme_durumu = 'onaylandi') as toplam_harcama
            FROM kullanicilar k
            WHERE k.id = ?
        ");
        $stmt->execute([$kullanici_id]);
        $kullanici = $stmt->fetch();
        
        if ($kullanici) {
            $response['success'] = true;
            $response['data'] = $kullanici;
        } else {
            $response['message'] = 'Kullanıcı bulunamadı!';
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
    
    // TOPLU KULLANICI DURUM GÜNCELLEME
    if (isset($_POST['toplu_islem']) && isset($_POST['kullanici_ids'])) {
        $kullanici_ids = array_map('intval', $_POST['kullanici_ids']);
        $islem = $_POST['toplu_islem'];
        $ids_str = implode(',', $kullanici_ids);
        
        try {
            $db->query("UPDATE kullanicilar SET durum = '$islem' WHERE id IN ($ids_str)");
            $_SESSION['success'] = count($kullanici_ids) . ' kullanıcı güncellendi!';
            header("Location: " . SITE_URL . "admin/kullanicilar.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Hata: ' . $e->getMessage();
            header("Location: " . SITE_URL . "admin/kullanicilar.php");
            exit();
        }
    }
}

// =====================================================
// NORMAL SAYFA
// =====================================================
$page_title = 'Kullanıcı Yönetimi';
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
$yetki = isset($_GET['yetki']) ? clean($_GET['yetki']) : '';
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$sayfa = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
$limit = 20;
$offset = ($sayfa - 1) * $limit;

// =====================================================
// KULLANICI LİSTESİNİ ÇEK
// =====================================================
$where = "1=1";
$params = [];

if ($durum) {
    $where .= " AND durum = ?";
    $params[] = $durum;
}

if ($yetki) {
    $where .= " AND yetki = ?";
    $params[] = $yetki;
}

if ($search) {
    $where .= " AND (ad LIKE ? OR soyad LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Toplam kullanıcı sayısı
try {
    $countSql = "SELECT COUNT(*) as toplam FROM kullanicilar WHERE $where";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $toplam_kayit = $stmt->fetch()['toplam'] ?? 0;
} catch (PDOException $e) {
    $toplam_kayit = 0;
    $error = 'Veritabanı hatası: ' . $e->getMessage();
}

$toplam_sayfa = ceil($toplam_kayit / $limit);

// Kullanıcıları çek
try {
    $sql = "
        SELECT k.*,
               (SELECT COUNT(*) FROM siparisler WHERE kullanici_id = k.id) as toplam_siparis
        FROM kullanicilar k
        WHERE $where
        ORDER BY k.kayit_tarihi DESC
        LIMIT ? OFFSET ?
    ";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $kullanicilar = $stmt->fetchAll();
} catch (PDOException $e) {
    $kullanicilar = [];
    $error = 'Veritabanı hatası: ' . $e->getMessage();
}

// Kullanıcı durumları
$durumlar = [
    'aktif' => 'Aktif',
    'pasif' => 'Pasif',
    'beklemede' => 'Beklemede',
    'engelli' => 'Engelli'
];

$yetkiler = [
    'super_admin' => 'Super Admin',
    'admin' => 'Admin',
    'editor' => 'Editor',
    'user' => 'Kullanıcı'
];
?>

<!-- ============================================ -->
<!-- SAYFA BAŞLIĞI -->
<!-- ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-users"></i> Kullanıcı Yönetimi</h2>
        <span class="page-count"><?php echo $toplam_kayit; ?> kullanıcı</span>
    </div>
</div>

<!-- ============================================ -->
<!-- FİLTRELEME -->
<!-- ============================================ -->
<div class="filter-section">
    <form method="GET" class="filter-form">
        <div class="filter-row">
            <div class="filter-item">
                <input type="text" name="search" placeholder="Ad, soyad, email ara..." value="<?php echo $search; ?>">
            </div>
            <div class="filter-item">
                <select name="durum">
                    <option value="">Tüm Durumlar</option>
                    <?php foreach ($durumlar as $key => $value): ?>
                        <option value="<?php echo $key; ?>" <?php echo $durum == $key ? 'selected' : ''; ?>>
                            <?php echo $value; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-item">
                <select name="yetki">
                    <option value="">Tüm Yetkiler</option>
                    <?php foreach ($yetkiler as $key => $value): ?>
                        <option value="<?php echo $key; ?>" <?php echo $yetki == $key ? 'selected' : ''; ?>>
                            <?php echo $value; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrele</button>
                <a href="<?php echo SITE_URL; ?>admin/kullanicilar.php" class="btn btn-outline"><i class="fas fa-undo"></i> Temizle</a>
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
<!-- KULLANICI LİSTESİ -->
<!-- ============================================ -->
<div class="table-card">
    <div class="table-header">
        <h3>Kullanıcı Listesi</h3>
        <div class="table-actions">
            <form method="POST" class="bulk-form">
                <select name="toplu_islem" class="bulk-select">
                    <option value="">Toplu İşlem</option>
                    <option value="aktif">Aktifleştir</option>
                    <option value="pasif">Pasifleştir</option>
                    <option value="engelli">Engelli Yap</option>
                </select>
                <button type="submit" name="toplu_islem" class="btn btn-sm btn-primary" onclick="return confirmBulk()">
                    <i class="fas fa-check"></i> Uygula
                </button>
            </form>
        </div>
    </div>
    
    <?php if (!empty($kullanicilar)): ?>
    <div class="table-responsive">
        <form method="POST" id="kullaniciForm">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" id="selectAll"></th>
                        <th>#</th>
                        <th>Ad Soyad</th>
                        <th>Email</th>
                        <th>Yetki</th>
                        <th>Durum</th>
                        <th>Sipariş</th>
                        <th>Kayıt Tarihi</th>
                        <th>Son Giriş</th>
                        <th width="140">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kullanicilar as $kullanici): ?>
                    <tr id="kullanici-row-<?php echo $kullanici['id']; ?>">
                        <td><input type="checkbox" name="kullanici_ids[]" value="<?php echo $kullanici['id']; ?>" class="kullanici-checkbox"></td>
                        <td><?php echo $kullanici['id']; ?></td>
                        <td>
                            <strong><?php echo $kullanici['ad'] . ' ' . $kullanici['soyad']; ?></strong>
                        </td>
                        <td><?php echo $kullanici['email']; ?></td>
                        <td>
                            <span class="yetki-badge <?php echo $kullanici['yetki']; ?>">
                                <?php echo $yetkiler[$kullanici['yetki']] ?? $kullanici['yetki']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $kullanici['durum']; ?>" id="durum-badge-<?php echo $kullanici['id']; ?>">
                                <?php echo $durumlar[$kullanici['durum']] ?? $kullanici['durum']; ?>
                            </span>
                        </td>
                        <td><?php echo $kullanici['toplam_siparis']; ?></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($kullanici['kayit_tarihi'])); ?></td>
                        <td>
                            <?php if ($kullanici['son_giris']): ?>
                                <?php echo date('d.m.Y H:i', strtotime($kullanici['son_giris'])); ?>
                            <?php else: ?>
                                <span style="color: var(--gray);">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary" onclick="viewKullanici(<?php echo $kullanici['id']; ?>)" title="Detay">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-warning" onclick="changeStatus(<?php echo $kullanici['id']; ?>, '<?php echo $kullanici['durum']; ?>')" title="Durum Değiştir">
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
            Toplam <?php echo $toplam_kayit; ?> kullanıcıdan <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $toplam_kayit); ?> arası
        </div>
        <div class="pagination">
            <?php for ($i = 1; $i <= $toplam_sayfa; $i++): ?>
                <?php if ($i == $sayfa): ?>
                    <span class="page-link active"><?php echo $i; ?></span>
                <?php elseif ($i <= 3 || $i > $toplam_sayfa - 3 || abs($i - $sayfa) <= 1): ?>
                    <a href="?sayfa=<?php echo $i; ?>&durum=<?php echo $durum; ?>&yetki=<?php echo $yetki; ?>&search=<?php echo $search; ?>" class="page-link">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-users"></i>
        <h3>Henüz kullanıcı yok</h3>
        <p>İlk kullanıcı kaydını bekleyin.</p>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================ -->
<!-- KULLANICI DETAY MODAL -->
<!-- ============================================ -->
<div class="modal" id="detailModal">
    <div class="modal-overlay" onclick="closeModal('detailModal')"></div>
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3><i class="fas fa-user"></i> Kullanıcı Detayı</h3>
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
            <h3><i class="fas fa-sync"></i> Kullanıcı Durumu Değiştir</h3>
            <button class="modal-close" onclick="closeModal('statusModal')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="status_kullanici_id">
            <div class="form-group">
                <select id="status_select" style="width:100%;padding:10px;border:2px solid var(--light-gray);border-radius:10px;font-size:14px;">
                    <?php foreach ($durumlar as $key => $value): ?>
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
    var checkboxes = document.querySelectorAll('.kullanici-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Lütfen en az bir kullanıcı seçin!');
        return false;
    }
    var select = document.querySelector('.bulk-select');
    if (select.value === '') {
        alert('Lütfen bir işlem seçin!');
        return false;
    }
    return confirm(checkboxes.length + ' kullanıcı için durum değiştirilecek. Devam et?');
}

// ============================================
// TÜMÜNÜ SEÇ
// ============================================
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.kullanici-checkbox').forEach(function(cb) {
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
// KULLANICI DETAY GÖSTER
// ============================================
function viewKullanici(id) {
    const content = document.getElementById('detailContent');
    content.innerHTML = `
        <div style="text-align:center; padding:40px;">
            <i class="fas fa-spinner fa-spin" style="font-size:32px;color:var(--gray);"></i>
            <p>Yükleniyor...</p>
        </div>
    `;
    openModal('detailModal');
    
    fetch('<?php echo SITE_URL; ?>admin/kullanicilar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax_detay=1&kullanici_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const k = data.data;
            
            let html = `
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
                    <div>
                        <h4 style="font-weight:700;margin-bottom:8px;">Kullanıcı Bilgileri</h4>
                        <p><strong>Ad Soyad:</strong> ${k.ad} ${k.soyad}</p>
                        <p><strong>Email:</strong> ${k.email}</p>
                        <p><strong>Telefon:</strong> ${k.telefon || '-'}</p>
                        <p><strong>Yetki:</strong> <span class="yetki-badge ${k.yetki}">${k.yetki}</span></p>
                        <p><strong>Durum:</strong> <span class="status-badge ${k.durum}">${k.durum}</span></p>
                    </div>
                    <div>
                        <h4 style="font-weight:700;margin-bottom:8px;">İstatistikler</h4>
                        <p><strong>Toplam Sipariş:</strong> ${k.toplam_siparis || 0}</p>
                        <p><strong>Toplam Harcama:</strong> <strong style="color:var(--primary);">${Number(k.toplam_harcama || 0).toLocaleString('tr-TR')} ₺</strong></p>
                        <p><strong>Kayıt Tarihi:</strong> ${new Date(k.kayit_tarihi).toLocaleString('tr-TR')}</p>
                        <p><strong>Son Giriş:</strong> ${k.son_giris ? new Date(k.son_giris).toLocaleString('tr-TR') : '-'}</p>
                    </div>
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
    document.getElementById('status_kullanici_id').value = id;
    document.getElementById('status_select').value = current;
    openModal('statusModal');
}

document.getElementById('confirmStatusBtn').addEventListener('click', function() {
    const id = document.getElementById('status_kullanici_id').value;
    const yeni_durum = document.getElementById('status_select').value;
    const btn = this;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Değiştiriliyor...';
    btn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>admin/kullanicilar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax_durum=1&kullanici_id=' + id + '&yeni_durum=' + yeni_durum
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.getElementById('durum-badge-' + id);
            const durumlar = {
                'aktif': 'Aktif',
                'pasif': 'Pasif',
                'beklemede': 'Beklemede',
                'engelli': 'Engelli'
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