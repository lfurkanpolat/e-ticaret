<?php
/**
 * iyzico / iyzipay-php Autoloader
 * Yapı: /iyzipay/ altındaki Iyzipay sınıflarını otomatik yükler.
 */

spl_autoload_register(function ($class) {
    // Iyzipay namespace ön eki
    $prefix = 'Iyzipay\\';

    // Sınıf bu namespace ile başlamıyorsa devam etme
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Namespace'den sonraki bağıl sınıf adını al
    $relative_class = substr($class, $len);

    // Iyzipay/ altındaki dosya yolunu oluştur
    $file = __DIR__ . '/iyzipay/' . str_replace('\\', '/', $relative_class) . '.php';

    // Dosya mevcutsa dahil et
    if (file_exists($file)) {
        require_once $file;
    }
});