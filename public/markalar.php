<?php
// =====================================================
// MARKALAR SAYFASI - public/markalar.php
// =====================================================

require_once '../includes/config.php';

$page_title = 'Markalar';

// =====================================================
// MARKA LİSTESİNİ ÇEK
// =====================================================
try {
    $stmt = $db->query("
        SELECT m.*, 
               (SELECT COUNT(*) FROM urunler WHERE marka_id = m.id AND durum = 'aktif') as urun_sayisi
        FROM markalar m
        WHERE m.durum = 'aktif'
        ORDER BY m.ad ASC
    ");
    $markalar = $stmt->fetchAll();
} catch (PDOException $e) {
    $markalar = [];
    $error = 'Veritabanı hatası: ' . $e->getMessage();
}

// =====================================================
// HEADER'I DAHİL ET
// =====================================================
include '../includes/header.php';
?>

<!-- ============================================ -->
<!-- MARKALAR SAYFASI -->
<!-- ============================================ -->
<section class="brands-page">
    <div class="container">
        
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>public/index.php">Ana Sayfa</a>
            <span class="separator">/</span>
            <span class="current">Markalar</span>
        </div>
        
        <!-- Başlık -->
        <div class="page-header">
            <h1><i class="fa-regular fa-copyright"></i> Markalar</h1>
            <p>En popüler markaları keşfedin</p>
        </div>
        
        <?php if (!empty($markalar)): ?>
        
            <div class="brands-grid">
                <?php foreach ($markalar as $marka): ?>
                    <a href="<?php echo SITE_URL; ?>public/marka.php?slug=<?php echo $marka['slug']; ?>" class="brand-card">
                        <div class="brand-logo">
                            <?php if ($marka['logo_url']): ?>
                                <img src="<?php echo SITE_URL; ?>uploads/markalar/<?php echo $marka['logo_url']; ?>" alt="<?php echo $marka['ad']; ?>">
                            <?php else: ?>
                                <div class="brand-icon">
                                    <i class="fa-solid fa-copyright"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="brand-info">
                            <h3><?php echo $marka['ad']; ?></h3>
                            <?php if ($marka['aciklama']): ?>
                                <p><?php echo mb_substr($marka['aciklama'], 0, 60) . (strlen($marka['aciklama']) > 60 ? '...' : ''); ?></p>
                            <?php endif; ?>
                            <span class="brand-product-count"><?php echo $marka['urun_sayisi']; ?> ürün</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            
        <?php else: ?>
            
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fa-regular fa-copyright"></i>
                </div>
                <h2>Henüz Marka Eklenmemiş</h2>
                <p>Markalar yakında eklenecek.</p>
            </div>
            
        <?php endif; ?>
        
    </div>
</section>

<!-- ============================================ -->
<!-- CSS -->
<!-- ============================================ -->
<style>
/* ============================================
   MARKALAR SAYFASI ÖZEL STİLLER
   ============================================ */

.brands-page {
    padding: 30px 0 60px;
    background: #f8f9fb;
    min-height: calc(100vh - 200px);
}

/* Breadcrumb */
.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #999;
    margin-bottom: 20px;
}

.breadcrumb a {
    color: #666;
    text-decoration: none;
    transition: all 0.3s;
}

.breadcrumb a:hover {
    color: #ffd400;
}

.breadcrumb .separator {
    color: #ccc;
}

.breadcrumb .current {
    color: #222;
    font-weight: 600;
}

/* Page Header */
.page-header {
    margin-bottom: 32px;
}

.page-header h1 {
    font-size: 28px;
    font-weight: 800;
    margin-bottom: 4px;
}

.page-header h1 i {
    color: #ffd400;
}

.page-header p {
    color: #999;
    font-size: 16px;
}

/* Brands Grid */
.brands-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 24px;
}

.brand-card {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    text-align: center;
    text-decoration: none;
    color: #222;
    transition: all 0.3s;
    border: 1px solid #f0f0f0;
    box-shadow: 0 2px 15px rgba(0,0,0,0.04);
}

.brand-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
    border-color: #ffd400;
}

.brand-logo {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    overflow: hidden;
    margin: 0 auto 16px;
    background: #f8f9fb;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #f0f0f0;
    transition: all 0.3s;
}

.brand-card:hover .brand-logo {
    border-color: #ffd400;
}

.brand-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 12px;
}

.brand-icon {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    color: #ccc;
}

.brand-info h3 {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 4px;
}

.brand-info p {
    font-size: 13px;
    color: #999;
    margin-bottom: 8px;
    line-height: 1.5;
    min-height: 36px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.brand-product-count {
    display: inline-block;
    font-size: 12px;
    color: #fff;
    background: #ffd400;
    color: #111;
    padding: 3px 14px;
    border-radius: 50px;
    font-weight: 600;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.05);
}

.empty-state-icon {
    font-size: 72px;
    color: #ddd;
    margin-bottom: 16px;
}

.empty-state h2 {
    font-size: 28px;
    font-weight: 800;
    margin-bottom: 8px;
}

.empty-state p {
    color: #999;
    font-size: 16px;
}

/* Responsive */
@media (max-width: 768px) {
    .brands-page {
        padding: 20px 0 40px;
    }
    
    .page-header h1 {
        font-size: 24px;
    }
    
    .brands-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 16px;
    }
    
    .brand-card {
        padding: 16px;
    }
    
    .brand-logo {
        width: 72px;
        height: 72px;
    }
    
    .brand-info h3 {
        font-size: 15px;
    }
}

@media (max-width: 480px) {
    .brands-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    
    .brand-card {
        padding: 12px;
    }
    
    .brand-logo {
        width: 60px;
        height: 60px;
    }
    
    .brand-icon {
        font-size: 28px;
    }
    
    .brand-info h3 {
        font-size: 13px;
    }
    
    .brand-info p {
        display: none;
    }
}
</style>

<?php
// =====================================================
// FOOTER'I DAHİL ET
// =====================================================
include '../includes/footer.php';
?>