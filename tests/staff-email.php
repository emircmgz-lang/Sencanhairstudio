<?php
require __DIR__.'/../private/bootstrap.php';
require __DIR__.'/../private/mail.php';
function check($ok,$label){if(!$ok)throw new RuntimeException($label);echo "OK: $label\n";}
$business=sc_initial_business();
$business['barbers'][0]=sc_barber(['email'=>'  kadir@example.com  '],$business['barbers'][0]);
$business['barbers'][1]=sc_barber(['email'=>'davut@example.com'],$business['barbers'][1]);
check($business['barbers'][0]['email']==='kadir@example.com','Email trimmed and saved');
try {sc_barber(['email'=>'broken-address'],$business['barbers'][0]);throw new RuntimeException('Invalid email accepted');}catch(ScError $e){check($e->getCode()===400,'Invalid email rejected');}
check(sc_mail_recipient($business,1)==='kadir@example.com','Selected employee recipient');
check(sc_mail_recipient($business,2)==='davut@example.com','Other employee independent');
check(sc_mail_recipient($business,3)===null,'Empty email disables recipient');
$public=sc_public_barbers($business['barbers']);check(!str_contains(json_encode($public),'example.com'),'Public employee profiles hide emails');
check($business['barbers'][0]['email']==='kadir@example.com','Public filtering preserves admin data');
$booking=['barberId'=>1,'name'=>'Test','phone'=>'05000000000','date'=>'2026-11-01','time'=>'18:00','services'=>[['name'=>'Saç']],'total'=>600,'priceFrom'=>false,'code'=>'TESTCODE'];
$c=['mail_from'=>'info@sencanhairstudio.com','mail_to'=>['wrong@example.com']];
check(sc_mail_payload($c,$booking,$business)['to']===['kadir@example.com'],'Delivery payload only targets booked employee');
$business['barbers'][0]=sc_barber(['email'=>'new@example.com'],$business['barbers'][0]);
check(sc_mail_payload($c,$booking,$business)['to']===['new@example.com'],'Saved email change changes recipient');
$business['barbers'][0]=sc_barber(['email'=>''],$business['barbers'][0]);check(sc_mail_recipient($business,1)===null,'Removing email stops notifications');
check(!sc_mail_ready(['mail_enabled'=>false]),'Disabled transport stays disabled');
check(!sc_mail_ready(['mail_enabled'=>true]),'Incomplete transport does not block appointments');
