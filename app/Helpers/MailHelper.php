<?php

namespace App\Helpers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailHelper
{
    public static function sendMail($to, $subject, $body)
    {
        try {
            $mail = new PHPMailer(true);
            $mail->isMail(); // Usar la función mail() en lugar de SMTP
            $mail->setFrom('no-reply@tu-dominio.com', 'Tu Aplicación');
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $body;

            return $mail->send();
        } catch (Exception $e) {
            return "Error: {$mail->ErrorInfo}";
        }
    }
}
