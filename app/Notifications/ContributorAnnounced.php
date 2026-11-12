<?php

namespace App\Notifications;

use App\Models\ContributorAward;
use Illuminate\Notifications\Notification;

class ContributorAnnounced extends Notification
{
    public function __construct(private ContributorAward $award)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $label = ContributorAward::labelFor($this->award->period);

        return [
            'type'    => 'contributor_announced',
            'period'  => $this->award->period,
            'rank'    => $this->award->rank,
            'title'   => "Selamat, kamu Bintang Mading {$label}!",
            'message' => "Kamu peringkat {$this->award->rank} dengan {$this->award->posts_count} karya "
                . "dan {$this->award->likes_count} suka. Lihat podiumnya di Beranda.",
            'url'     => '/user/beranda',
        ];
    }
}