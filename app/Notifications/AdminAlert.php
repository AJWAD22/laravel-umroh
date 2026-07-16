<?php

namespace App\Notifications;

use App\Notifications\Channels\BranchDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

abstract class AdminAlert extends Notification
{
    use Queueable;

    /*
     * Class dasar untuk notifikasi admin.
     *
     * Class ini dipakai oleh notifikasi lain yang ingin mengirim alert
     * ke admin, misalnya SOS jamaah atau status monitoring tertentu.
     */
    public function __construct(
        // ID cabang dipakai agar notifikasi hanya masuk ke cabang terkait.
        public readonly int $branchId,

        // Isi notifikasi yang nantinya disimpan ke database.
        protected readonly array $payload,
    ) {}

    /*
     * Menentukan channel pengiriman notifikasi.
     *
     * Di sistem ini notifikasi admin disimpan melalui BranchDatabaseChannel,
     * supaya data notifikasi bisa difilter berdasarkan cabang.
     */
    public function via(object $notifiable): array
    {
        return [BranchDatabaseChannel::class];
    }

    /*
     * Mengubah payload menjadi data database.
     *
     * Laravel akan memanggil method ini ketika notifikasi disimpan.
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->payload;
    }
}
