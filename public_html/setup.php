<?php
declare(strict_types=1);
ini_set('display_errors','0');
require_once dirname(__DIR__).'/private/bootstrap.php';
header('Cache-Control: no-store');header('X-Frame-Options: DENY');header('X-Content-Type-Options: nosniff');
$message='';$success=false;$locked=false;
try{
 $config=sc_config();$db=sc_db($config);try{$locked=(bool)sc_query($db,'SELECT id FROM sc_admin WHERE id=1')->fetch();}catch(PDOException){}
 if($locked){http_response_code(404);$message='Kurulum tamamlanmış. Yönetici panelinden giriş yapın.';}
 elseif($_SERVER['REQUEST_METHOD']==='POST'){
  sc_origin($config);$token=$_POST['token']??'';$password=$_POST['password']??'';$confirm=$_POST['confirm']??'';
  if(strlen((string)($config['install_token']??''))<24)sc_fail(400,'Önce private/config.php içinde en az 24 karakterlik kurulum anahtarı belirleyin.');
  if(!is_string($token)||!hash_equals($config['install_token'],$token))sc_fail(403,'Kurulum anahtarı hatalı.');
  if(!is_string($password)||mb_strlen($password)<12||mb_strlen($password)>256||$password!==$confirm)sc_fail(400,'Şifreler aynı ve en az 12 karakter olmalı.');
  foreach(explode(';',file_get_contents(dirname(__DIR__).'/private/schema.sql')) as $sql)if(trim($sql)!=='')$db->exec($sql);
  $db->beginTransaction();try{sc_query($db,'INSERT INTO sc_admin(id,password_hash) VALUES(1,?)',[sc_password_hash($password)]);sc_query($db,'INSERT INTO sc_business(id,json) VALUES(1,?)',[json_encode(sc_initial_business(),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);$db->commit();}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
  $success=true;$message='Kurulum tamamlandı. Yönetici panelinden giriş yapabilirsiniz. Güvenlik için setup.php dosyasını silin.';
 }
}catch(ScError $e){http_response_code($e->getCode());$message=$e->getMessage();}catch(Throwable){http_response_code(500);$message='Kurulum tamamlanamadı. Veritabanı izinlerini ve config.php ayarlarını kontrol edin.';error_log('Sencan kurulumu tamamlanamadi.');}
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Şencan · İlk kurulum</title><style>body{background:#10100d;color:#eee8d9;font:15px/1.7 Arial,sans-serif;padding:30px 15px}main{max-width:490px;margin:8vh auto;background:#19170f;border:1px solid #514a37;border-radius:25px;padding:30px}h1{font:30px Georgia,serif;color:#c5b48c}label{display:block;margin:18px 0}input{display:block;box-sizing:border-box;width:100%;padding:13px;border:1px solid #514a37;border-radius:12px;background:#0d0c08;color:white;margin-top:7px}button,a{display:inline-block;background:#c5b48c;color:#15130d;padding:12px 20px;border:0;border-radius:25px;text-decoration:none}p{font-size:13px;overflow-wrap:anywhere}</style></head><body><main><h1>Davut Şencan Hair Studio</h1><p>PHP + MySQL · İlk yönetici kurulumu</p><p role="status"><?=htmlspecialchars($message,ENT_QUOTES,'UTF-8')?></p><?php if(!$success&&!$locked):?><form method="post"><label>Kurulum anahtarı<input name="token" type="password" required autocomplete="off"></label><label>Yönetici şifresi<input name="password" type="password" required minlength="12" maxlength="256" autocomplete="new-password"></label><label>Şifre tekrar<input name="confirm" type="password" required minlength="12" maxlength="256" autocomplete="new-password"></label><button>Kurulumu tamamla</button></form><?php else:?><a href="admin.html">Yönetici panelini aç</a><?php endif;?></main></body></html>
