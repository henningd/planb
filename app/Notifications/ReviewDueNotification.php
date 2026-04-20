<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Company $company) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $due = $this->company->reviewDueAt();
        $teamSlug = $this->company->team?->slug;

        $message = (new MailMessage)
            ->subject(__('Notfallhandbuch prüfen: :company', ['company' => $this->company->name]))
            ->greeting(__('Hallo :name,', ['name' => $notifiable->name]))
            ->line(__('Ihr Notfallhandbuch für :company steht zur regelmäßigen Überprüfung an.', [
                'company' => $this->company->name,
            ]))
            ->line(__('Letzte Bestätigung: :date', [
                'date' => $this->company->last_reviewed_at?->translatedFormat('d.m.Y') ?? __('noch keine'),
            ]))
            ->line(__('Fällig seit: :date', [
                'date' => $due?->translatedFormat('d.m.Y') ?? __('unbekannt'),
            ]))
            ->line(__('Bitte prüfen Sie Ansprechpartner, Systeme und Dienstleister und bestätigen Sie, dass die Einträge noch aktuell sind. Wenn etwas veraltet ist, passen Sie es an – das dauert meist nur wenige Minuten.'));

        if ($teamSlug) {
            $message->action(__('Handbuch prüfen'), url("/{$teamSlug}/dashboard"));
        }

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'company_id' => $this->company->id,
            'company_name' => $this->company->name,
        ];
    }
}
