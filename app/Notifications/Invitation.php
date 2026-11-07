<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class Invitation extends Notification
{
    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute('activate', now()->addMinutes(60), ['user' => $notifiable->id, 'token' => $this->token]);

        return (new MailMessage)->subject('Aktivasi Akun Portal Akademik Sekolah AKSARA')->greeting('Halo '.$notifiable->name.',')
            ->line('Admin Sekolah telah membuatkan akun AKSARA untuk Anda. Konfirmasi alamat email ini dan buat kata sandi baru untuk mengaktifkan akun.')
            ->action('Aktifkan Akun', $url)->line('Tautan undangan ini berlaku selama 60 menit. Hubungi Admin Sekolah jika Anda memerlukan pengiriman ulang tautan.');
    }
}
