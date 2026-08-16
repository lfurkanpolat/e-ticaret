<?php
// =====================================================
// KAMPANYA YÖNETİMİ - admin/kampanyalar.php
// =====================================================

// =====================================================
// AJAX İSTEKLERİ - HEADER'DAN ÖNCE
// =====================================================

// KAMPANYA SİL (AJAX)
if (isset($_POST['ajax_sil']) && isset($_POST['kampanya_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $kampanya_id = (int)$_POST['kampanya_id'];
    $response = ['success' => false, 'message' => ''];
    
    try {
        $stmt = $db->prepare("DELETE FROM kampanyalar WHERE id = ?");
        $stmt->execute([$kampanya_id]);
        
        $response['success'] = true;
        $response['message'] = 'Kampanya silindi!';
    } catch (PDOException $e) {
        $response['message'] = 'Hata: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// KAMPANYA DETAY (AJAX - Düzenleme için)
if (isset($_POST['ajax_detay']) && isset($_POST['kampanya_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $kampanya_id = (int)$_POST['kampanya_id'];
    $response = ['success' => false, 'data' => null];
    
    try {
        $stmt = $db->prepare("SELECT * FROM kampanyalar WHERE id = ?");
        $stmt->execute([$kampanya_id]);
        $kampanya = $stmt->fetch();
        
        if ($kampanya) {
            $response['success'] = true;
            $response['data'] = $kampanya;
        } else {
            $response['message'] = 'Kampanya bulunamadı!';
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
    
    // KAMPANYA EKLE
    if (isset($_POST['kampanya_ekle'])) {
        $ad = clean($_POST['ad']);
        $kod = strtoupper(clean($_POST['kod']));
        $indirim_tipi = clean($_POST['indirim_tipi']);
        $indirim_miktari = (float)$_POST['indirim_miktari'];
        $min_sepet_tutari = !empty($_POST['min_sepet_tutari']) ? (float)$_POST['min_sepet_tutari'] : null;
        $baslangic_tarihi = clean($_POST['baslangic_tarihi']);
        $bitis_tarihi = clean($_POST['bitis_tarihi']);
        $aktif = isset($_POST['aktif']) ? 1 : 0;
        
        $errors = [];
        
        if (empty($ad)) $errors[] = 'Kampanya adı boş olamaz.';
        if (empty($kod)) $errors[] = 'Kampanya kodu boş olamaz.';
        if ($indirim_miktari <= 0) $errors[] = 'İndirim miktarı 0\'dan büyük olmalıdır.';
        if (empty($baslangic_tarihi)) $errors[] = 'Başlangıç tarihi boş olamaz.';
        if (empty($bitis_tarihi)) $errors[] = 'Bitiş tarihi boş olamaz.';
        
        // Kod benzersiz mi?
        if (empty($errors)) {
            try {
                $stmt = $db->prepare("SELECT id FROM kampanyalar WHERE kod = ?");
                $stmt->execute([$kod]);
                if ($stmt->fetch()) {
                    $errors[] = 'Bu kampanya kodu zaten kullanılıyor!';
                }
            } catch (PDOException $e) {}
        }
        
        // Tarih kontrolü
        if (empty($errors) && strtotime($baslangic_tarihi) > strtotime($bitis_tarihi)) {
            $errors[] = 'Başlangıç tarihi bitiş tarihinden büyük olamaz!';
        }
        
        if (empty($errors)) {
            try {
                $sql = "INSERT INTO kampanyalar (
                            ad, kod, indirim_tipi, indirim_miktari, 
                            min_sepet_tutari, baslangic_tarihi, bitis_tarihi, aktif
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?
                        )";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $ad, $kod, $indirim_tipi, $indirim_miktari,
                    $min_sepet_tutari, $baslangic_tarihi, $bitis_tarihi, $aktif
                ]);
                
                $_SESSION['success'] = 'Kampanya başarıyla eklendi!';
                header("Location: " . SITE_URL . "admin/kampanyalar.php");
                exit();
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Veritabanı hatası: ' . $e->getMessage();
                header("Location: " . SITE_URL . "admin/kampanyalar.php");
                exit();
            }
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
            header("Location: " . SITE_URL . "admin/kampanyalar.php");
            exit();
        }
    }
    
    // KAMPANYA DÜZENLE
    if (isset($_POST['kampanya_duzenle'])) {
        $id = (int)$_POST['id'];
        $ad = clean($_POST['ad']);
        $kod = strtoupper(clean($_POST['kod']));
        $indirim_tipi = clean($_POST['indirim_tipi']);
        $indirim_miktari = (float)$_POST['indirim_miktari'];
        $min_sepet_tutari = !empty($_POST['min_sepet_tutari']) ? (float)$_POST['min_sepet_tutari'] : null;
        $baslangic_tarihi = clean($_POST['baslangic_tarihi']);
        $bitis_tarihi = clean($_POST['bitis_tarihi']);
        $aktif = isset($_POST['aktif']) ? 1 : 0;
        
        $errors = [];
        
        if (empty($ad)) $errors[] = 'Kampanya adı boş olamaz.';
        if (empty($kod)) $errors[] = 'Kampanya kodu boş olamaz.';
        if ($indirim_miktari <= 0) $errors[] = 'İndirim miktarı 0\'dan büyük olmalıdır.';
        if (empty($baslangic_tarihi)) $errors[] = 'Başlangıç tarihi boş olamaz.';
        if (empty($bitis_tarihi)) $errors[] = 'Bitiş tarihi boş olamaz.';
        
        // Kod benzersiz mi? (kendisi hariç)
        if (empty($errors)) {
            try {
                $stmt = $db->prepare("SELECT id FROM kampanyalar WHERE kod = ? AND id != ?");
                $stmt->execute([$kod, $id]);
                if ($stmt->fetch()) {
                    $errors[] = 'Bu kampanya kodu zaten kullanılıyor!';
                }
            } catch (PDOException $e) {}
        }
        
        // Tarih kontrolü
        if (empty($errors) && strtotime($baslangic_tarihi) > strtotime($bitis_tarihi)) {
            $errors[] = 'Başlangıç tarihi bitiş tarihinden büyük olamaz!';
        }
        
        if (empty($errors)) {
            try {
                $sql = "UPDATE kampanyalar SET 
                            ad = ?, kod = ?, indirim_tipi = ?, indirim_miktari = ?,
                            min_sepet_tutari = ?, baslangic_tarihi = ?, bitis_tarihi = ?, aktif = ?
                        WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $ad, $kod, $indirim_tipi, $indirim_miktari,
                    $min_sepet_tutari, $baslangic_tarihi, $bitis_tarihi, $aktif, $id
                ]);
                
                $_SESSION['success'] = 'Kampanya güncellendi!';
                header("Location: " . SITE_URL . "admin/kampanyalar.php");
                exit();
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Veritabanı hatası: ' . $e->getMessage();
                header("Location: " . SITE_URL . "admin/kampanyalar.php");
                exit();
            }
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
            header("Location: " . SITE_URL . "admin/kampanyalar.php");
            exit();
        }
    }
}

// =====================================================
// NORMAL SAYFA
// =====================================================
$page_title = 'Kampanya Yönetimi';
include 'header.php';

// Session mesajlarını al ve temizle
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error']);
unset($_SESSION['success']);

// =====================================================
// KAMPANYA LİSTESİNİ ÇEK
// =====================================================
try {
    $kampanyalar = $db->query("SELECT * FROM kampanyalar ORDER BY id DESC")->fetchAll();
} catch (PDOException $e) {
    $kampanyalar = [];
}
?>

<!-- ============================================ -->
<!-- SAYFA BAŞLIĞI -->
<!-- ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-gift"></i> Kampanya Yönetimi</h2>
        <span class="page-count"><?php echo count($kampanyalar); ?> kampanya</span>
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
<!-- KAMPANYA EKLEME FORM -->
<!-- ============================================ -->
<div class="form-card">
    <h3 class="form-section-title">
        <i class="fas fa-plus-circle"></i> Yeni Kampanya Ekle
    </h3>
    <form method="POST" action="" class="kampanya-form">
        <div class="form-row">
            <div class="form-group">
                <label>Kampanya Adı *</label>
                <input type="text" name="ad" placeholder="Kampanya adı" required>
            </div>
            <div class="form-group">
                <label>Kampanya Kodu *</label>
                <input type="text" name="kod" placeholder="Örn: KAMPANYA10" required style="text-transform:uppercase;">
                <small class="form-hint">Büyük harf, rakam ve alt çizgi kullanabilirsiniz.</small>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>İndirim Tipi *</label>
                <select name="indirim_tipi" required>
                    <option value="yuzde">Yüzde (%)</option>
                    <option value="sabit">Sabit (₺)</option>
                </select>
            </div>
            <div class="form-group">
                <label>İndirim Miktarı *</label>
                <input type="number" name="indirim_miktari" step="0.01" placeholder="0.00" required>
                <small class="form-hint">Yüzde ise 10 = %10, Sabit ise 10 = 10₺</small>
            </div>
        </div>
        
        <div class="form-group">
            <label>Minimum Sepet Tutarı</label>
            <input type="number" name="min_sepet_tutari" step="0.01" placeholder="0.00">
            <small class="form-hint">Boş bırakırsanız limit yoktur.</small>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Başlangıç Tarihi *</label>
                <input type="datetime-local" name="baslangic_tarihi" required>
            </div>
            <div class="form-group">
                <label>Bitiş Tarihi *</label>
                <input type="datetime-local" name="bitis_tarihi" required>
            </div>
        </div>
        
        <div class="form-group">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="aktif" value="1" checked>
                <span>Aktif</span>
            </label>
        </div>
        
        <div class="form-actions">
            <button type="submit" name="kampanya_ekle" class="btn btn-primary">
                <i class="fas fa-save"></i> Kampanya Ekle
            </button>
        </div>
    </form>
</div>

<!-- ============================================ -->
<!-- KAMPANYA LİSTESİ -->
<!-- ============================================ -->
<div class="table-card">
    <div class="table-header">
        <h3><i class="fas fa-list"></i> Kampanya Listesi</h3>
    </div>
    
    <?php if (!empty($kampanyalar)): ?>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="60">#</th>
                    <th>Kampanya Adı</th>
                    <th>Kod</th>
                    <th>İndirim</th>
                    <th>Min. Tutar</th>
                    <th>Tarih Aralığı</th>
                    <th>Durum</th>
                    <th width="140">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kampanyalar as $kampanya): ?>
                <tr id="kampanya-row-<?php echo $kampanya['id']; ?>">
                    <td><?php echo $kampanya['id']; ?></td>
                    <td><strong><?php echo $kampanya['ad']; ?></strong></td>
                    <td><code><?php echo $kampanya['kod']; ?></code></td>
                    <td>
                        <?php if ($kampanya['indirim_tipi'] == 'yuzde'): ?>
                            %<?php echo number_format($kampanya['indirim_miktari'], 0); ?>
                        <?php else: ?>
                            <?php echo number_format($kampanya['indirim_miktari'], 2, ',', '.'); ?> ₺
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($kampanya['min_sepet_tutari']): ?>
                            <?php echo number_format($kampanya['min_sepet_tutari'], 2, ',', '.'); ?> ₺
                        <?php else: ?>
                            <span style="color: var(--gray);">Yok</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo date('d.m.Y H:i', strtotime($kampanya['baslangic_tarihi'])); ?>
                        <br>
                        <small style="color: var(--gray);">
                            → <?php echo date('d.m.Y H:i', strtotime($kampanya['bitis_tarihi'])); ?>
                        </small>
                    </td>
                    <td>
                        <?php
                        $now = time();
                        $baslangic = strtotime($kampanya['baslangic_tarihi']);
                        $bitis = strtotime($kampanya['bitis_tarihi']);
                        $aktif_mi = $kampanya['aktif'] && $now >= $baslangic && $now <= $bitis;
                        ?>
                        <span class="status-badge <?php echo $aktif_mi ? 'aktif' : 'pasif'; ?>">
                            <?php echo $aktif_mi ? '<i class="fas fa-check-circle"></i> Aktif' : '<i class="fas fa-times-circle"></i> Pasif'; ?>
                        </span>
                        <?php if ($kampanya['aktif'] && $now < $baslangic): ?>
                            <br><small style="color: #F39C12;">(Başlamadı)</small>
                        <?php elseif ($kampanya['aktif'] && $now > $bitis): ?>
                            <br><small style="color: #FF6B6B;">(Sona Erdi)</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-primary" onclick="editKampanya(<?php echo $kampanya['id']; ?>)" title="Düzenle">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteKampanya(<?php echo $kampanya['id']; ?>)" title="Sil">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-gift"></i>
        <h3>Henüz kampanya eklenmemiş</h3>
        <p>İlk kampanyanızı ekleyerek başlayın.</p>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================ -->
<!-- DÜZENLEME MODAL -->
<!-- ============================================ -->
<div class="modal" id="editModal">
    <div class="modal-overlay" onclick="closeModal('editModal')"></div>
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Kampanya Düzenle</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editForm" method="POST">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="kampanya_duzenle" value="1">
                
                <div class="form-group">
                    <label>Kampanya Adı *</label>
                    <input type="text" name="ad" id="edit_ad" required>
                </div>
                
                <div class="form-group">
                    <label>Kampanya Kodu *</label>
                    <input type="text" name="kod" id="edit_kod" required style="text-transform:uppercase;">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>İndirim Tipi *</label>
                        <select name="indirim_tipi" id="edit_indirim_tipi" required>
                            <option value="yuzde">Yüzde (%)</option>
                            <option value="sabit">Sabit (₺)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>İndirim Miktarı *</label>
                        <input type="number" name="indirim_miktari" id="edit_indirim_miktari" step="0.01" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Minimum Sepet Tutarı</label>
                    <input type="number" name="min_sepet_tutari" id="edit_min_sepet_tutari" step="0.01" placeholder="0.00">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Başlangıç Tarihi *</label>
                        <input type="datetime-local" name="baslangic_tarihi" id="edit_baslangic_tarihi" required>
                    </div>
                    <div class="form-group">
                        <label>Bitiş Tarihi *</label>
                        <input type="datetime-local" name="bitis_tarihi" id="edit_bitis_tarihi" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="aktif" id="edit_aktif" value="1">
                        <span>Aktif</span>
                    </label>
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

<!-- ============================================ -->
<!-- SİLME ONAY MODAL -->
<!-- ============================================ -->
<div class="modal" id="deleteModal">
    <div class="modal-overlay" onclick="closeModal('deleteModal')"></div>
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h3 style="color: #FF6B6B;"><i class="fas fa-exclamation-triangle"></i> Silme Onayı</h3>
            <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p style="text-align:center;font-size:16px;margin-bottom:20px;">
                Bu kampanyayı silmek istediğinize emin misiniz?
            </p>
            <input type="hidden" id="delete_kampanya_id">
            <div class="form-actions" style="justify-content:center;">
                <button class="btn btn-outline" onclick="closeModal('deleteModal')">İptal</button>
                <button class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i> Evet, Sil
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- JAVASCRIPT -->
<!-- ============================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // KOD OTOMATİK BÜYÜK HARF
    // ============================================
    document.querySelectorAll('input[name="kod"]').forEach(function(input) {
        input.addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9_]/g, '');
        });
    });
    
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
// KAMPANYA DÜZENLE
// ============================================
function editKampanya(id) {
    const submitBtn = document.getElementById('editSubmitBtn');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Yükleniyor...';
    submitBtn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>admin/kampanyalar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax_detay=1&kampanya_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const k = data.data;
            document.getElementById('edit_id').value = k.id;
            document.getElementById('edit_ad').value = k.ad;
            document.getElementById('edit_kod').value = k.kod;
            document.getElementById('edit_indirim_tipi').value = k.indirim_tipi;
            document.getElementById('edit_indirim_miktari').value = k.indirim_miktari;
            document.getElementById('edit_min_sepet_tutari').value = k.min_sepet_tutari || '';
            document.getElementById('edit_baslangic_tarihi').value = k.baslangic_tarihi.replace(' ', 'T');
            document.getElementById('edit_bitis_tarihi').value = k.bitis_tarihi.replace(' ', 'T');
            document.getElementById('edit_aktif').checked = k.aktif == 1;
            
            openModal('editModal');
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        alert('Bir hata oluştu: ' + error);
    })
    .finally(() => {
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Güncelle';
        submitBtn.disabled = false;
    });
}

// ============================================
// KAMPANYA SİL
// ============================================
function deleteKampanya(id) {
    document.getElementById('delete_kampanya_id').value = id;
    openModal('deleteModal');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    const id = document.getElementById('delete_kampanya_id').value;
    const btn = this;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Siliniyor...';
    btn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>admin/kampanyalar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax_sil=1&kampanya_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeModal('deleteModal');
            const row = document.getElementById('kampanya-row-' + id);
            if (row) {
                row.style.transition = 'all 0.3s';
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    row.remove();
                    location.reload();
                }, 300);
            }
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        alert('Bir hata oluştu: ' + error);
    })
    .finally(() => {
        btn.innerHTML = '<i class="fas fa-trash"></i> Evet, Sil';
        btn.disabled = false;
    });
});
</script>



<?php
include 'footer.php';
?>