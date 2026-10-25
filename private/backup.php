<?php
declare(strict_types=1);
function sc_last_backup(): ?array { $files=glob(__DIR__.'/backups/sencan-*.sql.gz')?:[];rsort($files);if(!$files)return null;return ['name'=>basename($files[0]),'createdAt'=>gmdate('c',filemtime($files[0]))]; }
function sc_make_backup(PDO $db,array $config): array {
 $dir=__DIR__.'/backups';if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new RuntimeException('Yedek klasörü oluşturulamadı.');$name='sencan-'.gmdate('Y-m-d-His').'-'.bin2hex(random_bytes(3)).'.sql.gz';$tmp=$dir.'/'.$name.'.part';$handle=gzopen($tmp,'wb9');if(!$handle)throw new RuntimeException('Yedek yazılamadı.');$db->beginTransaction();try{
 sc_query($db,'SELECT id FROM sc_business WHERE id=1 FOR UPDATE')->fetch();gzwrite($handle,"-- Davut Sencan Hair Studio yedegi. Mevcut verilerin ustune yazar.\nSET NAMES utf8mb4;\n");gzwrite($handle,file_get_contents(__DIR__.'/schema.sql'));gzwrite($handle,"\nSET FOREIGN_KEY_CHECKS=0;\nSTART TRANSACTION;\n");
 foreach(['sc_email_outbox','sc_appointments','sc_sessions','sc_rate_limits','sc_audit','sc_admin','sc_business'] as $table)gzwrite($handle,"DELETE FROM `$table`;\n");
 foreach(['sc_business','sc_admin','sc_appointments','sc_email_outbox','sc_audit'] as $table){$q=$db->query("SELECT * FROM `$table`");while($row=$q->fetch()){if($table==='sc_email_outbox'&&$row['status']==='pending')$row['status']='skipped';$cols=implode(',',array_map(fn($c)=>'`'.$c.'`',array_keys($row)));$values=implode(',',array_map(fn($v)=>$v===null?'NULL':$db->quote((string)$v),array_values($row)));gzwrite($handle,"INSERT INTO `$table` ($cols) VALUES ($values);\n");}}
 gzwrite($handle,"COMMIT;\nSET FOREIGN_KEY_CHECKS=1;\n");$db->commit();gzclose($handle);chmod($tmp,0600);rename($tmp,$dir.'/'.$name);
 }catch(Throwable $e){if($db->inTransaction())$db->rollBack();gzclose($handle);@unlink($tmp);throw $e;}
 $cutoff=time()-max(1,(int)($config['backup_keep_days']??30))*86400;foreach(glob($dir.'/sencan-*.sql.gz')?:[] as $file)if(filemtime($file)<$cutoff)unlink($file);return ['name'=>$name,'createdAt'=>gmdate('c')];
}
