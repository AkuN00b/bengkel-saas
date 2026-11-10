<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class SetupMechanicPassword extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)->subject('Siapkan sandi mekanik BengkelOS')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Akun mekanik Anda telah dibuat. Buat sandi untuk mulai menggunakan BengkelOS.')
            ->action('Buat sandi', $this->resetUrl($notifiable))
            ->line('Tautan berlaku selama 60 menit dan hanya dapat digunakan sekali.')
            ->line('Jika Anda tidak mengenali permintaan ini, abaikan email ini.');
    }
}
