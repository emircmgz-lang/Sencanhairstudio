<?php
declare(strict_types=1);
// Galeri: görseller veritabanında (base64, JPEG) saklanır; /api/gallery/image ile sunulur.
// /config yanıtını şişirmemek ve yedeğe dahil olmak için dosya yerine tablo kullanılır.
const SC_GALLERY_MAX=40;

function sc_gallery_list(PDO $db): array {
 return array_map(fn($r)=>['id'=>$r['id'],'caption'=>$r['caption']],sc_query($db,'SELECT id,caption FROM sc_gallery ORDER BY sort_order,created_at')->fetchAll());
}
function sc_gallery_image(PDO $db,string $id): never {
 if(!preg_match('/^[a-f0-9-]{36}$/D',$id))sc_fail(404,'Görsel bulunamadı.');
 $data=sc_query($db,'SELECT data FROM sc_gallery WHERE id=?',[$id])->fetchColumn();
 if(!is_string($data))sc_fail(404,'Görsel bulunamadı.');
 $bin=base64_decode($data,true);if($bin===false)sc_fail(404,'Görsel bulunamadı.');
 $etag='"'.substr(hash('sha256',$id),0,20).'"';
 header('Content-Type: image/jpeg');header('Cache-Control: public, max-age=31536000, immutable');header('ETag: '.$etag);
 if(($_SERVER['HTTP_IF_NONE_MATCH']??'')===$etag){http_response_code(304);exit;}
 header('Content-Length: '.strlen($bin));echo $bin;exit;
}
function sc_gallery_add(PDO $db,array $data): array {
 $image=$data['image']??null;$caption=$data['caption']??'';
 if(!is_string($image)||!preg_match('~^data:image/jpeg;base64,([A-Za-z0-9+/=]+)$~D',$image,$m))sc_fail(400,'Geçerli bir JPG görsel seçin.');
 if(!is_string($caption)||mb_strlen(trim($caption))>120)sc_fail(400,'Açıklama en fazla 120 karakter olabilir.');
 if(strlen($m[1])>2800000)sc_fail(413,'Görsel çok büyük. Daha küçük bir görsel deneyin.');
 $bin=base64_decode($m[1],true);$info=$bin===false?false:@getimagesizefromstring($bin);
 if(!$info||$info[2]!==IMAGETYPE_JPEG||$info[0]>5000||$info[1]>5000||$info[0]<100||$info[1]<100)sc_fail(400,'Görsel okunamadı. JPG, JPEG, PNG veya WebP seçin.');
 return sc_tx($db,function()use($db,$m,$caption){
  if((int)sc_query($db,'SELECT COUNT(*) FROM sc_gallery')->fetchColumn()>=SC_GALLERY_MAX)sc_fail(400,'Galeriye en fazla '.SC_GALLERY_MAX.' görsel eklenebilir.');
  $id=sc_uuid();$order=(int)sc_query($db,'SELECT COALESCE(MAX(sort_order),0)+1 FROM sc_gallery')->fetchColumn();
  sc_query($db,'INSERT INTO sc_gallery(id,sort_order,caption,data,created_at) VALUES(?,?,?,?,?)',[$id,$order,trim($caption),$m[1],sc_now()]);
  sc_audit($db,'gallery_add',$id);return ['id'=>$id,'caption'=>trim($caption)];
 });
}
function sc_gallery_update(PDO $db,string $id,array $data): void {
 sc_tx($db,function()use($db,$id,$data){
  $row=sc_query($db,'SELECT id,sort_order,caption FROM sc_gallery WHERE id=?',[$id])->fetch();if(!$row)sc_fail(404,'Görsel bulunamadı.');
  if(array_key_exists('caption',$data)){$c=$data['caption'];if(!is_string($c)||mb_strlen(trim($c))>120)sc_fail(400,'Açıklama en fazla 120 karakter olabilir.');sc_query($db,'UPDATE sc_gallery SET caption=? WHERE id=?',[trim($c),$id]);}
  if(isset($data['move'])){
   if(!in_array($data['move'],['up','down'],true))sc_fail(400,'Sıralama isteği geçersiz.');
   // Sıra numaralarını 1..n olarak yeniden yazıp komşuyla yer değiştir.
   $ids=array_column(sc_query($db,'SELECT id FROM sc_gallery ORDER BY sort_order,created_at')->fetchAll(),'id');$i=array_search($id,$ids,true);$j=$data['move']==='up'?$i-1:$i+1;
   if($i!==false&&isset($ids[$j])){[$ids[$i],$ids[$j]]=[$ids[$j],$ids[$i]];}
   foreach($ids as $n=>$gid)sc_query($db,'UPDATE sc_gallery SET sort_order=? WHERE id=?',[$n+1,$gid]);
  }
  sc_audit($db,'gallery_update',$id);
 });
}
function sc_gallery_delete(PDO $db,string $id): void {
 sc_tx($db,function()use($db,$id){sc_query($db,'DELETE FROM sc_gallery WHERE id=?',[$id]);sc_audit($db,'gallery_delete',$id);});
}
