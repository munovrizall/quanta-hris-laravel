<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PenggajianSubmittedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly string $submittedBy,
        private readonly string $periodeLabel,
        private readonly int $recordsUpdated,
        private readonly int $periodeBulan,
        private readonly int $periodeTahun,
    ) {
    }

    /**
     * Create a new notification instance.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Pengajuan Draf Penggajian',
            'message' => sprintf(
                '%s mengajukan draf penggajian periode %s (total %d data).',
                $this->submittedBy,
                $this->periodeLabel,
                $this->recordsUpdated
            ),
            'submitted_by' => $this->submittedBy,
            'periode' => $this->periodeLabel,
            'records_updated' => $this->recordsUpdated,
            'periode_bulan' => $this->periodeBulan,
            'periode_tahun' => $this->periodeTahun,
        ];
    }
}
