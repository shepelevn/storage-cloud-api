<?php

declare(strict_types=1);

namespace Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use RuntimeException;

class MailerService
{
    private string $isDebug;
    private string $smtpUsername;
    private string $smtpPassword;
    private string $smtpHost;
    private string $smtpPort;
    private string $smtpSecure;

    public function __construct(ConfigService $configService)
    {
        $mainConfig = $configService->mainConfig;
        $smtpConfig = $configService->smtpConfig;

        $this->isDebug = $mainConfig['DEBUG'];
        $this->smtpUsername = $smtpConfig['SMTP_USERNAME'];
        $this->smtpPassword = $smtpConfig['SMTP_PASSWORD'];
        $this->smtpHost = $smtpConfig['SMTP_HOST'];
        $this->smtpPort = $smtpConfig['SMTP_PORT'];
        $this->smtpSecure = $smtpConfig['SMTP_SECURE'];
    }

    private function createMailer(): PHPMailer
    {
        $mailer = new PHPMailer();

        $mailer->setLanguage('ru');
        $mailer->CharSet = 'utf-8';
        $mailer->isSMTP();
        $mailer->isHTML(false);

        if ($this->isDebug) {
            $mailer->SMTPDebug = SMTP::DEBUG_SERVER;
        } else {
            $mailer->SMTPDebug = SMTP::DEBUG_OFF;
        }

        $mailer->Host = $this->smtpHost;
        $mailer->Port = $this->smtpPort;

        if ($this->smtpSecure) {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mailer->SMTPAuth = true;
        }

        $mailer->Username = $this->smtpUsername;
        $mailer->Password = $this->smtpPassword;

        return $mailer;
    }

    public function sendEmail(string $author, string $subject, string $text, string $to): void
    {
        $mailer = $this->createMailer();

        $mailer->setFrom($this->smtpUsername, $author);

        $mailer->addAddress($to);

        $mailer->Subject = $subject;

        $mailer->Body = $text;

        if (!$mailer->send()) {
            throw new RuntimeException('Ошибка при отправке сообщения: ' . $mailer->ErrorInfo);
        }
    }
}
