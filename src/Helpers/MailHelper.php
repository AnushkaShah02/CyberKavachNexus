<?php

namespace CyberKavach\Nexus\Helpers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailHelper
{
    public static function sendCertificate(
    string $email,
    string $name,
    string $pdfPath
): bool {

    $mail = new PHPMailer(true);

    $mail->SMTPDebug = 2;
    $mail->Debugoutput = 'html';

    try {

        $mail->isSMTP();

            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;

            $mail->Username =
                'nexcyberkavach@gmail.com';

            $mail->Password =
                'hgbt czzw xaqh bcqm';

            $mail->SMTPSecure =
                PHPMailer::ENCRYPTION_STARTTLS;

            $mail->Port = 587;

            $mail->setFrom(
                'nexcyberkavach@gmail.com',
                'CyberKavach Nexus'
            );

            $mail->addAddress(
                $email,
                $name
            );

            $mail->Subject =
                'CyberKavach Certificate';

            $mail->Body =
                "Dear {$name},

Please find your certificate attached.

Regards,
CyberKavach Nexus";

            $mail->addAttachment($pdfPath);

            return $mail->send();

        } catch (Exception $e) {

    die(
        "MAIL ERROR:<br>" .
        $mail->ErrorInfo .
        "<br><br>" .
        $e->getMessage()
    );
}
    }
}