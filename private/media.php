<?php
declare(strict_types=1);
function sc_media_path(string $id): string { if(!preg_match('/^[a-f0-9]{48}$/D',$id))sc_fail(404,'Video bulunamadı.');return __DIR__.'/media/'.$id; }
function sc_video_upload(PDO $db): array {
 $f=$_FILES['video']??null;if(!$f||!is_array($f)||($f['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)sc_fail(413,'Video yüklenemedi. PHP upload_max_filesize ve post_max_size sınırlarını kontrol edin.');
 if($f['size']<1||$f['size']>60*1024*1024||!is_uploaded_file($f['tmp_name']))sc_fail(400,'Video en fazla 60 MB olabilir.');
 $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);if(!in_array($mime,['video/mp4','video/webm'],true))sc_fail(400,'MP4 veya WebM video gerekli.');
 $dir=__DIR__.'/media';if(!is_dir($dir)&&!mkdir($dir,0750,true))sc_fail(503,'Video dizini oluşturulamadı.');$id=bin2hex(random_bytes(24));$path=sc_media_path($id);if(!move_uploaded_file($f['tmp_name'],$path))sc_fail(503,'Video kaydedilemedi.');chmod($path,0640);
 $record=['id'=>$id,'mime'=>$mime,'size'=>(int)$f['size'],'name'=>mb_substr(basename((string)$f['name']),0,160)];
 try{$old=sc_tx($db,function()use($db,$record){$b=sc_business($db);$old=$b['heroVideo']['id']??null;$b['heroVideo']=$record;sc_save_business($db,$b);sc_audit($db,'hero_video_update');return $old;});}catch(Throwable $e){@unlink($path);throw $e;}
 if(is_string($old)&&preg_match('/^[a-f0-9]{48}$/D',$old))@unlink(sc_media_path($old));return $record;
}
function sc_video_delete(PDO $db): void { $old=sc_tx($db,function()use($db){$b=sc_business($db);$old=$b['heroVideo']['id']??null;$b['heroVideo']=null;sc_save_business($db,$b);sc_audit($db,'hero_video_delete');return $old;});if(is_string($old)&&preg_match('/^[a-f0-9]{48}$/D',$old))@unlink(sc_media_path($old)); }
function sc_video_stream(array $business): never {
 $id=$_GET['id']??'';$video=$business['heroVideo']??null;if(!is_string($id)||!is_array($video)||$video['id']!==$id)sc_fail(404,'Video bulunamadı.');$path=sc_media_path($id);if(!is_file($path))sc_fail(404,'Video bulunamadı.');$size=filesize($path);$start=0;$end=$size-1;
 if(isset($_SERVER['HTTP_RANGE'])){if(!preg_match('/^bytes=(\d*)-(\d*)$/D',$_SERVER['HTTP_RANGE'],$m)||($m[1]===''&&$m[2]==='')){header('Content-Range: bytes */'.$size);http_response_code(416);exit;}if($m[1]===''){$start=max(0,$size-(int)$m[2]);}else{$start=(int)$m[1];if($m[2]!=='')$end=min($end,(int)$m[2]);}if($start>$end||$start>=$size){header('Content-Range: bytes */'.$size);http_response_code(416);exit;}http_response_code(206);header('Content-Range: bytes '.$start.'-'.$end.'/'.$size);}
 header('Content-Type: '.$video['mime']);header('Content-Length: '.($end-$start+1));header('Accept-Ranges: bytes');header('Cache-Control: public, max-age=86400');header('Content-Disposition: inline');if($_SERVER['REQUEST_METHOD']==='HEAD')exit;$file=fopen($path,'rb');fseek($file,$start);$remaining=$end-$start+1;while($remaining>0&&!feof($file)&&!connection_aborted()){$chunk=fread($file,min(65536,$remaining));if($chunk===false)break;echo $chunk;$remaining-=strlen($chunk);}fclose($file);exit;
}
function sc_about_data(array $data): array {
 $title=$data['title']??null;$body=$data['body']??null;$photos=$data['photos']??null;
 if(!is_string($title)||!is_string($body)||mb_strlen(trim($title))<1||mb_strlen($title)>120||mb_strlen(trim($body))<1||mb_strlen($body)>6000||!is_array($photos)||!array_is_list($photos)||count($photos)>5)sc_fail(400,'Hakkımızda başlığı, metni veya fotoğrafları geçersiz.');
 foreach($photos as $p){if(!is_string($p)||strlen($p)>700000||!preg_match('~^data:image/jpeg;base64,([A-Za-z0-9+/=]+)$~D',$p,$m))sc_fail(400,'Küçük bir JPG fotoğrafı kullanın.');$raw=base64_decode($m[1],true);$info=$raw!==false?@getimagesizefromstring($raw):false;if(!$info||$info[2]!==IMAGETYPE_JPEG||$info[0]>4096||$info[1]>4096)sc_fail(400,'Geçerli bir JPG fotoğrafı gerekli.');}
 return ['title'=>trim($title),'body'=>trim($body),'photos'=>$photos];
}
