<?php
// =====================================================
// SİPARİŞ YÖNETİMİ - admin/siparisler.php
// =====================================================

// =====================================================
// AJAX İSTEKLERİ - HEADER'DAN ÖNCE
// =====================================================

// SİPARİŞ DURUMU GÜNCELLE (AJAX)
if (isset($_POST['ajax_durum']) && isset($_POST['siparis_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $siparis_id = (int)$_POST['siparis_id'];
    $yeni_durum = $_POST['yeni_durum'];
    $response = ['success' => false, 'message' => ''];
    
    try {
        $stmt = $db->prepare("UPDATE siparisler SET siparis_durumu = ? WHERE id = ?");
        $stmt->execute([$yeni_durum, $siparis_id]);
        
        $response['success'] = true;
        $response['message'] = 'Sipariş durumu güncellendi!';
        $response['yeni_durum'] = $yeni_durum;
    } catch (PDOException $e) {
        $response['message'] = 'Hata: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// SİPARİŞ DETAY (AJAX)
if (isset($_POST['ajax_detay']) && isset($_POST['siparis_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $siparis_id = (int)$_POST['siparis_id'];
    $response = ['success' => false, 'data' => null, 'urunler' => null];
    
    try {
        // Sipariş başlık bilgileri
        $stmt = $db->prepare("
            SELECT s.*, u.ad, u.soyad, u.email, u.telefon,
                   fa.ad as fatura_ad, fa.soyad as fatura_soyad, fa.telefon as fatura_telefon,
                   fa.il as fatura_il, fa.ilce as fatura_ilce, fa.acik_adres as fatura_adres,
                   ta.ad as teslimat_ad, ta.soyad as teslimat_soyad, ta.telefon as teslimat_telefon,
                   ta.il as teslimat_il, ta.ilce as teslimat_ilce, ta.acik_adres as teslimat_adres
            FROM siparisler s
            LEFT JOIN kullanicilar u ON s.kullanici_id = u.id
            LEFT JOIN kullanici_adresleri fa ON s.fatura_adres_id = fa.id
            LEFT JOIN kullanici_adresleri ta ON s.teslimat_adres_id = ta.id
            WHERE s.id = ?
        ");
        $stmt->execute([$siparis_id]);
        $siparis = $stmt->fetch();
        
        if ($siparis) {
            // Sipariş ürünleri
            $stmt2 = $db->prepare("
                SELECT sd.*, u.ad as urun_adi, u.urun_kodu
                FROM siparis_detay sd
                LEFT JOIN urunler u ON sd.urun_id = u.id
                WHERE sd.siparis_id = ?
            ");
            $stmt2->execute([$siparis_id]);
            $urunler = $stmt2->fetchAll();
            
            $response['success'] = true;
            $response['data'] = $siparis;
            $response['urunler'] = $urunler;
        } else {
            $response['message'] = 'Sipariş bulunamadı!';
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
    
    // TOPLU DURUM GÜNCELLEME
    if (isset($_POST['toplu_islem']) && isset($_POST['siparis_ids'])) {
        $siparis_ids = array_map('intval', $_POST['siparis_ids']);
        $islem = $_POST['toplu_islem'];
        $ids_str = implode(',', $siparis_ids);
        
        try {
            $db->query("UPDATE siparisler SET siparis_durumu = '$islem' WHERE id IN ($ids_str)");
            $_SESSION['success'] = count($siparis_ids) . ' sipariş güncellendi!';
            header("Location: " . SITE_URL . "admin/siparisler.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Hata: ' . $e->getMessage();
            header("Location: " . SITE_URL . "admin/siparisler.php");
            exit();
        }
    }
}

// =====================================================
// NORMAL SAYFA
// =====================================================
$page_title = 'Sipariş Yönetimi';
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
$odeme = isset($_GET['odeme']) ? clean($_GET['odeme']) : '';
$tarih_baslangic = isset($_GET['tarih_baslangic']) ? clean($_GET['tarih_baslangic']) : '';
$tarih_bitis = isset($_GET['tarih_bitis']) ? clean($_GET['tarih_bitis']) : '';
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$sayfa = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
$limit = 20;
$offset = ($sayfa - 1) * $limit;

// =====================================================
// SİPARİŞ LİSTESİNİ ÇEK
// =====================================================
$where = "1=1";
$params = [];

if ($durum) {
    $where .= " AND s.siparis_durumu = ?";
    $params[] = $durum;
}

if ($odeme) {
    $where .= " AND s.odeme_durumu = ?";
    $params[] = $odeme;
}

if ($tarih_baslangic) {
    $where .= " AND DATE(s.olusturma_tarihi) >= ?";
    $params[] = $tarih_baslangic;
}

if ($tarih_bitis) {
    $where .= " AND DATE(s.olusturma_tarihi) <= ?";
    $params[] = $tarih_bitis;
}

if ($search) {
    $where .= " AND (s.siparis_no LIKE ? OR u.ad LIKE ? OR u.soyad LIKE ? OR u.email LIKE ?)";
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
    $error = 'Veritabanı hatası!';
}

// Sipariş durumları (isteğe bağlı)
$durumlar = [
    'hazirlaniyor' => 'Hazırlanıyor',
    'kargoya_verildi' => 'Kargoya Verildi',
    'kargoda' => 'Kargoda',
    'teslim_edildi' => 'Teslim Edildi',
    'iptal' => 'İptal',
    'iade' => 'İade'
];
?>

<!-- ============================================ -->
<!-- SAYFA BAŞLIĞI -->
<!-- ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-shopping-cart"></i> Sipariş Yönetimi</h2>
        <span class="page-count"><?php echo $toplam_kayit; ?> sipariş</span>
    </div>
</div>

<!-- ============================================ -->
<!-- FİLTRELEME -->
<!-- ============================================ -->
<div class="filter-section">
    <form method="GET" class="filter-form">
        <div class="filter-row">
            <div class="filter-item">
                <input type="text" name="search" placeholder="Sipariş No, Müşteri adı, email..." value="<?php echo $search; ?>">
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
                <select name="odeme">
                    <option value="">Tüm Ödemeler</option>
                    <option value="beklemede" <?php echo $odeme == 'beklemede' ? 'selected' : ''; ?>>Beklemede</option>
                    <option value="onaylandi" <?php echo $odeme == 'onaylandi' ? 'selected' : ''; ?>>Onaylandı</option>
                    <option value="iptal" <?php echo $odeme == 'iptal' ? 'selected' : ''; ?>>İptal</option>
                </select>
            </div>
            <div class="filter-item">
                <input type="date" name="tarih_baslangic" placeholder="Başlangıç" value="<?php echo $tarih_baslangic; ?>">
            </div>
            <div class="filter-item">
                <input type="date" name="tarih_bitis" placeholder="Bitiş" value="<?php echo $tarih_bitis; ?>">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrele</button>
                <a href="<?php echo SITE_URL; ?>admin/siparisler.php" class="btn btn-outline"><i class="fas fa-undo"></i> Temizle</a>
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
<!-- SİPARİŞ LİSTESİ -->
<!-- ============================================ -->
<div class="table-card">
    <div class="table-header">
        <h3>Sipariş Listesi</h3>
        <div class="table-actions">
            <form method="POST" class="bulk-form">
                <select name="toplu_islem" class="bulk-select">
                    <option value="">Toplu İşlem</option>
                    <option value="hazirlaniyor">Hazırlanıyor</option>
                    <option value="kargoya_verildi">Kargoya Verildi</option>
                    <option value="kargoda">Kargoda</option>
                    <option value="teslim_edildi">Teslim Edildi</option>
                    <option value="iptal">İptal</option>
                </select>
                <button type="submit" class="btn btn-sm btn-primary" onclick="return confirmBulk()">
                    <i class="fas fa-check"></i> Uygula
                </button>
            </form>
        </div>
    </div>
    
    <?php if (!empty($siparisler)): ?>
    <div class="table-responsive">
        <form method="POST">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" id="selectAll"></th>
                        <th>Sipariş No</th>
                        <th>Müşteri</th>
                        <th>Tutar</th>
                        <th>Ürün</th>
                        <th>Durum</th>
                        <th>Ödeme</th>
                        <th>Tarih</th>
                        <th width="140">İşlemler</th>
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
                        <td>
                            <strong><?php echo number_format($siparis['toplam_tutar'], 2, ',', '.'); ?> ₺</strong>
                        </td>
                        <td><?php echo $siparis['urun_sayisi']; ?> ürün</td>
                        <td>
                            <span class="status-badge <?php echo $siparis['siparis_durumu']; ?>" id="durum-badge-<?php echo $siparis['id']; ?>">
                                <?php echo $durumlar[$siparis['siparis_durumu']] ?? $siparis['siparis_durumu']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="payment-badge <?php echo $siparis['odeme_durumu']; ?>">
                                <?php 
                                $odeme_durumlari = [
                                    'beklemede' => 'Bekliyor',
                                    'onaylandi' => 'Onaylandı',
                                    'iptal' => 'İptal'
                                ];
                                echo $odeme_durumlari[$siparis['odeme_durumu']] ?? $siparis['odeme_durumu'];
                                ?>
                            </span>
                        </td>
                        <td>
                            <?php echo date('d.m.Y H:i', strtotime($siparis['olusturma_tarihi'])); ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary" onclick="viewSiparis(<?php echo $siparis['id']; ?>)" title="Detay">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-warning" onclick="changeStatus(<?php echo $siparis['id']; ?>, '<?php echo $siparis['siparis_durumu']; ?>')" title="Durum Değiştir">
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
            Toplam <?php echo $toplam_kayit; ?> siparişten <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $toplam_kayit); ?> arası
        </div>
        <div class="pagination">
            <?php for ($i = 1; $i <= $toplam_sayfa; $i++): ?>
                <?php if ($i == $sayfa): ?>
                    <span class="page-link active"><?php echo $i; ?></span>
                <?php elseif ($i <= 3 || $i > $toplam_sayfa - 3 || abs($i - $sayfa) <= 1): ?>
                    <a href="?sayfa=<?php echo $i; ?>&durum=<?php echo $durum; ?>&odeme=<?php echo $odeme; ?>&tarih_baslangic=<?php echo $tarih_baslangic; ?>&tarih_bitis=<?php echo $tarih_bitis; ?>&search=<?php echo $search; ?>" class="page-link">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <h3>Henüz sipariş yok</h3>
        <p>İlk siparişinizi bekleyin.</p>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================ -->
<!-- SİPARİŞ DETAY MODAL -->
<!-- ============================================ -->
<div class="modal" id="detailModal">
    <div class="modal-overlay" onclick="closeModal('detailModal')"></div>
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3><i class="fas fa-file-invoice"></i> Sipariş Detayı</h3>
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
            <h3><i class="fas fa-sync"></i> Durum Değiştir</h3>
            <button class="modal-close" onclick="closeModal('statusModal')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="status_siparis_id">
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
    var checkboxes = document.querySelectorAll('.siparis-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Lütfen en az bir sipariş seçin!');
        return false;
    }
    var select = document.querySelector('.bulk-select');
    if (select.value === '') {
        alert('Lütfen bir işlem seçin!');
        return false;
    }
    return confirm(checkboxes.length + ' siparişin durumunu değiştirmek istediğine emin misin?');
}

// ============================================
// TÜMÜNÜ SEÇ
// ============================================
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.siparis-checkbox').forEach(function(cb) {
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
// SİPARİŞ DETAY GÖSTER
// ============================================
function viewSiparis(id) {
    const content = document.getElementById('detailContent');
    content.innerHTML = `
        <div style="text-align:center; padding:40px;">
            <i class="fas fa-spinner fa-spin" style="font-size:32px;color:var(--gray);"></i>
            <p>Yükleniyor...</p>
        </div>
    `;
    openModal('detailModal');
    
    fetch('<?php echo SITE_URL; ?>admin/siparisler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax_detay=1&siparis_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const s = data.data;
            const urunler = data.urunler;
            
            let html = `
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
                    <div>
                        <h4 style="font-weight:700;margin-bottom:8px;">Sipariş Bilgileri</h4>
                        <p><strong>Sipariş No:</strong> #${s.siparis_no}</p>
                        <p><strong>Tarih:</strong> ${new Date(s.olusturma_tarihi).toLocaleString('tr-TR')}</p>
                        <p><strong>Durum:</strong> <span class="status-badge ${s.siparis_durumu}">${s.siparis_durumu}</span></p>
                        <p><strong>Ödeme:</strong> <span class="payment-badge ${s.odeme_durumu}">${s.odeme_durumu}</span></p>
                        <p><strong>Toplam Tutar:</strong> <strong style="color:var(--primary);">${Number(s.toplam_tutar).toLocaleString('tr-TR')} ₺</strong></p>
                    </div>
                    <div>
                        <h4 style="font-weight:700;margin-bottom:8px;">Müşteri Bilgileri</h4>
                        <p><strong>Ad Soyad:</strong> ${s.ad} ${s.soyad}</p>
                        <p><strong>Email:</strong> ${s.email}</p>
                        <p><strong>Telefon:</strong> ${s.telefon || '-'}</p>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
                    <div>
                        <h4 style="font-weight:700;margin-bottom:8px;">Fatura Adresi</h4>
                        <p>${s.fatura_ad} ${s.fatura_soyad}</p>
                        <p>${s.fatura_telefon || '-'}</p>
                        <p>${s.fatura_il} ${s.fatura_ilce}</p>
                        <p>${s.fatura_adres}</p>
                    </div>
                    <div>
                        <h4 style="font-weight:700;margin-bottom:8px;">Teslimat Adresi</h4>
                        <p>${s.teslimat_ad} ${s.teslimat_soyad}</p>
                        <p>${s.teslimat_telefon || '-'}</p>
                        <p>${s.teslimat_il} ${s.teslimat_ilce}</p>
                        <p>${s.teslimat_adres}</p>
                    </div>
                </div>
                <div>
                    <h4 style="font-weight:700;margin-bottom:12px;">Ürünler</h4>
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:var(--light-gray);">
                                <th style="padding:8px 12px;text-align:left;">Ürün</th>
                                <th style="padding:8px 12px;text-align:center;">Adet</th>
                                <th style="padding:8px 12px;text-align:right;">Birim Fiyat</th>
                                <th style="padding:8px 12px;text-align:right;">Toplam</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            urunler.forEach(function(u) {
                html += `
                    <tr style="border-bottom:1px solid var(--light-gray);">
                        <td style="padding:8px 12px;">${u.urun_adi}</td>
                        <td style="padding:8px 12px;text-align:center;">${u.adet}</td>
                        <td style="padding:8px 12px;text-align:right;">${Number(u.birim_fiyat).toLocaleString('tr-TR')} ₺</td>
                        <td style="padding:8px 12px;text-align:right;">${Number(u.toplam_fiyat).toLocaleString('tr-TR')} ₺</td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
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
    document.getElementById('status_siparis_id').value = id;
    document.getElementById('status_select').value = current;
    openModal('statusModal');
}

document.getElementById('confirmStatusBtn').addEventListener('click', function() {
    const id = document.getElementById('status_siparis_id').value;
    const yeni_durum = document.getElementById('status_select').value;
    const btn = this;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Değiştiriliyor...';
    btn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>admin/siparisler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax_durum=1&siparis_id=' + id + '&yeni_durum=' + yeni_durum
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.getElementById('durum-badge-' + id);
            const durumlar = {
                'hazirlaniyor': 'Hazırlanıyor',
                'kargoya_verildi': 'Kargoya Verildi',
                'kargoda': 'Kargoda',
                'teslim_edildi': 'Teslim Edildi',
                'iptal': 'İptal',
                'iade': 'İade'
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