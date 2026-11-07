<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCode extends Notification
{
    public function __construct(public string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode Verifikasi Dua Faktor (2FA) - AKSARA')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Berikut adalah kode verifikasi 6 digit untuk masuk ke akun AKSARA Anda:')
            ->line('**'.$this->code.'**')
            ->line('Kode ini berlaku selama 10 menit. Jangan bagikan kode ini kepada siapa pun.')
            ->salutation('Salam hangat, Tim Akademik AKSARA');
    }

    public function toArray(object $notifiable): array
    {
        return ['code' => $this->code];
    }
}

