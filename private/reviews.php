<?php
declare(strict_types=1);
// Yorumlar: herkes yazabilir. Her yorum isteğe bağlı olarak bir çalışana bağlıdır (çalışan bazlı puan).

function sc_barber_label(array $business,?int $id): ?string {
 if(!$id)return null;$b=sc_find_barber($business,$id);return $b['name']??null;
}
function sc_review_public(array $r,array $business): array {
 $bid=$r['barber_id']===null?null:(int)$r['barber_id'];
 return ['id'=>$r['id'],'name'=>$r['name'],'rating'=>(int)$r['rating'],'body'=>$r['body'],'createdAt'=>$r['created_at'],'barberId'=>$bid,'barberName'=>sc_barber_label($business,$bid)];
}
/** Çalışan başına ortalama puan ve yorum sayısı (yalnız yayındaki yorumlar). */
function sc_barber_ratings(PDO $db): array {
 $out=[];
 foreach(sc_query($db,'SELECT barber_id,COUNT(*) n,AVG(rating) a FROM sc_reviews WHERE visible=1 AND barber_id IS NOT NULL GROUP BY barber_id')->fetchAll() as $r)$out[(string)$r['barber_id']]=['count'=>(int)$r['n'],'average'=>round((float)$r['a'],1)];
 return $out;
}
function sc_reviews_public(PDO $db,array $business): array {
 $rows=sc_query($db,'SELECT * FROM sc_reviews WHERE visible=1 ORDER BY created_at DESC LIMIT 150')->fetchAll();
 $stat=sc_query($db,'SELECT COUNT(*) n, AVG(rating) a FROM sc_reviews WHERE visible=1')->fetch();
 return ['reviews'=>array_map(fn($r)=>sc_review_public($r,$business),$rows),'count'=>(int)$stat['n'],'average'=>$stat['n']?round((float)$stat['a'],1):null,'byBarber'=>sc_barber_ratings($db)];
}
function sc_review_create(PDO $db,array $data): array {
 // Bot tuzağı: gizli "website" alanı doluysa sessizce kabul etmiş gibi davran.
 if(!empty($data['website']))sc_json(201,['review'=>null]);
 $name=$data['name']??null;$body=$data['body']??null;$rating=filter_var($data['rating']??null,FILTER_VALIDATE_INT);
 if(!is_string($name)||mb_strlen(trim($name))<2||mb_strlen(trim($name))>40)sc_fail(400,'Adınızı yazın (2–40 karakter).');
 if($rating===false||$rating<1||$rating>5)sc_fail(400,'1 ile 5 arasında yıldız seçin.');
 if(!is_string($body))sc_fail(400,'Yorum yazın.');$body=trim(preg_replace('/\R{3,}/u',"\n\n",$body)??'');
 if(mb_strlen($body)<3||mb_strlen($body)>600)sc_fail(400,'Yorum 3–600 karakter olmalı.');
 if(preg_match('~https?://|www\.~iu',$body.' '.$name))sc_fail(400,'Yorumda bağlantı paylaşılamaz.');
 $barberId=null;$raw=$data['barberId']??null;
 return sc_tx($db,function()use($db,$name,$body,$rating,$raw){
  $business=sc_business($db);$barberId=null;
  if($raw!==null&&$raw!==''){$id=filter_var($raw,FILTER_VALIDATE_INT);if($id===false||!sc_find_barber($business,$id))sc_fail(400,'Seçilen usta bulunamadı.');$barberId=$id;}
  $id=sc_uuid();$now=sc_now();
  sc_query($db,'INSERT INTO sc_reviews(id,barber_id,name,rating,body,visible,edited,created_at,updated_at) VALUES(?,?,?,?,?,1,0,?,?)',[$id,$barberId,trim($name),$rating,$body,$now,$now]);
  sc_audit($db,'review_created',$id);
  return sc_review_public(sc_query($db,'SELECT * FROM sc_reviews WHERE id=?',[$id])->fetch(),$business);
 });
}
function sc_reviews_admin(PDO $db,array $business): array {
 $rows=sc_query($db,'SELECT * FROM sc_reviews ORDER BY created_at DESC LIMIT 300')->fetchAll();
 return array_map(function($r)use($business){return sc_review_public($r,$business)+['visible'=>(int)$r['visible']===1,'edited'=>(int)$r['edited']===1,'updatedAt'=>$r['updated_at']];},$rows);
}
function sc_review_admin_update(PDO $db,string $id,array $data): array {
 return sc_tx($db,function()use($db,$id,$data){
  $business=sc_business($db);
  $r=sc_query($db,'SELECT * FROM sc_reviews WHERE id=?',[$id])->fetch();if(!$r)sc_fail(404,'Yorum bulunamadı.');
  $name=array_key_exists('name',$data)?$data['name']:$r['name'];$body=array_key_exists('body',$data)?$data['body']:$r['body'];$rating=array_key_exists('rating',$data)?filter_var($data['rating'],FILTER_VALIDATE_INT):(int)$r['rating'];$visible=array_key_exists('visible',$data)?$data['visible']:((int)$r['visible']===1);
  $barberId=$r['barber_id']===null?null:(int)$r['barber_id'];
  if(array_key_exists('barberId',$data)){$raw=$data['barberId'];if($raw===null||$raw==='')$barberId=null;else{$bid=filter_var($raw,FILTER_VALIDATE_INT);if($bid===false||!sc_find_barber($business,$bid))sc_fail(400,'Seçilen usta bulunamadı.');$barberId=$bid;}}
  if(!is_string($name)||mb_strlen(trim($name))<1||mb_strlen(trim($name))>100)sc_fail(400,'İsim 1–100 karakter olmalı.');
  if(!is_string($body)||mb_strlen(trim($body))<1||mb_strlen(trim($body))>1000)sc_fail(400,'Yorum 1–1000 karakter olmalı.');
  if($rating===false||$rating<1||$rating>5)sc_fail(400,'Puan 1–5 olmalı.');if(!is_bool($visible))sc_fail(400,'Görünürlük geçersiz.');
  $old=$r['barber_id']===null?null:(int)$r['barber_id'];
  $changed=trim($name)!==$r['name']||trim($body)!==$r['body']||$rating!==(int)$r['rating']||$barberId!==$old;
  sc_query($db,'UPDATE sc_reviews SET name=?,body=?,rating=?,barber_id=?,visible=?,edited=?,updated_at=? WHERE id=?',[trim($name),trim($body),$rating,$barberId,$visible?1:0,($changed||(int)$r['edited']===1)?1:0,sc_now(),$id]);
  sc_audit($db,'review_admin_update',$id);
  return sc_query($db,'SELECT * FROM sc_reviews WHERE id=?',[$id])->fetch();
 });
}
