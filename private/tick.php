<?php
declare(strict_types=1);
// Cron'suz "döngü": siteye/yönetim paneline gelen her istek (ve tarayıcıdaki tick.js)
// bu işi tetikler. En fazla ~55 saniyede bir gerçekten çalışır; aynı anda iki kez çalışmaz.
// Yaptığı işler: mail kuyruğu + tekrar denemeler, günlük yedek, süresi dolan oturum/limit temizliği.

function sc_kv_claim(PDO $db,string $key,int $seconds): bool {
 $now=sc_ms();
 $row=sc_query($db,'SELECT v FROM sc_kv WHERE k=?',[$key])->fetch();
 if($row&&(int)$row['v']>$now)return false; // henüz zamanı gelmedi (ucuz kontrol)
 if(!$row)sc_query($db,'INSERT IGNORE INTO sc_kv(k,v) VALUES(?,0)',[$key]);
 // Atomik sahiplenme: yalnız tek istek rowCount()=1 alır.
 return sc_query($db,'UPDATE sc_kv SET v=? WHERE k=? AND v<=?',[$now+$seconds*1000,$key,$now])->rowCount()===1;
}
function sc_kv_text(PDO $db,string $key): ?string {
 $v=sc_query($db,'SELECT s FROM sc_kv WHERE k=?',[$key])->fetchColumn();return is_string($v)?$v:null;
}
function sc_kv_save_text(PDO $db,string $key,string $text): void {
 sc_query($db,'INSERT INTO sc_kv(k,v,s) VALUES(?,0,?) ON DUPLICATE KEY UPDATE s=VALUES(s)',[$key,$text]);
}
function sc_tick_status(PDO $db): ?array {
 try{$s=sc_kv_text($db,'tick_status');return $s?json_decode($s,true):null;}catch(Throwable){return null;}
}
function sc_tick(PDO $db,array $c): void {
 try{if(!sc_kv_claim($db,'tick_next',55))return;}catch(Throwable){return;}
 ignore_user_abort(true);@set_time_limit(55);
 // Gerçek cron.php veya elle çalıştırma ile aynı kilit: üst üste binmez.
 $lock=@fopen(__DIR__.'/.cron.lock','c');
 if($lock&&!flock($lock,LOCK_EX|LOCK_NB)){fclose($lock);return;}
 $report=['completedAt'=>null,'mail'=>null,'backup'=>null,'errors'=>[]];
 try{
  try{$report['mail']=sc_drain_mail($db,$c,5);}catch(Throwable){$report['errors'][]='mail';error_log('Sencan tick: mail kuyrugu islenemedi.');}
  try{
   $last=sc_last_backup();
   if((!$last||time()-strtotime($last['createdAt'])>=86400)&&sc_kv_claim($db,'backup_next',3600)){sc_make_backup($db,$c);$report['backup']='created';}
  }catch(Throwable){$report['errors'][]='backup';error_log('Sencan tick: yedek alinamadi.');}
  try{sc_query($db,'DELETE FROM sc_sessions WHERE expires<?',[sc_ms()]);sc_query($db,'DELETE FROM sc_rate_limits WHERE expires<?',[sc_ms()]);}catch(Throwable){}
  $report['completedAt']=gmdate('c');
  try{sc_kv_save_text($db,'tick_status',json_encode($report,JSON_THROW_ON_ERROR));}catch(Throwable){}
 }finally{if($lock){flock($lock,LOCK_UN);fclose($lock);}}
}
