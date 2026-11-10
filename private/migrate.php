<?php
declare(strict_types=1);
// Eski kurulumları kendiliğinden günceller: phpMyAdmin / elle SQL gerekmez.
// Her şey tekrar çalıştırılabilir (idempotent) yazıldı.
function sc_has_column(PDO $db,string $table,string $column): bool {
 return (int)sc_query($db,'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?',[$table,$column])->fetchColumn()>0;
}
function sc_migrate(PDO $db): void {
 $marker=__DIR__.'/.migrated-v3';
 if(is_file($marker))return;
 $db->exec("CREATE TABLE IF NOT EXISTS sc_reviews (id CHAR(36) PRIMARY KEY, booking_id CHAR(36) NULL UNIQUE, barber_id INT NULL, name VARCHAR(100) NOT NULL, rating TINYINT NOT NULL, body VARCHAR(1000) NOT NULL, visible TINYINT NOT NULL DEFAULT 1, edited TINYINT NOT NULL DEFAULT 0, created_at VARCHAR(40) NOT NULL, updated_at VARCHAR(40) NOT NULL, INDEX sc_rev_list(visible,created_at), INDEX sc_rev_barber(barber_id,visible)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
 $db->exec("CREATE TABLE IF NOT EXISTS sc_kv (k VARCHAR(40) PRIMARY KEY, v BIGINT NOT NULL DEFAULT 0, s TEXT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
 $db->exec("CREATE TABLE IF NOT EXISTS sc_gallery (id CHAR(36) PRIMARY KEY, sort_order INT NOT NULL DEFAULT 0, caption VARCHAR(120) NOT NULL DEFAULT '', data LONGTEXT NOT NULL, created_at VARCHAR(40) NOT NULL, INDEX sc_gal_sort(sort_order,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
 // Önceki sürümde (randevuya bağlı yorum) kurulmuş tabloyu yeni biçime getir.
 if(!sc_has_column($db,'sc_reviews','barber_id')){
  try{$db->exec('ALTER TABLE sc_reviews ADD COLUMN barber_id INT NULL, ADD INDEX sc_rev_barber(barber_id,visible)');}
  catch(PDOException $e){if(!sc_has_column($db,'sc_reviews','barber_id'))throw $e;}
 }
 try{$db->exec('ALTER TABLE sc_reviews MODIFY booking_id CHAR(36) NULL');}catch(PDOException){}
 // Artık IP saklanmıyor; önceki sürümden kalan özet sütununu kaldır.
 if(sc_has_column($db,'sc_appointments','ip_hash')){
  try{$db->exec('ALTER TABLE sc_appointments DROP COLUMN ip_hash');}catch(PDOException){}
 }
 @file_put_contents($marker,gmdate('c'),LOCK_EX);@chmod($marker,0600);
}
