<?php

namespace App\Helpers;

use App\Models\EmailManagement;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\Log;

class MailHelper
{
    /**
     * Send an email using PHPMailer and DB-stored credentials.
     *
     * @param string $to
     * @param string $subject
     * @param string $body (HTML allowed)
     * @param array $attachments (optional file paths)
     * @return bool
     */
    public static function sendMail($to, $subject, $body, $attachments = [])
    {
        try {
            $mailSetting = EmailManagement::first();

            if (!$mailSetting) {
                Log::error('MailHelper: No email settings found in database.');
                return false;
            }

            $mail = new PHPMailer(true);

            // === SMTP CONFIGURATION ===
            $mail->isSMTP();
            $mail->Host       =  'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $mailSetting->email;
            $mail->Password   = $mailSetting->password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $mailSetting->port ?? 587;

            // === SENDER ===
            $mail->setFrom($mailSetting->email,  'GYM Management System');

            // === RECIPIENT ===
            $mail->addAddress($to);

            // === ATTACHMENTS (optional) ===
            foreach ($attachments as $filePath) {
                if (file_exists($filePath)) {
                    $mail->addAttachment($filePath);
                } else {
                    Log::warning("MailHelper: Attachment not found at path {$filePath}");
                }
            }

            // === CONTENT ===
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();

            Log::info("MailHelper: Email sent successfully to {$to} | Subject: {$subject}");
            return true;

        } catch (Exception $e) {
            Log::error('MailHelper Exception: ' . $e->getMessage());
            return false;
        } catch (\Throwable $e) {
            Log::error('MailHelper Error: ' . $e->getMessage());
            return false;
        }
    }
}
