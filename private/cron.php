<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/bootstrap.php';require_once __DIR__.'/backup.php';require_once __DIR__.'/mail.php';
$lock=fopen(__DIR__.'/.cron.lock','c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))exit;
try{$config=sc_config();$db=sc_db($config);sc_ready($db);$last=sc_last_backup();if(!$last||time()-strtotime($last['createdAt'])>=86400)sc_make_backup($db,$config);sc_drain_mail($db,$config);sc_query($db,'DELETE FROM sc_sessions WHERE expires<?',[sc_ms()]);sc_query($db,'DELETE FROM sc_rate_limits WHERE expires<?',[sc_ms()]);file_put_contents(__DIR__.'/cron-status.json',json_encode(['completedAt'=>gmdate('c')],JSON_THROW_ON_ERROR),LOCK_EX);@chmod(__DIR__.'/cron-status.json',0600);echo "Yedek / bildirim görevi tamamlandı.\n";}catch(Throwable){fwrite(STDERR,"Görev tamamlanamadı. Yapılandırma ve dosya izinlerini kontrol edin.\n");exit(1);}finally{flock($lock,LOCK_UN);fclose($lock);}
