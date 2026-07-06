<?php

namespace App\Concerns;

use App\Enums\CommunicationChannel;
use App\Models\CommunicationTemplate;
use App\Models\Employee;
use App\Services\Communication\TemplateDispatcher;
use App\Support\TemplatePlaceholders;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

/**
 * Wiederverwendbare Livewire-Logik für den Direkt-Versand von
 * Kommunikationsvorlagen (SMS & E-Mail) – genutzt von der Vorlagen-Seite und
 * vom Krisen-Cockpit. Empfängerauswahl + zweistufige Bestätigung; der
 * eigentliche Versand läuft über {@see TemplateDispatcher}.
 *
 * Das gemeinsame Modal-Markup liegt in
 * resources/views/partials/communication-send-modals.blade.php.
 */
trait SendsCommunicationTemplates
{
    public ?string $smsTemplateId = null;

    /** @var list<string> */
    public array $smsRecipients = [];

    /** @var list<array{to: string, name: string, success: bool, error: ?string}> */
    public array $smsResults = [];

    public bool $smsConfirming = false;

    public ?string $emailTemplateId = null;

    /** @var list<string> */
    public array $emailRecipients = [];

    /** @var list<array{to: string, name: string, success: bool, error: ?string}> */
    public array $emailResults = [];

    public bool $emailConfirming = false;

    // ----- SMS ---------------------------------------------------------------

    public function openSmsSend(string $id): void
    {
        $this->smsTemplateId = $id;
        $this->smsResults = [];
        $this->smsConfirming = false;
        $this->smsRecipients = Employee::query()
            ->whereNotNull('mobile_phone')
            ->where('mobile_phone', '!=', '')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->pluck('id')
            ->all();

        Flux::modal('template-sms-send')->show();
    }

    public function confirmSendSms(): void
    {
        if (empty($this->smsRecipients)) {
            Flux::toast(variant: 'warning', text: __('Keine Empfänger ausgewählt.'));

            return;
        }

        $this->smsConfirming = true;
    }

    public function cancelSendSms(): void
    {
        $this->smsConfirming = false;
    }

    public function sendSms(TemplateDispatcher $dispatcher): void
    {
        if (! $this->smsTemplateId) {
            return;
        }

        $template = CommunicationTemplate::findOrFail($this->smsTemplateId);
        if ($template->channel !== CommunicationChannel::Sms) {
            Flux::toast(variant: 'warning', text: __('Diese Vorlage ist kein SMS-Kanal.'));

            return;
        }

        $out = $dispatcher->sendSms($template, $this->smsRecipients);

        if ($out['results'] === []) {
            Flux::toast(variant: 'warning', text: __('Keine gültigen Empfänger ausgewählt.'));

            return;
        }

        $this->smsResults = $out['results'];
        $this->smsConfirming = false;

        Flux::toast(
            variant: $out['failed'] === 0 ? 'success' : 'warning',
            text: __(':ok von :total SMS verschickt.', ['ok' => $out['sent'], 'total' => count($out['results'])]),
        );
    }

    public function selectAllSmsRecipients(): void
    {
        $this->smsRecipients = $this->smsCandidates->pluck('id')->all();
    }

    public function deselectAllSmsRecipients(): void
    {
        $this->smsRecipients = [];
    }

    /**
     * @return Collection<int, Employee>
     */
    #[Computed]
    public function smsCandidates(): Collection
    {
        return Employee::query()
            ->whereNotNull('mobile_phone')
            ->where('mobile_phone', '!=', '')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    public function smsBodyPreview(): string
    {
        if (! $this->smsTemplateId) {
            return '';
        }

        $template = CommunicationTemplate::find($this->smsTemplateId);

        return $template
            ? TemplatePlaceholders::resolve($template->body, Auth::user()?->currentCompany())
            : '';
    }

    // ----- E-Mail ------------------------------------------------------------

    public function openEmailSend(string $id): void
    {
        $this->emailTemplateId = $id;
        $this->emailResults = [];
        $this->emailConfirming = false;
        $this->emailRecipients = Employee::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->pluck('id')
            ->all();

        Flux::modal('template-email-send')->show();
    }

    public function confirmSendEmail(): void
    {
        if (empty($this->emailRecipients)) {
            Flux::toast(variant: 'warning', text: __('Keine Empfänger ausgewählt.'));

            return;
        }

        $this->emailConfirming = true;
    }

    public function cancelSendEmail(): void
    {
        $this->emailConfirming = false;
    }

    public function sendEmail(TemplateDispatcher $dispatcher): void
    {
        if (! $this->emailTemplateId) {
            return;
        }

        $template = CommunicationTemplate::findOrFail($this->emailTemplateId);
        if ($template->channel !== CommunicationChannel::Email) {
            Flux::toast(variant: 'warning', text: __('Diese Vorlage ist kein E-Mail-Kanal.'));

            return;
        }

        $out = $dispatcher->sendEmail($template, $this->emailRecipients);

        if ($out['results'] === []) {
            Flux::toast(variant: 'warning', text: __('Keine gültigen Empfänger ausgewählt.'));

            return;
        }

        $this->emailResults = $out['results'];
        $this->emailConfirming = false;

        Flux::toast(
            variant: $out['failed'] === 0 ? 'success' : 'warning',
            text: __(':ok von :total E-Mails verschickt.', ['ok' => $out['sent'], 'total' => count($out['results'])]),
        );
    }

    /**
     * @return Collection<int, Employee>
     */
    #[Computed]
    public function emailCandidates(): Collection
    {
        return Employee::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    public function emailBodyPreview(): string
    {
        if (! $this->emailTemplateId) {
            return '';
        }

        $template = CommunicationTemplate::find($this->emailTemplateId);

        return $template
            ? TemplatePlaceholders::resolve($template->body, Auth::user()?->currentCompany())
            : '';
    }

    public function emailSubjectPreview(): string
    {
        if (! $this->emailTemplateId) {
            return '';
        }

        $template = CommunicationTemplate::find($this->emailTemplateId);

        return $template
            ? TemplatePlaceholders::resolve((string) $template->subject, Auth::user()?->currentCompany())
            : '';
    }
}
