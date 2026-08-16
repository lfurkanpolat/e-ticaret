<?php
// =====================================================
// KATEGORİ YÖNETİMİ - admin/kategoriler.php
// =====================================================

// =====================================================
// AJAX İSTEKLERİ - HEADER'DAN ÖNCE
// =====================================================

// KATEGORİ SİL (AJAX)
if (isset($_POST['ajax_sil']) && isset($_POST['kategori_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $kategori_id = (int)$_POST['kategori_id'];
    $response = ['success' => false, 'message' => ''];
    
    try {
        // Alt kategorileri kontrol et
        $stmt = $db->prepare("SELECT COUNT(*) as toplam FROM kategoriler WHERE ust_id = ?");
        $stmt->execute([$kategori_id]);
        $alt_kategori = $stmt->fetch()['toplam'] ?? 0;
        
        if ($alt_kategori > 0) {
            $response['message'] = 'Bu kategorinin alt kategorileri var! Önce onları silin.';
            echo json_encode($response);
            exit();
        }
        
        // Bu kategoriye ait ürün var mı?
        $stmt = $db->prepare("SELECT COUNT(*) as toplam FROM urunler WHERE kategori_id = ?");
        $stmt->execute([$kategori_id]);
        $urun_sayisi = $stmt->fetch()['toplam'] ?? 0;
        
        if ($urun_sayisi > 0) {
            $response['message'] = 'Bu kategoride ' . $urun_sayisi . ' ürün var! Önce ürünleri taşıyın veya silin.';
            echo json_encode($response);
            exit();
        }
        
        $stmt = $db->prepare("DELETE FROM kategoriler WHERE id = ?");
        $stmt->execute([$kategori_id]);
        
        $response['success'] = true;
        $response['message'] = 'Kategori silindi!';
    } catch (PDOException $e) {
        $response['message'] = 'Hata: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// KATEGORİ DETAY (AJAX - Düzenleme için)
if (isset($_POST['ajax_detay']) && isset($_POST['kategori_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $kategori_id = (int)$_POST['kategori_id'];
    $response = ['success' => false, 'data' => null];
    
    try {
        $stmt = $db->prepare("SELECT * FROM kategoriler WHERE id = ?");
        $stmt->execute([$kategori_id]);
        $kategori = $stmt->fetch();
        
        if ($kategori) {
            $response['success'] = true;
            $response['data'] = $kategori;
        } else {
            $response['message'] = 'Kategori bulunamadı!';
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
    
    // KATEGORİ EKLE
    if (isset($_POST['kategori_ekle'])) {
        $ad = clean($_POST['ad']);
        $slug = clean($_POST['slug']);
        
        $ust_id = null;
        if (isset($_POST['ust_id']) && $_POST['ust_id'] !== '' && $_POST['ust_id'] !== null) {
            $ust_id = (int)$_POST['ust_id'];
        }
        
        $durum = clean($_POST['durum']);
        $icon = clean($_POST['kategoriIcon']);
        
        $errors = [];
        
        if (empty($ad)) $errors[] = 'Kategori adı boş olamaz.';
        if (empty($slug)) $errors[] = 'Slug boş olamaz.';
        
        if (empty($errors)) {
            try {
                $stmt = $db->prepare("SELECT id FROM kategoriler WHERE slug = ?");
                $stmt->execute([$slug]);
                if ($stmt->fetch()) {
                    $errors[] = 'Bu slug zaten kullanılıyor!';
                }
            } catch (PDOException $e) {}
        }
        
        if (empty($errors)) {
            try {
                $sql = "INSERT INTO kategoriler (ad, slug, ust_id, durum, icon) VALUES (?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->execute([$ad, $slug, $ust_id, $durum, $icon]);
                
                $_SESSION['success'] = 'Kategori başarıyla eklendi!';
                header("Location: " . SITE_URL . "admin/kategoriler.php");
                exit();
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Veritabanı hatası: ' . $e->getMessage();
                header("Location: " . SITE_URL . "admin/kategoriler.php");
                exit();
            }
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
            header("Location: " . SITE_URL . "admin/kategoriler.php");
            exit();
        }
    }
    
    // KATEGORİ DÜZENLE
    if (isset($_POST['kategori_duzenle'])) {
        $id = (int)$_POST['id'];
        $ad = clean($_POST['ad']);
        $slug = clean($_POST['slug']);
        
        $ust_id = null;
        if (isset($_POST['ust_id']) && $_POST['ust_id'] !== '' && $_POST['ust_id'] !== null) {
            $ust_id = (int)$_POST['ust_id'];
        }
        
        $durum = clean($_POST['durum']);
        
        $errors = [];
        
        if (empty($ad)) $errors[] = 'Kategori adı boş olamaz.';
        if (empty($slug)) $errors[] = 'Slug boş olamaz.';
        
        if (empty($errors)) {
            try {
                $stmt = $db->prepare("SELECT id FROM kategoriler WHERE slug = ? AND id != ?");
                $stmt->execute([$slug, $id]);
                if ($stmt->fetch()) {
                    $errors[] = 'Bu slug zaten kullanılıyor!';
                }
            } catch (PDOException $e) {}
        }
        
        if ($ust_id == $id) {
            $errors[] = 'Kategori kendisinin alt kategorisi olamaz!';
        }
        
        if (empty($errors)) {
            try {
                $sql = "UPDATE kategoriler SET ad = ?, slug = ?, ust_id = ?, durum = ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$ad, $slug, $ust_id, $durum, $id]);
                
                $_SESSION['success'] = 'Kategori güncellendi!';
                header("Location: " . SITE_URL . "admin/kategoriler.php");
                exit();
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Veritabanı hatası: ' . $e->getMessage();
                header("Location: " . SITE_URL . "admin/kategoriler.php");
                exit();
            }
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
            header("Location: " . SITE_URL . "admin/kategoriler.php");
            exit();
        }
    }
}

// =====================================================
// NORMAL SAYFA
// =====================================================
$page_title = 'Kategori Yönetimi';
include 'header.php';

// Session mesajlarını al ve temizle
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error']);
unset($_SESSION['success']);

// =====================================================
// KATEGORİ LİSTESİNİ ÇEK
// =====================================================
try {
    $stmt = $db->query("SELECT * FROM kategoriler ORDER BY ust_id IS NULL DESC, ust_id, ad");
    $tum_kategoriler = $stmt->fetchAll();
} catch (PDOException $e) {
    $tum_kategoriler = [];
}

function buildCategoryTree($categories, $parent_id = null, $level = 0) {
    $result = [];
    foreach ($categories as $category) {
        if ($category['ust_id'] == $parent_id) {
            $category['level'] = $level;
            $result[] = $category;
            $children = buildCategoryTree($categories, $category['id'], $level + 1);
            $result = array_merge($result, $children);
        }
    }
    return $result;
}

$kategoriler = buildCategoryTree($tum_kategoriler);
?>

<!-- ============================================ -->
<!-- SAYFA BAŞLIĞI -->
<!-- ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-tags"></i> Kategori Yönetimi</h2>
        <span class="page-count"><?php echo count($tum_kategoriler); ?> kategori</span>
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
<!-- KATEGORİ EKLEME FORM -->
<!-- ============================================ -->
<div class="form-card">
    <h3 class="form-section-title">
        <i class="fas fa-plus-circle"></i> Yeni Kategori Ekle
    </h3>
    <form method="POST" action="" class="category-form">
        <div class="form-row">
            <div class="form-group">
                <label>Kategori Adı *</label>
                <input type="text" name="ad" id="kategoriAd" placeholder="Kategori adı" required>
            </div>
            <div class="form-group">
                <label>Slug *</label>
                <input type="text" name="slug" id="kategoriSlug" placeholder="kategori-adi" required>
            </div>

        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Üst Kategori</label>
                <select name="ust_id">
                    <option value="">Ana Kategori</option>
                    <?php foreach ($tum_kategoriler as $kat): ?>
                        <option value="<?php echo $kat['id']; ?>">
                            <?php echo $kat['ad']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Durum</label>
                <select name="durum">
                    <option value="aktif">Aktif</option>
                    <option value="pasif">Pasif</option>
                </select>
            </div>
            <div class="form-group">
                <label>İcon *</label>
                <input type="text" name="kategoriIcon" placeholder="İcon kullanımı : fa-solid fa-shirt" required>
            <label>İcon linki için <a href="https://fontawesome.com/icons" target="_blank">buraya</a> tıklayın</label>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" name="kategori_ekle" class="btn btn-primary">
                <i class="fas fa-save"></i> Kategori Ekle
            </button>
        </div>
    </form>
</div>

<!-- ============================================ -->
<!-- KATEGORİ LİSTESİ -->
<!-- ============================================ -->
<div class="table-card">
    <div class="table-header">
        <h3><i class="fas fa-list"></i> Kategori Listesi</h3>
    </div>
    
    <?php if (!empty($kategoriler)): ?>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="60">#</th>
                    <th>Kategori Adı</th>
                    <th>Slug</th>
                    <th>Üst Kategori</th>
                    <th>Durum</th>
                    <th>Alt Kategori</th>
                    <th width="140">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kategoriler as $kategori): ?>
                <tr id="kategori-row-<?php echo $kategori['id']; ?>">
                    <td><?php echo $kategori['id']; ?></td>
                    <td>
                        <?php echo str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $kategori['level'] ?? 0); ?>
                        <?php if (($kategori['level'] ?? 0) > 0): ?>
                            <i class="fas fa-chevron-right" style="color: var(--gray); font-size: 12px;"></i>
                        <?php endif; ?>
                        <?php echo $kategori['ad']; ?>
                    </td>
                    <td><code><?php echo $kategori['slug']; ?></code></td>
                    <td>
                        <?php 
                        if ($kategori['ust_id']) {
                            $parent = array_filter($tum_kategoriler, function($item) use ($kategori) {
                                return $item['id'] == $kategori['ust_id'];
                            });
                            $parent = array_shift($parent);
                            echo $parent ? $parent['ad'] : '-';
                        } else {
                            echo '<span style="color: var(--gray);">Ana Kategori</span>';
                        }
                        ?>
                    </td>
                    <td>
                        <span class="status-badge <?php echo $kategori['durum']; ?>">
                            <?php echo $kategori['durum'] == 'aktif' ? 'Aktif' : 'Pasif'; ?>
                        </span>
                    </td>
                    <td>
                        <?php 
                        $alt_sayisi = 0;
                        foreach ($tum_kategoriler as $kat) {
                            if ($kat['ust_id'] == $kategori['id']) $alt_sayisi++;
                        }
                        echo $alt_sayisi > 0 ? '<span class="badge">' . $alt_sayisi . '</span>' : '-';
                        ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-primary" onclick="editCategory(<?php echo $kategori['id']; ?>)" title="Düzenle">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteCategory(<?php echo $kategori['id']; ?>)" title="Sil">
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
        <i class="fas fa-folder-open"></i>
        <h3>Henüz kategori eklenmemiş</h3>
        <p>İlk kategoriyi ekleyerek başlayın.</p>
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
            <h3><i class="fas fa-edit"></i> Kategori Düzenle</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editForm" method="POST">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="kategori_duzenle" value="1">
                
                <div class="form-group">
                    <label>Kategori Adı *</label>
                    <input type="text" name="ad" id="edit_ad" required>
                </div>

                             
                
                <div class="form-group">
                    <label>Slug *</label>
                    <input type="text" name="slug" id="edit_slug" required>
                </div>
                
                <div class="form-group">
                    <label>Üst Kategori</label>
                    <select name="ust_id" id="edit_ust_id">
                        <option value="">Ana Kategori</option>
                        <?php foreach ($tum_kategoriler as $kat): ?>
                            <option value="<?php echo $kat['id']; ?>">
                                <?php echo $kat['ad']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Durum</label>
                    <select name="durum" id="edit_durum">
                        <option value="aktif">Aktif</option>
                        <option value="pasif">Pasif</option>
                    </select>
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
            <p style="text-align:center;font-size:16px;margin-bottom:8px;">
                Bu kategoriyi silmek istediğinize emin misiniz?
            </p>
            <p style="text-align:center;color:var(--gray);font-size:14px;margin-bottom:20px;">
                <i class="fas fa-info-circle"></i> Alt kategorileri ve ürünleri olan kategoriler silinemez.
            </p>
            <input type="hidden" id="delete_kategori_id">
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
    // SLUG OTOMATİK OLUŞTUR
    // ============================================
    const adInput = document.getElementById('kategoriAd');
    const slugInput = document.getElementById('kategoriSlug');
    
    if (adInput && slugInput) {
        adInput.addEventListener('keyup', function() {
            if (!slugInput.dataset.edited) {
                const slug = this.value
                    .toLowerCase()
                    .replace(/ğ/g, 'g')
                    .replace(/ü/g, 'u')
                    .replace(/ş/g, 's')
                    .replace(/ı/g, 'i')
                    .replace(/ö/g, 'o')
                    .replace(/ç/g, 'c')
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                slugInput.value = slug;
            }
        });
        
        slugInput.addEventListener('focus', function() {
            this.dataset.edited = 'true';
        });
    }
    
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
// KATEGORİ DÜZENLE
// ============================================
function editCategory(id) {
    const submitBtn = document.getElementById('editSubmitBtn');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Yükleniyor...';
    submitBtn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>admin/kategoriler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax_detay=1&kategori_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const kategori = data.data;
            document.getElementById('edit_id').value = kategori.id;
            document.getElementById('edit_ad').value = kategori.ad;
            document.getElementById('edit_slug').value = kategori.slug;
            document.getElementById('edit_ust_id').value = kategori.ust_id || '';
            document.getElementById('edit_durum').value = kategori.durum;
            
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
// KATEGORİ SİL
// ============================================
function deleteCategory(id) {
    document.getElementById('delete_kategori_id').value = id;
    openModal('deleteModal');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    const id = document.getElementById('delete_kategori_id').value;
    const btn = this;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Siliniyor...';
    btn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>admin/kategoriler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax_sil=1&kategori_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeModal('deleteModal');
            const row = document.getElementById('kategori-row-' + id);
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