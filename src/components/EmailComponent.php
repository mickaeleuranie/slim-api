<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\components;

use api\Api;
use api\exceptions\ApiException;
use PHPMailer\PHPMailer\PHPMailer;

class EmailComponent
{

    /**
     * Send mail generic function
     * @todo handle reply to field
     * @param string $to
     * @param string $subject
     * @param array $headersSpecific
     * @param string $from
     * @param string $fromName
     * @return boolean
     */
    public static function send($to, $subject, $message, $headersSpecific = [], $from = null, $fromName = null)
    {
        // don't send mail if not in preproduction or production
        if (!in_array(getenv('ENV'), ['preprod', 'preproduction', 'prod', 'production']) || 'true' !== getenv('MAIL_ENABLED')) {
            return false;
        }

        if (empty($from)) {
            $from = getenv('EMAIL_FROM');
        }

        if (empty($fromName)) {
            $fromName = getenv('EMAIL_FROM_NAME');
        }

        // send from PHPMailer
        $mail             = new PHPMailer;

        $mail->IsSMTP(); // telling the class to use SMTP

        // // Debug
        // // -----
        // if (in_array(ENV, ['local', 'development'])) {
        //     $mail->SMTPDebug  = 2;                     // enables SMTP debug information (for testing)
        //                                                // 1 = errors and messages
        //                                                // 2 = messages only
        //     $mail->Debugoutput = 'html';
        // }
        // // \Debug
        // // ------

        // send from GMAIL if environment variables are set
        if (!empty(getenv('GMAIL_USERNAME', null)) && !empty(getenv('GMAIL_PASSWORD', null))) {
            $mail->SMTPAuth   = true;                  // enable SMTP authentication
            $mail->SMTPSecure = 'ssl';                 // sets the prefix to the servier
            $mail->Host       = 'smtp.gmail.com';      // sets GMAIL as the SMTP server
            $mail->Port       = 465;                   // set the SMTP port for the GMAIL server
            $mail->Username   = getenv('GMAIL_USERNAME');  // GMAIL username
            $mail->Password   = getenv('GMAIL_PASSWORD');  // GMAIL password
        }

        $mail->setFrom($from, $fromName);
        $mail->addAddress($to);     // Add a recipient
        $mail->addReplyTo(getenv('EMAIL_REPLY_TO'), Api::t('label.no_reply'));
        // $mail->addCC('cc@example.com');
        // $mail->addBCC('bcc@example.com');

        $mail->isHTML(true);
        $mail->Encoding = 'base64';
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body = $message;

        if (!$mail->send()) {
            throw new ApiException(Api::t('error.email.send', ['%info%' => $mail->ErrorInfo]));
        }

        return true;
    }
}
