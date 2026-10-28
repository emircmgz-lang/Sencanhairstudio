<?php
declare(strict_types=1);

final class ScMailError extends RuntimeException {}

function sc_smtp_settings(array $c): array {
    $host=$c['smtp_host']??'mail.sencanhairstudio.com';
    $port=$c['smtp_port']??25;
    // This adapter intentionally implements only the requested no-auth, no-TLS setup; port 25 is the configurable default.
    if(!is_string($host)||!preg_match('/^(?:localhost|[a-zA-Z0-9](?:[a-zA-Z0-9.-]*[a-zA-Z0-9])?)$/D',$host)||strlen($host)>253
        ||filter_var($port,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>65535]])===false
        ||!empty($c['smtp_username'])||!empty($c['smtp_password'])||!in_array($c['smtp_security']??'none',['none',''],true)) {
        throw new ScMailError('SMTP ayarları geçersiz. Bu sürüm şifresiz ve şifrelemesiz bağlantı içindir.');
    }
    $helo=parse_url($c['public_origin']??'https://sencanhairstudio.com',PHP_URL_HOST);
    if(!is_string($helo)||!preg_match('/^[a-zA-Z0-9.-]+$/D',$helo))$helo='sencanhairstudio.com';
    return ['host'=>$host,'port'=>(int)$port,'helo'=>$helo];
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

function sc_smtp_reply($socket,array $expected,string $stage): int {
    $code=null;
    for($i=0;$i<100;$i++){
        $line=@fgets($socket,1024);
        if($line===false){$meta=stream_get_meta_data($socket);throw new ScMailError($meta['timed_out']?'SMTP '.$stage.' yanıtı zaman aşımına uğradı.':'SMTP '.$stage.' sırasında bağlantı kesildi.');}
        if(!preg_match('/^(\d{3})([ -])[^\r\n]*\r?\n$/D',$line,$m))throw new ScMailError('SMTP '.$stage.' yanıtı geçersiz.');
        $lineCode=(int)$m[1];if($code!==null&&$code!==$lineCode)throw new ScMailError('SMTP '.$stage.' çok satırlı yanıtı geçersiz.');$code=$lineCode;
        if($m[2]===' '){if(!in_array($code,$expected,true))throw new ScMailError('SMTP '.$stage.' reddedildi ('.$code.').');return $code;}
    }
    throw new ScMailError('SMTP '.$stage.' yanıtı çok uzun.');
}

function sc_send_smtp(array $c,array $payload,string $jobId): void {
    $settings=sc_smtp_settings($c);$message=sc_smtp_message($payload,$jobId);
    $socket=@stream_socket_client('tcp://'.$settings['host'].':'.$settings['port'],$errno,$error,10,STREAM_CLIENT_CONNECT);
    if($socket===false)throw new ScMailError('SMTP bağlantısı açılamadı ('.(int)$errno.').');
    stream_set_timeout($socket,10);
    try{
        sc_smtp_reply($socket,[220],'karşılama');
        sc_smtp_write($socket,'EHLO '.$settings['helo']."\r\n");
        $greeting=sc_smtp_reply($socket,[250,500,502,504],'EHLO');
        if($greeting!==250){sc_smtp_write($socket,'HELO '.$settings['helo']."\r\n");sc_smtp_reply($socket,[250],'HELO');}
        sc_smtp_write($socket,'MAIL FROM:<'.$message['sender'].">\r\n");sc_smtp_reply($socket,[250],'MAIL FROM');
        sc_smtp_write($socket,'RCPT TO:<'.$message['recipient'].">\r\n");sc_smtp_reply($socket,[250,251],'RCPT TO');
        sc_smtp_write($socket,"DATA\r\n");sc_smtp_reply($socket,[354],'DATA');
        $wire=preg_replace('/(^|\r\n)\./','$1..',$message['message']);
        sc_smtp_write($socket,rtrim($wire,"\r\n")."\r\n.\r\n");
        sc_smtp_reply($socket,[250],'ileti kabulü');
        // Once DATA is accepted, a missing QUIT reply must not cause a duplicate retry.
        @fwrite($socket,"QUIT\r\n");
    }finally{fclose($socket);}
}
