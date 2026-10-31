<?php
declare(strict_types=1);

final class ScMailError extends RuntimeException {}

function sc_smtp_settings(array $c): array {
    $host=$c['smtp_host']??'mail.sencanhairstudio.com';$port=$c['smtp_port']??25;
    $security=$c['smtp_security']??'none';$username=$c['smtp_username']??'';$password=$c['smtp_password']??'';
    if(!is_string($host)||!preg_match('/^(?:localhost|[a-zA-Z0-9](?:[a-zA-Z0-9.-]*[a-zA-Z0-9])?)$/D',$host)||strlen($host)>253
        ||filter_var($port,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>65535]])===false
        ||!in_array($security,['none','','ssl','tls'],true)||!is_string($username)||!is_string($password)
        ||strlen($username)>254||strlen($password)>1024||preg_match('/[\x00-\x1F\x7F]/',$username))throw new ScMailError('SMTP sunucu/port/şifreleme ayarları geçersiz.');
    if(($username==='')!==($password===''))throw new ScMailError('SMTP kullanıcı adı ve mail şifresi birlikte girilmeli.');
    if($username!==''&&in_array($security,['none',''],true))throw new ScMailError('Şifreli oturum açma için SSL/TLS veya STARTTLS seçin.');
    if(in_array($security,['ssl','tls'],true)&&!extension_loaded('openssl'))throw new ScMailError('SMTP SSL/TLS için PHP OpenSSL eklentisi gerekli.');
    $helo=parse_url($c['public_origin']??'https://sencanhairstudio.com',PHP_URL_HOST);
    if(!is_string($helo)||!preg_match('/^[a-zA-Z0-9.-]+$/D',$helo))$helo='sencanhairstudio.com';
    return ['host'=>$host,'port'=>(int)$port,'helo'=>$helo,'security'=>$security,'username'=>$username,'password'=>$password];
}

function sc_smtp_address(string $value): array {
    if(preg_match('/[\x00-\x1F\x7F]/',$value))throw new ScMailError('Gönderen veya alıcı adresi geçersiz.');
    $value=trim($value);$name='';$address=$value;
    if(preg_match('/^(.+?)\s*<([^<>]+)>$/D',$value,$m)){$name=trim($m[1]," \t\"");$address=trim($m[2]);}
    if(!filter_var($address,FILTER_VALIDATE_EMAIL)||strlen($address)>254||preg_match('/[^\x20-\x7E]/',$address))throw new ScMailError('Gönderen veya alıcı adresi geçersiz.');
    return [$address,$name];
}

function sc_smtp_header(string $value): string {
    if(preg_match('/[\x00-\x1F\x7F]/',$value))throw new ScMailError('E-posta başlığı geçersiz.');
    $parts=[];$offset=0;while($offset<strlen($value)){$part=mb_strcut($value,$offset,42,'UTF-8');$offset+=strlen($part);$parts[]='=?UTF-8?B?'.base64_encode($part).'?=';}return implode("\r\n ",$parts);
}

function sc_smtp_message(array $payload,string $jobId): array {
    [$sender,$senderName]=sc_smtp_address($payload['from']);
    if(count($payload['to'])!==1)throw new ScMailError('Tek bir çalışan adresi gerekli.');
    [$recipient]=sc_smtp_address($payload['to'][0]);
    $domain=substr($sender,strrpos($sender,'@')+1);
    $headers=[
        'Date: '.gmdate('D, d M Y H:i:s').' +0000',
        'From: '.($senderName!==''?sc_smtp_header($senderName).' <'.$sender.'>':$sender),
        'To: '.$recipient,
        'Subject: '.sc_smtp_header($payload['subject']),
        'Message-ID: <'.hash('sha256','sencan-booking-'.$jobId).'@'.$domain.'>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
    ];
    $text=preg_replace('/\r\n|\r|\n/',"\r\n",$payload['text']);
    $message=implode("\r\n",$headers)."\r\n\r\n".chunk_split(base64_encode($text),76,"\r\n");
    return ['sender'=>$sender,'recipient'=>$recipient,'message'=>$message];
}

function sc_smtp_write($socket,string $bytes): void {
    $length=strlen($bytes);$offset=0;
    while($offset<$length){$n=@fwrite($socket,substr($bytes,$offset));if($n===false||$n===0)throw new ScMailError('SMTP bağlantısı yazma sırasında kesildi.');$offset+=$n;}
}

function sc_smtp_reply($socket,array $expected,string $stage,?array &$lines=null,bool $sensitive=false): int {
    $code=null;
    for($i=0;$i<100;$i++){
        $line=@fgets($socket,1024);
        if($line===false){$meta=stream_get_meta_data($socket);throw new ScMailError($meta['timed_out']?'SMTP '.$stage.' yanıtı zaman aşımına uğradı.':'SMTP '.$stage.' sırasında bağlantı kesildi.');}
        if(!preg_match('/^(\d{3})([ -])[^\r\n]*\r?\n$/D',$line,$m))throw new ScMailError('SMTP '.$stage.' yanıtı geçersiz.');
        if($lines!==null)$lines[]=rtrim(substr($line,4),"\r\n");
        $lineCode=(int)$m[1];if($code!==null&&$code!==$lineCode)throw new ScMailError('SMTP '.$stage.' çok satırlı yanıtı geçersiz.');$code=$lineCode;
        if($m[2]===' '){if(!in_array($code,$expected,true)){if($sensitive)throw new ScMailError('SMTP kimlik doğrulaması reddedildi ('.$code.'). Mail kullanıcı adını ve şifresini kontrol edin.');$detail=trim(preg_replace('/[\x00-\x1F\x7F]+/',' ',substr($line,4)));if(!mb_check_encoding($detail,'UTF-8'))$detail=mb_convert_encoding($detail,'UTF-8','UTF-8');throw new ScMailError(mb_substr('SMTP '.$stage.' reddedildi ('.$code.'): '.$detail,0,240,'UTF-8'));}return $code;}
    }
    throw new ScMailError('SMTP '.$stage.' yanıtı çok uzun.');
}

function sc_smtp_ehlo($socket,array $settings): array {
    sc_smtp_write($socket,'EHLO '.$settings['helo']."\r\n");$lines=[];
    $greeting=sc_smtp_reply($socket,[250,500,502,504],'EHLO',$lines);
    if($greeting!==250){
        if($settings['username']!==''||$settings['security']==='tls')throw new ScMailError('SMTP sunucusu güvenli giriş için gerekli EHLO isteğini desteklemiyor.');
        sc_smtp_write($socket,'HELO '.$settings['helo']."\r\n");sc_smtp_reply($socket,[250],'HELO');return [];
    }
    return $lines;
}
function sc_smtp_auth($socket,array $settings,array $capabilities): void {
    if($settings['username']==='')return;
    $methods=[];foreach($capabilities as $line)if(preg_match('/^AUTH(?:=|\s+)(.*)$/i',$line,$m))$methods=array_merge($methods,preg_split('/\s+/',strtoupper(trim($m[1]))));
    $capture=null;
    if(in_array('LOGIN',$methods,true)){
        sc_smtp_write($socket,"AUTH LOGIN\r\n");sc_smtp_reply($socket,[334],'AUTH LOGIN',$capture,true);
        sc_smtp_write($socket,base64_encode($settings['username'])."\r\n");sc_smtp_reply($socket,[334],'AUTH kullanıcı',$capture,true);
        sc_smtp_write($socket,base64_encode($settings['password'])."\r\n");sc_smtp_reply($socket,[235],'AUTH şifre',$capture,true);
    }elseif(in_array('PLAIN',$methods,true)){
        $encoded=base64_encode("\0".$settings['username']."\0".$settings['password']);sc_smtp_write($socket,'AUTH PLAIN '.$encoded."\r\n");
        if(sc_smtp_reply($socket,[235,334],'AUTH PLAIN',$capture,true)===334){sc_smtp_write($socket,$encoded."\r\n");sc_smtp_reply($socket,[235],'AUTH PLAIN',$capture,true);}
    }else throw new ScMailError('SMTP sunucusu desteklenen LOGIN/PLAIN kimlik doğrulamasını sunmuyor.');
}
function sc_send_smtp(array $c,array $payload,string $jobId): void {
    $settings=sc_smtp_settings($c);$message=sc_smtp_message($payload,$jobId);
    $crypto=STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT|(defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')?STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT:0);
    $context=stream_context_create(['ssl'=>['verify_peer'=>true,'verify_peer_name'=>true,'allow_self_signed'=>false,'peer_name'=>$settings['host'],'SNI_enabled'=>true,'crypto_method'=>$crypto]]);
    $scheme=$settings['security']==='ssl'?'tls':'tcp';
    $socket=@stream_socket_client($scheme.'://'.$settings['host'].':'.$settings['port'],$errno,$error,10,STREAM_CLIENT_CONNECT,$context);
    if($socket===false)throw new ScMailError($settings['security']==='ssl'?'SMTP SSL/TLS bağlantısı kurulamadı. Sunucu adı, port ve sertifikayı kontrol edin.':'SMTP bağlantısı açılamadı ('.(int)$errno.').');
    stream_set_timeout($socket,10);
    try{
        sc_smtp_reply($socket,[220],'karşılama');$capabilities=sc_smtp_ehlo($socket,$settings);
        if($settings['security']==='tls'){
            if(!array_filter($capabilities,fn($v)=>strtoupper(trim($v))==='STARTTLS'))throw new ScMailError('SMTP sunucusu STARTTLS sunmuyor; şifresiz bağlantıya geçilmedi.');
            sc_smtp_write($socket,"STARTTLS\r\n");sc_smtp_reply($socket,[220],'STARTTLS');
            if(@stream_socket_enable_crypto($socket,true,$crypto)!==true)throw new ScMailError('SMTP STARTTLS/sertifika doğrulaması başarısız.');
            $capabilities=sc_smtp_ehlo($socket,$settings);
        }
        sc_smtp_auth($socket,$settings,$capabilities);
        sc_smtp_write($socket,'MAIL FROM:<'.$message['sender'].">\r\n");sc_smtp_reply($socket,[250],'MAIL FROM');
        sc_smtp_write($socket,'RCPT TO:<'.$message['recipient'].">\r\n");sc_smtp_reply($socket,[250,251],'RCPT TO');
        sc_smtp_write($socket,"DATA\r\n");sc_smtp_reply($socket,[354],'DATA');
        $wire=preg_replace('/(^|\r\n)\./','$1..',$message['message']);
        sc_smtp_write($socket,rtrim($wire,"\r\n")."\r\n.\r\n");sc_smtp_reply($socket,[250],'ileti kabulü');
        // DATA accepted: a missing QUIT reply must not cause a duplicate retry.
        @fwrite($socket,"QUIT\r\n");
    }finally{fclose($socket);}
}
