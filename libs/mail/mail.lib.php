<?php

require_once('vendor/PHPMailerAutoload.php');

class mail
{
    public $error = false;
    public $return = false;

    public function send($from_name, $to_mail, $to_name, $subject, $message)
    {
        // INVOKE
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->Host = 'smtp.yandex.com';
        $mail->Port = 465;
        $mail->SMTPSecure = true;
        $mail->SMTPAuth = true;
        $mail->IsHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Username = 'no-reply@appixar.com';
        $mail->Password = 'rehruurzvhwdhjvo'; // app password
        $mail->setFrom('no-reply@appixar.com', $from_name);
        $mail->addReplyTo('contato@qmoleza.com.br', 'Contato Qmoleza');
        $mail->addAddress($to_mail, $to_name);
        $mail->Subject = $subject;
        $mail->Body = $message;

        // CB
        if (!$mail->send()) {
            $this->error = $mail->ErrorInfo;
            return false;
        }
        return true;
    }
}
