<?php
// =====================================================
// KARGO TAKİP - admin/kargo-takip.php
// =====================================================

// =====================================================
// AJAX İSTEKLERİ - HEADER'DAN ÖNCE
// =====================================================

// KARGO BİLGİSİ GÜNCELLE (AJAX)
if (isset($_POST['ajax_kargo_guncelle']) && isset($_POST['siparis_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $siparis_id = (int)$_POST['siparis_id'];
    $kargo_sirketi = clean($_POST['kargo_sirketi']);
    $kargo_takip_no = clean($_POST['kargo_takip_no']);
    $response = ['success' => false, 'message' => ''];
    
    try {
        $stmt = $db->prepare("
            UPDATE siparisler SET 
                kargo_sirketi = ?, 
                kargo_takip_no = ?,
                siparis_durumu = 'kargoda'
            WHERE id = ?
        ");
        $stmt->execute([$kargo_sirketi, $kargo_takip_no, $siparis_id]);
        
        $response['success'] = true;
        $response['message'] = 'Kargo bilgileri güncellendi!';
        $response['kargo_sirketi'] = $kargo_sirketi;
        $response['kargo_takip_no'] = $kargo_takip_no;
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
    
    // TOPLU KARGO GÜNCELLEME
    if (isset($_POST['toplu_kargo']) && isset($_POST['siparis_ids'])) {
        $siparis_ids = array_map('intval', $_POST['siparis_ids']);
        $kargo_sirketi = clean($_POST['kargo_sirketi']);
        $kargo_takip_no = clean($_POST['kargo_takip_no']);
        $ids_str = implode(',', $siparis_ids);
        
        try {
            $db->query("
                UPDATE siparisler SET 
                    kargo_sirketi = '$kargo_sirketi', 
                    kargo_takip_no = '$kargo_takip_no',
                    siparis_durumu = 'kargoda'
                WHERE id IN ($ids_str)
            ");
            $_SESSION['success'] = count($siparis_ids) . ' siparişin kargo bilgileri güncellendi!';
            header("Location: " . SITE_URL . "admin/kargo-takip.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Hata: ' . $e->getMessage();
            header("Location: " . SITE_URL . "admin/kargo-takip.php");
            exit();
        }
    }
}

// =====================================================
// NORMAL SAYFA
// =====================================================
$page_title = 'Kargo Takip';
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
// KARGO FİRMALARI LİSTESİ
// =====================================================
$kargo_firmalari = [
    'Aras Kargo' => 'Aras Kargo',
    'MNG Kargo' => 'MNG Kargo',
    'PTT Kargo' => 'PTT Kargo',
    'Sürat Kargo' => 'Sürat Kargo',
    'UPS' => 'UPS',
    'DHL' => 'DHL',
    'FedEx' => 'FedEx',
    'Yurtiçi Kargo' => 'Yurtiçi Kargo',
    'Horoz Lojistik' => 'Horoz Lojistik',
    'Diğer' => 'Diğer'
];

// =====================================================
// SİPARİŞ LİSTESİNİ ÇEK (Kargodakiler ve hazırlananlar)
// =====================================================
$where = "s.siparis_durumu IN ('hazirlaniyor', 'kargoya_verildi', 'kargoda')";
$params = [];

if ($durum) {
    $where .= " AND s.siparis_durumu = ?";
    $params[] = $durum;
}

if ($search) {
    $where .= " AND (s.siparis_no LIKE ? OR u.ad LIKE ? OR u.soyad LIKE ? OR s.kargo_takip_no LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Toplam sipariş sayısı
try {
    $countSql = "SELECT COUNT(*) as toplam FROM siparisler s LEFT JOIN kullanicilar u ON s.kullanici_id = u.id WHERE $where";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $toplam_kayit = $stmt->fetch()['toplam'] ?? 0;
} catch (PDOException $e) {
    $toplam_kayit = 0;
    $error = 'Veritabanı hatası (sayım): ' . $e->getMessage();
}

$toplam_sayfa = ceil($toplam_kayit / $limit);

// Siparişleri çek
try {
   $sql = "
    SELECT s.*, u.ad, u.soyad, u.email,
           (SELECT COUNT(*) FROM siparis_detay WHERE siparis_id = s.id) as urun_sayisi
    FROM siparisler s
    LEFT JOIN kullanicilar u ON s.kullanici_id = u.id
    WHERE $where
    ORDER BY s.olusturma_tarihi DESC
    LIMIT ? OFFSET ?
";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $siparisler = $stmt->fetchAll();
} catch (PDOException $e) {
    $siparisler = [];
    $error = 'Veritabanı hatası (liste): ' . $e->getMessage();
}

// Sipariş durumları
$durumlar = [
    'hazirlaniyor' => 'Hazırlanıyor',
    'kargoya_verildi' => 'Kargoya Verildi',
    'kargoda' => 'Kargoda',
    'teslim_edildi' => 'Teslim Edildi'
];
?>

<!-- ============================================ -->
<!-- SAYFA BAŞLIĞI -->
<!-- ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-truck"></i> Kargo Takip</h2>
        <span class="page-count"><?php echo $toplam_kayit; ?> sipariş</span>
    </div>
    <div class="page-header-right">
        <a href="<?php echo SITE_URL; ?>admin/siparisler.php" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Tüm Siparişler
        </a>
    </div>
</div>

<!-- ============================================ -->
<!-- FİLTRELEME -->
<!-- ============================================ -->
<div class="filter-section">
    <form method="GET" class="filter-form">
        <div class="filter-row">
            <div class="filter-item">
                <input type="text" name="search" placeholder="Sipariş No, Müşteri, Takip No..." value="<?php echo $search; ?>">
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
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrele</button>
                <a href="<?php echo SITE_URL; ?>admin/kargo-takip.php" class="btn btn-outline"><i class="fas fa-undo"></i> Temizle</a>
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
<!-- KARGO LİSTESİ -->
<!-- ============================================ -->
<div class="table-card">
    <div class="table-header">
        <h3>Kargo Takip Listesi</h3>
        <div class="table-actions">
            <form method="POST" class="bulk-form" id="bulkKargoForm">
                <select name="kargo_sirketi" class="bulk-select" required>
                    <option value="">Kargo Firması Seç</option>
                    <?php foreach ($kargo_firmalari as $value): ?>
                        <option value="<?php echo $value; ?>"><?php echo $value; ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="kargo_takip_no" placeholder="Takip No" class="bulk-input" required>
                <button type="submit" name="toplu_kargo" class="btn btn-sm btn-primary" onclick="return confirmBulkKargo()">
                    <i class="fas fa-truck"></i> Toplu Kargo Gönder
                </button>
            </form>
        </div>
    </div>
    
    <?php if (!empty($siparisler)): ?>
    <div class="table-responsive">
        <form method="POST" id="siparisForm">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" id="selectAll"></th>
                        <th>Sipariş No</th>
                        <th>Müşteri</th>
                        <th>Ürün</th>
                        <th>Tutar</th>
                        <th>Durum</th>
                        <th>Kargo Firması</th>
                        <th>Takip No</th>
                        <th width="160">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($siparisler as $siparis): ?>
                    <tr id="siparis-row-<?php echo $siparis['id']; ?>">
                        <td><input type="checkbox" name="siparis_ids[]" value="<?php echo $siparis['id']; ?>" class="siparis-checkbox"></td>
                        <td>
                            <strong>#<?php echo $siparis['siparis_no']; ?></strong>
                        </td>
                        <td>
                            <?php echo $siparis['ad'] . ' ' . $siparis['soyad']; ?>
                            <br><small style="color: var(--gray);"><?php echo $siparis['email']; ?></small>
                        </td>
                        <td><?php echo $siparis['urun_sayisi']; ?> ürün</td>
                        <td><strong><?php echo number_format($siparis['toplam_tutar'], 2, ',', '.'); ?> ₺</strong></td>
                        <td>
                            <span class="status-badge <?php echo $siparis['siparis_durumu']; ?>">
                                <?php 
                                $durum_etiket = [
                                    'hazirlaniyor' => 'Hazırlanıyor',
                                    'kargoya_verildi' => 'Kargoya Verildi',
                                    'kargoda' => 'Kargoda',
                                    'teslim_edildi' => 'Teslim Edildi'
                                ];
                                echo $durum_etiket[$siparis['siparis_durumu']] ?? $siparis['siparis_durumu']; 
                                ?>
                            </span>
                        </td>
                        <td id="kargo-firma-<?php echo $siparis['id']; ?>">
                            <?php echo $siparis['kargo_sirketi'] ?? '<span style="color: var(--gray);">-</span>'; ?>
                        </td>
                        <td id="kargo-takip-<?php echo $siparis['id']; ?>">
                            <?php if ($siparis['kargo_takip_no']): ?>
                                <strong><?php echo $siparis['kargo_takip_no']; ?></strong>
                            <?php else: ?>
                                <span style="color: var(--gray);">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary" onclick="editKargo(<?php echo $siparis['id']; ?>)" title="Kargo Düzenle">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-success" onclick="updateStatus(<?php echo $siparis['id']; ?>, 'teslim_edildi')" title="Teslim Et">
                                    <i class="fas fa-check"></i>
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
            Toplam <?php echo $toplam_kayit; ?> siparişten <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $toplam_kayit); ?> arası
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
        <i class="fas fa-truck"></i>
        <h3>Kargo bekleyen sipariş yok</h3>
        <p>Tüm siparişler teslim edilmiş veya henüz kargoya verilmemiş.</p>
        <a href="<?php echo SITE_URL; ?>admin/siparisler.php" class="btn btn-primary" style="margin-top:16px;">
            <i class="fas fa-list"></i> Tüm Siparişlere Git
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================ -->
<!-- KARGO DÜZENLEME MODAL -->
<!-- ============================================ -->
<div class="modal" id="kargoModal">
    <div class="modal-overlay" onclick="closeModal('kargoModal')"></div>
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h3><i class="fas fa-truck"></i> Kargo Bilgileri</h3>
            <button class="modal-close" onclick="closeModal('kargoModal')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="kargo_siparis_id">
            <div class="form-group">
                <label>Sipariş No</label>
                <p id="kargo_siparis_no" style="font-weight:700;font-size:16px;"></p>
            </div>
            <div class="form-group">
                <label>Kargo Firması</label>
                <select id="kargo_sirketi" style="width:100%;padding:10px;border:2px solid var(--light-gray);border-radius:10px;font-size:14px;">
                    <option value="">Kargo Firması Seçin</option>
                    <?php foreach ($kargo_firmalari as $value): ?>
                        <option value="<?php echo $value; ?>"><?php echo $value; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Takip Numarası</label>
                <input type="text" id="kargo_takip_no" placeholder="Takip numarasını girin" style="width:100%;padding:10px;border:2px solid var(--light-gray);border-radius:10px;font-size:14px;">
            </div>
            <div class="form-actions" style="justify-content:center;">
                <button class="btn btn-outline" onclick="closeModal('kargoModal')">İptal</button>
                <button class="btn btn-primary" id="confirmKargoBtn">
                    <i class="fas fa-save"></i> Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- JAVASCRIPT -->
<!-- ============================================ -->
<script>
// ============================================
// TÜMÜNÜ SEÇ
// ============================================
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.siparis-checkbox').forEach(function(cb) {
        cb.checked = this.checked;
    }, this);
});

// ============================================
// TOPLU KARGO ONAY
// ============================================
function confirmBulkKargo() {
    var checkboxes = document.querySelectorAll('.siparis-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Lütfen en az bir sipariş seçin!');
        return false;
    }
    var firma = document.querySelector('select[name="kargo_sirketi"]').value;
    var takip = document.querySelector('input[name="kargo_takip_no"]').value;
    if (firma === '') {
        alert('Lütfen bir kargo firması seçin!');
        return false;
    }
    if (takip === '') {
        alert('Lütfen takip numarası girin!');
        return false;
    }
    return confirm(checkboxes.length + ' sipariş için kargo bilgileri gönderilecek. Devam et?');
}

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
// KARGO DÜZENLE
// ============================================
function editKargo(id) {
    var row = document.getElementById('siparis-row-' + id);
    var siparisNo = row.querySelector('td:nth-child(2) strong').textContent;
    
    document.getElementById('kargo_siparis_id').value = id;
    document.getElementById('kargo_siparis_no').textContent = siparisNo;
    
    var firma = document.getElementById('kargo-firma-' + id).textContent.trim();
    var takip = document.getElementById('kargo-takip-' + id).textContent.trim();
    
    document.getElementById('kargo_sirketi').value = firma !== '-' ? firma : '';
    document.getElementById('kargo_takip_no').value = takip !== '-' ? takip : '';
    
    openModal('kargoModal');
}

document.getElementById('confirmKargoBtn').addEventListener('click', function() {
    const id = document.getElementById('kargo_siparis_id').value;
    const firma = document.getElementById('kargo_sirketi').value;
    const takip = document.getElementById('kargo_takip_no').value;
    const btn = this;
    
    if (firma === '') {
        alert('Lütfen bir kargo firması seçin!');
        return;
    }
    if (takip === '') {
        alert('Lütfen takip numarası girin!');
        return;
    }
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...';
    btn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>admin/kargo-takip.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax_kargo_guncelle=1&siparis_id=' + id + '&kargo_sirketi=' + encodeURIComponent(firma) + '&kargo_takip_no=' + encodeURIComponent(takip)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('kargo-firma-' + id).textContent = data.kargo_sirketi;
            document.getElementById('kargo-takip-' + id).innerHTML = '<strong>' + data.kargo_takip_no + '</strong>';
            
            var badge = document.querySelector('#siparis-row-' + id + ' .status-badge');
            badge.textContent = 'Kargoda';
            badge.className = 'status-badge kargoda';
            
            closeModal('kargoModal');
            alert(data.message);
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        alert('Bir hata oluştu: ' + error);
    })
    .finally(() => {
        btn.innerHTML = '<i class="fas fa-save"></i> Kaydet';
        btn.disabled = false;
    });
});

// ============================================
// TESLİM ET
// ============================================
function updateStatus(id, durum) {
    if (!confirm('Bu siparişi teslim edildi olarak işaretlemek istediğinize emin misiniz?')) {
        return;
    }
    
    fetch('<?php echo SITE_URL; ?>admin/siparisler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax_durum=1&siparis_id=' + id + '&yeni_durum=' + durum
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            var row = document.getElementById('siparis-row-' + id);
            row.style.transition = 'all 0.3s';
            row.style.opacity = '0';
            row.style.transform = 'translateX(-20px)';
            setTimeout(function() {
                row.remove();
                if (document.querySelectorAll('#siparisForm tbody tr').length === 0) {
                    location.reload();
                }
            }, 300);
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        alert('Bir hata oluştu: ' + error);
    });
}
</script>


<?php
include 'footer.php';
?>