<?php

namespace App\Notifications;

use App\Models\IncidentReport;
use App\Models\IncidentReportObligation;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportingDeadlineApproaching extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public IncidentReport $report,
        public IncidentReportObligation $obligation,
        public CarbonInterface $deadline,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = $this->obligation->obligation->label();
        $hoursLeft = max(0, (int) ceil(Carbon::now()->diffInHours($this->deadline, false)));
        $isOverdue = $this->deadline->isPast();

        $message = (new MailMessage)
            ->subject(__('Meldepflicht-Frist läuft: :obligation', ['obligation' => $label]))
            ->greeting(__('Hallo :name,', ['name' => $notifiable->name]))
            ->line(__('Für den Vorfall „:title" läuft eine gesetzliche Meldefrist ab.', [
                'title' => $this->report->title,
            ]))
            ->line(__('Meldepflicht: :obligation', ['obligation' => $label]))
            ->line(__('Frist: :date Uhr', ['date' => $this->deadline->translatedFormat('d.m.Y H:i')]));

        if ($isOverdue) {
            $message->line(__('Achtung: Die Frist ist bereits abgelaufen.'));
        } else {
            $message->line(__('Verbleibende Zeit: ca. :hours Stunden.', ['hours' => $hoursLeft]));
        }

        $message->line(__('Bitte erledigen Sie die Meldung umgehend und markieren Sie die Meldepflicht auf der Vorfall-Detailseite als gemeldet.'));

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'incident_report_id' => $this->report->id,
            'obligation' => $this->obligation->obligation->value,
            'deadline' => $this->deadline->toIso8601String(),
        ];
    }
}
