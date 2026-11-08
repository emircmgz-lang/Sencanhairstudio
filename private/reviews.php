<?php
declare(strict_types=1);
// Yorumlar: yalnız randevuyu alan kişinin IP'sinden, randevu saati geçtikten sonra yazılabilir.
// IP düz metin saklanmaz; sunucuya özel tuzla HMAC özeti saklanır.

function sc_client_ip(array $c): string {
 $ip=(string)($_SERVER['REMOTE_ADDR']??'');
 $h=$c['ip_header']??''; // Cloudflare vb. arkasındaysanız config.php: 'ip_header'=>'HTTP_CF_CONNECTING_IP'
 if(is_string($h)&&preg_match('/^HTTP_[A-Z0-9_]+$/D',$h)&&!empty($_SERVER[$h])){$v=trim(explode(',',(string)$_SERVER[$h])[0]);if(filter_var($v,FILTER_VALIDATE_IP))$ip=$v;}
 if(filter_var($ip,FILTER_VALIDATE_IP)===false)return '';
 if(str_contains($ip,':')){$bin=inet_pton($ip);if($bin!==false)$ip=bin2hex(substr($bin,0,8));} // IPv6: /64 öneki (alt kısım sürekli değişir)
 return $ip;
}
function sc_ip_salt(PDO $db): string {
 $s=sc_kv_text($db,'ip_salt');
 if(!$s){sc_query($db,'INSERT IGNORE INTO sc_kv(k,v,s) VALUES(?,0,?)',['ip_salt',bin2hex(random_bytes(32))]);$s=sc_kv_text($db,'ip_salt');}
 return (string)$s;
}
function sc_ip_hash(PDO $db,array $c): ?string {
 $ip=sc_client_ip($c);return $ip===''?null:hash_hmac('sha256',$ip,sc_ip_salt($db));
}
function sc_review_name(string $full): string {
 $p=preg_split('/\s+/u',trim($full))?:[];$first=$p[0]??'Müşteri';
 return count($p)>1?$first.' '.mb_strtoupper(mb_substr(end($p),0,1),'UTF-8').'.':$first;
}
function sc_review_public(array $r): array {
 return ['id'=>$r['id'],'name'=>$r['name'],'rating'=>(int)$r['rating'],'body'=>$r['body'],'createdAt'=>$r['created_at']];
}
/** Bu IP'nin yorum yazabileceği (saati geçmiş, henüz yorumlanmamış) en yeni randevu. */
function sc_review_eligible(PDO $db,array $c,?array $business=null): ?array {
 $hash=sc_ip_hash($db,$c);if($hash===null)return null;
 $days=max(1,(int)($c['review_window_days']??30));$since=(new DateTimeImmutable('now',new DateTimeZone('Europe/Istanbul')))->modify("-$days days")->format('Y-m-d');
 $rows=sc_query($db,"SELECT a.* FROM sc_appointments a LEFT JOIN sc_reviews r ON r.booking_id=a.id WHERE a.ip_hash=? AND a.status IN ('confirmed','completed') AND a.date>=? AND r.id IS NULL ORDER BY a.date DESC,a.start DESC LIMIT 10",[$hash,$since])->fetchAll();
 foreach($rows as $row)if(sc_booking_started($row)){
  $b=$business?sc_find_barber($business,(int)$row['barber_id']):null;
  return ['id'=>$row['id'],'name'=>sc_review_name($row['name']),'date'=>$row['date'],'time'=>sc_clock((int)$row['start']),'barberName'=>$b['name']??null];
 }
 return null;
}
function sc_reviews_public(PDO $db): array {
 $rows=sc_query($db,'SELECT * FROM sc_reviews WHERE visible=1 ORDER BY created_at DESC LIMIT 60')->fetchAll();
 $stat=sc_query($db,'SELECT COUNT(*) n, AVG(rating) a FROM sc_reviews WHERE visible=1')->fetch();
 return ['reviews'=>array_map('sc_review_public',$rows),'count'=>(int)$stat['n'],'average'=>$stat['n']?round((float)$stat['a'],1):null];
}
function sc_review_create(PDO $db,array $c,array $data): array {
 $rating=filter_var($data['rating']??null,FILTER_VALIDATE_INT);$body=$data['body']??null;
 if($rating===false||$rating<1||$rating>5)sc_fail(400,'1 ile 5 arasında yıldız seçin.');
 if(!is_string($body))sc_fail(400,'Yorum yazın.');$body=trim(preg_replace('/\R{3,}/u',"\n\n",$body)??'');
 if(mb_strlen($body)<3||mb_strlen($body)>600)sc_fail(400,'Yorum 3–600 karakter olmalı.');
 return sc_tx($db,function()use($db,$c,$rating,$body){
  $e=sc_review_eligible($db,$c,sc_business($db));
  if(!$e)sc_fail(403,'Yorum yapabilmek için bu cihazın ağından alınmış ve saati geçmiş bir randevunuz olmalı.');
  $id=sc_uuid();$now=sc_now();
  try{sc_query($db,'INSERT INTO sc_reviews(id,booking_id,name,rating,body,visible,edited,created_at,updated_at) VALUES(?,?,?,?,?,1,0,?,?)',[$id,$e['id'],$e['name'],$rating,$body,$now,$now]);}
  catch(PDOException $x){if($x->getCode()==='23000')sc_fail(409,'Bu randevu için zaten yorum yapılmış.');throw $x;}
  sc_audit($db,'review_created',$id);
  return sc_review_public(sc_query($db,'SELECT * FROM sc_reviews WHERE id=?',[$id])->fetch());
 });
}
function sc_reviews_admin(PDO $db,array $business): array {
 $rows=sc_query($db,'SELECT r.*,a.date adate,a.start astart,a.code acode,a.barber_id abarber FROM sc_reviews r LEFT JOIN sc_appointments a ON a.id=r.booking_id ORDER BY r.created_at DESC LIMIT 300')->fetchAll();
 return array_map(function($r)use($business){return ['id'=>$r['id'],'name'=>$r['name'],'rating'=>(int)$r['rating'],'body'=>$r['body'],'visible'=>(int)$r['visible']===1,'edited'=>(int)$r['edited']===1,'createdAt'=>$r['created_at'],'updatedAt'=>$r['updated_at'],'appointment'=>$r['adate']?['date'=>$r['adate'],'time'=>sc_clock((int)$r['astart']),'code'=>$r['acode'],'barberName'=>sc_find_barber($business,(int)$r['abarber'])['name']??null]:null];},$rows);
}
function sc_review_admin_update(PDO $db,string $id,array $data): array {
 return sc_tx($db,function()use($db,$id,$data){
  $r=sc_query($db,'SELECT * FROM sc_reviews WHERE id=?',[$id])->fetch();if(!$r)sc_fail(404,'Yorum bulunamadı.');
  $name=array_key_exists('name',$data)?$data['name']:$r['name'];$body=array_key_exists('body',$data)?$data['body']:$r['body'];$rating=array_key_exists('rating',$data)?filter_var($data['rating'],FILTER_VALIDATE_INT):(int)$r['rating'];$visible=array_key_exists('visible',$data)?$data['visible']:((int)$r['visible']===1);
  if(!is_string($name)||mb_strlen(trim($name))<1||mb_strlen(trim($name))>100)sc_fail(400,'İsim 1–100 karakter olmalı.');
  if(!is_string($body)||mb_strlen(trim($body))<1||mb_strlen(trim($body))>1000)sc_fail(400,'Yorum 1–1000 karakter olmalı.');
  if($rating===false||$rating<1||$rating>5)sc_fail(400,'Puan 1–5 olmalı.');if(!is_bool($visible))sc_fail(400,'Görünürlük geçersiz.');
  $changed=trim($name)!==$r['name']||trim($body)!==$r['body']||$rating!==(int)$r['rating'];
  sc_query($db,'UPDATE sc_reviews SET name=?,body=?,rating=?,visible=?,edited=?,updated_at=? WHERE id=?',[trim($name),trim($body),$rating,$visible?1:0,($changed||(int)$r['edited']===1)?1:0,sc_now(),$id]);
  sc_audit($db,'review_admin_update',$id);
  return sc_query($db,'SELECT * FROM sc_reviews WHERE id=?',[$id])->fetch();
 });
}
