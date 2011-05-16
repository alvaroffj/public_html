<?php
include_once 'modelo/class.phpmailer-lite.php';

$email = "no-reply@gpsline.cl";
$mail = new PHPMailerLite();
$mail->IsMail();
$mail->SetFrom($email, "GPSLine");
$mail->Subject = "GPSLine - Alerta activada";
$mail->AddAddress("super.neeph@gmail.com", "super.neeph@gmail.com");
$mail->AddAddress("cmorales@mki.cl", "cmorales@mki.cl");
$mail->AddAddress("cmorales@ipline.cl", "cmorales@ipline.cl");
$html = "lala";
$mail->MsgHTML($html);
echo $html."<br>";
$mail->Send();

?>

