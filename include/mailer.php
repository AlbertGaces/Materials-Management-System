<?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    require 'phpmailer/src/Exception.php';
    require 'phpmailer/src/PHPMailer.php';
    require 'phpmailer/src/SMTP.php';

    function send_mail($recipient,$subject,$message) {
        $mail = new PHPMailer();
        $mail->IsSMTP();

        $mail->SMTPDebug  = 0;  
        $mail->SMTPAuth   = TRUE;
        $mail->SMTPSecure = "tls";
        $mail->Port       = 587;
        $mail->Host       = "smtp.gmail.com";
        $mail->Username   = "jjfabricationmms@gmail.com";
        $mail->Password   = "jjfabmms2022!";

        $mail->IsHTML(true);
        $mail->AddAddress($recipient, "Admin Password Reset");
        $mail->SetFrom("jjfabricationmms@gmail.com", "JJ Fabrication MMS");
        $mail->Subject = $subject;
        $content = $message;

        $mail->MsgHTML($content); 
        if(!$mail->Send()) {
            return false;
        } else {
            return true;
        }
    }
?>