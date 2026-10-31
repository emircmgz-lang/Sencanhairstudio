<?php
declare(strict_types=1);
require_once __DIR__.'/smtp.php';
function sc_mail_transport(array $c): string { return $c['mail_transport']??'smtp'; }
function sc_mail_ready(array $c): bool {
 if(empty($c['mail_enabled'])||empty($c['mail_from']))return false;
 if(sc_mail_transport($c)==='resend')return !empty($c['resend_api_key'])&&function_exists('curl_init');
 if(sc_mail_transport($c)!=='smtp'||!function_exists('stream_socket_client')||!function_exists('mb_strcut'))return false;
 try{sc_smtp_settings($c);sc_smtp_address($c['mail_from']);return true;}catch(ScMailError){return false;}
}
function sc_mail_recipient(array $business,int $barberId): ?string { $b=sc_find_barber($business,$barberId);$email=$b['email']??'';return is_string($email)&&filter_var($email,FILTER_VALIDATE_EMAIL)?$email:null; }
function sc_mail_payload(array $c,array $a,array $business): array { $recipient=sc_mail_recipient($business,(int)$a['barberId']);if($recipient===null)throw new RuntimeException('Çalışan e-posta adresi tanımlı değil.');$b=sc_find_barber($business,$a['barberId']);$text=implode("\n",[$business['name'],'Yeni randevu kaydedildi.','Müşteri: '.$a['name'],'Telefon: '.$a['phone'],'Çalışan: '.($b['name']??'Çalışan'),'Tarih: '.$a['date'],'Saat: '.$a['time'].' (Türkiye saati)','Hizmetler: '.implode(' + ',array_column($a['services'],'name')),'Tutar: '.$a['total'].' TL'.($a['priceFrom']?' ve üzeri':''),'Randevu kodu: '.$a['code']]);return ['from'=>$c['mail_from'],'to'=>[$recipient],'subject'=>$business['name'].' · Yeni randevu · '.$a['code'],'text'=>$text]; }
function sc_send_mail(array $c,array $a,array $business,string $jobId): void { $payload=sc_mail_payload($c,$a,$business);if(sc_mail_transport($c)==='smtp'){sc_send_smtp($c,$payload,$jobId);return;}if(sc_mail_transport($c)!=='resend')throw new ScMailError('Gönderim yöntemi geçersiz.');$ch=curl_init('https://api.resend.com/emails');curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15,CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.$c['resend_api_key'],'Idempotency-Key: booking-'.$jobId],CURLOPT_POSTFIELDS=>json_encode($payload,JSON_THROW_ON_ERROR)]);curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$err=curl_errno($ch);curl_close($ch);if($err||$status<200||$status>=300)throw new RuntimeException('Gönderim hizmeti isteği tamamlanamadı.'); }
function sc_booking_started(array $row): bool { $now=new DateTimeImmutable('now',new DateTimeZone('Europe/Istanbul'));$today=$now->format('Y-m-d');return $row['date']<$today||($row['date']===$today&&(int)$row['start']<=((int)$now->format('H')*60+(int)$now->format('i'))); }
function sc_drain_mail(PDO $db,array $c,int $limit=10): array {
 $result=['processed'=>0,'accepted'=>0,'skipped'=>0,'errors'=>0,'lastError'=>null];
 if(!sc_mail_ready($c))return $result;
 $limit=max(1,min(10,$limit));
 $jobs=sc_query($db,"SELECT * FROM sc_email_outbox WHERE status='pending' AND next_attempt<=? ORDER BY created_at LIMIT ".$limit,[sc_ms()])->fetchAll();
 foreach($jobs as $job){
  $result['processed']++;
  $row=sc_query($db,'SELECT * FROM sc_appointments WHERE id=?',[$job['booking_id']])->fetch();
  if(!$row||$row['status']!=='confirmed'||sc_booking_started($row)||sc_mail_recipient(sc_business($db),(int)$row['barber_id'])===null){sc_query($db,"UPDATE sc_email_outbox SET status='skipped' WHERE id=?",[$job['id']]);$result['skipped']++;continue;}
  try{
   sc_send_mail($c,sc_booking($row),sc_business($db),$job['id']);
   sc_query($db,"UPDATE sc_email_outbox SET status='sent',attempts=attempts+1,last_error=NULL WHERE id=?",[$job['id']]);$result['accepted']++;
  }catch(Throwable $e){
   $error=$e instanceof ScMailError?$e->getMessage():'Gönderim hizmeti isteği tamamlanamadı.';
   $n=(int)$job['attempts']+1;
   sc_query($db,'UPDATE sc_email_outbox SET status=?,attempts=?,next_attempt=?,last_error=? WHERE id=?',[$n>=5?'failed':'pending',$n,sc_ms()+min(60,2**$n)*60000,$error,$job['id']]);$result['errors']++;$result['lastError']=$error;
  }
 }
 return $result;
}
function sc_manual_mail_run(PDO $db,array $c): array {
 if(!sc_mail_ready($c))sc_fail(503,'Mail gönderim ayarları hazır değil.');
 $lock=@fopen(__DIR__.'/.cron.lock','c');
 if(!$lock)sc_fail(503,'Mail kuyruğu kilidi açılamadı. PHP kullanıcısının private dizini ve .cron.lock dosyası izinlerini kontrol edin.');
 try{
  if(!flock($lock,LOCK_EX|LOCK_NB))sc_fail(409,'Mail kuyruğu başka bir görev tarafından işleniyor. Biraz sonra tekrar deneyin.');
  // One job per click limits HTTP runtime. Uses the same lock as cron.php.
  $result=sc_drain_mail($db,$c,1);sc_audit($db,'manual_mail_run');return $result;
 }finally{flock($lock,LOCK_UN);fclose($lock);}
}
