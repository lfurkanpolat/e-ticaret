<?php
// =====================================================
// YORUM YÖNETİMİ - admin/yorumlar.php
// =====================================================

// =====================================================
// AJAX İSTEKLERİ - HEADER'DAN ÖNCE
// =====================================================

// YORUM DURUMU GÜNCELLE (AJAX)
if (isset($_POST['ajax_yorum_durum']) && isset($_POST['yorum_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $yorum_id = (int)$_POST['yorum_id'];
    $yeni_durum = $_POST['yeni_durum'];
    $response = ['success' => false, 'message' => ''];
    
    try {
        $stmt = $db->prepare("UPDATE urun_yorumlari SET durum = ? WHERE id = ?");
        $stmt->execute([$yeni_durum, $yorum_id]);
        
        // Yorum onaylandıysa ürün puanını güncelle
        if ($yeni_durum == 'onaylandi') {
            // Yorumun ürün ID'sini al
            $stmt2 = $db->prepare("SELECT urun_id, puan FROM urun_yorumlari WHERE id = ?");
            $stmt2->execute([$yorum_id]);
            $yorum = $stmt2->fetch();
            
            if ($yorum) {
                // Ürünün ortalama puanını güncelle
                $stmt3 = $db->prepare("
                    UPDATE urunler SET 
                        puan_ortalamasi = (
                            SELECT AVG(puan) FROM urun_yorumlari 
                            WHERE urun_id = ? AND durum = 'onaylandi'
                        ),
                        puan_sayisi = (
                            SELECT COUNT(*) FROM urun_yorumlari 
                            WHERE urun_id = ? AND durum = 'onaylandi'
                        )
                    WHERE id = ?
                ");
                $stmt3->execute([$yorum['urun_id'], $yorum['urun_id'], $yorum['urun_id']]);
            }
        }
        
        $response['success'] = true;
        $response['message'] = 'Yorum durumu güncellendi!';
        $response['yeni_durum'] = $yeni_durum;
    } catch (PDOException $e) {
        $response['message'] = 'Hata: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// YORUM DETAY (AJAX)
if (isset($_POST['ajax_yorum_detay']) && isset($_POST['yorum_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $yorum_id = (int)$_POST['yorum_id'];
    $response = ['success' => false, 'data' => null];
    
    try {
        $stmt = $db->prepare("
            SELECT y.*, 
                   u.ad as urun_adi, u.urun_kodu,
                   k.ad as kullanici_ad, k.soyad as kullanici_soyad, k.email
            FROM urun_yorumlari y
            LEFT JOIN urunler u ON y.urun_id = u.id
            LEFT JOIN kullanicilar k ON y.kullanici_id = k.id
            WHERE y.id = ?
        ");
        $stmt->execute([$yorum_id]);
        $yorum = $stmt->fetch();
        
        if ($yorum) {
            $response['success'] = true;
            $response['data'] = $yorum;
        } else {
            $response['message'] = 'Yorum bulunamadı!';
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
    
    // TOPLU YORUM DURUM GÜNCELLEME
    if (isset($_POST['toplu_islem']) && isset($_POST['yorum_ids'])) {
        $yorum_ids = array_map('intval', $_POST['yorum_ids']);
        $islem = $_POST['toplu_islem'];
        $ids_str = implode(',', $yorum_ids);
        
        try {
            $db->query("UPDATE urun_yorumlari SET durum = '$islem' WHERE id IN ($ids_str)");
            
            // Eğer onaylandı ise ürün puanlarını güncelle
            if ($islem == 'onaylandi') {
                // Onaylanan yorumların ürün ID'lerini al
                $stmt = $db->query("SELECT DISTINCT urun_id FROM urun_yorumlari WHERE id IN ($ids_str)");
                while ($row = $stmt->fetch()) {
                    $urun_id = $row['urun_id'];
                    $db->query("
                        UPDATE urunler SET 
                            puan_ortalamasi = (
                                SELECT AVG(puan) FROM urun_yorumlari 
                                WHERE urun_id = $urun_id AND durum = 'onaylandi'
                            ),
                            puan_sayisi = (
                                SELECT COUNT(*) FROM urun_yorumlari 
                                WHERE urun_id = $urun_id AND durum = 'onaylandi'
                            )
                        WHERE id = $urun_id
                    ");
                }
            }
            
            $_SESSION['success'] = count($yorum_ids) . ' yorum güncellendi!';
            header("Location: " . SITE_URL . "admin/yorumlar.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Hata: ' . $e->getMessage();
            header("Location: " . SITE_URL . "admin/yorumlar.php");
            exit();
        }
    }
}

// =====================================================
// NORMAL SAYFA
// =====================================================
$page_title = 'Yorum Yönetimi';
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
$puan = isset($_GET['puan']) ? (int)$_GET['puan'] : 0;
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$sayfa = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
$limit = 20;
$offset = ($sayfa - 1) * $limit;

// =====================================================
// YORUM LİSTESİNİ ÇEK
// =====================================================
$where = "1=1";
$params = [];

if ($durum) {
    $where .= " AND y.durum = ?";
    $params[] = $durum;
}

if ($puan > 0) {
    $where .= " AND y.puan = ?";
    $params[] = $puan;
}

if ($search) {
    $where .= " AND (y.yorum LIKE ? OR y.baslik LIKE ? OR u.ad LIKE ? OR k.ad LIKE ? OR k.soyad LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Toplam yorum sayısı
try {
    $countSql = "
        SELECT COUNT(*) as toplam 
        FROM urun_yorumlari y
        LEFT JOIN urunler u ON y.urun_id = u.id
        LEFT JOIN kullanicilar k ON y.kullanici_id = k.id
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

// Yorumları çek
try {
    $sql = "
        SELECT y.*, 
               u.ad as urun_adi, u.urun_kodu,
               k.ad as kullanici_ad, k.soyad as kullanici_soyad, k.email
        FROM urun_yorumlari y
        LEFT JOIN urunler u ON y.urun_id = u.id
        LEFT JOIN kullanicilar k ON y.kullanici_id = k.id
        WHERE $where
        ORDER BY y.olusturma_tarihi DESC
        LIMIT ? OFFSET ?
    ";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $yorumlar = $stmt->fetchAll();
} catch (PDOException $e) {
    $yorumlar = [];
    $error = 'Veritabanı hatası: ' . $e->getMessage();
}

// Yorum durumları
$durumlar = [
    'beklemede' => 'Beklemede',
    'onaylandi' => 'Onaylandı',
    'reddedildi' => 'Reddedildi'
];

// Puan yıldızları
function yildizlar($puan) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $puan) {
            $html .= '<i class="fas fa-star" style="color: #F39C12;"></i>';
        } else {
            $html .= '<i class="fas fa-star" style="color: #ddd;"></i>';
        }
    }
    return $html;
}
?>

<!-- ============================================ -->
<!-- SAYFA BAŞLIĞI -->
<!-- ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-star"></i> Yorum Yönetimi</h2>
        <span class="page-count"><?php echo $toplam_kayit; ?> yorum</span>
    </div>
</div>

<!-- ============================================ -->
<!-- FİLTRELEME -->
<!-- ============================================ -->
<div class="filter-section">
    <form method="GET" class="filter-form">
        <div class="filter-row">
            <div class="filter-item">
                <input type="text" name="search" placeholder="Yorum, ürün, kullanıcı ara..." value="<?php echo $search; ?>">
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
                <select name="puan">
                    <option value="0">Tüm Puanlar</option>
                    <option value="5" <?php echo $puan == 5 ? 'selected' : ''; ?>>5 Yıldız</option>
                    <option value="4" <?php echo $puan == 4 ? 'selected' : ''; ?>>4 Yıldız</option>
                    <option value="3" <?php echo $puan == 3 ? 'selected' : ''; ?>>3 Yıldız</option>
                    <option value="2" <?php echo $puan == 2 ? 'selected' : ''; ?>>2 Yıldız</option>
                    <option value="1" <?php echo $puan == 1 ? 'selected' : ''; ?>>1 Yıldız</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrele</button>
                <a href="<?php echo SITE_URL; ?>admin/yorumlar.php" class="btn btn-outline"><i class="fas fa-undo"></i> Temizle</a>
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
<!-- YORUM LİSTESİ -->
<!-- ============================================ -->
<div class="table-card">
    <div class="table-header">
        <h3>Yorum Listesi</h3>
        <div class="table-actions">
            <form method="POST" class="bulk-form">
                <select name="toplu_islem" class="bulk-select">
                    <option value="">Toplu İşlem</option>
                    <option value="onaylandi">Onayla</option>
                    <option value="reddedildi">Reddet</option>
                </select>
                <button type="submit" name="toplu_islem" class="btn btn-sm btn-primary" onclick="return confirmBulk()">
                    <i class="fas fa-check"></i> Uygula
                </button>
            </form>
        </div>
    </div>
    
    <?php if (!empty($yorumlar)): ?>
    <div class="table-responsive">
        <form method="POST" id="yorumForm">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" id="selectAll"></th>
                        <th>#</th>
                        <th>Ürün</th>
                        <th>Kullanıcı</th>
                        <th>Puan</th>
                        <th>Yorum</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                        <th width="140">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($yorumlar as $yorum): ?>
                    <tr id="yorum-row-<?php echo $yorum['id']; ?>">
                        <td><input type="checkbox" name="yorum_ids[]" value="<?php echo $yorum['id']; ?>" class="yorum-checkbox"></td>
                        <td><?php echo $yorum['id']; ?></td>
                        <td>
                            <?php echo $yorum['urun_adi']; ?>
                            <br><small style="color: var(--gray);"><?php echo $yorum['urun_kodu']; ?></small>
                        </td>
                        <td>
                            <?php echo $yorum['kullanici_ad'] . ' ' . $yorum['kullanici_soyad']; ?>
                            <br><small style="color: var(--gray);"><?php echo $yorum['email']; ?></small>
                        </td>
                        <td>
                            <?php echo yildizlar($yorum['puan']); ?>
                            <br><small style="color: var(--gray);">(<?php echo $yorum['puan']; ?>/5)</small>
                        </td>
                        <td>
                            <?php if ($yorum['baslik']): ?>
                                <strong><?php echo $yorum['baslik']; ?></strong><br>
                            <?php endif; ?>
                            <?php echo mb_substr($yorum['yorum'], 0, 60) . (strlen($yorum['yorum']) > 60 ? '...' : ''); ?>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $yorum['durum']; ?>" id="durum-badge-<?php echo $yorum['id']; ?>">
                                <?php echo $durumlar[$yorum['durum']] ?? $yorum['durum']; ?>
                            </span>
                        </td>
                        <td>
                            <?php echo date('d.m.Y H:i', strtotime($yorum['olusturma_tarihi'])); ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary" onclick="viewYorum(<?php echo $yorum['id']; ?>)" title="Detay">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($yorum['durum'] == 'beklemede'): ?>
                                    <button class="btn btn-sm btn-success" onclick="changeStatus(<?php echo $yorum['id']; ?>, 'onaylandi')" title="Onayla">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="changeStatus(<?php echo $yorum['id']; ?>, 'reddedildi')" title="Reddet">
                                        <i class="fas fa-times"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-warning" onclick="changeStatus(<?php echo $yorum['id']; ?>, '<?php echo $yorum['durum']; ?>')" title="Durum Değiştir">
                                        <i class="fas fa-sync"></i>
                                    </button>
                                <?php endif; ?>
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
            Toplam <?php echo $toplam_kayit; ?> yorumdan <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $toplam_kayit); ?> arası
        </div>
        <div class="pagination">
            <?php for ($i = 1; $i <= $toplam_sayfa; $i++): ?>
                <?php if ($i == $sayfa): ?>
                    <span class="page-link active"><?php echo $i; ?></span>
                <?php elseif ($i <= 3 || $i > $toplam_sayfa - 3 || abs($i - $sayfa) <= 1): ?>
                    <a href="?sayfa=<?php echo $i; ?>&durum=<?php echo $durum; ?>&puan=<?php echo $puan; ?>&search=<?php echo $search; ?>" class="page-link">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-star"></i>
        <h3>Henüz yorum yok</h3>
        <p>İlk yorumu bekleyin.</p>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================ -->
<!-- YORUM DETAY MODAL -->
<!-- ============================================ -->
<div class="modal" id="detailModal">
    <div class="modal-overlay" onclick="closeModal('detailModal')"></div>
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3><i class="fas fa-file-alt"></i> Yorum Detayı</h3>
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
            <h3><i class="fas fa-sync"></i> Yorum Durumu Değiştir</h3>
            <button class="modal-close" onclick="closeModal('statusModal')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="status_yorum_id">
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
    var checkboxes = document.querySelectorAll('.yorum-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Lütfen en az bir yorum seçin!');
        return false;
    }
    var select = document.querySelector('.bulk-select');
    if (select.value === '') {
        alert('Lütfen bir işlem seçin!');
        return false;
    }
    return confirm(checkboxes.length + ' yorum için durum değiştirilecek. Devam et?');
}

// ============================================
// TÜMÜNÜ SEÇ
// ============================================
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.yorum-checkbox').forEach(function(cb) {
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
// YORUM DETAY GÖSTER
// ============================================
function viewYorum(id) {
    const content = document.getElementById('detailContent');
    content.innerHTML = `
        <div style="text-align:center; padding:40px;">
            <i class="fas fa-spinner fa-spin" style="font-size:32px;color:var(--gray);"></i>
            <p>Yükleniyor...</p>
        </div>
    `;
    openModal('detailModal');
    
    fetch('<?php echo SITE_URL; ?>admin/yorumlar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax_yorum_detay=1&yorum_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const y = data.data;
            
            let yildizHtml = '';
            for (let i = 1; i <= 5; i++) {
                if (i <= y.puan) {
                    yildizHtml += '<i class="fas fa-star" style="color: #F39C12;"></i>';
                } else {
                    yildizHtml += '<i class="fas fa-star" style="color: #ddd;"></i>';
                }
            }
            
            let html = `
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
                    <div>
                        <h4 style="font-weight:700;margin-bottom:8px;">Yorum Bilgileri</h4>
                        <p><strong>Yorum ID:</strong> #${y.id}</p>
                        <p><strong>Ürün:</strong> ${y.urun_adi} (${y.urun_kodu})</p>
                        <p><strong>Puan:</strong> ${yildizHtml}</p>
                        <p><strong>Durum:</strong> <span class="status-badge ${y.durum}">${y.durum}</span></p>
                        <p><strong>Tarih:</strong> ${new Date(y.olusturma_tarihi).toLocaleString('tr-TR')}</p>
                    </div>
                    <div>
                        <h4 style="font-weight:700;margin-bottom:8px;">Kullanıcı Bilgileri</h4>
                        <p><strong>Ad Soyad:</strong> ${y.kullanici_ad} ${y.kullanici_soyad}</p>
                        <p><strong>Email:</strong> ${y.email}</p>
                    </div>
                </div>
                <div>
                    <h4 style="font-weight:700;margin-bottom:8px;">Yorum Başlığı</h4>
                    <p style="font-size:16px;font-weight:600;">${y.baslik || 'Başlık yok'}</p>
                </div>
                <div>
                    <h4 style="font-weight:700;margin-bottom:8px;">Yorum İçeriği</h4>
                    <p style="background:var(--light-gray);padding:16px;border-radius:8px;min-height:80px;line-height:1.8;">
                        ${y.yorum}
                    </p>
                </div>
                ${y.admin_cevap ? `
                <div style="margin-top:16px;">
                    <h4 style="font-weight:700;margin-bottom:8px;">Yönetici Cevabı</h4>
                    <p style="background:var(--primary);color:var(--white);padding:16px;border-radius:8px;line-height:1.8;">
                        ${y.admin_cevap}
                    </p>
                    <small style="color:var(--gray);">Cevap Tarihi: ${y.admin_cevap_tarihi ? new Date(y.admin_cevap_tarihi).toLocaleString('tr-TR') : '-'}</small>
                </div>
                ` : ''}
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
    document.getElementById('status_yorum_id').value = id;
    document.getElementById('status_select').value = current;
    openModal('statusModal');
}

document.getElementById('confirmStatusBtn').addEventListener('click', function() {
    const id = document.getElementById('status_yorum_id').value;
    const yeni_durum = document.getElementById('status_select').value;
    const btn = this;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Değiştiriliyor...';
    btn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>admin/yorumlar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax_yorum_durum=1&yorum_id=' + id + '&yeni_durum=' + yeni_durum
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.getElementById('durum-badge-' + id);
            const durumlar = {
                'beklemede': 'Beklemede',
                'onaylandi': 'Onaylandı',
                'reddedildi': 'Reddedildi'
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